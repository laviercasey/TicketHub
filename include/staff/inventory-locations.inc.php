<?php
if (!defined('OSTSCPINC') || !is_object($thisuser) || !$thisuser->isStaff()) die('Доступ запрещён');

$locationTypes = InventoryLocation::getTypes();

$typeColors = array(
    'building' => '#8e44ad',
    'floor'    => '#2980b9',
    'room'     => '#27ae60',
    'storage'  => '#e67e22',
    'rack'     => '#7f8c8d',
    'other'    => '#95a5a6'
);

function getLocTreeRows($parent_id = 0, $depth = 0) {
    $rows = array();
    $parentCond = $parent_id ? 'parent_id=' . db_input($parent_id) : '(parent_id IS NULL OR parent_id=0)';
    $sql = 'SELECT * FROM ' . LOCATIONS_TABLE
         . ' WHERE ' . $parentCond
         . ' ORDER BY sort_order, location_name';
    $res = db_query($sql);
    if (!$res || !db_num_rows($res)) return $rows;

    while ($row = db_fetch_array($res)) {
        $loc = new InventoryLocation($row['location_id']);
        $row['depth'] = $depth;
        $row['item_count'] = $loc->getItemCount(true);

        $chk = db_query('SELECT COUNT(*) as cnt FROM ' . LOCATIONS_TABLE . ' WHERE parent_id=' . db_input($row['location_id']));
        $chkRow = db_fetch_array($chk);
        $row['has_children'] = $chkRow['cnt'] > 0;

        $rows[] = $row;
        if ($row['has_children']) {
            $children = getLocTreeRows($row['location_id'], $depth + 1);
            foreach ($children as $child) {
                $rows[] = $child;
            }
        }
    }
    return $rows;
}

$treeRows = getLocTreeRows(0, 0);
$totalLocations = count($treeRows);

$totalItems = 0;
$typeCounts = array();
foreach ($treeRows as $tr) {
    if ($tr['depth'] == 0) $totalItems += $tr['item_count'];
    $t = $tr['location_type'] ? $tr['location_type'] : 'other';
    if (!isset($typeCounts[$t])) $typeCounts[$t] = 0;
    $typeCounts[$t]++;
}
?>
<div>
    <?php if (isset($errors['err']) && $errors['err']) { ?>
        <div class="alert-danger mb-4" id="errormessage"><?=Format::htmlchars($errors['err'])?></div>
    <?php } elseif ($msg) { ?>
        <div class="alert-success mb-4" id="infomessage"><?=Format::htmlchars($msg)?></div>
    <?php } ?>
</div>

<div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-4">
    <div>
        <div class="card text-center p-4">
            <div class="text-3xl font-bold text-gray-800"><?=$totalLocations?></div>
            <div class="text-xs text-gray-500 uppercase tracking-wider mt-1">Всего локаций</div>
        </div>
    </div>
    <?php
    $statTypes = array('building' => 'Зданий', 'floor' => 'Этажей', 'room' => 'Кабинетов');
    $statBorderClasses = array('building' => 'border-t-4 border-purple-500', 'floor' => 'border-t-4 border-blue-500', 'room' => 'border-t-4 border-emerald-500');
    $statTextClasses = array('building' => 'text-purple-500', 'floor' => 'text-blue-500', 'room' => 'text-emerald-500');
    foreach ($statTypes as $sKey => $sLabel) {
        $cnt = isset($typeCounts[$sKey]) ? $typeCounts[$sKey] : 0;
        $col = isset($typeColors[$sKey]) ? $typeColors[$sKey] : '#999';
        $borderClass = isset($statBorderClasses[$sKey]) ? $statBorderClasses[$sKey] : '';
        $textClass = isset($statTextClasses[$sKey]) ? $statTextClasses[$sKey] : '';
    ?>
    <div>
        <div class="card text-center p-4 <?=$borderClass?>">
            <div class="text-3xl font-bold <?=$textClass?>"><?=$cnt?></div>
            <div class="text-xs text-gray-500 uppercase tracking-wider mt-1"><?=$sLabel?></div>
        </div>
    </div>
    <?php } ?>
</div>

