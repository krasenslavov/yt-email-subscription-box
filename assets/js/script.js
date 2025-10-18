/**
 * YT Email Subscription Box - Frontend Script
 *
 * @package YT_Email_Subscription_Box
 */

(function ($) {
	'use strict';

	$(document).ready(function () {
		/**
		 * Handle subscription form submission
		 */
		$('.yt-esb-form').on('submit', function (e) {
			e.preventDefault();

			const $form = $(this);
			const $button = $form.find('.yt-esb-button');
			const $message = $form.find('.yt-esb-message');
			const $input = $form.find('.yt-esb-input');
			const email = $input.val().trim();

			// Reset message
			$message.removeClass('success error loading').hide().text('');

			// Validate email
			if (!email || !isValidEmail(email)) {
				showMessage($message, 'error', 'Please enter a valid email address.');
				return;
			}

			// Disable form
			$button.prop('disabled', true);
			$input.prop('disabled', true);
			showMessage($message, 'loading', 'Subscribing...');

			// AJAX request
			$.ajax({
				url: ytEsbAjax.ajax_url,
				type: 'POST',
				data: {
					action: 'yt_esb_subscribe',
					nonce: ytEsbAjax.nonce,
					email: email
				},
				success: function (response) {
					if (response.success) {
						showMessage($message, 'success', response.data.message);
						$input.val(''); // Clear input
					} else {
						showMessage($message, 'error', response.data.message);
					}
				},
				error: function () {
					showMessage(
						$message,
						'error',
						'An error occurred. Please try again later.'
					);
				},
				complete: function () {
					// Re-enable form
					$button.prop('disabled', false);
					$input.prop('disabled', false);
				}
			});
		});

		/**
		 * Validate email format
		 *
		 * @param {string} email Email address
		 * @return {boolean} Is valid
		 */
		function isValidEmail(email) {
			const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
			return re.test(email);
		}

		/**
		 * Show message
		 *
		 * @param {jQuery} $element Message element
		 * @param {string} type Message type (success, error, loading)
		 * @param {string} text Message text
		 */
		function showMessage($element, type, text) {
			$element
				.removeClass('success error loading')
				.addClass(type)
				.text(text)
				.show();
		}
	});
})(jQuery);
