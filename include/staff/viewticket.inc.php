<?php
if(!defined('OSTSCPINC') || !@$thisuser->isStaff() || !is_object($ticket) ) die('Invalid path');
if(!$ticket->getId() or (!$thisuser->canAccessDept($ticket->getDeptId()) and $thisuser->getId()!=$ticket->getStaffId() and !in_array($thisuser->getId(), explode(',', str_replace('*', '', $ticket->getStaffsIdWithStar()))))) die('Доступ запрещён');

$info=($_POST && $errors)?Format::input($_POST):array();

if($cfg->getLockTime() && !$ticket->acquireLock())
    $warn.='Не удалось заблокировать заявку';

$dept  = $ticket->getDept();
$staff = $ticket->getStaff();
$lock  = $ticket->getLock();
$id=$ticket->getId();
$staffs_id = $ticket->getStaffsId();

if($staff)
    $warn.='&nbsp;&nbsp;<span class="inline-flex items-center gap-1"><i data-lucide="user" class="w-4 h-4"></i> Заявка назначена на '.Format::htmlchars($staff->getName()).'</span>';
if(!$errors['err'] && ($lock && $lock->getStaffId()!=$thisuser->getId()))
    $errors['err']='Этот запрос заблокирован другим менеджером!';
if(!$errors['err'] && ($emailBanned=BanList::isbanned($ticket->getEmail())))
    $errors['err']='Email в чёрном списке! Необходимо удалить перед ответом';
if($ticket->isOverdue())
    $warn.='&nbsp;&nbsp;<span class="inline-flex items-center gap-1"><i data-lucide="alert-triangle" class="w-4 h-4 text-red-500"></i> Отмечена как просроченная!</span>';

?>

<div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 mb-4">
    <div>
        <h4 class="text-lg font-semibold text-gray-900 flex items-center gap-2">
            <i data-lucide="ticket" class="w-5 h-5"></i> Заявка № <?=$ticket->getExtId()?>&nbsp;
            <a href="tickets.php?id=<?=$id?>" title="Обновить" class="text-gray-400 hover:text-gray-600"><i data-lucide="refresh-cw" class="w-4 h-4"></i></a>
        </h4>
    </div>
    <div class="flex items-center gap-2">
        <? if($thisuser->canEditTickets() || ($thisuser->isManager() && $dept->getId()==$thisuser->getDeptId())) { ?>
            <a href="tickets.php?id=<?=$id?>&a=edit" title="Изменить заявку" class="btn-secondary btn-sm">
                <i data-lucide="edit" class="w-4 h-4"></i> Изменить Заявку
            </a>
        <?}?>
        <a href="tasks.php?a=add&ticket_id=<?php echo $ticket->getId(); ?>" class="btn-secondary btn-sm" title="Создать задачу по этой заявке">
            <i data-lucide="check-circle" class="w-4 h-4"></i> Создать задачу
        </a>
    </div>
</div>

<div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
    <div class="card">
        <div class="card-body p-0">
            <table class="table-modern w-full">
                <tr>
                    <th class="table-th w-40">Статус:</th>
                    <td class="table-td"><?=Ticket::GetRusStatus($ticket->getStatus())?></td>
                </tr>
                <tr>
                    <th class="table-th w-40">Приоритет:</th>
                    <td class="table-td"><?=Format::htmlchars($ticket->getPriority())?></td>
                </tr>
                <tr>
                    <th class="table-th w-40">Отдел:</th>
                    <td class="table-td"><?=Format::htmlchars($ticket->getDeptName())?></td>
                </tr>
                <tr>
                    <th class="table-th w-40">Дата Создания:</th>
                    <td class="table-td"><?=Format::db_datetime($ticket->getCreateDate())?></td>
                </tr>
            </table>
        </div>
    </div>
    <div class="card">
        <div class="card-body p-0">
            <table class="table-modern w-full">
                <tr>
                    <th class="table-th w-40">Имя:</th>
                    <td class="table-td"><?=Format::htmlchars($ticket->getName())?></td>
                </tr>
                <tr>
                    <th class="table-th w-40">Email:</th>
                    <td class="table-td"><?php
                        echo Format::htmlchars($ticket->getEmail());
                        if(($related=$ticket->getRelatedTicketsCount())) {
                            echo sprintf('&nbsp;&nbsp;<a href="tickets.php?a=search&query=%s" title="Связанные заявки" class="text-indigo-600 hover:underline">(<b>%d</b>)</a>',
                                        urlencode($ticket->getEmail()),$related);
                        }
                        ?>
                    </td>
                </tr>
                <tr>
                    <th class="table-th w-40">Номер кабинета:</th>
                    <td class="table-td"><?=Format::phone($ticket->getPhoneNumber())?></td>
                </tr>
                <tr>
                    <th class="table-th w-40">Источник:</th>
                    <td class="table-td"><?=Format::htmlchars($ticket->getSource())?></td>
                </tr>
            </table>
        </div>
    </div>
