<?php
if (!defined('OSTSCPINC') || !is_object($thisuser) || !$thisuser->isStaff()) die('Доступ запрещён');

if (!isset($item) || !is_object($item) || !$item->getId()) $item = null;
$action = $item ? 'update' : 'add';
$title = ($action == 'update') ? 'Редактирование: ' . Format::htmlchars($item->getInventoryNumber()) : 'Добавить технику';

$categories = InventoryCategory::getTree();
$locations = InventoryLocation::getTree();
$brands = InventoryBrand::getAll();
$statusLabels = InventoryItem::getStatusLabels();
$assignLabels = InventoryItem::getAssignmentLabels();

$staffList = array();
$sres = db_query('SELECT staff_id, CONCAT(firstname," ",lastname) as name FROM ' . STAFF_TABLE . ' WHERE isactive=1 ORDER BY firstname');
if ($sres) { while ($sr = db_fetch_array($sres)) $staffList[] = $sr; }

$models = array();
if ($item && $item->getBrandId()) {
    $models = InventoryModel::getByBrand($item->getBrandId());
}
?>
<div>
    <?php if (isset($errors['err']) && $errors['err']) { ?>
        <div class="alert-danger mb-4"><i data-lucide="alert-triangle" class="w-4 h-4 flex-shrink-0"></i><span><?=Format::htmlchars($errors['err'])?></span></div>
    <?php } elseif ($msg) { ?>
        <div class="alert-success mb-4"><i data-lucide="check-circle" class="w-4 h-4 flex-shrink-0"></i><span><?=Format::htmlchars($msg)?></span></div>
    <?php } ?>
</div>

