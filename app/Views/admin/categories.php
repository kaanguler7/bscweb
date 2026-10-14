<?php
$isEditing = $editingCategory !== null;

// Recursive renderer for tree nodes
$renderTreeNodes = static function (array $nodes, int $depth = 0) use (&$renderTreeNodes, $isEditing, $editingCategory, $csrfToken): void {
    foreach ($nodes as $node) {
        $hasChildren = !empty($node['children']);
        $isRoot = $depth === 0;
        $nodeId = (int) $node['id'];
        $isBeingEdited = $isEditing && (int) $editingCategory['id'] === $nodeId;
        ?>
        <div class="cat-tree-item depth-<?= $depth ?><?= $isBeingEdited ? ' editing' : '' ?>" data-id="<?= $nodeId ?>">
            <div class="cat-tree-row d-flex align-items-center<?= $isRoot ? ' root-row' : '' ?>">
                <!-- Toggle arrow for items with children -->
                <span class="cat-toggle me-1" onclick="toggleChildren(this)" style="width:20px;cursor:pointer;<?= $hasChildren ? '' : 'visibility:hidden' ?>">
                    <svg class="chevron-icon" width="16" height="16" viewBox="0 0 16 16" fill="currentColor">
                        <path d="M6 4l4 4-4 4" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </span>

                <!-- Category icon & name -->
                <span class="cat-icon me-2">
                    <?php if ($isRoot): ?>
                        <svg width="18" height="18" viewBox="0 0 16 16" fill="#6c757d"><path d="M1 3.5A1.5 1.5 0 0 1 2.5 2h2.764c.958 0 1.76.56 2.311 1.184C7.985 3.648 8.48 4 9 4h4.5A1.5 1.5 0 0 1 15 5.5v7a1.5 1.5 0 0 1-1.5 1.5h-11A1.5 1.5 0 0 1 1 12.5v-9z"/></svg>
                    <?php else: ?>
                        <svg width="16" height="16" viewBox="0 0 16 16" fill="#adb5bd"><path d="M4 0a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2V4.414A2 2 0 0 0 13.414 3L11 .586A2 2 0 0 0 9.586 0H4z"/></svg>
                    <?php endif; ?>
                </span>

                <span class="cat-name flex-grow-1 <?= $isRoot ? 'fw-semibold' : '' ?>">
                    <?= htmlspecialchars($node['name'], ENT_QUOTES, 'UTF-8') ?>
                </span>

                <!-- Badges -->
                <span class="badge bg-<?= $isRoot ? 'primary' : 'secondary' ?> bg-opacity-10 text-<?= $isRoot ? 'primary' : 'secondary' ?> me-2" style="font-size: 0.7rem;">
                    <?= $isRoot ? 'Ana' : 'Alt' ?>
                </span>
                <?php if ($hasChildren): ?>
                    <span class="badge bg-info bg-opacity-10 text-info me-2" style="font-size: 0.7rem;">
                        <?= count($node['children']) ?> alt
                    </span>
                <?php endif; ?>

                <!-- Action buttons -->
                <div class="cat-actions d-flex gap-1">
                    <button type="button" class="btn btn-sm btn-outline-success py-0 px-2" onclick="addSubCategory(<?= $nodeId ?>, '<?= htmlspecialchars(addslashes($node['name']), ENT_QUOTES, 'UTF-8') ?>')" title="Alt kategori ekle">
                        <svg width="14" height="14" viewBox="0 0 16 16" fill="currentColor"><path d="M8 4a.5.5 0 0 1 .5.5v3h3a.5.5 0 0 1 0 1h-3v3a.5.5 0 0 1-1 0v-3h-3a.5.5 0 0 1 0-1h3v-3A.5.5 0 0 1 8 4z"/></svg>
                        Alt Ekle
                    </button>
                    <a class="btn btn-sm btn-outline-primary py-0 px-2" href="index.php?route=admin/categories&cat_edit=<?= $nodeId ?>" title="Düzenle">
                        <svg width="14" height="14" viewBox="0 0 16 16" fill="currentColor"><path d="M12.146.146a.5.5 0 0 1 .708 0l3 3a.5.5 0 0 1 0 .708l-10 10a.5.5 0 0 1-.168.11l-5 2a.5.5 0 0 1-.65-.65l2-5a.5.5 0 0 1 .11-.168l10-10zM11.207 2.5L13.5 4.793 14.793 3.5 12.5 1.207 11.207 2.5zm1.586 3L10.5 3.207 4 9.707V10h.5a.5.5 0 0 1 .5.5v.5h.5a.5.5 0 0 1 .5.5v.5h.293l6.5-6.5z"/></svg>
                    </a>
                    <form method="post" action="index.php?route=admin/categories/delete" class="d-inline" onsubmit="return confirm('&quot;<?= htmlspecialchars($node['name'], ENT_QUOTES, 'UTF-8') ?>&quot; kategorisi silinsin mi?');">
                        <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                        <input type="hidden" name="id" value="<?= $nodeId ?>">
                        <input type="hidden" name="return_to" value="admin/categories">
                        <button class="btn btn-sm btn-outline-danger py-0 px-2" type="submit" title="Sil">
                            <svg width="14" height="14" viewBox="0 0 16 16" fill="currentColor"><path d="M5.5 5.5A.5.5 0 0 1 6 6v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5zm2.5 0a.5.5 0 0 1 .5.5v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5zm3 .5a.5.5 0 0 0-1 0v6a.5.5 0 0 0 1 0V6z"/><path fill-rule="evenodd" d="M14.5 3a1 1 0 0 1-1 1H13v9a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V4h-.5a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1H5.707l.293-.293A1 1 0 0 1 6.707 0h2.586a1 1 0 0 1 .707.293L10.293 1H12.5a1 1 0 0 1 1 1v1zM4.118 4L4 4.059V13a1 1 0 0 0 1 1h6a1 1 0 0 0 1-1V4.059L11.882 4H4.118z"/></svg>
                        </button>
                    </form>
                </div>
            </div>

            <!-- Children container -->
            <?php if ($hasChildren): ?>
                <div class="cat-children">
                    <?php $renderTreeNodes($node['children'], $depth + 1); ?>
                </div>
            <?php endif; ?>
        </div>
        <?php
    }
};
?>

