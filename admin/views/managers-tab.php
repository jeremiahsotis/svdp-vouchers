<div class="svdp-managers-section">
    <h2>Override Managers</h2>
    <p>Managers can approve emergency vouchers when duplicates are detected. Each manager has a unique 4-character code.</p>

    <div class="svdp-admin-section">
        <h3>Add New Manager</h3>
        <div class="svdp-form-inline">
            <input type="text" id="svdp-new-manager-name" placeholder="Manager Name" style="width: 300px;">
            <input type="text" id="svdp-new-manager-code" placeholder="Optional 4-character code" maxlength="4" pattern="[A-Z2-9]{4}" style="width: 180px; text-transform: uppercase;">
            <button type="button" id="svdp-add-manager" class="button button-primary">Add Manager</button>
        </div>
    </div>

    <div class="svdp-admin-section">
        <h3>Existing Managers</h3>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Status</th>
                    <th>Created</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody id="svdp-managers-list">
                <tr>
                    <td colspan="4">Loading...</td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

<!-- Manager Code Modal -->
<div id="svdp-manager-code-modal" class="svdp-modal" style="display: none;">
    <div class="svdp-modal-content">
        <h3>Manager Code Generated</h3>
        <p><strong>Important:</strong> Save this code securely. It will only be shown once!</p>
        <div style="text-align: center; margin: 20px 0;">
            <div style="font-size: 32px; font-weight: bold; letter-spacing: 5px; background: #f0f0f0; padding: 20px; border-radius: 5px;">
                <span id="svdp-generated-code"></span>
            </div>
        </div>
        <p style="margin-top: 20px;">Manager: <strong id="svdp-manager-name-display"></strong></p>
        <button type="button" class="button button-primary" onclick="document.getElementById('svdp-manager-code-modal').style.display='none'">Close</button>
    </div>
</div>

<style>
.svdp-admin-section {
    background: #fff;
    padding: 20px;
    margin: 20px 0;
    border: 1px solid #ccd0d4;
    box-shadow: 0 1px 1px rgba(0,0,0,.04);
}

.svdp-form-inline {
    display: flex;
    gap: 10px;
    align-items: center;
}

.svdp-modal {
    display: none;
    position: fixed;
    z-index: 100000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0,0,0,0.5);
}

.svdp-modal-content {
    background-color: #fefefe;
    margin: 10% auto;
    padding: 30px;
    border: 1px solid #888;
    width: 500px;
    max-width: 90%;
    border-radius: 5px;
}

.manager-status-active {
    color: #46b450;
}

.manager-status-inactive {
    color: #dc3232;
}
</style>
