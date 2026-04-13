<?php
if (!defined('OSTSCPINC') || !is_object($thisuser)) die('Kwaheri');
?>
<form action="profile.php" method="POST">
    <?= Misc::csrfField() ?>
    <input type="hidden" name="t" value="passwd">
    <input type="hidden" name="id" value="<?= $thisuser->getId() ?>">

    <div class="card">
        <div class="card-header">
            <h2 class="font-heading font-semibold text-gray-900">Смена пароля</h2>
        </div>
        <div class="card-body space-y-5">

            <div class="form-group">
                <label class="label">Текущий пароль <span class="text-red-500">*</span></label>
                <div class="flex items-center gap-2">
                    <input class="input flex-1 <?= @$errors['password'] ? 'input-error' : '' ?>"
                        type="password" id="pw_current" name="password" autocomplete="current-password">
                    <button type="button" onclick="togglePw('pw_current', this)" class="btn-secondary btn-sm flex-shrink-0" tabindex="-1">
                        <i data-lucide="eye" class="w-4 h-4"></i>
                    </button>
                </div>
                <?php if (@$errors['password']) { ?><span class="form-error"><?= Format::htmlchars($errors['password']) ?></span><?php } ?>
            </div>

            <div class="form-group">
                <label class="label">Новый пароль <span class="text-red-500">*</span></label>
                <div class="flex items-center gap-2">
                    <input class="input flex-1 <?= @$errors['npassword'] ? 'input-error' : '' ?>"
                        type="password" id="pw_new" name="npassword" autocomplete="new-password">
                    <button type="button" onclick="togglePw('pw_new', this)" class="btn-secondary btn-sm flex-shrink-0" tabindex="-1">
                        <i data-lucide="eye" class="w-4 h-4"></i>
                    </button>
                </div>
                <?php if (@$errors['npassword']) { ?><span class="form-error"><?= Format::htmlchars($errors['npassword']) ?></span><?php } ?>
            </div>

            <div class="form-group">
                <label class="label">Подтверждение пароля <span class="text-red-500">*</span></label>
                <div class="flex items-center gap-2">
                    <input class="input flex-1 <?= @$errors['vpassword'] ? 'input-error' : '' ?>"
                        type="password" id="pw_confirm" name="vpassword" autocomplete="new-password">
                    <button type="button" onclick="togglePw('pw_confirm', this)" class="btn-secondary btn-sm flex-shrink-0" tabindex="-1">
                        <i data-lucide="eye" class="w-4 h-4"></i>
                    </button>
                </div>
                <?php if (@$errors['vpassword']) { ?><span class="form-error"><?= Format::htmlchars($errors['vpassword']) ?></span><?php } ?>
            </div>

        </div>
    </div>

    <div class="flex items-center gap-3 mt-6">
        <button class="btn-primary" type="submit">
            <i data-lucide="save" class="w-4 h-4"></i> Сохранить
        </button>
        <button class="btn-secondary" type="reset">Очистить</button>
        <a href="profile.php" class="btn-ghost">Отмена</a>
    </div>
</form>

<script>
function togglePw(id, btn) {
    var input = document.getElementById(id);
    var icon = btn.querySelector('i');
    if (input.type === 'password') {
        input.type = 'text';
        icon.setAttribute('data-lucide', 'eye-off');
    } else {
        input.type = 'password';
        icon.setAttribute('data-lucide', 'eye');
    }
    if (typeof lucide !== 'undefined') lucide.createIcons();
}
</script>
