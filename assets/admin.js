/**
 * Admin JavaScript for Security & Comments Lite Tweaks
 *
 * @package SecurityCommentsLiteTweaks
 * @since   1.0.0
 */

(function($) {
	'use strict';

	$(document).ready(function() {
		// Auto-dismiss notices after 5 seconds
		setTimeout(function() {
			$('.notice.is-dismissible').fadeOut();
		}, 5000);
	});

})(jQuery);
