(function () {
   var priceInput = document.getElementById('price-input');
   var wrap = document.getElementById('kdv-preview-wrap');
   var baseEl = document.getElementById('kdv-base');
   var amountEl = document.getElementById('kdv-amount');
   var totalEl = document.getElementById('kdv-total');

   if (!priceInput || !wrap) {
      return;
   }

   function formatTl(amount) {
      return amount.toFixed(2).replace('.', ',').replace(/\B(?=(\d{3})+(?!\d))/g, '.') + ' TL';
   }

   function updateKdv() {
      var price = parseFloat(priceInput.value) || 0;
      if (price > 0) {
         wrap.style.display = '';
         var kdv = price * 0.20;
         var total = price + kdv;
         if (baseEl) {
            baseEl.textContent = formatTl(price);
         }
         if (amountEl) {
            amountEl.textContent = formatTl(kdv);
         }
         if (totalEl) {
            totalEl.textContent = formatTl(total);
         }
      } else {
         wrap.style.display = 'none';
      }
   }

   priceInput.addEventListener('input', updateKdv);
   updateKdv();
})();
