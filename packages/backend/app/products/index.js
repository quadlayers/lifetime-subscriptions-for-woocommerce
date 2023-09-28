import $ from 'jquery';

import { onDocumentLoaded } from '../../helpers';

function toggle_is_lifetime_variable({ field, isActive }) {
	if (isActive) {
		field.classList.add('is_lifetime_active_variation');
	} else {
		field.classList.remove('is_lifetime_active_variation');
	}
}

onDocumentLoaded(() => {
	/**
	 * Use jQuery to detect the ajax load event
	 */
	$('#variable_product_options').on('change', function (e) {
		const element = e.target;
		const toggle = element
			?.closest('.woocommerce_variation')
			?.querySelector('#_is_lifetime');
		if (toggle) {
			const isActive = !!toggle.checked;
			const field = element?.closest('.woocommerce_variation');
			toggle_is_lifetime_variable({ field, isActive });
		}
	});
});
