jQuery(document).ready(function ($) {
	if ($(".wpes-settings").length > 0) {
		/**
		 * Settings panel
		 */
		let keys = 'enable_history,smtp-enabled,enable-smime,enable-dkim,smtp-is_html'.split(',');
		keys.forEach( (selector) => {
			$("#" + selector).on('change', function (e) { // we need 'function' here for 'this'.
				let target_id = e.target.id;
				$(".on-" + target_id).toggle($(this).is(':checked'));
				$(".not-" + target_id).toggle(!$(this).is(':checked'));
			}).trigger('change');
		});

		$(".on-regexp-test").each(function () { // we need 'function' here for 'this'.
			((field, regexp, label) => {
				$('#' + field).on('change keyup blur paste', function () {
					label.toggle(null !== ($(this).val() || "").match(new RegExp(regexp, 'i')));
				}).trigger('change');
			})($(this).attr('data-field'), $(this).attr('data-regexp'), $(this));
		});
	}

	if ($(".wpes-emails").length > 0) {
		/**
		 * Emails panel
		 */
		$(".email-item").on('click', function (e) { // we need 'function' here for 'this'.
			if ($(e.target).is('a.dashicons-download')) {
				e.stopPropagation();
				return true;
			}
			let alt = e.altKey || false;
			$(this).addClass('active').siblings().removeClass('active').removeClass( (index, className) => (className.match (/(^|\s)show-\S+/g) || []).join(' ') );

			let id = '#' + $(".email-item.active").attr('id').replace('email-', 'email-data-');
			let that = $(id);
			$('#mail-data-viewer .email-data').removeClass( (index, className) => (className.match (/(^|\s)show-\S+/g) || []).join(' ') );

			// Click to cycle through the views.
			let this_and_that = $(this).add(that);
			if (alt) {
				this_and_that.removeClass('show-body').removeClass('show-alt-body').removeClass('show-headers').addClass('show-debug');
			} else if ($(this).is('.show-body')) {
				this_and_that.removeClass('show-body').addClass('show-headers');
			} else if ($(this).is('.show-headers')) {
				this_and_that.removeClass('show-headers').addClass('show-alt-body');
			} else if ($(this).is('.show-alt-body')) {
				this_and_that.removeClass('show-alt-body').addClass('show-body');
			} else {
				this_and_that.addClass('show-body');
			}
			$(window).trigger('resize');
		});

		$(window).bind('resize', function () { // we need 'function' here for 'this'.
			$(".autofit").each(function () {
				$(this).css('width', $(this).parent().innerWidth());
				$(this).css('height', $(this).parent().innerHeight());
			});
		}).trigger('resize');
	}

	if ($(".wpes-admins").length > 0) {
		/**
		 * Admins panel
		 */
		let t = function () { // we need 'function' here for 'this'.
			if (/^\/[\s\S]+\/[i]?$/.test(($(this).val() || ""))) {
				let that = this;
				let re = $(that).val();

				re = re.split(re.substr(0, 1));
				re = new RegExp(re[1], re[2]);

				$(".a-fail").each(function () {
					$(this).toggleClass('match', re.test(($(this).text() || "")));
				});
			} else {
				$(".a-fail").removeClass('match');
			}
		};
		$(".a-regexp").bind('blur', function () { // we need 'function' here for 'this'.
			let val = ($(this).val() || "");
			if ("" === val) {
				return $(this).removeClass('error match');
			}
			$(this).toggleClass('error', !/^\/[\s\S]+\/[i]?$/.test(val)).not('.error').addClass('match');
		}).bind('focus', function (e) { // we need 'function' here for 'this'.
			$(".a-fail,.a-regexp").removeClass('match');
			$(this).removeClass('error match');
			t.apply(this, [e]);
		}).bind('keyup', t);
	}
});