</div>

<div class="bg-gray-50 rounded-xl p-4 border border-gray-200 mb-4">
    <strong class="inline-flex items-center gap-1"><i data-lucide="tag" class="w-4 h-4"></i> Тема:</strong> <?=Format::htmlchars($ticket->getSubject())?>
</div>

<div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
    <div class="card">
        <div class="card-body p-0">
            <table class="table-modern w-full">
                <tr>
                    <th class="table-th w-44">Ответственный:</th>
                    <td class="table-td"><?=$staff?Format::htmlchars($staff->getName()):'- не назначен -'?></td>
                </tr>
                <tr>
                    <th class="table-th w-44">Исполнители:</th>
                    <?
                        $stafffio = $staff ? $ticket->GetShortFIO($staff->getName()) : '';
                        $morestaffs_fio = $ticket->getStaffsIdNames($ticket->getStaffsIdWithStar(), $thisuser->getId());
                        $zpt = (!empty($stafffio) && !empty($morestaffs_fio)) ? ', ' : '';
                        $fios = $stafffio.$zpt.$morestaffs_fio;
                    ?>
                    <td class="table-td"><?=!empty($fios)?$fios:'- не назначены -'?></td>
                </tr>
                <tr>
                    <th class="table-th w-44">Последний Ответ:</th>
                    <td class="table-td"><?=Format::db_datetime($ticket->getLastResponseDate())?></td>
                </tr>
                <?php
                if($ticket->isOpen()){ ?>
                <tr>
                    <th class="table-th w-44">Истекшая дата:</th>
                    <td class="table-td"><?=Format::db_datetime($ticket->getDueDate())?></td>
                </tr>
                <?php
                }else { ?>
                <tr>
                    <th class="table-th w-44">Дата Закрытия:</th>
                    <td class="table-td"><?=Format::db_datetime($ticket->getCloseDate())?></td>
                </tr>
                <?php
                }
                ?>
            </table>
        </div>
    </div>
    <div class="card">
        <div class="card-body p-0">
            <table class="table-modern w-full">
                <tr><th class="table-th w-44">Тема обращения:</th>
                    <td class="table-td"><?
                        $ht=$ticket->getHelpTopic();
                        echo Format::htmlchars($ht?$ht:'N/A');
                        ?>
                    </td>
                </tr>
                <tr>
                    <th class="table-th w-44">IP Адрес:</th>
                    <td class="table-td"><?=Format::htmlchars($ticket->getIP())?></td>
                </tr>
                <tr><th class="table-th w-44">Последнее Сообщение:</th>
                    <td class="table-td"><?=Format::db_datetime($ticket->getLastMessageDate())?></td>
                </tr>
            </table>
        </div>
    </div>
</div>

