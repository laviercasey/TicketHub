<?php
if(!defined('OSTADMININC') || !$thisuser->isadmin()) die('Доступ запрещён');

$sql='SELECT staff.staff_id, staff.group_id,staff.dept_id, firstname,lastname, username'.
     ',isactive,onvacation,isadmin,group_name,dept_name,DATE(staff.created) as created,lastlogin,staff.updated '.
     ' FROM '.STAFF_TABLE.' staff '.
     ' LEFT JOIN '.GROUP_TABLE.' `groups` ON staff.group_id=`groups`.group_id'.
     ' LEFT JOIN '.DEPT_TABLE.' dept ON staff.dept_id=dept.dept_id';

if(!empty($_REQUEST['dept']) && is_numeric($_REQUEST['dept'])){
    $id=$_REQUEST['dept'];
    $sql.=' WHERE staff.dept_id='.db_input($_REQUEST['dept']);
}
$users=db_query($sql.' ORDER BY lastname,firstname');
$showing=($num=db_num_rows($users))?"Менеджеры":"Сотрудники не найдены. <a href='admin.php?t=staff&a=new&dept=$id'>Добавить нового сотрудника</a>.";
?>
<div class="flex justify-between items-center mb-4">
    <h2 class="text-lg font-heading font-semibold text-gray-900 flex items-center gap-2">
        <i data-lucide="users" class="w-5 h-5"></i> Менеджеры
    </h2>
    <a href="admin.php?t=staff&a=new" class="btn-primary">
        <i data-lucide="plus" class="w-4 h-4"></i> Новый пользователь
    </a>
</div>
<div class="card">
    <div class="card-header flex items-center justify-between">
        <h2 class="text-lg font-heading font-semibold text-gray-900"><?=$showing?></h2>
    </div>
    <form action="admin.php" method="POST" name="staff" onSubmit="return checkbox_checker(document.forms['staff'],1,0);">
    <?php echo Misc::csrfField(); ?>
    <input type="hidden" name="t" value="staff">
    <input type="hidden" name="do" value="mass_process">
    <div class="table-wrapper">
     <table class="table-modern">
        <thead>
        <tr>
	        <th class="table-th w-8">&nbsp;</th>
	        <th class="table-th">Имя</th>
            <th class="table-th">Логин</th>
            <th class="table-th">Статус</th>
            <th class="table-th">Группа</th>
            <th class="table-th">Отдел</th>
            <th class="table-th">Создан</th>
            <th class="table-th">Последний вход</th>
        </tr>
        </thead>
        <tbody>
        <?
        $class = 'row1';
        $total=0;
        $uids=($errors && is_array($_POST['uids']))?$_POST['uids']:null;
        if($users && db_num_rows($users)):
            while ($row = db_fetch_array($users)) {
                $sel=false;
                if(($uids && in_array($row['staff_id'],$uids)) or (isset($uID) && $uID==$row['staff_id'])){
                    $class="$class highlight";
                    $sel=true;
                }
                $name=ucfirst($row['firstname'].' '.$row['lastname']);
                ?>
            <tr id="<?=$row['staff_id']?>">
                <td class="table-td w-8">
                  <input type="checkbox" class="checkbox" name="uids[]" value="<?=$row['staff_id']?>" <?=$sel?'checked':''?> onClick="highLight(this.value,this.checked);">
                </td>
                <td class="table-td"><a href="admin.php?t=staff&id=<?=$row['staff_id']?>" class="text-blue-600 hover:text-blue-800"><?=Format::htmlchars($name)?></a>&nbsp;</td>
                <td class="table-td"><?=Format::htmlchars($row['username'])?></td>
                <td class="table-td"><?=$row['isactive']?'<span class="badge-success">Активен</span>':'<span class="badge-danger">Заблокирован</span>'?>&nbsp;<?=$row['onvacation']?'(<i>вакансия</i>)':''?></td>
                <td class="table-td"><a href="admin.php?t=grp&id=<?=$row['group_id']?>" class="text-blue-600 hover:text-blue-800"><?=Format::htmlchars($row['group_name'])?></a></td>
                <td class="table-td"><a href="admin.php?t=dept&id=<?=$row['dept_id']?>" class="text-blue-600 hover:text-blue-800"><?=Format::htmlchars($row['dept_name'])?></a></td>
                <td class="table-td"><?=Format::db_date($row['created'])?></td>
                <td class="table-td"><?=Format::db_datetime($row['lastlogin'])?>&nbsp;</td>
            </tr>
            <?
            $class = ($class =='row2') ?'row1':'row2';
            } //end of while.
        else: ?>
            <tr><td colspan="8" class="table-td text-center text-gray-500">Запрос не вернул результатов</td></tr>
        <?
        endif; ?>
        </tbody>
     </table>
    </div>
    <div class="card-footer space-y-3">
        <?php if (db_num_rows($users) > 0): ?>
        <div class="border-t border-gray-100 pt-3">
            <div class="flex items-center gap-4 text-sm mb-3">
                <span class="text-gray-500">Выбрать:</span>
                <a href="#" class="text-blue-600 hover:text-blue-800" onclick="return select_all(document.forms['staff'],true)">Все</a>
                <a href="#" class="text-blue-600 hover:text-blue-800" onclick="return toogle_all(document.forms['staff'],true)">Обратить</a>
                <a href="#" class="text-blue-600 hover:text-blue-800" onclick="return reset_all(document.forms['staff'])">Ничего</a>
            </div>
            <div class="flex items-center justify-center gap-3">
                <button class="btn-success btn-sm" type="submit" name="enable" value="Включить"
                    onClick='return confirm("Вы уверены, что хотите ВКЛЮЧИТЬ выбранных пользователей?");'>
                    <i data-lucide="check-circle" class="w-4 h-4"></i> Включить
                </button>
                <button class="btn-warning btn-sm" type="submit" name="disable" value="Заблокировать"
                    onClick='return confirm("Вы уверены, что хотите ЗАБЛОКИРОВАТЬ выбранных пользователей?");'>
                    <i data-lucide="lock" class="w-4 h-4"></i> Заблокировать
                </button>
                <button class="btn-danger btn-sm" type="submit" name="delete" value="Удалить"
                    onClick='return confirm("Вы уверены, что хотите УДАЛИТЬ выбранных пользователей?");'>
                    <i data-lucide="trash-2" class="w-4 h-4"></i> Удалить
                </button>
            </div>
        </div>
        <?php endif; ?>
    </div>
    <?php // footer always shown (moved condition inside) ?>
    </form>
</div>
