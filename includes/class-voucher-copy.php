<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Centralized user-facing voucher copy.
 */
class SVDP_Voucher_Copy {

    public static function get_redemption_rule_text() {
        return 'This voucher expires 30 days after issuance. It must be redeemed in one visit; remaining items cannot be saved for a later visit.';
    }

    public static function get_delivery_copy() {
        return [
            'addressNotVerified' => 'Address not verified',
            'deliveryLabel' => 'Delivery',
            'pickupLabel' => 'Pickup',
            'yesLabel' => 'Yes',
            'noLabel' => 'No',
            'verifiedSuffix' => '(verified)',
            'deliveryRequestedToTemplate' => 'Delivery requested to %s.',
            'pickupRequested' => 'Pickup requested. No delivery address was provided.',
            'deliveryDetailsHeading' => 'Delivery Details',
        ];
    }

    public static function get_document_copy() {
        return [
            'neighborReceiptTitle' => 'Furniture Voucher Receipt',
            'neighborReceiptSubtitle' => 'Neighbor copy. No prices are shown on this receipt.',
            'conferenceInvoiceTitle' => 'Conference Invoice',
            'completionDocumentsHeading' => 'Completion Documents',
            'openNeighborReceiptLabel' => 'Open Neighbor Receipt',
            'openConferenceInvoiceLabel' => 'Open Conference Invoice',
            'completeVoucherHeading' => 'Complete Voucher',
            'completeVoucherDescription' => 'All requested items are resolved. Completing this voucher will generate the neighbor receipt and stored conference invoice.',
            'completeVoucherButton' => 'Complete Voucher & Generate Documents',
        ];
    }

    public static function get_request_copy() {
        return [
            'clothingSuccessTitle' => 'Voucher Created Successfully!',
            'clothingReadyText' => 'The voucher has been created and is ready to use immediately.',
            'importantRemindersHeading' => 'Important Reminders:',
            'thriftStoreHoursText' => 'Thrift Store hours: 9:30 AM - 4:00 PM',
            'customerServiceReminder' => 'Ask your Neighbor to check in at Customer Service before shopping',
            'nextEligibleTemplate' => 'This household can receive another voucher after: %s',
            'coatEligibleAfterTemplate' => 'Winter coat eligible after: %s',
        ];
    }

    public static function get_cashier_copy() {
        return [
            'connecting' => 'Connecting',
            'keepingSessionLive' => 'Keeping Session Live',
            'shellLive' => 'Shell Live',
            'connectionRetrying' => 'Connection Retrying',
            'reauthRequired' => 'Re-auth Required',
            'overrideReasonPlaceholder' => 'Select a reason...',
            'overrideReasonsLoadFailed' => 'Override reasons could not be loaded.',
            'saving' => 'Saving...',
            'checking' => 'Checking...',
            'uploading' => 'Uploading...',
            'generating' => 'Generating...',
            'voucherRedeemedSuccessTemplate' => 'Voucher redeemed successfully. Estimated value: $%s',
            'voucherRedeemFailed' => 'Failed to redeem voucher.',
            'statusUpdateFailed' => 'Failed to update status',
            'coatIssueSuccessTemplate' => 'Coats issued successfully. Total coats: %s.',
            'coatIssueFailed' => 'Failed to issue coats.',
            'emergencyCreateFailed' => 'Unable to create the emergency voucher.',
            'emergencyCreateSuccess' => 'Emergency clothing voucher created successfully.',
            'managerCodeInvalid' => 'Enter a valid 6-digit manager code.',
            'overrideReasonRequired' => 'Select an override reason.',
            'managerCodeValidationFailed' => 'Manager code validation failed.',
            'overrideValidationFailed' => 'Unable to validate the override.',
            'similarVoucherHeading' => 'Similar Clothing Voucher Found',
            'similarCreateButton' => 'Create New Voucher',
            'similarCancelButton' => 'Cancel',
            'denialReasonTemplate' => 'Duplicate found: %s %s received a voucher from %s on %s. Next eligible: %s',
            'photoRequired' => 'Choose one photo to upload before continuing.',
            'photoUploadSuccess' => 'Photo uploaded successfully.',
            'photoUploadFailed' => 'Failed to upload the photo.',
            'furnitureItemCompleted' => 'Furniture item marked completed.',
            'furnitureItemCompleteFailed' => 'Failed to complete the furniture item.',
            'substituteSaved' => 'Substitute item saved.',
            'substituteSaveFailed' => 'Failed to save the substitute item.',
            'furnitureItemCancelled' => 'Furniture item cancelled.',
            'furnitureItemCancelFailed' => 'Failed to cancel the furniture item.',
            'furnitureVoucherCompleteFallback' => 'Complete Voucher',
            'furnitureVoucherCompletedTemplate' => 'Furniture voucher completed.%s',
            'furnitureVoucherInvoiceGeneratedTemplate' => ' Invoice %s generated.',
            'furnitureVoucherCompleteFailed' => 'Failed to complete the furniture voucher.',
            'actualPriceRequired' => 'Enter an actual price greater than zero.',
            'substituteCatalogRequired' => 'Choose a catalog item for the substitute.',
            'substituteNameRequired' => 'Enter a substitute item name.',
            'cancellationReasonRequired' => 'Choose a cancellation reason.',
            'currentRedemptionTotalTemplate' => 'Current total: %s of %s',
            'estimatedValueTemplate' => 'Estimated value: $%s',
            'adultItemsMaxExceeded' => 'Adult items exceed the allowed maximum.',
            'childItemsMaxExceeded' => 'Child items exceed the allowed maximum.',
            'totalItemsMaxExceeded' => 'Total items exceed the voucher limit.',
            'redeemedItemRequired' => 'Enter at least one redeemed item.',
            'showMore' => 'Show More',
            'showMoreWithCountTemplate' => 'Show %s More',
            'showingOfTemplate' => '%s of %s',
            'emptyDetailMessage' => 'Select a voucher from the list to view its details.',
        ];
    }

