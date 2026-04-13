<?php
if(!defined('OSTSCPINC') || !@$thisuser->isStaff()) die('Доступ запрещён');

$qstr='&t=syslog';
if(!empty($_REQUEST['type'])) {
    $qstr.='&amp;type='.urlencode($_REQUEST['type']);
}

$type=null;

switch(strtolower($_REQUEST['type'] ?? '')){
    case 'error':
        $title='Ошибки';
        $type=$_REQUEST['type'];
        break;
    case 'warning':
        $title='Предупреждения';
        $type=$_REQUEST['type'];
        break;
    case 'debug':
        $title='Отладочные записи';
        $type=$_REQUEST['type'];
        break;
    default:
        $type=null;
        $title='Все записи';
}

$qwhere =' WHERE 1';

if($type){
    $qwhere.=' AND log_type='.db_input($type);
}

$startTime  =(!empty($_REQUEST['startDate']) && (strlen($_REQUEST['startDate'])>=8))?strtotime($_REQUEST['startDate']):0;
$endTime    =(!empty($_REQUEST['endDate']) && (strlen($_REQUEST['endDate'])>=8))?strtotime($_REQUEST['endDate']):0;
if( ($startTime && $startTime>time()) or ($startTime>$endTime && $endTime>0)){
    $errors['err']='Указанный период дат недействителен. Выбор проигнорирован.';
    $startTime=$endTime=0;
}else{

    if($startTime){

        $qwhere.=' AND created>=FROM_UNIXTIME('.$startTime.')';


        $qstr.='&startDate='.urlencode($_REQUEST['startDate']);



    }

    if($endTime){

        $qwhere.=' AND created<=FROM_UNIXTIME('.$endTime.')';

        $qstr.='&endDate='.urlencode($_REQUEST['endDate']);

    }
}

$qselect = 'SELECT log.* ';
$qfrom=' FROM '.SYSLOG_TABLE.' log ';
$total=db_count("SELECT count(*) $qfrom $qwhere");
$pagelimit=30;
$page = (isset($_GET['p']) && is_numeric($_GET['p']))?$_GET['p']:1;
$pageNav=new Pagenate($total,$page,$pagelimit);
$pageNav->setURL('admin.php',$qstr);
$query="$qselect $qfrom $qwhere ORDER BY log.created DESC LIMIT ".$pageNav->getStart().",".$pageNav->getLimit();
$result = db_query($query);
$showing=($result && db_num_rows($result))?$pageNav->showing():"";
?>
<h2 class="text-lg font-heading font-semibold text-gray-900 mb-4">Системные Журналы</h2>

<div class="bg-gray-50 rounded-xl p-4 border border-gray-200 mb-4">
 <form action="admin.php?t=syslog" method="get" class="flex flex-wrap items-center gap-3">
    <input type="hidden" name="t" value="syslog" />
    <span class="text-sm text-gray-600">Дата:</span>
    <span class="text-sm text-gray-500">от</span>
    <input id="sd" name="startDate" class="input w-36 datepicker" value="<?=Format::htmlchars($_REQUEST['startDate'] ?? '')?>" autocomplete="OFF">
    <a href="#" onclick="document.getElementById('sd')._flatpickr.open(); return false;"><i data-lucide="calendar" class="w-4 h-4 text-gray-500"></i></a>
    <span class="text-sm text-gray-500">до</span>
    <input id="ed" name="endDate" class="input w-36 datepicker" value="<?=Format::htmlchars($_REQUEST['endDate'] ?? '')?>" autocomplete="OFF">
    <a href="#" onclick="document.getElementById('ed')._flatpickr.open(); return false;"><i data-lucide="calendar" class="w-4 h-4 text-gray-500"></i></a>
    <span class="text-sm text-gray-600">Тип:</span>
    <select name="type" class="select w-auto">
        <option value="" selected>Все</option>
        <option value="Error" <?=($type=='Error')?'selected="selected"':''?>>Ошибки</option>
        <option value="Warning" <?=($type=='Warning')?'selected="selected"':''?>>Предупреждения</option>
        <option value="Debug" <?=($type=='Debug')?'selected="selected"':''?>>Отладка</option>
    </select>
    <button type="submit" class="btn-primary"><i data-lucide="filter" class="w-4 h-4"></i> Перейти</button>
 </form>
</div>

<div class="card">
    <form action="tickets.php" method="POST" name="tickets" onSubmit="return checkbox_checker(this,1,0);">
    <?php echo Misc::csrfField(); ?>
    <input type="hidden" name="a" value="mass_process">
    <input type="hidden" name="status" value="<?=$statusss ?? ''?>">
    <div class="table-wrapper">
    <table class="table-modern">
        <thead>
        <tr><th class="table-th"><?=$title?></th></tr>
        </thead>
        <tbody>
        <?
        $total=0;
        if($result && ($num=db_num_rows($result))):
            $icons=array('Debug'=>'debugLog','Warning'=>'alertLog','Error'=>'errorLog');
            while ($row = db_fetch_array($result)) {
                $icon=isset($icons[$row['log_type']])?$icons[$row['log_type']]:'debugLog';
                ?>
            <tr id="<?=$row['log_id']?>">
                <td class="table-td">
                  <a href="javascript:toggleMessage('<?=$row['log_id']?>');" class="flex items-center gap-2 text-gray-800 hover:text-blue-600">
                  <i data-lucide="plus-square" class="w-4 h-4 flex-shrink-0" id="img_<?=$row['log_id']?>"></i>
                  <span class="w-48 flex-shrink-0 text-sm text-gray-600"><?=Format::db_daydatetime($row['created'])?></span>
                  <span class="Icon <?=$icon?> text-sm"><?=Format::htmlchars($row['title'])?></span></a>
                    <div id="msg_<?=$row['log_id']?>" class="hidden mt-2 pl-6 border-t border-gray-200 pt-2">
                        <?=Format::display($row['log'])?>
                        <div class="text-right text-xs text-gray-400 italic mt-1"><?=Format::htmlchars($row['ip_address'])?></div>
                    </div>

                </td>
            </tr>
            <?
            }
        else: ?>
            <tr><td class="table-td text-center text-gray-500">Запрос вернул 0 результатов.</td></tr>
        <?
        endif; ?>
        </tbody>
    </table>
    </div>
    <?
    if($num>0){
    ?>
        <div class="card-footer">
            <div class="flex items-center gap-4 text-sm">
                <span class="text-gray-500">страница:<?=$pageNav->getPageLinks()?></span>
            </div>
        </div>
    <?} ?>
    </form>
</div>
<?
