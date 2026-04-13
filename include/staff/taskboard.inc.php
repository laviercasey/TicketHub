<?php
if(!defined('OSTSCPINC') || !is_object($thisuser)) die('Доступ запрещён');

$info = $board ? $board->getInfo() : array();
$action = $board ? 'update' : 'add';
$title = $board ? 'Доска: ' . Format::htmlchars($board->getName()) : 'Новая доска';

$depts = array();
$dsql = 'SELECT dept_id, dept_name FROM ' . TABLE_PREFIX . 'department ORDER BY dept_name';
if (($dres = db_query($dsql)) && db_num_rows($dres)) {
    while ($drow = db_fetch_array($dres)) {
        $depts[] = $drow;
    }
}

$lists = array();
if ($board) {
    $lists = $board->getLists();
}

require_once(INCLUDE_DIR.'class.tasktag.php');
require_once(INCLUDE_DIR.'class.taskcustomfield.php');
require_once(INCLUDE_DIR.'class.taskpermission.php');
$boardTags = array();
$boardFields = array();
$boardPerms = array();
if ($board) {
    $boardTags = TaskTag::getByBoard($board->getId());
    $boardFields = TaskCustomField::getByBoard($board->getId());
    $boardPerms = TaskPermission::getByBoard($board->getId());
}
$fieldTypeLabels = TaskCustomField::getTypeLabels();
$permLevelLabels = TaskPermission::getLevelLabels();

$allStaff = array();
$stSql = 'SELECT staff_id, CONCAT(firstname," ",lastname) as name FROM ' . TABLE_PREFIX . 'staff WHERE isactive=1 ORDER BY firstname, lastname';
if (($stRes = db_query($stSql)) && db_num_rows($stRes)) {
    while ($stRow = db_fetch_array($stRes)) {
        $allStaff[] = $stRow;
    }
}

$allDepts = array();
$dpSql = 'SELECT dept_id, dept_name FROM ' . TABLE_PREFIX . 'department ORDER BY dept_name';
if (($dpRes = db_query($dpSql)) && db_num_rows($dpRes)) {
    while ($dpRow = db_fetch_array($dpRes)) {
        $allDepts[] = $dpRow;
    }
}

$colors = array('#3498db', '#2ecc71', '#e74c3c', '#f39c12', '#9b59b6', '#1abc9c', '#e67e22', '#34495e', '#16a085', '#c0392b');
$tagColors = array('#3498db', '#2ecc71', '#e74c3c', '#f39c12', '#9b59b6', '#1abc9c', '#e67e22', '#34495e', '#27ae60', '#c0392b', '#8e44ad', '#d35400');
?>

<?php if($msg) { ?>
<div id="infomessage" class="alert-success"><?php echo Format::htmlchars($msg); ?></div>
<?php } elseif(!empty($errors['err'])) { ?>
<div id="errormessage" class="alert-danger"><?php echo Format::htmlchars($errors['err']); ?></div>
<?php } ?>

<div class="flex items-center gap-2 mb-3">
    <a href="taskboards.php" class="btn-secondary btn-sm"><i data-lucide="arrow-left" class="w-4 h-4"></i> К доскам</a>
    <?php if ($board) { ?>
    <a href="tasks.php?board_id=<?php echo $board->getId(); ?>" class="btn-secondary btn-sm"><i data-lucide="list" class="w-4 h-4"></i> Задачи доски</a>
    <a href="tasks.php?a=automation&board_id=<?php echo $board->getId(); ?>" class="btn-secondary btn-sm"><i data-lucide="zap" class="w-4 h-4"></i> Автоматизация</a>
    <a href="tasks.php?a=add&board_id=<?php echo $board->getId(); ?>" class="btn-primary btn-sm"><i data-lucide="plus" class="w-4 h-4"></i> Новая задача</a>
    <?php } ?>
</div>

