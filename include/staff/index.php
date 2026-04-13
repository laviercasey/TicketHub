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
        $results_type='Overdue Tickets';
        break;
    case 'assigned':
        $status='open';
        $staffId=$thisuser->getId();
        break;
    case 'answered':
        $status='open';
        $showanswered=true;
        $results_type='Answered Tickets';
        break;
	case 'archived':
        $isArchived = true;
        break;
    default:
        if(!$search)
            $status='open';
}


if($stats) {
    if(!$stats['open'] && (!$status || $status=='open')){
        if(!$cfg->showAnsweredTickets() && $stats['answered']) {
             $status='open';
             $showanswered=true;
             $results_type='Answered Tickets';
        }elseif(!$stats['answered']) {
            $status='closed';
            $results_type='Closed Tickets';
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
    $results_type='Assigned Tickets';
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

if(!$results_type) {
    $results_type=($search)?'Search Results':ucfirst($status).' Tickets';
}
$negorder=$order=='DESC'?'ASC':'DESC';

$canDelete=$canClose=false;
$canDelete=$thisuser->canDeleteTickets();
$canClose=$thisuser->canCloseTickets();
$basic_display=!isset($_REQUEST['advance_search'])?true:false;

?>

<!-- Alerts / Messages -->
<div class="mb-4">
    <?php if(!empty($errors['err'])) { ?>
        <div class="alert-danger" role="alert">
            <div class="flex items-center gap-2">
                <i data-lucide="alert-circle" class="w-4 h-4 flex-shrink-0"></i>
                <span><?=$errors['err']?></span>
            </div>
        </div>
    <?php } elseif($msg) { ?>
        <div class="alert-success" role="alert">
            <div class="flex items-center gap-2">
                <i data-lucide="check-circle" class="w-4 h-4 flex-shrink-0"></i>
                <span><?=$msg?></span>
            </div>
        </div>
    <?php } elseif($warn) { ?>
        <div class="alert-warning" role="alert">
            <div class="flex items-center gap-2">
                <i data-lucide="alert-triangle" class="w-4 h-4 flex-shrink-0"></i>
                <span><?=$warn?></span>
            </div>
        </div>
    <?php } ?>
</div>

<!-- Stat Cards -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
    <!-- Open Tickets -->
    <a href="tickets.php" class="stat-card group hover:shadow-md transition-shadow">
        <div class="flex items-center justify-between">
            <div>
                <div class="stat-card-label font-body text-gray-500">Открыто</div>
                <div class="stat-card-value font-heading text-gray-900"><?=intval($stats['open'] ?? 0) + intval($stats['answered'] ?? 0)?></div>
            </div>
            <div class="stat-card-icon bg-indigo-100 text-indigo-600">
                <i data-lucide="inbox" class="w-5 h-5"></i>
            </div>
        </div>
    </a>
    <!-- Overdue Tickets -->
    <a href="tickets.php?status=overdue" class="stat-card group hover:shadow-md transition-shadow">
        <div class="flex items-center justify-between">
            <div>
                <div class="stat-card-label font-body text-gray-500">Просрочено</div>
                <div class="stat-card-value font-heading text-red-600"><?=intval($stats['overdue'] ?? 0)?></div>
            </div>
            <div class="stat-card-icon bg-red-100 text-red-500">
                <i data-lucide="clock" class="w-5 h-5"></i>
            </div>
        </div>
    </a>
    <!-- My Tickets -->
    <a href="tickets.php?status=assigned" class="stat-card group hover:shadow-md transition-shadow">
        <div class="flex items-center justify-between">
            <div>
                <div class="stat-card-label font-body text-gray-500">Мои Заявки</div>
                <div class="stat-card-value font-heading text-gray-900"><?=intval($stats['assigned'] ?? 0)?></div>
            </div>
            <div class="stat-card-icon bg-amber-100 text-amber-500">
                <i data-lucide="user-check" class="w-5 h-5"></i>
            </div>
        </div>
    </a>
    <!-- Closed Tickets -->
    <a href="tickets.php?status=closed" class="stat-card group hover:shadow-md transition-shadow">
        <div class="flex items-center justify-between">
            <div>
                <div class="stat-card-label font-body text-gray-500">Закрыто</div>
                <div class="stat-card-value font-heading text-gray-900"><?=intval($stats['closed'] ?? 0)?></div>
            </div>
            <div class="stat-card-icon bg-emerald-100 text-emerald-500">
                <i data-lucide="check-circle" class="w-5 h-5"></i>
            </div>
        </div>
    </a>
</div>

<!-- SEARCH FORM START -->
<div class="mb-6">
    <!-- Basic Search -->
    <div id="basic" style="display:<?=$basic_display?'block':'none'?>">
        <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
            <form action="tickets.php" method="get" class="flex flex-wrap items-center gap-3">
                <input type="hidden" name="a" value="search">
                <?php if ($isArchived) echo '<input type="hidden" name="status" value="archived">'; ?>
                <label class="text-sm font-medium text-gray-700 font-body">Поиск:</label>
                <input type="text" id="query" name="query"
                       class="rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm font-body text-gray-900 placeholder-gray-400 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 focus:outline-none transition-colors"
                       size="30" value="<?=Format::htmlchars($_REQUEST['query'])?>" placeholder="Введите запрос...">
                <input type="hidden" name="secret" value="<?=$_SESSION["secret"]?>" />
                <button type="submit" name="basic_search" class="btn-primary btn-sm">
                    <i data-lucide="search" class="w-4 h-4"></i>
                    Найти
                </button>
                <a href="#" onClick="showHide('basic','advance'); return false;" class="text-sm text-indigo-600 hover:text-indigo-700 font-medium transition-colors">
                    <i data-lucide="sliders-horizontal" class="w-3.5 h-3.5 inline-block"></i>
                    Расширенный
                </a>
            </form>
        </div>
    </div>

    <!-- Advanced Search -->
    <div id="advance" style="display:<?=$basic_display?'none':'block'?>">
        <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
            <form action="tickets.php" method="get" class="space-y-4">
                <input type="hidden" name="a" value="поиск">
                <?php if ($isArchived) echo '<input type="hidden" name="status" value="archived">'; ?>

                <!-- Row 1: Query + Status -->
                <div class="flex flex-wrap items-center gap-3">
                    <label class="text-sm font-medium text-gray-700 font-body">Поиск:</label>
                    <input type="text" id="query" name="query"
                           class="rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm font-body text-gray-900 placeholder-gray-400 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 focus:outline-none transition-colors"
                           value="<?=Format::htmlchars($_REQUEST['query'])?>" placeholder="Введите запрос...">
                    <label class="text-sm font-medium text-gray-700 font-body">Статус:</label>
                    <?php
                    ?>
                    <select name="status" class="rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm font-body text-gray-900 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 focus:outline-none transition-colors">
                        <option value="any" selected>любой статус</option>
                        <option value="open" <?=!strcasecmp($_REQUEST['status'],'Open')?'selected':''?>>Открыт</option>
                        <option value="overdue" <?=!strcasecmp($_REQUEST['status'],'overdue')?'selected':''?>>Просрочен</option>
                        <option value="closed" <?=!strcasecmp($_REQUEST['status'],'Closed')?'selected':''?>>Закрыт</option>
                    </select>
                </div>

                <!-- Row 2: Date Range -->
                <div class="flex flex-wrap items-center gap-3">
                    <label class="text-sm font-medium text-gray-700 font-body">Диапазон дат:</label>
                    <span class="text-sm text-gray-500">От</span>
                    <div class="relative">
                        <input id="sd" name="startDate"
                               class="datepicker rounded-lg border border-gray-300 bg-white pl-3 pr-8 py-2 text-sm font-body text-gray-900 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 focus:outline-none transition-colors"
                               value="<?=Format::htmlchars($_REQUEST['startDate'])?>" autocomplete="off">
                        <a href="#" onclick="document.getElementById('sd')._flatpickr.open(); return false;" class="absolute right-2.5 top-1/2 -translate-y-1/2 text-gray-400 hover:text-indigo-600">
                            <i data-lucide="calendar" class="w-4 h-4"></i>
                        </a>
                    </div>
                    <span class="text-sm text-gray-500">до</span>
                    <div class="relative">
                        <input id="ed" name="endDate"
                               class="datepicker rounded-lg border border-gray-300 bg-white pl-3 pr-8 py-2 text-sm font-body text-gray-900 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 focus:outline-none transition-colors"
                               value="<?=Format::htmlchars($_REQUEST['endDate'])?>" autocomplete="off">
                        <a href="#" onclick="document.getElementById('ed')._flatpickr.open(); return false;" class="absolute right-2.5 top-1/2 -translate-y-1/2 text-gray-400 hover:text-indigo-600">
                            <i data-lucide="calendar" class="w-4 h-4"></i>
                        </a>
                    </div>
                </div>

                <!-- Row 3: Type + Sort -->
                <div class="flex flex-wrap items-center gap-3">
                    <label class="text-sm font-medium text-gray-700 font-body">Тип:</label>
                    <select name="stype" class="rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm font-body text-gray-900 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 focus:outline-none transition-colors">
                        <option value="LIKE" <?=(!$_REQUEST['stype'] || $_REQUEST['stype'] == 'LIKE') ?'selected':''?>>Выборочно (%)</option>
                        <option value="FT"<?= $_REQUEST['stype'] == 'FT'?'selected':''?>>Весь текст</option>
                    </select>
                    <label class="text-sm font-medium text-gray-700 font-body">Сортировать:</label>
                    <?php
                     $sort=($_GET['sort'] ?? null)?$_GET['sort']:'date';
                    ?>
                    <select name="sort" class="rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm font-body text-gray-900 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 focus:outline-none transition-colors">
                        <option value="ID" <?= $sort== 'ID' ?'selected':''?>>По № заявки</option>
                        <option value="pri" <?= $sort == 'pri' ?'selected':''?>>По приоритету</option>
                        <option value="date" <?= $sort == 'date' ?'selected':''?>>По дате</option>
                        <option value="dept" <?= $sort == 'dept' ?'selected':''?>>По отделу</option>
                        <option value="dept" <?= $sort == 'assign' ?'selected':''?>>По ответственному</option>
                        <option value="dept" <?= $sort == 'name' ?'selected':''?>>По создавшему заявку</option>
                    </select>
                    <select name="order" class="rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm font-body text-gray-900 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 focus:outline-none transition-colors">
                        <option value="DESC"<?= $_REQUEST['order'] == 'DESC' ?'selected':''?>>в порядке убывания</option>
                        <option value="ASC"<?= $_REQUEST['order'] == 'ASC'?'selected':''?>>в порядке возрастания</option>
                    </select>
                </div>

                <!-- Row 4: Dept + Staff Filters -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <!-- Departments Filter -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 font-body mb-2">Отделы:</label>
                        <?php
                           $detailsopenned = false;
                           for ($x = 0; $x <= 20; $x++)
                               if (isset($_REQUEST["s_deps_id_" . $x])) {$detailsopenned = true; break;}
                        ?>
                        <div class="rounded-xl border border-gray-200 bg-gray-50 overflow-hidden" x-data="{ open: <?=$detailsopenned ? 'true' : 'false'?> }">
                            <button type="button" @click="open = !open"
                                    class="flex items-center justify-between w-full px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-100 transition-colors">
                                <span class="flex items-center gap-2">
                                    <i data-lucide="building-2" class="w-4 h-4 text-gray-400"></i>
                                    Фильтр отделов (ИЛИ)
                                </span>
                                <i data-lucide="chevron-down" class="w-4 h-4 text-gray-400 transition-transform" :class="open ? 'rotate-180' : ''"></i>
                            </button>
                            <div x-show="open" x-collapse class="px-4 py-3 border-t border-gray-200 bg-white space-y-2">
                                <?php
                                $depts= db_query('SELECT dept_id,dept_name FROM '.DEPT_TABLE. ' WHERE dept_id IN ('.implode(',',$thisuser->getDepts()).')');
                                $i = 0;
                                while (list($deptId,$deptName) = db_fetch_row($depts)){
                                    $selected = '';
                                    for ($x = 0; $x <= 100; $x++) {
                                        if (isset($_REQUEST["s_deps_id_" . $x]) && $_REQUEST["s_deps_id_" . $x] == $deptId)
                                            $selected = 'checked';
                                    }
                                    ?>
                                    <label class="flex items-center gap-2 text-sm text-gray-700 cursor-pointer hover:text-gray-900">
                                        <input type="checkbox" name="s_deps_id_<?=$i++?>" value="<?=$deptId?>" <?=$selected?>
                                               class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                                        <?=$deptName?>
                                    </label>
                                <?php
                                }?>
                            </div>
                        </div>
                    </div>

                    <!-- Staff Filter -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 font-body mb-2">Исполнители (задействованные):</label>
                        <?php
                        $detailsopenned2 = false;
                        for ($x = 0; $x <= 20; $x++)
                            if (isset($_REQUEST["s_assigns_id_" . $x])) {$detailsopenned2 = true; break;}
                        ?>
                        <div class="rounded-xl border border-gray-200 bg-gray-50 overflow-hidden" x-data="{ open: <?=$detailsopenned2 ? 'true' : 'false'?> }">
                            <button type="button" @click="open = !open"
                                    class="flex items-center justify-between w-full px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-100 transition-colors">
                                <span class="flex items-center gap-2">
                                    <i data-lucide="users" class="w-4 h-4 text-gray-400"></i>
                                    Фильтр исполнителей (ИЛИ)
                                </span>
                                <i data-lucide="chevron-down" class="w-4 h-4 text-gray-400 transition-transform" :class="open ? 'rotate-180' : ''"></i>
                            </button>
                            <div x-show="open" x-collapse class="px-4 py-3 border-t border-gray-200 bg-white space-y-2">
                                <?php
                                $sql=' SELECT staff_id, CONCAT_WS(" ",firstname,lastname) as name FROM '.STAFF_TABLE.
                                    ' WHERE isactive=1 AND onvacation=0 ';
                                $staffs= db_query($sql.' ORDER BY firstname,lastname ');
                                $i = 0;
                                while (list($staffId,$staffName) = db_fetch_row($staffs)) {
                                    $checked = '';
                                    for ($x = 0; $x <= 100; $x++) {
                                        if (isset($_REQUEST["s_assigns_id_" . $x]) && $_REQUEST["s_assigns_id_" . $x] == $staffId)
                                            $checked = ' checked ';
                                    }
                                    echo "<label class='flex items-center gap-2 text-sm text-gray-700 cursor-pointer hover:text-gray-900'><input type='checkbox' name='s_assigns_id_" . $i++ . "' value='" . $staffId . "' " . $checked . " class='rounded border-gray-300 text-indigo-600 focus:ring-indigo-500'>" . $staffName . "</label>";
                                }
                                ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Row 5: Per-page + Submit -->
                <div class="flex flex-wrap items-center gap-3 pt-2 border-t border-gray-100">
                    <label class="text-sm font-medium text-gray-700 font-body">Кол-во на страницу:</label>
                    <select name="limit" class="rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm font-body text-gray-900 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 focus:outline-none transition-colors">
                    <?php
                     $sel=$_REQUEST['limit']?$_REQUEST['limit']:25;
                     for ($x = 5; $x <= 25; $x += 5) {?>
                        <option value="<?=$x?>" <?=($sel==$x )?'selected':''?>><?=$x?></option>
                    <?php }
                    for ($x = 50; $x <= 200; $x += 25) {?>
                        <option value="<?=$x?>" <?=($sel==$x )?'selected':''?>><?=$x?></option>
                    <?php
                    }
                    for ($x = 300; $x <= 1000; $x += 100) {?>
                        <option value="<?=$x?>" <?=($sel==$x )?'selected':''?>><?=$x?></option>
                    <?php
                    }?>
                    </select>
                    <input type="hidden" name="secret" value="<?=$_SESSION["secret"]?>" />
                    <button type="submit" name="advance_search" class="btn-primary btn-sm">
                        <i data-lucide="search" class="w-4 h-4"></i>
                        Найти
                    </button>
                    <a href="#" onClick="showHide('advance','basic'); return false;" class="text-sm text-indigo-600 hover:text-indigo-700 font-medium transition-colors">
                        Обычный
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>
<!-- SEARCH FORM END -->

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

<!-- Legend -->
<div class="flex flex-wrap items-center gap-4 mb-4 px-1">
    <div class="flex items-center gap-2 rounded-lg bg-emerald-50 border border-emerald-200 px-3 py-1.5">
        <i data-lucide="star" class="w-4 h-4 text-emerald-600"></i>
        <span class="text-sm font-medium text-emerald-700 font-body">Наиболее приоритетная задача</span>
    </div>
    <div class="flex items-center gap-2 text-sm text-gray-600 font-body">
        <i data-lucide="mail" class="w-4 h-4 text-red-500"></i>
        <span>Нет ответа на заявку (необходимо ответить заявителю!)</span>
    </div>
    <div class="flex items-center gap-2 text-sm text-gray-600 font-body">
        <i data-lucide="mail" class="w-4 h-4 text-amber-500"></i>
        <span>Имеется внутреннее сообщение не от Вас</span>
    </div>
</div>

<!-- Table Header Bar -->
<div class="flex items-center justify-between mb-3 px-1">
    <div class="text-sm font-medium text-gray-700 font-body">
        <?php if($showing) { ?>
            <span><?=$showing?></span>
            <span class="mx-2 text-gray-300">|</span>
        <?php } ?>
        <span class="font-heading font-semibold text-gray-900"><?=$results_type?></span>
    </div>
    <a href="" class="inline-flex items-center gap-1.5 text-sm text-gray-500 hover:text-indigo-600 transition-colors" title="Обновить">
        <i data-lucide="refresh-cw" class="w-4 h-4"></i>
        <span class="hidden sm:inline">Обновить</span>
    </a>
</div>

<!-- Ticket Table -->
<form action="tickets.php" method="POST" name="tickets" onSubmit="return checkbox_checker(this,1,0);">
    <?php echo Misc::csrfField(); ?>
    <input type="hidden" name="a" value="mass_process">
    <input type="hidden" name="status" value="<?=$statusss?>">
    <input type="hidden" name="secret" value="<?=$_SESSION["secret"]?>" />

    <div class="table-wrapper rounded-xl border border-gray-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="table-modern w-full">
                <thead>
                    <tr class="bg-gray-50">
                        <th class="table-th w-[5px] px-1">&nbsp;</th>
                        <?php if($canDelete || $canClose) { ?>
                        <th class="table-th w-8 px-2">&nbsp;</th>
                        <?php } ?>
                        <th class="table-th w-[70px]">
                            <a href="tickets.php?sort=ID&order=<?=$negorder?><?=$qstr?>" title="Sort By Ticket ID <?=$negorder?>" class="inline-flex items-center gap-1 text-gray-600 hover:text-indigo-600 transition-colors">
                                Заявка
                                <i data-lucide="arrow-up-down" class="w-3 h-3 opacity-40"></i>
                            </a>
                        </th>
                        <th class="table-th w-20">
                            <a href="tickets.php?sort=date&order=<?=$negorder?><?=$qstr?>" title="Sort By Date <?=$negorder?>" class="inline-flex items-center gap-1 text-gray-600 hover:text-indigo-600 transition-colors">
                                Дата
                                <i data-lucide="arrow-up-down" class="w-3 h-3 opacity-40"></i>
                            </a>
                        </th>
                        <th class="table-th w-[280px]">Тема</th>
                        <th class="table-th w-[120px]">
                            <a href="tickets.php?sort=dept&order=<?=$negorder?><?=$qstr?>" title="Sort By Category <?=$negorder?>" class="inline-flex items-center gap-1 text-gray-600 hover:text-indigo-600 transition-colors">
                                Отдел
                                <i data-lucide="arrow-up-down" class="w-3 h-3 opacity-40"></i>
                            </a>
                        </th>
                        <th class="table-th w-40">
                            <a href="tickets.php?sort=assign&order=<?=$negorder?><?=$qstr?>" title="Sort By AssignName <?=$negorder?>" class="inline-flex items-center gap-1 text-gray-600 hover:text-indigo-600 transition-colors">
                                Исполнители
                                <i data-lucide="arrow-up-down" class="w-3 h-3 opacity-40"></i>
                            </a>
                        </th>
                        <th class="table-th w-[15px]">
                            <i data-lucide="mail" class="w-4 h-4 text-gray-400" title="Новое сообщение"></i>
                        </th>
                        <th class="table-th w-[60px]">
                            <a href="tickets.php?sort=pri&order=<?=$negorder?><?=$qstr?>" title="Sort By Priority <?=$negorder?>" class="inline-flex items-center gap-1 text-gray-600 hover:text-indigo-600 transition-colors">
                                Приор.
                                <i data-lucide="arrow-up-down" class="w-3 h-3 opacity-40"></i>
                            </a>
                        </th>
                        <th class="table-th w-40">
                            <a href="tickets.php?sort=name&order=<?=$negorder?><?=$qstr?>" title="Sort By Name <?=$negorder?>" class="inline-flex items-center gap-1 text-gray-600 hover:text-indigo-600 transition-colors">
                                От
                                <i data-lucide="arrow-up-down" class="w-3 h-3 opacity-40"></i>
                            </a>
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                <?php
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

                        if (!empty($row['lastnotestaffid']) && strcasecmp($row['lastnotestaffid'],$thisuser->getId())) {$newmessageiconcolor = "text-amber-500"; $newmessageicontitle = "Новое внутреннее сообщение";}
                        if (strripos($row['lastmesstaffname'], $row['name']) !== false || empty($row['lastmesstaffname'])) {$newmessageiconcolor = "text-red-500"; $newmessageicontitle = "Ответ от заявителя";}

                        $thisuser_id = $thisuser->getId();

                        $mecolor = $thisuser_id == $row['staff_id'] ? ' style="color: Red;" ' : "";

                        $priorityColors = array(
                            'низкий' => 'bg-emerald-500',
                            'обычный' => 'bg-sky-500',
                            'средний' => 'bg-sky-500',
                            'высокий' => 'bg-amber-500',
                            'критичный' => 'bg-red-500',
                            'экстренный' => 'bg-red-500',
                            'low' => 'bg-emerald-500',
                            'normal' => 'bg-sky-500',
                            'medium' => 'bg-sky-500',
                            'high' => 'bg-amber-500',
                            'critical' => 'bg-red-500',
                            'emergency' => 'bg-red-500'
                        );
                        $priorityKey = mb_strtolower($row['priority_desc'], 'utf-8');
                        $priorityColorClass = isset($priorityColors[$priorityKey]) ? $priorityColors[$priorityKey] : 'bg-gray-400';

                        $priorityBadgeColors = array(
                            'низкий' => 'badge-success',
                            'обычный' => 'badge-info',
                            'средний' => 'badge-info',
                            'высокий' => 'badge-warning',
                            'критичный' => 'badge-danger',
                            'экстренный' => 'badge-danger',
                            'low' => 'badge-success',
                            'normal' => 'badge-info',
                            'medium' => 'badge-info',
                            'high' => 'badge-warning',
                            'critical' => 'badge-danger',
                            'emergency' => 'badge-danger'
                        );
                        $priorityBadge = isset($priorityBadgeColors[$priorityKey]) ? $priorityBadgeColors[$priorityKey] : 'badge-default';

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
                                    $andStaffNames[] = '<a class="text-red-500 font-medium">' . $staffName . '</a>';
                                } else {
                                    $andStaffNames[] = $staffName;
                                }
                            }
                            $staff_name .= $zpt . implode(', ', $andStaffNames);
                        }

                        $flagIcon = '';
                        if ($flag == 'locked') $flagIcon = '<i data-lucide="lock" class="w-3.5 h-3.5 inline-block text-gray-400 mr-1" title="'.ucfirst($flagtitle).' заявка"></i>';
                        elseif ($flag == 'assigned') $flagIcon = '<i data-lucide="user-check" class="w-3.5 h-3.5 inline-block text-indigo-400 mr-1" title="'.ucfirst($flagtitle).' заявка"></i>';
                        elseif ($flag == 'overdue') $flagIcon = '<i data-lucide="alert-circle" class="w-3.5 h-3.5 inline-block text-red-400 mr-1" title="'.ucfirst($flagtitle).' заявка"></i>';

                        $sourceIcon = '';
                        switch(strtolower($row['source'])) {
                            case 'web': $sourceIcon = '<i data-lucide="globe" class="w-3.5 h-3.5 inline-block text-gray-400"></i>'; break;
                            case 'email': $sourceIcon = '<i data-lucide="mail" class="w-3.5 h-3.5 inline-block text-gray-400"></i>'; break;
                            case 'phone': $sourceIcon = '<i data-lucide="phone" class="w-3.5 h-3.5 inline-block text-gray-400"></i>'; break;
                            default: $sourceIcon = '<i data-lucide="globe" class="w-3.5 h-3.5 inline-block text-gray-400"></i>'; break;
                        }

                        $rowClass = $vipHighlight ? 'bg-amber-50/50 hover:bg-amber-50' : 'hover:bg-gray-50';
                        $statusBadge = '';
                        if (!strcasecmp($row['status'], 'open')) {
                            $statusBadge = 'status-open';
                        } else {
                            $statusBadge = 'status-closed';
                        }
                        ?>
                    <tr id="<?=$row['ticket_id']?>" class="<?=$rowClass?> transition-colors">
                        <td class="w-1 p-0">
                            <div class="w-1 h-full min-h-[48px] <?=$priorityColorClass?> rounded-r-sm"></div>
                        </td>
                        <?php if($canDelete || $canClose) { ?>
                        <td class="table-td text-center px-2">
                            <input type="checkbox" name="tids[]" value="<?=$row['ticket_id']?>" onClick="highLight(this.value,this.checked);"
                                   class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                        </td>
                        <?php } ?>
                        <td class="table-td text-center whitespace-nowrap" title="<?=Format::htmlchars($row['email'])?>">
                            <a title="<?=Format::htmlchars($row['source'])?> Заявка: <?=Format::htmlchars($row['email'])?>"
                               href="tickets.php?id=<?=$row['ticket_id']?>"
                               class="inline-flex items-center gap-1.5 text-indigo-600 hover:text-indigo-700 font-medium transition-colors">
                                <?=$sourceIcon?> <?=$tid?>
                            </a>
                        </td>
                        <td class="table-td text-center whitespace-nowrap text-sm text-gray-600"><?=Format::db_datetime($row['created'])?></td>
                        <td class="table-td">
                            <a href="tickets.php?id=<?=$row['ticket_id']?>" class="text-sm text-gray-900 hover:text-indigo-600 transition-colors">
                                <?=$flagIcon?><?=$subject?>
                            </a>
                            <?php if($row['attachments']) { ?>
                                <i data-lucide="paperclip" class="w-3 h-3 inline-block text-gray-400 ml-1"></i>
                            <?php } ?>
                        </td>
                        <td class="table-td text-center text-sm text-gray-600"><?=Format::truncate($row['dept_name'],80)?></td>
                        <td class="table-td text-center text-sm text-gray-700"><?=$staff_name?>&nbsp;</td>
                        <td class="table-td text-center whitespace-nowrap">
                            <?php if(!empty($newmessageiconcolor)) { ?>
                                <i data-lucide="mail" class="w-4 h-4 <?=$newmessageiconcolor?>" title="<?=$newmessageicontitle?>"></i>
                            <?php } ?>
                        </td>
                        <td class="table-td text-center">
                            <span class="<?=$priorityBadge?> inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium"><?=Format::htmlchars($row['priority_desc'])?></span>
                        </td>
                        <td class="table-td text-center text-sm text-gray-600"><?=Format::truncate($row['name'],80,strpos($row['name'],'@'))?>&nbsp;</td>
                    </tr>
                    <?php
                    }
                else: ?>
                    <tr>
                        <td colspan="10" class="table-td text-center py-12">
                            <div class="flex flex-col items-center gap-2 text-gray-400">
                                <i data-lucide="inbox" class="w-10 h-10"></i>
                                <span class="text-sm font-medium font-body">Запрос вернул пустой результат.</span>
                            </div>
                        </td>
                    </tr>
                <?php
                endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <?php
    if($num>0){
    ?>
        <div class="flex flex-wrap items-center justify-between gap-4 mt-4 px-1">
            <div class="flex items-center gap-3 text-sm font-body">
                <?php if($canDelete || $canClose) { ?>
                <span class="text-gray-500 font-medium">Выбрать:</span>
                <a href="#" onclick="return select_all(document.forms['tickets'],true)" class="text-indigo-600 hover:text-indigo-700 font-medium transition-colors">Все</a>
                <a href="#" onclick="return reset_all(document.forms['tickets'])" class="text-indigo-600 hover:text-indigo-700 font-medium transition-colors">Ничего</a>
                <a href="#" onclick="return toogle_all(document.forms['tickets'],true)" class="text-indigo-600 hover:text-indigo-700 font-medium transition-colors">Обратить</a>
                <?php } ?>
            </div>
            <div class="text-sm text-gray-600 font-body">
                страница: <?=$pageNav->getPageLinks()?>
            </div>
        </div>

        <?php if($canClose or $canDelete) { ?>
        <div class="flex flex-wrap items-center justify-center gap-2 mt-4">
            <?php
            $status=$_REQUEST['status']?$_REQUEST['status']:$status;

            switch (strtolower($status)) {
                case 'closed': ?>
                    <button type="submit" name="reopen" value="Открыть" class="btn-secondary btn-sm"
                        onClick='return confirm("Вы уверены что хотите открыть выбранные заявки?");'>
                        <i data-lucide="folder-open" class="w-4 h-4"></i>
                        Открыть
                    </button>
                    <?php
                    break;
                case 'open':
                case 'answered':
                case 'assigned':
                    ?>
                    <button type="submit" name="overdue" value="Просрочен" class="btn-secondary btn-sm"
                        onClick='return confirm("Вы уверены что хотите пометить выбранные заявки как просроченные?");'>
                        <i data-lucide="clock" class="w-4 h-4"></i>
                        Просрочен
                    </button>
                    <button type="submit" name="close" value="Закрыть" class="btn-secondary btn-sm"
                        onClick='return confirm("Вы уверены что хотите закрыть выбранные заявки?");'>
                        <i data-lucide="x-circle" class="w-4 h-4"></i>
                        Закрыть
                    </button>
                    <?php
                    break;
                default:
                    ?>
                    <button type="submit" name="close" value="Закрыть" class="btn-secondary btn-sm"
                        onClick='return confirm("Вы уверены что хотите закрыть выбранные заявки?");'>
                        <i data-lucide="x-circle" class="w-4 h-4"></i>
                        Закрыть
                    </button>
                    <button type="submit" name="reopen" value="Открыть" class="btn-secondary btn-sm"
                        onClick='return confirm("Вы уверены что хотите открыть выбранные заявки?");'>
                        <i data-lucide="folder-open" class="w-4 h-4"></i>
                        Открыть
                    </button>
            <?php
            }
            if($canDelete) {?>
                <button type="submit" name="delete" value="Удалить" class="inline-flex items-center gap-1.5 px-3 py-1.5 text-sm font-medium rounded-lg bg-red-50 text-red-600 border border-red-200 hover:bg-red-100 transition-colors btn-sm"
                    onClick='return confirm("Вы уверены что хотите УДАЛИТЬ выбранные заявки?");'>
                    <i data-lucide="trash-2" class="w-4 h-4"></i>
                    Удалить
                </button>
            <?php }?>
        </div>
        <?php }
    } ?>
</form>

<?php
