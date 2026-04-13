<?php
if(!defined('OSTADMININC') || !$thisuser->isadmin() || !is_object($template)) die('Доступ запрещён');
$tpl=($errors && $_POST)?Format::input($_POST):Format::htmlchars($template->getInfo());
?>
<h2 class="text-lg font-heading font-semibold text-gray-900 mb-4">Email Шаблоны</h2>
<form action="admin.php?t=templates" method="post">
    <?php echo Misc::csrfField(); ?>
    <input type="hidden" name="t" value="templates">
    <input type="hidden" name="do" value="update">
    <input type="hidden" name="id" value="<?=$template->getId()?>">

<div class="card mb-6">
    <div class="card-header"><h3 class="font-heading font-semibold">Информация о шаблоне</h3></div>
    <div class="card-body space-y-5">
        <p class="text-gray-500 text-sm font-semibold">Последнее обновление: <?=Format::db_daydatetime($template->getUpdateDate())?></p>

        <div class="form-group">
            <label class="label">Имя</label>
            <input class="input" type="text" name="name" value="<?=$tpl['name']?>">
            <span class="form-error">*&nbsp;<?=$errors['name'] ?? ''?></span>
        </div>
        <div class="form-group">
            <label class="label">Внутренние заметки:</label>
            <p class="form-hint">Административные заметки</p>
            <span class="form-error">&nbsp;<?=$errors['notes'] ?? ''?></span>
            <textarea class="textarea" rows="5" name="notes"><?=$tpl['notes']?></textarea>
        </div>
    </div>
</div>

<h3 class="text-lg font-heading font-semibold text-gray-900 mb-4">Пользователь</h3>

<div class="card mb-6">
    <div class="card-header"><h3 class="font-heading font-semibold">Автоответ на новую заявку</h3></div>
    <div class="card-body space-y-5">
        <p class="text-gray-500 text-sm">Автоответ, отправляемый пользователю при создании новой заявки (если включён).
            Предназначен для предоставления пользователю номера заявки, по которому можно проверить статус онлайн.</p>

        <div class="form-group">
            <label class="label">Тема</label>
            <input class="input" type="text" name="ticket_autoresp_subj" value="<?=$tpl['ticket_autoresp_subj']?>">
            <span class="form-error">&nbsp;<?=$errors['ticket_autoresp_subj'] ?? ''?></span>
        </div>
        <div class="form-group">
            <label class="label">Текст сообщения:</label>
            <textarea class="textarea" rows="7" name="ticket_autoresp_body"><?=$tpl['ticket_autoresp_body']?></textarea>
            <span class="form-error">&nbsp;<?=$errors['ticket_autoresp_body'] ?? ''?></span>
        </div>
    </div>
</div>

<div class="card mb-6">
    <div class="card-header"><h3 class="font-heading font-semibold">New Message Autoresponse</h3></div>
    <div class="card-body space-y-5">
        <p class="text-gray-500 text-sm">Confirmation sent to user when a new message is appended to an existing ticket. (email and web replies)</p>

        <div class="form-group">
            <label class="label">Тема</label>
            <input class="input" type="text" name="message_autoresp_subj" value="<?=$tpl['message_autoresp_subj']?>">
            <span class="form-error">&nbsp;<?=$errors['message_autoresp_subj'] ?? ''?></span>
        </div>
        <div class="form-group">
            <label class="label">Текст сообщения:</label>
            <textarea class="textarea" rows="7" name="message_autoresp_body"><?=$tpl['message_autoresp_body']?></textarea>
            <span class="form-error">&nbsp;<?=$errors['message_autoresp_body'] ?? ''?></span>
        </div>
    </div>
</div>

