(function() {
    'use strict';

    const config = window.svdpCashierShell || {};
    const itemValues = window.svdpVouchers?.itemValues || { adult: 5.00, child: 3.00 };
    const cashierCopy = (config.copy && config.copy.cashier) || {};
    const coatCopy = (config.copy && config.copy.coat) || {};
    const defaultVisibleCount = Number(config.defaultVisibleCount || 5);

    let keepaliveTimer = null;
    let pendingEmergencyAction = null;
    let shouldScrollDetailOnSwap = false;

    document.addEventListener('DOMContentLoaded', function() {
        const shell = document.querySelector('[data-svdp-cashier-shell]');
        if (!shell) {
            return;
        }

        ensureStore();
        bindShellEvents();
        loadOverrideReasons();
        startKeepalive();
    });

    function ensureStore() {
        if (!window.Alpine) {
            return null;
        }

        const existing = window.Alpine.store('cashier');
        if (existing) {
            return existing;
        }

        window.Alpine.store('cashier', {
            sessionLost: false,
            keepaliveState: 'idle',
            keepaliveLabel: cashierText('connecting'),
            emergencyOpen: false,
            activePanel: null,
            overrideOpen: false,
            selectedVoucherId: null
        });

        return window.Alpine.store('cashier');
    }

    function cashierStore() {
        return window.Alpine ? window.Alpine.store('cashier') : null;
    }

    function bindShellEvents() {
        document.body.addEventListener('click', handleClick);
        document.body.addEventListener('submit', handleSubmit);
        document.body.addEventListener('input', handleInput, true);
        document.body.addEventListener('change', handleChange, true);
        document.body.addEventListener('htmx:configRequest', handleHtmxConfig);
        document.body.addEventListener('htmx:afterSwap', handleHtmxAfterSwap);
        document.body.addEventListener('htmx:responseError', handleHtmxError);
    }

    function handleClick(event) {
        const showMoreButton = event.target.closest('[data-cashier-show-more]');
        if (showMoreButton) {
            event.preventDefault();
            showMoreVouchers(showMoreButton);
            return;
        }

        const voucherCard = event.target.closest('[data-voucher-card]');
        if (voucherCard) {
            shouldScrollDetailOnSwap = true;
            setSelectedVoucher(voucherCard.getAttribute('data-voucher-id'));
            return;
        }

        if (event.target.closest('#svdpCancelOverride')) {
            event.preventDefault();
            cancelOverride();
            return;
        }

        if (event.target.closest('#svdpConfirmOverride')) {
            event.preventDefault();
            confirmOverride();
            return;
        }

        const similarAction = event.target.closest('[data-similar-action]');
        if (similarAction) {
            event.preventDefault();
            handleSimilarAction(similarAction.getAttribute('data-similar-action'));
        }
    }

    function handleSubmit(event) {
        const form = event.target;
        if (!(form instanceof HTMLFormElement)) {
            return;
        }

        const action = form.getAttribute('data-cashier-action');
        if (!action) {
            return;
        }

        event.preventDefault();

        if (action === 'redeem') {
            submitRedemption(form);
            return;
        }

        if (action === 'coat') {
            submitCoatIssuance(form);
            return;
        }

        if (action === 'emergency') {
            submitEmergencyVoucher(form);
            return;
        }

        if (action === 'furniture-photo') {
            submitFurniturePhoto(form);
            return;
        }

        if (action === 'furniture-complete') {
            submitFurnitureComplete(form);
            return;
        }

        if (action === 'furniture-substitute') {
            submitFurnitureSubstitute(form);
            return;
        }

        if (action === 'furniture-cancel') {
            submitFurnitureCancel(form);
            return;
        }

        if (action === 'furniture-voucher-complete') {
            submitFurnitureVoucherComplete(form);
            return;
        }

        if (action === 'voucher-correct') {
            submitVoucherCorrection(form);
        }
    }

    function handleInput(event) {
        if (event.target && event.target.id === 'svdpCashierSearch') {
            resetVisibleCount();
        }

        const form = event.target.closest('form[data-cashier-action]');
        if (!form) {
            return;
        }

        const action = form.getAttribute('data-cashier-action');
        if (action === 'redeem') {
            updateRedemptionSummary(form);
            return;
        }

        if (action === 'coat') {
            updateCoatSummary(form);
            return;
        }

        if (action === 'furniture-complete') {
            validateFurnitureCompleteForm(form);
        }
    }

    function handleChange(event) {
        if (!event.target) {
            return;
        }

        if (event.target.id === 'svdpCashierFilter' || event.target.id === 'svdpCashierSort') {
            resetVisibleCount();
        }
    }

    function handleHtmxConfig(event) {
        if (!event.detail || typeof event.detail.path !== 'string') {
            return;
        }

        if (event.detail.path.indexOf('/svdp/v1/') !== -1) {
            event.detail.headers['X-WP-Nonce'] = config.nonce || '';
        }
    }

    function handleHtmxAfterSwap(event) {
        if (!event.target) {
            return;
        }

        if (event.target.id === 'svdpCashierDetailPanel') {
            const detail = event.target.querySelector('[data-current-voucher-id]');
            if (detail) {
                setSelectedVoucher(detail.getAttribute('data-current-voucher-id'));
            }

            const redeemForm = event.target.querySelector('form[data-cashier-action="redeem"]');
            if (redeemForm) {
                updateRedemptionSummary(redeemForm);
            }

            const coatForm = event.target.querySelector('form[data-cashier-action="coat"]');
            if (coatForm) {
                updateCoatSummary(coatForm);
            }

            maybeScrollDetailIntoView(event.target);
        }
    }

    function handleHtmxError(event) {
        const xhr = event.detail ? event.detail.xhr : null;
        if (!xhr) {
            return;
        }

        if (xhr.status === 401 || xhr.status === 403) {
            handleSessionLost();
        }
    }

    function setSelectedVoucher(voucherId) {
        const selectedId = String(voucherId || '');
        const hiddenInput = document.getElementById('svdpSelectedVoucherId');
        if (hiddenInput) {
            hiddenInput.value = selectedId;
        }

        const store = cashierStore();
        if (store) {
            store.selectedVoucherId = selectedId;
        }

        document.querySelectorAll('[data-voucher-card]').forEach(function(card) {
            card.classList.toggle('is-selected', card.getAttribute('data-voucher-id') === selectedId);
        });
    }

    function startKeepalive() {
        pingSession();
        keepaliveTimer = window.setInterval(pingSession, Number(config.pingInterval || 60000));
    }

    async function pingSession() {
        const store = cashierStore();
        if (store) {
            store.keepaliveState = 'working';
            store.keepaliveLabel = cashierText('keepingSessionLive');
        }

        try {
            const payload = await requestJSON(config.restUrl + 'svdp/v1/cashier/ping', {
                method: 'POST'
            });

            if (payload.nonce) {
                config.nonce = payload.nonce;
            }

            if (store) {
                store.keepaliveState = 'ready';
                store.keepaliveLabel = cashierText('shellLive');
            }
        } catch (error) {
            if (error.status === 401 || error.status === 403) {
                handleSessionLost();
                return;
            }

            if (store) {
                store.keepaliveState = 'warning';
                store.keepaliveLabel = cashierText('connectionRetrying');
            }
        }
    }

    function handleSessionLost() {
        const store = cashierStore();
        if (store) {
            store.sessionLost = true;
            store.keepaliveState = 'expired';
            store.keepaliveLabel = cashierText('reauthRequired');
        }

        if (keepaliveTimer) {
            window.clearInterval(keepaliveTimer);
            keepaliveTimer = null;
        }
    }

    async function loadOverrideReasons() {
        try {
            const reasons = await requestJSON(config.restUrl + 'svdp/v1/override-reasons');
            const select = document.getElementById('svdpOverrideReason');
            if (!select) {
                return;
            }

            select.innerHTML = '<option value="">' + escapeHtml(cashierText('overrideReasonPlaceholder')) + '</option>';
            reasons.forEach(function(reason) {
                const option = document.createElement('option');
                option.value = String(reason.id);
                option.textContent = reason.reason_text;
                select.appendChild(option);
            });
        } catch (error) {
            showFlash('error', cashierText('overrideReasonsLoadFailed'));
        }
    }

    async function submitRedemption(form) {
        if (!validateRedemptionForm(form)) {
            return;
        }

        const voucherId = form.getAttribute('data-voucher-id');
        const submitButton = form.querySelector('button[type="submit"]');
        const originalLabel = submitButton.textContent;

        setButtonState(submitButton, true, cashierText('saving'));

        try {
            const payload = {
                status: 'Redeemed',
                items_adult: parseNumber(form.elements.items_adult.value),
                items_children: parseNumber(form.elements.items_children.value)
            };

            const response = await requestJSON(config.restUrl + 'svdp/v1/vouchers/' + voucherId + '/status', {
                method: 'PATCH',
                data: payload
            });

            collapsePanels();
            showFlash('success', formatText(cashierText('voucherRedeemedSuccessTemplate'), [response.redemption_value]));
            refreshShell(voucherId);
        } catch (error) {
            showInlineError(form, extractErrorMessage(error, cashierText('voucherRedeemFailed')));
        } finally {
            setButtonState(submitButton, false, originalLabel);
        }
    }

    async function submitCoatIssuance(form) {
        if (!validateCoatForm(form)) {
            return;
        }

        const voucherId = form.getAttribute('data-voucher-id');
        const submitButton = form.querySelector('button[type="submit"]');
        const originalLabel = submitButton.textContent;

        setButtonState(submitButton, true, cashierText('saving'));

        try {
            const payload = {
                adults: parseNumber(form.elements.adults.value),
                children: parseNumber(form.elements.children.value)
            };

            const response = await requestJSON(config.restUrl + 'svdp/v1/vouchers/' + voucherId + '/coat', {
                method: 'PATCH',
                data: payload
            });

            collapsePanels();
            showFlash('success', formatText(cashierText('coatIssueSuccessTemplate'), [response.total]));
            refreshShell(voucherId);
        } catch (error) {
            showInlineError(form, extractErrorMessage(error, cashierText('coatIssueFailed')));
        } finally {
            setButtonState(submitButton, false, originalLabel);
        }
    }

    async function submitEmergencyVoucher(form) {
        const messageBox = document.getElementById('svdpEmergencyMessage');
        const submitButton = form.querySelector('button[type="submit"]');
        const originalLabel = submitButton.textContent;
        const formData = collectEmergencyFormData(form);

        clearSectionMessage(messageBox);
        setButtonState(submitButton, true, cashierText('checking'));

        try {
            const duplicateResponse = await requestJSON(config.restUrl + 'svdp/v1/vouchers/check-duplicate', {
                method: 'POST',
                data: {
                    firstName: formData.firstName,
                    lastName: formData.lastName,
                    dob: formData.dob,
                    voucherType: 'clothing',
                    createdBy: 'Cashier'
                }
            });

            if (duplicateResponse.found && duplicateResponse.matchType === 'exact') {
                pendingEmergencyAction = {
                    formData: formData,
                    duplicateData: duplicateResponse
                };
                openOverrideModal(duplicateResponse);
                return;
            }

            if (duplicateResponse.found && duplicateResponse.matchType === 'similar') {
                pendingEmergencyAction = {
                    formData: formData,
                    duplicateData: duplicateResponse
                };
                showSimilarWarning(messageBox, duplicateResponse);
                return;
            }

            await createEmergencyVoucher(form, formData);
        } catch (error) {
            showSectionMessage(messageBox, extractErrorMessage(error, cashierText('emergencyCreateFailed')), 'error');
        } finally {
            setButtonState(submitButton, false, originalLabel);
        }
    }

    async function createEmergencyVoucher(form, formData) {
        const messageBox = document.getElementById('svdpEmergencyMessage');
        const response = await requestJSON(config.restUrl + 'svdp/v1/vouchers/create', {
            method: 'POST',
            data: formData
        });

        form.reset();
        pendingEmergencyAction = null;
        clearSectionMessage(messageBox);

        const store = cashierStore();
        if (store) {
            store.emergencyOpen = false;
        }

        showFlash('success', cashierText('emergencyCreateSuccess'));
        if (response.voucher_id) {
            refreshShell(response.voucher_id);
        } else {
            window.htmx.trigger(document.body, 'svdp:list-refresh');
        }
    }

    function openOverrideModal(duplicateData) {
        const store = cashierStore();
        const message = document.getElementById('svdpOverrideMessage');
        const managerCode = document.getElementById('svdpManagerCode');
        const managerName = document.getElementById('svdpManagerName');
        const reasonSelect = document.getElementById('svdpOverrideReason');

        if (message) {
            message.innerHTML = '<strong>' + escapeHtml(duplicateData.firstName + ' ' + duplicateData.lastName) + '</strong> already has a clothing voucher.<br><br>' +
                '<strong>Conference:</strong> ' + escapeHtml(duplicateData.conference) + '<br>' +
                '<strong>Created:</strong> ' + escapeHtml(duplicateData.voucherCreatedDate) + '<br>' +
                '<strong>Next Eligible:</strong> ' + escapeHtml(duplicateData.nextEligibleDate);
        }

        if (managerCode) {
            managerCode.value = '';
        }

        if (managerName) {
            managerName.value = '';
        }

        if (reasonSelect) {
            reasonSelect.value = '';
        }

        if (store) {
            store.overrideOpen = true;
        }
    }

    function cancelOverride() {
        const store = cashierStore();
        if (store) {
            store.overrideOpen = false;
        }

        if (pendingEmergencyAction) {
            saveDeniedVoucher(pendingEmergencyAction.formData, pendingEmergencyAction.duplicateData);
        }

        pendingEmergencyAction = null;
    }

    async function confirmOverride() {
        const store = cashierStore();
        const managerCode = document.getElementById('svdpManagerCode');
        const managerName = document.getElementById('svdpManagerName');
        const reasonSelect = document.getElementById('svdpOverrideReason');
        const emergencyForm = document.getElementById('svdpEmergencyForm');

        if (!pendingEmergencyAction || !managerCode || !managerName || !reasonSelect || !emergencyForm) {
            return;
        }

        const enteredCode = managerCode.value.trim().toUpperCase();
        const enteredName = managerName.value.trim();
        const selectedReason = reasonSelect.value;

        if (!/^[A-Z2-9]{4}$/.test(enteredCode)) {
            showFlash('error', cashierText('managerCodeInvalid'));
            return;
        }

        if (!enteredName) {
            showFlash('error', cashierText('overrideValidationFailed'));
            return;
        }

        if (!selectedReason) {
            showFlash('error', cashierText('overrideReasonRequired'));
            return;
        }

        try {
            const validation = await requestJSON(config.restUrl + 'svdp/v1/managers/validate', {
                method: 'POST',
                data: {
                    code: enteredCode,
                    managerName: enteredName,
                    reasonId: selectedReason,
                    context: 'override_validation'
                }
            });

            if (!validation.valid) {
                showFlash('error', cashierText('managerCodeValidationFailed'));
                return;
            }

            const formData = Object.assign({}, pendingEmergencyAction.formData, {
                manager_id: validation.id,
                reason_id: parseNumber(selectedReason)
            });

            if (store) {
                store.overrideOpen = false;
            }

            await createEmergencyVoucher(emergencyForm, formData);
        } catch (error) {
            showFlash('error', extractErrorMessage(error, cashierText('overrideValidationFailed')));
        } finally {
            managerCode.value = '';
            managerName.value = '';
            reasonSelect.value = '';
        }
    }

    function showSimilarWarning(messageBox, similarData) {
        const matches = similarData.matches.map(function(match) {
            return '<div class="svdp-similar-match">' +
                '<strong>' + escapeHtml(match.firstName + ' ' + match.lastName) + '</strong><br>' +
                'DOB: ' + escapeHtml(match.dob) + '<br>' +
                'Conference: ' + escapeHtml(match.conference) + '<br>' +
                'Created: ' + escapeHtml(match.voucherCreatedDate) +
                '</div>';
        }).join('');

        const html = '<strong>' + escapeHtml(cashierText('similarVoucherHeading')) + '</strong><br><br>' +
            matches +
            '<div class="svdp-similar-actions">' +
            '<button type="button" class="svdp-btn svdp-btn-warning" data-similar-action="proceed">' + escapeHtml(cashierText('similarCreateButton')) + '</button>' +
            '<button type="button" class="svdp-btn svdp-btn-secondary" data-similar-action="cancel">' + escapeHtml(cashierText('similarCancelButton')) + '</button>' +
            '</div>';

        showSectionMessage(messageBox, html, 'error');
    }

    async function handleSimilarAction(action) {
        const emergencyForm = document.getElementById('svdpEmergencyForm');
        const messageBox = document.getElementById('svdpEmergencyMessage');

        if (!pendingEmergencyAction || !emergencyForm) {
            return;
        }

        if (action === 'cancel') {
            emergencyForm.reset();
            pendingEmergencyAction = null;
            clearSectionMessage(messageBox);
            return;
        }

        if (action === 'proceed') {
            try {
                await createEmergencyVoucher(emergencyForm, pendingEmergencyAction.formData);
            } catch (error) {
                showSectionMessage(messageBox, extractErrorMessage(error, cashierText('emergencyCreateFailed')), 'error');
            }
        }
    }

    async function saveDeniedVoucher(formData, duplicateData) {
        if (!formData || !duplicateData) {
            return;
        }

        try {
            await requestJSON(config.restUrl + 'svdp/v1/vouchers/create-denied', {
                method: 'POST',
                data: {
                    firstName: formData.firstName,
                    lastName: formData.lastName,
                    dob: formData.dob,
                    adults: formData.adults,
                    children: formData.children,
                    conference: 'emergency',
                    denialReason: formatText(cashierText('denialReasonTemplate'), [
                        duplicateData.firstName,
                        duplicateData.lastName,
                        duplicateData.conference,
                        duplicateData.voucherCreatedDate,
                        duplicateData.nextEligibleDate
                    ]),
                    createdBy: 'Cashier'
                }
            });
        } catch (error) {
            // Denied tracking should not block the cashier.
        }
    }

    async function submitFurniturePhoto(form) {
        const voucherId = form.getAttribute('data-voucher-id');
        const itemId = form.getAttribute('data-item-id');
        const fileInput = form.querySelector('input[name="photo"]');
        const submitButton = form.querySelector('button[type="submit"]');
        const originalLabel = submitButton ? submitButton.textContent : 'Upload Photo';

        if (!fileInput || !fileInput.files || !fileInput.files.length) {
            showInlineError(form, cashierText('photoRequired'));
            return;
        }

        const formData = new FormData();
        formData.append('photo', fileInput.files[0]);

        showInlineError(form, '');
        setButtonState(submitButton, true, cashierText('uploading'));

        try {
            await requestJSON(config.restUrl + 'svdp/v1/cashier/vouchers/' + voucherId + '/items/' + itemId + '/photo', {
                method: 'POST',
                formData: formData
            });

            showFlash('success', cashierText('photoUploadSuccess'));
            refreshShell(voucherId);
        } catch (error) {
            showInlineError(form, extractErrorMessage(error, cashierText('photoUploadFailed')));
        } finally {
            if (fileInput) {
                fileInput.value = '';
            }

            setButtonState(submitButton, false, originalLabel);
        }
    }

    async function submitFurnitureComplete(form) {
        if (!validateFurnitureCompleteForm(form)) {
            return;
        }

        const voucherId = form.getAttribute('data-voucher-id');
        const itemId = form.getAttribute('data-item-id');
        const submitButton = form.querySelector('button[type="submit"]');
        const originalLabel = submitButton ? submitButton.textContent : 'Mark Completed';

        setButtonState(submitButton, true, cashierText('saving'));

        try {
            await requestJSON(config.restUrl + 'svdp/v1/cashier/vouchers/' + voucherId + '/items/' + itemId + '/complete', {
                method: 'POST',
                data: {
                    actualPrice: parseDecimal(form.elements.actualPrice.value),
                    completionNotes: form.elements.completionNotes.value.trim()
                }
            });

            showFlash('success', cashierText('furnitureItemCompleted'));
            refreshShell(voucherId);
        } catch (error) {
            showInlineError(form, extractErrorMessage(error, cashierText('furnitureItemCompleteFailed')));
        } finally {
            setButtonState(submitButton, false, originalLabel);
        }
    }

    async function submitFurnitureSubstitute(form) {
        if (!validateFurnitureSubstituteForm(form)) {
            return;
        }

        const voucherId = form.getAttribute('data-voucher-id');
        const itemId = form.getAttribute('data-item-id');
        const submitButton = form.querySelector('button[type="submit"]');
        const originalLabel = submitButton ? submitButton.textContent : 'Save Substitute';
        const substitutionType = form.elements.substitutionType.value;
        const payload = {
            substitutionType: substitutionType
        };

        if (substitutionType === 'catalog') {
            payload.substituteCatalogItemId = parseNumber(form.elements.substituteCatalogItemId.value);
        } else {
            payload.substituteItemName = form.elements.substituteItemName.value.trim();
        }

        setButtonState(submitButton, true, cashierText('saving'));

        try {
            await requestJSON(config.restUrl + 'svdp/v1/cashier/vouchers/' + voucherId + '/items/' + itemId + '/substitute', {
                method: 'POST',
                data: payload
            });

            showFlash('success', cashierText('substituteSaved'));
            refreshShell(voucherId);
        } catch (error) {
            showInlineError(form, extractErrorMessage(error, cashierText('substituteSaveFailed')));
        } finally {
            setButtonState(submitButton, false, originalLabel);
        }
    }

    async function submitFurnitureCancel(form) {
        if (!validateFurnitureCancelForm(form)) {
            return;
        }

        const voucherId = form.getAttribute('data-voucher-id');
        const itemId = form.getAttribute('data-item-id');
        const submitButton = form.querySelector('button[type="submit"]');
        const originalLabel = submitButton ? submitButton.textContent : 'Confirm Cancellation';

        setButtonState(submitButton, true, cashierText('saving'));

        try {
            await requestJSON(config.restUrl + 'svdp/v1/cashier/vouchers/' + voucherId + '/items/' + itemId + '/cancel', {
                method: 'POST',
                data: {
                    cancellationReasonId: parseNumber(form.elements.cancellationReasonId.value),
                    cancellationNotes: form.elements.cancellationNotes.value.trim()
                }
            });

            showFlash('success', cashierText('furnitureItemCancelled'));
            refreshShell(voucherId);
        } catch (error) {
            showInlineError(form, extractErrorMessage(error, cashierText('furnitureItemCancelFailed')));
        } finally {
            setButtonState(submitButton, false, originalLabel);
        }
    }

    async function submitFurnitureVoucherComplete(form) {
        const voucherId = form.getAttribute('data-voucher-id');
        const submitButton = form.querySelector('button[type="submit"]');
        const originalLabel = submitButton ? submitButton.textContent : cashierText('furnitureVoucherCompleteFallback');

        showInlineError(form, '');
        setButtonState(submitButton, true, cashierText('generating'));

        try {
            const response = await requestJSON(config.restUrl + 'svdp/v1/cashier/vouchers/' + voucherId + '/complete', {
                method: 'POST',
                data: {}
            });

            const invoiceMessage = response.invoiceNumber
                ? formatText(cashierText('furnitureVoucherInvoiceGeneratedTemplate'), [response.invoiceNumber])
                : '';
            showFlash('success', formatText(cashierText('furnitureVoucherCompletedTemplate'), [invoiceMessage]));
            refreshShell(voucherId);
        } catch (error) {
            showInlineError(form, extractErrorMessage(error, cashierText('furnitureVoucherCompleteFailed')));
        } finally {
            setButtonState(submitButton, false, originalLabel);
        }
    }

    async function submitVoucherCorrection(form) {
        const voucherId = form.getAttribute('data-voucher-id');
        const submitButton = form.querySelector('button[type="submit"]');
        const originalLabel = submitButton ? submitButton.textContent : 'Submit Correction';
        const managerCode = form.elements.managerCode ? form.elements.managerCode.value.trim().toUpperCase() : '';
        const managerName = form.elements.managerName ? form.elements.managerName.value.trim() : '';
        const reasonSelect = form.elements.reasonId;
        const reasonId = reasonSelect ? reasonSelect.value : '';
        const selectedReason = reasonSelect && reasonSelect.selectedIndex >= 0 ? reasonSelect.options[reasonSelect.selectedIndex] : null;
        const reasonText = selectedReason ? selectedReason.textContent.trim() : '';

        if (form.elements.managerCode) {
            form.elements.managerCode.value = managerCode;
        }

        if (!voucherId) {
            showInlineError(form, cashierText('voucherCorrectionFailed', 'Unable to identify the voucher.'));
            return;
        }

        if (!managerName) {
            showInlineError(form, cashierText('voucherCorrectionManagerNameRequired', 'Enter a manager name.'));
            return;
        }

        if (!/^[A-Z2-9]{4}$/.test(managerCode)) {
            showInlineError(form, cashierText('managerCodeInvalid'));
            return;
        }

        if (!reasonId) {
            showInlineError(form, cashierText('overrideReasonRequired'));
            return;
        }

        showInlineError(form, '');
        setButtonState(submitButton, true, cashierText('checking'));

        try {
            const validation = await requestJSON(config.restUrl + 'svdp/v1/managers/validate', {
                method: 'POST',
                data: {
                    code: managerCode,
                    managerName: managerName,
                    reasonId: parseNumber(reasonId),
                    context: 'voucher_correction',
                    voucherId: parseNumber(voucherId)
                }
            });

            if (!validation.valid) {
                showInlineError(form, cashierText('managerCodeValidationFailed'));
                return;
            }

            setButtonState(submitButton, true, cashierText('saving'));

            await requestJSON(config.restUrl + 'svdp/v1/vouchers/' + voucherId + '/correct', {
                method: 'POST',
                data: buildVoucherCorrectionPayload(form, validation, {
                    managerName: managerName,
                    reasonId: reasonId,
                    reasonText: reasonText
                })
            });

            collapsePanels();
            showFlash('success', cashierText('voucherCorrectionSuccess', 'Voucher correction saved.'));
            refreshShell(voucherId);
        } catch (error) {
            showInlineError(form, extractErrorMessage(error, cashierText('voucherCorrectionFailed', 'Failed to save voucher correction.')));
        } finally {
            setButtonState(submitButton, false, originalLabel);
        }
    }

    function buildVoucherCorrectionPayload(form, validation, authorityInput) {
        return {
            changes: collectVoucherCorrectionChanges(form),
            authority: {
                managerId: validation.id || validation.managerId || validation.manager_id,
                managerName: validation.name || validation.managerName || authorityInput.managerName,
                reasonId: parseNumber(authorityInput.reasonId),
                reasonText: authorityInput.reasonText
            }
        };
    }

    function collectVoucherCorrectionChanges(form) {
        const changes = {};
        const integerFields = ['adults', 'children'];
        const textFields = [
            'dob',
            'status',
            'voucher_created_date',
            'delivery_address_line_1',
            'delivery_address_line_2',
            'delivery_city',
            'delivery_state',
            'delivery_zip'
        ];

        integerFields.forEach(function(field) {
            if (form.elements[field]) {
                changes[field] = parseNumber(form.elements[field].value);
            }
        });

        textFields.forEach(function(field) {
            if (form.elements[field]) {
                changes[field] = form.elements[field].value.trim();
            }
        });

        return changes;
    }

    function refreshShell(voucherId) {
        window.htmx.trigger(document.body, 'svdp:list-refresh');

        if (voucherId) {
            setSelectedVoucher(voucherId);
            loadVoucherDetail(voucherId);
            return;
        }

        window.htmx.trigger(document.body, 'svdp:detail-refresh');
    }

    function loadVoucherDetail(voucherId) {
        if (!voucherId || !window.htmx) {
            return;
        }

        window.htmx.ajax('GET', config.restUrl + 'svdp/v1/cashier/vouchers/' + voucherId, {
            target: '#svdpCashierDetailPanel',
            swap: 'innerHTML'
        });
    }

    function showMoreVouchers(button) {
        const visibleInput = document.getElementById('svdpCashierVisibleCount');
        if (!visibleInput) {
            return;
        }

        visibleInput.value = String(parseNumber(button.getAttribute('data-next-visible-count')) || defaultVisibleCount);
        window.htmx.trigger(document.body, 'svdp:list-refresh');
    }

    function resetVisibleCount() {
        const visibleInput = document.getElementById('svdpCashierVisibleCount');
        if (visibleInput) {
            visibleInput.value = String(defaultVisibleCount);
        }
    }

    function validateFurnitureCompleteForm(form) {
        const actualPrice = parseDecimal(form.elements.actualPrice.value);
        const errors = [];

        if (!Number.isFinite(actualPrice) || actualPrice <= 0) {
            errors.push(cashierText('actualPriceRequired'));
        }

        showInlineError(form, errors.join(' '));
        return errors.length === 0;
    }

    function validateFurnitureSubstituteForm(form) {
        const substitutionType = form.elements.substitutionType.value;
        const errors = [];

        if (substitutionType === 'catalog' && !parseNumber(form.elements.substituteCatalogItemId.value)) {
            errors.push(cashierText('substituteCatalogRequired'));
        }

        if (substitutionType === 'free_text' && !form.elements.substituteItemName.value.trim()) {
            errors.push(cashierText('substituteNameRequired'));
        }

        showInlineError(form, errors.join(' '));
        return errors.length === 0;
    }

    function validateFurnitureCancelForm(form) {
        const errors = [];

        if (!parseNumber(form.elements.cancellationReasonId.value)) {
            errors.push(cashierText('cancellationReasonRequired'));
        }

        showInlineError(form, errors.join(' '));
        return errors.length === 0;
    }

    function updateRedemptionSummary(form) {
        const adultItems = parseNumber(form.elements.items_adult.value);
        const childItems = parseNumber(form.elements.items_children.value);
        const maxAdultItems = parseNumber(form.elements.items_adult.getAttribute('max'));
        const maxChildItems = parseNumber(form.elements.items_children.getAttribute('max'));
        const maxTotalItems = parseNumber(form.getAttribute('data-max-total'));
        const totalItems = adultItems + childItems;
        const estimatedValue = (adultItems * itemValues.adult) + (childItems * itemValues.child);

        const totalLabel = form.querySelector('[data-redemption-total]');
        if (totalLabel) {
            totalLabel.textContent = formatText(cashierText('currentRedemptionTotalTemplate'), [totalItems, maxTotalItems]);
        }

        const valueLabel = form.querySelector('[data-redemption-value]');
        if (valueLabel) {
            valueLabel.textContent = formatText(cashierText('estimatedValueTemplate'), [estimatedValue.toFixed(2)]);
        }

        const errors = [];
        if (adultItems > maxAdultItems) {
            errors.push(cashierText('adultItemsMaxExceeded'));
        }
        if (childItems > maxChildItems) {
            errors.push(cashierText('childItemsMaxExceeded'));
        }
        if (totalItems > maxTotalItems) {
            errors.push(cashierText('totalItemsMaxExceeded'));
        }
        if (totalItems === 0) {
            errors.push(cashierText('redeemedItemRequired'));
        }

        showInlineError(form, errors.join(' '));
        return errors.length === 0;
    }

    function maybeScrollDetailIntoView(detailTarget) {
        if (!shouldScrollDetailOnSwap) {
            return;
        }

        if (window.innerWidth > 1100) {
            shouldScrollDetailOnSwap = false;
            return;
        }

        shouldScrollDetailOnSwap = false;

        if (detailTarget && typeof detailTarget.scrollIntoView === 'function') {
            detailTarget.scrollIntoView({
                behavior: 'smooth',
                block: 'start'
            });
        }
    }

    function validateRedemptionForm(form) {
        return updateRedemptionSummary(form);
    }

    function updateCoatSummary(form) {
        const adultCoats = parseNumber(form.elements.adults.value);
        const childCoats = parseNumber(form.elements.children.value);
        const totalCoats = adultCoats + childCoats;

        const totalLabel = form.querySelector('[data-coat-total]');
        if (totalLabel) {
            totalLabel.textContent = formatText(coatText('totalCoatsTemplate'), [totalCoats]);
        }

        const errors = [];
        if (adultCoats === 0 && childCoats === 0) {
            errors.push(coatText('issueAtLeastOne'));
        }

        if (adultCoats > parseNumber(form.elements.adults.getAttribute('max'))) {
            errors.push(coatText('adultCoatsExceedHousehold'));
        }

        if (childCoats > parseNumber(form.elements.children.getAttribute('max'))) {
            errors.push(coatText('childCoatsExceedHousehold'));
        }

        showInlineError(form, errors.join(' '));
        return errors.length === 0;
    }

    function validateCoatForm(form) {
        return updateCoatSummary(form);
    }

    function showInlineError(form, message) {
        const errorBox = form.querySelector('[data-inline-error]');
        if (!errorBox) {
            return;
        }

        if (!message) {
            errorBox.style.display = 'none';
            errorBox.textContent = '';
            return;
        }

        errorBox.style.display = 'block';
        errorBox.textContent = message;
    }

    function showFlash(type, message) {
        const flash = document.getElementById('svdpCashierFlash');
        if (!flash) {
            return;
        }

        flash.className = 'svdp-message ' + type;
        flash.textContent = message;
        flash.style.display = 'block';

        window.clearTimeout(showFlash.timeoutId);
        showFlash.timeoutId = window.setTimeout(function() {
            flash.style.display = 'none';
        }, 5000);
    }

    function showSectionMessage(messageBox, message, type) {
        if (!messageBox) {
            return;
        }

        messageBox.className = 'svdp-message ' + type;
        messageBox.innerHTML = message;
        messageBox.style.display = 'block';
    }

    function clearSectionMessage(messageBox) {
        if (!messageBox) {
            return;
        }

        messageBox.className = 'svdp-message';
        messageBox.innerHTML = '';
        messageBox.style.display = 'none';
    }

    function collapsePanels() {
        const store = cashierStore();
        if (store) {
            store.activePanel = null;
        }
    }

    function collectEmergencyFormData(form) {
        return {
            firstName: form.elements.firstName.value.trim(),
            lastName: form.elements.lastName.value.trim(),
            dob: form.elements.dob.value,
            adults: parseNumber(form.elements.adults.value),
            children: parseNumber(form.elements.children.value),
            conference: 'emergency',
            voucherType: 'clothing'
        };
    }

    function setButtonState(button, disabled, label) {
        if (!button) {
            return;
        }

        button.disabled = disabled;
        button.textContent = label;
    }

    async function requestJSON(url, options = {}) {
        const headers = Object.assign({
            'X-WP-Nonce': config.nonce || ''
        }, options.headers || {});
        const fetchOptions = {
            method: options.method || 'GET',
            credentials: 'same-origin',
            headers: headers
        };

        if (options.data) {
            fetchOptions.body = JSON.stringify(options.data);
            fetchOptions.headers['Content-Type'] = 'application/json';
        } else if (options.formData) {
            fetchOptions.body = options.formData;
        }

        const response = await window.fetch(url, fetchOptions);
        const contentType = response.headers.get('content-type') || '';
        const isJson = contentType.indexOf('application/json') !== -1;
        const payload = isJson ? await response.json() : await response.text();

        if (!response.ok) {
            throw {
                status: response.status,
                payload: payload
            };
        }

        return payload;
    }

    function extractErrorMessage(error, fallback) {
        if (!error) {
            return fallback;
        }

        if (error.payload && typeof error.payload === 'object' && error.payload.message) {
            return error.payload.message;
        }

        if (typeof error.payload === 'string' && error.payload.trim() !== '') {
            return error.payload;
        }

        return fallback;
    }

    function parseNumber(value) {
        const parsed = parseInt(value, 10);
        return Number.isNaN(parsed) ? 0 : parsed;
    }

    function parseDecimal(value) {
        const parsed = parseFloat(value);
        return Number.isNaN(parsed) ? NaN : parsed;
    }

    function cashierText(key, fallback) {
        return cashierCopy[key] || fallback || key;
    }

    function coatText(key, fallback) {
        return coatCopy[key] || fallback || key;
    }

    function formatText(template, values) {
        const replacements = Array.isArray(values) ? values : [values];
        let index = 0;

        return String(template).replace(/%s/g, function() {
            const value = replacements[index] == null ? '' : replacements[index];
            index++;

            return String(value);
        });
    }

    function escapeHtml(value) {
        return String(value)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }
})();
