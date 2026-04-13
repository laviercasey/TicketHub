<?php
if(!defined('OSTSCPINC') || !@$thisuser->isStaff()) die('Доступ запрещён');

$qstr='&';
if(!empty($_REQUEST['status'])) {
    $qstr.='status='.urlencode($_REQUEST['status']);
}

$search=(($_REQUEST['a'] ?? '')==='поиск' || ($_REQUEST['a'] ?? '')==='search')?true:false;
$searchTerm='';
if($search) {
  $searchTerm=$_REQUEST['query'];
  if( ($_REQUEST['query'] && strlen($_REQUEST['query'])<3)
      || (!$_REQUEST['query'] && isset($_REQUEST['basic_search'])) ){
      $search=false;
      $errors['err']='Search term must be more than 3 chars';
      $searchTerm='';
  }
}
$showoverdue=$showanswered=false;
$staffId=0;
$status=null;
$isArchived = false;
switch(strtolower($_REQUEST['status'] ?? '')){
    case 'open':
        $status='open';
        break;
    case 'closed':
        $status='closed';
        break;
    case 'overdue':
        $status='open';
        $showoverdue=true;
        $results_type='Просроченные заявки';
        break;
    case 'assigned':
        $status='open';
        $staffId=$thisuser->getId();
        $results_type='Назначенные заявки';
        break;
    case 'answered':
        $status='open';
        $showanswered=true;
        $results_type='Отвеченные заявки';
        break;
	case 'archived':
        $isArchived = true;
        break;
    default:
        if(!$search)
            $status='open';
}

$explicitFilter = !empty($_REQUEST['status']) || $staffId || $showoverdue || $showanswered;
if(false && $stats && !$explicitFilter) {
    if(!$stats['open'] && (!$status || $status=='open')){
        if(!$cfg->showAnsweredTickets() && $stats['answered']) {
             $status='open';
             $showanswered=true;
             $results_type='Отвеченные заявки';
        }elseif(!$stats['answered']) {
            $status='closed';
            $results_type='Закрытые заявки';
        }
    }
}

$have_deps_in_search = false;
$start_str = ' ticket.dept_id IN (';
$s_deps_id = $start_str;
for ($x = 0; $x <= 20; $x++) {
    if (isset($_REQUEST["s_deps_id_" . $x])) {
        $have_deps_in_search = true;
        $zpt = $s_deps_id == $start_str ? '' : ',';
        $s_deps_id .=  $zpt . intval($_REQUEST["s_deps_id_" . $x]);
    }
}
if ($s_deps_id != $start_str)
{
    $qwhere_deps = $s_deps_id.')';
}
$have_assigns_in_search = false;
$start_str = ' (';
$s_assigns_id = $start_str;
for ($x = 0; $x <= 20; $x++) {
    if (isset($_REQUEST["s_assigns_id_" . $x])) {
        $have_assigns_in_search = true;
        $zpt = $s_assigns_id == $start_str ? '' : ' OR ';
        $safe_assign_id = intval($_REQUEST["s_assigns_id_" . $x]);
        $s_assigns_id .=  $zpt . 'ticket.andstaffs_id LIKE \'%*'.$safe_assign_id.'*%\'';
        $s_assigns_id .=  ' OR ticket.staff_id='.$safe_assign_id;
    }
}
if ($s_assigns_id != $start_str)
{
    $and = empty($qwhere_deps) ? '' : ' AND ';
    $qwhere_assigns = $and.$s_assigns_id.')';
}

$qwhere ='';
$depts=$thisuser->getDepts();
if(!$depts or !is_array($depts) or !count($depts)){
    $qwhere =' WHERE ticket.dept_id IN ( 0 ) ';
}else if($thisuser->isadmin()){
    $qwhere = ($have_deps_in_search || $have_assigns_in_search) ?
        (' WHERE ('.$qwhere_deps.$qwhere_assigns.')') :
        ' WHERE 1';
}else{
    $qwhere = ($have_deps_in_search || $have_assigns_in_search) ?
        (' WHERE ('.$qwhere_deps.$qwhere_assigns.')') :
        (' WHERE (ticket.dept_id IN ('.implode(',',$depts).')'. (empty($qwhere_assigns) ? ' OR ticket.andstaffs_id LIKE \'%*'.$thisuser->getId().'*%\' OR ticket.staff_id='.$thisuser->getId().')':''));
}

if($status){
    $qwhere.=' AND status='.db_input(strtolower($status));
}

if($staffId && ($staffId==$thisuser->getId())) {
    $results_type='Назначенные заявки';
    $qwhere.=' AND (ticket.staff_id='.db_input($staffId).' OR ticket.andstaffs_id LIKE \'%*'.$staffId.'*%\')';
}elseif($showoverdue) {
    $qwhere.=' AND isoverdue=1 ';
}elseif($showanswered) {
    $qwhere.=' AND isanswered=1 ';
}elseif(!$search && !$cfg->showAnsweredTickets() && !strcasecmp($status,'open')) {
    $qwhere.=' AND isanswered=0 ';
}

if(!$cfg->showAssignedTickets() && !$thisuser->isadmin()) {
    $qwhere.=' AND (ticket.staff_id=0 OR ticket.staff_id='.db_input($thisuser->getId()).' OR dept.manager_id='.db_input($thisuser->getId()).') ';
}