<div class="grid grid-cols-1 <?=$item ? 'lg:grid-cols-3' : ''?> gap-6">
<div class="<?=$item ? 'lg:col-span-2' : ''?>">
<div class="card inv-card">
    <div class="card-header"><i data-lucide="<?=$item ? 'edit' : 'plus-circle'?>" class="w-4 h-4"></i> <?=$title?></div>
    <div class="card-body">
        <form action="inventory.php" method="POST">
            <?=Misc::csrfField()?>
            <input type="hidden" name="a" value="<?=$action?>">
            <?php if ($item) { ?>
            <input type="hidden" name="id" value="<?=$item->getId()?>">
            <?php } ?>

            <div class="form-group">
                <label class="label">Инвентарный номер</label>
                <input type="text" name="inventory_number" class="input <?=@$errors['inventory_number'] ? 'input-error' : ''?>"
                    value="<?=Format::htmlchars($item ? $item->getInventoryNumber() : @$_POST['inventory_number'])?>"
                    placeholder="Например: INV-001">
                <?php if (@$errors['inventory_number']) { ?>
                <span class="form-error"><?=Format::htmlchars(@$errors['inventory_number'])?></span>
                <?php } ?>
            </div>

            <div class="form-group">
                <label class="label">Категория <span class="text-red-500">*</span></label>
                <select name="category_id" class="select <?=@$errors['category_id'] ? 'input-error' : ''?>">
                    <option value="">Выберите</option>
                    <?php
                    $selCat = $item ? $item->getCategoryId() : @$_POST['category_id'];
                    foreach ($categories as $cat) {
                        $indent = str_repeat('&nbsp;&nbsp;', $cat['depth']);
                        $sel = ($selCat == $cat['category_id']) ? ' selected' : '';
                    ?>
                    <option value="<?=$cat['category_id']?>"<?=$sel?>><?=$indent?><?=Format::htmlchars($cat['category_name'])?></option>
                    <?php } ?>
                </select>
                <?php if (@$errors['category_id']) { ?>
                <span class="form-error"><?=Format::htmlchars(@$errors['category_id'])?></span>
                <?php } ?>
            </div>

            <div class="form-group">
                <label class="label">Бренд</label>
                <div class="flex items-center gap-2">
                    <select name="brand_id" id="inv-brand-select" class="select flex-1">
                        <option value="">Выберите</option>
                        <?php
                        $selBrand = $item ? $item->getBrandId() : @$_POST['brand_id'];
                        foreach ($brands as $br) {
                            $sel = ($selBrand == $br['brand_id']) ? ' selected' : '';
                        ?>
                        <option value="<?=$br['brand_id']?>"<?=$sel?>><?=Format::htmlchars($br['brand_name'])?></option>
                        <?php } ?>
                        <option value="other">Другое (добавить)</option>
                    </select>
                    <a href="#" id="inv-add-brand-btn" class="btn-secondary btn-sm" title="Добавить бренд"><i data-lucide="plus" class="w-4 h-4"></i></a>
                </div>
            </div>

            <div class="form-group">
                <label class="label">Модель</label>
                <div class="flex items-center gap-2">
                    <div class="flex-1">
                        <select name="model_id" id="inv-model-select" class="select w-full" <?=!$selBrand ? 'disabled' : ''?>>
                            <option value="">Выберите модель</option>
                            <?php
                            $selModel = $item ? $item->getModelId() : @$_POST['model_id'];
                            foreach ($models as $md) {
                                $sel = ($selModel == $md['model_id']) ? ' selected' : '';
                            ?>
                            <option value="<?=$md['model_id']?>"<?=$sel?>><?=Format::htmlchars($md['model_name'])?></option>
                            <?php } ?>
                            <option value="other">-- Другое (ввести вручную) --</option>
                        </select>
                    </div>
                    <a href="#" id="inv-add-model-btn" class="btn-secondary btn-sm" title="Добавить модель"><i data-lucide="plus" class="w-4 h-4"></i></a>
                </div>
                <div class="custom-model-field mt-2 <?=($item && $item->getCustomModel()) ? 'active' : ''?>" style="<?=($item && $item->getCustomModel()) ? '' : 'display:none'?>">
                    <input type="text" name="custom_model" class="input" placeholder="Введите название модели вручную"
                        value="<?=Format::htmlchars($item ? $item->getCustomModel() : @$_POST['custom_model'])?>">
                </div>
            </div>

            <div class="form-group">
                <label class="label">Серийный номер</label>
                <input type="text" name="serial_number" class="input"
                    value="<?=Format::htmlchars($item ? $item->getSerialNumber() : @$_POST['serial_number'])?>">
            </div>

            <div class="form-group">
                <label class="label">Парт-номер</label>
                <input type="text" name="part_number" class="input"
                    value="<?=Format::htmlchars($item ? $item->getPartNumber() : @$_POST['part_number'])?>">
            </div>

            <div class="border-t border-gray-200 my-4"></div>

            <div class="form-group">
                <label class="label">Локация</label>
                <select name="location_id" class="select">
                    <option value="">Не указана</option>
                    <?php
                    $selLoc = $item ? $item->getLocationId() : @$_POST['location_id'];
                    foreach ($locations as $loc) {
                        $indent = str_repeat('&nbsp;&nbsp;', $loc['depth']);
                        $sel = ($selLoc == $loc['location_id']) ? ' selected' : '';
                    ?>
                    <option value="<?=$loc['location_id']?>"<?=$sel?>><?=$indent?><?=Format::htmlchars($loc['location_name'])?></option>
                    <?php } ?>
                </select>
            </div>

            <div class="form-group">
                <label class="label">За кем закреплено</label>
                <div class="flex items-center gap-2">
                    <select name="assigned_staff_id" class="select flex-1">
                        <option value="">Не закреплено</option>
                        <?php
                        $selStaff = $item ? $item->getAssignedStaffId() : @$_POST['assigned_staff_id'];
                        foreach ($staffList as $st) {
                            $sel = ($selStaff == $st['staff_id']) ? ' selected' : '';
                        ?>
                        <option value="<?=$st['staff_id']?>"<?=$sel?>><?=Format::htmlchars($st['name'])?></option>
                        <?php } ?>
                    </select>
                    <select name="assignment_type" class="select flex-1">
                        <?php
                        $selAssign = $item ? $item->getAssignmentType() : (@$_POST['assignment_type'] ? @$_POST['assignment_type'] : 'workplace');
                        foreach ($assignLabels as $key => $label) {
                            $sel = ($selAssign == $key) ? ' selected' : '';
                        ?>
                        <option value="<?=$key?>"<?=$sel?>><?=Format::htmlchars($label)?></option>
                        <?php } ?>
                    </select>
                </div>
            </div>

            <div class="form-group">
                <label class="label">Статус</label>
                <select name="status" class="select">
                    <?php
                    $selStatus = $item ? $item->getStatus() : (@$_POST['status'] ? @$_POST['status'] : 'active');
                    foreach ($statusLabels as $key => $label) {
                        $sel = ($selStatus == $key) ? ' selected' : '';
                    ?>
                    <option value="<?=$key?>"<?=$sel?>><?=Format::htmlchars($label)?></option>
                    <?php } ?>
                </select>
            </div>

            <div class="border-t border-gray-200 my-4"></div>

            <div class="form-group">
                <label class="label">Стоимость</label>
                <div class="flex items-center gap-2">
                    <input type="text" name="cost" class="input"
                        value="<?=Format::htmlchars($item ? $item->getCost() : @$_POST['cost'])?>"
                        placeholder="0.00">
                    <span class="text-sm text-gray-500">руб.</span>
                </div>
            </div>

            <div class="form-group">
                <label class="label">Дата покупки</label>
                <input type="date" name="purchase_date" class="input"
                    value="<?=Format::htmlchars($item ? $item->getPurchaseDate() : @$_POST['purchase_date'])?>">
            </div>

            <div class="form-group">
                <label class="label">Гарантия до</label>
                <input type="date" name="warranty_until" class="input"
                    value="<?=Format::htmlchars($item ? $item->getWarrantyUntil() : @$_POST['warranty_until'])?>">
            </div>

            <div class="form-group">
                <label class="label">Описание / Заметки</label>
                <textarea name="description" class="textarea" rows="3"><?=Format::htmlchars($item ? $item->getDescription() : @$_POST['description'])?></textarea>
            </div>

            <div class="flex items-center gap-3 mt-6">
                <button type="submit" class="btn-primary"><i data-lucide="check" class="w-4 h-4"></i> <?=$item ? 'Сохранить' : 'Добавить'?></button>
                <a href="inventory.php" class="btn-secondary">Отмена</a>
                <?php if ($item) { ?>
                <button type="submit" name="a" value="delete" class="btn-danger inv-delete-btn"><i data-lucide="trash-2" class="w-4 h-4"></i> Удалить</button>
                <?php } ?>
            </div>
        </form>
    </div>
