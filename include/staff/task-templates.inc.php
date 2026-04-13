<?php
if(!defined('OSTSCPINC') || !is_object($thisuser)) die('Доступ запрещён');

require_once(INCLUDE_DIR.'class.tasktemplate.php');

$typeLabels = TaskTemplate::getTypeLabels();

$filterType = isset($_REQUEST['tpl_type']) ? $_REQUEST['tpl_type'] : '';
$templates = TaskTemplate::getAll($filterType ? $filterType : null);
?>

<?php if($msg) { ?>
<div id="infomessage" class="alert-success"><?php echo Format::htmlchars($msg); ?></div>
<?php } elseif(!empty($errors['err'])) { ?>
<div id="errormessage" class="alert-danger"><?php echo Format::htmlchars($errors['err']); ?></div>
<?php } ?>

<div class="flex items-center gap-2 mb-4">
    <a href="tasks.php" class="btn-secondary btn-sm"><i data-lucide="arrow-left" class="w-4 h-4"></i> К задачам</a>
</div>

<h2 class="text-2xl font-heading font-bold mb-4 flex items-center gap-2"><i data-lucide="copy" class="w-6 h-6"></i> Шаблоны задач</h2>

<div class="mb-4">
    <div class="inline-flex rounded-lg border border-gray-200 overflow-hidden">
        <a href="tasks.php?display=templates" class="btn-sm <?php echo !$filterType ? 'btn-primary' : 'btn-secondary'; ?>">Все</a>
        <?php foreach ($typeLabels as $tk => $tv) { ?>
        <a href="tasks.php?display=templates&tpl_type=<?php echo $tk; ?>" class="btn-sm <?php echo ($filterType == $tk) ? 'btn-primary' : 'btn-secondary'; ?>"><?php echo Format::htmlchars($tv); ?></a>
        <?php } ?>
    </div>
</div>

<?php if (count($templates) > 0) { ?>
<div class="table-wrapper">
<table class="table-modern">
    <thead>
        <tr>
            <th class="table-th">Название</th>
            <th class="table-th w-[120px]">Тип</th>
            <th class="table-th w-[180px]">Создал</th>
            <th class="table-th w-[140px]">Создан</th>
            <th class="table-th w-[160px]">Действия</th>
        </tr>
    </thead>
    <tbody>
    <?php foreach ($templates as $tpl) { ?>
        <tr id="tpl-row-<?php echo $tpl['template_id']; ?>">
            <td class="table-td">
                <strong class="text-gray-800"><?php echo Format::htmlchars($tpl['template_name']); ?></strong>
            </td>
            <td class="table-td">
                <span class="badge-secondary"><?php echo Format::htmlchars($typeLabels[$tpl['template_type']]); ?></span>
            </td>
            <td class="table-td text-sm text-gray-600"><?php echo Format::htmlchars($tpl['creator_name']); ?></td>
            <td class="table-td text-sm text-gray-600"><?php echo date('d.m.Y H:i', strtotime($tpl['created'])); ?></td>
            <td class="table-td">
                <div class="flex items-center gap-2">
                    <button type="button" class="btn-primary btn-sm use-template" data-id="<?php echo $tpl['template_id']; ?>">
                        <i data-lucide="plus" class="w-4 h-4"></i> Создать задачу
                    </button>
                    <button type="button" class="btn-danger btn-sm delete-template" data-id="<?php echo $tpl['template_id']; ?>">
                        <i data-lucide="trash-2" class="w-4 h-4"></i>
                    </button>
                </div>
            </td>
        </tr>
    <?php } ?>
    </tbody>
</table>
</div>
<?php } else { ?>
<div class="alert-info">
    <i data-lucide="info" class="w-4 h-4 inline-block"></i> Шаблонов пока нет. Вы можете сохранить задачу как шаблон из карточки задачи.
</div>
<?php } ?>

<script type="text/javascript">
$(document).ready(function(){
    $('.use-template').click(function(){
        var tplId = $(this).data('id');
        var btn = $(this);
        btn.prop('disabled', true);

        var params = new URLSearchParams({ api: 'taskrecurring', f: 'createFromTemplate', template_id: tplId });
        fetch('dispatch.php?' + params.toString(), { headers: {'X-Requested-With': 'XMLHttpRequest'} })
        .then(function(response) {
            if (!response.ok) throw new Error('Ошибка сети');
            return response.json();
        })
        .then(function(data) {
            if (data && data.success && data.task_id) {
                window.location.href = 'tasks.php?id=' + data.task_id + '&msg=' + encodeURIComponent('Задача создана из шаблона');
            } else {
                alert(data && data.error ? data.error : 'Ошибка создания задачи из шаблона');
                btn.prop('disabled', false);
            }
        })
        .catch(function() {
            alert('Ошибка сервера');
            btn.prop('disabled', false);
        });
    });

    $('.delete-template').click(function(){
        if (!confirm('Удалить шаблон?')) return;
        var tplId = $(this).data('id');
        var $row = $('#tpl-row-' + tplId);

        var params = new URLSearchParams({ api: 'taskrecurring', f: 'deleteTemplate', template_id: tplId });
        fetch('dispatch.php?' + params.toString(), { headers: {'X-Requested-With': 'XMLHttpRequest'} })
        .then(function(response) {
            if (!response.ok) throw new Error('Ошибка сети');
            return response.json();
        })
        .then(function(data) {
            if (data && data.success) {
                $row.fadeOut(300, function(){ $(this).remove(); });
            } else {
                alert(data && data.error ? data.error : 'Ошибка удаления');
            }
        })
        .catch(function() {
            alert('Ошибка сервера');
        });
    });
});
</script>
