(function () {
   const input = document.getElementById('brand-filter-input');
   const list = document.getElementById('brand-filter-list');
   const form = document.getElementById('shop-filter-form');
   if (!input || !list) {
      return;
   }

   input.addEventListener('input', function () {
      const term = input.value.trim().toLowerCase();
      const items = list.querySelectorAll('.brand-item');
      items.forEach(function (item) {
         const brand = item.getAttribute('data-brand') || '';
         item.style.display = term === '' || brand.includes(term) ? 'flex' : 'none';
      });
   });

   if (form) {
      const autoSubmitInputs = form.querySelectorAll('input[type="checkbox"]');
      autoSubmitInputs.forEach(function (checkbox) {
         checkbox.addEventListener('change', function () {
            form.submit();
         });
      });
   }
})();