<?
if($thisuser->canManageTickets() || $thisuser->isManager()){ ?>
<div class="card mb-4">
    <div class="card-body">
        <form name="action" action="tickets.php?id=<?=$id?>" method="post" class="flex flex-wrap items-center gap-3">
            <?php echo Misc::csrfField(); ?>
            <input type="hidden" name="ticket_id" value="<?=$id?>">
            <input type="hidden" name="a" value="process">
            <strong class="inline-flex items-center gap-1"><i data-lucide="settings" class="w-4 h-4"></i> Действие:</strong>
            <select id="do" name="do" class="select"
              onChange="this.form.ticket_priority.disabled=strcmp(this.options[this.selectedIndex].value,'change_priority','reopen','overdue')?false:true;">
                <option value="">Выберите</option>
                <option value="change_priority" <?=($info['do'] ?? '')==='change_priority'?'selected':''?> >Изменить Приоритет</option>
                <?if(!$ticket->isoverdue()){ ?>
                <option value="overdue" <?=($info['do'] ?? '')==='overdue'?'selected':''?> >Пометить истекшим</option>
                <?}?>
                <?if($ticket->isAssigned()){ ?>
                <option value="release" <?=($info['do'] ?? '')==='release'?'selected':''?> >Освободить ответственного</option>
                <?}?>

                <?if($thisuser->canCloseTickets()){
                    if($ticket->isOpen()){?>
                     <option value="close" <?=($info['do'] ?? '')==='close'?'selected':''?> >Закрыть заявку</option>
                    <?}else{?>
                        <option value="reopen" <?=($info['do'] ?? '')==='reopen'?'selected':''?> >Открыть заявку</option>
                    <?}
                }?>
                <?php
                 if($thisuser->canManageBanList()) {
                    if(!$emailBanned) {?>
                        <option value="banemail">Заблокировать Email <?=$ticket->isOpen()?'&amp; Закрыть':''?></option>
                    <?}else{?>
                        <option value="unbanemail">Разблокировать Email</option>
                    <?}
                 }?>

                <?if($thisuser->canDeleteTickets()){ ?>
                <option value="delete">Удалить заявку</option>
                <?}?>
            </select>

            <strong>Приоритет:</strong>
            <select id="ticket_priority" name="ticket_priority" class="select" <?=empty($info['do'])?'disabled':''?> >
                <option value="0" selected="selected">-неизменяемый-</option>
                <?
                $priorityId=$ticket->getPriorityId();
                $resp=db_query('SELECT priority_id,priority_desc FROM '.TICKET_PRIORITY_TABLE);
                while($row=db_fetch_array($resp)){ ?>
                    <option value="<?=$row['priority_id']?>" <?=$priorityId==$row['priority_id']?'disabled':''?> ><?=Format::htmlchars($row['priority_desc'])?></option>
                <?}?>
            </select>

            <input type="hidden" name="secret" value="<?=$_SESSION["secret"]?>" />
            <input type="hidden" name="old_viewticket_id" value="<?=$ticket->id?>" />
            <input class="btn-primary btn-sm" type="submit" value="GO">
        </form>
    </div>
</div>
<?}?>

<?php if(!empty($errors['err'])) { ?>
    <div class="alert-danger text-center mb-4"><?=Format::htmlchars($errors['err'])?></div>
<?php } elseif(!empty($msg)) { ?>
    <div class="alert-success text-center mb-4"><?=Format::htmlchars($msg)?></div>
<?php } ?>
<?php if(!empty($warn)) { ?>
    <div class="alert-warning mb-4"><?=$warn?></div>
<?php } ?>

<?
$sql ='SELECT note_id,title,note,source,created FROM '.TICKET_NOTE_TABLE.' WHERE ticket_id='.db_input($id).' ORDER BY created DESC';
if(($resp=db_query($sql)) && ($notes=db_num_rows($resp))){
    $display=($notes>5)?'none':'block';
?>
<div class="mb-4">
    <a href="#" onClick="toggleLayer('ticketnotes'); return false;" class="font-bold text-gray-700 hover:text-gray-900 inline-flex items-center gap-1">
        <i data-lucide="sticky-note" class="w-4 h-4"></i> Внутренние Сообщения (<?=$notes?>)
    </a>
    <div id="ticketnotes" style="display:<?=$display?>;" class="mt-3 space-y-3">
        <?
        while($row=db_fetch_array($resp)) {?>
        <div class="thread-message-note">
            <div class="card-header text-sm text-gray-600"><?=Format::db_daydatetime($row['created'])?>&nbsp;-&nbsp; написал <?=Format::htmlchars($row['source'])?></div>
            <? if($row['title']) {?>
            <div class="px-4 py-2 bg-gray-50 border-b border-gray-200 text-sm flex items-center gap-1"><i data-lucide="paperclip" class="w-3 h-3"></i> <?=Format::display($row['title'])?></div>
            <?} ?>
            <div class="card-body text-sm"><?=Format::display($row['note'])?></div>
        </div>
     <?} ?>
   </div>
</div>
<?} ?>

