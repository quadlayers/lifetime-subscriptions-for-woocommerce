export const onDocumentLoaded = (cb) => {
	if (/comp|inter|loaded/.test(document.readyState)) {
		cb();
	} else {
		document.addEventListener('DOMContentLoaded', cb, false);
	}
};

export const is_blocked = function ($node) {
	return $node.is('.processing') || $node.parents('.processing').length;
};

export const block = function ($node) {
	if (!is_blocked($node)) {
		$node.addClass('processing').block({
			message: null,
			overlayCSS: {
				background: '#fff',
				opacity: 0.6,
			},
		});
	}
};

export const unblock = function ($node) {
	$node.removeClass('processing').unblock();
};
