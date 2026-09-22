/* global jQuery */
(function ($) {
	'use strict';

	function activateTab($tab) {
		var $tabs = $tab.closest('dl');
		var contentLocation = $tab.attr('href');

		if (!contentLocation || contentLocation.charAt(0) !== '#') {
			return;
		}

		$tabs.find('a.active').removeClass('active').attr('aria-selected', 'false');
		$tab.addClass('active').attr('aria-selected', 'true');

		var $target = $(contentLocation + 'Tab');
		if (!$target.length) {
			return;
		}

		$target.closest('.tabs-content').find('li').attr('hidden', true);
		$target.removeAttr('hidden');
	}

	$(function () {
		$('.nav-bar li').has('ul').addClass('has-flyout');
		$('.nav-bar li ul').addClass('flyout');

		$('dl.tabs').each(function () {
			var $links = $(this).children('dd').children('a');
			$links.attr('role', 'tab');
			$links.on('click.candyCaneTabs', function (event) {
				event.preventDefault();
				activateTab($(this));
			});
		});

		if (window.location.hash) {
			var $hashTab = $('a[href="' + window.location.hash.replace(/"/g, '\\"') + '"]');
			if ($hashTab.length) {
				activateTab($hashTab.first());
			}
		}
	});

	$(window).on('load', function () {
		var $featured = $('#featured');
		if ($featured.length && $.fn.orbit) {
			$featured.orbit();
		}
	});
})(jQuery);
