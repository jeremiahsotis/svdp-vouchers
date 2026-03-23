<div class="svdp-emergency-panel">
    <div class="svdp-emergency-panel-header">
        <div>
            <h2>Emergency Voucher</h2>
            <p>Create a same-day clothing voucher without leaving the shell.</p>
        </div>
        <button type="button" class="svdp-btn svdp-btn-secondary" @click="$store.cashier.emergencyOpen = false">Close</button>
    </div>

    <div id="svdpEmergencyMessage" class="svdp-message" style="display: none;"></div>

    <form id="svdpEmergencyForm" class="svdp-form" data-cashier-action="emergency">
        <div class="svdp-form-row">
            <div class="svdp-form-group">
                <label for="svdpEmergencyFirstName">First Name *</label>
                <input id="svdpEmergencyFirstName" type="text" name="firstName" required>
            </div>
            <div class="svdp-form-group">
                <label for="svdpEmergencyLastName">Last Name *</label>
                <input id="svdpEmergencyLastName" type="text" name="lastName" required>
            </div>
        </div>

        <div class="svdp-form-group">
            <label for="svdpEmergencyDob">Date of Birth *</label>
            <input id="svdpEmergencyDob" type="date" name="dob" required>
        </div>

        <div class="svdp-form-row">
            <div class="svdp-form-group">
                <label for="svdpEmergencyAdults">Adults *</label>
                <input id="svdpEmergencyAdults" type="number" name="adults" min="0" value="1" required>
                <small class="svdp-help-text">Emergency clothing vouchers use the existing cashier rules.</small>
            </div>
            <div class="svdp-form-group">
                <label for="svdpEmergencyChildren">Children *</label>
                <input id="svdpEmergencyChildren" type="number" name="children" min="0" value="0" required>
                <small class="svdp-help-text">Count every child in the household for item allocation.</small>
            </div>
        </div>

        <button type="submit" class="svdp-btn svdp-btn-primary">Create Emergency Voucher</button>
    </form>
</div>
