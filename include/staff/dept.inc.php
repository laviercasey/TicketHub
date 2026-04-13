<?php
if(!defined('OSTADMININC') || !$thisuser->isadmin()) die('Доступ запрещён');
$info=array();
if($dept && ($_REQUEST['a'] ?? '') !='new'){
    $title='Редактирование отдела';
    $action='update';
    $info=$dept->getInfo();
}else {
    $title='Новый отдел';
    $action='create';
    $info['ispublic']=isset($info['ispublic'])?$info['ispublic']:1;
    $info['ticket_auto_response']=isset($info['ticket_auto_response'])?$info['ticket_auto_response']:1;
    $info['message_auto_response']=isset($info['message_auto_response'])?$info['message_auto_response']:1;
}
$info=($errors && $_POST)?Format::input($_POST):($info ? Format::htmlchars($info) : array());

?>
<h2 class="text-lg font-heading font-semibold text-gray-900 mb-4"><?=$title?></h2>
<form action="admin.php?t=dept&id=<?=$info['dept_id'] ?? ''?>" method="POST" name="dept">
 <?php echo Misc::csrfField(); ?>
 <input type="hidden" name="do" value="<?=$action?>">
 <input type="hidden" name="a" value="<?=Format::htmlchars($_REQUEST['a'] ?? '')?>">
 <input type="hidden" name="t" value="dept">
 <input type="hidden" name="dept_id" value="<?=$info['dept_id'] ?? ''?>">

<div class="card mb-6">
    <div class="card-header"><h3 class="font-heading font-semibold">Отдел</h3></div>
    <div class="card-body space-y-5">
        <p class="text-gray-500 text-sm">Отдел зависит от настроек email и тем обращений для входящих заявок.</p>

        <div class="form-group">
            <label class="label">Название Отдела:</label>
            <input class="input" type="text" name="dept_name" value="<?=$info['dept_name'] ?? ''?>">
            <span class="form-error">*&nbsp;<?=$errors['dept_name'] ?? ''?></span>
        </div>
        <div class="form-group">
            <label class="label">Email отдела:</label>
            <select class="select" name="email_id">
                <option value="">Выберите</option>
                <?
                $emails=db_query('SELECT email_id,email,name,smtp_active FROM '.EMAIL_TABLE);
                while (list($id,$email,$name,$smtp) = db_fetch_row($emails)){
                    $email=$name?"$name &lt;$email&gt;":$email;
                    if($smtp)
                        $email.=' (SMTP)';
                    ?>
                 <option value="<?=(int)$id?>"<?=(($info['email_id'] ?? '')==$id)?'selected':''?>><?=Format::htmlchars($email)?></option>
                <?
                }?>
             </select>
             <span class="form-error">*&nbsp;<?=$errors['email_id'] ?? ''?></span>&nbsp;<span class="form-hint">(исходящий email)</span>
        </div>
        <? if(!empty($info['dept_id'])) {
            $users= db_query('SELECT staff_id,CONCAT_WS(" ",firstname,lastname) as name FROM '.STAFF_TABLE.' WHERE dept_id='.db_input($info['dept_id']));
            ?>
        <div class="form-group">
            <label class="label">Руководитель отдела:</label>
            <?if($users && db_num_rows($users)) {?>
            <select class="select" name="manager_id">
                <option value="0">-------Нет-------</option>
                <option value="0" disabled>Выберите руководителя (необязательно)</option>
                 <?
                 while (list($id,$name) = db_fetch_row($users)){ ?>
                    <option value="<?=(int)$id?>"<?=(($info['manager_id'] ?? '')==$id)?'selected':''?>><?=Format::htmlchars($name)?></option>
                 <?}?>

            </select>
             <?}else {?>
                   <span class="text-gray-500 text-sm">Нет пользователей (Добавить)</span>
                   <input type="hidden" name="manager_id" value="0" />
             <?}?>
                <span class="form-error">&nbsp;<?=$errors['manager_id'] ?? ''?></span>
        </div>
        <?}?>
        <div class="form-group">
            <label class="label">Тип отдела</label>
            <div class="flex items-center gap-4">
                <label class="flex items-center gap-2"><input type="radio" class="radio" name="ispublic" value="1" <?=!empty($info['ispublic'])?'checked':''?> /><span class="text-sm">Публичный</span></label>
                <label class="flex items-center gap-2"><input type="radio" class="radio" name="ispublic" value="0" <?=empty($info['ispublic'])?'checked':''?> /><span class="text-sm">Приватный (Скрытый)</span></label>
            </div>
            <span class="form-error"><?=$errors['ispublic'] ?? ''?></span>
        </div>
        <div class="form-group">
            <label class="label">Подпись отдела:</label>
            <p class="form-hint">Обязательно для публичного отдела</p>
            <span class="form-error"><?=$errors['dept_signature'] ?? ''?></span>
            <textarea class="textarea" name="dept_signature" rows="5"><?=$info['dept_signature'] ?? ''?></textarea>
            <label class="flex items-center gap-2 mt-2">
                <input type="checkbox" class="checkbox" name="can_append_signature" <?=!empty($info['can_append_signature']) ?'checked':''?>>
                <span class="text-sm text-gray-700">может добавляться к ответам. (доступно как опция для публичных отделов)</span>
            </label>
        </div>
        <div class="form-group">
            <label class="label">Email шаблоны:</label>
            <select class="select" name="tpl_id">
                <option value="0" disabled>Выберите шаблон</option>
                <option value="0" selected="selected">По умолчанию</option>
                <?
                $templates=db_query('SELECT tpl_id,name FROM '.EMAIL_TEMPLATE_TABLE.' WHERE tpl_id!='.db_input($cfg->getDefaultTemplateId()));
                while (list($id,$name) = db_fetch_row($templates)){
                    $selected = (($info['tpl_id'] ?? '')==$id)?'SELECTED':''; ?>
                    <option value="<?=$id?>"<?=$selected?>><?=Format::htmlchars($name)?></option>
                <?
                }?>
            </select>
            <span class="form-error">&nbsp;<?=$errors['tpl_id'] ?? ''?></span>
            <p class="form-hint">Используется для исходящих писем, оповещений и уведомлений пользователям и сотрудникам.</p>
        </div>
    </div>
