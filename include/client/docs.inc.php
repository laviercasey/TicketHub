<?php
if(!defined('OSTCLIENTINC')) die('Доступ запрещён');

$where = " WHERE doc.isenabled=1 AND doc.audience IN ('client','all') ";
$qstr = '';

if($_REQUEST['q'] && strlen($_REQUEST['q'])>=3) {
    $where .= " AND MATCH(doc.title,doc.description) AGAINST (".db_input($_REQUEST['q']).")";
    $qstr .= '&q='.urlencode($_REQUEST['q']);
}

if($_REQUEST['dept_id'] && is_numeric($_REQUEST['dept_id'])) {
    $where .= " AND (doc.dept_id=".db_input($_REQUEST['dept_id'])." OR doc.dept_id=0)";
    $qstr .= '&dept_id='.urlencode($_REQUEST['dept_id']);
}

$select = 'SELECT doc.*, dept.dept_name ';
$from = 'FROM '.KB_DOCUMENTS_TABLE.' doc LEFT JOIN '.DEPT_TABLE.' dept ON doc.dept_id=dept.dept_id ';
$order_by = ' ORDER BY doc.created DESC ';

$total = db_count('SELECT count(*) '.$from.$where);
$pagelimit = 12;
$page = (isset($_GET['p']) && is_numeric($_GET['p'])) ? $_GET['p'] : 1;
$pageNav = new Pagenate($total, $page, $pagelimit);
$pageNav->setURL('docs.php', $qstr);

$query = "$select $from $where $order_by LIMIT ".$pageNav->getStart().",".$pageNav->getLimit();
$docs = db_query($query);
$showing = db_num_rows($docs) ? $pageNav->showing() : '';
?>

<div class="mb-6">
    <h1 class="text-2xl font-heading font-bold text-gray-900">База знаний</h1>
    <p class="text-sm text-gray-500 mt-1">Документация и полезные материалы</p>
</div>

<?php if(!empty($errors['err'])) { ?>
    <div class="alert-danger mb-6">
        <i data-lucide="alert-triangle" class="w-4 h-4 flex-shrink-0"></i>
        <span><?=Format::htmlchars($errors['err'])?></span>
    </div>
<?php } ?>

<!-- Search & Filter -->
<div class="card mb-6">
    <div class="card-body">
        <form action="docs.php" method="GET" class="flex flex-col sm:flex-row gap-3">
            <div class="flex-1">
                <input type="text" name="q" class="input" value="<?=Format::htmlchars($_REQUEST['q'])?>"
                       placeholder="Поиск документов...">
            </div>
            <div class="sm:w-48">
                <select name="dept_id" class="select">
                    <option value="0">Все отделы</option>
                    <?php
                    $depts_q = db_query('SELECT dept_id,dept_name FROM '.DEPT_TABLE.' WHERE ispublic=1 ORDER BY dept_name');
                    while (list($deptId,$deptName) = db_fetch_row($depts_q)){
                        $selected = ($_REQUEST['dept_id']==$deptId)?'selected':''; ?>
                        <option value="<?=$deptId?>" <?=$selected?>><?=Format::htmlchars($deptName)?></option>
                    <?php } ?>
                </select>
            </div>
            <button type="submit" class="btn-primary">
                <i data-lucide="search" class="w-4 h-4"></i> Найти
            </button>
            <a href="docs.php" class="btn-secondary">Сбросить</a>
        </form>
    </div>
</div>

<?php if($showing) { ?>
    <p class="text-sm text-gray-500 mb-4"><?=$showing?></p>
<?php } ?>

<?php if($docs && db_num_rows($docs)): ?>
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
    <?php
    while ($row = db_fetch_array($docs)) {
        $typeIcon = ($row['doc_type']=='file') ? 'file' : 'link';
        $typeLabel = ($row['doc_type']=='file') ? 'Файл' : 'Ссылка';
        $deptLabel = $row['dept_name'] ? Format::htmlchars($row['dept_name']) : 'Общий';
        $descr = $row['description'] ? Format::htmlchars(Format::truncate($row['description'], 120)) : 'Нет описания';
    ?>
    <div class="card hover:shadow-card-hover transition-shadow duration-200 flex flex-col">
        <div class="card-body flex-1">
            <div class="flex items-start gap-3 mb-3">
                <div class="w-10 h-10 rounded-lg bg-indigo-50 flex items-center justify-center flex-shrink-0">
                    <i data-lucide="<?=$typeIcon?>" class="w-5 h-5 text-indigo-600"></i>
                </div>
                <div class="min-w-0">
                    <h3 class="text-sm font-semibold text-gray-900 line-clamp-2">
                        <?=Format::htmlchars(Format::truncate($row['title'], 60))?>
                    </h3>
                    <span class="badge-gray mt-1"><?=$typeLabel?></span>
                </div>
            </div>
            <p class="text-sm text-gray-500 mb-3 line-clamp-3"><?=$descr?></p>
            <div class="flex items-center gap-3 text-xs text-gray-400">
                <span class="flex items-center gap-1">
                    <i data-lucide="building-2" class="w-3 h-3"></i> <?=$deptLabel?>
                </span>
                <span class="flex items-center gap-1">
                    <i data-lucide="calendar" class="w-3 h-3"></i> <?=Format::db_datetime($row['created'])?>
                </span>
            </div>
        </div>
        <div class="px-6 py-3 border-t border-gray-100 flex items-center gap-2">
            <a href="docs.php?id=<?=$row['doc_id']?>" class="btn-primary btn-sm flex-1 text-center">
                <i data-lucide="eye" class="w-3.5 h-3.5"></i> Просмотреть
            </a>
            <?php if($row['doc_type']=='file'){ ?>
            <a href="doc_attachment.php?id=<?=$row['doc_id']?>" class="btn-secondary btn-sm">
                <i data-lucide="download" class="w-3.5 h-3.5"></i>
            </a>
            <?php } ?>
        </div>
    </div>
    <?php } ?>
</div>

<?php if($total > $pagelimit): ?>
<div class="flex items-center justify-center mt-6 gap-2">
    <span class="text-sm text-gray-500">Страница:</span>
    <div class="pagination">
        <?=$pageNav->getPageLinks()?>
    </div>
</div>
<?php endif; ?>

<?php else: ?>
<div class="card">
    <div class="py-12">
        <div class="empty-state">
            <i data-lucide="book-open" class="empty-state-icon"></i>
            <p class="empty-state-title">Документы не найдены</p>
            <p class="empty-state-text">Попробуйте изменить параметры поиска</p>
        </div>
    </div>
</div>
<?php endif; ?>
