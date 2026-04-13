<?php
if(!defined('OSTADMININC') || !$thisuser->isadmin()) die('Доступ запрещён');

$select='SELECT * ';
$from='FROM '.BANLIST_TABLE;
$where='';
$qstr='';
if(($_REQUEST['a'] ?? '')=='search') {
    if(empty($_REQUEST['query']) || strlen($_REQUEST['query'])<3) {
        $errors['err']='Поисковый запрос должен содержать более 3 символов';
    }else{
        $search=true;
        $qstr.='&a='.urlencode($_REQUEST['a']);
        $qstr.='&query='.urlencode($_REQUEST['query']);
        $searchTerm=trim($_REQUEST['query']);
        if(strpos($searchTerm,'@') && Validator::is_email($searchTerm)){
            $where=' WHERE email='.db_input($searchTerm);
        }else{
            $searchEscaped=str_replace(array('%','_'),array('\\%','\\_'),db_real_escape($searchTerm,false));
            $where=' WHERE email LIKE '.db_input('%'.$searchEscaped.'%');
        }
    }
}

$sortOptions=array('date'=>'added','email'=>'email');
$orderWays=array('DESC'=>'DESC','ASC'=>'ASC');
if(!empty($_REQUEST['sort'])) {
    $order_column =$sortOptions[$_REQUEST['sort']] ?? null;
}

if(!empty($_REQUEST['order'])) {
    $order=$orderWays[$_REQUEST['order']] ?? null;
}


$order_column=!empty($order_column)?$order_column:'added';
$order=!empty($order)?$order:'DESC';

$order_by=" ORDER BY $order_column $order ";

$total=db_count('SELECT count(*) '.$from.' '.$where);
$pagelimit=$thisuser->getPageLimit();
$pagelimit=$pagelimit?$pagelimit:PAGE_LIMIT;
$page=(isset($_GET['p']) && is_numeric($_GET['p']))?$_GET['p']:1;
$pageNav=new Pagenate($total,$page,$pagelimit);
$pageNav->setURL('admin.php',$qstr.'&sort='.urlencode($_REQUEST['sort'] ?? '').'&order='.urlencode($_REQUEST['order'] ?? ''));
$query="$select $from $where $order_by LIMIT ".$pageNav->getStart().",".$pageNav->getLimit();
$banlist = db_query($query);
$showing=db_num_rows($banlist)?$pageNav->showing():'';
$result_type=($search)?'Результаты Поиска':'Заблокированные Email Адреса';
$negorder=$order=='DESC'?'ASC':'DESC';
$showadd=($errors && ($_POST['a'] ?? '')=='add')?true:false;
?>
<div id="search" style="display:<?=$showadd?'none':'block'?>;">
    <div class="flex items-center gap-3 mb-4">
    <form action="admin.php?t=settings" method="GET" class="flex items-center gap-3">
        <input type="hidden" name="t" value="banlist">
        <input type="hidden" name="a" value="search">
        <span class="text-sm text-gray-600">Поиск:</span>
        <input type="text" name="query" class="input w-64" value="<?=Format::htmlchars($_REQUEST['query'] ?? '')?>">
        <button type="submit" name="search" class="btn-primary"><i data-lucide="search" class="w-4 h-4"></i> Поиск</button>
        <a href="#" class="text-blue-600 hover:text-blue-800 text-sm" onClick="showHide('add','search'); return false;">(Добавить)</a>
    </form>
    </div>