</div>

<div class="card mb-6">
    <div class="card-header"><h3 class="font-heading font-semibold">Автоответчики</h3></div>
    <div class="card-body space-y-5">
        <p class="text-gray-500 text-sm">
            Глобальные настройки автоответов в разделе параметров должны быть включены, чтобы настройка «Включить» для отдела вступила в силу.
        </p>

        <div class="form-group">
            <label class="label">Новая заявка:</label>
            <div class="flex items-center gap-4">
                <label class="flex items-center gap-2"><input type="radio" class="radio" name="ticket_auto_response" value="1" <?=!empty($info['ticket_auto_response'])?'checked':''?> /><span class="text-sm">Включить</span></label>
                <label class="flex items-center gap-2"><input type="radio" class="radio" name="ticket_auto_response" value="0" <?=empty($info['ticket_auto_response'])?'checked':''?> /><span class="text-sm">Отключить</span></label>
            </div>
        </div>
        <div class="form-group">
            <label class="label">Новое сообщение:</label>
            <div class="flex items-center gap-4">
                <label class="flex items-center gap-2"><input type="radio" class="radio" name="message_auto_response" value="1" <?=!empty($info['message_auto_response'])?'checked':''?> /><span class="text-sm">Включить</span></label>
                <label class="flex items-center gap-2"><input type="radio" class="radio" name="message_auto_response" value="0" <?=empty($info['message_auto_response'])?'checked':''?> /><span class="text-sm">Отключить</span></label>
            </div>
        </div>
        <div class="form-group">
            <label class="label">Email для автоответов:</label>
            <select class="select" name="autoresp_email_id">
                <option value="0" disabled>Выберите</option>
                <option value="0" selected="selected">Email отдела (см. выше)</option>
                <?
                $emails=db_query('SELECT email_id,email,name,smtp_active FROM '.EMAIL_TABLE.' WHERE email_id!='.db_input($info['email_id'] ?? 0));
                if($emails && db_num_rows($emails)) {
                    while (list($id,$email,$name,$smtp) = db_fetch_row($emails)){
                        $email=$name?"$name &lt;$email&gt;":$email;
                        if($smtp)
                            $email.=' (SMTP)';
                        ?>
                        <option value="<?=(int)$id?>"<?=(($info['autoresp_email_id'] ?? '')==$id)?'selected':''?>><?=Format::htmlchars($email)?></option>
                    <?
                    }
                }?>
             </select>
             <span class="form-error">&nbsp;<?=$errors['autoresp_email_id'] ?? ''?></span>
             <p class="form-hint">Email-адрес, используемый для отправки автоответов, если включено.</p>
        </div>
    </div>
</div>

<div class="flex items-center gap-3 mt-6">
    <button class="btn-primary" type="submit" name="submit" value="Отправить"><i data-lucide="save" class="w-4 h-4"></i> Отправить</button>
    <button class="btn-secondary" type="reset" name="reset">Очистить</button>
    <button class="btn-ghost" type="button" name="cancel" onClick='window.location.href="admin.php?t=dept"'>Отмена</button>
</div>
</form>
