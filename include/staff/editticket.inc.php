<?php
if(!defined('OSTSCPINC') || !is_object($ticket) || !is_object($thisuser) || !$thisuser->isStaff()) die('Доступ запрещён');

if(!($thisuser->canEditTickets() || ($thisuser->isManager() && $ticket->getDeptId()==$thisuser->getDeptId()))) die('Доступ запрещён. Ошибка прав.');

if($_POST && $errors){
    $info=Format::input($_POST);
}else{
    $info=array('email'=>$ticket->getEmail(),
                'name' =>$ticket->getName(),
                'phone'=>$ticket->getPhone(),
                'phone_ext'=>$ticket->getPhoneExt(),
                'pri'=>$ticket->getPriorityId(),
                'topicId'=>$ticket->getTopicId(),
                'topic'=>$ticket->getHelpTopic(),
                'subject' =>$ticket->getSubject(),
                'duedate' =>$ticket->getDueDate()?(Format::userdate('m/d/Y',Misc::db2gmtime($ticket->getDueDate()))):'',
                'time'=>$ticket->getDueDate()?(Format::userdate('G:i',Misc::db2gmtime($ticket->getDueDate()))):'',
                );
}
?>

<?php if(!empty($errors['err'])) { ?>
    <div class="alert-danger mb-4">
        <i data-lucide="alert-triangle" class="w-4 h-4 flex-shrink-0"></i>
        <span><?=Format::htmlchars($errors['err'])?></span>
    </div>
<?php } elseif($msg) { ?>
    <div class="alert-success mb-4">
        <i data-lucide="check-circle" class="w-4 h-4 flex-shrink-0"></i>
        <span><?=Format::htmlchars($msg)?></span>
    </div>
<?php } elseif($warn) { ?>
    <div class="alert-warning mb-4">
        <i data-lucide="alert-circle" class="w-4 h-4 flex-shrink-0"></i>
        <span><?=Format::htmlchars($warn)?></span>
    </div>
<?php } ?>

<form action="tickets.php?id=<?=$ticket->getId()?>" method="post">
    <?php echo Misc::csrfField(); ?>
    <input type='hidden' name='id' value='<?=$ticket->getId()?>'>
    <input type='hidden' name='a' value='update'>