</div>
<div id="add" style="display:<?=$showadd?'block':'none'?>;">
    <div class="flex items-center gap-3 mb-4">
    <form action="admin.php" method="POST" class="flex items-center gap-3">
    <?php echo Misc::csrfField(); ?>
    <input type="hidden" name="t" value="banlist">
    <input type="hidden" name="a" value="add">
    <span class="text-sm text-gray-600">Email:</span>
    <input type="text" name="email" class="input w-64" value="<?=Format::htmlchars($_POST['email'] ?? '')?>">
    <button type="submit" name="add" class="btn-primary btn-sm"><i data-lucide="plus-circle" class="w-4 h-4"></i> Добавить</button>
    <a href="#" class="text-blue-600 hover:text-blue-800 text-sm" onClick="showHide('add','search'); return false;">(Поиск)</a>
    </form>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h2 class="text-lg font-heading font-semibold text-gray-900"><?=$result_type?>: <?=$showing?></h2>
    </div>
    <form action="admin.php?t=banlist" method="POST" name="banlist" onSubmit="return checkbox_checker(document.forms['banlist'],1,0);">
    <?php echo Misc::csrfField(); ?>
    <input type="hidden" name="t" value="banlist">
    <input type="hidden" name="a" value="remove">
    <div class="table-wrapper">
    <table class="table-modern">
        <thead>
        <tr>
	        <th class="table-th w-8">&nbsp;</th>
	        <th class="table-th w-64">
                <a href="admin.php?t=banlist&sort=email&order=<?=$negorder?><?=$qstr?>" title="Сортировать по email <?=$negorder?>" class="text-gray-700 hover:text-blue-600">Email</a></th>
	        <th class="table-th w-52">Отправитель</th>
	        <th class="table-th w-40">
                <a href="admin.php?t=banlist&sort=date&order=<?=$negorder?><?=$qstr?>" title="Сортировать по дате создания <?=$negorder?>" class="text-gray-700 hover:text-blue-600">Дата Добавления</a></th>
        </tr>
        </thead>
        <tbody>
        <?
        $total=0;
        $sids=($errors && is_array($_POST['ids']))?$_POST['ids']:null;
        if($banlist && db_num_rows($banlist)):
            while ($row = db_fetch_array($banlist)) {
                $sel=false;
                if($sids && in_array($row['id'],$sids)){
                    $sel=true;
                }
                ?>
            <tr id="<?=$row['id']?>" class="<?=$sel ? 'highlight' : ''?>">
                <td class="table-td w-8">
                  <input type="checkbox" class="checkbox" name="ids[]" value="<?=$row['id']?>" <?=$sel?'checked':''?>
                        onClick="highLight(this.value,this.checked);">
                </td>
                <td class="table-td"><?=Format::htmlchars($row['email'])?></td>
                <td class="table-td"><?=Format::htmlchars($row['submitter'])?></td>
                <td class="table-td"><?=Format::db_datetime($row['added'])?></td>
            </tr>
            <?
            }
        else: ?>
            <tr><td colspan="4" class="table-td text-center text-gray-500">Запрос вернул 0 результатов &nbsp;&nbsp;<a href="admin.php?t=banlist" class="text-blue-600 hover:text-blue-800">Список</a></td></tr>
        <?
        endif; ?>
        </tbody>
    </table>
    </div>
    <?
    if(db_num_rows($banlist)>0):
     ?>
    <div class="card-footer">
        <div class="flex items-center gap-4 text-sm mb-3">
            <span class="text-gray-500">Выбрать:</span>
            <a href="#" class="text-blue-600 hover:text-blue-800" onclick="return select_all(document.forms['banlist'],true)">Все</a>
            <a href="#" class="text-blue-600 hover:text-blue-800" onclick="return toogle_all(document.forms['banlist'],true)">Обратить</a>
            <a href="#" class="text-blue-600 hover:text-blue-800" onclick="return reset_all(document.forms['banlist'])">Ничего</a>
            <span class="text-gray-500 ml-auto">страница:<?=$pageNav->getPageLinks()?></span>
        </div>
        <div class="flex items-center justify-center gap-3">
            <button class="btn-danger btn-sm" type="submit" name="delete" value="Удалить"
                     onClick='return confirm("Вы уверены, что хотите УДАЛИТЬ выбранный email из бан листа?");'>
                <i data-lucide="trash-2" class="w-4 h-4"></i> Удалить
            </button>
        </div>
    </div>
    <?
    endif;
    ?>
    </form>
</div>
