<?php
if (!defined('OSTSCPINC') || !is_object($thisuser) || !$thisuser->isStaff()) die('Доступ запрещён');

$categories = InventoryCategory::getTree(0, 0, false);
$brands = InventoryBrand::getAll(false);
$allModels = InventoryModel::getAll(false);
$catTree = InventoryCategory::getTree();
?>
<div>
    <?php if (isset($errors['err']) && $errors['err']) { ?>
        <div class="alert-danger mb-4" id="errormessage"><?=Format::htmlchars($errors['err'])?></div>
    <?php } elseif ($msg) { ?>
        <div class="alert-success mb-4" id="infomessage"><?=Format::htmlchars($msg)?></div>
    <?php } ?>
</div>

<div class="card" x-data="{ tab: '<?=$tab?>' }">
    <div class="card-header"><i data-lucide="tags" class="w-4 h-4 inline-block align-middle mr-1"></i> Справочники</div>
    <div class="card-body">

        <!-- Tabs -->
        <div class="border-b border-gray-200 mb-4" role="tablist">
            <nav class="flex space-x-4">
                <button @click="tab='categories'"
                    :class="tab==='categories' ? 'text-indigo-600 border-b-2 border-indigo-600' : 'text-gray-500 hover:text-gray-700'"
                    class="px-4 py-2 text-sm font-medium focus:outline-none">
                    Категории
                </button>
                <button @click="tab='brands'"
                    :class="tab==='brands' ? 'text-indigo-600 border-b-2 border-indigo-600' : 'text-gray-500 hover:text-gray-700'"
                    class="px-4 py-2 text-sm font-medium focus:outline-none">
                    Бренды
                </button>
                <button @click="tab='models'"
                    :class="tab==='models' ? 'text-indigo-600 border-b-2 border-indigo-600' : 'text-gray-500 hover:text-gray-700'"
                    class="px-4 py-2 text-sm font-medium focus:outline-none">
                    Модели
                </button>
            </nav>
        </div>

        <div class="pt-4">

        <!-- === CATEGORIES === -->
        <div x-show="tab==='categories'" id="tab-categories">
            <div class="grid grid-cols-1 lg:grid-cols-5 gap-6">
                <div class="lg:col-span-3">
                    <h3 class="text-lg font-heading font-semibold text-gray-900 mb-3">Категории техники</h3>
                    <?php if ($categories) { ?>
                    <table class="table-modern w-full">
                        <thead>
                            <tr><th class="table-th">Название</th><th class="table-th">Иконка</th><th class="table-th" width="60">Сорт.</th><th class="table-th" width="80">Активна</th><th class="table-th" width="100"></th></tr>
                        </thead>
                        <tbody>
                        <?php foreach ($categories as $cat) {
                            $indent = str_repeat('&nbsp;&nbsp;&nbsp;', $cat['depth']);
                        ?>
                        <tr>
                            <td class="table-td"><?=$indent?><i class="fa fa-<?=Format::htmlchars($cat['icon'])?>"></i> <?=Format::htmlchars($cat['category_name'])?></td>
                            <td class="table-td"><code><?=Format::htmlchars($cat['icon'])?></code></td>
                            <td class="table-td"><?=$cat['sort_order']?></td>
                            <td class="table-td"><?=$cat['is_active'] ? '<span class="text-emerald-500">Да</span>' : '<span class="text-gray-500">Нет</span>'?></td>
                            <td class="table-td">
                                <button class="btn-ghost btn-sm p-1 cat-edit"
                                    data-id="<?=$cat['category_id']?>"
                                    data-name="<?=Format::htmlchars($cat['category_name'])?>"
                                    data-parent="<?=intval($cat['parent_id'])?>"
                                    data-desc="<?=Format::htmlchars($cat['description'])?>"
                                    data-icon="<?=Format::htmlchars($cat['icon'])?>"
                                    data-sort="<?=$cat['sort_order']?>"
                                    data-active="<?=$cat['is_active']?>"
                                ><i data-lucide="edit" class="w-4 h-4"></i></button>
                                <form action="inventory_catalog.php" method="POST" class="inline">
                                    <?=Misc::csrfField()?>
                                    <input type="hidden" name="a" value="delete_category">
                                    <input type="hidden" name="id" value="<?=$cat['category_id']?>">
                                    <button class="btn-danger btn-sm p-1 inv-delete-btn" type="submit"><i data-lucide="trash-2" class="w-4 h-4"></i></button>
                                </form>
                            </td>
                        </tr>
                        <?php } ?>
                        </tbody>
                    </table>
                    <?php } else { ?>
                    <p class="text-gray-500">Нет категорий</p>
                    <?php } ?>
                </div>
                <div class="lg:col-span-2">
                    <div class="card">
                        <div class="card-header" id="cat-form-title">Добавить категорию</div>
                        <div class="card-body">
                            <form action="inventory_catalog.php" method="POST">
                                <?=Misc::csrfField()?>
                                <input type="hidden" name="a" id="cat-action" value="add_category">
                                <input type="hidden" name="id" id="cat-id" value="">
                                <input type="hidden" name="t" value="categories">
                                <div class="form-group">
                                    <label>Название <span class="text-red-500">*</span></label>
                                    <input type="text" name="category_name" id="cat-name" class="input" required>
                                </div>
                                <div class="form-group">
                                    <label>Родительская</label>
                                    <select name="parent_id" id="cat-parent" class="select">
                                        <option value="">Корневая</option>
                                        <?php foreach ($categories as $c) {
                                            $ci = str_repeat('&nbsp;&nbsp;', $c['depth']);
                                        ?>
                                        <option value="<?=$c['category_id']?>"><?=$ci?><?=Format::htmlchars($c['category_name'])?></option>
                                        <?php } ?>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label>Иконка FA</label>
                                    <input type="text" name="icon" id="cat-icon" class="input" value="desktop" placeholder="desktop">
                                </div>
                                <div class="form-group">
                                    <label>Описание</label>
                                    <textarea name="description" id="cat-desc" class="textarea" rows="2"></textarea>
                                </div>
                                <div class="form-group">
                                    <label>Порядок</label>
                                    <input type="number" name="sort_order" id="cat-sort" class="input" value="0">
                                </div>
                                <div class="form-group" id="cat-active-group" style="display:none;">
                                    <label><input type="checkbox" name="is_active" id="cat-active" value="1" checked> Активна</label>
                                </div>
                                <button type="submit" class="btn-primary btn-sm">Сохранить</button>
                                <button type="button" class="btn-secondary btn-sm" id="cat-reset">Сбросить</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- === BRANDS === -->
        <div x-show="tab==='brands'" id="tab-brands">
            <div class="grid grid-cols-1 lg:grid-cols-5 gap-6">
                <div class="lg:col-span-3">
                    <h3 class="text-lg font-heading font-semibold text-gray-900 mb-3">Бренды / Производители</h3>
                    <?php if ($brands) { ?>
                    <table class="table-modern w-full">
                        <thead><tr><th class="table-th">Название</th><th class="table-th" width="80">Активен</th><th class="table-th" width="100"></th></tr></thead>
                        <tbody>
                        <?php foreach ($brands as $br) { ?>
                        <tr>
                            <td class="table-td"><?=Format::htmlchars($br['brand_name'])?></td>
                            <td class="table-td"><?=$br['is_active'] ? '<span class="text-emerald-500">Да</span>' : '<span class="text-gray-500">Нет</span>'?></td>
                            <td class="table-td">
                                <button class="btn-ghost btn-sm p-1 brand-edit"
                                    data-id="<?=$br['brand_id']?>"
                                    data-name="<?=Format::htmlchars($br['brand_name'])?>"
                                    data-active="<?=$br['is_active']?>"
                                ><i data-lucide="edit" class="w-4 h-4"></i></button>
                                <form action="inventory_catalog.php" method="POST" class="inline">
                                    <?=Misc::csrfField()?>
                                    <input type="hidden" name="a" value="delete_brand">
                                    <input type="hidden" name="id" value="<?=$br['brand_id']?>">
                                    <input type="hidden" name="t" value="brands">
                                    <button class="btn-danger btn-sm p-1 inv-delete-btn" type="submit"><i data-lucide="trash-2" class="w-4 h-4"></i></button>
                                </form>
                            </td>
                        </tr>
                        <?php } ?>
                        </tbody>
                    </table>
                    <?php } else { ?>
                    <p class="text-gray-500">Нет брендов</p>
                    <?php } ?>
                </div>
                <div class="lg:col-span-2">
                    <div class="card">
                        <div class="card-header" id="brand-form-title">Добавить бренд</div>
                        <div class="card-body">
                            <form action="inventory_catalog.php" method="POST">
                                <?=Misc::csrfField()?>
                                <input type="hidden" name="a" id="brand-action" value="add_brand">
                                <input type="hidden" name="id" id="brand-id" value="">
                                <input type="hidden" name="t" value="brands">
                                <div class="form-group">
                                    <label>Название <span class="text-red-500">*</span></label>
                                    <input type="text" name="brand_name" id="brand-name" class="input" required>
                                </div>
                                <div class="form-group" id="brand-active-group" style="display:none;">
                                    <label><input type="checkbox" name="is_active" id="brand-active" value="1" checked> Активен</label>
                                </div>
                                <button type="submit" class="btn-primary btn-sm">Сохранить</button>
                                <button type="button" class="btn-secondary btn-sm" id="brand-reset">Сбросить</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- === MODELS === -->
        <div x-show="tab==='models'" id="tab-models">
            <div class="grid grid-cols-1 lg:grid-cols-5 gap-6">
                <div class="lg:col-span-3">
                    <h3 class="text-lg font-heading font-semibold text-gray-900 mb-3">Модели</h3>
                    <?php if ($allModels) { ?>
                    <table class="table-modern w-full">
                        <thead><tr><th class="table-th">Модель</th><th class="table-th">Бренд</th><th class="table-th" width="80">Активна</th><th class="table-th" width="100"></th></tr></thead>
                        <tbody>
                        <?php foreach ($allModels as $md) { ?>
                        <tr>
                            <td class="table-td"><?=Format::htmlchars($md['model_name'])?></td>
                            <td class="table-td"><?=Format::htmlchars($md['brand_name'])?></td>
                            <td class="table-td"><?=$md['is_active'] ? '<span class="text-emerald-500">Да</span>' : '<span class="text-gray-500">Нет</span>'?></td>
                            <td class="table-td">
                                <button class="btn-ghost btn-sm p-1 model-edit"
                                    data-id="<?=$md['model_id']?>"
                                    data-name="<?=Format::htmlchars($md['model_name'])?>"
                                    data-brand="<?=$md['brand_id']?>"
                                    data-category="<?=$md['category_id']?>"
                                    data-active="<?=$md['is_active']?>"
                                ><i data-lucide="edit" class="w-4 h-4"></i></button>
                                <form action="inventory_catalog.php" method="POST" class="inline">
                                    <?=Misc::csrfField()?>
                                    <input type="hidden" name="a" value="delete_model">
                                    <input type="hidden" name="id" value="<?=$md['model_id']?>">
                                    <input type="hidden" name="t" value="models">
                                    <button class="btn-danger btn-sm p-1 inv-delete-btn" type="submit"><i data-lucide="trash-2" class="w-4 h-4"></i></button>
                                </form>
                            </td>
                        </tr>
                        <?php } ?>
                        </tbody>
                    </table>
                    <?php } else { ?>
                    <p class="text-gray-500">Нет моделей</p>
                    <?php } ?>
                </div>
                <div class="lg:col-span-2">
                    <div class="card">
                        <div class="card-header" id="model-form-title">Добавить модель</div>
                        <div class="card-body">
                            <form action="inventory_catalog.php" method="POST">
                                <?=Misc::csrfField()?>
                                <input type="hidden" name="a" id="model-action" value="add_model">
                                <input type="hidden" name="id" id="model-id" value="">
                                <input type="hidden" name="t" value="models">
                                <div class="form-group">
                                    <label>Название <span class="text-red-500">*</span></label>
                                    <input type="text" name="model_name" id="model-name" class="input" required>
                                </div>
                                <div class="form-group">
                                    <label>Бренд <span class="text-red-500">*</span></label>
                                    <select name="brand_id" id="model-brand" class="select" required>
                                        <option value="">Выберите</option>
                                        <?php foreach ($brands as $br) { if (!$br['is_active']) continue; ?>
                                        <option value="<?=$br['brand_id']?>"><?=Format::htmlchars($br['brand_name'])?></option>
                                        <?php } ?>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label>Категория</label>
                                    <select name="category_id" id="model-category" class="select">
                                        <option value="">Не указана</option>
                                        <?php foreach ($catTree as $ct) {
                                            $ci = str_repeat('&nbsp;&nbsp;', $ct['depth']);
                                        ?>
                                        <option value="<?=$ct['category_id']?>"><?=$ci?><?=Format::htmlchars($ct['category_name'])?></option>
                                        <?php } ?>
                                    </select>
                                </div>
                                <div class="form-group" id="model-active-group" style="display:none;">
                                    <label><input type="checkbox" name="is_active" id="model-active" value="1" checked> Активна</label>
                                </div>
                                <button type="submit" class="btn-primary btn-sm">Сохранить</button>
                                <button type="button" class="btn-secondary btn-sm" id="model-reset">Сбросить</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        </div><!-- tab-content -->
    </div>
