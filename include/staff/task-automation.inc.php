<?php
if(!defined('OSTSCPINC') || !is_object($thisuser)) die('Доступ запрещён');

require_once(INCLUDE_DIR.'class.taskautomation.php');
require_once(INCLUDE_DIR.'class.taskboard.php');
require_once(INCLUDE_DIR.'class.tasktag.php');

if (!$board) {
    echo '<div class="alert-danger">Доска не найдена</div>';
    return;
}

$rules = TaskAutomation::getByBoard($board->getId());
$triggerLabels = TaskAutomation::getTriggerLabels();
$actionLabels = TaskAutomation::getActionLabels();
$statusLabels = Task::getStatusLabels();
$priorityLabels = Task::getPriorityLabels();
$lists = $board->getLists();
$boardTags = TaskTag::getByBoard($board->getId());

$staffList = array();
$sSql = 'SELECT staff_id, firstname, lastname FROM ' . TABLE_PREFIX . 'staff WHERE isactive=1 ORDER BY firstname, lastname';
if (($sRes = db_query($sSql)) && db_num_rows($sRes)) {
    while ($sRow = db_fetch_array($sRes)) {
        $staffList[] = $sRow;
    }
}
?>

<?php if($msg) { ?>
<div class="alert-success"><?php echo Format::htmlchars($msg); ?></div>
<?php } elseif(!empty($errors['err'])) { ?>
<div class="alert-danger"><?php echo Format::htmlchars($errors['err']); ?></div>
<?php } ?>

<div class="flex items-center gap-2 mb-3">
    <a href="taskboards.php?id=<?php echo $board->getId(); ?>" class="btn-secondary btn-sm"><i data-lucide="arrow-left" class="w-4 h-4"></i> К настройкам доски</a>
    <a href="tasks.php?board_id=<?php echo $board->getId(); ?>" class="btn-secondary btn-sm"><i data-lucide="list" class="w-4 h-4"></i> Задачи доски</a>
</div>

<h2 class="text-2xl font-heading font-bold mb-1 flex items-center gap-2"><i data-lucide="zap" class="w-6 h-6"></i> Автоматизация: <?php echo Format::htmlchars($board->getName()); ?></h2>
<p class="text-gray-500 text-sm mb-6">Правила автоматизации выполняются при наступлении триггерных событий для задач этой доски.</p>

