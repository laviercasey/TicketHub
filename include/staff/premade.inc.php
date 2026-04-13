<?php
if(!defined('OSTSCPINC') or !is_object($thisuser) or !$thisuser->canManageKb()) die('Доступ запрещён');

$select='SELECT premade.*,dept_name ';
$from='FROM '.KB_PREMADE_TABLE.' premade LEFT JOIN '.DEPT_TABLE.' USING(dept_id) ';

$qstr='';
if(($_REQUEST['a'] ?? '')==='search') {
    $hasQuery = !empty($_REQUEST['query']) && strlen($_REQUEST['query']) >= 3;
    $hasDept = !empty($_REQUEST['dept']);

    if(!empty($_REQUEST['query']) && strlen($_REQUEST['query']) < 3) {
        $errors['err']='Поисковый запрос должен быть не менее 3 символов';
    } else {
        $search=true;
        $qstr.='&a='.urlencode($_REQUEST['a']);
        $where=' WHERE 1';
        if($hasQuery) {
            $qstr.='&query='.urlencode($_REQUEST['query']);
            $where.=' AND (title LIKE '.db_input('%'.$_REQUEST['query'].'%').' OR answer LIKE '.db_input('%'.$_REQUEST['query'].'%').')';
        }
        if($hasDept) {
            $qstr.='&dept='.urlencode($_REQUEST['dept']);
            $where.=' AND dept_id='.db_input($_REQUEST['dept']);
        }
    }
}


$sortOptions=array('createdate'=>'premade.created','updatedate'=>'premade.updated','title'=>'premade.title');
$orderWays=array('DESC'=>'DESC','ASC'=>'ASC');
if(!empty($_REQUEST['sort'])) {
    $order_column =$sortOptions[$_REQUEST['sort']];
}

if(!empty($_REQUEST['order'])) {
    $order=$orderWays[$_REQUEST['order']];
}


$order_column=$order_column?$order_column:'premade.title';
$order=$order?$order:'DESC';

$order_by=$search?'':" ORDER BY $order_column $order ";


$total=db_count('SELECT count(*) '.$from.' '.$where);
$pagelimit=$thisuser->getPageLimit();
$pagelimit=$pagelimit?$pagelimit:PAGE_LIMIT;
$page=(isset($_GET['p']) && is_numeric($_GET['p']))?$_GET['p']:1;
$pageNav=new Pagenate($total,$page,$pagelimit);
$pageNav->setURL('kb.php',$qstr.'&sort='.urlencode($_REQUEST['sort'] ?? '').'&order='.urlencode($_REQUEST['order'] ?? ''));
$query="$select $from $where $order_by LIMIT ".$pageNav->getStart().",".$pageNav->getLimit();
$replies = db_query($query);
$showing=db_num_rows($replies)?$pageNav->showing():'';
$results_type=($search)?'Результаты поиска':'Готовые ответы';
$negorder=$order=='DESC'?'ASC':'DESC';
?>
<div>
    <?php if(!empty($errors['err'])) {?>
        <div class="alert-danger"><?=Format::htmlchars($errors['err'])?></div>
    <?php }elseif($msg) {?>
        <div class="alert-success"><?=Format::htmlchars($msg)?></div>
    <?php }elseif($warn) {?>
        <div class="alert-warning"><?=Format::htmlchars($warn)?></div>
    <?php }?>
</div>
<div class="flex justify-between items-center mb-4">
    <h2 class="text-lg font-heading font-semibold text-gray-900 flex items-center gap-2">
        <i data-lucide="message-square" class="w-5 h-5"></i> Готовые ответы
    </h2>
    <a href="kb.php?a=add" class="btn-primary">
        <i data-lucide="plus" class="w-4 h-4"></i> Новый готовый ответ
    </a>
</div>
<div class="flex items-center gap-3 mb-4">
    <form action="kb.php" method="GET" class="flex items-center gap-3">
    <input type="hidden" name="a" value="search">
    <span class="text-sm text-gray-600">Поиск:</span>
    <input type="text" name="query" class="input w-64" value="<?=Format::htmlchars($_REQUEST['query'])?>">
    <span class="text-sm text-gray-600">Отдел:</span>
    <select name="dept" class="select">
            <option value="0">Все Отделы</option>
            <?php
            $depts= db_query('SELECT dept_id,dept_name FROM '.DEPT_TABLE.' WHERE dept_id!='.db_input($ticket['dept_id']));
            while (list($deptId,$deptName) = db_fetch_row($depts)){
                $selected = (($_GET['dept'] ?? '')==$deptId)?'selected':''; ?>
                <option value="<?=$deptId?>"<?=$selected?>>&nbsp;&nbsp;<?=$deptName?></option>
           <?php }?>
    </select>
    <button type="submit" name="search" class="btn-primary"><i data-lucide="search" class="w-4 h-4"></i> Найти</button>
    </form>
