<?php
if(!defined('OSTADMININC') || !$thisuser->isadmin()) die('Доступ запрещён');
$sql='SELECT dept.dept_id,dept_name,email.email_id,email.email,email.name as email_name,ispublic,count(staff.staff_id) as users '.
     ',CONCAT_WS(" ",mgr.firstname,mgr.lastname) as manager,mgr.staff_id as manager_id,dept.created,dept.updated  FROM '.DEPT_TABLE.' dept '.
     ' LEFT JOIN '.STAFF_TABLE.' mgr ON dept.manager_id=mgr.staff_id '.
     ' LEFT JOIN '.EMAIL_TABLE.' email ON dept.email_id=email.email_id '.
     ' LEFT JOIN '.STAFF_TABLE.' staff ON dept.dept_id=staff.dept_id ';
$depts=db_query($sql.' GROUP BY dept.dept_id ORDER BY dept_name');
?>
<div class="flex justify-between items-center mb-4">
    <h2 class="text-lg font-heading font-semibold text-gray-900 flex items-center gap-2">
        <i data-lucide="building-2" class="w-5 h-5"></i> Отделы
    </h2>
    <a href="admin.php?t=dept&a=new" class="btn-primary">
        <i data-lucide="plus" class="w-4 h-4"></i> Новый отдел
    </a>
</div>
<div class="card">
    <div class="card-header">
        <h2 class="text-lg font-heading font-semibold text-gray-900">Отделы</h2>
    </div>
    <form action="admin.php" method="POST" name="depts" onSubmit="return checkbox_checker(document.forms['depts'],1,0);">
    <?php echo Misc::csrfField(); ?>
    <input type="hidden" name="t" value="dept">
    <input type="hidden" name="do" value="mass_process">
    <div class="table-wrapper">
    <table class="table-modern">
        <thead>
        <tr>
	        <th class="table-th w-8">&nbsp;</th>
	        <th class="table-th">Имя отдела</th>
            <th class="table-th">Тип</th>
            <th class="table-th w-10">Пользователи</th>
            <th class="table-th">Основной исходящий Email</th>
            <th class="table-th">Менеджер</th>
        </tr>
        </thead>
        <tbody>
        <?
        $class = 'row1';
        $total=0;
        $ids=($errors && is_array($_POST['ids']))?$_POST['ids']:null;
        if($depts && db_num_rows($depts)):
            $defaultId=$cfg->getDefaultDeptId();
            while ($row = db_fetch_array($depts)) {
                $sel=false;
                if(($ids && in_array($row['dept_id'],$ids)) || (isset($deptID) && $deptID==$row['dept_id'])){
                    $class="$class highlight";
                    $sel=true;
                }
                $row['email']=$row['email_name']?($row['email_name'].' &lt;'.$row['email'].'&gt;'):$row['email'];
                $default=($defaultId==$row['dept_id'])?'(По умолчанию)':'';
                ?>
            <tr id="<?=$row['dept_id']?>">
                <td class="table-td w-8">
                  <input type="checkbox" class="checkbox" name="ids[]" value="<?=$row['dept_id']?>" <?=$sel?'checked':''?> <?=$default?'disabled':''?>
                            onClick="highLight(this.value,this.checked);"> </td>
                <td class="table-td"><a href="admin.php?t=dept&id=<?=$row['dept_id']?>" class="text-blue-600 hover:text-blue-800"><?=Format::htmlchars($row['dept_name'])?></a>&nbsp;<?=$default?></td>
                <td class="table-td"><?=$row['ispublic']?'<span class="badge-success">Публичный</span>':'<span class="badge-danger">Приватный</span>'?></td>
                <td class="table-td text-center">
                    <?if($row['users']>0) {?>
                        <a href="admin.php?t=staff&dept=<?=$row['dept_id']?>" class="text-blue-600 hover:text-blue-800 font-semibold"><?=$row['users']?></a>
                    <?}else{?> 0
                    <?}?>
                </td>
                <td class="table-td"><a href="admin.php?t=email&id=<?=$row['email_id']?>" class="text-blue-600 hover:text-blue-800"><?=Format::htmlchars($row['email'])?></a></td>
                <td class="table-td"><a href="admin.php?t=staff&id=<?=$row['manager_id']?>" class="text-blue-600 hover:text-blue-800"><?=Format::htmlchars($row['manager'])?>&nbsp;</a></td>
            </tr>
            <?
            $class = ($class =='row2') ?'row1':'row2';
            }
        else: ?>
            <tr><td colspan="6" class="table-td text-center text-gray-500">Поиск вернул пустой результат</td></tr>
        <?
        endif; ?>
        </tbody>
    </table>
    </div>
    <div class="card-footer space-y-3">
        <?php if ($depts && db_num_rows($depts)): ?>
        <div class="border-t border-gray-100 pt-3">
            <div class="flex items-center gap-4 text-sm mb-3">
                <span class="text-gray-500">Выбрать:</span>
                <a href="#" class="text-blue-600 hover:text-blue-800" onclick="return select_all(document.forms['depts'],true)">Все</a>
                <a href="#" class="text-blue-600 hover:text-blue-800" onclick="return reset_all(document.forms['depts'])">Ничего</a>
                <a href="#" class="text-blue-600 hover:text-blue-800" onclick="return toogle_all(document.forms['depts'],true)">Обратить</a>
            </div>
            <div class="flex items-center justify-center gap-3">
                <button class="btn-success btn-sm" type="submit" name="public" value="Сделать Публичным"
                    onClick='return confirm("Вы уверены, что хотите сделать выбранные отделы публичными?");'>
                    <i data-lucide="eye" class="w-4 h-4"></i> Сделать публичным
                </button>
                <button class="btn-warning btn-sm" type="submit" name="private" value="Сделать Приватным"
                    onClick='return confirm("Вы уверены, что хотите сделать выбранные отделы приватными?");'>
                    <i data-lucide="lock" class="w-4 h-4"></i> Сделать приватным
                </button>
                <button class="btn-danger btn-sm" type="submit" name="delete" value="Удалить"
                    onClick='return confirm("Вы уверены, что хотите УДАЛИТЬ выбранные отделы?");'>
                    <i data-lucide="trash-2" class="w-4 h-4"></i> Удалить
                </button>
            </div>
        </div>
        <?php endif; ?>
    </div>
    </form>
</div>
