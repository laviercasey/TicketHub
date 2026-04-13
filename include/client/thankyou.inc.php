<?php
if(!defined('OSTCLIENTINC') || !is_object($ticket)) die('Kwaheri rafiki!');
if(!defined('ROOT_PATH')) define('ROOT_PATH','./');
?>

<?php if(!empty($errors['err'])) { ?>
    <div class="alert-danger">
        <i data-lucide="alert-triangle" class="w-4 h-4 flex-shrink-0"></i>
        <span><?=Format::htmlchars($errors['err'])?></span>
    </div>
<?php } elseif($msg) { ?>
    <div class="alert-success">
        <i data-lucide="check-circle" class="w-4 h-4 flex-shrink-0"></i>
        <span><?=Format::htmlchars($msg)?></span>
    </div>
<?php } elseif($warn) { ?>
    <div class="alert-warning">
        <i data-lucide="alert-circle" class="w-4 h-4 flex-shrink-0"></i>
        <span><?=Format::htmlchars($warn)?></span>
    </div>
<?php } ?>

<div class="max-w-2xl mx-auto mt-8">
    <div class="card">
        <div class="card-body text-center py-10">
            <div class="w-16 h-16 rounded-full bg-emerald-50 flex items-center justify-center mx-auto mb-6">
                <i data-lucide="check-circle" class="w-8 h-8 text-emerald-500"></i>
            </div>

            <h2 class="text-2xl font-heading font-bold text-gray-900 mb-2">Заявка создана!</h2>
            <p class="text-gray-500 mb-8">
                <?=Format::htmlchars($ticket->getName())?>, спасибо за обращение. Вы получите ответ в самое ближайшее время.
            </p>

            <div class="bg-gray-50 rounded-xl p-5 mb-8 inline-block text-left">
                <div class="space-y-3">
                    <div class="flex items-center gap-3">
                        <span class="text-sm text-gray-500 w-32">Номер заявки:</span>
                        <span class="text-sm font-semibold text-gray-900"><?=Format::htmlchars($ticket->getExtId())?></span>
                    </div>
                    <div class="flex items-center gap-3">
                        <span class="text-sm text-gray-500 w-32">Email:</span>
                        <span class="text-sm font-semibold text-gray-900"><?=Format::htmlchars($ticket->getEmail())?></span>
                    </div>
                </div>
            </div>

            <?php if($cfg->autoRespONNewTicket()){ ?>
            <p class="text-sm text-gray-500 mb-2">
                Письмо с номером заявки отправлено на <strong><?=Format::htmlchars($ticket->getEmail())?></strong>.
            </p>
            <p class="text-sm text-gray-400 mb-6">
                Сохраните номер заявки и email для просмотра статуса.
            </p>
            <?php } ?>

            <div class="flex flex-col sm:flex-row gap-3 justify-center">
                <a href="view.php?e=<?=urlencode($ticket->getEmail())?>&amp;t=<?=urlencode($ticket->getExtId())?>" class="btn-primary">
                    <i data-lucide="eye" class="w-4 h-4"></i> Просмотреть заявку
                </a>
                <a href="index.php" class="btn-secondary">
                    <i data-lucide="home" class="w-4 h-4"></i> На главную
                </a>
            </div>
        </div>
    </div>
</div>
<?php
unset($_POST);
?>