</div>
<div class="card">
    <div class="card-header">
        <h2 class="text-lg font-heading font-semibold text-gray-900"><?=$result_type?>&nbsp;<?=$showing?></h2>
    </div>
    <form action="kb.php" method="POST" name="premade" onSubmit="return checkbox_checker(document.forms['premade'],1,0);">
    <?php echo Misc::csrfField(); ?>
    <input type="hidden" name="a" value="process">
    <div class="table-wrapper">
    <table class="table-modern">
        <thead>
        <tr>
	        <th class="table-th w-8">&nbsp;</th>
	        <th class="table-th">
                <a href="kb.php?sort=title&order=<?=$negorder?><?=$qstr?>" title="Сортировать по заголовку <?=$negorder?>" class="text-gray-700 hover:text-blue-600">Заголовок Ответов</a></th>
            <th class="table-th w-24">Статус</th>
	        <th class="table-th w-48">Отдел</th>
	        <th class="table-th w-40">
                <a href="kb.php?sort=updatedate&order=<?=$negorder?><?=$qstr?>" title="Сортировать по дате обновления <?=$negorder?>" class="text-gray-700 hover:text-blue-600">Последнее Обновление</a></th>
        </tr>
        </thead>
        <tbody>
        <?php
        $total=0;
        $grps=($errors && is_array($_POST['grps']))?$_POST['grps']:null;
        if($replies && db_num_rows($replies)):
            while ($row = db_fetch_array($replies)) {
                $sel=false;
                $highlight=false;
                if(($canned ?? null) && in_array($row['premade_id'],$canned)){
                    $sel=true;
                    $highlight=true;
                }elseif(($replyID ?? null) && $replyID==$row['premade_id']) {
                    $highlight=true;
                }
                ?>
            <tr id="<?=$row['premade_id']?>" class="<?=$highlight ? 'highlight' : ''?>">
                <td class="table-td w-8">
                  <input type="checkbox" class="checkbox" name="canned[]" value="<?=$row['premade_id']?>" <?=$sel?'checked':''?>
                        onClick="highLight(this.value,this.checked);">
                </td>
                <td class="table-td"><a href="kb.php?id=<?=$row['premade_id']?>" class="text-blue-600 hover:text-blue-800"><?=Format::htmlchars(Format::truncate($row['title'],60))?></a></td>
                <td class="table-td"><?=$row['isenabled']?'<span class="badge-success">Включен</span>':'<span class="badge-danger">Выключен</span>'?></td>
                <td class="table-td"><?=$row['dept_name']?Format::htmlchars($row['dept_name']):'Все отделы'?></td>
                <td class="table-td"><?=Format::db_datetime($row['updated'])?></td>
            </tr>
            <?php
            }
        else: ?>
            <tr><td colspan="6" class="table-td text-center text-gray-500">Запрос вернул пустой результат</td></tr>
        <?php
        endif; ?>
        </tbody>
    </table>
    </div>
    <?php
    if(db_num_rows($replies)>0):
     ?>
    <div class="card-footer">
        <div class="flex items-center gap-4 text-sm mb-3">
            <span class="text-gray-500">Выбрать:</span>
            <a href="#" class="text-blue-600 hover:text-blue-800" onclick="return select_all(document.forms['premade'],true)">Все</a>
            <a href="#" class="text-blue-600 hover:text-blue-800" onclick="return toogle_all(document.forms['premade'],true)">Обратить</a>
            <a href="#" class="text-blue-600 hover:text-blue-800" onclick="return reset_all(document.forms['premade'])">Ничего</a>
            <span class="text-gray-500 ml-auto">страница:<?=$pageNav->getPageLinks()?></span>
        </div>
        <div class="flex items-center justify-center gap-3">
            <button class="btn-success btn-sm" type="submit" name="enable" value="Включить"
                onClick='return confirm("Вы уверены что хотите ВКЛЮЧИТЬ выбранные записи?");'>
                <i data-lucide="check-circle" class="w-4 h-4"></i> Включить
            </button>
            <button class="btn-warning btn-sm" type="submit" name="disable" value="Отключить"
                onClick='return confirm("Вы уверены что хотите ОТКЛЮЧИТЬ выбранные записи?");'>
                <i data-lucide="x-circle" class="w-4 h-4"></i> Отключить
            </button>
            <button class="btn-danger btn-sm" type="submit" name="delete" value="Удалить"
                onClick='return confirm("Вы уверены что хотите УДАЛИТЬ выбранные записи?");'>
                <i data-lucide="trash-2" class="w-4 h-4"></i> Удалить
            </button>
        </div>
    </div>
    <?php
    endif;
    ?>
    </form>
</div>
