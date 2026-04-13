<?php
if(!defined('OSTADMININC') || !$thisuser->isadmin()) die('Доступ запрещён');


$select='SELECT tpl.*,count(dept.tpl_id) as depts ';
$from='FROM '.EMAIL_TEMPLATE_TABLE.' tpl '.
      'LEFT JOIN '.DEPT_TABLE.' dept USING(tpl_id) ';
$where='';
$sortOptions=array('date'=>'tpl.created','name'=>'tpl.name');
$orderWays=array('DESC'=>'DESC','ASC'=>'ASC');
if(!empty($_REQUEST['sort'])) {
    $order_column =$sortOptions[$_REQUEST['sort']] ?? null;
}

if(!empty($_REQUEST['order'])) {
    $order=$orderWays[$_REQUEST['order']] ?? null;
}
$order_column=!empty($order_column)?$order_column:'name';
$order=!empty($order)?$order:'ASC';
$order_by=" ORDER BY $order_column $order ";

$total=db_count('SELECT count(*) '.$from.' '.$where);
$pagelimit=1000;//No limit.
$page=(isset($_GET['p']) && is_numeric($_GET['p']))?$_GET['p']:1;
$pageNav=new Pagenate($total,$page,$pagelimit);
$qstr='t=templates';
$pageNav->setURL('admin.php',$qstr.'&sort='.urlencode($_REQUEST['sort'] ?? '').'&order='.urlencode($_REQUEST['order'] ?? ''));
$query="$select $from $where GROUP BY tpl.tpl_id $order_by";
$result = db_query($query);
$showing=db_num_rows($result)?$pageNav->showing():'';
$negorder=$order=='DESC'?'ASC':'DESC'; //Negate the sorting..
$deletable=0;
?>
<div class="card mb-6">
    <div class="card-header flex items-center justify-between">
        <h2 class="text-lg font-heading font-semibold text-gray-900">Email Шаблоны</h2>
        <span class="text-sm text-gray-500"><?=$showing?></span>
    </div>
   <form action="admin.php?t=templates" method="POST" name="tpl" onSubmit="return checkbox_checker(document.forms['tpl'],1,0);">
   <?php echo Misc::csrfField(); ?>
   <input type="hidden" name="t" value="templates">
   <input type="hidden" name="do" value="mass_process">
    <div class="table-wrapper">
    <table class="table-modern">
        <thead>
        <tr>
	        <th class="table-th w-8">&nbsp;</th>
	        <th class="table-th">
                <a href="admin.php?t=templates&sort=name&order=<?=$negorder?>" title="Сортировать по имени <?=$negorder?>" class="text-gray-700 hover:text-blue-600">Имя</a></th>
            <th class="table-th w-20">Используется</th>
	        <th class="table-th w-44">
                <a href="admin.php?t=templates&sort=date&order=<?=$negorder?>" title="Сортировать по дате <?=$negorder?>" class="text-gray-700 hover:text-blue-600">Последнее обновление</a></th>
            <th class="table-th w-44">Создан</th>
        </tr>
        </thead>
        <tbody>
        <?
        $class = 'row1';
        $total=0;
        $sids=($errors && is_array($_POST['ids']))?$_POST['ids']:null;
        if($result && db_num_rows($result)):
            $dtpl=$cfg->getDefaultTemplateId();
            while ($row = db_fetch_array($result)) {
                $sel=false;
                $disabled='';
                if($dtpl==$row['tpl_id'] || $row['depts'])
                    $disabled='disabled';
                else {
                    $deletable++;
                    if($sids && in_array($row['tpl_id'],$sids)){
                        $class="$class highlight";
                        $sel=true;
                    }
                }
                ?>
            <tr id="<?=$row['tpl_id']?>">
                <td class="table-td w-8">
                  <input type="checkbox" class="checkbox" name="ids[]" value="<?=$row['tpl_id']?>" <?=$sel?'checked':''?> <?=$disabled?>
                        onClick="highLight(this.value,this.checked);">
                </td>
                <td class="table-td"><a href="admin.php?t=templates&id=<?=$row['tpl_id']?>" class="text-blue-600 hover:text-blue-800"><?=$row['name']?></a></td>
                <td class="table-td"><?=$disabled?'<span class="badge-success">Yes</span>':'<span class="text-gray-500">No</span>'?></td>
                <td class="table-td"><?=Format::db_datetime($row['updated'])?></td>
                <td class="table-td"><?=Format::db_datetime($row['created'])?></td>
            </tr>
            <?
            $class = ($class =='row2') ?'row1':'row2';
            } //end of while.
        else: //nothin' found!! ?>
            <tr><td colspan="5" class="table-td text-center text-gray-500">Запрос вернул пустой результат &nbsp;&nbsp;<a href="admin.php?t=templates" class="text-blue-600 hover:text-blue-800">Основной лист</a></td></tr>
        <?
        endif; ?>
        </tbody>
     </table>
    </div>
    <?
    if(db_num_rows($result)>0 && $deletable): //Show options..
     ?>
    <div class="card-footer">
        <div class="flex items-center justify-center gap-3">
            <button class="btn-danger btn-sm" type="submit" name="delete" value="Удалить шаблон(ы)"
                     onClick='return confirm("Вы уверены что хотите УДАЛИТЬ выбранный(-ые) шаблон(ы)?");'>
                <i data-lucide="trash-2" class="w-4 h-4"></i> Удалить шаблон(ы)
            </button>
        </div>
    </div>
    <?
    endif;
    ?>
    </form>
