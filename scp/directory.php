<?php
require('staff.inc.php');
$nav->setTabActive('directory');
$nav->addSubMenu(array('desc' => 'Менеджеры', 'href' => 'directory.php', 'iconclass' => 'staff'));

$filter_search = isset($_REQUEST['query']) ? trim($_REQUEST['query']) : '';
$filter_dept   = isset($_REQUEST['dept'])  ? intval($_REQUEST['dept'])  : 0;

$WHERE = ' WHERE isvisible=1 ';
$sql = 'SELECT staff.staff_id, staff.dept_id, firstname, lastname, email, phone, phone_ext, mobile, dept_name, onvacation'
     . ' FROM ' . STAFF_TABLE . ' staff LEFT JOIN ' . DEPT_TABLE . ' USING(dept_id)';

if ($filter_search) {
    $escaped = str_replace(array('%', '_'), array('\\%', '\\_'), db_real_escape($filter_search, false));
    if (is_numeric($filter_search)) {
        $WHERE .= ' AND staff.phone LIKE ' . db_input('%' . $escaped . '%');
    } elseif (strpos($filter_search, '@') && Validator::is_email($filter_search)) {
        $WHERE .= ' AND staff.email=' . db_input($filter_search);
    } else {
        $WHERE .= ' AND (staff.email LIKE ' . db_input('%' . $escaped . '%')
                . ' OR staff.lastname LIKE ' . db_input('%' . $escaped . '%')
                . ' OR staff.firstname LIKE ' . db_input('%' . $escaped . '%') . ')';
    }
}
if ($filter_dept) {
    $WHERE .= ' AND staff.dept_id=' . db_input($filter_dept);
}

$users = db_query("$sql $WHERE ORDER BY lastname, firstname");
$count = $users ? db_num_rows($users) : 0;

require_once(STAFFINC_DIR . 'header.inc.php');
?>

<!-- Header -->
<div class="flex items-center gap-3 mb-6">
    <div class="flex-shrink-0 w-10 h-10 rounded-lg bg-indigo-100 flex items-center justify-center">
        <i data-lucide="users" class="w-5 h-5 text-indigo-600"></i>
    </div>
    <div>
        <h2 class="text-xl font-bold font-heading m-0 leading-tight">Менеджеры</h2>
        <p class="text-gray-500 text-sm mt-0.5">Справочник сотрудников компании</p>
    </div>
</div>

<!-- Filters -->
<div class="card mb-5">
    <div class="card-body">
        <form action="directory.php" method="GET">
            <div class="flex flex-wrap items-end gap-3">
                <div class="flex-1 min-w-48">
                    <label class="label">Поиск</label>
                    <div class="relative">
                        <i data-lucide="search" class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400 pointer-events-none"></i>
                        <input type="text" name="query" value="<?= Format::htmlchars($filter_search) ?>"
                            class="input pl-9" placeholder="Имя, email или телефон...">
                    </div>
                </div>
                <div class="min-w-48">
                    <label class="label">Отдел</label>
                    <select name="dept" class="select">
                        <option value="0">Все отделы</option>
                        <?php
                        $depts = db_query('SELECT dept_id, dept_name FROM ' . DEPT_TABLE . ' ORDER BY dept_name');
                        while ($d = db_fetch_array($depts)) {
                            $sel = ($filter_dept == $d['dept_id']) ? ' selected' : '';
                        ?>
                        <option value="<?= $d['dept_id'] ?>"<?= $sel ?>><?= Format::htmlchars($d['dept_name']) ?></option>
                        <?php } ?>
                    </select>
                </div>
                <div class="flex items-center gap-2">
                    <button type="submit" class="btn-primary btn-sm"><i data-lucide="search" class="w-4 h-4"></i> Найти</button>
                    <?php if ($filter_search || $filter_dept) { ?>
                    <a href="directory.php" class="btn-secondary btn-sm"><i data-lucide="x" class="w-4 h-4"></i> Сбросить</a>
                    <?php } ?>
                </div>
                <?php if ($count) { ?>
                <span class="text-sm text-gray-500 ml-auto self-end pb-0.5"><?= $count ?> сотр.</span>
                <?php } ?>
            </div>
        </form>
    </div>