<h2 class="text-2xl font-heading font-bold mb-4"><?php echo $title; ?></h2>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <div class="lg:col-span-2">
        <form action="taskboards.php" method="POST">
            <?=Misc::csrfField()?>
            <input type="hidden" name="a" value="<?php echo $action; ?>">
            <?php if ($board) { ?>
            <input type="hidden" name="id" value="<?php echo $board->getId(); ?>">
            <?php } ?>

            <div class="card mb-6">
                <div class="card-header"><strong>Настройки доски</strong></div>
                <div class="card-body space-y-4">

                    <div class="form-group">
                        <label class="label">Название <span class="text-red-500">*</span></label>
                        <input type="text" name="board_name" class="input"
                               value="<?php echo Format::htmlchars($info['board_name'] ? $info['board_name'] : $_POST['board_name']); ?>"
                               placeholder="Название доски" required>
                        <?php if ($errors['board_name']) { ?>
                        <span class="form-error"><?php echo Format::htmlchars($errors['board_name']); ?></span>
                        <?php } ?>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div class="form-group">
                            <label class="label">Тип доски</label>
                            <select name="board_type" id="board_type" class="select">
                                <option value="project" <?php echo ($info['board_type']=='project' || !$info['board_type'])?'selected':''; ?>>Проект</option>
                                <option value="department" <?php echo ($info['board_type']=='department')?'selected':''; ?>>Отдел</option>
                            </select>
                        </div>
                        <div id="dept-group" style="<?php echo ($info['board_type']!='department')?'display:none;':''; ?>">
                            <div class="form-group">
                                <label class="label">Отдел</label>
                                <select name="dept_id" class="select">
                                    <option value="">Выберите отдел</option>
                                    <?php foreach ($depts as $d) { ?>
                                    <option value="<?php echo $d['dept_id']; ?>" <?php echo ($info['dept_id']==$d['dept_id'])?'selected':''; ?>><?php echo Format::htmlchars($d['dept_name']); ?></option>
                                    <?php } ?>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="label">Описание</label>
                        <textarea name="description" class="textarea" rows="3"
                                  placeholder="Описание доски..."><?php echo Format::htmlchars($info['description']); ?></textarea>
                    </div>

                    <div class="form-group">
                        <label class="label">Цвет</label>
                        <div class="flex flex-wrap gap-2">
                            <?php foreach ($colors as $c) { ?>
                            <label class="cursor-pointer">
                                <input type="radio" name="color" value="<?php echo $c; ?>"
                                       <?php echo (($info['color']==$c) || (!$info['color'] && $c=='#3498db'))?'checked':''; ?>
                                       style="display:none;">
                                <span class="inline-block w-8 h-8 rounded-full border-[3px] border-transparent color-pick transition-all hover:scale-110"
                                      style="background:<?php echo $c; ?>;"></span>
                            </label>
                            <?php } ?>
                        </div>
                    </div>
                </div>
            </div>

            <div class="flex items-center gap-3">
                <button type="submit" class="btn-primary"><i data-lucide="save" class="w-4 h-4"></i> <?php echo $board ? 'Сохранить' : 'Создать доску'; ?></button>
                <a href="taskboards.php" class="btn-secondary">Отмена</a>
            </div>
        </form>
    </div>

    <?php if ($board) { ?>
    <div class="space-y-6">
        <div class="card">
            <div class="card-header">
                <strong>Списки / Колонки</strong>
            </div>
            <div class="card-body p-0">
                <?php if (count($lists) > 0) { ?>
                <div class="divide-y divide-gray-100">
                    <?php foreach ($lists as $l) { ?>
                    <div class="flex items-center justify-between px-4 py-2">
                        <div class="flex items-center gap-2">
                            <i data-lucide="grip-vertical" class="w-4 h-4 text-gray-400 cursor-grab"></i>
                            <span class="text-sm"><?php echo Format::htmlchars($l['list_name']); ?></span>
                        </div>
                        <form action="taskboards.php" method="POST" class="inline">
                            <?=Misc::csrfField()?>
                            <input type="hidden" name="a" value="deletelist">
                            <input type="hidden" name="board_id" value="<?php echo $board->getId(); ?>">
                            <input type="hidden" name="list_id" value="<?php echo $l['list_id']; ?>">
                            <button type="submit" class="btn-danger btn-sm" onclick="return confirm('Удалить список? Задачи будут перемещены.');" title="Удалить"><i data-lucide="trash-2" class="w-4 h-4"></i></button>
                        </form>
                    </div>
                    <?php } ?>
                </div>
                <?php } else { ?>
                <div class="text-center text-gray-500 py-6">Списков нет</div>
                <?php } ?>
            </div>
            <div class="card-footer">
                <form action="taskboards.php" method="POST" class="flex gap-2">
                    <?=Misc::csrfField()?>
                    <input type="hidden" name="a" value="addlist">
                    <input type="hidden" name="board_id" value="<?php echo $board->getId(); ?>">
                    <input type="text" name="list_name" class="input text-sm flex-1" placeholder="Название списка">
                    <button type="submit" class="btn-primary btn-sm"><i data-lucide="plus" class="w-4 h-4"></i> Добавить</button>
                </form>
            </div>
        </div>

        <div class="card">
            <div class="card-header"><strong>Информация</strong></div>
            <div class="card-body text-sm text-gray-600 space-y-1">
                <div><strong>Создал:</strong> <?php
                    $cSql = 'SELECT CONCAT(firstname," ",lastname) as name FROM ' . TABLE_PREFIX . 'staff WHERE staff_id=' . db_input($board->getCreatedBy());
                    if (($cRes = db_query($cSql)) && db_num_rows($cRes)) {
                        $cRow = db_fetch_array($cRes);
                        echo Format::htmlchars($cRow['name']);
                    }
                    ?></div>
                <div><strong>Создана:</strong> <?php echo Format::htmlchars($board->getCreated()); ?></div>
                <?php if ($board->getUpdated()) { ?>
                <div><strong>Обновлена:</strong> <?php echo Format::htmlchars($board->getUpdated()); ?></div>
                <?php } ?>
                <div><strong>Задач:</strong> <?php echo $board->getTaskCount(); ?> (открытых: <?php echo $board->getOpenTaskCount(); ?>)</div>
            </div>
        </div>
    </div>

    <div class="lg:col-span-3">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-6">
            <div>
                <div class="card">
                    <div class="card-header">
                        <strong class="flex items-center gap-2"><i data-lucide="tags" class="w-4 h-4"></i> Теги доски</strong>
                    </div>
                    <div class="card-body p-0" id="board-tags-list">
                        <?php if (count($boardTags) > 0) { ?>
                        <div class="divide-y divide-gray-100">
                            <?php foreach ($boardTags as $bt) { ?>
                            <div class="flex items-center px-4 py-2" id="tag-row-<?php echo $bt['tag_id']; ?>">
                                <span class="inline-block w-3.5 h-3.5 rounded-full mr-3" style="background:<?php echo Format::htmlchars($bt['tag_color']); ?>;"></span>
                                <span class="text-sm flex-1"><?php echo Format::htmlchars($bt['tag_name']); ?></span>
                                <a href="#" class="text-red-500 delete-tag" data-id="<?php echo $bt['tag_id']; ?>" title="Удалить"><i data-lucide="trash-2" class="w-4 h-4"></i></a>
                            </div>
                            <?php } ?>
                        </div>
                        <?php } else { ?>
                        <div class="text-center text-gray-500 no-tags-msg py-4">Тегов нет</div>
                        <?php } ?>
                    </div>
                    <div class="card-footer">
                        <div class="flex items-center gap-2">
                            <input type="text" id="new-tag-name" class="input text-sm flex-1" placeholder="Название тега">
                            <select id="new-tag-color" class="select text-sm w-auto">
                                <?php foreach ($tagColors as $tc) { ?>
                                <option value="<?php echo $tc; ?>" style="background:<?php echo $tc; ?>;color:#fff;"><?php echo $tc; ?></option>
                                <?php } ?>
                            </select>
                            <button type="button" id="add-tag-btn" class="btn-primary btn-sm"><i data-lucide="plus" class="w-4 h-4"></i> Добавить</button>
                        </div>
                    </div>
                </div>
            </div>
            <div>
                <div class="card">
                    <div class="card-header">
                        <strong class="flex items-center gap-2"><i data-lucide="list" class="w-4 h-4"></i> Кастомные поля</strong>
                    </div>
                    <div class="card-body p-0" id="board-fields-list">
                        <?php if (count($boardFields) > 0) { ?>
                        <div class="divide-y divide-gray-100">
                            <?php foreach ($boardFields as $bf) { ?>
                            <div class="flex items-center px-4 py-2" id="field-row-<?php echo $bf['field_id']; ?>">
                                <span class="text-sm flex-1"><?php echo Format::htmlchars($bf['field_name']); ?></span>
                                <small class="text-gray-500 mr-2"><?php echo Format::htmlchars($fieldTypeLabels[$bf['field_type']]); ?></small>
                                <?php echo $bf['is_required'] ? '<span class="text-red-500 mr-2" title="Обязательное">*</span>' : ''; ?>
                                <a href="#" class="text-red-500 delete-field" data-id="<?php echo $bf['field_id']; ?>" title="Удалить"><i data-lucide="trash-2" class="w-4 h-4"></i></a>
                            </div>
                            <?php } ?>
                        </div>
                        <?php } else { ?>
                        <div class="text-center text-gray-500 no-fields-msg py-4">Кастомных полей нет</div>
                        <?php } ?>
                    </div>
                    <div class="card-footer">
                        <div class="flex items-center gap-2">
                            <input type="text" id="new-field-name" class="input text-sm flex-1" placeholder="Название поля">
                            <select id="new-field-type" class="select text-sm w-auto">
                                <?php foreach ($fieldTypeLabels as $fk => $fv) { ?>
                                <option value="<?php echo $fk; ?>"><?php echo Format::htmlchars($fv); ?></option>
                                <?php } ?>
                            </select>
                            <button type="button" id="add-field-btn" class="btn-primary btn-sm"><i data-lucide="plus" class="w-4 h-4"></i> Добавить</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="mt-6">
            <div class="card">
                <div class="card-header">
                    <strong class="flex items-center gap-2"><i data-lucide="lock" class="w-4 h-4"></i> Права доступа</strong>
                </div>
                <div class="card-body p-0" id="board-perms-list">
                    <?php if (count($boardPerms) > 0) { ?>
                    <div class="table-wrapper">
                    <table class="table-modern">
                        <thead>
                            <tr>
                                <th class="table-th">Тип</th>
                                <th class="table-th">Сотрудник / Отдел</th>
                                <th class="table-th">Уровень</th>
                                <th class="table-th w-8"></th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($boardPerms as $bp) { ?>
                            <tr id="perm-row-<?php echo $bp['permission_id']; ?>">
                                <td class="table-td"><small class="text-gray-500"><?php echo $bp['staff_id'] ? 'Сотрудник' : 'Отдел'; ?></small></td>
                                <td class="table-td"><?php echo Format::htmlchars($bp['staff_id'] ? $bp['staff_name'] : $bp['dept_name']); ?></td>
                                <td class="table-td"><span class="<?php echo $bp['permission_level']=='admin' ? 'badge-danger' : ($bp['permission_level']=='edit' ? 'badge-warning' : 'badge-info'); ?>"><?php echo Format::htmlchars($permLevelLabels[$bp['permission_level']]); ?></span></td>
                                <td class="table-td">
                                    <a href="#" class="text-red-500 delete-perm" data-id="<?php echo $bp['permission_id']; ?>" title="Удалить"><i data-lucide="trash-2" class="w-4 h-4"></i></a>
                                </td>
                            </tr>
                        <?php } ?>
                        </tbody>
                    </table>
                    </div>
                    <?php } else { ?>
                    <div class="text-center text-gray-500 no-perms-msg py-4">Права не настроены — доска доступна всем сотрудникам</div>
                    <?php } ?>
                </div>
                <div class="card-footer space-y-2">
                    <div class="flex flex-wrap items-center gap-2">
                        <label class="inline-flex items-center gap-1 text-sm cursor-pointer">
                            <input type="radio" name="perm_type" value="staff" checked="checked" id="perm-type-staff" class="radio"> Сотрудник
                        </label>
                        <label class="inline-flex items-center gap-1 text-sm cursor-pointer">
                            <input type="radio" name="perm_type" value="dept" id="perm-type-dept" class="radio"> Отдел
                        </label>
                        <select id="perm-staff-id" class="select text-sm w-auto">
                            <option value="">Сотрудник</option>
                            <?php foreach ($allStaff as $as) { ?>
                            <option value="<?php echo $as['staff_id']; ?>"><?php echo Format::htmlchars($as['name']); ?></option>
                            <?php } ?>
                        </select>
                        <select id="perm-dept-id" class="select text-sm w-auto" style="display:none;">
                            <option value="">Отдел</option>
                            <?php foreach ($allDepts as $ad) { ?>
                            <option value="<?php echo $ad['dept_id']; ?>"><?php echo Format::htmlchars($ad['dept_name']); ?></option>
                            <?php } ?>
                        </select>
                        <select id="perm-level" class="select text-sm w-auto">
                            <?php foreach ($permLevelLabels as $lk => $lv) { ?>
                            <option value="<?php echo $lk; ?>"><?php echo Format::htmlchars($lv); ?></option>
                            <?php } ?>
                        </select>
                        <button type="button" id="add-perm-btn" class="btn-primary btn-sm"><i data-lucide="plus" class="w-4 h-4"></i> Добавить</button>
                    </div>
                    <div>
                        <small class="text-gray-500 flex items-center gap-1"><i data-lucide="info" class="w-3 h-3"></i> Если права не настроены, доска доступна всем сотрудникам</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php } ?>