<div class="card mb-6">
    <div class="card-header"><h3 class="font-heading font-semibold">New Ticket Notice</h3></div>
    <div class="card-body space-y-5">
        <p class="text-gray-500 text-sm">Notice sent to user, if enabled, on new ticket <b>created by staff</b> on their behalf.</p>

        <div class="form-group">
            <label class="label">Тема</label>
            <input class="input" type="text" name="ticket_notice_subj" value="<?=$tpl['ticket_notice_subj']?>">
            <span class="form-error">&nbsp;<?=$errors['ticket_notice_subj'] ?? ''?></span>
        </div>
        <div class="form-group">
            <label class="label">Текст сообщения:</label>
            <textarea class="textarea" rows="7" name="ticket_notice_body"><?=$tpl['ticket_notice_body']?></textarea>
            <span class="form-error">&nbsp;<?=$errors['ticket_notice_body'] ?? ''?></span>
        </div>
    </div>
</div>

<div class="card mb-6">
    <div class="card-header"><h3 class="font-heading font-semibold">Over Ticket limit Notice</h3></div>
    <div class="card-body space-y-5">
        <p class="text-gray-500 text-sm">A one time notice sent when user has reached the max allowed open tickets defined in preferences.
            <br/>Admin email gets alert each time a support ticket request is denied.</p>

        <div class="form-group">
            <label class="label">Тема</label>
            <input class="input" type="text" name="ticket_overlimit_subj" value="<?=$tpl['ticket_overlimit_subj']?>">
            <span class="form-error">&nbsp;<?=$errors['ticket_overlimit_subj'] ?? ''?></span>
        </div>
        <div class="form-group">
            <label class="label">Текст сообщения:</label>
            <textarea class="textarea" rows="7" name="ticket_overlimit_body"><?=$tpl['ticket_overlimit_body']?></textarea>
            <span class="form-error">&nbsp;<?=$errors['ticket_overlimit_body'] ?? ''?></span>
        </div>
    </div>
</div>

<div class="card mb-6">
    <div class="card-header"><h3 class="font-heading font-semibold">Ticket Response/Reply</h3></div>
    <div class="card-body space-y-5">
        <p class="text-gray-500 text-sm">Message template used when responding to a ticket or simply alerting the user about a response/answer availability.</p>

        <div class="form-group">
            <label class="label">Тема</label>
            <input class="input" type="text" name="ticket_reply_subj" value="<?=$tpl['ticket_reply_subj']?>">
            <span class="form-error">&nbsp;<?=$errors['ticket_reply_subj'] ?? ''?></span>
        </div>
        <div class="form-group">
            <label class="label">Текст сообщения:</label>
            <textarea class="textarea" rows="7" name="ticket_reply_body"><?=$tpl['ticket_reply_body']?></textarea>
            <span class="form-error">&nbsp;<?=$errors['ticket_reply_body'] ?? ''?></span>
        </div>
    </div>
</div>

<h3 class="text-lg font-heading font-semibold text-gray-900 mb-4">Staff</h3>

<div class="card mb-6">
    <div class="card-header"><h3 class="font-heading font-semibold">New Ticket Alert</h3></div>
    <div class="card-body space-y-5">
        <p class="text-gray-500 text-sm">Alert sent to staff ( if enabled) on new ticket.</p>

        <div class="form-group">
            <label class="label">Тема</label>
            <input class="input" type="text" name="ticket_alert_subj" value="<?=$tpl['ticket_alert_subj']?>">
            <span class="form-error">&nbsp;<?=$errors['ticket_alert_subj'] ?? ''?></span>
        </div>
        <div class="form-group">
            <label class="label">Текст сообщения:</label>
            <textarea class="textarea" rows="7" name="ticket_alert_body"><?=$tpl['ticket_alert_body']?></textarea>
            <span class="form-error">&nbsp;<?=$errors['ticket_alert_body'] ?? ''?></span>
        </div>
    </div>
</div>