    public static function get_coat_copy() {
        return [
            'label' => 'Coat',
            'issued' => 'Issued',
            'available' => 'Available',
            'notEligible' => 'Not Eligible',
            'availableMessage' => 'Coat Available',
            'notEligibleUntilTemplate' => 'Coat not eligible until %s',
            'coatsIssuedTemplate' => 'Coats Issued: %s adults, %s children on %s',
            'issueWinterCoatsHeading' => 'Issue Winter Coats',
            'issueWinterCoatsDescription' => 'Record coat issuance without leaving the selected voucher.',
            'issueCoatButton' => 'Issue Coat',
            'issueCoatsButton' => 'Issue Coats',
            'adultCoatsLabel' => 'Adult Coats *',
            'childCoatsLabel' => 'Children\'s Coats *',
            'adultCoatsMaximumTemplate' => 'Maximum: %s adult coats',
            'childCoatsMaximumTemplate' => 'Maximum: %s children\'s coats',
            'totalCoatsTemplate' => 'Total coats: %s',
            'issueAtLeastOne' => 'Issue at least one coat.',
            'adultCoatsExceedHousehold' => 'Adult coats exceed the household count.',
            'childCoatsExceedHousehold' => 'Children\'s coats exceed the household count.',
            'invalidCounts' => 'Invalid coat counts',
            'mustIssueAtLeastOne' => 'Must issue at least one coat',
            'alreadyReceivedTemplate' => 'This household already received a coat this season. Next eligible date: %s',
            'updateFailed' => 'Failed to update coat status',
        ];
    }

    public static function get_email_copy() {
        return [
            'subjectTemplate' => 'New %s Voucher Created - %s',
            'headerTitle' => 'New Voucher Created',
            'createdIntroTemplate' => 'A new virtual %s voucher has been created for your conference.',
            'neighborLabel' => 'Neighbor',
            'dateOfBirthLabel' => 'Date of Birth',
            'householdSizeLabel' => 'Household Size',
            'voucherTypeLabel' => 'Voucher Type',
            'requestedItemsLabel' => 'Requested Items',
            'voucherAmountLabel' => 'Voucher Amount',
            'createdLabel' => 'Created',
            'expiresLabel' => 'Expires',
            'deliveryLabel' => 'Delivery',
            'deliveryAddressLabel' => 'Delivery Address',
            'createdByLabel' => 'Created By',
            'vincentianEmailLabel' => 'Vincentian Email',
            'reminderLabel' => 'Reminder',
            'neighborNeedsHeading' => 'What the Neighbor needs to know:',
            'furnitureItemsSaved' => 'The requested items are saved and visible to the cashier team.',
            'deliveryDetailsIncluded' => 'Delivery details are included when requested.',
            'clothingStoreHours' => 'Thrift Store hours: 9:30 AM - 4:00 PM',
            'clothingCustomerService' => 'Stop by Customer Service before shopping',
            'automatedFooter' => 'This is an automated notification from the SVdP Virtual Voucher System.',
            'questionsFooter' => 'For questions, please contact the Vincentian listed above.',
        ];
    }

    public static function get_client_copy_payload() {
        return [
            'delivery' => self::get_delivery_copy(),
            'documents' => self::get_document_copy(),
            'request' => self::get_request_copy(),
            'cashier' => self::get_cashier_copy(),
            'coat' => self::get_coat_copy(),
            'email' => self::get_email_copy(),
        ];
    }

    public static function format($template, $values = []) {
        $values = (array) $values;
        $index = 0;

        return preg_replace_callback('/%s/', function() use ($values, &$index) {
            $value = $values[$index] ?? '';
            $index++;

            return (string) $value;
        }, (string) $template);
    }

    public static function delivery_requested_to($address) {
        $copy = self::get_delivery_copy();

        return self::format($copy['deliveryRequestedToTemplate'], [$address]);
    }

    public static function get_cashier_message($key, $fallback = '') {
        $copy = self::get_cashier_copy();

        return $copy[$key] ?? $fallback;
    }
}