$deep_search=false;
if($search):
    $qstr.='&a='.urlencode($_REQUEST['a']);
    $qstr.='&t='.urlencode($_REQUEST['t']);
    if(isset($_REQUEST['advance_search'])){
        $qstr.='&advance_search=Search';
    }

    if($searchTerm){
        $qstr.='&query='.urlencode($searchTerm);
        $queryterm=str_replace(array('%','_'),array('\\%','\\_'),db_real_escape($searchTerm,false));
        if(is_numeric($searchTerm)){
            $qwhere.=" AND ticket.ticketID LIKE '$queryterm%'";
        }elseif(strpos($searchTerm,'@') && Validator::is_email($searchTerm)){
            $qwhere.=" AND ticket.email='".db_real_escape($searchTerm,false)."'";
        }else{
            $deep_search=true;
            $ftterm=db_real_escape($searchTerm,false);
            if($_REQUEST['stype'] && $_REQUEST['stype']=='FT') {
                $qwhere.=" AND ( ticket.email LIKE '%$queryterm%'".
                            " OR ticket.name LIKE '%$queryterm%'".
                            " OR ticket.subject LIKE '%$queryterm%'".
                            " OR note.title LIKE '%$queryterm%'".
                            " OR MATCH(message.message)   AGAINST('$ftterm')".
                            " OR MATCH(response.response) AGAINST('$ftterm')".
                            " OR MATCH(note.note) AGAINST('$ftterm')".
                            ' ) ';
            }else{
                $qwhere.=" AND ( ticket.email LIKE '%$queryterm%'".
                            " OR ticket.name LIKE '%$queryterm%'".
                            " OR ticket.subject LIKE '%$queryterm%'".
                            " OR message.message LIKE '%$queryterm%'".
                            " OR response.response LIKE '%$queryterm%'".
                            " OR note.note LIKE '%$queryterm%'".
                            " OR note.title LIKE '%$queryterm%'".
                            ' ) ';
            }
        }
    }
    for ($x = 0; $x <= 20; $x++) {
        if (isset($_REQUEST["s_deps_id_" . $x])) {
            $qstr.='&s_deps_id_'.$x.'='.urlencode($_REQUEST["s_deps_id_" . $x]);
        }
    }
    for ($x = 0; $x <= 20; $x++) {
        if (isset($_REQUEST["s_assigns_id_" . $x])) {
            $qstr.='&s_assigns_id_'.$x.'='.urlencode($_REQUEST["s_assigns_id_" . $x]);
        }
    }
    if($_REQUEST['dept'] && ($thisuser->isadmin() || in_array($_REQUEST['dept'],$thisuser->getDepts()))) {
        $qwhere.=' AND ticket.dept_id='.db_input($_REQUEST['dept']);
        $qstr.='&dept='.urlencode($_REQUEST['dept']);
    }

    $startTime  =($_REQUEST['startDate'] && (strlen($_REQUEST['startDate'])>=8))?strtotime($_REQUEST['startDate']):0;
    $endTime    =($_REQUEST['endDate'] && (strlen($_REQUEST['endDate'])>=8))?strtotime($_REQUEST['endDate']):0;
    if( ($startTime && $startTime>time()) or ($startTime>$endTime && $endTime>0)){
        $errors['err']='Entered date span is invalid. Selection ignored.';
        $startTime=$endTime=0;
    }else{
        if($startTime){
            $qwhere.=' AND ticket.created>=FROM_UNIXTIME('.$startTime.')';
            $qstr.='&startDate='.urlencode($_REQUEST['startDate']);

        }
        if($endTime){
            $qwhere.=' AND ticket.created<=FROM_UNIXTIME('.$endTime.')';
            $qstr.='&endDate='.urlencode($_REQUEST['endDate']);
        }
}

endif;


$sortOptions=array('date'=>'ticket.created','ID'=>'ticketID','pri'=>'priority_urgency','dept'=>'dept_name', 'assign'=>'assignfname', 'name'=>'name');
$orderWays=array('DESC'=>'DESC','ASC'=>'ASC');

if(!empty($_REQUEST['sort'])) {
    $order_by =$sortOptions[$_REQUEST['sort']] ?? null;
}
if(!empty($_REQUEST['order'])) {
    $order=$orderWays[$_REQUEST['order']] ?? null;
}
if(!empty($_GET['limit'])){
    $qstr.='&limit='.urlencode($_GET['limit']);
}
$order_by = $order_by ?? null;
$order = $order ?? null;
if(!$order_by && $showanswered) {
    $order_by='ticket.lastresponse DESC, ticket.created';
}elseif(!$order_by && !strcasecmp($status,'closed')){
    $order_by='ticket.closed DESC, ticket.created';
}


$order_by =$order_by?$order_by:'priority_urgency,effective_date DESC ,ticket.created';
$order=$order?$order:'DESC';
$pagelimit=($_GET['limit'] ?? null)?intval($_GET['limit']):$thisuser->getPageLimit();
$pagelimit=$pagelimit?$pagelimit:PAGE_LIMIT;
$page=(isset($_GET['p']) && is_numeric($_GET['p']))?$_GET['p']:1;


$qselect = 'SELECT DISTINCT ticket.ticket_id,lock_id,ticketID,ticket.dept_id,ticket.staff_id,subject,name,email,dept_name '.
           ',ticket.status,ticket.source,isoverdue,isanswered,ticket.created,pri.* ,count(attach.attach_id) as attachments '.
            ',ticket.andstaffs_id ';

