<?php
if(!defined('OSTCLIENTINC') || !is_object($thisclient) || !$thisclient->isValid()) die('Kwaheri');

$qstr='&';
$status=null;
$activeTab='open';
if(!empty($_REQUEST['status'])) {
    $qstr.='status='.urlencode($_REQUEST['status']);
    switch(strtolower($_REQUEST['status'])) {
     case 'open':
        $status='open';
        $activeTab='open';
        break;
     case 'assigned':
        $status='open';
        $activeTab='open';
        break;
     case 'closed':
        $status='closed';
        $activeTab='closed';
        break;
     default:
        $status='';
    }
}

$qwhere =' WHERE email='.db_input($thisclient->getEmail());

if($status == 'assigned'){
    $qwhere.=" AND status != 'closed' AND ticket.staff_id > 0";
} elseif($status && $status != 'assigned'){
    $qwhere.=' AND status='.db_input($status);
} elseif($status === null) {
    $qwhere.=" AND status != 'closed'";
}
$sortOptions=array('date'=>'ticket.created','ID'=>'ticketID','pri'=>'priority_id','dept'=>'dept_name');
$orderWays=array('DESC'=>'DESC','ASC'=>'ASC');

if(!empty($_REQUEST['sort'])) {
    $order_by = isset($sortOptions[$_REQUEST['sort']]) ? $sortOptions[$_REQUEST['sort']] : null;
}
if(!empty($_REQUEST['order'])) {
    $order = isset($orderWays[$_REQUEST['order']]) ? $orderWays[$_REQUEST['order']] : null;
}
$requestedLimit = isset($_GET['limit']) ? (int)$_GET['limit'] : 0;
if ($requestedLimit >= 1) {
    $qstr .= '&limit=' . $requestedLimit;
}

$order_by =$order_by?$order_by:'ticket.created';
$order=$order?$order:'DESC';
$pagelimit=($requestedLimit >= 1 && $requestedLimit <= 100) ? $requestedLimit : PAGE_LIMIT;
$page=(isset($_GET['p']) && is_numeric($_GET['p']))?$_GET['p']:1;

$qselect = 'SELECT ticket.ticket_id,ticket.ticketID,ticket.dept_id,isanswered,ispublic,subject,name,email '.
           ',dept_name,status,source,priority_id ,ticket.created ';
$qfrom=' FROM '.TICKET_TABLE.' ticket LEFT JOIN '.DEPT_TABLE.' dept ON ticket.dept_id=dept.dept_id ';
$total=db_count('SELECT count(*) '.$qfrom.' '.$qwhere);
$pageNav=new Pagenate($total,$page,$pagelimit);
$pageNav->setURL('view.php',$qstr.'&sort='.urlencode($_REQUEST['sort'] ?? '').'&order='.urlencode($_REQUEST['order'] ?? ''));

$qselect.=' ,count(attach_id) as attachments ';
$qfrom.=' LEFT JOIN '.TICKET_ATTACHMENT_TABLE.' attach ON  ticket.ticket_id=attach.ticket_id ';
$qgroup=' GROUP BY ticket.ticket_id';

$qselect.=' ,lastmessdept.lastmessdepid';
$qfrom.=' LEFT JOIN (SELECT tickets.ticket_id AS latmessticketid, staffs.dept_id AS lastmessdepid FROM '.TICKET_RESPONSE_TABLE.' as tickets
			INNER JOIN '.STAFF_TABLE.' AS staffs ON staffs.staff_id = tickets.staff_id
			ORDER BY tickets.created DESC) lastmessdept ON lastmessdept.latmessticketid = ticket.ticket_id ';

$qselect.=' ,myth_staff.assignfname, myth_staff.assignlname';
$qfrom.=' LEFT JOIN (SELECT staff_id, firstname as assignfname, lastname AS assignlname FROM '.STAFF_TABLE.') myth_staff ON myth_staff.staff_id = ticket.staff_id ';

$query="$qselect $qfrom $qwhere $qgroup ORDER BY $order_by $order LIMIT ".$pageNav->getStart().",".$pageNav->getLimit();
$tickets_res = db_query($query);
$showing=db_num_rows($tickets_res)?$pageNav->showing():"";
$tabLabels = array('open'=>'Открытые заявки','assigned'=>'Назначенные заявки','closed'=>'Закрытые заявки');
$results_type = isset($tabLabels[$status]) ? $tabLabels[$status] : 'Заявки';
$negorder=$order=='DESC'?'ASC':'DESC';
?>