<div class="mb-4">
    <a href="#" onClick="toggleLayer('ticketthread'); return false;" class="font-bold text-gray-700 hover:text-gray-900 inline-flex items-center gap-1">
        <i data-lucide="message-circle" class="w-4 h-4"></i> Заголовок заявки
    </a>
    <div id="ticketthread" class="mt-3 space-y-3">
	<?
        $sql='SELECT msg.msg_id,msg.created,msg.message,count(attach_id) as attachments FROM '.TICKET_MESSAGE_TABLE.' msg '.
            ' LEFT JOIN '.TICKET_ATTACHMENT_TABLE." attach ON msg.ticket_id=attach.ticket_id AND msg.msg_id=attach.ref_id AND ref_type='M' ".
            ' WHERE msg.ticket_id='.db_input($id).
            ' GROUP BY msg.msg_id ORDER BY created';

	    $messages = array();
	    $msgres = db_query($sql);
	    if ($msgres && db_num_rows($msgres)) {
	        while ($msg_row = db_fetch_array($msgres)) {
	            $messages[$msg_row['msg_id']] = $msg_row;
	        }
	    }

	    $responses = array();
	    if (!empty($messages)) {
	        $sql='SELECT resp.*,count(attach_id) as attachments FROM '.TICKET_RESPONSE_TABLE.' resp '.
	            ' LEFT JOIN '.TICKET_ATTACHMENT_TABLE." attach ON resp.ticket_id=attach.ticket_id AND resp.response_id=attach.ref_id AND ref_type='R' ".
	            ' WHERE resp.ticket_id='.db_input($id).
	            ' GROUP BY resp.response_id ORDER BY resp.msg_id, resp.created';

	        $respres = db_query($sql);
	        if ($respres && db_num_rows($respres)) {
	            while ($resp_row = db_fetch_array($respres)) {
	                $msg_id = $resp_row['msg_id'];
	                if (!isset($responses[$msg_id])) {
	                    $responses[$msg_id] = array();
	                }
	                $responses[$msg_id][] = $resp_row;
	            }
	        }
	    }

	    foreach ($messages as $msg_id => $msg_row) {
		    ?>
		    <div class="thread-message-client">
		        <div class="card-header text-sm"><?=Format::db_daydatetime($msg_row['created'])?></div>
                <?if($msg_row['attachments']>0){ ?>
                <div class="px-4 py-2 bg-gray-50 border-b border-gray-200 text-sm flex items-center gap-1"><i data-lucide="paperclip" class="w-3 h-3"></i> <?=$ticket->getAttachmentStr($msg_row['msg_id'],'M')?></div>
                <?}?>
                <div class="card-body text-sm"><?=Format::display($msg_row['message'])?>&nbsp;</div>
		    </div>
            <?
            if (isset($responses[$msg_id])) {
                foreach ($responses[$msg_id] as $resp_row) {
                    $respID=$resp_row['response_id'];
                    ?>
        		    <div class="thread-message-staff ml-8">
        		        <div class="card-header text-sm"><?=Format::db_daydatetime($resp_row['created'])?>&nbsp;-&nbsp;<?=Format::htmlchars($resp_row['staff_name'])?></div>
                        <?if($resp_row['attachments']>0){ ?>
                        <div class="px-4 py-2 bg-gray-50 border-b border-gray-200 text-sm flex items-center gap-1"><i data-lucide="paperclip" class="w-3 h-3"></i> <?=$ticket->getAttachmentStr($respID,'R')?></div>
                        <?}?>
    			        <div class="card-body text-sm"><?=Format::display($resp_row['response'])?></div>
    		        </div>
    	        <?}
            }
            $msgid = $msg_row['msg_id'];
	    }?>
    </div>
</div>

