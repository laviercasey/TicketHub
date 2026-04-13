<?php
if(!defined('OSTADMININC') || !$thisuser->isadmin()) die('Доступ запрещён');

$rep=array();
$newuser=true;
if($staff && ($_REQUEST['a'] ?? '') !='new'){
    $rep=$staff->getInfo();
    $title='Редактирование: '.$rep['firstname'].' '.$rep['lastname'];
    $action='update';
    $pwdinfo='To reset the password enter a new one below';
    $newuser=false;
}else {
    $title='New Staff Member';
    $pwdinfo='Temp password required';
    $action='create';
    $rep['resetpasswd']=isset($rep['resetpasswd'])?$rep['resetpasswd']:1;
    $rep['isactive']=isset($rep['isactive'])?$rep['isactive']:1;
    $rep['dept_id']=!empty($rep['dept_id'])?$rep['dept_id']:($_GET['dept'] ?? '');
    $rep['isvisible']=isset($rep['isvisible'])?$rep['isvisible']:1;
}
$rep=($errors && $_POST)?Format::input($_POST):($rep ? Format::htmlchars($rep) : array());

$groups=db_query('SELECT group_id,group_name FROM '.GROUP_TABLE);
$depts= db_query('SELECT dept_id,dept_name FROM '.DEPT_TABLE);

?>
<h2 class="text-lg font-heading font-semibold text-gray-900 mb-4"><?=$title?></h2>
<form action="admin.php" method="post">
 <?php echo Misc::csrfField(); ?>
 <input type="hidden" name="do" value="<?=$action?>">
 <input type="hidden" name="a" value="<?=Format::htmlchars($_REQUEST['a'] ?? '')?>">
 <input type="hidden" name="t" value="staff">
 <input type="hidden" name="staff_id" value="<?=$rep['staff_id'] ?? ''?>">

<div class="card mb-6">
    <div class="card-header"><h3 class="font-heading font-semibold">Аккаунт Пользователя</h3></div>
    <div class="card-body space-y-5">
        <p class="text-gray-500 text-sm">Общая информация</p>

        <div class="form-group">
            <label class="label">Логин:</label>
            <input class="input" type="text" name="username" value="<?=$rep['username'] ?? ''?>">
            <span class="form-error">*&nbsp;<?=$errors['username'] ?? ''?></span>
        </div>
        <div class="form-group">
            <label class="label">Отдел:</label>
            <select class="select" name="dept_id">
                <option value="0">Выберите отдел</option>
                <?
                while (list($id,$name) = db_fetch_row($depts)){
                    $selected = (($rep['dept_id'] ?? '')==$id)?'selected':''; ?>
                    <option value="<?=$id?>"<?=$selected?>><?=$name?></option>
                <?
                }?>
            </select>
            <span class="form-error">*&nbsp;<?=$errors['dept'] ?? ''?></span>
        </div>
        <div class="form-group">
            <label class="label">Группа Пользователя:</label>
            <select class="select" name="group_id">
                <option value="0">Выберите группу</option>
                <?
                while (list($id,$name) = db_fetch_row($groups)){
                    $selected = (($rep['group_id'] ?? '')==$id)?'selected':''; ?>
                    <option value="<?=$id?>"<?=$selected?>><?=$name?></option>
                <?
                }?>
            </select>
            <span class="form-error">*&nbsp;<?=$errors['group'] ?? ''?></span>
        </div>
        <div class="form-group">
            <label class="label">Имя (Фамилия,Имя):</label>
            <div class="flex items-center gap-3">
                <input class="input w-auto" type="text" name="firstname" value="<?=$rep['firstname'] ?? ''?>">
                <span class="text-red-500">*</span>
                <input class="input w-auto" type="text" name="lastname" value="<?=$rep['lastname'] ?? ''?>">
            </div>
            <span class="form-error">*&nbsp;<?=$errors['name'] ?? ''?></span>
        </div>
        <div class="form-group">
            <label class="label">Email Адрес:</label>
            <input class="input" type="text" name="email" value="<?=$rep['email'] ?? ''?>">
            <span class="form-error">*&nbsp;<?=$errors['email'] ?? ''?></span>
        </div>
        <div class="form-group">
            <label class="label">Рабочий Телефон:</label>
            <div class="flex items-center gap-3">
                <input class="input w-auto" type="text" name="phone" value="<?=$rep['phone'] ?? ''?>">
                <span class="text-gray-500">Доб.</span>
                <input class="input w-24" type="text" name="phone_ext" value="<?=$rep['phone_ext'] ?? ''?>">
            </div>
            <span class="form-error">&nbsp;<?=$errors['phone'] ?? ''?></span>
        </div>
        <div class="form-group">
            <label class="label">Мобильный Телефон:</label>
            <input class="input" type="text" name="mobile" value="<?=$rep['mobile'] ?? ''?>">
            <span class="form-error">&nbsp;<?=$errors['mobile'] ?? ''?></span>
        </div>
        <div class="form-group">
            <label class="label">Подпись:</label>
            <textarea class="textarea" name="signature" rows="5"><?=$rep['signature'] ?? ''?></textarea>
        </div>
        <div class="form-group">
            <label class="label">Пароль:</label>
            <p class="form-hint"><?=$pwdinfo?></p>
            <span class="form-error">&nbsp;<?=$errors['npassword'] ?? ''?></span>
            <input class="input" type="password" name="npassword" autocomplete="off">
        </div>
        <div class="form-group">
            <label class="label">Пароль (повторно):</label>
            <input class="input" type="password" name="vpassword" autocomplete="off">
            <span class="form-error">&nbsp;<?=$errors['vpassword'] ?? ''?></span>
        </div>
        <div class="form-group">
            <label class="label">Назначение Пароля:</label>
            <label class="flex items-center gap-2">
                <input type="checkbox" class="checkbox" name="resetpasswd" <?=!empty($rep['resetpasswd']) ? 'checked': ''?>>
                <span class="text-sm text-gray-700">Необходимо изменить пароль при следующем входе</span>
            </label>
        </div>
    </div>
