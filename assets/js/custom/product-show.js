document.addEventListener('DOMContentLoaded', function () {
   const wrap = document.querySelector('.detail-image-wrap');
   const image = wrap ? wrap.querySelector('.detail-image-zoom-target') : null;

   if (wrap && image) {
      const setOriginFromEvent = function (event) {
         const rect = wrap.getBoundingClientRect();
         if (rect.width <= 0 || rect.height <= 0) {
            return;
         }

         let x = ((event.clientX - rect.left) / rect.width) * 100;
         let y = ((event.clientY - rect.top) / rect.height) * 100;
         x = Math.max(0, Math.min(100, x));
         y = Math.max(0, Math.min(100, y));
         wrap.style.setProperty('--zoom-x', `${x}%`);
         wrap.style.setProperty('--zoom-y', `${y}%`);
      };

      wrap.addEventListener('mouseenter', function (event) {
         setOriginFromEvent(event);
         wrap.classList.add('is-zoomed');
      });

      wrap.addEventListener('mousemove', function (event) {
         if (!wrap.classList.contains('is-zoomed')) {
            return;
         }
         setOriginFromEvent(event);
      });

      wrap.addEventListener('mouseleave', function () {
         wrap.classList.remove('is-zoomed');
         wrap.style.setProperty('--zoom-x', '50%');
         wrap.style.setProperty('--zoom-y', '50%');
      });
   }

   const guestCartForm = document.querySelector('.js-guest-cart-choice-form');
   const guestModal = document.getElementById('guest-cart-choice-modal');
   if (!guestCartForm || !guestModal) {
      return;
   }

   const quantityInput = guestCartForm.querySelector('input[name="quantity"]');
   const nextInput = guestCartForm.querySelector('input[name="next"]');
   const addProductInput = guestCartForm.querySelector('input[name="add_product"]');
   const loginAction = guestModal.querySelector('[data-guest-action="login"]');
   const whatsappAction = guestModal.querySelector('[data-guest-action="whatsapp"]');
   const closeActions = guestModal.querySelectorAll('[data-guest-action="close"]');

   const getQuantity = function () {
      if (!quantityInput) {
         return 1;
      }

      const min = Math.max(1, parseInt(quantityInput.getAttribute('min') || '1', 10));
      const max = Math.max(min, parseInt(quantityInput.getAttribute('max') || '999', 10));
      let quantity = parseInt(quantityInput.value || String(min), 10);
      if (!Number.isFinite(quantity)) {
         quantity = min;
      }
      quantity = Math.max(min, Math.min(max, quantity));
      quantityInput.value = String(quantity);

      return quantity;
   };

   const buildLoginUrl = function (quantity) {
      const next = nextInput ? String(nextInput.value || 'cart') : 'cart';
      const addProduct = addProductInput ? String(addProductInput.value || '') : '';
      const params = new URLSearchParams();
      params.set('route', 'auth/login');
      params.set('next', next);
      if (addProduct !== '') {
         params.set('add_product', addProduct);
      }
      params.set('quantity', String(quantity));

      return `index.php?${params.toString()}`;
   };

   const buildWhatsappUrl = function (quantity) {
      const phone = String(guestCartForm.dataset.whatsappPhone || '').replace(/\D+/g, '');
      const productName = String(guestCartForm.dataset.productName || 'Ürün');
      const partCode = String(guestCartForm.dataset.partCode || '').trim();
      const productUrlRaw = String(guestCartForm.dataset.productUrl || window.location.href);
      let productUrl = productUrlRaw;

      if (!/^https?:\/\//i.test(productUrlRaw)) {
         productUrl = new URL(productUrlRaw, window.location.href).toString();
      }

      const lines = [
         'Merhaba, bu ürünü almak istiyorum.',
         `Ürün: ${productName}`,
      ];

      if (partCode !== '' && partCode !== '-') {
         lines.push(`Parça Kodu: ${partCode}`);
      }

      lines.push(`Adet: ${quantity}`);
      lines.push(`Ürün Linki: ${productUrl}`);

      const text = encodeURIComponent(lines.join('\n'));
      return `https://wa.me/${phone}?text=${text}`;
   };

   const updateActions = function () {
      const quantity = getQuantity();

      if (loginAction) {
         loginAction.setAttribute('href', buildLoginUrl(quantity));
      }
      if (whatsappAction) {
         whatsappAction.setAttribute('href', buildWhatsappUrl(quantity));
      }
   };

   const closeModal = function () {
      guestModal.classList.remove('is-open');
      guestModal.setAttribute('hidden', 'hidden');
      document.body.classList.remove('guest-cart-choice-lock');
   };

   const openModal = function () {
      updateActions();
      guestModal.removeAttribute('hidden');
      guestModal.classList.add('is-open');
      document.body.classList.add('guest-cart-choice-lock');
   };

   guestCartForm.addEventListener('submit', function (event) {
      event.preventDefault();
      openModal();
   });

   if (quantityInput) {
      quantityInput.addEventListener('change', updateActions);
      quantityInput.addEventListener('input', updateActions);
   }

   closeActions.forEach(function (button) {
      button.addEventListener('click', closeModal);
   });

   guestModal.addEventListener('click', function (event) {
      if (event.target === guestModal) {
         closeModal();
      }
   });

   document.addEventListener('keydown', function (event) {
      if (event.key === 'Escape' && guestModal.classList.contains('is-open')) {
         closeModal();
      }
   });

   updateActions();
});