<div class="card mb-4">
    <!-- Горизонтальные вкладки действий -->
    <div class="flex border-b border-gray-200" id="action-tabs">
        <button type="button" class="action-tab active px-5 py-3 text-sm font-medium border-b-2 border-indigo-500 text-indigo-600 bg-white focus:outline-none" data-tab="reply" onclick="switchActionTab('reply')">
            <i data-lucide="reply" class="w-4 h-4 inline-block mr-1 align-text-bottom"></i> Ответ
        </button>
        <button type="button" class="action-tab px-5 py-3 text-sm font-medium border-b-2 border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 bg-white focus:outline-none" data-tab="notes" onclick="switchActionTab('notes')">
            <i data-lucide="file-text" class="w-4 h-4 inline-block mr-1 align-text-bottom"></i> Внутреннее сообщение
        </button>
        <?php if($thisuser->canTransferTickets()) { ?>
        <button type="button" class="action-tab px-5 py-3 text-sm font-medium border-b-2 border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 bg-white focus:outline-none" data-tab="transfer" onclick="switchActionTab('transfer')">
            <i data-lucide="arrow-right-left" class="w-4 h-4 inline-block mr-1 align-text-bottom"></i> Перенос в другой отдел
        </button>
        <?php } ?>
        <?php if(!$ticket->isAssigned() || $thisuser->isadmin()  || $thisuser->isManager() || $thisuser->getId()==$ticket->getStaffId() || in_array($thisuser->getId(), explode(',', str_replace('*', '', $ticket->getStaffsIdWithStar()))) || ($ticket->getStaff() && $thisuser->getDeptId() == $ticket->getStaff()->getDeptId())) { ?>
        <button type="button" class="action-tab px-5 py-3 text-sm font-medium border-b-2 border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 bg-white focus:outline-none" data-tab="assign" onclick="switchActionTab('assign')">
            <i data-lucide="user-plus" class="w-4 h-4 inline-block mr-1 align-text-bottom"></i> <?=$staff?'Изменить ответственного':'Назначить ответственного'?>
        </button>
        <?php } ?>
    </div>

    <!-- Панель: Ответ -->
    <div id="tab-reply" class="action-tab-panel p-4">
        <form action="tickets.php?id=<?=$id?>" name="reply" id="replyform" method="post" enctype="multipart/form-data">
            <?php echo Misc::csrfField(); ?>
            <input type="hidden" name="ticket_id" value="<?=$id?>">
            <input type="hidden" name="msg_id" value="<?=$msgid?>">
            <input type="hidden" name="a" value="reply">
            <div class="form-group">
                <span class="text-red-500 text-sm">&nbsp;<?=Format::htmlchars($errors['response'] ?? '')?></span>
            </div>
            <div class="form-group">
               <?
                 $sql='SELECT premade_id,title FROM '.KB_PREMADE_TABLE.' WHERE isenabled=1 '.
                    ' AND (dept_id=0 OR dept_id='.db_input($ticket->getDeptId()).')';
                $canned=db_query($sql);
                if($canned && db_num_rows($canned)) {
                 ?>
                   <label class="label">Возможные ответы:</label>&nbsp;
                   <select id="canned" name="canned" class="select inline-block w-auto"
                    onChange="getCannedResponse(this.options[this.selectedIndex].value,this.form,'response');this.selectedIndex='0';" >
                    <option value="0" selected="selected">Выберите ответ</option>
                    <?while(list($cannedId,$title)=db_fetch_row($canned)) { ?>
                     <option value="<?=$cannedId?>" ><?=Format::htmlchars($title)?></option>
                    <?}?>
                   </select>&nbsp;&nbsp;&nbsp;<label class="inline-flex items-center gap-1 text-sm"><input type='checkbox' value='1' name=append checked="true" class="rounded border-gray-300" /> Добавить</label>
                <?}?>
                <textarea name="response" id="response" cols="90" rows="9" wrap="soft" class="textarea mt-2"><?=$info['response'] ?? ''?></textarea>
            </div>
            <?php if($cfg->canUploadFiles()){ ?>
            <div class="form-group">
                <label for="attachment" class="label inline-flex items-center gap-1"><i data-lucide="paperclip" class="w-4 h-4"></i> Вложенный файл:</label>
                <input type="file" name="attachment" id="attachment" value="<?=$info['attachment'] ?? ''?>" class="block text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100">
                    <span class="text-red-500 text-sm">&nbsp;<?=Format::htmlchars($errors['attachment'] ?? '')?></span>
            </div>
            <?php }?>
            <?
             $appendStaffSig=$thisuser->appendMySignature();
             $appendDeptSig=$dept->canAppendSignature();
             $info['signature']=empty($info['signature'])?'none':$info['signature'];
             if($appendStaffSig || $appendDeptSig) { ?>
              <div class="form-group">
                    <label class="label">Добавить подпись:</label>
                    <div class="flex items-center gap-4 mt-1">
                        <label class="inline-flex items-center gap-1 text-sm"><input type="radio" name="signature" value="none" checked class="text-indigo-600"> Нет</label>
                        <?if($appendStaffSig) {?>
                        <label class="inline-flex items-center gap-1 text-sm"><input type="radio" name="signature" value="mine" <?=$info['signature']=='mine'?'checked':''?> class="text-indigo-600"> Моя подпись</label>
                        <?}?>
                        <?if($appendDeptSig) {?>
                        <label class="inline-flex items-center gap-1 text-sm"><input type="radio" name="signature" value="dept" <?=$info['signature']=='dept'?'checked':''?> class="text-indigo-600"> Подпись отдела</label>
                        <?}?>
                    </div>
               </div>
             <?}?>
            <div class="form-group">
                <strong class="text-sm text-gray-700">Статус Запроса:</strong>
                <?
                $checked=isset($info['ticket_status'])?'checked':'';
                if($ticket->isOpen()){?>
                <label class="inline-flex items-center gap-1 text-sm ml-2"><input type="checkbox" name="ticket_status" id="l_ticket_status" value="Close" <?=$checked?> class="rounded border-gray-300"> Закрыть после ответа</label>
                <?}else{ ?>
                <label class="inline-flex items-center gap-1 text-sm ml-2"><input type="checkbox" name="ticket_status" id="l_ticket_status" value="Reopen" <?=$checked?> class="rounded border-gray-300"> Открыть после ответа</label>
                <?}?>
            </div>
            <div class="form-group mt-4 flex items-center gap-2">
                <input type="hidden" name="secret" value="<?=$_SESSION["secret"]?>" />
                <input type="hidden" name="old_viewticket_id" value="<?=$ticket->id?>" />
                <input class="btn-primary" type='submit' value='Ответить' />
                <input class="btn-secondary" type='reset' value='Очистить' />
                <input class="btn-secondary" type='button' value='Отмена' onClick="history.go(-1)" />
            </div>
        </form>
    </div>

    <!-- Панель: Внутреннее сообщение -->
    <div id="tab-notes" class="action-tab-panel p-4" style="display:none;">
        <form action="tickets.php?id=<?=$id?>" name="notes" class="inline" method="post" enctype="multipart/form-data">
            <?php echo Misc::csrfField(); ?>
            <input type="hidden" name="ticket_id" value="<?=$id?>">
            <input type="hidden" name="a" value="postnote">
            <div class="form-group">
                <label for="title" class="label">Заголовок:</label>
                <div class="flex items-center gap-2">
                    <input type="text" name="title" id="title" value="<?=$info['title'] ?? ''?>" class="input w-auto">
                    <span class="text-red-500 text-sm">*&nbsp;<?=Format::htmlchars($errors['title'] ?? '')?></span>
                </div>
            </div>
            <div class="form-group">
                <label for="note" class="label">Текст сообщения:</label>
                <span class="text-red-500 text-sm">*&nbsp;<?=Format::htmlchars($errors['note'] ?? '')?></span>
                <textarea name="note" id="note" cols="80" rows="7" wrap="soft" class="textarea"><?=$info['note'] ?? ''?></textarea>
            </div>

            <?
            if(!$ticket->isAssigned() || $thisuser->isadmin()  || $thisuser->isManager() || $thisuser->getId()==$ticket->getStaffId()) {
             ?>
            <div class="form-group">
                <strong class="text-sm text-gray-700">Статус Заявки:</strong>
                <?
                $checked=($info && isset($info['ticket_status']))?'checked':'';
                if($ticket->isOpen()){?>
                <label class="inline-flex items-center gap-1 text-sm ml-2"><input type="checkbox" name="ticket_status" id="ticket_status" value="Close" <?=$checked?> class="rounded border-gray-300"> Закрыть заявку</label>
                <?}else{ ?>
                <label class="inline-flex items-center gap-1 text-sm ml-2"><input type="checkbox" name="ticket_status" id="ticket_status" value="Reopen" <?=$checked?> class="rounded border-gray-300"> Открыть заявку</label>
                <?}?>
            </div>
            <?}?>
            <div class="form-group mt-4 flex items-center gap-2">
                <input type="hidden" name="secret" value="<?=$_SESSION["secret"]?>" />
                <input type="hidden" name="old_viewticket_id" value="<?=$ticket->id?>" />
                <input class="btn-primary" type='submit' value='Отправить' />
                <input class="btn-secondary" type='reset' value='Очистить' />
                <input class="btn-secondary" type='button' value='Отмена' onClick="history.go(-1)" />
            </div>
        </form>
    </div>

    <!-- Панель: Перенос в другой отдел -->
    <?php if($thisuser->canTransferTickets()) { ?>
    <div id="tab-transfer" class="action-tab-panel p-4" style="display:none;">
        <form action="tickets.php?id=<?=$id?>" name="transfer_form" method="post" enctype="multipart/form-data">
            <?php echo Misc::csrfField(); ?>
            <input type="hidden" name="ticket_id" value="<?=$id?>">
            <input type="hidden" name="a" value="transfer">
            <div class="form-group">
                <label for="dept_id" class="label">Отдел:</label>
                <div class="flex items-center gap-2">
                    <select id="dept_id" name="dept_id" class="select w-auto">
                        <option value="" selected="selected">-Выберите отдел-</option>
                        <?
                        $depts= db_query('SELECT dept_id,dept_name FROM '.DEPT_TABLE.' WHERE dept_id!='.db_input($ticket->getDeptId()));
                        while (list($deptId,$deptName) = db_fetch_row($depts)){
                            $selected = (($info['dept_id'] ?? '')==$deptId)?'selected':''; ?>
                            <option value="<?=(int)$deptId?>"<?=$selected?>><?=Format::htmlchars($deptName)?> Отдел </option>
                        <?
                        }?>
                    </select>
                    <span class="text-red-500 text-sm">&nbsp;*<?=Format::htmlchars($errors['dept_id'] ?? '')?></span>
                </div>
            </div>
            <div class="form-group">
                <label class="label">Комментарий/Причина для переноса. &nbsp;(<i>Внутреннее сообщение</i>)</label>
                <span class="text-red-500 text-sm">&nbsp;*<?=$errors['message'] ?? ''?></span>
                <textarea name="message" id="message" cols="80" rows="7" wrap="soft" class="textarea"><?=$info['message'] ?? ''?></textarea>
            </div>
            <div class="form-group mt-4 flex items-center gap-2">
                <input type="hidden" name="secret" value="<?=$_SESSION["secret"]?>" />
                <input type="hidden" name="old_viewticket_id" value="<?=$ticket->id?>" />
                <input class="btn-primary" type='submit' value='Перенести' />
                <input class="btn-secondary" type='reset' value='Очистить' />
                <input class="btn-secondary" type='button' value='Отмена' onClick="history.go(-1)" />
            </div>
        </form>
    </div>
    <?php } ?>

    <!-- Панель: Назначить ответственного -->
    <?php if(!$ticket->isAssigned() || $thisuser->isadmin()  || $thisuser->isManager() || $thisuser->getId()==$ticket->getStaffId() || in_array($thisuser->getId(), explode(',', str_replace('*', '', $ticket->getStaffsIdWithStar()))) || ($ticket->getStaff() && $thisuser->getDeptId() == $ticket->getStaff()->getDeptId())) { ?>
    <div id="tab-assign" class="action-tab-panel p-4" style="display:none;">
        <form action="tickets.php?id=<?=$id?>" name="assign_form" method="post" enctype="multipart/form-data">
            <?php echo Misc::csrfField(); ?>
            <input type="hidden" name="ticket_id" value="<?=$id?>">
            <input type="hidden" name="a" value="assign">
            <input type="hidden" name="staffId" id="staffId" value="<?=$ticket->staff_id?>">
            <div style="display: none;">
                <label class="label">Комментарий/сообщение для назначенного. &nbsp;(<i>Сохраняется как внутреннее сообщение</i>)</label>
                <span class="text-red-500 text-sm">&nbsp;<?=$errors['assign_message'] ?? ''?></span>
                <textarea name="assign_message" id="assign_message" cols="80" rows="7"
                    wrap="soft" class="textarea" placeholder="Не обязательно заполнять"><?=$info['assign_message'] ?? ''?></textarea>
            </div>
            <div class="form-group">
                <label class="label inline-flex items-center gap-1"><i data-lucide="users" class="w-4 h-4"></i> Исполнители:</label>
                <?
                $deptSql = 'SELECT d.dept_id, d.dept_name, COUNT(s.staff_id) as staff_count
                            FROM '.DEPT_TABLE.' d
                            LEFT JOIN '.STAFF_TABLE.' s ON d.dept_id = s.dept_id
                                AND s.isactive=1 AND s.onvacation=0
                            GROUP BY d.dept_id, d.dept_name
                            HAVING staff_count > 0
                            ORDER BY d.dept_name';
                $depts = db_query($deptSql);

                $staffSql = 'SELECT s.staff_id, s.dept_id, CONCAT_WS(" ",s.firstname,s.lastname) as name
                             FROM '.STAFF_TABLE.' s
                             WHERE s.isactive=1 AND s.onvacation=0
                             ORDER BY s.dept_id, s.firstname, s.lastname';
                $staffs = db_query($staffSql);

                $staffsByDept = array();
                while ($row = db_fetch_array($staffs)) {
                    $staffsByDept[$row['dept_id']][] = array(
                        'staff_id' => $row['staff_id'],
                        'name' => $row['name']
                    );
                }

                $i = 0;
                $mystaff_id = $ticket->staff_id;
                $mystaffs_id = explode(',', $staffs_id);
                ?>
                <div class="space-y-2 mt-2">
                    <?
                    while ($deptRow = db_fetch_array($depts)) {
                        $deptId = $deptRow['dept_id'];
                        $deptName = $deptRow['dept_name'];
                        $staffCount = $deptRow['staff_count'];

                        $hasSelectedStaff = false;
                        if (isset($staffsByDept[$deptId])) {
                            foreach ($staffsByDept[$deptId] as $staffData) {
                                if ($staffData['staff_id'] == $mystaff_id ||
                                    in_array($staffData['staff_id'], $mystaffs_id)) {
                                    $hasSelectedStaff = true;
                                    break;
                                }
                            }
                        }

                        $collapseClass = $hasSelectedStaff ? 'in' : '';
                        ?>
                        <div class="border border-gray-200 rounded-lg overflow-hidden">
                            <div class="flex items-center gap-2 px-4 py-2.5 bg-gray-50 cursor-pointer hover:bg-gray-100 transition-colors" onclick="toggleDeptCollapse('dept_<?=$deptId?>', this)">
                                <i data-lucide="building-2" class="w-4 h-4 text-gray-500"></i>
                                <span class="font-medium text-sm text-gray-700"><?=$deptName?></span>
                                <span class="text-xs text-gray-400">(<?=$staffCount?>)</span>
                                <svg class="w-4 h-4 text-gray-400 ml-auto dept-chevron" style="transition:transform 0.2s;<?=$hasSelectedStaff ? 'transform:rotate(90deg)' : ''?>" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"></polyline></svg>
                            </div>
                            <div id="dept_<?=$deptId?>" class="border-t border-gray-200" style="display:<?=$hasSelectedStaff ? 'block' : 'none'?>">
                                <?
                                if (isset($staffsByDept[$deptId])) {
                                    foreach ($staffsByDept[$deptId] as $staffData) {
                                        $staffId = $staffData['staff_id'];
                                        $staffName = $staffData['name'];

                                        $checked = '';
                                        $badge = '';
                                        if ($mystaff_id == $staffId) {
                                            $checked = ' checked ';
                                            $badge = ' <span class="badge-danger">ответственный</span>';
                                        } else {
                                            $founded = false;
                                            foreach($mystaffs_id as $onestaff_id) {
                                                if ($staffId == $onestaff_id) {
                                                    $founded = true;
                                                    break;
                                                }
                                            }
                                            $checked = $founded ? ' checked ' : '';
                                            $badge = $founded ? ' <span class="status-open">назначен</span>' : '';
                                        }
                                        ?>
                                        <div class='px-4 py-2 hover:bg-gray-50'>
                                            <label class="inline-flex items-center gap-2 text-sm cursor-pointer">
                                                <input type='checkbox'
                                                       name='morestaffs_id_<?=$i++?>'
                                                       id='morestaffs_id_<?=$staffId?>'
                                                       value='<?=$staffId?>'
                                                       <?=$checked?>
                                                       onchange='onSelectAssign(<?=$staffId?>,false);'
                                                       class="rounded border-gray-300">
                                                <span id='morestaffs_a_id_<?=$staffId?>'><?=$staffName?><?=$badge?></span>
                                            </label>
                                        </div>
                                        <?
                                    }
                                }
                                ?>
                            </div>
                        </div>
                        <?
                    }
                    ?>
                </div>
            </div>
            <div class="form-group mt-4 flex items-center gap-2">
                <input type="hidden" name="secret" value="<?=$_SESSION["secret"]?>" />
                <input type="hidden" name="old_viewticket_id" value="<?=$ticket->id?>" />
                <input class="btn-primary" type='submit' value='Назначить' />
                <input class="btn-secondary" type='reset' value='Очистить' />
                <input class="btn-secondary" type='button' value='Отмена' onClick="history.go(-1)" />
            </div>
        </form>
    </div>
    <?php } ?>