$fromtable = $isArchived ? TICKET_TABLE_ARCHIVED : TICKET_TABLE;

$qfrom=' FROM '.$fromtable.' ticket '.
       ' LEFT JOIN '.DEPT_TABLE.' dept ON ticket.dept_id=dept.dept_id ';

if($search && $deep_search) {
    $qfrom.=' LEFT JOIN '.TICKET_MESSAGE_TABLE.' message ON (ticket.ticket_id=message.ticket_id )';
    $qfrom.=' LEFT JOIN '.TICKET_RESPONSE_TABLE.' response ON (ticket.ticket_id=response.ticket_id )';
    $qfrom.=' LEFT JOIN '.TICKET_NOTE_TABLE.' note ON (ticket.ticket_id=note.ticket_id )';
}

$qgroup=' GROUP BY ticket.ticket_id';
$total=db_count("SELECT count(DISTINCT ticket.ticket_id) $qfrom $qwhere");
$pageNav=new Pagenate($total,$page,$pagelimit);
$pageNav->setURL('tickets.php',$qstr.'&sort='.urlencode($_REQUEST['sort'] ?? '').'&order='.urlencode($_REQUEST['order'] ?? ''));

$qselect.=' ,count(attach.attach_id) as attachments, IF(ticket.reopened is NULL,ticket.created,ticket.reopened) as effective_date';
$qfrom.=' LEFT JOIN '.TICKET_PRIORITY_TABLE.' pri ON ticket.priority_id=pri.priority_id '.
        ' LEFT JOIN '.TICKET_LOCK_TABLE.' tlock ON ticket.ticket_id=tlock.ticket_id AND tlock.expire>NOW() '.
        ' LEFT JOIN '.TICKET_ATTACHMENT_TABLE.' attach ON  ticket.ticket_id=attach.ticket_id ';

$qselect.=' ,lastmessdept.lastmessdepid, lastmessdept.lastmessstaffid, lastmessdept.lastmesstaffname';
$qfrom.=' LEFT JOIN (SELECT tickets.ticket_id AS latmessticketid, staffs.dept_id AS lastmessdepid, staffs.staff_id AS lastmessstaffid, tickets.staff_name AS lastmesstaffname FROM '.TICKET_RESPONSE_TABLE.' as tickets
			INNER JOIN '.STAFF_TABLE.' AS staffs ON staffs.staff_id = tickets.staff_id
			ORDER BY tickets.created DESC) lastmessdept ON lastmessdept.latmessticketid = ticket.ticket_id ';

$qselect.=' ,lastnotestaff.lastnotestaffid';
$qfrom.=" LEFT JOIN (SELECT notes.ticket_id AS latnoteticketid, staffs.staff_id AS lastnotestaffid, notes.note FROM ".TICKET_NOTE_TABLE." as notes
			INNER JOIN ".STAFF_TABLE." AS staffs ON staffs.staff_id = notes.staff_id
            WHERE notes.note NOT LIKE '%Ticket closed%'
            AND notes.note NOT LIKE '%closed the ticket%'
			AND notes.note NOT LIKE '%Ticket flagged%'
            AND notes.note NOT LIKE '%Ticket priority%'
            AND notes.note NOT LIKE '%Ticket assigned%'
            AND notes.note NOT LIKE '%Ticket released%'
            AND notes.note NOT LIKE '%Ticket created%'
            AND notes.note NOT LIKE '%Назначен ответственный%'
			ORDER BY notes.created DESC) lastnotestaff ON lastnotestaff.latnoteticketid = ticket.ticket_id  ";

$qselect.=' ,myth_staff.assignfname, myth_staff.assignlname';
$qfrom.=' LEFT JOIN (SELECT staff_id, firstname as assignfname, lastname AS assignlname FROM th_staff) myth_staff ON myth_staff.staff_id = ticket.staff_id ';

$query="$qselect $qfrom $qwhere $qgroup ORDER BY $order_by $order LIMIT ".$pageNav->getStart().",".$pageNav->getLimit();
$tickets_res = db_query($query);
$showing=db_num_rows($tickets_res)?$pageNav->showing():"";

$ticketStaffMap = array();
$allStaffIds = array();

if ($tickets_res && db_num_rows($tickets_res)) {
    db_data_seek($tickets_res, 0);
    while ($tmprow = db_fetch_array($tickets_res)) {
        if (!empty($tmprow['andstaffs_id'])) {
            $staffIds = str_replace('*', '', $tmprow['andstaffs_id']);
            $staffIdsArray = explode(',', $staffIds);
            foreach ($staffIdsArray as $sid) {
                $sid = trim($sid);
                if ($sid && is_numeric($sid)) {
                    $allStaffIds[$sid] = $sid;
                }
            }
        }
    }

    if (!empty($allStaffIds)) {
        $staffSql = 'SELECT staff_id, CONCAT_WS(" ", firstname, lastname) as name
                     FROM '.STAFF_TABLE.'
                     WHERE isactive=1 AND onvacation=0
                     AND staff_id IN (' . implode(',', $allStaffIds) . ')
                     ORDER BY firstname, lastname';

        $staffData = array();
        $staffRes = db_query($staffSql);
        if ($staffRes) {
            while ($staffRow = db_fetch_array($staffRes)) {
                $staffData[$staffRow['staff_id']] = $staffRow['name'];
            }
        }

        db_data_seek($tickets_res, 0);
        while ($tmprow = db_fetch_array($tickets_res)) {
            if (!empty($tmprow['andstaffs_id'])) {
                $staffIds = str_replace('*', '', $tmprow['andstaffs_id']);
                $staffIdsArray = explode(',', $staffIds);
                $staffNames = array();
                foreach ($staffIdsArray as $sid) {
                    $sid = trim($sid);
                    if ($sid && isset($staffData[$sid])) {
                        $staffNames[] = array(
                            'id' => $sid,
                            'name' => $staffData[$sid]
                        );
                    }
                }
                $ticketStaffMap[$tmprow['ticket_id']] = $staffNames;
            }
        }
    }

    db_data_seek($tickets_res, 0);
}

