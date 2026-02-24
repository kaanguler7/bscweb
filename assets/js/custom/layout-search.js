(function () {
   const input = document.getElementById('global-search-input');
   const button = document.getElementById('global-search-btn');
   const resultsBox = document.getElementById('global-search-results');
   if (!input || !button || !resultsBox) {
      return;
   }

   let debounceTimer = null;
   let controller = null;

   const hideResults = () => {
      resultsBox.classList.remove('is-open');
      resultsBox.innerHTML = '';
   };

   const renderItems = (items) => {
      if (!Array.isArray(items) || items.length === 0) {
         hideResults();
         return;
      }

      resultsBox.innerHTML = '';
      items.forEach((item) => {
         const link = document.createElement('a');
         link.className = 'global-search-item';
         link.href = String(item.url || '#');

         const title = document.createElement('span');
         title.textContent = String(item.title || '');

         const type = document.createElement('span');
         type.className = 'global-search-type';
         const typeText = String(item.type || '');
         const metaText = String(item.meta || '');
         type.textContent = metaText ? `${typeText} - ${metaText}` : typeText;

         link.appendChild(title);
         link.appendChild(type);
         resultsBox.appendChild(link);
      });

      resultsBox.classList.add('is-open');
   };

   const fetchSuggestions = async (query) => {
      if (controller) {
         controller.abort();
      }
      controller = new AbortController();

      const response = await fetch(`index.php?route=search/suggest&q=${encodeURIComponent(query)}`, {
         signal: controller.signal
      });
      if (!response.ok) {
         hideResults();
         return;
      }
      const payload = await response.json();
      renderItems(payload.items || []);
   };

   input.addEventListener('input', () => {
      const query = input.value.trim();
      if (debounceTimer) {
         clearTimeout(debounceTimer);
      }

      if (query.length < 2) {
         hideResults();
         return;
      }

      debounceTimer = setTimeout(() => {
         fetchSuggestions(query).catch(() => hideResults());
      }, 220);
   });

   input.addEventListener('keydown', (event) => {
      if (event.key === 'Enter') {
         event.preventDefault();
         const query = input.value.trim();
         if (query.length >= 1) {
            window.location.href = `index.php?route=products&q=${encodeURIComponent(query)}`;
         }
      }
   });

   button.addEventListener('click', () => {
      const query = input.value.trim();
      if (query.length >= 1) {
         window.location.href = `index.php?route=products&q=${encodeURIComponent(query)}`;
      }
   });

   document.addEventListener('click', (event) => {
      if (!resultsBox.contains(event.target) && event.target !== input) {
         hideResults();
      }
   });
})();
