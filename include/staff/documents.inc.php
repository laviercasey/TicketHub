<?php
if(!defined('OSTSCPINC') or !is_object($thisuser) or !$thisuser->canManageKb()) die('Доступ запрещён');

$select='SELECT doc.*, dept.dept_name, CONCAT(staff.firstname," ",staff.lastname) as staff_name ';
$from='FROM '.KB_DOCUMENTS_TABLE.' doc LEFT JOIN '.DEPT_TABLE.' dept ON doc.dept_id=dept.dept_id LEFT JOIN '.STAFF_TABLE.' staff ON doc.staff_id=staff.staff_id ';
$where='';
$qstr='';

if(($_REQUEST['a'] ?? '')==='search') {
    $hasQuery = !empty($_REQUEST['query']) && strlen($_REQUEST['query']) >= 3;
    $hasDept = !empty($_REQUEST['dept_id']) && is_numeric($_REQUEST['dept_id']) && $_REQUEST['dept_id'] > 0;
    $hasAudience = !empty($_REQUEST['audience']) && in_array($_REQUEST['audience'], array('staff','client','all'));
    $hasStaff = !empty($_REQUEST['staff_id']) && is_numeric($_REQUEST['staff_id']) && $_REQUEST['staff_id'] > 0;
    $hasFilter = $hasDept || $hasAudience || $hasStaff;

    if(!empty($_REQUEST['query']) && strlen($_REQUEST['query']) < 3) {
        $errors['err']='Поисковый запрос должен быть не менее 3 символов';
    } else {
        $search=true;
        $qstr.='&a='.urlencode($_REQUEST['a']);
        $where=' WHERE 1';
        if($hasQuery) {
            $qstr.='&query='.urlencode($_REQUEST['query']);
            $where.=' AND (doc.title LIKE '.db_input('%'.$_REQUEST['query'].'%').' OR doc.description LIKE '.db_input('%'.$_REQUEST['query'].'%').')';
        }
        if($hasDept) {
            $where .= ' AND doc.dept_id=' . db_input($_REQUEST['dept_id']);
            $qstr .= '&dept_id=' . urlencode($_REQUEST['dept_id']);
        }
        if($hasAudience) {
            $where .= ' AND doc.audience=' . db_input($_REQUEST['audience']);
            $qstr .= '&audience=' . urlencode($_REQUEST['audience']);
        }
        if($hasStaff) {
            $where .= ' AND doc.staff_id=' . db_input($_REQUEST['staff_id']);
            $qstr .= '&staff_id=' . urlencode($_REQUEST['staff_id']);
        }
    }
} else {
    if(!empty($_REQUEST['dept_id']) && is_numeric($_REQUEST['dept_id'])) {
        $where .= ($where ? ' AND ' : ' WHERE ') . 'doc.dept_id=' . db_input($_REQUEST['dept_id']);
        $qstr .= '&dept_id=' . urlencode($_REQUEST['dept_id']);
    }
    if(!empty($_REQUEST['audience']) && in_array($_REQUEST['audience'], array('staff','client','all'))) {
        $where .= ($where ? ' AND ' : ' WHERE ') . 'doc.audience=' . db_input($_REQUEST['audience']);
        $qstr .= '&audience=' . urlencode($_REQUEST['audience']);
    }
    if(!empty($_REQUEST['staff_id']) && is_numeric($_REQUEST['staff_id'])) {
        $where .= ($where ? ' AND ' : ' WHERE ') . 'doc.staff_id=' . db_input($_REQUEST['staff_id']);
        $qstr .= '&staff_id=' . urlencode($_REQUEST['staff_id']);
    }
}

$sortOptions=array('title'=>'doc.title','created'=>'doc.created','updated'=>'doc.updated','type'=>'doc.doc_type');
$orderWays=array('DESC'=>'DESC','ASC'=>'ASC');

$order_column = isset($sortOptions[$_REQUEST['sort'] ?? '']) ? $sortOptions[$_REQUEST['sort']] : 'doc.created';
$order = isset($orderWays[$_REQUEST['order'] ?? '']) ? $orderWays[$_REQUEST['order']] : 'DESC';

$order_by = $search ? '' : " ORDER BY $order_column $order ";