<div class="card">
    <div class="card-header flex items-center justify-between">
        <span><i data-lucide="network" class="w-4 h-4 inline-block"></i> <strong>Структура локаций</strong></span>
        <div class="flex items-center gap-2">
            <button type="button" class="btn-ghost btn-sm p-1" id="loc-expand-all" title="Развернуть все"><i data-lucide="expand" class="w-4 h-4"></i></button>
            <button type="button" class="btn-ghost btn-sm p-1" id="loc-collapse-all" title="Свернуть все"><i data-lucide="minimize-2" class="w-4 h-4"></i></button>
            <button type="button" class="btn-primary btn-sm" onclick="openLocModal()" id="loc-add-root">
                <i data-lucide="plus" class="w-4 h-4"></i> Добавить локацию
            </button>
        </div>
    </div>

    <?php if ($treeRows) { ?>
    <table class="table-modern w-full loc-table">
        <thead>
            <tr>
                <th class="table-th" style="width:40px;"></th>
                <th class="table-th">Название</th>
                <th class="table-th" style="width:120px;">Тип</th>
                <th class="table-th" style="width:100px;text-align:center;">Техника</th>
                <th class="table-th" style="width:140px;text-align:right;">Действия</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($treeRows as $row) {
            $icon = InventoryLocation::getTypeIcon($row['location_type']);
            $typeLabel = InventoryLocation::getTypeLabel($row['location_type']);
            $t = $row['location_type'] ? $row['location_type'] : 'other';
            $color = isset($typeColors[$t]) ? $typeColors[$t] : '#999';
            $indent = $row['depth'] * 28;
            $isInactive = !$row['is_active'];
            $rowStyle = $isInactive ? 'opacity:0.5;' : '';
            $parentClass = $row['depth'] > 0 ? 'loc-child loc-parent-' . intval($row['parent_id']) : 'loc-root';
            $hideChild = $row['depth'] > 1 ? ' style="display:none;"' : '';
        ?>
        <tr class="loc-row group <?=$parentClass?>" data-id="<?=$row['location_id']?>" data-depth="<?=$row['depth']?>"<?=$hideChild?>>
            <td class="table-td" style="border-left:3px solid <?=$color?>;<?=$rowStyle?>">
                <?php if ($row['has_children']) { ?>
                <span class="loc-toggle-btn" data-id="<?=$row['location_id']?>" data-open="<?=$row['depth'] > 0 ? '0' : '1'?>" style="cursor:pointer;color:#999;font-size:14px;">
                    <i data-lucide="<?=$row['depth'] > 0 ? 'chevron-right' : 'chevron-down'?>" class="w-4 h-4"></i>
                </span>
                <?php } ?>
            </td>
            <td class="table-td" style="<?=$rowStyle?>">
                <span style="margin-left:<?=$indent?>px;">
                    <i data-lucide="<?=htmlspecialchars($icon)?>" style="color:<?=$color?>;width:18px;text-align:center;" class="w-4 h-4 inline-block"></i>
                    <strong style="margin-left:6px;"><?=Format::htmlchars($row['location_name'])?></strong>
                    <?php if ($isInactive) { ?>
                    <span class="badge-gray" style="font-size:10px;margin-left:5px;">неактивна</span>
                    <?php } ?>
                    <?php if ($row['description']) { ?>
                    <br><small style="margin-left:<?=($indent+24)?>px;" class="text-gray-500"><?=Format::htmlchars($row['description'])?></small>
                    <?php } ?>
                </span>
            </td>
            <td class="table-td" style="<?=$rowStyle?>">
                <span class="inline-block text-white text-xs px-2 py-0.5 rounded whitespace-nowrap" style="background:<?=$color?>;">
                    <?=$typeLabel?>
                </span>
            </td>
            <td class="table-td" style="text-align:center;<?=$rowStyle?>">
                <?php if ($row['item_count'] > 0) { ?>
                <a href="inventory.php?location=<?=$row['location_id']?>" class="inline-block text-white text-xs font-semibold px-2 py-0.5 rounded" style="background:<?=$color?>;">
                    <?=$row['item_count']?>
                </a>
                <?php } else { ?>
                <span class="text-gray-300">&mdash;</span>
                <?php } ?>
            </td>
            <td class="table-td" style="text-align:right;">
                <div class="flex items-center gap-1 justify-end loc-actions opacity-0 group-hover:opacity-100 transition-opacity">
                    <button type="button" class="btn-ghost btn-sm p-1 loc-add-child" title="Добавить дочернюю"
                        data-id="<?=$row['location_id']?>"
                        data-name="<?=Format::htmlchars($row['location_name'])?>">
                        <i data-lucide="plus" class="w-4 h-4"></i>
                    </button>
                    <button type="button" class="btn-ghost btn-sm p-1 loc-edit" title="Редактировать"
                        data-id="<?=$row['location_id']?>"
                        data-name="<?=Format::htmlchars($row['location_name'])?>"
                        data-parent="<?=intval($row['parent_id'])?>"
                        data-type="<?=$row['location_type']?>"
                        data-desc="<?=Format::htmlchars($row['description'])?>"
                        data-sort="<?=intval($row['sort_order'])?>"
                        data-active="<?=intval($row['is_active'])?>">
                        <i data-lucide="edit" class="w-4 h-4"></i>
                    </button>
                    <button type="button" class="btn-danger btn-sm p-1 loc-delete" title="Удалить"
                        data-id="<?=$row['location_id']?>"
                        data-name="<?=Format::htmlchars($row['location_name'])?>">
                        <i data-lucide="trash-2" class="w-4 h-4"></i>
                    </button>
                </div>
            </td>
        </tr>
        <?php } ?>
        </tbody>
    </table>
    <?php } else { ?>
    <div class="card-body text-center" style="padding:40px;">
        <i data-lucide="network" class="w-12 h-12 text-gray-300 mx-auto"></i><br><br>
        <p class="text-gray-500">Нет локаций. Нажмите «Добавить локацию» для создания первой.</p>
    </div>
    <?php } ?>