</div>

<div class="card mb-6">
    <div class="card-header"><h3 class="font-heading font-semibold">Добавить новый шаблон</h3></div>
    <div class="card-body">
        <p class="text-sm text-gray-600 mb-4">Для добавления нового шаблона выберите существующий шаблон и затем измените его.</p>
        <form action="admin.php?t=templates" method="POST">
            <?php echo Misc::csrfField(); ?>
            <input type="hidden" name="t" value="templates">
            <input type="hidden" name="do" value="add">
            <div class="flex flex-wrap items-center gap-3">
                <div class="flex items-center gap-2">
                    <label class="label whitespace-nowrap">Имя:</label>
                    <input class="input w-64" name="name" value="<?=($errors)?Format::htmlchars($_REQUEST['name']):''?>" />
                    <span class="form-error">*&nbsp;<?=$errors['name']?></span>
                </div>
                <div class="flex items-center gap-2">
                    <label class="label whitespace-nowrap">Копия:</label>
                    <select class="select" name="copy_template">
                        <option value="0">выберите шаблон для копирования</option>
                          <?
                          $result=db_query('SELECT tpl_id,name FROM '.EMAIL_TEMPLATE_TABLE);
                          while (list($id,$name)= db_fetch_row($result)){ ?>
                              <option value="<?=$id?>"><?=$name?></option>
                                  <?
                          }?>
                     </select>
                     <span class="form-error">*&nbsp;<?=$errors['copy_template']?></span>
                </div>
                <button class="btn-primary btn-sm" type="submit" name="add" value="Добавить">
                    <i data-lucide="plus-circle" class="w-4 h-4"></i> Добавить
                </button>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-header"><h3 class="font-heading font-semibold">Переменные</h3></div>
    <div class="card-body">
        <p class="text-sm text-gray-600 mb-4">Variables are used on email templates as placeholders. Please note that non-base variables depends on the context in question.</p>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <h4 class="font-semibold text-gray-900 mb-2">Базовые переменные</h4>
                <div class="space-y-1 text-sm">
                    <div class="flex gap-4"><code class="text-blue-600 w-36">%id</code><span class="text-gray-600">ID заявки (внутренний ID)</span></div>
                    <div class="flex gap-4"><code class="text-blue-600 w-36">%ticket</code><span class="text-gray-600">Номер заявки (внешний ID)</span></div>
                    <div class="flex gap-4"><code class="text-blue-600 w-36">%email</code><span class="text-gray-600">Email адрес</span></div>
                    <div class="flex gap-4"><code class="text-blue-600 w-36">%name</code><span class="text-gray-600">Имя</span></div>
                    <div class="flex gap-4"><code class="text-blue-600 w-36">%subject</code><span class="text-gray-600">Тема</span></div>
                    <div class="flex gap-4"><code class="text-blue-600 w-36">%topic</code><span class="text-gray-600">Тема обращения (web only)</span></div>
                    <div class="flex gap-4"><code class="text-blue-600 w-36">%phone</code><span class="text-gray-600">Номер телефона</span></div>
                    <div class="flex gap-4"><code class="text-blue-600 w-36">%status</code><span class="text-gray-600">Статус</span></div>
                    <div class="flex gap-4"><code class="text-blue-600 w-36">%priority</code><span class="text-gray-600">Приоритет</span></div>
                    <div class="flex gap-4"><code class="text-blue-600 w-36">%dept</code><span class="text-gray-600">Отдел</span></div>
                    <div class="flex gap-4"><code class="text-blue-600 w-36">%assigned_staff</code><span class="text-gray-600">Assigned staff (if any)</span></div>
                    <div class="flex gap-4"><code class="text-blue-600 w-36">%createdate</code><span class="text-gray-600">Дата создания</span></div>
                    <div class="flex gap-4"><code class="text-blue-600 w-36">%duedate</code><span class="text-gray-600">Due date</span></div>
                    <div class="flex gap-4"><code class="text-blue-600 w-36">%closedate</code><span class="text-gray-600">Дата закрытия</span></div>
                </div>
            </div>
            <div>
                <h4 class="font-semibold text-gray-900 mb-2">Другие переменные</h4>
                <div class="space-y-1 text-sm">
                    <div class="flex gap-4"><code class="text-blue-600 w-36">%message</code><span class="text-gray-600">Сообщение (incoming)</span></div>
                    <div class="flex gap-4"><code class="text-blue-600 w-36">%response</code><span class="text-gray-600">Ответ (outgoing)</span></div>
                    <div class="flex gap-4"><code class="text-blue-600 w-36">%note</code><span class="text-gray-600">Внутреннее сообщение</span></div>
                    <div class="flex gap-4"><code class="text-blue-600 w-36">%staff</code><span class="text-gray-600">Staff's name (alert/notices)</span></div>
                    <div class="flex gap-4"><code class="text-blue-600 w-36">%assignee</code><span class="text-gray-600">Assigned staff</span></div>
                    <div class="flex gap-4"><code class="text-blue-600 w-36">%assigner</code><span class="text-gray-600">Staff assigning the ticket</span></div>
                    <div class="flex gap-4"><code class="text-blue-600 w-36">%url</code><span class="text-gray-600">TicketHub базовый url (FQDN)</span></div>
                </div>
            </div>
        </div>
    </div>
</div>
