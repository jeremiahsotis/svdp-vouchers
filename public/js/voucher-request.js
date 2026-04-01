(function($) {
    'use strict';

    $(document).ready(function() {
        const form = $('#svdpVoucherForm');
        if (form.length === 0) {
            return;
        }

        const messageDiv = $('#svdpFormMessage');
        const catalogContainer = $('#svdpFurnitureCatalog');
        const catalogLoadingState = $('#svdpFurnitureCatalogLoading');
        const searchInput = $('#svdpFurnitureSearch');
        const searchEmptyState = $('#svdpFurnitureSearchEmpty');
        const deliveryRequiredInput = $('#svdpDeliveryRequired');
        const deliveryAddressFields = $('#svdpDeliveryAddressFields');
        const deliveryFeeNote = $('#svdpDeliveryFeeNote');
        const summaryDeliveryFee = $('#svdpSummaryDeliveryFee');
        const allVoucherTypes = form.find('[name="voucherType"]').map(function() {
            return $(this).val();
        }).get().filter(function(type, index, array) {
            return type && array.indexOf(type) === index;
        });
        const state = {
            catalogLoaded: false,
            catalogLoading: false,
            catalogCategories: [],
            catalogByCategory: {},
            catalogById: {},
            openCategories: {},
            searchQuery: '',
            selectedItems: {},
            currentVoucherType: form.data('default-voucher-type') || 'clothing'
        };

        initializeDateInput();
        initializeVoucherTypeControls();
        initializeConferenceControls();
        initializeSearchControls();
        initializeDeliveryControls();
        syncVoucherTypeAvailability();
        syncVoucherBranch();
        syncDeliveryControls();
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

            catalogContainer.on('click', '[data-category-card]', function() {
                const categoryKey = $(this).attr('data-category-card');

                if (!categoryKey) {
                    return;
                }

                state.openCategories[categoryKey] = !state.openCategories[categoryKey];
                syncCategorySectionState();
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

        function initializeSearchControls() {
            searchInput.on('input', function() {
                state.searchQuery = normalizeSearchQuery($(this).val());

                if (state.catalogLoaded) {
                    renderCatalog();
                    updateFurnitureSummary();
                }
            });
        }

        function initializeDeliveryControls() {
            deliveryRequiredInput.on('change', function() {
                syncDeliveryControls();
                updateFurnitureSummary();
            });
        }

        function syncDeliveryControls() {
            const voucherType = getCurrentVoucherType();
            const showDeliveryFields = voucherType === 'furniture' && deliveryRequiredInput.is(':checked');
            const requiredDeliveryFields = ['deliveryLine1', 'deliveryCity', 'deliveryState', 'deliveryZip'];

            deliveryAddressFields
                .prop('hidden', !showDeliveryFields)
                .attr('aria-hidden', showDeliveryFields ? 'false' : 'true');

            deliveryAddressFields.find('input').prop('disabled', !showDeliveryFields);

            requiredDeliveryFields.forEach(function(fieldName) {
                form.find('[name="' + fieldName + '"]').prop('required', showDeliveryFields);
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

            syncDeliveryControls();
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
                    state.catalogByCategory = {};
                    state.catalogById = {};

                    state.catalogCategories.forEach(function(category) {
                        state.catalogByCategory[category.key] = category;

                        (category.items || []).forEach(function(item) {
                            state.catalogById[Number(item.id)] = item;
                        });
                    });

                    state.catalogLoaded = true;
                    searchInput.prop('disabled', false);
                    renderCatalog();
                    updateFurnitureSummary();
                },
                error: function() {
                    catalogLoadingState.prop('hidden', true);
                    catalogContainer.prepend(
                        '<div class="svdp-message error svdp-furniture-shell-error">' +
                            'Unable to load furniture catalog items right now. Please try again.' +
                        '</div>'
                    );
                    searchInput.prop('disabled', true);
                },
                complete: function() {
                    state.catalogLoading = false;
                }
            });
        }

        function renderCatalog() {
            const visibleCounts = {};
            const searchActive = state.searchQuery !== '';
            let totalVisibleMatches = 0;

            catalogContainer.find('.svdp-furniture-shell-error').remove();

            state.catalogCategories.forEach(function(category) {
                const visibleItems = getVisibleItemsForCategory(category);
                visibleCounts[category.key] = visibleItems.length;
                totalVisibleMatches += visibleItems.length;
            });

            catalogContainer.find('[data-category-card]').each(function() {
                const card = $(this);
                const categoryKey = card.attr('data-category-card');
                const visibleCount = Number(visibleCounts[categoryKey] || 0);

                card.toggleClass('is-empty', !searchActive && visibleCount === 0);
                card.toggleClass('is-filtered-out', searchActive && visibleCount === 0);
                card.prop('hidden', searchActive && visibleCount === 0);
                card.find('[data-category-available-count]').text(
                    searchActive ? formatMatchCount(visibleCount) : formatAvailableCount(visibleCount)
                );
            });

            catalogContainer.find('[data-category-section]').each(function() {
                const section = $(this);
                const categoryKey = section.attr('data-category-section');
                const category = state.catalogByCategory[categoryKey] || { items: [] };
                const items = getVisibleItemsForCategory(category);
                const visibleCount = Number(visibleCounts[categoryKey] || 0);

                section.toggleClass('is-empty', visibleCount === 0);
                section.find('[data-category-pill]').text(
                    searchActive ? formatMatchCount(visibleCount) : (visibleCount > 0 ? formatAvailableCount(visibleCount) : 'No active items')
                );
                section.attr('data-visible-item-count', String(visibleCount));

                section.find('.svdp-furniture-category-section-body').html(
                    renderCategorySectionBody(items, categoryKey)
                );
            });

            searchEmptyState.prop('hidden', !searchActive || totalVisibleMatches > 0);
            catalogContainer.attr('data-catalog-loaded', 'true');
            catalogLoadingState.prop('hidden', true);
            syncCategorySectionState();
            updateCatalogQuantities();
            updateCategorySelectedCounts();
        }

        function getVisibleItemsForCategory(category) {
            const items = category && Array.isArray(category.items) ? category.items : [];

            if (!state.searchQuery) {
                return items;
            }

            return items.filter(function(item) {
                const searchableText = normalizeSearchQuery([
                    item.name,
                    item.categoryLabel,
                    item.priceDisplay
                ].join(' '));

                return searchableText.indexOf(state.searchQuery) !== -1;
            });
        }

        function renderCategorySectionBody(items, categoryKey) {
            if (!items.length) {
                return '' +
                    '<p class="svdp-furniture-category-placeholder" data-category-placeholder="' + escapeHtml(categoryKey) + '">' +
                        (state.searchQuery
                            ? 'No items in this category match the current search.'
                            : 'No active catalog items are currently available in this category.') +
                    '</p>';
            }

            return items.map(function(item) {
                const itemId = Number(item.id);
                const quantity = Number(state.selectedItems[itemId] || 0);

                return '' +
                    '<article class="svdp-catalog-item' + (quantity > 0 ? ' is-selected' : '') + '" data-catalog-item="' + itemId + '">' +
                        '<div class="svdp-catalog-item-copy">' +
                            '<h5>' + escapeHtml(item.name) + '</h5>' +
                            '<p>' + escapeHtml(item.priceDisplay) + '</p>' +
                        '</div>' +
                        '<div class="svdp-catalog-item-controls">' +
                            '<button type="button" class="svdp-qty-btn" data-catalog-adjust="decrement" data-catalog-item-id="' + itemId + '" aria-label="Remove one ' + escapeHtml(item.name) + '"' + (quantity === 0 ? ' disabled' : '') + '>-</button>' +
                            '<span class="svdp-qty-value" data-catalog-qty="' + itemId + '">' + quantity + '</span>' +
                            '<button type="button" class="svdp-qty-btn" data-catalog-adjust="increment" data-catalog-item-id="' + itemId + '" aria-label="Add one ' + escapeHtml(item.name) + '">+</button>' +
                        '</div>' +
                    '</article>';
            }).join('');
        }

        function syncCategorySectionState() {
            const searchActive = state.searchQuery !== '';

            catalogContainer.find('[data-category-card]').each(function() {
                const card = $(this);
                const categoryKey = card.attr('data-category-card');
                const section = catalogContainer.find('[data-category-section="' + categoryKey + '"]');
                const visibleCount = Number(section.attr('data-visible-item-count') || 0);
                const isOpen = searchActive ? visibleCount > 0 : !!state.openCategories[categoryKey];
                const shouldShowSection = searchActive ? visibleCount > 0 : isOpen;

                card.attr('aria-expanded', isOpen ? 'true' : 'false');
                card.toggleClass('is-open', isOpen);
                section.prop('hidden', !shouldShowSection);
                section.toggleClass('is-open', shouldShowSection);
            });
        }

        function updateCatalogQuantities() {
            catalogContainer.find('[data-catalog-qty]').each(function() {
                const quantityNode = $(this);
                const itemId = Number(quantityNode.attr('data-catalog-qty'));
                const quantity = Number(state.selectedItems[itemId] || 0);

                quantityNode.text(quantity);

                const itemRow = catalogContainer.find('[data-catalog-item="' + itemId + '"]');
                itemRow.toggleClass('is-selected', quantity > 0);
                itemRow.find('[data-catalog-adjust="decrement"]').prop('disabled', quantity === 0);
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

            const deliveryFee = getCurrentVoucherType() === 'furniture' && deliveryRequiredInput.is(':checked')
                ? Number(svdpVouchers.deliveryFee || 50)
                : 0;
            const requestorMin = (estimatedTotalMin * 0.5) + deliveryFee;
            const requestorMax = (estimatedTotalMax * 0.5) + deliveryFee;

            summaryCount.text(itemCount);
            summaryTotal.text(formatMoneyRange(estimatedTotalMin, estimatedTotalMax));
            summaryRequestor.text(formatMoneyRange(requestorMin, requestorMax));
            summaryDeliveryFee.text('$' + deliveryFee.toFixed(2));
            deliveryFeeNote.prop('hidden', deliveryFee === 0);
            updateCategorySelectedCounts();
        }

        function updateCategorySelectedCounts() {
            const categorySelectedCounts = {};

            Object.keys(state.selectedItems).forEach(function(key) {
                const itemId = Number(key);
                const quantity = Number(state.selectedItems[key] || 0);
                const item = state.catalogById[itemId];

                if (!item || quantity <= 0) {
                    return;
                }

                categorySelectedCounts[item.category] = Number(categorySelectedCounts[item.category] || 0) + quantity;
            });

            catalogContainer.find('[data-category-selected-count]').each(function() {
                const counter = $(this);
                const categoryKey = counter.attr('data-category-selected-count');
                const selectedCount = Number(categorySelectedCounts[categoryKey] || 0);

                counter.text(selectedCount === 1 ? '1 selected' : selectedCount + ' selected');
            });
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
            message += '• Estimated Conference portion: <strong>' + escapeHtml(formatMoneyRange(response.estimatedRequestorPortionMin || 0, response.estimatedRequestorPortionMax || 0)) + '</strong><br>';

            if (response.deliveryRequired) {
                message += '• Delivery fee included: <strong>$' + escapeHtml(Number(response.deliveryFee || 0).toFixed(2)) + '</strong><br>';
            }

            message += '• This household can receive another furniture voucher after: <strong>' + escapeHtml(response.nextEligibleDate) + '</strong><br>';
            message += '<br><em>Final fulfilled pricing may vary from the estimate range shown above.</em>';

            return message;
        }

        function resetFormState() {
            form[0].reset();
            state.openCategories = {};
            state.searchQuery = '';
            state.selectedItems = {};
            searchInput.val('');
            setCurrentVoucherType(form.data('default-voucher-type') || 'clothing');
            syncVoucherTypeAvailability();
            syncDeliveryControls();

            if (state.catalogLoaded) {
                renderCatalog();
            } else {
                syncCategorySectionState();
                updateCatalogQuantities();
            }

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

        function formatAvailableCount(count) {
            return count === 1 ? '1 item ready' : count + ' items ready';
        }

        function formatMatchCount(count) {
            if (count === 0) {
                return 'No matches';
            }

            return count === 1 ? '1 match' : count + ' matches';
        }

        function normalizeSearchQuery(value) {
            return $.trim(String(value || '')).toLowerCase();
        }

        function escapeHtml(value) {
            return $('<div>').text(value == null ? '' : String(value)).html();
        }
    });

})(jQuery);
