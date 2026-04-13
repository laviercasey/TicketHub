<?php
if(!defined('OSTADMININC') || !$thisuser->isadmin()) die('Доступ запрещён');

$sql='SELECT grp.group_id,group_name,group_enabled,count(staff.staff_id) as users, grp.created,grp.updated'
     .' FROM '.GROUP_TABLE.' grp LEFT JOIN '.STAFF_TABLE.' staff USING(group_id)';
$groups=db_query($sql.' GROUP BY grp.group_id ORDER BY group_name');
$showing=($num=db_num_rows($groups))?'Группы пользователей':'Группы не найдены';
?>
<div class="flex justify-between items-center mb-4">
    <h2 class="text-lg font-heading font-semibold text-gray-900 flex items-center gap-2">
        <i data-lucide="users" class="w-5 h-5"></i> Группы
    </h2>
    <a href="admin.php?t=groups&a=new" class="btn-primary">
        <i data-lucide="plus" class="w-4 h-4"></i> Новая группа
    </a>
</div>
<div class="card">
    <div class="card-header">
        <h2 class="text-lg font-heading font-semibold text-gray-900"><?=$showing?></h2>
    </div>
    <form action="admin.php" method="POST" name="groups" onSubmit="return checkbox_checker(document.forms['groups'],1,0);">
    <?php echo Misc::csrfField(); ?>
    <input type="hidden" name="t" value="groups">
    <div class="table-wrapper">
    <table class="table-modern">
        <thead>
        <tr>
	        <th class="table-th w-8">&nbsp;</th>
	        <th class="table-th">Имя группы</th>
            <th class="table-th">Статус группы</th>
	        <th class="table-th w-10">Пользователи</th>
	        <th class="table-th">Дата создания</th>
	        <th class="table-th">Последнее обновление</th>
        </tr>
        </thead>
        <tbody>
        <?
        $class = 'row1';
        $total=0;
        $grps=($errors && is_array($_POST['grps']))?$_POST['grps']:null;
        if($groups && db_num_rows($groups)):
            while ($row = db_fetch_array($groups)) {
                $sel=false;
                if(($grps && in_array($row['group_id'],$grps)) || (isset($gID) && $gID==$row['group_id']) ){
                    $class="$class highlight";
                    $sel=true;
                }
                ?>
            <tr id="<?=$row['group_id']?>">
                <td class="table-td w-8">
                  <input type="checkbox" class="checkbox" name="grps[]" value="<?=$row['group_id']?>" <?=$sel?'checked':''?> onClick="highLight(this.value,this.checked);">
                </td>
                <td class="table-td"><a href="admin.php?t=grp&id=<?=$row['group_id']?>" class="text-blue-600 hover:text-blue-800"><?=Format::htmlchars($row['group_name'])?></a></td>
                <td class="table-td"><?=$row['group_enabled']?'<span class="badge-success">Активно</span>':'<span class="badge-danger">Отключено</span>'?></td>
                <td class="table-td text-center"><a href="admin.php?t=staff&gid=<?=$row['group_id']?>" class="text-blue-600 hover:text-blue-800"><?=$row['users']?></a></td>
                <td class="table-td"><?=Format::db_date($row['created'])?></td>
                <td class="table-td"><?=Format::db_datetime($row['updated'])?></td>
            </tr>
            <?
            $class = ($class =='row2') ?'row1':'row2';
            }
        else: ?>
            <tr><td colspan="6" class="table-td text-center text-gray-500">Запрос вернул пустой результат</td></tr>
        <?
        endif; ?>
        </tbody>
    </table>
    </div>
    <div class="card-footer space-y-3">
        <?php if (db_num_rows($groups) > 0): ?>
        <div class="border-t border-gray-100 pt-3">
            <div class="flex items-center gap-4 text-sm mb-3">
                <span class="text-gray-500">Выбрать:</span>
                <a href="#" class="text-blue-600 hover:text-blue-800" onclick="return select_all(document.forms['groups'],true)">Все</a>
                <a href="#" class="text-blue-600 hover:text-blue-800" onclick="return toogle_all(document.forms['groups'],true)">Обратить</a>
                <a href="#" class="text-blue-600 hover:text-blue-800" onclick="return reset_all(document.forms['groups'])">Ничего</a>
            </div>
            <div class="flex items-center justify-center gap-3">
                <button class="btn-success btn-sm" type="submit" name="activate_grps" value="Включить"
                    onClick='return confirm("Вы уверены, что хотите ВКЛЮЧИТЬ выбранную(ые) группу(ы)?");'>
                    <i data-lucide="check-circle" class="w-4 h-4"></i> Включить
                </button>
                <button class="btn-warning btn-sm" type="submit" name="disable_grps" value="Отключить"
                    onClick='return confirm("Вы уверены, что хотите ОТКЛЮЧИТЬ выбранную(ые) группу(ы)?");'>
                    <i data-lucide="x-circle" class="w-4 h-4"></i> Отключить
                </button>
                <button class="btn-danger btn-sm" type="submit" name="delete_grps" value="Удалить"
                    onClick='return confirm("Вы уверены, что хотите УДАЛИТЬ выбранную(ые) группу(ы)?");'>
                    <i data-lucide="trash-2" class="w-4 h-4"></i> Удалить
                </button>
            </div>
        </div>
        <?php endif; ?>
    </div>
    </form>
</div>