if(!isset($results_type) || !$results_type) {
    $statusLabels = array('open'=>'Открытые заявки','closed'=>'Закрытые заявки');
    $results_type=($search)?'Результаты поиска':($statusLabels[$status] ?? ucfirst($status).' заявки');
}
$negorder=$order=='DESC'?'ASC':'DESC';

$canDelete=$canClose=false;
$canDelete=$thisuser->canDeleteTickets();
$canClose=$thisuser->canCloseTickets();
$basic_display=!isset($_REQUEST['advance_search'])?true:false;
?>
<div>
    <?if(!empty($errors['err'])) {?>
        <div class="alert-danger" id="errormessage"><?=Format::htmlchars($errors['err'])?></div>
    <?}elseif($msg) {?>
        <div class="alert-success" id="infomessage"><?=Format::htmlchars($msg)?></div>
    <?}elseif($warn) {?>
        <div class="alert-warning" id="warnmessage"><?=Format::htmlchars($warn)?></div>
     <?}?>
</div>
<div class="flex justify-between items-center mb-4">
    <h2 class="text-lg font-heading font-semibold text-gray-900 flex items-center gap-2">
        <i data-lucide="ticket" class="w-5 h-5"></i> <?=isset($results_type) ? $results_type : ($isArchived ? 'Архив заявок' : ($status=='closed' ? 'Закрытые заявки' : 'Открытые заявки'))?>
    </h2>
    <?php if($thisuser->canCreateTickets()) { ?>
    <a href="tickets.php?a=open" class="btn-primary">
        <i data-lucide="plus" class="w-4 h-4"></i> Новая заявка
    </a>
    <?php } ?>
</div>
<!-- SEARCH FORM START -->
<div id='basic' style="display:<?=$basic_display?'block':'none'?>">
    <div class="card mb-4">
    <div class="card-body">
    <form action="tickets.php" method="get" class="flex flex-wrap items-center gap-2">
    <input type="hidden" name="a" value="search">
	<? if ($isArchived) echo '<input type="hidden" name="status" value="archived">' ?>
    <span class="font-body text-gray-700">Поиск:</span> <input type="text" id="query" name="query" class="input inline-block w-auto" size=30 value="<?=Format::htmlchars($_REQUEST['query'] ?? '')?>">
    <input type="hidden" name="secret" value="<?=$_SESSION["secret"]?>" /><input type="submit" name="basic_search" class="btn-primary btn-sm" value="Найти">
     &nbsp;[<a href="#" onClick="showHide('basic','advance'); return false;" class="text-indigo-600 hover:text-indigo-800">Расширенный</a> ]
    </form>
    </div>
    </div>
