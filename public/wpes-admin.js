jQuery(document).ready(function ($) {
	if ($(".wpes-settings").length > 0) {
		/**
		 * Settings panel
		 */
		var keys = 'enable_history,smtp-enabled,enable-smime,enable-dkim,smtp-is_html'.split(',');
		for (var key in keys) {
			var i = keys[key];
			$("#" + i).on('change', function (e) {
				var i = e.target.id;
				$(".on-" + i).toggle($(this).is(':checked'));
				$(".not-" + i).toggle(!$(this).is(':checked'));
			}).trigger('change');
		}
		$(".on-regexp-test").each(function () {
			(function (field, regexp, label) {
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
		$(".email-item").on('click', function (e) {
			if ($(e.target).is('a.dashicons-download')) {
				e.stopPropagation();
				return true;
			}
			var alt = e.altKey || false;
			$(this).addClass('active').siblings().removeClass('show-body').removeClass('show-debug').removeClass('show-headers').removeClass('show-alt-body').removeClass('active');
			var id = '#' + $(".email-item.active").attr('id').replace('email-', 'email-data-');
			var that = $(id);
			$('#mail-data-viewer .email-data').removeClass('show-body').removeClass('show-debug').removeClass('show-headers').removeClass('show-alt-body');

			if (alt) {
				$(this).removeClass('show-body').removeClass('show-alt-body').removeClass('show-headers').addClass('show-debug');
				$(that).removeClass('show-body').removeClass('show-alt-body').removeClass('show-headers').addClass('show-debug');
			} else if ($(this).is('.show-body')) {
				$(this).removeClass('show-body').addClass('show-headers');
				$(that).removeClass('show-body').addClass('show-headers');
			} else if ($(this).is('.show-headers')) {
				$(this).removeClass('show-headers').addClass('show-alt-body');
				$(that).removeClass('show-headers').addClass('show-alt-body');
			} else if ($(this).is('.show-alt-body')) {
				$(this).removeClass('show-alt-body').addClass('show-body');
				$(that).removeClass('show-alt-body').addClass('show-body');
			} else {
				$(this).addClass('show-body');
				$(that).addClass('show-body');
			}
			$(window).trigger('resize');
		});

		$(window).bind('resize', function () {
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
		var t = function () {
			if (/^\/[\s\S]+\/[i]?$/.test(($(this).val() || ""))) {
				var that = this;
				var re = $(that).val();

				re = re.split(re.substr(0, 1));
				re = new RegExp(re[1], re[2]);

				$(".a-fail").each(function () {
					$(this).toggleClass('match', re.test(($(this).text() || "")));
				});
			} else {
				$(".a-fail").removeClass('match');
			}
		};
		$(".a-regexp").bind('blur', function () {
			var val = ($(this).val() || "");
			if ("" === val) {
				return $(this).removeClass('error match');
			}
			$(this).toggleClass('error', !/^\/[\s\S]+\/[i]?$/.test(val)).not('.error').addClass('match');
		}).bind('focus', function (e) {
			$(".a-fail,.a-regexp").removeClass('match');
			$(this).removeClass('error match');
			t.apply(this, [e]);
		}).bind('keyup', t);
	}
});
