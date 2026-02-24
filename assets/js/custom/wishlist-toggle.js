(function () {
   const forms = document.querySelectorAll('.js-wishlist-toggle');
   if (!forms.length) {
      return;
   }

   const setFavoriteIcon = (productId, isFavorite) => {
      const sameProductForms = document.querySelectorAll(`.js-wishlist-toggle[data-product-id="${productId}"]`);
      sameProductForms.forEach((form) => {
         const icon = form.querySelector('.js-wishlist-icon');
         if (!icon) {
            return;
         }
         icon.classList.remove('fas', 'far');
         icon.classList.add(isFavorite ? 'fas' : 'far');
      });
   };

   const updateWishlistCount = (count) => {
      const navLink = document.getElementById('wishlist-nav-link');
      if (!navLink) {
         return;
      }
      navLink.textContent = `Favorilerim (${Number(count || 0)})`;
   };

   document.addEventListener('submit', async (event) => {
      const form = event.target.closest('.js-wishlist-toggle');
      if (!form) {
         return;
      }

      event.preventDefault();

      const submitButton = form.querySelector('button[type="submit"]');
      if (submitButton) {
         submitButton.disabled = true;
      }

      try {
         const formData = new FormData(form);
         const response = await fetch(form.getAttribute('action') || 'index.php?route=wishlist/toggle', {
            method: 'POST',
            body: formData,
            headers: {
               'X-Requested-With': 'XMLHttpRequest',
               Accept: 'application/json'
            }
         });

         const payload = await response.json().catch(() => null);
         if (!response.ok || !payload || payload.ok !== true) {
            if (payload && payload.redirect) {
               window.location.href = payload.redirect;
               return;
            }
            if (payload && payload.message) {
               alert(payload.message);
            }
            return;
         }

         const productId = Number(payload.productId || form.getAttribute('data-product-id') || 0);
         const added = Boolean(payload.added);
         setFavoriteIcon(productId, added);
         updateWishlistCount(payload.wishlistCount);

         if (!added && form.getAttribute('data-remove-card') === '1') {
            const card = document.querySelector(`[data-wishlist-item="${productId}"]`);
            if (card) {
               card.remove();
            }
         }
      } catch (err) {
         alert('İşlem sırasında bir hata oluştu. Lütfen tekrar deneyin.');
      } finally {
         if (submitButton) {
            submitButton.disabled = false;
         }
      }
   });
})();
