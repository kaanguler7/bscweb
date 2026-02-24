window.addSubCategory = function (parentId) {
   var select = document.getElementById('parent-select');
   var nameInput = document.getElementById('cat-name-input');
   var formCard = document.getElementById('category-form-card');
   if (!select || !nameInput || !formCard) {
      return;
   }

   select.value = parentId;
   window.updateParentInfo();
   formCard.scrollIntoView({ behavior: 'smooth', block: 'start' });
   formCard.classList.add('highlight');
   setTimeout(function () {
      formCard.classList.remove('highlight');
   }, 1500);
   setTimeout(function () {
      nameInput.focus();
   }, 400);
};

window.updateParentInfo = function () {
   var select = document.getElementById('parent-select');
   var info = document.getElementById('parent-info');
   if (!select || !info) {
      return;
   }

   var selectedOption = select.options[select.selectedIndex];
   if (select.value === '') {
      info.classList.remove('visible');
      info.textContent = '';
      return;
   }

   info.classList.add('visible');
   info.textContent = '"' + selectedOption.textContent.trim() + '" kategorisinin altına eklenecek';
};

window.toggleChildren = function (toggleEl) {
   var item = toggleEl.closest('.cat-tree-item');
   if (!item) {
      return;
   }

   var children = item.querySelector('.cat-children');
   if (!children) {
      return;
   }

   toggleEl.classList.toggle('collapsed');
   children.classList.toggle('collapsed');
};

window.expandAll = function () {
   document.querySelectorAll('.cat-toggle').forEach(function (toggle) {
      toggle.classList.remove('collapsed');
   });
   document.querySelectorAll('.cat-children').forEach(function (children) {
      children.classList.remove('collapsed');
   });
};

window.collapseAll = function () {
   document.querySelectorAll('.cat-toggle').forEach(function (toggle) {
      toggle.classList.add('collapsed');
   });
   document.querySelectorAll('.cat-children').forEach(function (children) {
      children.classList.add('collapsed');
   });
};

document.addEventListener('DOMContentLoaded', function () {
   window.updateParentInfo();
});
