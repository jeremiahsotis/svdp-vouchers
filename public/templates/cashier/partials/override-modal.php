<div class="svdp-modal" x-show="$store.cashier && $store.cashier.overrideOpen" x-transition.opacity>
    <div class="svdp-modal-content">
        <h3>Duplicate Found: Manager Approval Required</h3>
        <p id="svdpOverrideMessage"></p>

        <div class="svdp-form-group">
            <label for="svdpManagerCode">Manager Code *</label>
            <input type="password" id="svdpManagerCode" placeholder="Enter 4-character code" maxlength="4" pattern="[A-Z2-9]{4}" style="text-transform: uppercase;">
        </div>

        <div class="svdp-form-group">
            <label for="svdpManagerName">Manager Name *</label>
            <input type="text" id="svdpManagerName" placeholder="Enter manager name">
        </div>

        <div class="svdp-form-group">
            <label for="svdpOverrideReason">Reason *</label>
            <select id="svdpOverrideReason">
                <option value="">Select a reason...</option>
            </select>
        </div>

        <div class="svdp-modal-buttons">
            <button type="button" id="svdpCancelOverride" class="svdp-btn svdp-btn-secondary">Cancel</button>
            <button type="button" id="svdpConfirmOverride" class="svdp-btn svdp-btn-warning">Validate &amp; Create</button>
        </div>
    </div>
</div>
