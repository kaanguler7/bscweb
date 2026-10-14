(function () {
   const bar = document.querySelector('.bsc-mobile-nav');
   const toggle = bar?.querySelector('.bsc-mobile-toggle');
   const panel = bar?.querySelector('.bsc-mobile-panel');
   if (!bar || !toggle || !panel) return;

   const close = () => {
      panel.hidden = true;
      toggle.setAttribute('aria-expanded', 'false');
   };

   toggle.addEventListener('click', () => {
      const open = panel.hidden;
      panel.hidden = !open;
      toggle.setAttribute('aria-expanded', String(open));
   });
   panel.addEventListener('click', (event) => {
      if (event.target.closest('a')) close();
   });
   document.addEventListener('keydown', (event) => {
      if (event.key === 'Escape' && !panel.hidden) {
         close();
         toggle.focus();
      }
   });
   document.addEventListener('click', (event) => {
      if (!bar.contains(event.target) && !panel.hidden) close();
   });
})();
