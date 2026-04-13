<?php
if(!defined('OSTADMININC') || !$thisuser->isadmin()) die('Доступ запрещён');
$sql='SELECT email.email_id,email,name,email.noautoresp,email.dept_id,dept_name,priority_desc,email.created,email.updated '.
     ' FROM '.EMAIL_TABLE.' email '.
     ' LEFT JOIN '.DEPT_TABLE.' dept ON dept.dept_id=email.dept_id '.
     ' LEFT JOIN '.TICKET_PRIORITY_TABLE.' pri ON pri.priority_id=email.priority_id ';
$emails=db_query($sql.' ORDER BY email');
?>
<div class="flex justify-between items-center mb-4">
    <h2 class="text-lg font-heading font-semibold text-gray-900 flex items-center gap-2">
        <i data-lucide="mail" class="w-5 h-5"></i> Email Адреса
    </h2>
    <a href="admin.php?t=email&a=new" class="btn-primary">
        <i data-lucide="plus" class="w-4 h-4"></i> Новый Email
    </a>
</div>
<div class="card">
    <form action="admin.php?t=email" method="POST" name="email" onSubmit="return checkbox_checker(document.forms['email'],1,0);">
    <?php echo Misc::csrfField(); ?>
    <input type="hidden" name="t" value="email">
    <input type="hidden" name="do" value="mass_process">
    <div class="table-wrapper">
    <table class="table-modern">
        <thead>
        <tr>
	        <th class="table-th w-8">&nbsp;</th>
	        <th class="table-th">Email Адрес</th>
            <th class="table-th">Автоответчик</th>
            <th class="table-th">Отдел</th>
            <th class="table-th">Приоритет</th>
	        <th class="table-th">Последнее Обновление</th>
        </tr>
        </thead>
        <tbody>
        <?
        $total=0;
        $ids=($errors && is_array($_POST['ids']))?$_POST['ids']:null;
        if($emails && db_num_rows($emails)):
            $defaultID=$cfg->getDefaultEmailId();
            while ($row = db_fetch_array($emails)) {
                $sel=false;
                if($ids && in_array($row['email_id'],$ids)){
                    $sel=true;
                }
                if($row['name']) {
                    $row['email']=$row['name'].' <'.$row['email'].'>';
                }
                ?>
            <tr id="<?=$row['email_id']?>" class="<?=$sel ? 'highlight' : ''?>">
                <td class="table-td w-8">
                 <input type="checkbox" class="checkbox" name="ids[]" value="<?=$row['email_id']?>" <?=$sel?'checked':''?>
                    <?=($defaultID==$row['email_id'])?'disabled':''?> onClick="highLight(this.value,this.checked);">
                </td>
                <td class="table-td"><a href="admin.php?t=email&id=<?=$row['email_id']?>" class="text-blue-600 hover:text-blue-800"><?=Format::htmlchars($row['email'])?></a></td>
                <td class="table-td"><?=$row['noautoresp']?'<span class="text-gray-500">No</span>':'<span class="badge-success">Yes</span>'?></td>
                <td class="table-td"><a href="admin.php?t=dept&id=<?=$row['dept_id']?>" class="text-blue-600 hover:text-blue-800"><?=Format::htmlchars($row['dept_name'])?></a></td>
                <td class="table-td"><?=Format::htmlchars($row['priority_desc'])?></td>
                <td class="table-td"><?=Format::db_datetime($row['updated'])?></td>
            </tr>
            <?
            }
        else: ?>
            <tr><td colspan="6" class="table-td text-center text-gray-500">Запрос вернул пустой результат</td></tr>
        <?
        endif; ?>
        </tbody>
    </table>
    </div>
    <?
    if(db_num_rows($emails)>0):
     ?>
    <div class="card-footer">
        <div class="flex items-center gap-4 text-sm mb-3">
            <span class="text-gray-500">Выбрать:</span>
            <a href="#" class="text-blue-600 hover:text-blue-800" onclick="return select_all(document.forms['email'],true)">Все</a>
            <a href="#" class="text-blue-600 hover:text-blue-800" onclick="return reset_all(document.forms['email'])">Ничего</a>
            <a href="#" class="text-blue-600 hover:text-blue-800" onclick="return toogle_all(document.forms['email'],true)">Обратить</a>
        </div>
        <div class="flex items-center justify-center gap-3">
            <button class="btn-danger btn-sm" type="submit" name="delete" value="Удалить"
                onClick='return confirm("Вы уверены что хотите УДАЛИТЬ выбранные email адреса?");'>
                <i data-lucide="trash-2" class="w-4 h-4"></i> Удалить
            </button>
        </div>
    </div>
    <?
    endif;
    ?>
    </form>
</div>
