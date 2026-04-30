(function($) {
    'use strict';

    $(document).ready(function() {
        if ($('#svdp-managers-list').length === 0) {
            return; // Not on managers tab
        }

        loadManagers();

        // Add manager
        $('#svdp-add-manager').on('click', function() {
            const name = $('#svdp-new-manager-name').val().trim();
            const code = $('#svdp-new-manager-code').val().trim().toUpperCase();

            if (!name) {
                alert('Please enter a manager name');
                return;
            }

            if (code && !/^[A-Z2-9]{4}$/.test(code)) {
                alert('Code must be 4 characters A-Z, 2-9');
                return;
            }

            $.ajax({
                url: svdpAdmin.ajaxUrl,
                method: 'POST',
                data: {
                    action: 'svdp_add_manager',
                    nonce: svdpAdmin.nonce,
                    name: name,
                    code: code
                },
                success: function(response) {
                    if (response.success) {
                        // Show code modal
                        $('#svdp-generated-code').text(response.data.code);
                        $('#svdp-manager-name-display').text(response.data.name);
                        $('#svdp-manager-code-modal').show();

                        $('#svdp-new-manager-name').val('');
                        $('#svdp-new-manager-code').val('');
                        loadManagers();
                    } else {
                        alert('Error: ' + response.data);
                    }
                },
                error: function() {
                    alert('Failed to add manager');
                }
            });
        });

        // Deactivate manager
        $(document).on('click', '.svdp-deactivate-manager', function() {
            if (!confirm('Deactivate this manager? Their code will no longer work.')) {
                return;
            }

            const id = $(this).data('id');

            $.ajax({
                url: svdpAdmin.ajaxUrl,
                method: 'POST',
                data: {
                    action: 'svdp_deactivate_manager',
                    nonce: svdpAdmin.nonce,
                    id: id
                },
                success: function(response) {
                    if (response.success) {
                        loadManagers();
                    } else {
                        alert('Error: ' + response.data);
                    }
                },
                error: function() {
                    alert('Failed to deactivate manager');
                }
            });
        });

        // Regenerate code
        $(document).on('click', '.svdp-regenerate-code', function() {
            if (!confirm('Generate a new code for this manager? The old code will stop working immediately.')) {
                return;
            }

            const id = $(this).data('id');
            const name = $(this).data('name');

            $.ajax({
                url: svdpAdmin.ajaxUrl,
                method: 'POST',
                data: {
                    action: 'svdp_regenerate_code',
                    nonce: svdpAdmin.nonce,
                    id: id
                },
                success: function(response) {
                    if (response.success) {
                        // Show new code
                        $('#svdp-generated-code').text(response.data.code);
                        $('#svdp-manager-name-display').text(name);
                        $('#svdp-manager-code-modal').show();
                    } else {
                        alert('Error: ' + response.data);
                    }
                },
                error: function() {
                    alert('Failed to regenerate code');
                }
            });
        });
    });

    function loadManagers() {
        $.ajax({
            url: svdpAdmin.ajaxUrl,
            method: 'POST',
            data: {
                action: 'svdp_get_managers',
                nonce: svdpAdmin.nonce
            },
            success: function(response) {
                if (response.success) {
                    renderManagers(response.data);
                }
            }
        });
    }

    function renderManagers(managers) {
        const tbody = $('#svdp-managers-list');
        tbody.empty();

        if (managers.length === 0) {
            tbody.append('<tr><td colspan="4">No managers yet. Add one above!</td></tr>');
            return;
        }

        managers.forEach(function(manager) {
            const statusClass = manager.active == 1 ? 'manager-status-active' : 'manager-status-inactive';
            const statusText = manager.active == 1 ? 'Active' : 'Inactive';

            const row = $('<tr>');
            row.append($('<td>').text(manager.name));
            row.append($('<td>').html('<span class="' + statusClass + '">' + statusText + '</span>'));
            row.append($('<td>').text(manager.created_date));

            const actions = $('<td>');
            if (manager.active == 1) {
                actions.append(
                    $('<button>')
                        .addClass('button button-small svdp-regenerate-code')
                        .attr('data-id', manager.id)
                        .attr('data-name', manager.name)
                        .text('Regenerate Code')
                );
                actions.append(' ');
                actions.append(
                    $('<button>')
                        .addClass('button button-small svdp-deactivate-manager')
                        .attr('data-id', manager.id)
                        .text('Deactivate')
                );
            } else {
                actions.append($('<span>').text('-'));
            }

            row.append(actions);
            tbody.append(row);
        });
    }

})(jQuery);