</div>
<div id='advance' style="display:<?=$basic_display?'none':'block'?>">
 <div class="card mb-4">
 <div class="card-body">
 <form action="tickets.php" method="get">
 <input type="hidden" name="a" value="поиск">
 <? if ($isArchived) echo '<input type="hidden" name="status" value="archived">' ?>

    <!-- Строка 1: Поиск + Статус -->
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-4">
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Поиск</label>
            <input type="text" id="query" name="query" class="input w-full" placeholder="Введите запрос..." value="<?=Format::htmlchars($_REQUEST['query'] ?? '')?>">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Статус</label>
            <select name="status" class="select w-full">
                <option value="any" selected>Любой статус</option>
                <option value="open" <?=!strcasecmp($_REQUEST['status'] ?? '','Open')?'selected':''?>>Открыт</option>
                <option value="overdue" <?=!strcasecmp($_REQUEST['status'] ?? '','overdue')?'selected':''?>>Просрочен</option>
                <option value="closed" <?=!strcasecmp($_REQUEST['status'] ?? '','Closed')?'selected':''?>>Закрыт</option>
            </select>
        </div>
    </div>

    <!-- Строка 2: Даты -->
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-4">
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Дата от</label>
            <div class="flex items-center gap-2">
                <input id="sd" name="startDate" class="input w-full datepicker" placeholder="дд.мм.гггг" value="<?=Format::htmlchars($_REQUEST['startDate'] ?? '')?>" autocomplete="off">
                <a href="#" onclick="document.getElementById('sd')._flatpickr.open(); return false;" class="text-indigo-600 hover:text-indigo-800"><i data-lucide="calendar" class="w-4 h-4"></i></a>
            </div>
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Дата до</label>
            <div class="flex items-center gap-2">
                <input id="ed" name="endDate" class="input w-full datepicker" placeholder="дд.мм.гггг" value="<?=Format::htmlchars($_REQUEST['endDate'] ?? '')?>" autocomplete="off">
                <a href="#" onclick="document.getElementById('ed')._flatpickr.open(); return false;" class="text-indigo-600 hover:text-indigo-800"><i data-lucide="calendar" class="w-4 h-4"></i></a>
            </div>
        </div>
    </div>

    <!-- Строка 3: Тип поиска + Сортировка + Порядок -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-4">
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Тип поиска</label>
            <select name="stype" class="select w-full">
                <option value="LIKE" <?=(empty($_REQUEST['stype']) || ($_REQUEST['stype'] ?? '') == 'LIKE') ?'selected':''?>>Выборочно (%)</option>
                <option value="FT" <?=($_REQUEST['stype'] ?? '') == 'FT'?'selected':''?>>Полнотекстовый</option>
            </select>
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Сортировка</label>
            <?php $sort=($_GET['sort'] ?? null)?$_GET['sort']:'date'; ?>
            <select name="sort" class="select w-full">
                <option value="date" <?=$sort=='date'?'selected':''?>>По дате</option>
                <option value="ID" <?=$sort=='ID'?'selected':''?>>По № заявки</option>
                <option value="pri" <?=$sort=='pri'?'selected':''?>>По приоритету</option>
                <option value="dept" <?=$sort=='dept'?'selected':''?>>По отделу</option>
                <option value="assign" <?=$sort=='assign'?'selected':''?>>По исполнителю</option>
                <option value="name" <?=$sort=='name'?'selected':''?>>По автору</option>
            </select>
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Порядок</label>
            <select name="order" class="select w-full">
                <option value="DESC" <?=($_REQUEST['order'] ?? '')=='DESC'?'selected':''?>>Убывание</option>
                <option value="ASC" <?=($_REQUEST['order'] ?? '')=='ASC'?'selected':''?>>Возрастание</option>
            </select>
        </div>
    </div>

    <!-- Строка 4: Отделы + Исполнители (выпадающие списки) -->
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-4">
        <?php
        $detailsopenned = false;
        for ($x = 0; $x <= 20; $x++)
            if (isset($_REQUEST["s_deps_id_" . $x])) { $detailsopenned = true; break; }
        ?>
        <div style="position:relative;">
            <button type="button" onclick="toggleDropdown('deptFilter','staffFilter')" class="w-full flex items-center justify-between px-3 py-2 border border-gray-300 rounded-lg bg-white hover:bg-gray-50 transition-colors text-sm font-medium text-gray-700">
                <span class="flex items-center gap-2"><i data-lucide="building" class="w-4 h-4"></i> Отделы (ИЛИ)</span>
                <i data-lucide="chevron-down" class="w-3.5 h-3.5 text-gray-400"></i>
            </button>
            <div id="deptFilter" style="display:<?=$detailsopenned?'block':'none'?>;position:absolute;top:100%;left:0;right:0;z-index:50;background:#fff;border:1px solid #e5e7eb;border-radius:0 0 8px 8px;box-shadow:0 10px 25px -5px rgba(0,0,0,.1);max-height:220px;overflow-y:auto;padding:8px 12px;">
                <?php
                $depts_q = db_query('SELECT dept_id,dept_name FROM '.DEPT_TABLE.' WHERE dept_id IN ('.implode(',',$thisuser->getDepts()).')');
                $i = 0;
                while (list($deptId,$deptName) = db_fetch_row($depts_q)){
                    $selected = '';
                    for ($x = 0; $x <= 100; $x++) {
                        if (isset($_REQUEST["s_deps_id_" . $x]) && $_REQUEST["s_deps_id_" . $x] == $deptId)
                            $selected = 'checked';
                    }
                ?>
                <label style="display:flex;align-items:center;gap:8px;padding:4px 0;font-size:13px;color:#374151;cursor:pointer;">
                    <input type="checkbox" name="s_deps_id_<?=$i++?>" value="<?=$deptId?>" <?=$selected?>>
                    <?=Format::htmlchars($deptName)?>
                </label>
                <?php } ?>
            </div>
        </div>
        <?php
        $detailsopenned2 = false;
        for ($x = 0; $x <= 20; $x++)
            if (isset($_REQUEST["s_assigns_id_" . $x])) { $detailsopenned2 = true; break; }
        ?>
        <div style="position:relative;">
            <button type="button" onclick="toggleDropdown('staffFilter','deptFilter')" class="w-full flex items-center justify-between px-3 py-2 border border-gray-300 rounded-lg bg-white hover:bg-gray-50 transition-colors text-sm font-medium text-gray-700">
                <span class="flex items-center gap-2"><i data-lucide="users" class="w-4 h-4"></i> Исполнители (ИЛИ)</span>
                <i data-lucide="chevron-down" class="w-3.5 h-3.5 text-gray-400"></i>
            </button>
            <div id="staffFilter" style="display:<?=$detailsopenned2?'block':'none'?>;position:absolute;top:100%;left:0;right:0;z-index:50;background:#fff;border:1px solid #e5e7eb;border-radius:0 0 8px 8px;box-shadow:0 10px 25px -5px rgba(0,0,0,.1);max-height:220px;overflow-y:auto;padding:8px 12px;">
                <?php
                $staffs_q = db_query('SELECT staff_id, CONCAT_WS(" ",firstname,lastname) as name FROM '.STAFF_TABLE.' WHERE isactive=1 AND onvacation=0 ORDER BY firstname,lastname');
                $i = 0;
                while (list($sId,$sName) = db_fetch_row($staffs_q)) {
                    $checked = '';
                    for ($x = 0; $x <= 100; $x++) {
                        if (isset($_REQUEST["s_assigns_id_" . $x]) && $_REQUEST["s_assigns_id_" . $x] == $sId)
                            $checked = 'checked';
                    }
                ?>
                <label style="display:flex;align-items:center;gap:8px;padding:4px 0;font-size:13px;color:#374151;cursor:pointer;">
                    <input type="checkbox" name="s_assigns_id_<?=$i++?>" value="<?=$sId?>" <?=$checked?>>
                    <?=Format::htmlchars($sName)?>
                </label>
                <?php } ?>
            </div>
        </div>
    </div>
    <script>
    function toggleDropdown(showId, hideId) {
        var el = document.getElementById(showId);
        var other = document.getElementById(hideId);
        if (other) other.style.display = 'none';
        el.style.display = el.style.display === 'none' ? 'block' : 'none';
    }
    document.addEventListener('click', function(e) {
        var df = document.getElementById('deptFilter');
        var sf = document.getElementById('staffFilter');
        if (df && !df.parentElement.contains(e.target)) df.style.display = 'none';
        if (sf && !sf.parentElement.contains(e.target)) sf.style.display = 'none';
    });
    </script>

    <!-- Строка 5: Кол-во + Кнопки -->
    <div class="flex flex-wrap items-center gap-3">
        <div class="flex items-center gap-2">
            <label class="text-sm font-medium text-gray-700">На страницу:</label>
            <select name="limit" class="select inline-block w-auto">
            <?php
            $sel = ($_REQUEST['limit'] ?? null) ? $_REQUEST['limit'] : 25;
            for ($x = 5; $x <= 25; $x += 5) { ?>
                <option value="<?=$x?>" <?=($sel==$x)?'selected':''?>><?=$x?></option>
            <?php }
            for ($x = 50; $x <= 200; $x += 25) { ?>
                <option value="<?=$x?>" <?=($sel==$x)?'selected':''?>><?=$x?></option>
            <?php }
            for ($x = 300; $x <= 1000; $x += 100) { ?>
                <option value="<?=$x?>" <?=($sel==$x)?'selected':''?>><?=$x?></option>
            <?php } ?>
            </select>
        </div>
        <input type="hidden" name="secret" value="<?=$_SESSION["secret"]?>" />
        <input type="submit" name="advance_search" class="btn-primary btn-sm" value="Найти">
        <a href="#" onClick="showHide('advance','basic'); return false;" class="text-sm text-indigo-600 hover:text-indigo-800">Обычный поиск</a>
    </div>
 </form>
 </div>
 </div>