</div>

<script type="text/javascript">
$(document).ready(function(){
    $('#board_type').change(function(){
        if ($(this).val() == 'department') {
            $('#dept-group').show();
        } else {
            $('#dept-group').hide();
        }
    });

    $('input[name="color"]').change(function(){
        $('.color-pick').css('border-color', 'transparent');
        $(this).next('.color-pick').css('border-color', '#333');
    });
    $('input[name="color"]:checked').next('.color-pick').css('border-color', '#333');

    $('#add-tag-btn').click(function(){
        var name = $.trim($('#new-tag-name').val());
        var color = $('#new-tag-color').val();
        if (!name) { alert('Введите название тега'); return; }

        var btn = $(this);
        btn.prop('disabled', true);
        var params = new URLSearchParams({ api: 'tasktags', f: 'add', tag_name: name, tag_color: color, board_id: <?php echo $board ? $board->getId() : 0; ?> });
        fetch('dispatch.php?' + params.toString(), { headers: {'X-Requested-With': 'XMLHttpRequest'} })
        .then(function(response) {
            if (!response.ok) throw new Error('Ошибка сети');
            return response.json();
        })
        .then(function(data) {
            if (data && data.success) {
                $('.no-tags-msg').remove();
                var container = $('#board-tags-list .divide-y');
                if (container.length == 0) {
                    $('#board-tags-list').html('<div class="divide-y divide-gray-100"></div>');
                    container = $('#board-tags-list .divide-y');
                }
                container.append('<div class="flex items-center px-4 py-2" id="tag-row-' + data.tag_id + '"><span class="inline-block w-3.5 h-3.5 rounded-full mr-3" style="background:' + color + ';"></span><span class="text-sm flex-1">' + data.tag_name + '</span><a href="#" class="text-red-500 delete-tag" data-id="' + data.tag_id + '" title="Удалить"><i data-lucide="trash-2" class="w-4 h-4"></i></a></div>');
                if (typeof lucide !== 'undefined') lucide.createIcons();
                $('#new-tag-name').val('');
            }
        })
        .catch(function() { alert('Ошибка создания тега'); })
        .finally(function() { btn.prop('disabled', false); });
    });

    $(document).on('click', '.delete-tag', function(e){
        e.preventDefault();
        if (!confirm('Удалить тег?')) return;
        var id = $(this).data('id');
        var params = new URLSearchParams({ api: 'tasktags', f: 'remove', id: id });
        fetch('dispatch.php?' + params.toString(), { headers: {'X-Requested-With': 'XMLHttpRequest'} })
        .then(function(response) {
            if (!response.ok) throw new Error('Ошибка сети');
            return response.json();
        })
        .then(function(data) {
            if (data && data.success) {
                $('#tag-row-' + id).fadeOut(300, function(){ $(this).remove(); });
            }
        })
        .catch(function() {});
    });

    $('#add-field-btn').click(function(){
        var name = $.trim($('#new-field-name').val());
        var type = $('#new-field-type').val();
        if (!name) { alert('Введите название поля'); return; }

        var btn = $(this);
        btn.prop('disabled', true);
        var params = new URLSearchParams({ api: 'tasktags', f: 'addField', field_name: name, field_type: type, board_id: <?php echo $board ? $board->getId() : 0; ?> });
        fetch('dispatch.php?' + params.toString(), { headers: {'X-Requested-With': 'XMLHttpRequest'} })
        .then(function(response) {
            if (!response.ok) throw new Error('Ошибка сети');
            return response.json();
        })
        .then(function(data) {
            if (data && data.success) {
                $('.no-fields-msg').remove();
                var container = $('#board-fields-list .divide-y');
                if (container.length == 0) {
                    $('#board-fields-list').html('<div class="divide-y divide-gray-100"></div>');
                    container = $('#board-fields-list .divide-y');
                }
                container.append('<div class="flex items-center px-4 py-2" id="field-row-' + data.field_id + '"><span class="text-sm flex-1">' + data.field_name + '</span><small class="text-gray-500 mr-2">' + data.field_type + '</small><a href="#" class="text-red-500 delete-field" data-id="' + data.field_id + '" title="Удалить"><i data-lucide="trash-2" class="w-4 h-4"></i></a></div>');
                if (typeof lucide !== 'undefined') lucide.createIcons();
                $('#new-field-name').val('');
            }
        })
        .catch(function() { alert('Ошибка создания поля'); })
        .finally(function() { btn.prop('disabled', false); });
    });

    $(document).on('click', '.delete-field', function(e){
        e.preventDefault();
        if (!confirm('Удалить кастомное поле? Все значения будут потеряны.')) return;
        var id = $(this).data('id');
        var params = new URLSearchParams({ api: 'tasktags', f: 'removeField', id: id });
        fetch('dispatch.php?' + params.toString(), { headers: {'X-Requested-With': 'XMLHttpRequest'} })
        .then(function(response) {
            if (!response.ok) throw new Error('Ошибка сети');
            return response.json();
        })
        .then(function(data) {
            if (data && data.success) {
                $('#field-row-' + id).fadeOut(300, function(){ $(this).remove(); });
            }
        })
        .catch(function() {});
    });

    $('input[name="perm_type"]').change(function(){
        if ($(this).val() == 'staff') {
            $('#perm-staff-id').show();
            $('#perm-dept-id').hide();
        } else {
            $('#perm-staff-id').hide();
            $('#perm-dept-id').show();
        }
    });

    $('#add-perm-btn').click(function(){
        var permType = $('input[name="perm_type"]:checked').val();
        var staffId = (permType == 'staff') ? $('#perm-staff-id').val() : '';
        var deptId = (permType == 'dept') ? $('#perm-dept-id').val() : '';
        var level = $('#perm-level').val();

        if (permType == 'staff' && !staffId) { alert('Выберите сотрудника'); return; }
        if (permType == 'dept' && !deptId) { alert('Выберите отдел'); return; }

        var btn = $(this);
        btn.prop('disabled', true);
        var params = new URLSearchParams({
            api: 'taskpermissions',
            f: 'add',
            board_id: <?php echo $board ? $board->getId() : 0; ?>,
            perm_type: permType,
            staff_id: staffId,
            dept_id: deptId,
            permission_level: level
        });
        fetch('dispatch.php?' + params.toString(), { headers: {'X-Requested-With': 'XMLHttpRequest'} })
        .then(function(response) {
            if (!response.ok) throw new Error('Ошибка сети');
            return response.json();
        })
        .then(function(data) {
            if (data && data.success) {
                location.reload();
            }
        })
        .catch(function() { alert('Ошибка добавления права доступа'); })
        .finally(function() { btn.prop('disabled', false); });
    });

    $(document).on('click', '.delete-perm', function(e){
        e.preventDefault();
        if (!confirm('Удалить право доступа?')) return;
        var id = $(this).data('id');
        var params = new URLSearchParams({ api: 'taskpermissions', f: 'remove', id: id });
        fetch('dispatch.php?' + params.toString(), { headers: {'X-Requested-With': 'XMLHttpRequest'} })
        .then(function(response) {
            if (!response.ok) throw new Error('Ошибка сети');
            return response.json();
        })
        .then(function(data) {
            if (data && data.success) {
                $('#perm-row-' + id).fadeOut(300, function(){ $(this).remove(); });
            }
        })
        .catch(function() {});
    });
});
</script>
