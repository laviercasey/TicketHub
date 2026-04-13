<?php
if(!defined('OSTCLIENTINC') || !is_object($thisclient) || !is_object($ticket)) die('Kwaheri');
if(strcasecmp($thisclient->getEmail(),$ticket->getEmail())) die('Доступ запрещён');

$info=($_POST && $errors)?Format::input($_POST):array();

$dept = $ticket->getDept();
$dept=($dept && $dept->isPublic())?$dept:$cfg->getDefaultDept();
$statusClass = ($ticket->getStatus()=='open') ? 'status-open' : 'status-closed';
?>

<!-- Header -->
<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
    <div>
        <h1 class="text-xl sm:text-2xl font-heading font-bold text-gray-900">
            Заявка №<?=$ticket->getExtId()?>
        </h1>
        <p class="text-sm text-gray-500 mt-1"><?=Format::htmlchars($ticket->getSubject())?></p>
    </div>
    <a href="view.php?id=<?=$ticket->getExtId()?>" class="btn-secondary btn-sm self-start">
        <i data-lucide="refresh-cw" class="w-4 h-4"></i> Обновить
    </a>
</div>

<?php if(!empty($errors['err'])) { ?>
    <div class="alert-danger mb-6">
        <i data-lucide="alert-triangle" class="w-4 h-4 flex-shrink-0"></i>
        <span><?=Format::htmlchars($errors['err'])?></span>
    </div>
<?php } elseif($msg) { ?>
    <div class="alert-success mb-6">
        <i data-lucide="check-circle" class="w-4 h-4 flex-shrink-0"></i>
        <span><?=Format::htmlchars($msg)?></span>
    </div>
<?php } ?>

<!-- Info Cards -->
<div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-8">
    <div class="card">
        <div class="card-header">
            <div class="flex items-center gap-2">
                <i data-lucide="info" class="w-4 h-4 text-indigo-500"></i>
                <h3 class="font-semibold text-gray-900 text-sm">Информация о заявке</h3>
            </div>
        </div>
        <div class="card-body space-y-3">
            <div class="flex justify-between items-center">
                <span class="text-sm text-gray-500">Статус</span>
                <span class="<?=$statusClass?>"><?=Ticket::GetRusStatus($ticket->getStatus())?></span>
            </div>
            <div class="flex justify-between items-center">
                <span class="text-sm text-gray-500">Отдел</span>
                <span class="text-sm text-gray-900"><?=Format::htmlchars($dept->getName())?></span>
            </div>
            <div class="flex justify-between items-center">
                <span class="text-sm text-gray-500">Дата создания</span>
                <span class="text-sm text-gray-900"><?=Format::db_datetime($ticket->getCreateDate())?></span>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <div class="flex items-center gap-2">
                <i data-lucide="user" class="w-4 h-4 text-indigo-500"></i>
                <h3 class="font-semibold text-gray-900 text-sm">Информация о пользователе</h3>
            </div>
        </div>
        <div class="card-body space-y-3">
            <div class="flex justify-between items-center">
                <span class="text-sm text-gray-500">Имя</span>
                <span class="text-sm text-gray-900"><?=Format::htmlchars($ticket->getName())?></span>
            </div>
            <div class="flex justify-between items-center">
                <span class="text-sm text-gray-500">Email</span>
                <a href="mailto:<?=Format::htmlchars($ticket->getEmail())?>" class="text-sm text-indigo-600 hover:text-indigo-700"><?=Format::htmlchars($ticket->getEmail())?></a>
            </div>
            <div class="flex justify-between items-center">
                <span class="text-sm text-gray-500">Телефон</span>
                <span class="text-sm text-gray-900"><?=Format::phone($ticket->getPhoneNumber())?></span>
            </div>
        </div>
    </div>
</div>

<!-- Thread -->
<h3 class="text-lg font-heading font-semibold text-gray-900 mb-4 flex items-center gap-2">
    <i data-lucide="message-square" class="w-5 h-5 text-gray-400"></i> Содержимое заявки
</h3>