<div class="card mb-6">
    <div class="card-header"><h3 class="font-heading font-semibold">New Message Alert</h3></div>
    <div class="card-body space-y-5">
        <p class="text-gray-500 text-sm">Alert sent to staff ( if enabled) when user replies to an existing ticket.</p>

        <div class="form-group">
            <label class="label">Тема</label>
            <input class="input" type="text" name="message_alert_subj" value="<?=$tpl['message_alert_subj']?>">
            <span class="form-error">&nbsp;<?=$errors['message_alert_subj'] ?? ''?></span>
        </div>
        <div class="form-group">
            <label class="label">Текст сообщения:</label>
            <textarea class="textarea" rows="7" name="message_alert_body"><?=$tpl['message_alert_body']?></textarea>
            <span class="form-error">&nbsp;<?=$errors['message_alert_body'] ?? ''?></span>
        </div>
    </div>
</div>

<div class="card mb-6">
    <div class="card-header"><h3 class="font-heading font-semibold">New Internal Note Alert</h3></div>
    <div class="card-body space-y-5">
        <p class="text-gray-500 text-sm">Alert sent to selected staff ( if enabled) when an internal note is appended to a ticket.</p>

        <div class="form-group">
            <label class="label">Тема</label>
            <input class="input" type="text" name="note_alert_subj" value="<?=$tpl['note_alert_subj']?>">
            <span class="form-error">&nbsp;<?=$errors['note_alert_subj'] ?? ''?></span>
        </div>
        <div class="form-group">
            <label class="label">Текст сообщения:</label>
            <textarea class="textarea" rows="7" name="note_alert_body"><?=$tpl['note_alert_body']?></textarea>
            <span class="form-error">&nbsp;<?=$errors['note_alert_body'] ?? ''?></span>
        </div>
    </div>
</div>

<div class="card mb-6">
    <div class="card-header"><h3 class="font-heading font-semibold">Ticket Assigned Alert/Notice</h3></div>
    <div class="card-body space-y-5">
        <p class="text-gray-500 text-sm">Alert sent to staff on ticket assignment.</p>

        <div class="form-group">
            <label class="label">Тема</label>
            <input class="input" type="text" name="assigned_alert_subj" value="<?=$tpl['assigned_alert_subj']?>">
            <span class="form-error">&nbsp;<?=$errors['assigned_alert_subj'] ?? ''?></span>
        </div>
        <div class="form-group">
            <label class="label">Текст сообщения:</label>
            <textarea class="textarea" rows="7" name="assigned_alert_body"><?=$tpl['assigned_alert_body']?></textarea>
            <span class="form-error">&nbsp;<?=$errors['assigned_alert_body'] ?? ''?></span>
        </div>
    </div>
</div>

<div class="card mb-6">
    <div class="card-header"><h3 class="font-heading font-semibold">Overdue/Stale Ticket Alert/Notice</h3></div>
    <div class="card-body space-y-5">
        <p class="text-gray-500 text-sm">Alert sent to staff on stale or overdue tickets.</p>

        <div class="form-group">
            <label class="label">Тема</label>
            <input class="input" type="text" name="ticket_overdue_subj" value="<?=$tpl['ticket_overdue_subj']?>">
            <span class="form-error">&nbsp;<?=$errors['ticket_overdue_subj'] ?? ''?></span>
        </div>
        <div class="form-group">
            <label class="label">Текст сообщения:</label>
            <textarea class="textarea" rows="7" name="ticket_overdue_body"><?=$tpl['ticket_overdue_body']?></textarea>
            <span class="form-error">&nbsp;<?=$errors['ticket_overdue_body'] ?? ''?></span>
        </div>
    </div>
</div>

<div class="flex items-center gap-3 mt-6">
    <button class="btn-primary" type="submit" name="submit" value="Save Changes"><i data-lucide="save" class="w-4 h-4"></i> Save Changes</button>
    <button class="btn-secondary" type="reset" name="reset">Reset Changes</button>
    <button class="btn-ghost" type="button" name="cancel" onClick='window.location.href="admin.php?t=email"'>Cancel Edit</button>
</div>
</form>