</div>

<div id="locModal" class="fixed inset-0 z-50 hidden">
    <div class="fixed inset-0 bg-black/50 backdrop-blur-sm" onclick="closeLocModal()"></div>
    <div class="fixed inset-0 flex items-center justify-center p-4">
        <div class="bg-white rounded-xl shadow-2xl w-full max-w-lg">
            <form action="inventory_locations.php" method="POST">
                <?=Misc::csrfField()?>
                <input type="hidden" name="a" id="loc-form-action" value="add_location">
                <input type="hidden" name="id" id="loc-form-id" value="">
                <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
                    <h4 class="text-lg font-semibold"><i data-lucide="map-pin" class="w-4 h-4 inline-block"></i> <span id="loc-modal-title">Добавить локацию</span></h4>
                    <button type="button" class="text-gray-400 hover:text-gray-600 text-2xl leading-none" onclick="closeLocModal()">&times;</button>
                </div>
                <div class="p-6 space-y-4">
                    <div class="form-group">
                        <label>Название <span class="text-red-500">*</span></label>
                        <input type="text" name="location_name" id="loc-name" class="input" required>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div class="form-group">
                            <label>Родительская локация</label>
                            <select name="parent_id" id="loc-parent" class="select">
                                <option value="">Корневая</option>
                                <?php
                                $allLocs = InventoryLocation::getFullTree();
                                foreach ($allLocs as $al) {
                                    $indent = str_repeat('&nbsp;&nbsp;', $al['depth']);
                                ?>
                                <option value="<?=$al['location_id']?>"><?=$indent?><?=Format::htmlchars($al['location_name'])?></option>
                                <?php } ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Тип</label>
                            <select name="location_type" id="loc-type" class="select">
                                <?php foreach ($locationTypes as $key => $label) { ?>
                                <option value="<?=$key?>"><?=Format::htmlchars($label)?></option>
                                <?php } ?>
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Описание</label>
                        <textarea name="description" id="loc-desc" class="textarea" rows="2" placeholder="Необязательно"></textarea>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div class="form-group">
                            <label>Порядок сортировки</label>
                            <input type="number" name="sort_order" id="loc-sort" class="input" value="0">
                        </div>
                        <div class="form-group" id="loc-active-group" style="display:none;padding-top:28px;">
                            <label class="flex items-center gap-2">
                                <input type="checkbox" name="is_active" id="loc-active" value="1" checked> Активна
                            </label>
                        </div>
                    </div>
                </div>
                <div class="px-6 py-4 border-t border-gray-200 flex justify-end gap-2">
                    <button type="button" class="btn-secondary btn-sm" onclick="closeLocModal()">Отмена</button>
                    <button type="submit" class="btn-primary btn-sm"><i data-lucide="check" class="w-4 h-4"></i> Сохранить</button>
                </div>
            </form>
        </div>
    </div>
</div>

<form id="loc-delete-form" action="inventory_locations.php" method="POST" style="display:none;">
    <?=Misc::csrfField()?>
    <input type="hidden" name="a" value="delete_location">
    <input type="hidden" name="id" id="loc-delete-id" value="">
</form>

<script type="text/javascript">
function openLocModal() {
    document.getElementById('locModal').classList.remove('hidden');
}
function closeLocModal() {
    document.getElementById('locModal').classList.add('hidden');
}

