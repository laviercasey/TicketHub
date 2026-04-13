<?php
if (!defined('OSTSCPINC') || !is_object($thisuser) || !$thisuser->isStaff()) die('Доступ запрещён');

if (!isset($item) || !is_object($item) || !$item->getId()) {
    echo '<div class="alert-danger">Запись не найдена</div>';
    return;
}

$statusLabels  = InventoryItem::getStatusLabels();
$assignLabels  = InventoryItem::getAssignmentLabels();
$locations     = InventoryLocation::getTree();
$staffList     = array();
$sres = db_query('SELECT staff_id, CONCAT(firstname," ",s.lastname) as name FROM ' . STAFF_TABLE . ' s WHERE isactive=1 ORDER BY firstname');
if ($sres) { while ($sr = db_fetch_array($sres)) $staffList[] = $sr; }

$statusColors = array(
    'active'          => 'badge-success',
    'in_repair'       => 'badge-warning',
    'reserved'        => 'badge-info',
    'decommissioned'  => 'badge-secondary',
    'written_off'     => 'badge-danger',
);
$statusClass = isset($statusColors[$item->getStatus()]) ? $statusColors[$item->getStatus()] : 'badge-secondary';

$warrantyExpired = $item->getWarrantyUntil() && strtotime($item->getWarrantyUntil()) < time();
$warrantyOk      = $item->getWarrantyUntil() && !$warrantyExpired;
?>

<?php if ($msg) { ?>
<div class="alert-success mb-4"><i data-lucide="check-circle" class="w-4 h-4 flex-shrink-0"></i><span><?= Format::htmlchars($msg) ?></span></div>
<?php } elseif (!empty($errors['err'])) { ?>
<div class="alert-danger mb-4"><i data-lucide="alert-triangle" class="w-4 h-4 flex-shrink-0"></i><span><?= Format::htmlchars($errors['err']) ?></span></div>
<?php } ?>

<!-- Header -->
<div class="flex items-start justify-between mb-6 gap-4">
    <div class="flex items-center gap-3 min-w-0">
        <div class="flex-shrink-0 w-10 h-10 rounded-lg bg-indigo-100 flex items-center justify-center">
            <i data-lucide="monitor" class="w-5 h-5 text-indigo-600"></i>
        </div>
        <div class="min-w-0">
            <h2 class="text-xl font-bold font-heading m-0 leading-tight">
                <?= Format::htmlchars($item->getInventoryNumber() ?: '#' . $item->getId()) ?>
            </h2>
            <div class="text-gray-500 text-sm mt-0.5">
                <?= Format::htmlchars(implode(' ', array_filter([$item->getBrandName(), $item->getModelDisplay()]))) ?>
                <?php if ($item->getCategoryName()) { ?>
                &middot; <?= Format::htmlchars($item->getCategoryName()) ?>
                <?php } ?>
            </div>
        </div>
        <span class="<?= $statusClass ?> text-xs flex-shrink-0"><?= Format::htmlchars($item->getStatusLabel()) ?></span>
    </div>
    <div class="flex items-center gap-2 flex-shrink-0">
        <a href="inventory.php?id=<?= $item->getId() ?>&a=edit" class="btn-primary btn-sm">
            <i data-lucide="edit" class="w-4 h-4"></i> Редактировать
        </a>
        <form method="POST" action="inventory.php" onsubmit="return confirm('Удалить запись?');" class="inline">
            <?= Misc::csrfField() ?>
            <input type="hidden" name="a" value="delete">
            <input type="hidden" name="id" value="<?= $item->getId() ?>">
            <button type="submit" class="btn-danger btn-sm"><i data-lucide="trash-2" class="w-4 h-4"></i></button>
        </form>
    </div>
</div>

<!-- Content grid -->
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

