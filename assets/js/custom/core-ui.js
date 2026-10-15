document.addEventListener('DOMContentLoaded', function () {
   const scrollTop = document.querySelector('.scroll-to-target');
   if (!scrollTop) {
      return;
   }

   const update = function () {
      scrollTop.classList.toggle('open', window.scrollY >= 245);
   };

   window.addEventListener('scroll', update, { passive: true });
   scrollTop.addEventListener('click', function () {
      window.scrollTo({ top: 0, behavior: 'smooth' });
   });
   update();
});
