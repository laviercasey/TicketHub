<?php
if(!defined('OSTSCPINC') or !$thisuser->canManageKb()) die('Доступ запрещён');
$info=($errors && $_POST)?Format::input($_POST):($answer ? Format::htmlchars($answer) : array());
if($answer && $_REQUEST['a']!='add'){
    $title='Редактировать шаблон ответа';
    $action='update';
}else {
    $title='Добавить шаблон ответа';
    $action='add';
    $info['isenabled']=1;
}
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

<form action="kb.php" method="POST" name="group">
    <?php echo Misc::csrfField(); ?>
    <input type="hidden" name="a" value="<?=$action?>">
    <input type="hidden" name="id" value="<?=$info['premade_id']?>">

<div class="card">
    <div class="card-header">
        <h2 class="font-heading font-semibold text-gray-900"><?=$title?></h2>
    </div>
    <div class="card-body space-y-5">
        <div class="form-group">
            <label class="label">Заголовок <span class="text-red-500">*</span></label>
            <input class="input" type="text" name="title" value="<?=$info['title']?>">
            <?php if($errors['title']) { ?><span class="form-error"><?=$errors['title']?></span><?php } ?>
        </div>

        <div class="form-group">
            <label class="label">Статус</label>
            <div class="flex items-center gap-4">
                <label class="flex items-center gap-1.5 text-sm text-gray-600">
                    <input type="radio" name="isenabled" value="1" class="radio" <?=$info['isenabled']?'checked':''?>> Активен
                </label>
                <label class="flex items-center gap-1.5 text-sm text-gray-600">
                    <input type="radio" name="isenabled" value="0" class="radio" <?=!$info['isenabled']?'checked':''?>> Отключён
                </label>
            </div>
            <?php if($errors['isenabled']) { ?><span class="form-error"><?=$errors['isenabled']?></span><?php } ?>
        </div>

        <div class="form-group">
            <label class="label">Отдел</label>
            <p class="text-xs text-gray-400 mb-1">Отдел, для которого доступен данный шаблон</p>
            <select class="select" name="dept_id">
                <option value="0" selected>Все отделы</option>
                <?php
                $depts= db_query('SELECT dept_id,dept_name FROM '.DEPT_TABLE.' ORDER BY dept_name');
                while (list($id,$name) = db_fetch_row($depts)){
                    $ck=($info['dept_id']==$id)?'selected':''; ?>
                    <option value="<?=$id?>" <?=$ck?>><?=$name?></option>
                <?php } ?>
            </select>
            <?php if($errors['depts']) { ?><span class="form-error"><?=$errors['depts']?></span><?php } ?>
        </div>

        <div class="form-group">
            <label class="label">Ответ <span class="text-red-500">*</span></label>
            <p class="text-xs text-gray-400 mb-1">Поддерживаются переменные заявок</p>
            <textarea class="textarea" name="answer" id="answer" rows="9"><?=Format::htmlchars($info['answer'])?></textarea>
            <?php if($errors['answer']) { ?><span class="form-error"><?=Format::htmlchars($errors['answer'])?></span><?php } ?>
        </div>
    </div>
</div>

<div class="flex items-center gap-3 mt-6">
    <button class="btn-primary" type="submit" name="submit">
        <i data-lucide="save" class="w-4 h-4"></i> Сохранить
    </button>
    <button class="btn-secondary" type="button" onclick="this.closest('form').querySelectorAll('input[type=text],textarea').forEach(function(el){el.value=''});">Очистить</button>
    <button class="btn-ghost" type="button" onclick='window.location.href="kb.php"'>Отмена</button>
</div>
</form>