<!-- Left: main details + actions -->
<div class="lg:col-span-2 space-y-6">

    <!-- Main info card -->
    <div class="card">
        <div class="card-header"><i data-lucide="info" class="w-4 h-4"></i> Основная информация</div>
        <div class="card-body">
            <dl class="divide-y divide-gray-100">
                <?php
                $fields = array(
                    array('Инвентарный номер', $item->getInventoryNumber()),
                    array('Категория',         $item->getCategoryName()),
                    array('Бренд',             $item->getBrandName()),
                    array('Модель',            $item->getModelDisplay()),
                    array('Серийный номер',    $item->getSerialNumber()),
                    array('Парт-номер',        $item->getPartNumber()),
                );
                foreach ($fields as $f) {
                    if (!$f[1]) continue;
                ?>
                <div class="flex py-2.5 gap-4">
                    <dt class="text-sm font-medium text-gray-500 w-44 flex-shrink-0"><?= $f[0] ?></dt>
                    <dd class="text-sm text-gray-900"><?= Format::htmlchars($f[1]) ?></dd>
                </div>
                <?php } ?>

                <div class="flex py-2.5 gap-4">
                    <dt class="text-sm font-medium text-gray-500 w-44 flex-shrink-0">Статус</dt>
                    <dd class="text-sm"><span class="<?= $statusClass ?>"><?= Format::htmlchars($item->getStatusLabel()) ?></span></dd>
                </div>

                <?php if ($item->getLocationName()) { ?>
                <div class="flex py-2.5 gap-4">
                    <dt class="text-sm font-medium text-gray-500 w-44 flex-shrink-0">Локация</dt>
                    <dd class="text-sm text-gray-900">
                        <?php $loc = $item->getLocation(); ?>
                        <?= Format::htmlchars($loc ? $loc->getBreadcrumb() : $item->getLocationName()) ?>
                    </dd>
                </div>
                <?php } ?>

                <?php if ($item->getAssignedStaffName()) { ?>
                <div class="flex py-2.5 gap-4">
                    <dt class="text-sm font-medium text-gray-500 w-44 flex-shrink-0">Закреплено за</dt>
                    <dd class="text-sm text-gray-900">
                        <?= Format::htmlchars($item->getAssignedStaffName()) ?>
                        <span class="text-gray-400 ml-1">(<?= Format::htmlchars($item->getAssignmentLabel()) ?>)</span>
                    </dd>
                </div>
                <?php } ?>

                <?php if ($item->getCost()) { ?>
                <div class="flex py-2.5 gap-4">
                    <dt class="text-sm font-medium text-gray-500 w-44 flex-shrink-0">Стоимость</dt>
                    <dd class="text-sm text-gray-900"><?= Format::htmlchars(number_format($item->getCost(), 2, '.', ' ')) ?> руб.</dd>
                </div>
                <?php } ?>

                <?php if ($item->getPurchaseDate()) { ?>
                <div class="flex py-2.5 gap-4">
                    <dt class="text-sm font-medium text-gray-500 w-44 flex-shrink-0">Дата покупки</dt>
                    <dd class="text-sm text-gray-900"><?= date('d.m.Y', strtotime($item->getPurchaseDate())) ?></dd>
                </div>
                <?php } ?>

                <?php if ($item->getWarrantyUntil()) { ?>
                <div class="flex py-2.5 gap-4">
                    <dt class="text-sm font-medium text-gray-500 w-44 flex-shrink-0">Гарантия</dt>
                    <dd class="text-sm <?= $warrantyExpired ? 'text-red-600 font-medium' : 'text-emerald-600' ?>">
                        <?= $warrantyExpired ? 'Истекла ' : 'До ' ?>
                        <?= date('d.m.Y', strtotime($item->getWarrantyUntil())) ?>
                        <?= $warrantyExpired ? '<span class="badge-danger ml-1 text-[10px]">Истекла</span>' : '<span class="badge-success ml-1 text-[10px]">Действует</span>' ?>
                    </dd>
                </div>
                <?php } ?>

                <?php if ($item->getDescription()) { ?>
                <div class="flex py-2.5 gap-4">
                    <dt class="text-sm font-medium text-gray-500 w-44 flex-shrink-0">Описание</dt>
                    <dd class="text-sm text-gray-900 whitespace-pre-line"><?= Format::htmlchars($item->getDescription()) ?></dd>
                </div>
                <?php } ?>
            </dl>
        </div>
    </div>

    <!-- Quick actions -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">

        <!-- Change status -->
        <div class="card">
            <div class="card-header text-xs"><i data-lucide="activity" class="w-3.5 h-3.5"></i> Изменить статус</div>
            <div class="card-body">
                <form method="POST" action="inventory.php">
                    <?= Misc::csrfField() ?>
                    <input type="hidden" name="a" value="change_status">
                    <input type="hidden" name="id" value="<?= $item->getId() ?>">
                    <select name="status" class="select text-sm w-full mb-2">
                        <?php foreach ($statusLabels as $k => $lbl) {
                            $sel = $k == $item->getStatus() ? ' selected' : '';
                        ?>
                        <option value="<?= $k ?>"<?= $sel ?>><?= Format::htmlchars($lbl) ?></option>
                        <?php } ?>
                    </select>
                    <button type="submit" class="btn-secondary btn-sm w-full">Применить</button>
                </form>
            </div>
        </div>

        <!-- Move location -->
        <div class="card">
            <div class="card-header text-xs"><i data-lucide="map-pin" class="w-3.5 h-3.5"></i> Переместить</div>
            <div class="card-body">
                <form method="POST" action="inventory.php">
                    <?= Misc::csrfField() ?>
                    <input type="hidden" name="a" value="move">
                    <input type="hidden" name="id" value="<?= $item->getId() ?>">
                    <select name="location_id" class="select text-sm w-full mb-2">
                        <option value="">Не указана</option>
                        <?php foreach ($locations as $loc) {
                            $indent = str_repeat('&nbsp;', $loc['depth'] * 2);
                            $sel = $loc['location_id'] == $item->getLocationId() ? ' selected' : '';
                        ?>
                        <option value="<?= $loc['location_id'] ?>"<?= $sel ?>><?= $indent ?><?= Format::htmlchars($loc['location_name']) ?></option>
                        <?php } ?>
                    </select>
                    <button type="submit" class="btn-secondary btn-sm w-full">Переместить</button>
                </form>
            </div>
        </div>

        <!-- Assign -->
        <div class="card">
            <div class="card-header text-xs"><i data-lucide="user" class="w-3.5 h-3.5"></i> Закрепить</div>
            <div class="card-body">
                <form method="POST" action="inventory.php">
                    <?= Misc::csrfField() ?>
                    <input type="hidden" name="a" value="assign">
                    <input type="hidden" name="id" value="<?= $item->getId() ?>">
                    <select name="assigned_staff_id" class="select text-sm w-full mb-2">
                        <option value="">Не закреплено</option>
                        <?php foreach ($staffList as $st) {
                            $sel = $st['staff_id'] == $item->getAssignedStaffId() ? ' selected' : '';
                        ?>
                        <option value="<?= $st['staff_id'] ?>"<?= $sel ?>><?= Format::htmlchars($st['name']) ?></option>
                        <?php } ?>
                    </select>
                    <select name="assignment_type" class="select text-sm w-full mb-2">
                        <?php foreach ($assignLabels as $k => $lbl) {
                            $sel = $k == $item->getAssignmentType() ? ' selected' : '';
                        ?>
                        <option value="<?= $k ?>"<?= $sel ?>><?= Format::htmlchars($lbl) ?></option>
                        <?php } ?>
                    </select>
                    <button type="submit" class="btn-secondary btn-sm w-full">Сохранить</button>
                </form>
            </div>
        </div>

    </div>