<?php if(!empty($errors['err'])) { ?>
    <div class="alert-danger mb-6">
        <i data-lucide="alert-triangle" class="w-4 h-4 flex-shrink-0"></i>
        <span><?=Format::htmlchars($errors['err'])?></span>
    </div>
<?php } elseif($msg) { ?>
    <div class="alert-success mb-6">
        <i data-lucide="check-circle" class="w-4 h-4 flex-shrink-0"></i>
        <span><?=Format::htmlchars($msg)?></span>
    </div>
<?php } elseif($warn) { ?>
    <div class="alert-warning mb-6">
        <i data-lucide="alert-circle" class="w-4 h-4 flex-shrink-0"></i>
        <span><?=Format::htmlchars($warn)?></span>
    </div>
<?php } ?>

<div class="flex items-center gap-1 mb-4 border-b border-gray-200 overflow-x-auto -mx-4 px-4 sm:mx-0 sm:px-0">
    <a href="view.php?status=open" class="flex items-center gap-2 px-3 py-2.5 sm:px-4 sm:py-3 text-xs sm:text-sm font-medium whitespace-nowrap border-b-2 -mb-px transition-colors <?=$activeTab=='open'?'border-emerald-500 text-emerald-600':'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'?>">
        <i data-lucide="folder-open" class="w-4 h-4"></i> Открытые
    </a>
    <a href="view.php?status=closed" class="flex items-center gap-2 px-3 py-2.5 sm:px-4 sm:py-3 text-xs sm:text-sm font-medium whitespace-nowrap border-b-2 -mb-px transition-colors <?=$activeTab=='closed'?'border-red-500 text-red-600':'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'?>">
        <i data-lucide="folder" class="w-4 h-4"></i> Закрытые
    </a>
    <a href="view.php" class="flex items-center gap-2 px-3 py-2.5 sm:px-4 sm:py-3 text-xs sm:text-sm font-medium whitespace-nowrap text-gray-400 hover:text-gray-600 ml-auto transition-colors">
        <i data-lucide="refresh-cw" class="w-4 h-4"></i> <span class="hidden sm:inline">Обновить</span>
    </a>
</div>

