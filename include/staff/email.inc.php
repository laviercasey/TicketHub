<?php
if(!defined('OSTADMININC') || basename($_SERVER['SCRIPT_NAME'])==basename(__FILE__)) die('Habari/Jambo rafiki? '); //Say hi to our friend..
if(!$thisuser || !$thisuser->isadmin()) die('Доступ запрещён');

$info=($_POST && $errors)?$_POST:array(); //Re-use the post info on error...savekeyboards.org
if($email && ($_REQUEST['a'] ?? '') !='new'){
    $title='Редактирование Email';
    $action='update';
    if(!$info) {
        $info=$email->getInfo();
        $info['userpass']=!empty($info['userpass'])?Misc::decrypt($info['userpass'],SECRET_SALT):'';
    }
    $qstr='?t=email&id='.$email->getId();
}else {
   $title='Новый Email';
   $action='create';
   $info['smtp_auth']=isset($info['smtp_auth'])?$info['smtp_auth']:1;
}

$info=$info ? Format::htmlchars($info) : array();
$depts= db_query('SELECT dept_id,dept_name FROM '.DEPT_TABLE);
$priorities= db_query('SELECT priority_id,priority_desc FROM '.TICKET_PRIORITY_TABLE);
?>
<h2 class="text-lg font-heading font-semibold text-gray-900 mb-4"><?=$title?></h2>
<form action="admin.php<?=$qstr ?? ''?>" method="post">
 <?php echo Misc::csrfField(); ?>
 <input type="hidden" name="do" value="<?=$action?>">
 <input type="hidden" name="a" value="<?=Format::htmlchars($_REQUEST['a'] ?? '')?>">
 <input type="hidden" name="t" value="email">
 <input type="hidden" name="email_id" value="<?=$info['email_id'] ?? ''?>">

    <div class="card mb-6">
        <div class="card-header"><h3 class="font-heading font-semibold">Email Информация</h3></div>
        <div class="card-body space-y-5">
            <p class="text-gray-500 text-sm">Настройки в основном для заявок, поступающих по email. Для online/web запросов смотрите тема обращения.</p>

            <div class="form-group">
                <label class="label">Email Адрес</label>
                <input class="input" type="text" name="email" value="<?=$info['email'] ?? ''?>">
                <span class="form-error">*&nbsp;<?=$errors['email'] ?? ''?></span>
            </div>
            <div class="form-group">
                <label class="label">Email Имя:</label>
                <input class="input" type="text" name="name" value="<?=$info['name'] ?? ''?>">
                <span class="form-error">&nbsp;<?=$errors['name'] ?? ''?></span>
                <p class="form-hint">Необязательное имя отправителя (FROM).</p>
            </div>
            <div class="form-group">
                <label class="label">Приоритет Нового Запроса</label>
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
                <label class="label">Отдел Нового Запроса</label>
                <select class="select" name="dept_id">
                    <option value="0">Выберите Отдел</option>
                    <?
                    while (list($id,$name) = db_fetch_row($depts)){
                        $selected = (($info['dept_id'] ?? '')==$id)?'selected':''; ?>
                        <option value="<?=$id?>"<?=$selected?>><?=$name?> Отдел</option>
                    <?
                    }?>
                </select>
                <span class="form-error">&nbsp;<?=$errors['dept_id'] ?? ''?></span>
            </div>
            <div class="form-group">
                <label class="label">Автоответ</label>
                <label class="flex items-center gap-2">
                    <input type="checkbox" class="checkbox" name="noautoresp" value="1" <?=!empty($info['noautoresp'])? 'checked': ''?>>
                    <span class="text-sm font-semibold text-gray-700">Отключить автоответ для этого email.</span>
                </label>
                <p class="form-hint">Переопределить настройку отдела</p>
            </div>

            <p class="text-gray-500 text-sm font-semibold">Данные для входа (необязательно): Требуются при включённом IMAP/POP и/или SMTP.</p>

            <div class="form-group">
                <label class="label">Логин</label>
                <input class="input" type="text" name="userid" value="<?=$info['userid'] ?? ''?>" autocomplete="off">
                <span class="form-error">&nbsp;<?=$errors['userid'] ?? ''?></span>
            </div>
            <div class="form-group">
                <label class="label">Пароль</label>
                <input class="input" type="password" name="userpass" value="" placeholder="<?=!empty($info['userpass']) ? '(оставьте пустым, чтобы сохранить текущий)' : ''?>" autocomplete="new-password">
                <span class="form-error">&nbsp;<?=$errors['userpass'] ?? ''?></span>
            </div>
        </div>
    </div>

    <div class="card mb-6">
        <div class="card-header"><h3 class="font-heading font-semibold">Почтовый аккаунт (Необязательно)</h3></div>
        <div class="card-body space-y-5">
            <p class="text-gray-500 text-sm">
                 Настройки для получения входящих писем. Получение почты должно быть включено с активным autocron или внешним cron.<br>
                <b>Пожалуйста, подождите — система попытается войти на почтовый сервер для проверки введённых данных.</b>
            </p>
            <span class="form-error">&nbsp;<?=$errors['mail'] ?? ''?></span>

            <div class="form-group">
                <label class="label">Статус</label>
                <div class="flex items-center gap-4">
                    <label class="flex items-center gap-2"><input type="radio" class="radio" name="mail_active" value="1" <?=!empty($info['mail_active'])?'checked':''?> /><span class="text-sm">Включить</span></label>
                    <label class="flex items-center gap-2"><input type="radio" class="radio" name="mail_active" value="0" <?=empty($info['mail_active'])?'checked':''?> /><span class="text-sm">Отключить</span></label>
                </div>
                <span class="form-error">&nbsp;<?=$errors['mail_active'] ?? ''?></span>
            </div>
            <div class="form-group">
                <label class="label">Хост</label>
                <input class="input" type="text" name="mail_host" value="<?=$info['mail_host'] ?? ''?>">
                <span class="form-error">&nbsp;<?=$errors['mail_host'] ?? ''?></span>
            </div>
            <div class="form-group">
                <label class="label">Порт</label>
                <input class="input w-32" type="text" name="mail_port" value="<?=!empty($info['mail_port'])?$info['mail_port']:''?>">
                <span class="form-error">&nbsp;<?=$errors['mail_port'] ?? ''?></span>
            </div>
            <div class="form-group">
                <label class="label">Протокол</label>
                <select class="select" name="mail_protocol">
                    <option value="POP">Выберите</option>
                    <option value="POP" <?=(($info['mail_protocol'] ?? '')=='POP')?'selected="selected"':''?> >POP</option>
                    <option value="IMAP" <?=(($info['mail_protocol'] ?? '')=='IMAP')?'selected="selected"':''?> >IMAP</option>
                </select>
                <span class="form-error">&nbsp;<?=$errors['mail_protocol'] ?? ''?></span>
            </div>
            <div class="form-group">
                <label class="label">Шифрование</label>
                <div class="flex items-center gap-4">
                    <label class="flex items-center gap-2"><input type="radio" class="radio" name="mail_encryption" value="NONE"
                        <?=(($info['mail_encryption'] ?? '')!='SSL')?'checked':''?> /><span class="text-sm">Нет</span></label>
                    <label class="flex items-center gap-2"><input type="radio" class="radio" name="mail_encryption" value="SSL"
                        <?=(($info['mail_encryption'] ?? '')=='SSL')?'checked':''?> /><span class="text-sm">SSL</span></label>
                </div>
                <span class="form-error">&nbsp;<?=$errors['mail_encryption'] ?? ''?></span>
            </div>
            <div class="form-group">
                <label class="label">Частота получения</label>
                <div class="flex items-center gap-2">
                    <input class="input w-24" type="text" name="mail_fetchfreq" value="<?=!empty($info['mail_fetchfreq'])?$info['mail_fetchfreq']:''?>">
                    <span class="text-sm text-gray-500">Интервал задержки в минутах</span>
                </div>
                <span class="form-error">&nbsp;<?=$errors['mail_fetchfreq'] ?? ''?></span>
            </div>
            <div class="form-group">
                <label class="label">Максимум писем за одно получение</label>
                <div class="flex items-center gap-2">
                    <input class="input w-24" type="text" name="mail_fetchmax" value="<?=!empty($info['mail_fetchmax'])?$info['mail_fetchmax']:''?>">
                    <span class="text-sm text-gray-500">Максимальное количество писем для обработки за раз.</span>
                </div>
                <span class="form-error">&nbsp;<?=$errors['mail_fetchmax'] ?? ''?></span>
            </div>
            <div class="form-group">
                <label class="label">Удаление Сообщений</label>
                <label class="flex items-center gap-2">
                    <input type="checkbox" class="checkbox" name="mail_delete" value="1" <?=!empty($info['mail_delete'])? 'checked': ''?>>
                    <span class="text-sm text-gray-700">Удалять полученные сообщения (<i>рекомендуется при использовании POP</i>)</span>
                </label>
                <span class="form-error">&nbsp;<?=$errors['mail_delete'] ?? ''?></span>
            </div>
        </div>
    </div>

    <div class="card mb-6">
        <div class="card-header"><h3 class="font-heading font-semibold">SMTP Настройки (Необязательные)</h3></div>
        <div class="card-body space-y-5">
            <p class="text-gray-500 text-sm">
                 При включении <b>учётная запись email</b> будет использовать SMTP сервер вместо встроенной PHP функции mail() для исходящих писем.<br>
                <b>Пожалуйста, подождите — система попытается войти на SMTP сервер для проверки введённых данных.</b>
            </p>
            <span class="form-error">&nbsp;<?=$errors['smtp'] ?? ''?></span>

            <div class="form-group">
                <label class="label">Статус</label>
                <div class="flex items-center gap-4">
                    <label class="flex items-center gap-2"><input type="radio" class="radio" name="smtp_active" value="1" <?=!empty($info['smtp_active'])?'checked':''?> /><span class="text-sm">Включить</span></label>
                    <label class="flex items-center gap-2"><input type="radio" class="radio" name="smtp_active" value="0" <?=empty($info['smtp_active'])?'checked':''?> /><span class="text-sm">Отключить</span></label>
                </div>
                <span class="form-error">&nbsp;<?=$errors['smtp_active'] ?? ''?></span>
            </div>
            <div class="form-group">
                <label class="label">SMTP Хост</label>
                <input class="input" type="text" name="smtp_host" value="<?=$info['smtp_host'] ?? ''?>">
                <span class="form-error">&nbsp;<?=$errors['smtp_host'] ?? ''?></span>
            </div>
            <div class="form-group">
                <label class="label">SMTP Порт</label>
                <input class="input w-32" type="text" name="smtp_port" value="<?=!empty($info['smtp_port'])?$info['smtp_port']:''?>">
                <span class="form-error">&nbsp;<?=$errors['smtp_port'] ?? ''?></span>
            </div>
            <div class="form-group">
                <label class="label">Требуется аутентификация?</label>
                <div class="flex items-center gap-4">
                    <label class="flex items-center gap-2"><input type="radio" class="radio" name="smtp_auth" value="1"
                        <?=!empty($info['smtp_auth'])?'checked':''?> /><span class="text-sm">Да</span></label>
                    <label class="flex items-center gap-2"><input type="radio" class="radio" name="smtp_auth" value="0"
                        <?=empty($info['smtp_auth'])?'checked':''?> /><span class="text-sm">Нет</span></label>
                </div>
                <span class="form-error">&nbsp;<?=$errors['smtp_auth'] ?? ''?></span>
            </div>
            <div class="form-group">
                <label class="label">Шифрование</label>
                <p class="text-sm text-gray-500">Лучший доступный метод аутентификации выбирается автоматически на основе поддержки сервера.</p>
            </div>
        </div>
    </div>

    <div class="flex items-center gap-3 mt-6">
        <button class="btn-primary" type="submit" name="submit" value="Отправить"><i data-lucide="save" class="w-4 h-4"></i> Отправить</button>
        <button class="btn-secondary" type="reset" name="reset">Очистить</button>
        <button class="btn-ghost" type="button" name="cancel" onClick='window.location.href="admin.php?t=email"'>Отмена</button>
    </div>
</form>