<div class="card">
    <div class="card-header">
        <div class="flex items-center justify-between">
            <h2 class="font-heading font-semibold text-gray-900">Редактировать заявку №<?=$ticket->getExtId()?></h2>
            <a href="tickets.php?id=<?=$ticket->getId()?>" class="btn-secondary btn-sm">
                <i data-lucide="eye" class="w-3.5 h-3.5"></i> Посмотреть
            </a>
        </div>
    </div>
    <div class="card-body space-y-5">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
            <div class="form-group">
                <label class="label">Email адрес <span class="text-red-500">*</span></label>
                <input class="input" type="text" id="email" name="email" value="<?=Format::htmlchars($info['email'])?>">
                <?php if($errors['email']) { ?><span class="form-error"><?=Format::htmlchars($errors['email'])?></span><?php } ?>
            </div>
            <div class="form-group">
                <label class="label">Имя <span class="text-red-500">*</span></label>
                <input class="input" type="text" id="name" name="name" value="<?=Format::htmlchars($info['name'])?>">
                <?php if($errors['name']) { ?><span class="form-error"><?=Format::htmlchars($errors['name'])?></span><?php } ?>
            </div>
        </div>

        <div class="form-group">
            <label class="label">Заголовок <span class="text-red-500">*</span></label>
            <input class="input" type="text" name="subject" value="<?=Format::htmlchars($info['subject'])?>">
            <?php if($errors['subject']) { ?><span class="form-error"><?=Format::htmlchars($errors['subject'])?></span><?php } ?>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
            <div class="form-group">
                <label class="label">Телефон</label>
                <div class="flex gap-2">
                    <input class="input flex-1" type="text" name="phone" value="<?=Format::htmlchars($info['phone'])?>">
                    <input class="input w-24" type="text" name="phone_ext" value="<?=Format::htmlchars($info['phone_ext'])?>" placeholder="Доп.">
                </div>
                <?php if($errors['phone']) { ?><span class="form-error"><?=Format::htmlchars($errors['phone'])?></span><?php } ?>
            </div>
            <div class="form-group">
                <label class="label">Срок выполнения</label>
                <p class="text-xs text-gray-400 mb-1">Часовой пояс (GM <?=$thisuser->getTZoffset()?>)</p>
                <div class="flex items-center gap-2">
                    <input id="duedate" name="duedate" class="input datepicker" value="<?=Format::htmlchars($info['duedate'])?>" autocomplete="off">
                    <a href="#" onclick="document.getElementById('duedate')._flatpickr.open(); return false;" class="btn-icon text-gray-400 hover:text-indigo-600">
                        <i data-lucide="calendar" class="w-4 h-4"></i>
                    </a>
                    <?php
                    $min=$hr=null;
                    if($info['time'])
                        list($hr,$min)=explode(':',$info['time']);
                    echo Misc::timeDropdown($hr,$min,'time');
                    ?>
                </div>
                <?php if($errors['duedate']) { ?><span class="form-error"><?=Format::htmlchars($errors['duedate'])?></span><?php } ?>
                <?php if($errors['time']) { ?><span class="form-error"><?=Format::htmlchars($errors['time'])?></span><?php } ?>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
            <?php
            $sql='SELECT priority_id,priority_desc FROM '.TICKET_PRIORITY_TABLE.' ORDER BY priority_urgency DESC';
            if(($priorities=db_query($sql)) && db_num_rows($priorities)){ ?>
            <div class="form-group">
                <label class="label">Приоритет</label>
                <select class="select" name="pri">
                    <?php while($row=db_fetch_array($priorities)){ ?>
                        <option value="<?=$row['priority_id']?>" <?=$info['pri']==$row['priority_id']?'selected':''?>><?=Format::htmlchars($row['priority_desc'])?></option>
                    <?php } ?>
                </select>
            </div>
            <?php } ?>

            <?php
            $services= db_query('SELECT topic_id,topic,isactive FROM '.TOPIC_TABLE.' ORDER BY topic');
            if($services && db_num_rows($services)){ ?>
            <div class="form-group">
                <label class="label">Тема обращения</label>
                <select class="select" name="topicId">
                    <option value="0" selected>Нет</option>
                    <?php if(!$info['topicId'] && $info['topic']){ ?>
                    <option value="0" selected><?=Format::htmlchars($info['topic'])?> (удалён)</option>
                    <?php }
                    while (list($topicId,$topic,$active) = db_fetch_row($services)){
                        $selected = ($info['topicId']==$topicId)?'selected':'';
                        $status=$active?'Активен':'Неактивен'; ?>
                        <option value="<?=$topicId?>"<?=$selected?>><?=Format::htmlchars($topic)?> (<?=Format::htmlchars($status)?>)</option>
                    <?php } ?>
                </select>
                <?php if($errors['topicId']) { ?><span class="form-error"><?=Format::htmlchars($errors['topicId'])?></span><?php } ?>
            </div>
            <?php } ?>
        </div>

        <div class="form-group">
            <label class="label">Внутреннее сообщение <span class="text-red-500">*</span></label>
            <p class="text-xs text-gray-400 mb-1">Причина редактирования</p>
            <?php if($errors['note']) { ?><span class="form-error mb-1"><?=Format::htmlchars($errors['note'])?></span><?php } ?>
            <textarea class="textarea" name="note" rows="4"><?=Format::htmlchars($info['note'])?></textarea>
        </div>
    </div>
</div>

<div class="flex items-center gap-3 mt-6">
    <input type="hidden" name="secret" value="<?=$_SESSION["secret"]?>" />
    <button class="btn-primary" type="submit" name="submit_x">
        <i data-lucide="save" class="w-4 h-4"></i> Обновить
    </button>
    <button class="btn-secondary" type="reset">Очистить</button>
    <button class="btn-ghost" type="button" onclick='window.location.href="tickets.php?id=<?=$ticket->getId()?>"'>Отмена</button>
</div>
</form>
