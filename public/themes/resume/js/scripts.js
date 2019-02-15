(function($) {
	'use strict';
	jQuery(document).on('ready', function(){
		jQuery('.tab-buttons li a').on('click', function(){
			var handler = jQuery(this);
			var rel = handler.data('rel');
			var parent = handler.parent();

			if(parent.hasClass('selected')){
			}else{
				
				jQuery('.tab-buttons li').removeClass('selected');
				parent.addClass('selected');

				jQuery('.tab-container > div').hide();
				jQuery('.tab-container #'+rel).show();
			}

			return false;

		});
		$('#counter_feature').bind('inview', function(event, visible, visiblePartX, visiblePartY) {
			if (visible) {
				$(this).find('.timer').each(function () {
					var $this = $(this);
					$({ Counter: 0 }).animate({ Counter: $this.text() }, {
						duration: 2000,
						easing: 'swing',
						step: function () {
							$this.text(Math.ceil(this.Counter));
						}
					});
				});
				$(this).unbind('inview');
			}
		});
		jQuery('.grid').mixitup({
		targetSelector: '.mix',
		});
		$('.image-popup').magnificPopup({
			type: 'image',
			closeOnContentClick: true,
			mainClass: 'mfp-img-mobile',
			image: {
				verticalFit: true
			}
		});
		$('.progress-bar > span').each(function(){
			var $this = $(this);
			var width = $(this).data('percent');
			$this.css({'transition' : 'width 2s' });
			
			setTimeout(function() {
				$this.appear(function() {
				    $this.css('width', width + '%');
				});
			}, 500);
		});
	});
	new WOW().init();	
})(jQuery);

