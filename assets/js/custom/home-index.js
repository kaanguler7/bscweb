document.addEventListener('DOMContentLoaded', function () {
   new Swiper('.home-hero-slider', {
      loop: true,
      autoplay: { delay: 3500, disableOnInteraction: false },
      speed: 600,
      navigation: {
         nextEl: '.home-hero-slider .swiper-button-next',
         prevEl: '.home-hero-slider .swiper-button-prev'
      },
      pagination: {
         el: '.home-hero-slider .swiper-pagination',
         clickable: true
      }
   });
});
