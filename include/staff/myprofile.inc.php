<?php
if(!defined('OSTSCPINC') || !is_object($thisuser)) die('Kwaheri');

?>
<form action="profile.php" method="post">
 <?php echo Misc::csrfField(); ?>
 <input type="hidden" name="t" value="info">
 <input type="hidden" name="id" value="<?=$thisuser->getId()?>">

<div class="card">
    <div class="card-header">
        <h2 class="font-heading font-semibold text-gray-900">Мой Профиль</h2>
    </div>
    <div class="card-body space-y-5">
        <div class="form-group">
            <label class="label">Логин</label>
            <p class="text-sm text-gray-900 font-medium py-2"><?=$thisuser->getUserName()?></p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
            <div class="form-group">
                <label class="label">Имя <span class="text-red-500">*</span></label>
                <input class="input" type="text" name="firstname" value="<?=Format::htmlchars($rep['firstname'])?>">
                <?php if($errors['firstname']) { ?><span class="form-error"><?=Format::htmlchars($errors['firstname'])?></span><?php } ?>
            </div>
            <div class="form-group">
                <label class="label">Фамилия <span class="text-red-500">*</span></label>
                <input class="input" type="text" name="lastname" value="<?=Format::htmlchars($rep['lastname'])?>">
                <?php if($errors['lastname']) { ?><span class="form-error"><?=Format::htmlchars($errors['lastname'])?></span><?php } ?>
            </div>
        </div>

        <div class="form-group">
            <label class="label">Email Адрес <span class="text-red-500">*</span></label>
            <input class="input" type="text" name="email" value="<?=Format::htmlchars($rep['email'])?>">
            <?php if($errors['email']) { ?><span class="form-error"><?=Format::htmlchars($errors['email'])?></span><?php } ?>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
            <div class="form-group">
                <label class="label">Телефон</label>
                <div class="flex gap-2">
                    <input class="input flex-1" type="text" name="phone" value="<?=Format::htmlchars($rep['phone'])?>">
                    <input class="input w-24" type="text" name="phone_ext" value="<?=Format::htmlchars($rep['phone_ext'])?>" placeholder="Доб.">
                </div>
                <?php if($errors['phone']) { ?><span class="form-error"><?=Format::htmlchars($errors['phone'])?></span><?php } ?>
            </div>
            <div class="form-group">
                <label class="label">Мобильный</label>
                <input class="input" type="text" name="mobile" value="<?=Format::htmlchars($rep['mobile'])?>">
                <?php if($errors['mobile']) { ?><span class="form-error"><?=Format::htmlchars($errors['mobile'])?></span><?php } ?>
            </div>
        </div>

        <div class="form-group">
            <label class="label">Подпись</label>
            <textarea class="textarea" name="signature" rows="5"><?=Format::htmlchars($rep['signature'])?></textarea>
        </div>
    </div>
</div>

<div class="flex items-center gap-3 mt-6">
    <button class="btn-primary" type="submit" name="submit">
        <i data-lucide="save" class="w-4 h-4"></i> Сохранить
    </button>
    <button class="btn-secondary" type="reset">Очистить</button>
    <button class="btn-ghost" type="button" onclick='window.location.href="index.php"'>Отмена</button>
</div>
</form>