$total=db_count('SELECT count(*) '.$from.' '.$where);
$pagelimit=$thisuser->getPageLimit();
$pagelimit=$pagelimit?$pagelimit:PAGE_LIMIT;
$page=(isset($_GET['p']) && is_numeric($_GET['p']))?$_GET['p']:1;
$pageNav=new Pagenate($total,$page,$pagelimit);
$pageNav->setURL('documents.php',$qstr.'&sort='.urlencode($_REQUEST['sort'] ?? '').'&order='.urlencode($_REQUEST['order'] ?? ''));

$query="$select $from $where $order_by LIMIT ".$pageNav->getStart().",".$pageNav->getLimit();
$docs = db_query($query);
$showing=db_num_rows($docs)?$pageNav->showing():'';
$negorder=$order=='DESC'?'ASC':'DESC';
?>

<?php if(!empty($errors['err'])) { ?>
    <div class="alert-danger mb-4">
        <i data-lucide="alert-triangle" class="w-4 h-4 flex-shrink-0"></i>
        <span><?=Format::htmlchars($errors['err'])?></span>
    </div>
<?php } elseif($msg) { ?>
    <div class="alert-success mb-4">
        <i data-lucide="check-circle" class="w-4 h-4 flex-shrink-0"></i>
        <span><?=Format::htmlchars($msg)?></span>
    </div>
<?php } elseif($warn) { ?>
    <div class="alert-warning mb-4">
        <i data-lucide="alert-circle" class="w-4 h-4 flex-shrink-0"></i>
        <span><?=Format::htmlchars($warn)?></span>
    </div>
<?php } ?>

<div class="flex justify-between items-center mb-4">
    <h2 class="text-lg font-heading font-semibold text-gray-900 flex items-center gap-2">
        <i data-lucide="file-text" class="w-5 h-5"></i> Документация
    </h2>
    <a href="documents.php?a=add" class="btn-primary">
        <i data-lucide="plus" class="w-4 h-4"></i> Новый документ
    </a>
</div>

<div class="card mb-4">
    <div class="card-body">
        <form action="documents.php" method="GET" class="flex flex-wrap items-end gap-3">
            <input type='hidden' name='a' value='search'>

                <div class="form-group">
                    <label class="label">Поиск</label>
                    <input type="text" name="query" class="input" value="<?=Format::htmlchars($_REQUEST['query'] ?? '')?>" placeholder="Название документа...">
                </div>

                <div class="form-group">
                    <label class="label">Отдел</label>
                    <select name="dept_id" class="select">
                        <option value="0">Все Отделы</option>
                        <?php
                        $depts_q = db_query('SELECT dept_id,dept_name FROM '.DEPT_TABLE.' ORDER BY dept_name');
                        while (list($deptId,$deptName) = db_fetch_row($depts_q)){
                            $selected = (($_REQUEST['dept_id'] ?? '')==$deptId)?'selected':''; ?>
                            <option value="<?=$deptId?>" <?=$selected?>><?=Format::htmlchars($deptName)?></option>
                        <?php } ?>
                    </select>
                </div>

                <div class="form-group">
                    <label class="label">Аудитория</label>
                    <select name="audience" class="select">
                        <option value="">Все</option>
                        <option value="staff" <?=($_REQUEST['audience'] ?? '')=='staff'?'selected':''?>>Менеджеры</option>
                        <option value="client" <?=($_REQUEST['audience'] ?? '')=='client'?'selected':''?>>Пользователи</option>
                        <option value="all" <?=($_REQUEST['audience'] ?? '')=='all'?'selected':''?>>Общие</option>
                    </select>
                </div>

                <div class="form-group">
                    <label class="label">Автор</label>
                    <select name="staff_id" class="select">
                        <option value="0">Все</option>
                        <?php
                        $staff_q = db_query('SELECT DISTINCT s.staff_id, CONCAT(s.firstname," ",s.lastname) as name FROM '.STAFF_TABLE.' s INNER JOIN '.KB_DOCUMENTS_TABLE.' d ON s.staff_id=d.staff_id ORDER BY s.firstname');
                        while ($srow = db_fetch_array($staff_q)){
                            $selected = (($_REQUEST['staff_id'] ?? '')==$srow['staff_id'])?'selected':''; ?>
                            <option value="<?=$srow['staff_id']?>" <?=$selected?>><?=Format::htmlchars($srow['name'])?></option>
                        <?php } ?>
                    </select>
                </div>

                <div class="flex items-center gap-2">
                    <button type="submit" class="btn-primary">
                        <i data-lucide="search" class="w-4 h-4"></i> Найти
                    </button>
                    <a href="documents.php" class="btn-secondary">Сбросить</a>
                </div>
            </form>
        </div>
    </div>

