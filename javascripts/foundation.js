/* global jQuery */
/**
 * Candy Cane Foundation 2 compatibility layer.
 *
 * The original Foundation 2.1.3 bundle embedded jQuery 1.7.1. This file keeps
 * the two public behaviors Candy Cane documented and used, Orbit and Reveal,
 * while relying on WordPress's registered jQuery instead of shipping a second
 * copy of jQuery.
 */
(function ($) {
	'use strict';

	var orbitDefaults = {
		animation: 'horizontal-push',
		animationSpeed: 600,
		timer: true,
		advanceSpeed: 4000,
		pauseOnHover: false,
		startClockOnMouseOut: false,
		startClockOnMouseOutAfter: 1000,
		directionalNav: true,
		captions: true,
		captionAnimation: 'fade',
		captionAnimationSpeed: 600,
		bullets: false,
		bulletThumbs: false,
		bulletThumbLocation: '',
		fluid: true,
		centerBullets: true,
		afterSlideChange: $.noop
	};

	function CandyCaneOrbit(element, options) {
		this.$element = $(element);
		this.options = $.extend({}, orbitDefaults, options || {});
		this.$slides = this.$element.children('img, a, div').not('.orbit-caption, .timer, .slider-nav, .orbit-bullets');
		this.activeSlide = 0;
		this.timer = null;
		this.outTimer = null;
		this.locked = false;
		this.init();
	}

	CandyCaneOrbit.prototype.init = function () {
		var self = this;

		if (!this.$slides.length || this.$element.data('candy-cane-orbit')) {
			return;
		}

		this.$element.data('candy-cane-orbit', this);
		this.$element.addClass('orbit').attr('aria-roledescription', 'carousel');

		if (!this.$element.parent().hasClass('orbit-wrapper')) {
			this.$element.wrap('<div class="orbit-wrapper"></div>');
		}
		this.$wrapper = this.$element.parent();

		this.$slides.each(function (index) {
			$(this)
				.attr('aria-hidden', index === 0 ? 'false' : 'true')
				.css({
					display: index === 0 ? 'block' : 'none',
					zIndex: index === 0 ? 3 : 1
				});
		});

		this.refreshDimensions();
		$(window).on('resize.candyCaneOrbit', function () {
			self.refreshDimensions();
		});

		if (this.options.directionalNav && this.$slides.length > 1) {
			this.setupDirectionalNav();
		}
		if (this.options.captions) {
			this.setupCaption();
		}
		if (this.options.bullets && this.$slides.length > 1) {
			this.setupBullets();
		}
		if (this.options.timer && this.$slides.length > 1) {
			this.startClock();
		}

		if (this.options.pauseOnHover) {
			this.$wrapper.on('mouseenter.candyCaneOrbit', function () {
				self.stopClock();
			});
			this.$wrapper.on('mouseleave.candyCaneOrbit', function () {
				self.startClock();
			});
		}

		this.$element
			.on('orbit.next.candyCaneOrbit', function () {
				self.shift('next');
			})
			.on('orbit.prev.candyCaneOrbit', function () {
				self.shift('prev');
			})
			.on('orbit.goto.candyCaneOrbit', function (event, index) {
				self.shift(parseInt(index, 10));
			})
			.on('orbit.start.candyCaneOrbit', function () {
				self.startClock();
			})
			.on('orbit.stop.candyCaneOrbit', function () {
				self.stopClock();
			});
	};

	CandyCaneOrbit.prototype.refreshDimensions = function () {
		var width = 0;
		var height = 0;

		this.$slides.each(function () {
			var $slide = $(this);
			width = Math.max(width, $slide.outerWidth() || this.naturalWidth || 0);
			height = Math.max(height, $slide.outerHeight() || this.naturalHeight || 0);
		});

		if (width) {
			this.$wrapper.css('width', this.options.fluid ? '100%' : width);
			this.$element.css('width', this.options.fluid ? '100%' : width);
		}
		if (height) {
			this.$wrapper.css('height', height);
			this.$element.css('height', height);
		}
	};

	CandyCaneOrbit.prototype.setupDirectionalNav = function () {
		var self = this;
		var $nav = $('<div class="slider-nav" aria-label="Slider navigation"><span class="right" role="button" tabindex="0" aria-label="Next slide">Right</span><span class="left" role="button" tabindex="0" aria-label="Previous slide">Left</span></div>');

		$nav.on('click keydown', '.right, .left', function (event) {
			if (event.type === 'keydown' && event.key !== 'Enter' && event.key !== ' ') {
				return;
			}
			event.preventDefault();
			self.stopClock();
			self.shift($(this).hasClass('right') ? 'next' : 'prev');
		});

		this.$wrapper.append($nav);
	};

	CandyCaneOrbit.prototype.setupCaption = function () {
		this.$caption = $('<div class="orbit-caption" aria-live="polite"></div>').appendTo(this.$wrapper);
		this.updateCaption();
	};

	CandyCaneOrbit.prototype.updateCaption = function () {
		if (!this.$caption) {
			return;
		}

		var selector = this.$slides.eq(this.activeSlide).attr('data-caption');
		if (!selector) {
			this.$caption.stop(true, true).hide().empty();
			return;
		}

		var $source = $(selector).first();
		if (!$source.length) {
			this.$caption.stop(true, true).hide().empty();
			return;
		}

		this.$caption.html($source.html());
		if (this.options.captionAnimation === 'none') {
			this.$caption.show();
		} else {
			this.$caption.stop(true, true).fadeIn(this.options.captionAnimationSpeed);
		}
	};

	CandyCaneOrbit.prototype.setupBullets = function () {
		var self = this;
		this.$bullets = $('<ul class="orbit-bullets" aria-label="Choose slide"></ul>');

		this.$slides.each(function (index) {
			var $bullet = $('<li tabindex="0" role="button"></li>')
				.attr('aria-label', 'Slide ' + (index + 1))
				.data('index', index);
			self.$bullets.append($bullet);
		});

		this.$bullets.on('click keydown', 'li', function (event) {
			if (event.type === 'keydown' && event.key !== 'Enter' && event.key !== ' ') {
				return;
			}
			event.preventDefault();
			self.stopClock();
			self.shift($(this).data('index'));
		});

		this.$wrapper.append(this.$bullets);
		this.$element.addClass('with-bullets');
		this.updateBullets();
	};

	CandyCaneOrbit.prototype.updateBullets = function () {
		if (!this.$bullets) {
			return;
		}
		this.$bullets.children().removeClass('active').removeAttr('aria-current');
		this.$bullets.children().eq(this.activeSlide).addClass('active').attr('aria-current', 'true');
	};

	CandyCaneOrbit.prototype.startClock = function () {
		var self = this;
		if (!this.options.timer || this.$slides.length < 2) {
			return;
		}
		this.stopClock();
		this.timer = window.setInterval(function () {
			self.shift('next');
		}, this.options.advanceSpeed);
	};

	CandyCaneOrbit.prototype.stopClock = function () {
		if (this.timer) {
			window.clearInterval(this.timer);
			this.timer = null;
		}
		if (this.outTimer) {
			window.clearTimeout(this.outTimer);
			this.outTimer = null;
		}
	};

	CandyCaneOrbit.prototype.shift = function (direction) {
		if (this.locked || this.$slides.length < 2) {
			return;
		}

		var previous = this.activeSlide;
		var next;

		if (direction === 'next') {
			next = (previous + 1) % this.$slides.length;
		} else if (direction === 'prev') {
			next = (previous - 1 + this.$slides.length) % this.$slides.length;
		} else {
			next = Math.max(0, Math.min(this.$slides.length - 1, parseInt(direction, 10)));
		}

		if (next === previous || Number.isNaN(next)) {
			return;
		}

		this.locked = true;
		var self = this;
		var $previous = this.$slides.eq(previous);
		var $next = this.$slides.eq(next);
		var duration = parseInt(this.options.animationSpeed, 10) || 0;
		var width = this.$element.width();
		var height = this.$element.height();
		var animation = this.options.animation;

		this.activeSlide = next;
		$next.css({display: 'block', zIndex: 3}).attr('aria-hidden', 'false');
		$previous.css('zIndex', 2).attr('aria-hidden', 'true');

		function done() {
			$previous.stop(true, true).hide().css({left: '', top: '', opacity: '', zIndex: 1});
			$next.stop(true, true).show().css({left: '', top: '', opacity: '', zIndex: 3});
			self.locked = false;
			self.updateCaption();
			self.updateBullets();
			self.options.afterSlideChange.call(self.$element[0], $previous, $next);
		}

		if (animation === 'fade') {
			$next.css('opacity', 0).animate({opacity: 1}, duration);
			$previous.animate({opacity: 0}, duration, done);
		} else if (animation === 'vertical-slide' || animation === 'vertical-push') {
			var verticalDirection = direction === 'prev' ? -1 : 1;
			$next.css('top', verticalDirection * height).animate({top: 0}, duration);
			$previous.animate({top: -verticalDirection * height}, duration, done);
		} else {
			var horizontalDirection = direction === 'prev' ? -1 : 1;
			$next.css('left', horizontalDirection * width).animate({left: 0}, duration);
			$previous.animate({left: -horizontalDirection * width}, duration, done);
		}
	};

	$.fn.orbit = function (options) {
		return this.each(function () {
			new CandyCaneOrbit(this, options);
		});
	};

	var revealDefaults = {
		animation: 'fadeAndPop',
		animationSpeed: 300,
		closeOnBackgroundClick: true,
		dismissModalClass: 'close-reveal-modal'
	};

	$.fn.reveal = function (options) {
		var settings = $.extend({}, revealDefaults, options || {});

		return this.each(function () {
			var $modal = $(this);
			var $background = $('.reveal-modal-bg').first();

			if (!$background.length) {
				$background = $('<div class="reveal-modal-bg" />').insertAfter($modal);
			}

			function closeModal() {
				$modal.stop(true, true).fadeOut(settings.animationSpeed).attr('aria-hidden', 'true');
				$background.stop(true, true).fadeOut(settings.animationSpeed);
				$(document).off('keydown.candyCaneReveal');
			}

			$modal.attr({role: 'dialog', 'aria-modal': 'true', 'aria-hidden': 'false'}).stop(true, true).fadeIn(settings.animationSpeed);
			$background.stop(true, true).fadeTo(settings.animationSpeed, 0.8);

			$modal.off('click.candyCaneReveal').on('click.candyCaneReveal', '.' + settings.dismissModalClass, function (event) {
				event.preventDefault();
				closeModal();
			});

			$background.off('click.candyCaneReveal');
			if (settings.closeOnBackgroundClick) {
				$background.on('click.candyCaneReveal', closeModal);
			}

			$(document).off('keydown.candyCaneReveal').on('keydown.candyCaneReveal', function (event) {
				if (event.key === 'Escape') {
					closeModal();
				}
			});
		});
	};

	$(document).on('click.candyCaneReveal', 'a[data-reveal-id]', function (event) {
		event.preventDefault();
		var id = $(this).attr('data-reveal-id');
		if (id) {
			$('#' + id).reveal($(this).data());
		}
	});
})(jQuery);