</div>
</div>

<?php if ($item) { ?>
<div class="lg:col-span-1 space-y-6">
    <!-- Info sidebar -->
    <div class="card">
        <div class="card-header"><i data-lucide="info" class="w-4 h-4"></i> Информация</div>
        <div class="card-body">
            <div class="flex items-center gap-2 text-sm py-1"><label class="font-bold">ID:</label> #<?=$item->getId()?></div>
            <?php if ($item->getLocation()) { ?>
            <div class="flex items-center gap-2 text-sm py-1"><label class="font-bold">Полный путь:</label> <?=Format::htmlchars($item->getLocation()->getBreadcrumb())?></div>
            <?php } ?>
            <div class="flex items-center gap-2 text-sm py-1"><label class="font-bold">Добавил:</label> <?=Format::htmlchars($item->getCreatedByName())?></div>
            <div class="flex items-center gap-2 text-sm py-1"><label class="font-bold">Создано:</label> <?=$item->getCreated() ? date('d.m.Y H:i', strtotime($item->getCreated())) : ''?></div>
            <div class="flex items-center gap-2 text-sm py-1"><label class="font-bold">Обновлено:</label> <?=$item->getUpdated() ? date('d.m.Y H:i', strtotime($item->getUpdated())) : ''?></div>
            <?php if ($item->getWarrantyUntil()) {
                $warrantyExpired = strtotime($item->getWarrantyUntil()) < time();
            ?>
            <div class="flex items-center gap-2 text-sm py-1">
                <label class="font-bold">Гарантия:</label>
                <span class="<?=$warrantyExpired ? 'text-red-500' : 'text-emerald-500'?>">
                    <?=$warrantyExpired ? 'Истекла' : 'Действует до ' . date('d.m.Y', strtotime($item->getWarrantyUntil()))?>
                </span>
            </div>
            <?php } ?>
        </div>
    </div>

    <!-- History sidebar -->
    <div class="card">
        <div class="card-header"><i data-lucide="history" class="w-4 h-4"></i> История</div>
        <div class="card-body max-h-[400px] overflow-y-auto">
            <?php
            $history = $item->getHistory();
            $actionLabels = InventoryItem::getActionLabels();
            if ($history) { ?>
            <div class="space-y-3">
                <?php foreach ($history as $h) {
                    $actionLabel = isset($actionLabels[$h['action']]) ? $actionLabels[$h['action']] : $h['action'];
                ?>
                <div class="border border-gray-200 rounded-lg p-3 text-sm">
                    <span class="font-semibold"><?=Format::htmlchars($actionLabel)?></span>
                    <?php if ($h['old_value'] || $h['new_value']) { ?>
                    <br><small class="text-gray-500"><?=Format::htmlchars($h['old_value'])?> &rarr; <?=Format::htmlchars($h['new_value'])?></small>
                    <?php } ?>
                    <br><span class="text-gray-700"><?=Format::htmlchars($h['staff_name'])?></span>
                    <span class="text-gray-500 ml-2"><?=$h['created'] ? date('d.m.Y H:i', strtotime($h['created'])) : ''?></span>
                </div>
                <?php } ?>
            </div>
            <?php } else { ?>
            <p class="text-gray-500 text-center">Нет записей</p>
            <?php } ?>
        </div>
    </div>
</div>
<?php } ?>
</div>