</div>

<?php if ($users && $count): ?>
<!-- Table -->
<div class="card">
    <div class="overflow-x-auto">
        <table class="table-modern w-full">
            <thead>
                <tr>
                    <th class="table-th">Сотрудник</th>
                    <th class="table-th">Отдел</th>
                    <th class="table-th">Email</th>
                    <th class="table-th">Телефон</th>
                    <th class="table-th">Мобильный</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = db_fetch_array($users)):
                    $initials = mb_strtoupper(mb_substr($row['firstname'], 0, 1) . mb_substr($row['lastname'], 0, 1));
                    $fullName = Format::htmlchars(trim($row['firstname'] . ' ' . $row['lastname']));
                    $phone = Format::phone($row['phone']);
                    $ext = $row['phone_ext'] ? ' доб. ' . $row['phone_ext'] : '';
                    $mobile = Format::phone($row['mobile']);
                    $colors = array('bg-indigo-100 text-indigo-700', 'bg-emerald-100 text-emerald-700',
                                    'bg-amber-100 text-amber-700', 'bg-rose-100 text-rose-700',
                                    'bg-sky-100 text-sky-700', 'bg-violet-100 text-violet-700');
                    $colorClass = $colors[crc32($row['staff_id']) % count($colors)];
                ?>
                <tr class="hover:bg-gray-50 transition-colors">
                    <td class="table-td">
                        <div class="flex items-center gap-3">
                            <div class="flex-shrink-0 w-9 h-9 rounded-full <?= $colorClass ?> flex items-center justify-center text-sm font-semibold">
                                <?= $initials ?>
                            </div>
                            <div>
                                <div class="font-medium text-gray-900 leading-tight"><?= $fullName ?></div>
                                <?php if ($row['onvacation']): ?>
                                <span class="badge-warning text-[10px] mt-0.5">В отпуске</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </td>
                    <td class="table-td text-sm text-gray-600">
                        <?= $row['dept_name'] ? Format::htmlchars($row['dept_name']) : '<span class="text-gray-400">—</span>' ?>
                    </td>
                    <td class="table-td text-sm">
                        <?php if ($row['email']): ?>
                        <a href="mailto:<?= Format::htmlchars($row['email']) ?>" class="text-indigo-600 hover:text-indigo-800">
                            <?= Format::htmlchars($row['email']) ?>
                        </a>
                        <?php else: ?>
                        <span class="text-gray-400">—</span>
                        <?php endif; ?>
                    </td>
                    <td class="table-td text-sm text-gray-600 whitespace-nowrap">
                        <?= $phone ? Format::htmlchars($phone . $ext) : '<span class="text-gray-400">—</span>' ?>
                    </td>
                    <td class="table-td text-sm text-gray-600 whitespace-nowrap">
                        <?php if ($mobile): ?>
                        <a href="tel:<?= Format::htmlchars($row['mobile']) ?>" class="hover:text-indigo-600">
                            <?= Format::htmlchars($mobile) ?>
                        </a>
                        <?php else: ?>
                        <span class="text-gray-400">—</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<?php else: ?>
<div class="card">
    <div class="card-body">
        <div class="flex flex-col items-center justify-center py-16 text-gray-400">
            <i data-lucide="users" class="w-14 h-14 mb-4 opacity-40"></i>
            <p class="text-sm"><?= ($filter_search || $filter_dept) ? 'Ничего не найдено. Попробуйте изменить критерии поиска.' : 'Нет сотрудников в справочнике.' ?></p>
        </div>
    </div>
</div>
<?php endif; ?>

<?php include_once(STAFFINC_DIR . 'footer.inc.php'); ?>
