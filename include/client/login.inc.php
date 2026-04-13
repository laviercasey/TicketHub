<?php
if(!defined('OSTCLIENTINC')) die('Kwaheri');

$e=Format::input(($_POST['lemail'] ?? null)?$_POST['lemail']:($_GET['e'] ?? ''));
$t=Format::input(($_POST['lticket'] ?? null)?$_POST['lticket']:($_GET['t'] ?? ''));
?>

<?php if(!empty($errors['err'])) { ?>
    <div class="alert-danger" role="alert">
        <i data-lucide="alert-triangle" class="w-4 h-4 flex-shrink-0"></i>
        <span><?=Format::htmlchars($errors['err'])?></span>
    </div>
<?php } elseif($warn) { ?>
    <div class="alert-warning" role="alert">
        <i data-lucide="alert-circle" class="w-4 h-4 flex-shrink-0"></i>
        <span><?=Format::htmlchars($warn)?></span>
    </div>
<?php } ?>

<div class="max-w-md mx-auto mt-8">
    <div class="card">
        <div class="card-header text-center">
            <div class="flex justify-center mb-3">
                <div class="w-12 h-12 bg-indigo-600 rounded-xl flex items-center justify-center">
                    <svg class="w-6 h-6 text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 5v2"/><path d="M15 11v2"/><path d="M15 17v2"/><path d="M5 5h14a2 2 0 0 1 2 2v3a2 2 0 0 0 0 4v3a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-3a2 2 0 0 0 0-4V7a2 2 0 0 1 2-2z"/></svg>
                </div>
            </div>
            <h3 class="font-heading font-semibold text-gray-900 text-lg">Вход в систему</h3>
        </div>
        <div class="card-body">
            <p class="text-center text-sm text-gray-500 mb-6">
                Для просмотра статуса заявки введите данные для входа.<br>
                Первое обращение? <a href="open.php" class="text-indigo-600 hover:text-indigo-700 font-medium">Создайте новую заявку</a>.
            </p>

            <?php if($loginmsg) { ?>
                <p class="text-center text-sm text-gray-400 italic mb-4">
                    <?=Format::htmlchars($loginmsg)?>
                </p>
            <?php } ?>

            <form action="login.php" method="post" id="loginform" class="space-y-4">
                <?php echo Misc::csrfField(); ?>

                <div class="form-group">
                    <label for="lemail" class="label">E-Mail</label>
                    <input type="email" name="lemail" id="lemail" class="input"
                           value="<?=$e?>" placeholder="your@email.com" required>
                </div>

                <div class="form-group">
                    <label for="lticket" class="label">Номер заявки</label>
                    <input type="text" name="lticket" id="lticket" class="input"
                           value="<?=$t?>" placeholder="12345" required>
                </div>

                <button type="submit" class="btn-primary btn-lg w-full">
                    <i data-lucide="search" class="w-4 h-4"></i> Посмотреть статус
                </button>
            </form>
        </div>
    </div>
</div>

<?php if (!empty($e) && !empty($t)) { ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    var form = document.getElementById('loginform');
    if (form) {
        form.submit();
    }
});
</script>
<?php } ?>