<div id="ticketthread" class="space-y-4 mb-8">
    <?php
    $sql='SELECT msg.*, count(attach_id) as attachments  FROM '.TICKET_MESSAGE_TABLE.' msg '.
        ' LEFT JOIN '.TICKET_ATTACHMENT_TABLE.' attach ON  msg.ticket_id=attach.ticket_id AND msg.msg_id=attach.ref_id AND ref_type=\'M\' '.
        ' WHERE  msg.ticket_id='.db_input($ticket->getId()).
        ' GROUP BY msg.msg_id ORDER BY created';
    $msgres =db_query($sql);
    while ($msg_row = db_fetch_array($msgres)):
        ?>
        <!-- Client message -->
        <div class="thread-message-client">
            <div class="px-4 sm:px-5 py-3 border-b border-gray-100 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-1 sm:gap-2">
                <div class="flex items-center gap-2">
                    <div class="w-7 h-7 rounded-full bg-emerald-100 flex items-center justify-center flex-shrink-0">
                        <i data-lucide="user" class="w-3.5 h-3.5 text-emerald-600"></i>
                    </div>
                    <span class="text-sm font-medium text-gray-900 truncate"><?=Format::htmlchars($ticket->getName())?></span>
                </div>
                <span class="text-xs text-gray-400 flex items-center gap-1 ml-9 sm:ml-0">
                    <i data-lucide="clock" class="w-3 h-3"></i> <?=Format::db_daydatetime($msg_row['created'])?>
                </span>
            </div>
            <div class="p-4 sm:p-5 text-sm text-gray-700 leading-relaxed prose prose-sm max-w-none break-words overflow-hidden">
                <?=Format::display($msg_row['message'])?>
            </div>
            <?php if($msg_row['attachments']>0) { ?>
                <div class="px-4 sm:px-5 py-3 border-t border-gray-100 bg-gray-50/50 text-sm text-gray-500 break-words">
                    <i data-lucide="paperclip" class="w-3.5 h-3.5 inline"></i> <?=$ticket->getAttachmentStr($msg_row['msg_id'],'M')?>
                </div>
            <?php } ?>
        </div>

        <?php
        $sql='SELECT resp.*,count(attach_id) as attachments FROM '.TICKET_RESPONSE_TABLE.' resp '.
            ' LEFT JOIN '.TICKET_ATTACHMENT_TABLE.' attach ON  resp.ticket_id=attach.ticket_id AND resp.response_id=attach.ref_id AND ref_type=\'R\' '.
            ' WHERE msg_id='.db_input($msg_row['msg_id']).' AND resp.ticket_id='.db_input($ticket->getId()).
            ' GROUP BY resp.response_id ORDER BY created';
        $resp =db_query($sql);
        while ($resp_row = db_fetch_array($resp)) {
            $respID=$resp_row['response_id'];
            $name=$cfg->hideStaffName()?'staff':Format::htmlchars($resp_row['staff_name']);
            ?>
            <!-- Staff response -->
            <div class="thread-message-staff ml-2 sm:ml-6">
                <div class="px-4 sm:px-5 py-3 border-b border-gray-100 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-1 sm:gap-2">
                    <div class="flex items-center gap-2">
                        <div class="w-7 h-7 rounded-full bg-indigo-100 flex items-center justify-center flex-shrink-0">
                            <i data-lucide="headphones" class="w-3.5 h-3.5 text-indigo-600"></i>
                        </div>
                        <span class="text-sm font-medium text-gray-900 truncate"><?=$name?></span>
                    </div>
                    <span class="text-xs text-gray-400 flex items-center gap-1 ml-9 sm:ml-0">
                        <i data-lucide="clock" class="w-3 h-3"></i> <?=Format::db_daydatetime($resp_row['created'])?>
                    </span>
                </div>
                <div class="p-4 sm:p-5 text-sm text-gray-700 leading-relaxed prose prose-sm max-w-none break-words overflow-hidden">
                    <?=Format::display($resp_row['response'])?>
                </div>
                <?php if($resp_row['attachments']>0) { ?>
                    <div class="px-4 sm:px-5 py-3 border-t border-gray-100 bg-gray-50/50 text-sm text-gray-500 break-words">
                        <i data-lucide="paperclip" class="w-3.5 h-3.5 inline"></i> <?=$ticket->getAttachmentStr($respID,'R')?>
                    </div>
                <?php } ?>
            </div>
        <?php
        }
        $msgid =$msg_row['msg_id'];
    endwhile;
    ?>
</div>

<!-- Reply Form -->
<div class="card" id="reply">
    <div class="card-header">
        <div class="flex items-center gap-2">
            <i data-lucide="reply" class="w-4 h-4 text-indigo-500"></i>
            <h3 class="font-semibold text-gray-900 text-sm">Ответить на заявку</h3>
        </div>
    </div>
    <div class="card-body">
        <?php if($ticket->isClosed()) { ?>
            <div class="alert-info mb-4">
                <i data-lucide="info" class="w-4 h-4 flex-shrink-0"></i>
                <span>Заявка обрабатывается менеджером</span>
            </div>
        <?php } ?>

        <form action="view.php?id=<?=$id?>#reply" name="reply" method="post" enctype="multipart/form-data" class="space-y-4">
            <input type="hidden" name="id" value="<?=$ticket->getExtId()?>">
            <input type="hidden" name="respid" value="<?=$respID?>">
            <input type="hidden" name="a" value="postmessage">
            <?=Misc::csrfField()?>

            <div class="form-group">
                <label for="message" class="label">Сообщение <span class="text-red-500">*</span></label>
                <textarea name="message" id="message" class="textarea <?=$errors['message']?'input-error':''?>" rows="6" required><?=$info['message']?></textarea>
                <?php if($errors['message']) { ?>
                    <span class="form-error"><?=$errors['message']?></span>
                <?php } ?>
            </div>

            <?php if($cfg->allowOnlineAttachments()) { ?>
            <div class="form-group">
                <label for="attachment" class="label">Файл</label>
                <input type="file" name="attachment" id="attachment"
                       class="block w-full text-sm text-gray-500 file:mr-4 file:py-2.5 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-indigo-50 file:text-indigo-600 hover:file:bg-indigo-100 cursor-pointer"
                       value="<?=$info['attachment']?>" />
                <?php if($errors['attachment']) { ?>
                    <span class="form-error"><?=$errors['attachment']?></span>
                <?php } ?>
            </div>
            <?php } ?>

            <div class="flex flex-wrap gap-3 pt-4 border-t border-gray-100">
                <button type="submit" class="btn-primary">
                    <i data-lucide="reply" class="w-4 h-4"></i> Ответить
                </button>
                <button type="reset" class="btn-secondary">
                    <i data-lucide="eraser" class="w-4 h-4"></i> Очистить
                </button>
                <button type="button" class="btn-ghost" onclick="window.location.href='view.php'">
                    <i data-lucide="x" class="w-4 h-4"></i> Отмена
                </button>
            </div>
        </form>
    </div>
</div>
