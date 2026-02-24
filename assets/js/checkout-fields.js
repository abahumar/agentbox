/**
 * Checkout Fields Conditional Display
 *
 * Show/hide pickup date and time fields based on selected collection method.
 */
jQuery(function($) {
	'use strict';

	var methodsRequireDateTime = aboxCheckoutFieldsVars.methodsRequireDateTime || [];
	var $collectionMethodField = $('#collection_method');
	var $pickupDateField = $('#pickup_date_field');
	var $pickupTimeField = $('#pickup_time_field');

	/**
	 * Toggle pickup date/time fields based on collection method
	 */
	function togglePickupFields() {
		var selectedMethod = $collectionMethodField.val();

		if (methodsRequireDateTime.indexOf(selectedMethod) !== -1) {
			// Method requires date/time - show fields and make required
			$pickupDateField.slideDown(200);
			$pickupTimeField.slideDown(200);
			$pickupDateField.find('input').prop('required', true);
			$pickupTimeField.find('input').prop('required', true);
		} else {
			// Method doesn't require date/time - hide fields and remove required
			$pickupDateField.slideUp(200);
			$pickupTimeField.slideUp(200);
			$pickupDateField.find('input').prop('required', false).val('');
			$pickupTimeField.find('input').prop('required', false).val('');
		}
	}

	// Initialize on page load
	togglePickupFields();

	// Toggle on collection method change
	$collectionMethodField.on('change', togglePickupFields);

	// Re-initialize after WooCommerce updates checkout
	$(document.body).on('updated_checkout', function() {
		$collectionMethodField = $('#collection_method');
		$pickupDateField = $('#pickup_date_field');
		$pickupTimeField = $('#pickup_time_field');
		togglePickupFields();
		$collectionMethodField.on('change', togglePickupFields);
	});
});