</div>

<div class="card mb-6">
    <div class="card-header"><h3 class="font-heading font-semibold">Права, статус и Настройки Аккаунта</h3></div>
    <div class="card-body space-y-5">
        <p class="text-gray-500 text-sm">Staff's permission is also based on the assigned group. <b>Admin is not restricted by group settings.</b></p>

        <div class="form-group">
            <label class="label"><b>Статус Аккаунта</b></label>
            <div class="flex items-center gap-4">
                <label class="flex items-center gap-2"><input type="radio" class="radio" name="isactive" value="1" <?=!empty($rep['isactive'])?'checked':''?> /><span class="font-semibold text-sm">Активен</span></label>
                <label class="flex items-center gap-2"><input type="radio" class="radio" name="isactive" value="0" <?=empty($rep['isactive'])?'checked':''?> /><span class="font-semibold text-sm">Заблокирован</span></label>
            </div>
        </div>
        <div class="form-group">
            <label class="label"><b>Тип Аккаунта</b></label>
            <div class="flex items-center gap-4">
                <label class="flex items-center gap-2"><input type="radio" class="radio" name="isadmin" value="1" <?=!empty($rep['isadmin'])?'checked':''?> /><span class="font-semibold text-sm text-red-500">Администратор</span></label>
                <label class="flex items-center gap-2"><input type="radio" class="radio" name="isadmin" value="0" <?=empty($rep['isadmin'])?'checked':''?> /><span class="font-semibold text-sm">Менеджер</span></label>
            </div>
        </div>
        <div class="form-group">
            <label class="label">Directory Listing</label>
            <label class="flex items-center gap-2">
                <input type="checkbox" class="checkbox" name="isvisible" <?=!empty($rep['isvisible']) ? 'checked': ''?>>
                <span class="text-sm text-gray-700">показывать пользователя на staff's directory</span>
            </label>
        </div>
        <div class="form-group">
            <label class="label">Режим Вакансии</label>
            <label class="flex items-center gap-2">
                <input type="checkbox" class="checkbox" name="onvacation" <?=!empty($rep['onvacation']) ? 'checked': ''?>>
                <span class="text-sm text-gray-700">Место менеджера вакантно. (<i>Запросы не принимаются</i>)</span>
            </label>
            <span class="form-error">&nbsp;<?=$errors['vacation'] ?? ''?></span>
        </div>
    </div>
</div>

<div class="flex items-center gap-3 mt-6">
    <button class="btn-primary" type="submit" name="submit" value="Отправить"><i data-lucide="save" class="w-4 h-4"></i> Отправить</button>
    <button class="btn-secondary" type="reset" name="reset">Очистить</button>
    <button class="btn-ghost" type="button" name="cancel" onClick='window.location.href="admin.php?t=staff"'>Отмена</button>
</div>
</form>