</div>
<script type="text/javascript">

    var options = {
        script:"dispatch.php?api=tickets&f=search&limit=10&",
        varname:"input",
        shownoresults:false,
        maxresults:10,
        callback: function (obj) { document.getElementById('query').value = obj.id; document.forms[0].submit();}
    };
    var autosug = new bsn.AutoSuggest('query', options);
</script>
<!-- SEARCH FORM END -->
<div class="ticket-legend mb-4">
    <div class="alert-success flex items-center gap-2 mb-2">
        <i data-lucide="star" class="w-4 h-4"></i> <strong class="font-heading">Наиболее приоритетная задача</strong>
    </div>
    <div class="legend-icons flex flex-wrap gap-4">
        <div class="legend-icon-item flex items-center gap-2">
            <i data-lucide="mail" class="w-4 h-4 legend-icon-red text-red-500"></i>
            <span class="font-body text-sm">Нет ответа на заявку (необходимо ответить заявителю!)</span>
        </div>
        <div class="legend-icon-item flex items-center gap-2">
            <i data-lucide="mail" class="w-4 h-4 legend-icon-yellow text-amber-500"></i>
            <span class="font-body text-sm">Имеется внутреннее сообщение не от Вас</span>
        </div>
    </div>
</div>
 <div class="flex items-center justify-between w-full mb-2 px-1">
    <div class="font-body text-gray-700">&nbsp;<b><?=$showing?>&nbsp;&nbsp;&nbsp;<?=$results_type?></b></div>
    <div class="whitespace-nowrap pr-5">
        <a href="" class="text-indigo-600 hover:text-indigo-800"><i data-lucide="refresh-cw" class="w-4 h-4 inline-block" title="Обновить"></i></a>
    </div>
 </div>
    <form action="tickets.php" method="POST" name='tickets' onSubmit="return checkbox_checker(this,1,0);">
    <?php echo Misc::csrfField(); ?>
    <input type="hidden" name="a" value="mass_process" >
    <input type="hidden" name="status" value="<?=$status?>" >
    <input type="hidden" name="secret" value="<?=$_SESSION["secret"]?>" />
    <div class="table-wrapper overflow-x-auto">
    <table class="table-modern w-full">
        <thead>
        <tr>
            <th class="table-th w-[5px]">&nbsp;</th>
            <?if($canDelete || $canClose) {?>
	        <th class="table-th w-[8px]">&nbsp;</th>
            <?}?>
	        <th class="table-th w-[70px]">
                <a href="tickets.php?sort=ID&order=<?=$negorder?><?=$qstr?>" title="Sort By Ticket ID <?=$negorder?>" class="text-indigo-600 hover:text-indigo-800">Заявка</a></th>
	        <th class="table-th w-[80px]">
                <a href="tickets.php?sort=date&order=<?=$negorder?><?=$qstr?>" title="Sort By Date <?=$negorder?>" class="text-indigo-600 hover:text-indigo-800">Дата</a></th>
	        <th class="table-th w-[280px]">Тема</th>
	        <th class="table-th w-[120px]">
                <a href="tickets.php?sort=dept&order=<?=$negorder?><?=$qstr?>" title="Sort By Category <?=$negorder?>" class="text-indigo-600 hover:text-indigo-800">Отдел</a></th>
			<th class="table-th w-[160px]">
                <a href="tickets.php?sort=assign&order=<?=$negorder?><?=$qstr?>" title="Sort By AssignName <?=$negorder?>" class="text-indigo-600 hover:text-indigo-800">Исполнители</a></th>
			<th class="table-th w-[15px]">
                <i data-lucide="mail" class="w-4 h-4 inline-block" id="email_icon" title="Новое сообщение"></i></th>
	        <th class="table-th w-[60px]">
                <a href="tickets.php?sort=pri&order=<?=$negorder?><?=$qstr?>" title="Sort By Priority <?=$negorder?>" class="text-indigo-600 hover:text-indigo-800">Приор.</a></th>
            <th class="table-th w-[160px]">
				<a href="tickets.php?sort=name&order=<?=$negorder?><?=$qstr?>" title="Sort By Name <?=$negorder?>" class="text-indigo-600 hover:text-indigo-800">От</a></th>
        </tr>
        </thead>
        <tbody>
        <?
        $total=0;
        require_once(INCLUDE_DIR.'class.priorityuser.php');
        $priorityEmails = PriorityUser::getActivePriorityEmails();

        if($tickets_res && ($num=db_num_rows($tickets_res))):
            while ($row = db_fetch_array($tickets_res)) {
                $tag=$row['staff_id']?'assigned':'openticket';
                $flag=null;
                if($row['lock_id']){
                    $flag='locked';
					$flagtitle='закрытый';}
                elseif($row['staff_id']){
                    $flag='assigned';
					$flagtitle='назначенный';}
                elseif($row['isoverdue']){
                    $flag='overdue';
					$flagtitle='просроченный';}

                $tid=$row['ticketID'];
                $subject = Format::truncate($row['subject'],200);
                if(!strcasecmp($row['status'],'open') && !$row['isanswered'] && !$row['lock_id']) {
                    $tid=sprintf('<b>%s</b>',$tid);
                }

				$newmessageiconcolor = "";
				$newmessageicontitle = "";

                if (!empty($row['lastnotestaffid']) && strcasecmp($row['lastnotestaffid'],$thisuser->getId())) {$newmessageiconcolor = "#B5AB29"; $newmessageicontitle = "Новое внутреннее сообщение";}
                if (empty($row['lastmesstaffname']) || strripos($row['lastmesstaffname'], $row['name']) !== false) {$newmessageiconcolor = "Red"; $newmessageicontitle = "Ответ от заявителя";}


                $thisuser_id = $thisuser->getId();

				$mecolor = $thisuser_id == $row['staff_id'] ? ' style="color: Red;" ' : "";

                $priorityColors = array(
                    'низкий' => '#5cb85c',
                    'обычный' => '#5bc0de',
                    'средний' => '#5bc0de',
                    'высокий' => '#f0ad4e',
                    'критичный' => '#d9534f',
                    'экстренный' => '#d9534f',
                    'low' => '#5cb85c',
                    'normal' => '#5bc0de',
                    'medium' => '#5bc0de',
                    'high' => '#f0ad4e',
                    'critical' => '#d9534f',
                    'emergency' => '#d9534f'
                );
                $priorityKey = mb_strtolower($row['priority_desc'], 'utf-8');
                $priorityColor = isset($priorityColors[$priorityKey]) ? $priorityColors[$priorityKey] : '#999';

                $vipHighlight = in_array(strtolower(trim($row['email'])), $priorityEmails);
                $staff_name = $row['assignfname'];
                $staff_name.= empty($row['assignlname']) ? '' : (" ".$row['assignlname']);
                $zpt = (empty($staff_name) || empty ($row['andstaffs_id'])) ? '' : ', ';
                $staff_name = empty($staff_name) ? '' : ("<a ".$mecolor.">".Ticket::GetShortFIO($staff_name)."</a>");

                if (!empty($ticketStaffMap[$row['ticket_id']])) {
                    $andStaffNames = array();
                    foreach ($ticketStaffMap[$row['ticket_id']] as $staffInfo) {
                        $staffName = Ticket::GetShortFIO($staffInfo['name']);
                        if ($staffInfo['id'] == $row['staff_id']) {
                            $andStaffNames[] = '<a style="color:Red;">' . $staffName . '</a>';
                        } else {
                            $andStaffNames[] = $staffName;
                        }
                    }
                    $staff_name .= $zpt . implode(', ', $andStaffNames);
                }

                ?>
            <tr id="<?=$row['ticket_id']?>" class="<?=$vipHighlight ? 'vip-ticket' : ''?> hover:bg-gray-50 transition-colors">
                <td class="table-td priority-flag" style="background-color: <?=$priorityColor?>;"></td>
                <?if($canDelete || $canClose) {?>
                <td class="table-td text-center nohover">
                    <input type="checkbox" name="tids[]" value="<?=$row['ticket_id']?>" onClick="highLight(this.value,this.checked);">
                </td>
                <?}?>
                <td class="table-td text-center whitespace-nowrap" title="<?=Format::htmlchars($row['email'])?>">
                  <?php
                    $sourceIcon = '';
                    switch(strtolower($row['source'])) {
                        case 'web': $sourceIcon = '<i data-lucide="globe" class="w-4 h-4 inline-block"></i>'; break;
                        case 'email': $sourceIcon = '<i data-lucide="mail" class="w-4 h-4 inline-block"></i>'; break;
                        case 'phone': $sourceIcon = '<i data-lucide="phone" class="w-4 h-4 inline-block"></i>'; break;
                        default: $sourceIcon = '<i data-lucide="globe" class="w-4 h-4 inline-block"></i>'; break;
                    }
                  ?>
                  <a title="<?=Format::htmlchars($row['source'])?> Заявка: <?=Format::htmlchars($row['email'])?>"
                    href="tickets.php?id=<?=$row['ticket_id']?>" class="text-indigo-600 hover:text-indigo-800"><?=$sourceIcon?> <?=$tid?></a></td>
                <td class="table-td text-center whitespace-nowrap"><?=Format::db_datetime($row['created'])?></td>
                <td class="table-td"><a <?if($flag) { ?> class="Icon <?=$flag?>Ticket" title="<?=ucfirst($flagtitle)?> заявка" <?}?>
                    href="tickets.php?id=<?=$row['ticket_id']?>"><?=Format::htmlchars($subject)?></a>
                    &nbsp;<?=$row['attachments']?"<span class='Icon file'>&nbsp;</span>":''?></td>
                <td class="table-td text-center"><?=Format::htmlchars(Format::truncate($row['dept_name'],80))?></td>
				<td class="table-td text-center"><?=$staff_name?>&nbsp;</td>
				<td class="table-td text-center whitespace-nowrap"><? if(!empty($newmessageiconcolor)) echo @'<i data-lucide="mail" class="w-4 h-4 inline-block" id="email_icon2" style="color: '.$newmessageiconcolor.';"></i>'; ?></td>
                <td class="table-td text-center nohover"><?=Format::htmlchars($row['priority_desc'])?></td>
                <td class="table-td text-center"><?=Format::htmlchars(Format::truncate($row['name'],80,strpos($row['name'],'@')))?>&nbsp;</td>
            </tr>
            <?
            } //end of while.
        else: ?>
            <tr><td colspan=8 class="table-td"><b class="text-gray-500">Запрос вернул пустой результат.</b></td></tr>
        <?
        endif; ?>
        </tbody>
    </table>
    </div>
    <?
    if($num>0){
    ?>
        <div class="pl-5 py-3 flex flex-wrap items-center gap-2 font-body text-sm">
            <?if($canDelete || $canClose) { ?>
            <span class="text-gray-700">Выбрать:</span>
                <a href="#" onclick="return select_all(document.forms['tickets'],true)" class="text-indigo-600 hover:text-indigo-800">Все</a>&nbsp;
                <a href="#" onclick="return reset_all(document.forms['tickets'])" class="text-indigo-600 hover:text-indigo-800">Ничего</a>&nbsp;
                <a href="#" onclick="return toogle_all(document.forms['tickets'],true)" class="text-indigo-600 hover:text-indigo-800">Обратить</a>&nbsp;
            <?}?>
            <span class="text-gray-700">страница:</span><?=$pageNav->getPageLinks()?>
        </div>
        <? if($canClose or $canDelete) { ?>
        <div class="text-center mt-3 flex flex-wrap justify-center gap-2">
            <?
            $status=$_REQUEST['status']?$_REQUEST['status']:$status;

            switch (strtolower($status)) {
                case 'closed': ?>
                    <input class="btn-primary btn-sm" type="submit" name="reopen" value="Открыть"
                        onClick=' return confirm("Вы уверены что хотите открыть выбранные заявки?");'>
                    <?
                    break;
                case 'open':
                case 'answered':
                case 'assigned':
                    ?>
                    <input class="btn-warning btn-sm" type="submit" name="overdue" value="Просрочен"
                        onClick=' return confirm("Вы уверены что хотите пометить выбранные заявки как просроченные?");'>
                    <input class="btn-primary btn-sm" type="submit" name="close" value="Закрыть"
                        onClick=' return confirm("Вы уверены что хотите закрыть выбранные заявки?");'>
                    <?
                    break;
                default:
                    ?>
                    <input class="btn-primary btn-sm" type="submit" name="close" value="Закрыть"
                        onClick=' return confirm("Вы уверены что хотите закрыть выбранные заявки?");'>
                    <input class="btn-secondary btn-sm" type="submit" name="reopen" value="Открыть"
                        onClick=' return confirm("Вы уверены что хотите открыть выбранные заявки?");'>
            <?
            }
            if($canDelete) {?>
                <input class="btn-danger btn-sm" type="submit" name="delete" value="Удалить"
                    onClick=' return confirm("Вы уверены что хотите УДАЛИТЬ выбранные заявки?");'>
            <?}?>
        </div>
        <? }
    } ?>
    </form>
</div>

<?
