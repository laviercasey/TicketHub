<?php
if(!defined('OSTADMININC') || !$thisuser->isadmin()) die('Доступ запрещён');

$info=($_POST && $errors)?Format::input($_POST):array(); //Re-use the post info on error...savekeyboards.org
if($topic && ($_REQUEST['a'] ?? '') !='new'){
    $title='Edit Topic';
    $action='update';
    $info=$info?$info:$topic->getInfo();
}else {
   $title='New Help Topic';
   $action='create';
   $info['isactive']=isset($info['isactive'])?$info['isactive']:1;
}
$depts= db_query('SELECT dept_id,dept_name FROM '.DEPT_TABLE);
$priorities= db_query('SELECT priority_id,priority_desc FROM '.TICKET_PRIORITY_TABLE);
?>
<form action="admin.php?t=topics" method="post">
 <?php echo Misc::csrfField(); ?>
 <input type="hidden" name="do" value="<?=$action?>">
 <input type="hidden" name="a" value="<?=Format::htmlchars($_REQUEST['a'] ?? '')?>">
 <input type="hidden" name="t" value="topics">
 <input type="hidden" name="topic_id" value="<?=$info['topic_id'] ?? ''?>">

<div class="card mb-6">
    <div class="card-header"><h3 class="font-heading font-semibold"><?=$title?></h3></div>
    <div class="card-body space-y-5">
        <p class="text-gray-500 text-sm">Disabling auto response will overwrite dept settings.</p>

        <div class="form-group">
            <label class="label">Тема Обращения:</label>
            <input class="input" type="text" name="topic" value="<?=$info['topic'] ?? ''?>">
            <span class="form-error">*&nbsp;<?=$errors['topic'] ?? ''?></span>
        </div>
        <div class="form-group">
            <label class="label">Статус</label>
            <div class="flex items-center gap-4">
                <label class="flex items-center gap-2"><input type="radio" class="radio" name="isactive" value="1" <?=!empty($info['isactive'])?'checked':''?> /><span class="text-sm">Активно</span></label>
                <label class="flex items-center gap-2"><input type="radio" class="radio" name="isactive" value="0" <?=empty($info['isactive'])?'checked':''?> /><span class="text-sm">Отключено</span></label>
            </div>
        </div>
        <div class="form-group">
            <label class="label">Автоответ:</label>
            <label class="flex items-center gap-2">
                <input type="checkbox" class="checkbox" name="noautoresp" value="1" <?=!empty($info['noautoresp'])? 'checked': ''?>>
                <span class="text-sm text-gray-700"><b>Отключить</b> автоответчик для этой темы. (<i>Overwrite Dept setting</i>)</span>
            </label>
        </div>
        <div class="form-group">
            <label class="label">Приоритет новой Заявки:</label>
            <select class="select" name="priority_id">
                <option value="0">Выберите приоритет</option>
                <?
                while (list($id,$name) = db_fetch_row($priorities)){
                    $selected = (($info['priority_id'] ?? '')==$id)?'selected':''; ?>
                    <option value="<?=$id?>"<?=$selected?>><?=$name?></option>
                <?
                }?>
            </select>
            <span class="form-error">*&nbsp;<?=$errors['priority_id'] ?? ''?></span>
        </div>
        <div class="form-group">
            <label class="label">Отдел для новой заявки:</label>
            <select class="select" name="dept_id">
                <option value="0">Выберите отдел</option>
                <?
                while (list($id,$name) = db_fetch_row($depts)){
                    $selected = (($info['dept_id'] ?? '')==$id)?'selected':''; ?>
                    <option value="<?=$id?>"<?=$selected?>><?=$name?></option>
                <?
                }?>
            </select>
            <span class="form-error">*&nbsp;<?=$errors['dept_id'] ?? ''?></span>
        </div>
    </div>
</div>

<div class="flex items-center gap-3 mt-6">
    <button class="btn-primary" type="submit" name="submit" value="Отправить"><i data-lucide="save" class="w-4 h-4"></i> Отправить</button>
    <button class="btn-secondary" type="reset" name="reset">Очистить</button>
    <button class="btn-ghost" type="button" name="cancel" onClick='window.location.href="admin.php?t=topics"'>Отмена</button>
</div>
</form>
