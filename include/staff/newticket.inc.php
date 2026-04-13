<?php
if(!defined('OSTSCPINC') || !is_object($thisuser) || !$thisuser->isStaff()) die('Доступ запрещён');
$info=($_POST && $errors)?Format::input($_POST):array();
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

<form action="tickets.php" method="post" enctype="multipart/form-data">
    <?php echo Misc::csrfField(); ?>
    <input type='hidden' name='a' value='open'>

<div class="card">
    <div class="card-header">
        <h2 class="font-heading font-semibold text-gray-900">Новая заявка</h2>
        <p class="text-sm text-gray-500 mt-1">Заполните данные формы для создания нового запроса</p>
    </div>
    <div class="card-body space-y-5">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
            <div class="form-group">
                <label for="email" class="label">Email адрес <span class="text-red-500">*</span></label>
                <input class="input" type="text" id="email" name="email" value="<?=Format::htmlchars($info['email'])?>">
                <?php if($errors['email']) { ?><span class="form-error"><?=Format::htmlchars($errors['email'])?></span><?php } ?>
            </div>
            <div class="form-group">
                <label for="name" class="label">Имя <span class="text-red-500">*</span></label>
                <input class="input" type="text" id="name" name="name" value="<?=Format::htmlchars($info['name'])?>">
                <?php if($errors['name']) { ?><span class="form-error"><?=Format::htmlchars($errors['name'])?></span><?php } ?>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
            <div class="form-group">
                <label class="label">Телефон</label>
                <div class="flex gap-2">
                    <input class="input flex-1" type="text" name="phone" value="<?=Format::htmlchars($info['phone'])?>">
                    <input class="input w-24" type="text" name="phone_ext" value="<?=Format::htmlchars($info['phone_ext'])?>" placeholder="Доб.">
                </div>
                <?php if($errors['phone']) { ?><span class="form-error"><?=Format::htmlchars($errors['phone'])?></span><?php } ?>
            </div>
            <div class="form-group">
                <label class="label">Источник запроса <span class="text-red-500">*</span></label>
                <select class="select" name="source">
                    <option value="">Выберите Источник</option>
                    <option value="Phone" <?=($info['source']=='Phone')?'selected':''?>>Телефон</option>
                    <option value="Email" <?=($info['source']=='Email')?'selected':''?>>Email</option>
                    <option value="Other" <?=($info['source']=='Other')?'selected':''?> selected>Другое</option>
                </select>
                <?php if($errors['source']) { ?><span class="form-error"><?=Format::htmlchars($errors['source'])?></span><?php } ?>
            </div>
            <div class="form-group">
                <label class="label">Отдел <span class="text-red-500">*</span></label>
                <select class="select" name="deptId">
                    <option value="" selected>Выберите Отдел</option>
                    <?php
                    $services= db_query('SELECT dept_id,dept_name FROM '.DEPT_TABLE.' ORDER BY dept_name');
                    while (list($deptId,$dept) = db_fetch_row($services)){
                        $selected = ($info['deptId']==$deptId)?'selected':''; ?>
                        <option value="<?=(int)$deptId?>"<?=$selected?>><?=Format::htmlchars($dept)?></option>
                    <?php } ?>
                </select>
                <?php if($errors['deptId']) { ?><span class="form-error"><?=Format::htmlchars($errors['deptId'])?></span><?php } ?>
            </div>
        </div>

        <?php if($cfg->notifyONNewStaffTicket()) { ?>
        <div class="flex items-center gap-2">
            <input type="checkbox" name="alertuser" class="checkbox" <?=(!$errors || $info['alertuser'])? 'checked': ''?>>
            <span class="text-sm text-gray-600">Отправить оповещение пользователю</span>
        </div>
        <?php } ?>

        <div class="form-group">
            <label class="label">Заголовок <span class="text-red-500">*</span></label>
            <input class="input" type="text" name="subject" value="<?=Format::htmlchars($info['subject'])?>">
            <?php if($errors['subject']) { ?><span class="form-error"><?=Format::htmlchars($errors['subject'])?></span><?php } ?>
        </div>

        <div class="form-group">
            <label class="label">Текст заявки <span class="text-red-500">*</span></label>
            <p class="text-xs text-gray-400 mb-1">Отображается исполнителю/ответственному.</p>
            <?php if($errors['issue']) { ?><span class="form-error mb-1"><?=Format::htmlchars($errors['issue'])?></span><?php } ?>
            <?php
            $sql='SELECT premade_id,title FROM '.KB_PREMADE_TABLE.' WHERE isenabled=1';
            $canned=db_query($sql);
            if($canned && db_num_rows($canned)) { ?>
            <div class="flex items-center gap-2 mb-2">
                <span class="text-sm text-gray-500">Шаблон:</span>
                <select id="canned" name="canned" class="select flex-1"
                    onChange="getCannedResponse(this.options[this.selectedIndex].value,this.form,'issue');this.selectedIndex='0';">
                    <option value="0" selected>Выберите шаблон ответа/заявки</option>
                    <?php while(list($cannedId,$title)=db_fetch_row($canned)) { ?>
                    <option value="<?=$cannedId?>"><?=Format::htmlchars($title)?></option>
                    <?php } ?>
                </select>
                <label class="flex items-center gap-1 text-sm text-gray-600 whitespace-nowrap">
                    <input type='checkbox' value='1' name='append' checked class="checkbox"> Добавлять
                </label>
            </div>
            <?php } ?>
            <textarea class="textarea" name="issue" rows="8"><?=Format::htmlchars($info['issue'])?></textarea>
        </div>

        <?php if($cfg->canUploadFiles()) { ?>
        <div class="form-group">
            <label class="label">Вложения</label>
            <input type="file" name="attachment"
                   class="block w-full text-sm text-gray-500 file:mr-4 file:py-2.5 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-indigo-50 file:text-indigo-600 hover:file:bg-indigo-100 cursor-pointer">
            <?php if($errors['attachment']) { ?><span class="form-error"><?=$errors['attachment']?></span><?php } ?>
        </div>
        <?php } ?>

        <div class="form-group">
            <label class="label">Внутренние сообщения</label>
            <p class="text-xs text-gray-400 mb-1">Внутреннее сообщение (не обязательно).</p>
            <?php if($errors['note']) { ?><span class="form-error mb-1"><?=Format::htmlchars($errors['note'])?></span><?php } ?>
            <textarea class="textarea" name="note" rows="4"><?=Format::htmlchars($info['note'])?></textarea>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
            <div class="form-group">
                <label class="label">Due Date</label>
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
                <?php if($errors['duedate']) { ?><span class="form-error"><?=$errors['duedate']?></span><?php } ?>
                <?php if($errors['time']) { ?><span class="form-error"><?=$errors['time']?></span><?php } ?>
            </div>

            <?php
            $sql='SELECT priority_id,priority_desc FROM '.TICKET_PRIORITY_TABLE.' ORDER BY priority_urgency DESC';
            if(($priorities=db_query($sql)) && db_num_rows($priorities)){ ?>
            <div class="form-group">
                <label class="label">Приоритет</label>
                <select class="select" name="pri">
                    <?php
                    $info['pri']=$info['pri']?$info['pri']:$cfg->getDefaultPriorityId();
                    while($row=db_fetch_array($priorities)){ ?>
                        <option value="<?=$row['priority_id']?>" <?=$info['pri']==$row['priority_id']?'selected':''?>><?=Format::htmlchars($row['priority_desc'])?></option>
                    <?php } ?>
                </select>
            </div>
            <?php } ?>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
            <?php
            $services= db_query('SELECT topic_id,topic FROM '.TOPIC_TABLE.' WHERE isactive=1 ORDER BY topic');
            if($services && db_num_rows($services)){ ?>
            <div class="form-group">
                <label class="label">Тема обращения</label>
                <select class="select" name="topicId">
                    <option value="" selected>Выберите</option>
                    <?php while (list($topicId,$topic) = db_fetch_row($services)){
                        $selected = ($info['topicId']==$topicId)?'selected':''; ?>
                        <option value="<?=(int)$topicId?>"<?=$selected?>><?=Format::htmlchars($topic)?></option>
                    <?php } ?>
                </select>
                <?php if($errors['topicId']) { ?><span class="form-error"><?=Format::htmlchars($errors['topicId'])?></span><?php } ?>
            </div>
            <?php } ?>

            <div class="form-group">
                <label class="label">Ответственный</label>
                <select class="select" id="staffId" name="staffId">
                    <option value="0" selected>— Назначить ответственного —</option>
                    <?php
                    $sql=' SELECT staff_id,CONCAT_WS(", ",firstname,lastname) as name FROM '.STAFF_TABLE.' WHERE isactive=1 AND onvacation=0 ';
                    $depts= db_query($sql.' ORDER BY firstname,lastname ');
                    while (list($staffId,$staffName) = db_fetch_row($depts)){
                        $selected = ($info['staffId']==$staffId)?'selected':''; ?>
                        <option value="<?=(int)$staffId?>"<?=$selected?>><?=Format::htmlchars($staffName)?></option>
                    <?php } ?>
                </select>
                <?php if($errors['staffId']) { ?><span class="form-error"><?=Format::htmlchars($errors['staffId'])?></span><?php } ?>
                <label class="flex items-center gap-1 text-sm text-gray-600 mt-2">
                    <input type="checkbox" name="alertstaff" class="checkbox" <?=(!$errors || $info['alertstaff'])? 'checked': ''?>>
                    Отправить сообщение ответственному
                </label>
            </div>
        </div>

        <div class="form-group">
            <label class="label">Подпись</label>
            <?php
            $appendStaffSig=$thisuser->appendMySignature();
            $info['signature']=!$info['signature']?'none':$info['signature'];
            ?>
            <div class="flex items-center gap-4">
                <label class="flex items-center gap-1.5 text-sm text-gray-600">
                    <input type="radio" name="signature" value="none" checked class="radio"> Нет
                </label>
                <?php if($appendStaffSig) { ?>
                <label class="flex items-center gap-1.5 text-sm text-gray-600">
                    <input type="radio" name="signature" value="mine" class="radio" <?=$info['signature']=='mine'?'checked':''?>> Моя подпись
                </label>
                <?php } ?>
                <label class="flex items-center gap-1.5 text-sm text-gray-600">
                    <input type="radio" name="signature" value="dept" class="radio" <?=$info['signature']=='dept'?'checked':''?>> Подпись Отдела
                </label>
            </div>
        </div>
    </div>
</div>

<div class="flex items-center gap-3 mt-6">
    <input type="hidden" name="secret" value="<?=$_SESSION["secret"]?>" />
    <button class="btn-primary" type="submit" name="submit_x">
        <i data-lucide="send" class="w-4 h-4"></i> Отправить Запрос
    </button>
    <button class="btn-secondary" type="reset">Очистить</button>
    <button class="btn-ghost" type="button" onclick='window.location.href="tickets.php"'>Отмена</button>
</div>
</form>

<script>
    var options = {
        script:"dispatch.php?api=tickets&f=searchbyemail&limit=10&",
        varname:"input",
        json: true,
        shownoresults:false,
        maxresults:10,
        callback: function (obj) { document.getElementById('email').value = obj.id; document.getElementById('name').value = obj.info; return false;}
    };
    var autosug = new bsn.AutoSuggest('email', options);
</script>
