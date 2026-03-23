(function($) {
    'use strict';

    $(document).ready(function() {
        const form = $('#svdpVoucherForm');
        if (form.length === 0) {
            return;
        }

        const messageDiv = $('#svdpFormMessage');
        const catalogContainer = $('#svdpFurnitureCatalog');
        const deliveryRequiredInput = $('#svdpDeliveryRequired');
        const deliveryAddressFields = $('#svdpDeliveryAddressFields');
        const deliveryFeeNote = $('#svdpDeliveryFeeNote');
        const allVoucherTypes = form.find('[name="voucherType"]').map(function() {
            return $(this).val();
        }).get().filter(function(type, index, array) {
            return type && array.indexOf(type) === index;
        });
        const state = {
            catalogLoaded: false,
            catalogLoading: false,
            catalogCategories: [],
            catalogById: {},
            selectedItems: {},
            currentVoucherType: form.data('default-voucher-type') || 'clothing'
        };

        initializeDateInput();
        initializeVoucherTypeControls();
        initializeConferenceControls();
        initializeDeliveryControls();
        syncVoucherTypeAvailability();
        syncVoucherBranch();
        updateFurnitureSummary();

        form.on('submit', function(e) {
            e.preventDefault();

            if (!form[0].reportValidity()) {
                return;
            }

            const voucherType = getCurrentVoucherType();
            if (voucherType === 'furniture') {
                const furnitureValidationError = validateFurnitureSelection();
                if (furnitureValidationError) {
                    showMessage(furnitureValidationError, 'error');
                    return;
                }
            }

            const dobFormatted = getFormattedDob();
            if (!dobFormatted) {
                showMessage('Enter a valid date of birth.', 'error');
                return;
            }

            const submitBtn = form.find('button[type="submit"]');
            submitBtn.prop('disabled', true).text('Processing...');
            messageDiv.hide().removeClass('success error');

            const formData = buildSubmissionPayload(voucherType, dobFormatted);

            $.ajax({
                url: svdpVouchers.restUrl + 'svdp/v1/vouchers/check-duplicate',
                method: 'POST',
                headers: {
                    'X-WP-Nonce': svdpVouchers.nonce
                },
                data: JSON.stringify({
                    firstName: formData.firstName,
                    lastName: formData.lastName,
                    dob: formData.dob,
                    conference: formData.conference,
                    voucherType: formData.voucherType,
                    createdBy: 'Vincentian'
                }),
                contentType: 'application/json',
                success: function(response) {
                    if (response.found) {
                        showDuplicateMessage(response);
                        submitBtn.prop('disabled', false).text('Submit Voucher Request');
                        return;
                    }

                    createVoucher(formData, submitBtn);
                },
                error: function(xhr) {
                    const error = xhr.responseJSON?.message || 'Error checking for duplicates';
                    showMessage(error, 'error');
                    submitBtn.prop('disabled', false).text('Submit Voucher Request');
                }
            });
        });

        function initializeDateInput() {
            function isMobile() {
                return /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent);
            }

            const dateInput = document.createElement('input');
            dateInput.setAttribute('type', 'date');
            const supportsDateInput = dateInput.type === 'date';

            if (!supportsDateInput || isMobile()) {
                const dobField = $('#svdp-dob-input');
                dobField.attr('type', 'text');
                dobField.attr('pattern', '\\d{2}/\\d{2}/\\d{4}');

                dobField.on('input', function(e) {
                    let value = e.target.value.replace(/\D/g, '');
                    if (value.length >= 2) {
                        value = value.substring(0, 2) + '/' + value.substring(2);
                    }
                    if (value.length >= 5) {
                        value = value.substring(0, 5) + '/' + value.substring(5, 9);
                    }
                    e.target.value = value;
                });
            }
        }

        function initializeVoucherTypeControls() {
            form.on('change', '[name="voucherType"]', function() {
                state.currentVoucherType = $(this).val();
                updateVoucherTypeOptionSelection();
                syncVoucherBranch();
            });

            catalogContainer.on('click', '[data-catalog-adjust]', function() {
                const button = $(this);
                const itemId = Number(button.attr('data-catalog-item-id'));
                const direction = button.attr('data-catalog-adjust');
                const currentQuantity = Number(state.selectedItems[itemId] || 0);
                const nextQuantity = direction === 'increment'
                    ? currentQuantity + 1
                    : Math.max(currentQuantity - 1, 0);

                if (nextQuantity > 0) {
                    state.selectedItems[itemId] = nextQuantity;
                } else {
                    delete state.selectedItems[itemId];
                }

                updateCatalogQuantities();
                updateFurnitureSummary();
            });
        }

        function initializeConferenceControls() {
            form.on('change', '[name="conference"]', function() {
                syncVoucherTypeAvailability();
            });
        }

        function initializeDeliveryControls() {
            deliveryRequiredInput.on('change', function() {
                const showDeliveryFields = $(this).is(':checked');
                deliveryAddressFields.prop('hidden', !showDeliveryFields);
                deliveryFeeNote.prop('hidden', !showDeliveryFields);
                updateFurnitureSummary();
            });
        }

        function getCurrentVoucherType() {
            const checkedVoucherType = form.find('input[type="radio"][name="voucherType"]:checked').val();
            if (checkedVoucherType) {
                return checkedVoucherType;
            }

            return form.find('[name="voucherType"]').first().val() || state.currentVoucherType || 'clothing';
        }

        function setCurrentVoucherType(voucherType) {
            const radioInputs = form.find('input[type="radio"][name="voucherType"]');
            if (radioInputs.length > 0) {
                radioInputs.filter('[value="' + voucherType + '"]').prop('checked', true);
            } else {
                form.find('input[type="hidden"][name="voucherType"]').val(voucherType);
            }

            state.currentVoucherType = voucherType;
            updateVoucherTypeOptionSelection();
            syncVoucherBranch();
        }

        function getAllowedVoucherTypesForConference() {
            const conferenceSelect = form.find('select[name="conference"]');
            const conferenceHidden = form.find('input[type="hidden"][name="conference"]');

            if (conferenceSelect.length > 0) {
                const selectedOption = conferenceSelect.find('option:selected');
                const rawTypes = selectedOption.attr('data-allowed-voucher-types');
                if (!rawTypes) {
                    return allVoucherTypes.slice();
                }

                try {
                    return JSON.parse(rawTypes);
                } catch (error) {
                    return allVoucherTypes.slice();
                }
            }

            if (conferenceHidden.length > 0) {
                const rawTypes = conferenceHidden.attr('data-allowed-voucher-types');
                if (rawTypes) {
                    try {
                        return JSON.parse(rawTypes);
                    } catch (error) {
                        return allVoucherTypes.slice();
                    }
                }
            }

            return allVoucherTypes.slice();
        }

        function syncVoucherTypeAvailability() {
            const allowedVoucherTypes = getAllowedVoucherTypesForConference();
            const radioInputs = form.find('input[type="radio"][name="voucherType"]');

            if (radioInputs.length > 0) {
                radioInputs.each(function() {
                    const input = $(this);
                    const voucherType = input.val();
                    const option = form.find('[data-voucher-type-option="' + voucherType + '"]');
                    const allowed = allowedVoucherTypes.indexOf(voucherType) !== -1;

                    input.prop('disabled', !allowed);
                    option.toggleClass('is-disabled', !allowed);
                });
            } else {
                const hiddenVoucherType = form.find('input[type="hidden"][name="voucherType"]');
                if (hiddenVoucherType.length > 0 && allowedVoucherTypes.length > 0 && allowedVoucherTypes.indexOf(hiddenVoucherType.val()) === -1) {
                    hiddenVoucherType.val(allowedVoucherTypes[0]);
                }
            }

            if (allowedVoucherTypes.length > 0 && allowedVoucherTypes.indexOf(getCurrentVoucherType()) === -1) {
                setCurrentVoucherType(allowedVoucherTypes[0]);
            } else {
                updateVoucherTypeOptionSelection();
                syncVoucherBranch();
            }
        }

        function updateVoucherTypeOptionSelection() {
            form.find('[data-voucher-type-option]').removeClass('is-selected');
            form.find('input[type="radio"][name="voucherType"]:checked').each(function() {
                form.find('[data-voucher-type-option="' + $(this).val() + '"]').addClass('is-selected');
            });
        }

        function syncVoucherBranch() {
            const voucherType = getCurrentVoucherType();

            form.find('[data-voucher-branch]').each(function() {
                const branch = $(this);
                branch.prop('hidden', branch.attr('data-voucher-branch') !== voucherType);
            });

            if (voucherType === 'furniture') {
                ensureCatalogLoaded();
            }

            updateFurnitureSummary();
        }

        function ensureCatalogLoaded() {
            if (state.catalogLoaded || state.catalogLoading) {
                return;
            }

            state.catalogLoading = true;

            $.ajax({
                url: svdpVouchers.restUrl + 'svdp/v1/catalog-items',
                method: 'GET',
                headers: {
                    'X-WP-Nonce': svdpVouchers.nonce
                },
                success: function(response) {
                    state.catalogCategories = response.categories || [];
                    state.catalogById = {};

                    state.catalogCategories.forEach(function(category) {
                        (category.items || []).forEach(function(item) {
                            state.catalogById[Number(item.id)] = item;
                        });
                    });

                    state.catalogLoaded = true;
                    renderCatalog();
                    updateFurnitureSummary();
                },
                error: function() {
                    catalogContainer.html(
                        '<div class="svdp-message error">' +
                            'Unable to load furniture catalog items right now. Please try again.' +
                        '</div>'
                    );
                },
                complete: function() {
                    state.catalogLoading = false;
                }
            });
        }

        function renderCatalog() {
            if (!state.catalogCategories.length) {
                catalogContainer.html(
                    '<div class="svdp-empty-state">' +
                        '<div class="svdp-empty-icon">🪑</div>' +
                        '<div class="svdp-empty-text">No furniture catalog items are active yet.</div>' +
                    '</div>'
                );
                return;
            }

            const html = state.catalogCategories.map(function(category) {
                const itemsHtml = (category.items || []).map(function(item) {
                    const itemId = Number(item.id);
                    const quantity = Number(state.selectedItems[itemId] || 0);

                    return '' +
                        '<article class="svdp-catalog-item" data-catalog-item="' + itemId + '">' +
                            '<div class="svdp-catalog-item-copy">' +
                                '<h4>' + escapeHtml(item.name) + '</h4>' +
                                '<p>' + escapeHtml(item.priceDisplay) + '</p>' +
                            '</div>' +
                            '<div class="svdp-catalog-item-controls">' +
                                '<button type="button" class="svdp-qty-btn" data-catalog-adjust="decrement" data-catalog-item-id="' + itemId + '" aria-label="Remove one ' + escapeHtml(item.name) + '">-</button>' +
                                '<span class="svdp-qty-value" data-catalog-qty="' + itemId + '">' + quantity + '</span>' +
                                '<button type="button" class="svdp-qty-btn" data-catalog-adjust="increment" data-catalog-item-id="' + itemId + '" aria-label="Add one ' + escapeHtml(item.name) + '">+</button>' +
                            '</div>' +
                        '</article>';
                }).join('');

                return '' +
                    '<section class="svdp-catalog-category">' +
                        '<div class="svdp-catalog-category-header">' +
                            '<h4>' + escapeHtml(category.label) + '</h4>' +
                        '</div>' +
                        '<div class="svdp-catalog-category-items">' + itemsHtml + '</div>' +
                    '</section>';
            }).join('');

            catalogContainer.html(html);
        }

        function updateCatalogQuantities() {
            catalogContainer.find('[data-catalog-qty]').each(function() {
                const quantityNode = $(this);
                const itemId = Number(quantityNode.attr('data-catalog-qty'));
                const quantity = Number(state.selectedItems[itemId] || 0);
                quantityNode.text(quantity);
            });
        }

        function buildFurnitureItemsPayload() {
            return Object.keys(state.selectedItems).reduce(function(items, key) {
                const catalogItemId = Number(key);
                const quantity = Number(state.selectedItems[key] || 0);

                if (catalogItemId > 0 && quantity > 0) {
                    items.push({
                        catalogItemId: catalogItemId,
                        quantity: quantity
                    });
                }

                return items;
            }, []);
        }

        function updateFurnitureSummary() {
            const summaryCount = $('#svdpSummaryItemCount');
            const summaryTotal = $('#svdpSummaryTotal');
            const summaryRequestor = $('#svdpSummaryRequestor');

            let itemCount = 0;
            let estimatedTotalMin = 0;
            let estimatedTotalMax = 0;

            Object.keys(state.selectedItems).forEach(function(key) {
                const itemId = Number(key);
                const quantity = Number(state.selectedItems[key] || 0);
                const item = state.catalogById[itemId];

                if (!item || quantity <= 0) {
                    return;
                }

                itemCount += quantity;

                if (item.pricingType === 'fixed') {
                    const fixedPrice = Number(item.priceFixed || 0);
                    estimatedTotalMin += fixedPrice * quantity;
                    estimatedTotalMax += fixedPrice * quantity;
                } else {
                    estimatedTotalMin += Number(item.priceMin || 0) * quantity;
                    estimatedTotalMax += Number(item.priceMax || 0) * quantity;
                }
            });

            const deliveryFee = deliveryRequiredInput.is(':checked') ? Number(svdpVouchers.deliveryFee || 50) : 0;
            const requestorMin = (estimatedTotalMin * 0.5) + deliveryFee;
            const requestorMax = (estimatedTotalMax * 0.5) + deliveryFee;

            summaryCount.text(itemCount);
            summaryTotal.text(formatMoneyRange(estimatedTotalMin, estimatedTotalMax));
            summaryRequestor.text(formatMoneyRange(requestorMin, requestorMax));
        }

        function validateFurnitureSelection() {
            const selectedItems = buildFurnitureItemsPayload();
            if (!state.catalogLoaded) {
                return 'Furniture catalog items are still loading. Please wait a moment and try again.';
            }

            if (selectedItems.length === 0) {
                return 'Select at least one furniture item before submitting a furniture voucher request.';
            }

            if (deliveryRequiredInput.is(':checked')) {
                const requiredFields = [
                    { name: 'deliveryLine1', label: 'delivery address line 1' },
                    { name: 'deliveryCity', label: 'city' },
                    { name: 'deliveryState', label: 'state' },
                    { name: 'deliveryZip', label: 'ZIP code' }
                ];

                for (let i = 0; i < requiredFields.length; i += 1) {
                    const field = requiredFields[i];
                    const value = $.trim(form.find('[name="' + field.name + '"]').val());
                    if (!value) {
                        return 'Enter the ' + field.label + ' for delivery requests.';
                    }
                }
            }

            return '';
        }

        function getFormattedDob() {
            const dobInput = $('input[name="dob"]').val();
            if (!dobInput) {
                return '';
            }

            if ($('input[name="dob"]').attr('type') === 'date') {
                return dobInput;
            }

            const dobParts = dobInput.split('/');
            if (dobParts.length !== 3 || dobParts[0].length !== 2 || dobParts[1].length !== 2 || dobParts[2].length !== 4) {
                return '';
            }

            return dobParts[2] + '-' + dobParts[0] + '-' + dobParts[1];
        }

        function buildSubmissionPayload(voucherType, dobFormatted) {
            const payload = {
                firstName: $.trim(form.find('input[name="firstName"]').val()),
                lastName: $.trim(form.find('input[name="lastName"]').val()),
                dob: dobFormatted,
                adults: parseInt(form.find('input[name="adults"]').val(), 10) || 0,
                children: parseInt(form.find('input[name="children"]').val(), 10) || 0,
                conference: form.find('[name="conference"]').val(),
                voucherType: voucherType,
                vincentianName: $.trim(form.find('input[name="vincentianName"]').val()),
                vincentianEmail: $.trim(form.find('input[name="vincentianEmail"]').val())
            };

            if (voucherType === 'furniture') {
                payload.items = buildFurnitureItemsPayload();
                payload.deliveryRequired = deliveryRequiredInput.is(':checked');
                payload.deliveryAddress = {
                    line1: $.trim(form.find('[name="deliveryLine1"]').val()),
                    line2: $.trim(form.find('[name="deliveryLine2"]').val()),
                    city: $.trim(form.find('[name="deliveryCity"]').val()),
                    state: $.trim(form.find('[name="deliveryState"]').val()),
                    zip: $.trim(form.find('[name="deliveryZip"]').val())
                };
            }

            return payload;
        }

        function showDuplicateMessage(duplicateData) {
            if (duplicateData.matchType === 'similar' && Array.isArray(duplicateData.matches)) {
                let message = '<strong>Similar recent vouchers were found for this person.</strong><br><br>';
                duplicateData.matches.forEach(function(match) {
                    message += '<div class="svdp-similar-match">';
                    message += '<strong>' + escapeHtml(match.firstName + ' ' + match.lastName) + '</strong><br>';
                    message += 'DOB: ' + escapeHtml(match.dob) + '<br>';
                    message += 'Conference: ' + escapeHtml(match.conference) + '<br>';
                    message += 'Date: ' + escapeHtml(match.voucherCreatedDate) + '<br>';
                    message += 'Next Eligible Date: ' + escapeHtml(match.nextEligibleDate);
                    message += '</div>';
                });
                message += '<br><em>Review these matches before creating a new voucher.</em>';
                showMessage(message, 'error');
                return;
            }

            let message = '<strong>This person has already received a recent voucher.</strong><br><br>';
            message += '<strong>Last Voucher Details:</strong><br>';
            message += 'Conference: ' + escapeHtml(duplicateData.conference) + '<br>';
            message += 'Date: ' + escapeHtml(duplicateData.voucherCreatedDate) + '<br>';

            if (duplicateData.vincentianName) {
                message += 'Vincentian: ' + escapeHtml(duplicateData.vincentianName) + '<br>';
            }

            message += '<br><strong>Next Eligible Date:</strong> ' + escapeHtml(duplicateData.nextEligibleDate) + '<br>';
            message += '<br><em>If this person needs assistance before then, please contact the District Office or connect them with resources that can help in the meantime.</em>';

            showMessage(message, 'error');
        }

        function createVoucher(formData, submitBtn) {
            $.ajax({
                url: svdpVouchers.restUrl + 'svdp/v1/vouchers/create',
                method: 'POST',
                headers: {
                    'X-WP-Nonce': svdpVouchers.nonce
                },
                data: JSON.stringify(formData),
                contentType: 'application/json',
                success: function(response) {
                    const successMessage = formData.voucherType === 'furniture'
                        ? buildFurnitureSuccessMessage(response)
                        : buildClothingSuccessMessage(response);

                    showMessage(successMessage, 'success');
                    resetFormState();
                    submitBtn.prop('disabled', false).text('Submit Voucher Request');

                    $('html, body').animate({
                        scrollTop: messageDiv.offset().top - 20
                    }, 500);
                },
                error: function(xhr) {
                    const error = xhr.responseJSON?.message || 'Error creating voucher';
                    showMessage(error, 'error');
                    submitBtn.prop('disabled', false).text('Submit Voucher Request');
                }
            });
        }

        function buildClothingSuccessMessage(response) {
            let message = '<strong>✅ Voucher Created Successfully!</strong><br><br>';
            message += 'The voucher has been created and is ready to use immediately.<br><br>';
            message += '<strong>Important Reminders:</strong><br>';
            message += '• Thrift Store hours: 9:30 AM – 4:00 PM<br>';
            message += '• Ask your Neighbor to check in at Customer Service before shopping<br>';
            message += '• This household can receive another voucher after: <strong>' + escapeHtml(response.nextEligibleDate) + '</strong><br>';

            if (response.coatEligibleAfter) {
                message += '• Winter coat eligible after: <strong>' + escapeHtml(response.coatEligibleAfter) + '</strong>';
            }

            return message;
        }

        function buildFurnitureSuccessMessage(response) {
            let message = '<strong>✅ Furniture Request Created Successfully!</strong><br><br>';
            message += 'The requested furniture items have been saved for cashier review.<br><br>';
            message += '<strong>Request Summary:</strong><br>';
            message += '• Selected items: <strong>' + escapeHtml(String(response.itemCount || 0)) + '</strong><br>';
            message += '• Estimated total: <strong>' + escapeHtml(formatMoneyRange(response.estimatedTotalMin || 0, response.estimatedTotalMax || 0)) + '</strong><br>';
            message += '• Estimated requestor portion: <strong>' + escapeHtml(formatMoneyRange(response.estimatedRequestorPortionMin || 0, response.estimatedRequestorPortionMax || 0)) + '</strong><br>';

            if (response.deliveryRequired) {
                message += '• Delivery fee included: <strong>$' + escapeHtml(Number(response.deliveryFee || 0).toFixed(2)) + '</strong><br>';
            }

            message += '• This household can receive another furniture voucher after: <strong>' + escapeHtml(response.nextEligibleDate) + '</strong><br>';
            message += '<br><em>Final fulfilled pricing may vary from the estimate range shown above.</em>';

            return message;
        }

        function resetFormState() {
            form[0].reset();
            state.selectedItems = {};
            updateCatalogQuantities();
            deliveryAddressFields.prop('hidden', true);
            deliveryFeeNote.prop('hidden', true);
            setCurrentVoucherType(form.data('default-voucher-type') || 'clothing');
            syncVoucherTypeAvailability();
            updateFurnitureSummary();
        }

        function showMessage(message, type) {
            messageDiv
                .html(message)
                .removeClass('success error')
                .addClass(type)
                .fadeIn();
        }

        function formatMoneyRange(min, max) {
            const normalizedMin = Number(min || 0);
            const normalizedMax = Number(max || 0);

            if (Math.abs(normalizedMax - normalizedMin) < 0.01) {
                return '$' + normalizedMin.toFixed(2);
            }

            return '$' + normalizedMin.toFixed(2) + ' - $' + normalizedMax.toFixed(2);
        }

        function escapeHtml(value) {
            return $('<div>').text(value == null ? '' : String(value)).html();
        }
    });

})(jQuery);