<div class="card">
    <div class="card-header">
        <div class="flex items-center gap-3">
            <i data-lucide="inbox" class="w-5 h-5 text-gray-400"></i>
            <h2 class="font-heading font-semibold text-gray-900"><?=$showing?> <?=$results_type?></h2>
        </div>
    </div>

    <?php
    $total=0;
    if($tickets_res && ($num=db_num_rows($tickets_res))):
        $defaultDept=Dept::getDefaultDeptName();
        $ticketRows = [];
        while ($row = db_fetch_array($tickets_res)) {
            $row['_dept'] = $row['ispublic'] ? $row['dept_name'] : $defaultDept;
            $row['_subject'] = Format::htmlchars(Format::truncate($row['subject'],200));
            $row['_isBold'] = $row['isanswered'] && !strcasecmp($row['status'],'open');
            $row['_statusClass'] = ($row['status']=='open') ? 'status-open' : 'status-closed';
            $row['_hasNewMsg'] = ($row['dept_id']!=$row['lastmessdepid'] && !empty($row['lastmessdepid']));
            $row['_assignee'] = trim($row['assignfname']." ".$row['assignlname']);
            $ticketRows[] = $row;
        }
    ?>

    <div class="md:hidden divide-y divide-gray-100">
        <?php foreach($ticketRows as $row): ?>
        <a href="view.php?id=<?=$row['ticketID']?>" class="block px-4 py-4 hover:bg-gray-50 transition-colors">
            <div class="flex items-start justify-between gap-3 mb-2">
                <div class="min-w-0 flex-1">
                    <p class="text-sm <?=$row['_isBold']?'font-semibold':'font-medium'?> text-gray-900 truncate">
                        <?=$row['_subject']?>
                    </p>
                    <p class="text-xs text-gray-500 mt-0.5">
                        #<?=$row['ticketID']?> &middot; <?=Format::db_datetime($row['created'])?>
                    </p>
                </div>
                <div class="flex items-center gap-1.5 flex-shrink-0">
                    <?php if($row['_hasNewMsg']) { ?>
                        <span class="w-2 h-2 bg-red-500 rounded-full" title="Новое сообщение"></span>
                    <?php } ?>
                    <?php if($row['attachments']) { ?>
                        <i data-lucide="paperclip" class="w-3.5 h-3.5 text-gray-400"></i>
                    <?php } ?>
                    <span class="<?=$row['_statusClass']?>">
                        <?=Ticket::GetRusStatus(ucfirst($row['status']))?>
                    </span>
                </div>
            </div>
            <div class="flex items-center gap-3 text-xs text-gray-400">
                <span class="truncate"><?=Format::htmlchars(Format::truncate($row['_dept'],40))?></span>
                <?php if($row['_assignee'] && $row['_assignee'] !== ' ') { ?>
                    <span>&middot;</span>
                    <span class="truncate"><?=Format::htmlchars(Format::truncate($row['_assignee'],30))?></span>
                <?php } ?>
            </div>
        </a>
        <?php endforeach; ?>
    </div>

    <div class="hidden md:block table-wrapper border-0 rounded-none border-t">
        <table class="table-modern">
            <thead>
            <tr>
                <th class="table-th text-center">
                    <a href="view.php?sort=ID&order=<?=$negorder?><?=$qstr?>" class="hover:text-indigo-600" title="Сортировать по ID">
                        Заявка № <i data-lucide="arrow-up-down" class="w-3 h-3 inline"></i>
                    </a>
                </th>
                <th class="table-th text-center">
                    <a href="view.php?sort=date&order=<?=$negorder?><?=$qstr?>" class="hover:text-indigo-600" title="Сортировать по дате">
                        Дата <i data-lucide="arrow-up-down" class="w-3 h-3 inline"></i>
                    </a>
                </th>
                <th class="table-th text-center">Статус</th>
                <th class="table-th">Тема</th>
                <th class="table-th text-center w-10">
                    <i data-lucide="mail" class="w-3.5 h-3.5 inline text-gray-400" title="Новое сообщение"></i>
                </th>
                <th class="table-th text-center">
                    <a href="view.php?sort=dept&order=<?=$negorder?><?=$qstr?>" class="hover:text-indigo-600">
                        Отдел <i data-lucide="arrow-up-down" class="w-3 h-3 inline"></i>
                    </a>
                </th>
                <th class="table-th text-center">Ответственный</th>
                <th class="table-th text-center">Email</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach($ticketRows as $row): ?>
            <tr id="<?=$row['ticketID']?>">
                <td class="table-td text-center" title="<?=Format::htmlchars($row['email'])?>">
                    <a href="view.php?id=<?=$row['ticketID']?>" class="text-indigo-600 hover:text-indigo-700 font-medium <?=$row['_isBold']?'font-bold':''?>">
                        <?=$row['ticketID']?>
                    </a>
                </td>
                <td class="table-td text-center whitespace-nowrap"><?=Format::db_datetime($row['created'])?></td>
                <td class="table-td text-center">
                    <span class="<?=$row['_statusClass']?>">
                        <?=Ticket::GetRusStatus(ucfirst($row['status']))?>
                    </span>
                </td>
                <td class="table-td">
                    <a href="view.php?id=<?=$row['ticketID']?>" class="text-gray-900 hover:text-indigo-600 <?=$row['_isBold']?'font-semibold':''?>">
                        <?=$row['_subject']?>
                    </a>
                    <?php if($row['attachments']) { ?>
                        <i data-lucide="paperclip" class="w-3.5 h-3.5 inline text-gray-400 ml-1" title="Есть вложения"></i>
                    <?php } ?>
                </td>
                <td class="table-td text-center">
                    <?php if($row['_hasNewMsg']) { ?>
                        <i data-lucide="mail" class="w-4 h-4 inline text-red-500" title="Новое сообщение"></i>
                    <?php } ?>
                </td>
                <td class="table-td text-center text-xs"><?=Format::htmlchars(Format::truncate($row['_dept'],80))?></td>
                <td class="table-td text-center text-xs"><?=Format::htmlchars(Format::truncate($row['_assignee'],80,strpos($row['_assignee'],'@')))?></td>
                <td class="table-td text-center text-xs"><?=Format::htmlchars(Format::truncate($row['email'],80))?></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <?php else: ?>
    <div class="py-12">
        <div class="empty-state">
            <i data-lucide="inbox" class="empty-state-icon"></i>
            <p class="empty-state-title">Заявки не найдены</p>
            <p class="empty-state-text">У вас пока нет заявок</p>
        </div>
    </div>
    <?php endif; ?>

    <?php if($num>0 && $pageNav->getNumPages()>1) { ?>
    <div class="card-footer">
        <div class="flex items-center justify-center gap-2">
            <span class="text-sm text-gray-500">Страница:</span>
            <div class="pagination">
                <?=$pageNav->getPageLinks()?>
            </div>
        </div>
    </div>
    <?php } ?>
</div>
<?
