<?php
if(!defined('OSTADMININC') || !$thisuser->isadmin()) die('Доступ запрещён');

$sql = 'SELECT * FROM ' . PRIORITY_USERS_TABLE . ' ORDER BY email';
$users = db_query($sql);
$showing = "Приоритетные пользователи" . (($num = db_num_rows($users)) ? " ($num)" : '');
?>
<div class="flex justify-between items-center mb-4">
    <h2 class="text-lg font-heading font-semibold text-gray-900 flex items-center gap-2">
        <i data-lucide="star" class="w-5 h-5"></i> Приоритетные пользователи
    </h2>
    <a href="admin.php?t=priorityusers&a=new" class="btn-primary">
        <i data-lucide="plus" class="w-4 h-4"></i> Добавить
    </a>
</div>
<div class="card">
    <div class="card-header">
        <h2 class="text-lg font-heading font-semibold text-gray-900"><?=$showing?></h2>
    </div>
<form action="admin.php" method="POST" name="priorityusers" onSubmit="return checkbox_checker(document.forms['priorityusers'],1,0);">
    <?php echo Misc::csrfField(); ?>
    <input type="hidden" name="t" value="priorityusers">
    <input type="hidden" name="do" value="mass_process">
    <div class="table-wrapper">
        <table class="table-modern">
            <thead>
            <tr>
                <th class="table-th w-8">&nbsp;</th>
                <th class="table-th">Email</th>
                <th class="table-th">Описание</th>
                <th class="table-th">Статус</th>
                <th class="table-th">Создан</th>
                <th class="table-th">Обновлен</th>
            </tr>
            </thead>
            <tbody>
            <?php
            $total = 0;
            $pids = ($errors && is_array($_POST['pids'])) ? $_POST['pids'] : null;
            if ($users && db_num_rows($users)):
                while ($row = db_fetch_array($users)) {
                    $sel = false;
                    if ($pids && in_array($row['id'], $pids)) {
                        $sel = true;
                    }
                    ?>
                    <tr id="<?=$row['id']?>">
                        <td class="table-td w-8">
                            <input type="checkbox" class="checkbox" name="pids[]" value="<?=$row['id']?>" <?=$sel ? 'checked' : ''?> onClick="highLight(this.value,this.checked);">
                        </td>
                        <td class="table-td"><a href="admin.php?t=priorityusers&id=<?=$row['id']?>" class="text-blue-600 hover:text-blue-800"><?=Format::htmlchars($row['email'])?></a></td>
                        <td class="table-td"><?=Format::htmlchars($row['description'])?>&nbsp;</td>
                        <td class="table-td"><?=$row['is_active'] ? '<span class="badge-success">Активен</span>' : '<span class="badge-danger">Отключен</span>'?></td>
                        <td class="table-td"><?=Format::db_date($row['created'])?></td>
                        <td class="table-td"><?=Format::db_datetime($row['updated'])?>&nbsp;</td>
                    </tr>
                    <?php
                }
            else: ?>
                <tr><td colspan="6" class="table-td text-center text-gray-500">Нет приоритетных пользователей</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
    <div class="card-footer space-y-3">
        <?php if (db_num_rows($users) > 0): ?>
        <div class="border-t border-gray-100 pt-3">
            <div class="flex items-center gap-4 text-sm mb-3">
                <span class="text-gray-500">Выбрать:</span>
                <a href="#" class="text-blue-600 hover:text-blue-800" onclick="return select_all(document.forms['priorityusers'],true)">Все</a>
                <a href="#" class="text-blue-600 hover:text-blue-800" onclick="return toogle_all(document.forms['priorityusers'],true)">Обратить</a>
                <a href="#" class="text-blue-600 hover:text-blue-800" onclick="return reset_all(document.forms['priorityusers'])">Ничего</a>
            </div>
            <div class="flex items-center justify-center gap-3">
                <button class="btn-success btn-sm" type="submit" name="enable" value="Включить"
                    onClick='return confirm("Включить выбранных приоритетных пользователей?");'>
                    <i data-lucide="check-circle" class="w-4 h-4"></i> Включить
                </button>
                <button class="btn-warning btn-sm" type="submit" name="disable" value="Отключить"
                    onClick='return confirm("Отключить выбранных приоритетных пользователей?");'>
                    <i data-lucide="x-circle" class="w-4 h-4"></i> Отключить
                </button>
                <button class="btn-danger btn-sm" type="submit" name="delete" value="Удалить"
                    onClick='return confirm("Удалить выбранных приоритетных пользователей?");'>
                    <i data-lucide="trash-2" class="w-4 h-4"></i> Удалить
                </button>
            </div>
        </div>
        <?php endif; ?>
    </div>
</form>
</div>