<div class="card mb-6">
    <div class="card-header flex items-center gap-2">
        <strong class="flex items-center gap-2"><i data-lucide="list" class="w-4 h-4"></i> Правила автоматизации</strong>
        <span class="badge-secondary" id="rules-count"><?php echo count($rules); ?></span>
    </div>
    <div class="card-body p-0">
        <div class="table-wrapper">
        <table class="table-modern" id="automation-rules-table">
            <thead>
                <tr>
                    <th class="table-th">Название</th>
                    <th class="table-th">Триггер</th>
                    <th class="table-th">Действие</th>
                    <th class="table-th w-20 text-center">Активно</th>
                    <th class="table-th w-16"></th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($rules) > 0) { ?>
                <?php foreach ($rules as $r) { ?>
                <tr id="rule-row-<?php echo $r['rule_id']; ?>">
                    <td class="table-td font-medium"><?php echo Format::htmlchars($r['rule_name']); ?></td>
                    <td class="table-td">
                        <span class="badge-info"><?php echo isset($triggerLabels[$r['trigger_type']]) ? $triggerLabels[$r['trigger_type']] : $r['trigger_type']; ?></span>
                        <?php
                        $tc = $r['trigger_config_arr'];
                        if (is_array($tc) && count($tc) > 0) {
                            $details = array();
                            if (isset($tc['from_status']) && $tc['from_status'] !== '') {
                                $details[] = 'из: ' . (isset($statusLabels[$tc['from_status']]) ? $statusLabels[$tc['from_status']] : $tc['from_status']);
                            }
                            if (isset($tc['to_status']) && $tc['to_status'] !== '') {
                                $details[] = 'в: ' . (isset($statusLabels[$tc['to_status']]) ? $statusLabels[$tc['to_status']] : $tc['to_status']);
                            }
                            if (isset($tc['from_priority']) && $tc['from_priority'] !== '') {
                                $details[] = 'из: ' . (isset($priorityLabels[$tc['from_priority']]) ? $priorityLabels[$tc['from_priority']] : $tc['from_priority']);
                            }
                            if (isset($tc['to_priority']) && $tc['to_priority'] !== '') {
                                $details[] = 'в: ' . (isset($priorityLabels[$tc['to_priority']]) ? $priorityLabels[$tc['to_priority']] : $tc['to_priority']);
                            }
                            if (isset($tc['days_before'])) {
                                $details[] = 'за ' . intval($tc['days_before']) . ' дн.';
                            }
                            if (count($details) > 0) {
                                echo ' <small class="text-gray-500">(' . implode(', ', $details) . ')</small>';
                            }
                        }
                        ?>
                    </td>
                    <td class="table-td">
                        <span class="badge-warning"><?php echo isset($actionLabels[$r['action_type']]) ? $actionLabels[$r['action_type']] : $r['action_type']; ?></span>
                        <?php
                        $ac = $r['action_config_arr'];
                        if (is_array($ac) && count($ac) > 0) {
                            $adetails = array();
                            if (isset($ac['status'])) {
                                $adetails[] = isset($statusLabels[$ac['status']]) ? $statusLabels[$ac['status']] : $ac['status'];
                            }
                            if (isset($ac['priority'])) {
                                $adetails[] = isset($priorityLabels[$ac['priority']]) ? $priorityLabels[$ac['priority']] : $ac['priority'];
                            }
                            if (isset($ac['staff_id']) && intval($ac['staff_id'])) {
                                $stSql = 'SELECT CONCAT(firstname," ",lastname) as name FROM ' . TABLE_PREFIX . 'staff WHERE staff_id=' . db_input($ac['staff_id']);
                                if (($stRes = db_query($stSql)) && db_num_rows($stRes)) {
                                    $stRow = db_fetch_array($stRes);
                                    $adetails[] = $stRow['name'];
                                }
                            }
                            if (isset($ac['list_id']) && intval($ac['list_id'])) {
                                $lSql = 'SELECT list_name FROM ' . TASK_LISTS_TABLE . ' WHERE list_id=' . db_input($ac['list_id']);
                                if (($lRes = db_query($lSql)) && db_num_rows($lRes)) {
                                    $lRow = db_fetch_array($lRes);
                                    $adetails[] = $lRow['list_name'];
                                }
                            }
                            if (isset($ac['tag_id']) && intval($ac['tag_id'])) {
                                $tSql = 'SELECT tag_name FROM ' . TASK_TAGS_TABLE . ' WHERE tag_id=' . db_input($ac['tag_id']);
                                if (($tRes = db_query($tSql)) && db_num_rows($tRes)) {
                                    $tRow = db_fetch_array($tRes);
                                    $adetails[] = $tRow['tag_name'];
                                }
                            }
                            if (count($adetails) > 0) {
                                echo ' <small class="text-gray-500">(' . Format::htmlchars(implode(', ', $adetails)) . ')</small>';
                            }
                        }
                        ?>
                    </td>
                    <td class="table-td text-center">
                        <a href="#" class="toggle-rule" data-id="<?php echo $r['rule_id']; ?>" title="Переключить">
                            <?php if ($r['is_enabled']) { ?>
                            <i data-lucide="toggle-right" class="w-6 h-6 text-green-500"></i>
                            <?php } else { ?>
                            <i data-lucide="toggle-left" class="w-6 h-6 text-gray-400"></i>
                            <?php } ?>
                        </a>
                    </td>
                    <td class="table-td text-right">
                        <a href="#" class="text-red-500 delete-rule" data-id="<?php echo $r['rule_id']; ?>" title="Удалить"><i data-lucide="trash-2" class="w-4 h-4"></i></a>
                    </td>
                </tr>
                <?php } ?>
                <?php } else { ?>
                <tr class="no-rules-row">
                    <td colspan="5" class="table-td">
                        <div class="empty-state py-6">
                            <div class="empty-state-text">Правил автоматизации нет</div>
                        </div>
                    </td>
                </tr>
                <?php } ?>
            </tbody>
        </table>
        </div>
    </div>
</div>

