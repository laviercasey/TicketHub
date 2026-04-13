<?php
if(!defined('OSTADMININC') || !$thisuser->isadmin()) die('Доступ запрещён');

$info = array();
$action = 'create';
$title = 'Добавить приоритетного пользователя';
$submit_text = 'Добавить';

if ($priorityuser && is_object($priorityuser) && $priorityuser->getId()) {
    $title = 'Редактировать приоритетного пользователя';
    $action = 'update';
    $submit_text = 'Сохранить';
    $info = array(
        'id'          => $priorityuser->getId(),
        'email'       => $priorityuser->getEmail(),
        'description' => $priorityuser->getDescription(),
        'is_active'   => $priorityuser->isActive()
    );
}

if ($_POST) {
    $info = array_merge($info, $_POST);
}
?>
<div class="card">
    <div class="card-header">
        <h2 class="text-lg font-heading font-semibold text-gray-900"><?=$title?></h2>
    </div>
    <div class="card-body">
<form action="admin.php?t=priorityusers" method="POST" name="priorityuser">
    <?php echo Misc::csrfField(); ?>
    <input type="hidden" name="do" value="<?=$action?>">
    <input type="hidden" name="a" value="priorityusers">
    <?php if ($action == 'update') { ?>
        <input type="hidden" name="pu_id" value="<?=$priorityuser->getId()?>">
    <?php } ?>
    <div class="space-y-5">
        <div class="form-group">
            <label class="label">Email адрес:</label>
            <input type="text" name="email" class="input w-full max-w-md" value="<?=Format::htmlchars($info['email'] ?? '')?>">
            <?php if (!empty($errors['email'])) { ?><span class="form-error"><?=$errors['email']?></span><?php } ?>
            <p class="form-hint">Email адрес из заявки для определения приоритетного пользователя</p>
        </div>
        <div class="form-group">
            <label class="label">Описание:</label>
            <input type="text" name="description" class="input w-full max-w-lg" value="<?=Format::htmlchars($info['description'] ?? '')?>">
            <p class="form-hint">Необязательное описание (например, имя клиента или компания)</p>
        </div>
        <div class="form-group">
            <label class="label">Активен:</label>
            <label class="flex items-center gap-2">
                <input type="checkbox" class="checkbox" name="is_active" value="1" <?=(!isset($info['is_active']) || $info['is_active']) ? 'checked' : ''?>>
                <span class="text-sm text-gray-700">Включить приоритетную подсветку для заявок этого пользователя</span>
            </label>
        </div>
    </div>
    <div class="flex items-center gap-3 mt-6">
        <button class="btn-primary" type="submit">
            <i data-lucide="save" class="w-4 h-4"></i> <?=$submit_text?>
        </button>
        <button class="btn-ghost" type="button" onclick="window.location='admin.php?t=priorityusers'">Отмена</button>
    </div>
</form>
    </div>
</div>
