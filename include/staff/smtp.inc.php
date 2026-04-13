<?php
if(!defined('OSTADMININC') || basename($_SERVER['SCRIPT_NAME'])==basename(__FILE__)) die('Habari/Jambo rafiki? '); //Say hi to our friend..
if(!$thisuser || !$thisuser->isadmin()) die('Доступ запрещён');

$info=($_POST && $errors)?Format::input($_POST):Format::htmlchars($cfg->getSMTPInfo());
?>
<h2 class="text-lg font-heading font-semibold text-gray-900 mb-4"><?=$title?></h2>
<form action="admin.php?t=smtp" method="post">
 <?php echo Misc::csrfField(); ?>
 <input type="hidden" name="do" value="save">
 <input type="hidden" name="t" value="smtp">

<div class="card mb-6">
    <div class="card-header"><h3 class="font-heading font-semibold">Настройки SMTP Сервера (Необязательно)</h3></div>
    <div class="card-body space-y-5">
        <p class="text-gray-500 text-sm">
             When enabled the system will use an SMTP server rather than the internal PHP mail() function for outgoing emails.<br>
             Leave the username and password empty of the SMTP server doesn't require authentication<br/>
            <b>Please be patient, the system will try to login to SMTP server to validate the entered login info.</b>
        </p>

        <div class="form-group">
            <label class="label">Включить SMTP</label>
            <div class="flex items-center gap-4">
                <label class="flex items-center gap-2"><input type="radio" class="radio" name="isenabled" value="1" <?=$info['isenabled']?'checked':''?> /><span class="text-sm font-semibold">Да</span></label>
                <label class="flex items-center gap-2"><input type="radio" class="radio" name="isenabled" value="0" <?=!$info['isenabled']?'checked':''?> /><span class="text-sm">Нет</span></label>
            </div>
            <span class="form-error">&nbsp;<?=$errors['isenabled']?></span>
        </div>
        <div class="form-group">
            <label class="label">SMTP Сервер</label>
            <input class="input" type="text" name="host" value="<?=$info['host']?>">
            <span class="form-error">*&nbsp;<?=$errors['host']?></span>
        </div>
        <div class="form-group">
            <label class="label">SMTP Порт</label>
            <input class="input w-32" type="text" name="port" value="<?=$info['port']?>">
            <span class="form-error">*&nbsp;<?=$errors['port']?></span>
        </div>
        <div class="form-group">
            <label class="label">Шифрование</label>
            <div class="flex items-center gap-4">
                <label class="flex items-center gap-2"><input type="radio" class="radio" name="issecure" value="0"
                    <?=!$info['issecure']?'checked':''?> /><span class="text-sm">Нет</span></label>
                <label class="flex items-center gap-2"><input type="radio" class="radio" name="issecure" value="1"
                    <?=$info['issecure']?'checked':''?> /><span class="text-sm">TLS (secure)</span></label>
            </div>
            <span class="form-error">&nbsp;<?=$errors['issecure']?></span>
        </div>
        <div class="form-group">
            <label class="label">Логин</label>
            <input class="input" type="text" name="userid" value="<?=$info['userid']?>" autocomplete="off">
            <span class="form-error">*&nbsp;<?=$errors['userid']?></span>
        </div>
        <div class="form-group">
            <label class="label">Пароль</label>
            <input class="input" type="password" name="userpass" value="" placeholder="<?=$info['userpass'] ? '(оставьте пустым, чтобы сохранить текущий)' : ''?>" autocomplete="new-password">
            <span class="form-error">*&nbsp;<?=$errors['userpass']?></span>
        </div>
        <div class="form-group">
            <label class="label">Email Адрес</label>
            <input class="input" type="text" name="fromaddress" value="<?=$info['fromaddress']?>">
            <span class="form-error">*&nbsp;<?=$errors['fromaddress']?></span>
        </div>
        <div class="form-group">
            <label class="label">Email Имя:</label>
            <input class="input" type="text" name="fromname" value="<?=$info['fromname']?>">
            <span class="form-error">&nbsp;<?=$errors['fromname']?></span>
            <p class="form-hint">Optional email's FROM name.</p>
        </div>
    </div>
</div>

<div class="flex items-center gap-3 mt-6">
    <button class="btn-primary" type="submit" name="submit" value="Отправить"><i data-lucide="save" class="w-4 h-4"></i> Отправить</button>
    <button class="btn-secondary" type="reset" name="reset">Очистить</button>
    <button class="btn-ghost" type="button" name="cancel" onClick='window.location.href="admin.php?t=email"'>Отмена</button>
</div>
</form>