<div class="card border-indigo-200">
    <div class="card-header bg-indigo-50">
        <strong class="flex items-center gap-2 text-indigo-700"><i data-lucide="plus" class="w-4 h-4"></i> Новое правило</strong>
    </div>
    <div class="card-body">
        <form id="add-rule-form">
            <input type="hidden" name="board_id" value="<?php echo $board->getId(); ?>">

            <div class="form-group mb-4">
                <label class="label">Название правила <span class="text-red-500">*</span></label>
                <input type="text" name="rule_name" id="rule_name" class="input" placeholder="Например: Автозавершение при переносе в Готово" required>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <div class="card">
                        <div class="card-header"><strong>Триггер (Когда)</strong></div>
                        <div class="card-body space-y-4">
                            <div class="form-group">
                                <label class="label">Тип триггера</label>
                                <select name="trigger_type" id="trigger_type" class="select">
                                    <option value="">Выберите</option>
                                    <?php foreach ($triggerLabels as $tk => $tv) { ?>
                                    <option value="<?php echo $tk; ?>"><?php echo Format::htmlchars($tv); ?></option>
                                    <?php } ?>
                                </select>
                            </div>

                            <div class="trigger-config" id="tc-status_changed" style="display:none;">
                                <div class="form-group">
                                    <label class="label">Из статуса <small class="text-gray-500">(необязательно)</small></label>
                                    <select name="from_status" class="select">
                                        <option value="">Любой</option>
                                        <?php foreach ($statusLabels as $sk => $sv) { ?>
                                        <option value="<?php echo $sk; ?>"><?php echo Format::htmlchars($sv); ?></option>
                                        <?php } ?>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label class="label">В статус <small class="text-gray-500">(необязательно)</small></label>
                                    <select name="to_status" class="select">
                                        <option value="">Любой</option>
                                        <?php foreach ($statusLabels as $sk => $sv) { ?>
                                        <option value="<?php echo $sk; ?>"><?php echo Format::htmlchars($sv); ?></option>
                                        <?php } ?>
                                    </select>
                                </div>
                            </div>

                            <div class="trigger-config" id="tc-priority_changed" style="display:none;">
                                <div class="form-group">
                                    <label class="label">Из приоритета <small class="text-gray-500">(необязательно)</small></label>
                                    <select name="from_priority" class="select">
                                        <option value="">Любой</option>
                                        <?php foreach ($priorityLabels as $pk => $pv) { ?>
                                        <option value="<?php echo $pk; ?>"><?php echo Format::htmlchars($pv); ?></option>
                                        <?php } ?>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label class="label">В приоритет <small class="text-gray-500">(необязательно)</small></label>
                                    <select name="to_priority" class="select">
                                        <option value="">Любой</option>
                                        <?php foreach ($priorityLabels as $pk => $pv) { ?>
                                        <option value="<?php echo $pk; ?>"><?php echo Format::htmlchars($pv); ?></option>
                                        <?php } ?>
                                    </select>
                                </div>
                            </div>

                            <div class="trigger-config" id="tc-deadline_passed" style="display:none;">
                                <div class="form-group">
                                    <label class="label">За сколько дней до дедлайна</label>
                                    <input type="number" name="days_before" class="input" min="0" value="0" placeholder="0 = в день дедлайна">
                                    <span class="form-hint">0 = когда дедлайн наступил</span>
                                </div>
                            </div>

                            <div class="trigger-config" id="tc-task_created" style="display:none;">
                                <p class="text-gray-500 text-sm flex items-center gap-1"><i data-lucide="info" class="w-4 h-4"></i> Дополнительных настроек не требуется</p>
                            </div>
                            <div class="trigger-config" id="tc-task_completed" style="display:none;">
                                <p class="text-gray-500 text-sm flex items-center gap-1"><i data-lucide="info" class="w-4 h-4"></i> Дополнительных настроек не требуется</p>
                            </div>
                            <div class="trigger-config" id="tc-assignee_changed" style="display:none;">
                                <p class="text-gray-500 text-sm flex items-center gap-1"><i data-lucide="info" class="w-4 h-4"></i> Дополнительных настроек не требуется</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div>
                    <div class="card">
                        <div class="card-header"><strong>Действие (Тогда)</strong></div>
                        <div class="card-body space-y-4">
                            <div class="form-group">
                                <label class="label">Тип действия</label>
                                <select name="action_type" id="action_type" class="select">
                                    <option value="">Выберите</option>
                                    <?php foreach ($actionLabels as $ak => $av) { ?>
                                    <option value="<?php echo $ak; ?>"><?php echo Format::htmlchars($av); ?></option>
                                    <?php } ?>
                                </select>
                            </div>

                            <div class="action-config" id="ac-change_status" style="display:none;">
                                <div class="form-group">
                                    <label class="label">Установить статус</label>
                                    <select name="action_status" class="select">
                                        <?php foreach ($statusLabels as $sk => $sv) { ?>
                                        <option value="<?php echo $sk; ?>"><?php echo Format::htmlchars($sv); ?></option>
                                        <?php } ?>
                                    </select>
                                </div>
                            </div>

                            <div class="action-config" id="ac-change_priority" style="display:none;">
                                <div class="form-group">
                                    <label class="label">Установить приоритет</label>
                                    <select name="action_priority" class="select">
                                        <?php foreach ($priorityLabels as $pk => $pv) { ?>
                                        <option value="<?php echo $pk; ?>"><?php echo Format::htmlchars($pv); ?></option>
                                        <?php } ?>
                                    </select>
                                </div>
                            </div>

                            <div class="action-config" id="ac-assign_to" style="display:none;">
                                <div class="form-group">
                                    <label class="label">Назначить на сотрудника</label>
                                    <select name="action_staff_id" class="select">
                                        <option value="">Выберите</option>
                                        <?php foreach ($staffList as $st) { ?>
                                        <option value="<?php echo $st['staff_id']; ?>"><?php echo Format::htmlchars(trim($st['firstname'] . ' ' . $st['lastname'])); ?></option>
                                        <?php } ?>
                                    </select>
                                </div>
                            </div>

                            <div class="action-config" id="ac-move_to_list" style="display:none;">
                                <div class="form-group">
                                    <label class="label">Переместить в список</label>
                                    <select name="action_list_id" class="select">
                                        <option value="">Выберите</option>
                                        <?php foreach ($lists as $l) { ?>
                                        <option value="<?php echo $l['list_id']; ?>"><?php echo Format::htmlchars($l['list_name']); ?></option>
                                        <?php } ?>
                                    </select>
                                </div>
                            </div>

                            <div class="action-config" id="ac-add_tag" style="display:none;">
                                <div class="form-group">
                                    <label class="label">Добавить тег</label>
                                    <select name="action_tag_id" class="select">
                                        <option value="">Выберите</option>
                                        <?php foreach ($boardTags as $bt) { ?>
                                        <option value="<?php echo $bt['tag_id']; ?>"><?php echo Format::htmlchars($bt['tag_name']); ?></option>
                                        <?php } ?>
                                    </select>
                                </div>
                            </div>

                            <div class="action-config" id="ac-send_notification" style="display:none;">
                                <div class="form-group">
                                    <label class="label">Текст уведомления</label>
                                    <input type="text" name="action_message" class="input" placeholder="Текст уведомления...">
                                    <span class="form-hint">Уведомление будет записано в журнал активности</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="mt-4">
                <button type="submit" class="btn-primary" id="save-rule-btn"><i data-lucide="save" class="w-4 h-4"></i> Сохранить правило</button>
            </div>
        </form>
    </div>
