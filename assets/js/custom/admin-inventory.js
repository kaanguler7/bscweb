(function () {
   var searchInput = document.getElementById('inventory-search');
   var tbody = document.getElementById('inventory-tbody');
   var countEl = document.getElementById('inventory-count');
   var paginationNav = document.getElementById('inventory-pagination');
   var timer = null;

   if (!searchInput || !tbody) {
      return;
   }

   function fetchInventory(page) {
      var term = searchInput.value.trim();
      var url = 'index.php?route=admin/inventory/search&q=' + encodeURIComponent(term) + '&page=' + page;

      fetch(url)
         .then(function (response) {
            return response.json();
         })
         .then(function (data) {
            renderRows(data.items || []);
            renderInfo(Number(data.total || 0), Number(data.page || 1), Number(data.totalPages || 1), term);
            renderPagination(Number(data.page || 1), Number(data.totalPages || 1));
         })
         .catch(function () {
            // Hatada mevcut tablo oldugu gibi kalsin.
         });
   }

   function renderRows(items) {
      var rows = '';
      if (items.length === 0) {
         rows = '<tr><td colspan="6" class="text-center text-muted py-4">Sonuç bulunamadı.</td></tr>';
      } else {
         items.forEach(function (item) {
            var stockClass = parseInt(item.stock, 10) > 0 ? 'bg-success' : 'bg-danger';
            var price = formatTl(item.price || 0);
            rows += '<tr>' +
               '<td>' + escHtml(item.name) + '</td>' +
               '<td><code>' + escHtml(item.part_code || '-') + '</code></td>' +
               '<td>' + escHtml(item.brand || '-') + '</td>' +
               '<td>' + escHtml(item.model || '-') + '</td>' +
               '<td><span class="badge ' + stockClass + '">' + parseInt(item.stock || 0, 10) + '</span></td>' +
               '<td>' + price + '</td>' +
               '</tr>';
         });
      }
      tbody.innerHTML = rows;
   }

   function renderInfo(total, page, totalPages, term) {
      if (!countEl) {
         return;
      }

      var html = '';
      if (term && term !== '') {
         html = '<strong>' + total + '</strong> ürün bulundu';
      } else {
         html = 'Toplam <strong>' + total + '</strong> ürün';
      }
      if (totalPages > 1) {
         html += ' &middot; Sayfa ' + page + '/' + totalPages;
      }
      countEl.innerHTML = html;
   }

   function renderPagination(page, totalPages) {
      if (!paginationNav) {
         return;
      }

      if (totalPages <= 1) {
         paginationNav.innerHTML = '';
         return;
      }

      var startPage = Math.max(1, page - 3);
      var endPage = Math.min(totalPages, startPage + 6);
      if (endPage - startPage < 6) {
         startPage = Math.max(1, endPage - 6);
      }

      var html = '<ul class="pagination justify-content-center">';
      html += '<li class="page-item' + (page <= 1 ? ' disabled' : '') + '">';
      html += '<a class="page-link" href="#" data-page="' + (page - 1) + '">&laquo;</a></li>';

      for (var i = startPage; i <= endPage; i++) {
         html += '<li class="page-item' + (i === page ? ' active' : '') + '">';
         html += '<a class="page-link" href="#" data-page="' + i + '">' + i + '</a></li>';
      }

      html += '<li class="page-item' + (page >= totalPages ? ' disabled' : '') + '">';
      html += '<a class="page-link" href="#" data-page="' + (page + 1) + '">&raquo;</a></li>';
      html += '</ul>';
      paginationNav.innerHTML = html;
   }

   function formatTl(value) {
      var amount = parseFloat(value || 0);
      return amount.toFixed(2).replace('.', ',').replace(/\B(?=(\d{3})+(?!\d))/g, '.') + ' TL';
   }

   function escHtml(value) {
      if (!value) {
         return '';
      }
      var div = document.createElement('div');
      div.textContent = value;
      return div.innerHTML;
   }

   searchInput.addEventListener('input', function () {
      clearTimeout(timer);
      timer = setTimeout(function () {
         fetchInventory(1);
      }, 300);
   });

   if (paginationNav) {
      paginationNav.addEventListener('click', function (event) {
         var target = event.target.closest('[data-page]');
         if (!target) {
            return;
         }
         event.preventDefault();

         var item = target.closest('.page-item');
         if (item && (item.classList.contains('disabled') || item.classList.contains('active'))) {
            return;
         }

         var page = parseInt(target.getAttribute('data-page'), 10);
         if (isNaN(page) || page < 1) {
            return;
         }

         fetchInventory(page);
      });
   }

   searchInput.focus();
})();
