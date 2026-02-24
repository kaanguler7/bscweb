(function () {
   var modal = document.getElementById('orders-note-modal');
   var content = document.getElementById('orders-note-modal-content');
   if (!modal || !content) {
      return;
   }

   document.addEventListener('click', function (event) {
      var openBtn = event.target.closest('.js-orders-note-open');
      if (openBtn) {
         content.textContent = openBtn.getAttribute('data-note') || '';
         modal.classList.add('is-open');
         modal.setAttribute('aria-hidden', 'false');
         return;
      }

      var isCloseBtn = event.target.closest('.js-orders-note-close');
      if (isCloseBtn || event.target === modal) {
         modal.classList.remove('is-open');
         modal.setAttribute('aria-hidden', 'true');
      }
   });

   document.addEventListener('keydown', function (event) {
      if (event.key === 'Escape' && modal.classList.contains('is-open')) {
         modal.classList.remove('is-open');
         modal.setAttribute('aria-hidden', 'true');
      }
   });
})();