</div>

<!-- Right: metadata + history -->
<div class="lg:col-span-1 space-y-6">

    <!-- Metadata -->
    <div class="card">
        <div class="card-header"><i data-lucide="clock" class="w-4 h-4"></i> Метаданные</div>
        <div class="card-body space-y-2">
            <div class="text-sm"><span class="text-gray-500">ID:</span> <span class="font-mono">#<?= $item->getId() ?></span></div>
            <div class="text-sm"><span class="text-gray-500">Добавил:</span> <?= Format::htmlchars($item->getCreatedByName()) ?></div>
            <div class="text-sm"><span class="text-gray-500">Создано:</span> <?= $item->getCreated() ? date('d.m.Y H:i', strtotime($item->getCreated())) : '—' ?></div>
            <div class="text-sm"><span class="text-gray-500">Обновлено:</span> <?= $item->getUpdated() ? date('d.m.Y H:i', strtotime($item->getUpdated())) : '—' ?></div>
        </div>
    </div>

    <!-- History -->
    <div class="card">
        <div class="card-header"><i data-lucide="history" class="w-4 h-4"></i> История изменений</div>
        <div class="card-body">
            <?php
            $history = $item->getHistory();
            $actionLabels = InventoryItem::getActionLabels();
            ?>
            <?php if ($history) { ?>
            <div class="space-y-3 max-h-[480px] overflow-y-auto pr-1">
                <?php foreach ($history as $h) {
                    $aLabel = isset($actionLabels[$h['action']]) ? $actionLabels[$h['action']] : $h['action'];
                ?>
                <div class="border border-gray-100 rounded-lg p-2.5 text-xs">
                    <div class="font-semibold text-gray-800"><?= Format::htmlchars($aLabel) ?></div>
                    <?php if ($h['old_value'] || $h['new_value']) { ?>
                    <div class="text-gray-500 mt-0.5">
                        <?= Format::htmlchars($h['old_value']) ?>
                        <?php if ($h['old_value'] && $h['new_value']) { ?>&rarr;<?php } ?>
                        <?= Format::htmlchars($h['new_value']) ?>
                    </div>
                    <?php } ?>
                    <div class="flex items-center justify-between mt-1 text-gray-400">
                        <span><?= Format::htmlchars($h['staff_name']) ?></span>
                        <span><?= $h['created'] ? date('d.m.Y H:i', strtotime($h['created'])) : '' ?></span>
                    </div>
                </div>
                <?php } ?>
            </div>
            <?php } else { ?>
            <p class="text-gray-400 text-sm text-center py-4">Нет записей</p>
            <?php } ?>
        </div>
    </div>

</div>
</div>
