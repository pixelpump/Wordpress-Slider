(function($) {
    $(document).ready(function() {
        $('.simple-elegant-slider').each(function() {
            var $slider = $(this);
            var autoSlide = $slider.data('auto-slide') === true;
            var animationSpeed = $slider.data('animation-speed') || 500;

            var swiperOptions = {
                loop: true,
                speed: animationSpeed,
                pagination: {
                    el: '.swiper-pagination',
                    clickable: true,
                },
                navigation: {
                    nextEl: '.swiper-button-next',
                    prevEl: '.swiper-button-prev',
                },
            };

            if (autoSlide) {
                swiperOptions.autoplay = {
                    delay: 5000, // 5 seconds delay between slides
                    disableOnInteraction: false,
                };
            }

            new Swiper($slider[0], swiperOptions);
        });
    });
})(jQuery);