</div>

<script type="text/javascript">
$(function() {
    $(document).on('click', '.cat-edit', function() {
        var $b = $(this);
        $('#cat-action').val('update_category');
        $('#cat-id').val($b.data('id'));
        $('#cat-name').val($b.data('name'));
        $('#cat-parent').val($b.data('parent'));
        $('#cat-desc').val($b.data('desc'));
        $('#cat-icon').val($b.data('icon'));
        $('#cat-sort').val($b.data('sort'));
        $('#cat-active').prop('checked', $b.data('active') == 1);
        $('#cat-active-group').show();
        $('#cat-form-title').text('Редактировать категорию');
    });
    $('#cat-reset').on('click', function() {
        $('#cat-action').val('add_category');
        $('#cat-id').val('');
        $('#cat-name, #cat-desc').val('');
        $('#cat-icon').val('desktop');
        $('#cat-parent').val('');
        $('#cat-sort').val('0');
        $('#cat-active-group').hide();
        $('#cat-form-title').text('Добавить категорию');
    });

    $(document).on('click', '.brand-edit', function() {
        var $b = $(this);
        $('#brand-action').val('update_brand');
        $('#brand-id').val($b.data('id'));
        $('#brand-name').val($b.data('name'));
        $('#brand-active').prop('checked', $b.data('active') == 1);
        $('#brand-active-group').show();
        $('#brand-form-title').text('Редактировать бренд');
    });
    $('#brand-reset').on('click', function() {
        $('#brand-action').val('add_brand');
        $('#brand-id, #brand-name').val('');
        $('#brand-active-group').hide();
        $('#brand-form-title').text('Добавить бренд');
    });

    $(document).on('click', '.model-edit', function() {
        var $b = $(this);
        $('#model-action').val('update_model');
        $('#model-id').val($b.data('id'));
        $('#model-name').val($b.data('name'));
        $('#model-brand').val($b.data('brand'));
        $('#model-category').val($b.data('category'));
        $('#model-active').prop('checked', $b.data('active') == 1);
        $('#model-active-group').show();
        $('#model-form-title').text('Редактировать модель');
    });
    $('#model-reset').on('click', function() {
        $('#model-action').val('add_model');
        $('#model-id, #model-name').val('');
        $('#model-brand, #model-category').val('');
        $('#model-active-group').hide();
        $('#model-form-title').text('Добавить модель');
    });
});
</script>