<section class="pt-60 pb-80">
    <div class="container">
        <div class="d-flex justify-content-between align-items-center mb-20">
            <h2 class="mb-0">Kategori Yönetimi</h2>
            <div class="d-flex gap-2">
                <a class="btn btn-outline-secondary btn-sm" href="index.php?route=admin/brands">Markalar</a>
                <a class="btn btn-outline-secondary btn-sm" href="index.php?route=admin/products">Ürün Yönetimi</a>
            </div>
        </div>

        <div class="row g-4">
            <!-- Left Column: Form + Tree -->
            <div class="col-xl-8">

                <!-- Category Add/Edit Form -->
                <div class="card mb-3" id="category-form-card">
                    <div class="card-body">
                        <h5 class="mb-3" id="form-title">
                            <?php if ($isEditing): ?>
                                <svg width="18" height="18" viewBox="0 0 16 16" fill="#0d6efd" class="me-1"><path d="M12.146.146a.5.5 0 0 1 .708 0l3 3a.5.5 0 0 1 0 .708l-10 10a.5.5 0 0 1-.168.11l-5 2a.5.5 0 0 1-.65-.65l2-5a.5.5 0 0 1 .11-.168l10-10z"/></svg>
                                Kategori Güncelle: <strong><?= htmlspecialchars($editingCategory['name'], ENT_QUOTES, 'UTF-8') ?></strong>
                            <?php else: ?>
                                <svg width="18" height="18" viewBox="0 0 16 16" fill="#198754" class="me-1"><path d="M8 4a.5.5 0 0 1 .5.5v3h3a.5.5 0 0 1 0 1h-3v3a.5.5 0 0 1-1 0v-3h-3a.5.5 0 0 1 0-1h3v-3A.5.5 0 0 1 8 4z"/><path d="M2 2a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V2z"/></svg>
                                Yeni Kategori Ekle
                            <?php endif; ?>
                        </h5>

                        <form method="post" action="index.php?route=<?= $isEditing ? 'admin/categories/update' : 'admin/categories/store' ?>" class="row g-3 align-items-end" id="category-form">
                            <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                            <?php if ($isEditing): ?>
                                <input type="hidden" name="id" value="<?= (int) $editingCategory['id'] ?>">
                            <?php endif; ?>
                            <input type="hidden" name="return_to" value="admin/categories">

                            <div class="col-md-6">
                                <label class="form-label fw-medium">Kategori Adı</label>
                                <input class="form-control" type="text" name="name" id="cat-name-input" required placeholder="örneğin: LED TV Panelleri" value="<?= htmlspecialchars((string) ($editingCategory['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" autocomplete="off">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-medium">Üst Kategori</label>
                                <select class="form-select" name="parent_id" id="parent-select" onchange="updateParentInfo()">
                                    <option value="">Ana Kategori (üst seviye)</option>
                                    <?php foreach ($parentOptions as $option): ?>
                                        <option value="<?= (int) $option['id'] ?>" <?= ((int) ($editingCategory['parent_id'] ?? 0) === (int) $option['id']) ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($option['display_name'], ENT_QUOTES, 'UTF-8') ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="parent-info" id="parent-info"></div>
                            </div>
                            <div class="col-md-2 d-flex gap-2">
                                <button class="btn <?= $isEditing ? 'btn-primary' : 'btn-success' ?> w-100" type="submit">
                                    <?= $isEditing ? 'Güncelle' : 'Ekle' ?>
                                </button>
                            </div>
                            <?php if ($isEditing): ?>
                                <div class="col-12">
                                    <a href="index.php?route=admin/categories" class="btn btn-sm btn-outline-secondary">
                                        <svg width="14" height="14" viewBox="0 0 16 16" fill="currentColor" class="me-1"><path fill-rule="evenodd" d="M15 8a.5.5 0 0 0-.5-.5H2.707l3.147-3.146a.5.5 0 1 0-.708-.708l-4 4a.5.5 0 0 0 0 .708l4 4a.5.5 0 0 0 .708-.708L2.707 8.5H14.5A.5.5 0 0 0 15 8z"/></svg>
                                        İptal - Listeye Dön
                                    </a>
                                </div>
                            <?php endif; ?>
                        </form>
                    </div>
                </div>

                <!-- Category Tree -->
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h5 class="mb-0">
                                <svg width="18" height="18" viewBox="0 0 16 16" fill="#6c757d" class="me-1"><path d="M1 3.5A1.5 1.5 0 0 1 2.5 2h2.764c.958 0 1.76.56 2.311 1.184C7.985 3.648 8.48 4 9 4h4.5A1.5 1.5 0 0 1 15 5.5v7a1.5 1.5 0 0 1-1.5 1.5h-11A1.5 1.5 0 0 1 1 12.5v-9z"/></svg>
                                Kategori Ağacı
                            </h5>
                            <div class="d-flex gap-2">
                                <button class="btn btn-sm btn-outline-secondary" onclick="expandAll()" title="Tümünü Aç">
                                    <svg width="14" height="14" viewBox="0 0 16 16" fill="currentColor"><path fill-rule="evenodd" d="M1.646 4.646a.5.5 0 0 1 .708 0L8 10.293l5.646-5.647a.5.5 0 0 1 .708.708l-6 6a.5.5 0 0 1-.708 0l-6-6a.5.5 0 0 1 0-.708z"/></svg>
                                    Aç
                                </button>
                                <button class="btn btn-sm btn-outline-secondary" onclick="collapseAll()" title="Tümünü Kapat">
                                    <svg width="14" height="14" viewBox="0 0 16 16" fill="currentColor"><path fill-rule="evenodd" d="M1.646 11.354a.5.5 0 0 0 .708 0L8 5.707l5.646 5.647a.5.5 0 0 0 .708-.708l-6-6a.5.5 0 0 0-.708 0l-6 6a.5.5 0 0 0 0 .708z"/></svg>
                                    Kapat
                                </button>
                            </div>
                        </div>

                        <?php if (empty($categoryTree)): ?>
                            <div class="text-center text-muted py-4">
                                <svg width="48" height="48" viewBox="0 0 16 16" fill="#dee2e6"><path d="M1 3.5A1.5 1.5 0 0 1 2.5 2h2.764c.958 0 1.76.56 2.311 1.184C7.985 3.648 8.48 4 9 4h4.5A1.5 1.5 0 0 1 15 5.5v7a1.5 1.5 0 0 1-1.5 1.5h-11A1.5 1.5 0 0 1 1 12.5v-9z"/></svg>
                                <p class="mt-2 mb-0">Henüz kategori eklenmemiş. Yukarıdaki formdan ilk kategorinizi ekleyin!</p>
                            </div>
                        <?php else: ?>
                            <div id="category-tree">
                                <?php $renderTreeNodes($categoryTree); ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Right Column: Stats & Help -->
            <div class="col-xl-4">
                <div class="card mb-3">
                    <div class="card-body">
                        <h5 class="mb-10">Günlük Giriş</h5>
                        <h3><?= (int) $todayVisitors ?> kişi</h3>
                    </div>
                </div>
                <div class="card mb-3">
                    <div class="card-body">
                        <h5 class="mb-10">Bugünkü Sipariş</h5>
                        <h3><?= (int) $todayOrders ?> sipariş</h3>
                    </div>
                </div>
                <div class="card">
                    <div class="card-body">
                        <h6 class="text-muted mb-2">Kullanım Kılavuzu</h6>
                        <ul class="small text-muted mb-0" style="padding-left: 1.2rem;">
                            <li class="mb-1"><strong>Ana Kategori:</strong> Üst kategori "Ana Kategori" seçili iken ekleyin</li>
                            <li class="mb-1"><strong>Alt Kategori:</strong> Herhangi bir kategorinin yanındaki yeşil <span class="text-success fw-bold">+ Alt Ekle</span> butonuna tıklayın</li>
                            <li class="mb-1"><strong>Düzenleme:</strong> Mavi kalem ikonuna tıklayın</li>
                            <li class="mb-1"><strong>Silme:</strong> Sadece alt kategorisi ve ürünü olmayan kategoriler silinebilir</li>
                            <li><strong>Ağaç Aç/Kapat:</strong> Ok işaretine tıklayarak alt kategorileri gizleyin/gösterin</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
