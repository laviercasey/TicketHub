<?php
if(!defined('OSTADMININC') || !$thisuser->isadmin()) die('Доступ запрещён');

$sql='SELECT topic_id,isactive,topic.noautoresp,topic.dept_id,topic,dept_name,priority_desc,topic.created,topic.updated FROM '.TOPIC_TABLE.' topic '.
     ' LEFT JOIN '.DEPT_TABLE.' dept ON dept.dept_id=topic.dept_id '.
     ' LEFT JOIN '.TICKET_PRIORITY_TABLE.' pri ON pri.priority_id=topic.priority_id ';
$services=db_query($sql.' ORDER BY topic');
?>
<div class="flex justify-between items-center mb-4">
    <h2 class="text-lg font-heading font-semibold text-gray-900 flex items-center gap-2">
        <i data-lucide="help-circle" class="w-5 h-5"></i> Тема Обращения
    </h2>
    <a href="admin.php?t=topics&a=new" class="btn-primary">
        <i data-lucide="plus" class="w-4 h-4"></i> Создать новую тему
    </a>
</div>
<div class="card">
    <div class="card-header">
        <h2 class="text-lg font-heading font-semibold text-gray-900">Тема Обращения</h2>
    </div>
   <form action="admin.php?t=topics" method="POST" name="topic" onSubmit="return checkbox_checker(document.forms['topic'],1,0);">
   <?php echo Misc::csrfField(); ?>
   <input type="hidden" name="t" value="topics">
   <input type="hidden" name="do" value="mass_process">
    <div class="table-wrapper">
    <table class="table-modern">
        <thead>
        <tr>
	        <th class="table-th w-8">&nbsp;</th>
	        <th class="table-th">Тема Обращения</th>
            <th class="table-th">Статус</th>
            <th class="table-th">Автоответ.</th>
            <th class="table-th">Отдел</th>
            <th class="table-th">Приоритет</th>
	        <th class="table-th">Обновлен</th>
        </tr>
        </thead>
        <tbody>
        <?
        $class = 'row1';
        $total=0;
        $ids=($errors && is_array($_POST['tids']))?$_POST['tids']:null;
        if($services && db_num_rows($services)):
            while ($row = db_fetch_array($services)) {
                $sel=false;
                if(($ids && in_array($row['topic_id'],$ids)) or ($row['topic_id']==(isset($topicID)?$topicID:null))){
                    $class="$class highlight";
                    $sel=true;
                }
                ?>
            <tr id="<?=$row['topic_id']?>">
                <td class="table-td w-8">
                 <input type="checkbox" class="checkbox" name="tids[]" value="<?=$row['topic_id']?>" <?=$sel?'checked':''?> onClick="highLight(this.value,this.checked);">
                </td>
                <td class="table-td"><a href="admin.php?t=topics&id=<?=$row['topic_id']?>" class="text-blue-600 hover:text-blue-800"><?=Format::htmlchars(Format::truncate($row['topic'],30))?></a></td>
                <td class="table-td"><?=$row['isactive']?'<span class="badge-success">Активно</span>':'<span class="badge-danger">Отключено</span>'?></td>
                <td class="table-td"><?=$row['noautoresp']?'<span class="text-gray-500">Нет</span>':'<span class="badge-success">Да</span>'?></td>
                <td class="table-td"><a href="admin.php?t=dept&id=<?=(int)$row['dept_id']?>" class="text-blue-600 hover:text-blue-800"><?=Format::htmlchars($row['dept_name'])?></a></td>
                <td class="table-td"><?=Format::htmlchars($row['priority_desc'])?></td>
                <td class="table-td"><?=Format::db_datetime($row['updated'])?></td>
            </tr>
            <?
            $class = ($class =='row2') ?'row1':'row2';
            }
        else: ?>
            <tr><td colspan="7" class="table-td text-center text-gray-500">Запрос вернул пустой результат</td></tr>
        <?
        endif; ?>
        </tbody>
    </table>
    </div>
    <div class="card-footer space-y-3">
        <?php if (db_num_rows($services) > 0): ?>
        <div class="border-t border-gray-100 pt-3">
            <div class="flex items-center gap-4 text-sm mb-3">
                <span class="text-gray-500">Выбрать:</span>
                <a href="#" class="text-blue-600 hover:text-blue-800" onclick="return select_all(document.forms['topic'],true)">Все</a>
                <a href="#" class="text-blue-600 hover:text-blue-800" onclick="return reset_all(document.forms['topic'])">Ничего</a>
                <a href="#" class="text-blue-600 hover:text-blue-800" onclick="return toogle_all(document.forms['topic'],true)">Инвертировать</a>
            </div>
            <div class="flex items-center justify-center gap-3">
                <button class="btn-success btn-sm" type="submit" name="enable" value="Включить"
                    onClick='return confirm("Вы уверены, что хотите включить выбранные темы?");'>
                    <i data-lucide="check-circle" class="w-4 h-4"></i> Включить
                </button>
                <button class="btn-warning btn-sm" type="submit" name="disable" value="Отключить"
                    onClick='return confirm("Вы уверены, что хотите ОТКЛЮЧИТЬ выбранные темы?");'>
                    <i data-lucide="x-circle" class="w-4 h-4"></i> Отключить
                </button>
                <button class="btn-danger btn-sm" type="submit" name="delete" value="Удалить"
                    onClick='return confirm("Вы уверены, что хотите УДАЛИТЬ выбранные темы?");'>
                    <i data-lucide="trash-2" class="w-4 h-4"></i> Удалить
                </button>
            </div>
        </div>
        <?php endif; ?>
    </div>
    </form>
</div>