$(function() {
    $(document).on('click', '.loc-toggle-btn', function() {
        var id = $(this).data('id');
        var $icon = $(this).find('svg, i');
        var isOpen = $(this).find('[data-lucide]').attr('data-lucide') === 'chevron-down'
                  || $(this).find('svg').closest('[data-lucide="chevron-down"]').length > 0;

        var $toggle = $(this);
        var currentlyOpen = $toggle.attr('data-open') === '1';

        if (currentlyOpen) {
            $toggle.attr('data-open', '0');
            $toggle.find('[data-lucide]').attr('data-lucide', 'chevron-right');
            if (typeof lucide !== 'undefined') lucide.createIcons();
            hideChildren(id);
        } else {
            $toggle.attr('data-open', '1');
            $toggle.find('[data-lucide]').attr('data-lucide', 'chevron-down');
            if (typeof lucide !== 'undefined') lucide.createIcons();
            showChildren(id);
        }
    });

    function hideChildren(parentId) {
        $('tr.loc-row').each(function() {
            var $row = $(this);
            if ($row.hasClass('loc-parent-' + parentId)) {
                $row.hide();
                var childId = $row.data('id');
                var $toggle = $row.find('.loc-toggle-btn');
                $toggle.attr('data-open', '0');
                $toggle.find('[data-lucide]').attr('data-lucide', 'chevron-right');
                hideChildren(childId);
            }
        });
        if (typeof lucide !== 'undefined') lucide.createIcons();
    }

    function showChildren(parentId) {
        $('tr.loc-row').each(function() {
            var $row = $(this);
            if ($row.hasClass('loc-parent-' + parentId)) {
                $row.show();
            }
        });
    }

    $('#loc-expand-all').on('click', function() {
        $('tr.loc-row').show();
        $('.loc-toggle-btn').attr('data-open', '1');
        $('.loc-toggle-btn [data-lucide]').attr('data-lucide', 'chevron-down');
        if (typeof lucide !== 'undefined') lucide.createIcons();
    });

    $('#loc-collapse-all').on('click', function() {
        $('tr.loc-row').each(function() {
            if ($(this).data('depth') > 0) $(this).hide();
        });
        $('.loc-toggle-btn').attr('data-open', '0');
        $('.loc-toggle-btn [data-lucide]').attr('data-lucide', 'chevron-right');
        if (typeof lucide !== 'undefined') lucide.createIcons();
    });

    $('#loc-add-root').on('click', function() {
        $('#loc-form-action').val('add_location');
        $('#loc-form-id').val('');
        $('#loc-modal-title').text('Добавить локацию');
        $('#loc-name').val('');
        $('#loc-parent').val('');
        $('#loc-type').val('building');
        $('#loc-desc').val('');
        $('#loc-sort').val('0');
        $('#loc-active-group').hide();
    });

    $(document).on('click', '.loc-add-child', function() {
        var id = $(this).data('id');
        var name = $(this).data('name');
        $('#loc-form-action').val('add_location');
        $('#loc-form-id').val('');
        $('#loc-modal-title').text('Добавить в «' + name + '»');
        $('#loc-name').val('');
        $('#loc-parent').val(id);
        $('#loc-type').val('room');
        $('#loc-desc').val('');
        $('#loc-sort').val('0');
        $('#loc-active-group').hide();
        openLocModal();
    });

    $(document).on('click', '.loc-edit', function() {
        var $btn = $(this);
        $('#loc-form-action').val('update_location');
        $('#loc-form-id').val($btn.data('id'));
        $('#loc-modal-title').text('Редактировать локацию');
        $('#loc-name').val($btn.data('name'));
        $('#loc-parent').val($btn.data('parent'));
        $('#loc-type').val($btn.data('type'));
        $('#loc-desc').val($btn.data('desc'));
        $('#loc-sort').val($btn.data('sort'));
        $('#loc-active').prop('checked', $btn.data('active') == 1);
        $('#loc-active-group').show();
        openLocModal();
    });

    $(document).on('click', '.loc-delete', function() {
        var name = $(this).data('name');
        var id = $(this).data('id');
        if (confirm('Удалить локацию «' + name + '»?\nДочерние локации будут перемещены на уровень выше.')) {
            $('#loc-delete-id').val(id);
            $('#loc-delete-form').submit();
        }
    });

    if (typeof lucide !== 'undefined') lucide.createIcons();
});
</script>
