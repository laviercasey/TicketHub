<?php
if(!defined('OSTADMININC') || !$thisuser->isadmin()) die('Доступ запрещён');

$info=($errors && $_POST)?Format::input($_POST):($group?Format::htmlchars($group):array());
if($group && ($_REQUEST['a'] ?? '') !='new'){
    $title='Изменить группу: '.$group['group_name'];
    $action='update';
}else {
    $title='Добавить новую группу';
    $action='create';
    $info['group_enabled']=isset($info['group_enabled'])?$info['group_enabled']:1; //Default to active
}

?>
<form action="admin.php" method="POST" name="group">
 <?php echo Misc::csrfField(); ?>
 <input type="hidden" name="do" value="<?=$action?>">
 <input type="hidden" name="a" value="<?=Format::htmlchars($_REQUEST['a'] ?? '')?>">
 <input type="hidden" name="t" value="groups">
 <input type="hidden" name="group_id" value="<?=$info['group_id'] ?? ''?>">
 <input type="hidden" name="old_name" value="<?=$info['group_name'] ?? ''?>">

<div class="card mb-6">
    <div class="card-header"><h3 class="font-heading font-semibold"><?=Format::htmlchars($title)?></h3></div>
    <div class="card-body space-y-5">
        <p class="text-gray-500 text-sm">
            Настройки прав группы ниже применяются ко всем участникам группы, но не распространяются на администраторов и руководителей отделов в некоторых случаях.
        </p>

        <div class="form-group">
            <label class="label">Имя группы:</label>
            <input class="input" type="text" name="group_name" value="<?=$info['group_name'] ?? ''?>">
            <span class="form-error">*&nbsp;<?=$errors['group_name'] ?? ''?></span>
        </div>
        <div class="form-group">
            <label class="label">Статус группы:</label>
            <div class="flex items-center gap-4">
                <label class="flex items-center gap-2"><input type="radio" class="radio" name="group_enabled" value="1" <?=!empty($info['group_enabled'])?'checked':''?> /><span class="text-sm">Активна</span></label>
                <label class="flex items-center gap-2"><input type="radio" class="radio" name="group_enabled" value="0" <?=empty($info['group_enabled'])?'checked':''?> /><span class="text-sm">Неактивна</span></label>
            </div>
            <span class="form-error">&nbsp;<?=$errors['group_enabled'] ?? ''?></span>
        </div>
        <div class="form-group">
            <label class="label">Доступ к отделам</label>
            <p class="form-hint">Выберите отделы, к которым участники группы имеют доступ, помимо своего собственного отдела.</p>
            <span class="form-error">&nbsp;<?=$errors['depts'] ?? ''?></span>
            <div class="space-y-1 mt-2">
            <?
            $access=(!empty($_POST['depts']) && $errors)?$_POST['depts']:explode(',',($info['dept_access'] ?? ''));
            $depts= db_query('SELECT dept_id,dept_name FROM '.DEPT_TABLE.' ORDER BY dept_name');
            while (list($id,$name) = db_fetch_row($depts)){
                $ck=($access && in_array($id,$access))?'checked':''; ?>
                <label class="flex items-center gap-2">
                    <input type="checkbox" class="checkbox" name="depts[]" value="<?=$id?>" <?=$ck?>>
                    <span class="text-sm text-gray-700"><?=$name?></span>
                </label>
            <?
            }?>
            </div>
            <div class="flex items-center gap-4 text-sm mt-2">
                <a href="#" class="text-blue-600 hover:text-blue-800" onclick="return select_all(document.forms['group'])">Выбрать все</a>
                <a href="#" class="text-blue-600 hover:text-blue-800" onclick="return reset_all(document.forms['group'])">Снять выбор</a>
            </div>
        </div>
        <div class="form-group">
            <label class="label">Может <b>создавать</b> заявки</label>
            <div class="flex items-center gap-4">
                <label class="flex items-center gap-2"><input type="radio" class="radio" name="can_create_tickets" value="1" <?=!empty($info['can_create_tickets'])?'checked':''?> /><span class="text-sm">Да</span></label>
                <label class="flex items-center gap-2"><input type="radio" class="radio" name="can_create_tickets" value="0" <?=empty($info['can_create_tickets'])?'checked':''?> /><span class="text-sm">Нет</span></label>
            </div>
            <p class="form-hint">Возможность создавать заявки от имени пользователей!</p>
        </div>
        <div class="form-group">
            <label class="label">Может <b>изменять</b> заявки</label>
            <div class="flex items-center gap-4">
                <label class="flex items-center gap-2"><input type="radio" class="radio" name="can_edit_tickets" value="1" <?=!empty($info['can_edit_tickets'])?'checked':''?> /><span class="text-sm">Да</span></label>
                <label class="flex items-center gap-2"><input type="radio" class="radio" name="can_edit_tickets" value="0" <?=empty($info['can_edit_tickets'])?'checked':''?> /><span class="text-sm">Нет</span></label>
            </div>
            <p class="form-hint">Возможность редактировать заявки. Администраторы и руководители отделов имеют это право по умолчанию.</p>
        </div>
        <div class="form-group">
            <label class="label">Может <b>закрывать</b> заявки</label>
            <div class="flex items-center gap-4">
                <label class="flex items-center gap-2"><input type="radio" class="radio" name="can_close_tickets" value="1" <?=!empty($info['can_close_tickets'])?'checked':''?> /><span class="text-sm">Да</span></label>
                <label class="flex items-center gap-2"><input type="radio" class="radio" name="can_close_tickets" value="0" <?=empty($info['can_close_tickets'])?'checked':''?> /><span class="text-sm">Нет</span></label>
            </div>
            <p class="form-hint"><b>Только массовое закрытие:</b> сотрудники по-прежнему могут закрывать одну заявку за раз при значении «Нет»</p>
        </div>
        <div class="form-group">
            <label class="label">Может <b>передавать</b> заявки</label>
            <div class="flex items-center gap-4">
                <label class="flex items-center gap-2"><input type="radio" class="radio" name="can_transfer_tickets" value="1" <?=!empty($info['can_transfer_tickets'])?'checked':''?> /><span class="text-sm">Да</span></label>
                <label class="flex items-center gap-2"><input type="radio" class="radio" name="can_transfer_tickets" value="0" <?=empty($info['can_transfer_tickets'])?'checked':''?> /><span class="text-sm">Нет</span></label>
            </div>
            <p class="form-hint">Возможность переводить заявки из одного отдела в другой.</p>
        </div>
        <div class="form-group">
            <label class="label">Может <b>удалять</b> заявки</label>
            <div class="flex items-center gap-4">
                <label class="flex items-center gap-2"><input type="radio" class="radio" name="can_delete_tickets" value="1" <?=!empty($info['can_delete_tickets'])?'checked':''?> /><span class="text-sm">Да</span></label>
                <label class="flex items-center gap-2"><input type="radio" class="radio" name="can_delete_tickets" value="0" <?=empty($info['can_delete_tickets'])?'checked':''?> /><span class="text-sm">Нет</span></label>
            </div>
            <p class="form-hint">Удалённые заявки невозможно восстановить!</p>
        </div>
        <div class="form-group">
            <label class="label">Может блокировать Emailы</label>
            <div class="flex items-center gap-4">
                <label class="flex items-center gap-2"><input type="radio" class="radio" name="can_ban_emails" value="1" <?=!empty($info['can_ban_emails'])?'checked':''?> /><span class="text-sm">Да</span></label>
                <label class="flex items-center gap-2"><input type="radio" class="radio" name="can_ban_emails" value="0" <?=empty($info['can_ban_emails'])?'checked':''?> /><span class="text-sm">Нет</span></label>
            </div>
            <p class="form-hint">Возможность добавлять/удалять email-адреса из чёрного списка через интерфейс заявок.</p>
        </div>
        <div class="form-group">
            <label class="label">Может управлять готовыми ответами</label>
            <div class="flex items-center gap-4">
                <label class="flex items-center gap-2"><input type="radio" class="radio" name="can_manage_kb" value="1" <?=!empty($info['can_manage_kb'])?'checked':''?> /><span class="text-sm">Да</span></label>
                <label class="flex items-center gap-2"><input type="radio" class="radio" name="can_manage_kb" value="0" <?=empty($info['can_manage_kb'])?'checked':''?> /><span class="text-sm">Нет</span></label>
            </div>
            <p class="form-hint">Возможность добавлять/редактировать/отключать/удалять готовые ответы.</p>
        </div>
    </div>
</div>

<div class="flex items-center gap-3 mt-6">
    <button class="btn-primary" type="submit" name="submit" value="Отправить"><i data-lucide="save" class="w-4 h-4"></i> Отправить</button>
    <button class="btn-secondary" type="reset" name="reset">Очистить</button>
    <button class="btn-ghost" type="button" name="cancel" onClick='window.location.href="admin.php?t=groups"'>Отмена</button>
</div>
</form>