</div>

<script type="text/javascript">
$(document).ready(function(){

    $('#trigger_type').change(function(){
        $('.trigger-config').hide();
        var val = $(this).val();
        if (val) {
            $('#tc-' + val).show();
        }
    });

    $('#action_type').change(function(){
        $('.action-config').hide();
        var val = $(this).val();
        if (val) {
            $('#ac-' + val).show();
        }
    });

    $('#add-rule-form').submit(function(e){
        e.preventDefault();
        var name = $.trim($('#rule_name').val());
        var trigger = $('#trigger_type').val();
        var action = $('#action_type').val();

        if (!name) { alert('Введите название правила'); return; }
        if (!trigger) { alert('Выберите тип триггера'); return; }
        if (!action) { alert('Выберите тип действия'); return; }

        var params = {
            api: 'taskautomation',
            f: 'add',
            board_id: <?php echo $board->getId(); ?>,
            rule_name: name,
            trigger_type: trigger,
            action_type: action
        };

        if (trigger == 'status_changed') {
            params.from_status = $('select[name="from_status"]').val();
            params.to_status = $('select[name="to_status"]').val();
        } else if (trigger == 'priority_changed') {
            params.from_priority = $('select[name="from_priority"]').val();
            params.to_priority = $('select[name="to_priority"]').val();
        } else if (trigger == 'deadline_passed') {
            params.days_before = $('input[name="days_before"]').val();
        }

        if (action == 'change_status') {
            params.action_status = $('select[name="action_status"]').val();
        } else if (action == 'change_priority') {
            params.action_priority = $('select[name="action_priority"]').val();
        } else if (action == 'assign_to') {
            params.action_staff_id = $('select[name="action_staff_id"]').val();
        } else if (action == 'move_to_list') {
            params.action_list_id = $('select[name="action_list_id"]').val();
        } else if (action == 'add_tag') {
            params.action_tag_id = $('select[name="action_tag_id"]').val();
        } else if (action == 'send_notification') {
            params.action_message = $('input[name="action_message"]').val();
        }

        var btn = $('#save-rule-btn');
        btn.prop('disabled', true);

        var queryParams = new URLSearchParams(params);
        fetch('dispatch.php?' + queryParams.toString(), { headers: {'X-Requested-With': 'XMLHttpRequest'} })
        .then(function(response) {
            if (!response.ok) {
                return response.text().then(function(text) {
                    var err = new Error('Ошибка сети');
                    err.responseText = text;
                    throw err;
                });
            }
            return response.json();
        })
        .then(function(data) {
            if (data && data.success) {
                location.reload();
            } else {
                alert('Ошибка создания правила');
            }
        })
        .catch(function(err) {
            var msg = 'Ошибка создания правила';
            try {
                var resp = JSON.parse(err.responseText);
                if (resp.errors) {
                    var errs = [];
                    for (var k in resp.errors) { errs.push(resp.errors[k]); }
                    msg = errs.join(', ');
                }
            } catch(e) {}
            alert(msg);
        })
        .finally(function() { btn.prop('disabled', false); });
    });

    $(document).on('click', '.toggle-rule', function(e){
        e.preventDefault();
        var id = $(this).data('id');
        var link = $(this);
        var params = new URLSearchParams({ api: 'taskautomation', f: 'toggle', id: id });
        fetch('dispatch.php?' + params.toString(), { headers: {'X-Requested-With': 'XMLHttpRequest'} })
        .then(function(response) {
            if (!response.ok) throw new Error('Ошибка сети');
            return response.json();
        })
        .then(function(data) {
            if (data && data.success) {
                if (data.is_enabled) {
                    link.html('<i data-lucide="toggle-right" class="w-6 h-6 text-green-500"></i>');
                } else {
                    link.html('<i data-lucide="toggle-left" class="w-6 h-6 text-gray-400"></i>');
                }
                if (typeof lucide !== 'undefined') lucide.createIcons();
            }
        })
        .catch(function() {});
    });

    $(document).on('click', '.delete-rule', function(e){
        e.preventDefault();
        if (!confirm('Удалить правило автоматизации?')) return;
        var id = $(this).data('id');
        var params = new URLSearchParams({ api: 'taskautomation', f: 'remove', id: id });
        fetch('dispatch.php?' + params.toString(), { headers: {'X-Requested-With': 'XMLHttpRequest'} })
        .then(function(response) {
            if (!response.ok) throw new Error('Ошибка сети');
            return response.json();
        })
        .then(function(data) {
            if (data && data.success) {
                $('#rule-row-' + id).fadeOut(300, function(){
                    $(this).remove();
                    var cnt = $('#automation-rules-table tbody tr').not('.no-rules-row').length;
                    $('#rules-count').text(cnt);
                    if (cnt == 0) {
                        $('#automation-rules-table tbody').append('<tr class="no-rules-row"><td colspan="5" class="table-td"><div class="empty-state py-6"><div class="empty-state-text">Правил автоматизации нет</div></div></td></tr>');
                    }
                });
            }
        })
        .catch(function() {});
    });
});
</script>
