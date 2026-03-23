(function($) {
    'use strict';

    $(document).ready(function() {
        initializeFurnitureCatalogTab();
        initializeFurnitureReasonsTab();

        $(document).on('click', '.svdp-close-modal', function() {
            const target = $(this).data('target');
            $(target).hide();
        });
    });

    function initializeFurnitureCatalogTab() {
        const addForm = $('#svdp-furniture-catalog-form');
        if (addForm.length === 0) {
            return;
        }

        syncPricingFields(addForm);

        addForm.on('change', '[name="pricing_type"]', function() {
            syncPricingFields(addForm);
        });

        $('#svdp-edit-furniture-catalog-form').on('change', '[name="pricing_type"]', function() {
            syncPricingFields($('#svdp-edit-furniture-catalog-form'));
        });

        $('#svdp-add-catalog-item').on('click', function() {
            submitCatalogForm(addForm, 'svdp_add_furniture_catalog_item');
        });

        $(document).on('click', '.svdp-edit-furniture-item', function() {
            const button = $(this);

            $('#svdp-edit-catalog-id').val(button.data('id'));
            $('#svdp-edit-catalog-name').val(button.data('name'));
            $('#svdp-edit-catalog-category').val(button.data('category'));
            $('#svdp-edit-catalog-pricing-type').val(button.attr('data-pricing-type'));
            $('#svdp-edit-catalog-price-min').val(button.attr('data-price-min'));
            $('#svdp-edit-catalog-price-max').val(button.attr('data-price-max'));
            $('#svdp-edit-catalog-price-fixed').val(button.attr('data-price-fixed'));
            $('#svdp-edit-catalog-sort-order').val(button.attr('data-sort-order'));

            syncPricingFields($('#svdp-edit-furniture-catalog-form'));
            $('#svdp-edit-furniture-catalog-modal').show();
        });

        $('#svdp-save-furniture-catalog-edit').on('click', function() {
            submitCatalogForm($('#svdp-edit-furniture-catalog-form'), 'svdp_update_furniture_catalog_item', {
                id: $('#svdp-edit-catalog-id').val()
            });
        });

        $(document).on('click', '.svdp-toggle-furniture-item-active', function() {
            const id = $(this).data('id');
            const isActive = Number($(this).data('active')) === 1;
            const targetActive = isActive ? 0 : 1;
            const actionLabel = isActive ? 'archive' : 'restore';

            if (!window.confirm('Are you sure you want to ' + actionLabel + ' this catalog item?')) {
                return;
            }

            $.ajax({
                url: svdpAdmin.ajaxUrl,
                method: 'POST',
                data: {
                    action: 'svdp_set_furniture_catalog_item_active',
                    nonce: svdpAdmin.nonce,
                    id: id,
                    active: targetActive
                },
                success: function(response) {
                    if (response.success) {
                        window.location.reload();
                    } else {
                        window.alert('Error: ' + response.data);
                    }
                },
                error: function() {
                    window.alert('Failed to update the catalog item status.');
                }
            });
        });
    }

    function initializeFurnitureReasonsTab() {
        const addForm = $('#svdp-furniture-reason-form');
        if (addForm.length === 0) {
            return;
        }

        $('#svdp-add-furniture-reason').on('click', function() {
            submitReasonForm(addForm, 'svdp_add_furniture_cancellation_reason');
        });

        $(document).on('click', '.svdp-edit-furniture-reason', function() {
            const button = $(this);

            $('#svdp-edit-furniture-reason-id').val(button.data('id'));
            $('#svdp-edit-furniture-reason-text').val(button.data('text'));
            $('#svdp-edit-furniture-reason-order').val(button.attr('data-display-order'));
            $('#svdp-edit-furniture-reason-modal').show();
        });

        $('#svdp-save-furniture-reason-edit').on('click', function() {
            submitReasonForm($('#svdp-edit-furniture-reason-form'), 'svdp_update_furniture_cancellation_reason', {
                id: $('#svdp-edit-furniture-reason-id').val()
            });
        });

        $(document).on('click', '.svdp-toggle-furniture-reason-active', function() {
            const id = $(this).data('id');
            const isActive = Number($(this).data('active')) === 1;
            const targetActive = isActive ? 0 : 1;
            const actionLabel = isActive ? 'archive' : 'restore';

            if (!window.confirm('Are you sure you want to ' + actionLabel + ' this cancellation reason?')) {
                return;
            }

            $.ajax({
                url: svdpAdmin.ajaxUrl,
                method: 'POST',
                data: {
                    action: 'svdp_set_furniture_cancellation_reason_active',
                    nonce: svdpAdmin.nonce,
                    id: id,
                    active: targetActive
                },
                success: function(response) {
                    if (response.success) {
                        window.location.reload();
                    } else {
                        window.alert('Error: ' + response.data);
                    }
                },
                error: function() {
                    window.alert('Failed to update the cancellation reason status.');
                }
            });
        });
    }

    function submitCatalogForm(form, action, extraData) {
        const payload = $.extend({
            action: action,
            nonce: svdpAdmin.nonce,
            name: form.find('[name="name"]').val().trim(),
            category: form.find('[name="category"]').val(),
            pricing_type: form.find('[name="pricing_type"]').val(),
            price_min: form.find('[name="price_min"]').val(),
            price_max: form.find('[name="price_max"]').val(),
            price_fixed: form.find('[name="price_fixed"]').val(),
            sort_order: form.find('[name="sort_order"]').val()
        }, extraData || {});

        $.ajax({
            url: svdpAdmin.ajaxUrl,
            method: 'POST',
            data: payload,
            success: function(response) {
                if (response.success) {
                    window.location.reload();
                } else {
                    window.alert('Error: ' + response.data);
                }
            },
            error: function() {
                window.alert('Failed to save the catalog item.');
            }
        });
    }

    function submitReasonForm(form, action, extraData) {
        const payload = $.extend({
            action: action,
            nonce: svdpAdmin.nonce,
            reason_text: form.find('[name="reason_text"]').val().trim(),
            display_order: form.find('[name="display_order"]').val()
        }, extraData || {});

        $.ajax({
            url: svdpAdmin.ajaxUrl,
            method: 'POST',
            data: payload,
            success: function(response) {
                if (response.success) {
                    window.location.reload();
                } else {
                    window.alert('Error: ' + response.data);
                }
            },
            error: function() {
                window.alert('Failed to save the cancellation reason.');
            }
        });
    }

    function syncPricingFields(form) {
        const pricingType = form.find('[name="pricing_type"]').val();

        form.find('[data-pricing-fields="range"]').toggle(pricingType === 'range');
        form.find('[data-pricing-fields="fixed"]').toggle(pricingType === 'fixed');
    }

})(jQuery);