<div class="card">
    <div class="card-header">
        <span class="text-sm text-gray-500"><?=$showing?></span>
    </div>

    <form action="documents.php" method="POST" name="doclist" onSubmit="return checkbox_checker(document.forms['doclist'],1,0);">
        <?php echo Misc::csrfField(); ?>
        <input type="hidden" name="a" value="process">
        <div class="table-wrapper overflow-x-auto">
        <table class="table-modern w-full">
            <thead>
            <tr>
                <th class="table-th w-8">&nbsp;</th>
                <th class="table-th"><a href="documents.php?sort=title&order=<?=$negorder?><?=$qstr?>" class="text-gray-500 hover:text-indigo-600">Название</a></th>
                <th class="table-th w-20"><a href="documents.php?sort=type&order=<?=$negorder?><?=$qstr?>" class="text-gray-500 hover:text-indigo-600">Тип</a></th>
                <th class="table-th w-24">Аудитория</th>
                <th class="table-th w-36">Отдел</th>
                <th class="table-th w-36">Автор</th>
                <th class="table-th w-16">Статус</th>
                <th class="table-th w-32"><a href="documents.php?sort=created&order=<?=$negorder?><?=$qstr?>" class="text-gray-500 hover:text-indigo-600">Дата создания</a></th>
                <th class="table-th w-20">Действия</th>
            </tr>
            </thead>
            <tbody>
            <?php
            if($docs && db_num_rows($docs)):
                while ($row = db_fetch_array($docs)) {
                    $highlight = ($document && $document->getId()==$row['doc_id']) ? 'bg-indigo-50' : '';
                    $typeIcon = ($row['doc_type']=='file') ? 'file' : 'link';
                    $typeLabel = ($row['doc_type']=='file') ? 'Файл' : 'Ссылка';
                    $audienceLabels = array('staff'=>'Менеджеры','client'=>'Пользователи','all'=>'Общие');
                    $audienceLabel = isset($audienceLabels[$row['audience']]) ? $audienceLabels[$row['audience']] : $row['audience'];
                    ?>
                <tr id="<?=$row['doc_id']?>" class="<?=$highlight?> hover:bg-gray-50 transition-colors">
                    <td class="table-td">
                        <input type="checkbox" name="docs[]" value="<?=$row['doc_id']?>" class="checkbox"
                            onClick="highLight(this.value,this.checked);">
                    </td>
                    <td class="table-td">
                        <a href="#" onclick="previewDocument(<?=$row['doc_id']?>); return false;" class="text-indigo-600 hover:text-indigo-800 font-medium">
                            <i data-lucide="<?=$typeIcon?>" class="w-4 h-4 inline-block mr-1"></i>
                            <?=Format::htmlchars(Format::truncate($row['title'],50))?>
                        </a>
                    </td>
                    <td class="table-td"><?=$typeLabel?></td>
                    <td class="table-td"><?=$audienceLabel?></td>
                    <td class="table-td"><?=$row['dept_name']?Format::htmlchars($row['dept_name']):'<span class="text-gray-400">Все отделы</span>'?></td>
                    <td class="table-td"><?=Format::htmlchars($row['staff_name'])?></td>
                    <td class="table-td">
                        <?php if($row['isenabled']) { ?>
                            <span class="badge-success">Вкл</span>
                        <?php } else { ?>
                            <span class="badge-gray">Выкл</span>
                        <?php } ?>
                    </td>
                    <td class="table-td text-gray-500"><?=Format::db_datetime($row['created'])?></td>
                    <td class="table-td">
                        <div class="flex items-center gap-1">
                            <a href="documents.php?id=<?=$row['doc_id']?>" class="btn-ghost btn-sm p-1" title="Редактировать">
                                <i data-lucide="edit" class="w-4 h-4"></i>
                            </a>
                            <a href="#" class="btn-ghost btn-sm p-1" title="Превью"
                               onclick="previewDocument(<?=$row['doc_id']?>); return false;">
                                <i data-lucide="eye" class="w-4 h-4"></i>
                            </a>
                        </div>
                    </td>
                </tr>
                <?php
                }
            else: ?>
                <tr>
                    <td colspan="9" class="table-td text-center py-12">
                        <i data-lucide="file-text" class="w-12 h-12 text-gray-300 mx-auto mb-3"></i>
                        <p class="text-gray-500">Документы не найдены</p>
                    </td>
                </tr>
            <?php
            endif; ?>
            </tbody>
        </table>
        </div>

        <?php if(db_num_rows($docs)>0): ?>
        <div class="px-6 py-3 border-t border-gray-200 flex flex-wrap items-center gap-2 text-sm">
            Выбрать:&nbsp;
            <a href="#" onclick="return select_all(document.forms['doclist'],true)" class="text-indigo-600 hover:text-indigo-800">Все</a>&nbsp;
            <a href="#" onclick="return toogle_all(document.forms['doclist'],true)" class="text-indigo-600 hover:text-indigo-800">Обратить</a>&nbsp;
            <a href="#" onclick="return reset_all(document.forms['doclist'])" class="text-indigo-600 hover:text-indigo-800">Ничего</a>&nbsp;
            &nbsp;страница:<?=$pageNav->getPageLinks()?>&nbsp;
        </div>
        <div class="px-6 py-3 border-t border-gray-200 flex flex-wrap justify-center gap-2">
            <button class="btn-primary btn-sm" type="submit" name="enable" value="Включить"
                onClick='return confirm("Вы уверены что хотите ВКЛЮЧИТЬ выбранные документы?");'>Включить</button>
            <button class="btn-warning btn-sm" type="submit" name="disable" value="Отключить"
                onClick='return confirm("Вы уверены что хотите ОТКЛЮЧИТЬ выбранные документы?");'>Отключить</button>
            <button class="btn-danger btn-sm" type="submit" name="delete" value="Удалить"
                onClick='return confirm("Вы уверены что хотите УДАЛИТЬ выбранные документы? Это действие необратимо!");'>Удалить</button>
        </div>
        <?php endif; ?>
    </form>
