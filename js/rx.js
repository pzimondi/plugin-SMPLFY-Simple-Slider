jQuery(document).ready(function($) {

	// Original image sliders (unchanged)
	$('.bxslider').bxSlider({
	  auto: true,
	  controls: false,
	  mode: 'fade',
	  speed: 1000,
	  pause: 7000,
	  pager: false,
	  infiniteLoop: true
	});

	$('.bxslider-custom').bxSlider({
	  auto: true,
	  controls: false,
	  mode: 'fade',
	  speed: 1000,
	  pause: 5000,
	  pager: false,
	  infiniteLoop: true
	});

	// Testimonial slider
	if ($('.bs-testimonial-slider').length) {
		$('.bs-testimonial-slider').bxSlider({
		  auto: true,
		  controls: true,
		  mode: 'horizontal',
		  speed: 250,
		  pause: 25000,
		  pager: true,
		  infiniteLoop: true,
		  adaptiveHeight: true,
		  adaptiveHeightSpeed: 300,
		  touchEnabled: true,
		  swipeThreshold: 30,
		  autoHover: true,
		  nextText: '',
		  prevText: ''
		});
	}

	// Scroll-triggered animations
	function bsAnimateOnScroll() {
		$('.bs-animate').each(function() {
			var elementTop = $(this).offset().top;
			var viewBottom = $(window).scrollTop() + $(window).height();
			if (viewBottom > elementTop + 60) {
				$(this).addClass('bs-visible');
			}
		});
	}

	// Run on load and scroll
	bsAnimateOnScroll();
	$(window).on('scroll', bsAnimateOnScroll);

});