</div>

<script>
function switchActionTab(tabName) {
    document.querySelectorAll('.action-tab-panel').forEach(function(panel) {
        panel.style.display = 'none';
    });
    document.querySelectorAll('.action-tab').forEach(function(btn) {
        btn.className = 'action-tab px-5 py-3 text-sm font-medium border-b-2 border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 bg-white focus:outline-none';
    });
    var panel = document.getElementById('tab-' + tabName);
    if (panel) {
        panel.style.display = 'block';
    }
    var btn = document.querySelector('.action-tab[data-tab="' + tabName + '"]');
    if (btn) {
        btn.className = 'action-tab active px-5 py-3 text-sm font-medium border-b-2 border-indigo-500 text-indigo-600 bg-white focus:outline-none';
    }
    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
    }
}
function toggleDeptCollapse(deptId, headerEl) {
    var panel = document.getElementById(deptId);
    var chevron = headerEl.querySelector('.dept-chevron');
    if (panel.style.display === 'none') {
        panel.style.display = 'block';
        if (chevron) chevron.style.transform = 'rotate(90deg)';
    } else {
        panel.style.display = 'none';
        if (chevron) chevron.style.transform = '';
    }
}

(function() {
    var postAction = <?=json_encode($_POST["a"] ?? "")?>;
    var tabMap = {
        'reply': 'reply',
        'postnote': 'notes',
        'transfer': 'transfer',
        'assign': 'assign'
    };
    if (postAction && tabMap[postAction]) {
        switchActionTab(tabMap[postAction]);
    }
})();
</script>