</div>

<div id="docPreviewModal" class="fixed inset-0 z-50 hidden" x-data="{ open: false }">
    <div class="fixed inset-0 bg-black/50 backdrop-blur-sm" onclick="closeDocPreview()"></div>
    <div class="fixed inset-4 md:inset-10 bg-white rounded-xl shadow-2xl flex flex-col z-10">
        <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200">
            <h3 class="font-heading font-semibold text-gray-900" id="previewTitle">Превью документа</h3>
            <button type="button" onclick="closeDocPreview()" class="btn-ghost p-1">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>
        </div>
        <div class="flex-1 overflow-auto p-6" id="previewBody">
            <div class="flex items-center justify-center h-full">
                <div class="spinner"></div>
            </div>
        </div>
        <div class="flex items-center justify-end px-6 py-4 border-t border-gray-200">
            <button type="button" class="btn-secondary" onclick="closeDocPreview()">Закрыть</button>
        </div>
    </div>
</div>

<script type="text/javascript">
function previewDocument(docId) {
    $('#previewBody').html('<div class="flex items-center justify-center h-full"><div class="spinner"></div></div>');
    document.getElementById('docPreviewModal').classList.remove('hidden');
    fetch('dispatch.php?api=documents&f=preview&id=' + docId, { headers: {'X-Requested-With': 'XMLHttpRequest'} })
    .then(function(response) {
        if (!response.ok) throw new Error('Ошибка сети');
        return response.text();
    })
    .then(function(data) {
        $('#previewBody').html(data);
    })
    .catch(function() {
        $('#previewBody').html('<p class="text-center text-red-500">Ошибка загрузки превью</p>');
    });
}
function closeDocPreview() {
    document.getElementById('docPreviewModal').classList.add('hidden');
}
</script>
