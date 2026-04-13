<?php
if(!defined('OSTADMININC') || !$thisuser->isadmin()) die('Доступ запрещён');

$config=($errors && $_POST)?Format::input($_POST):Format::htmlchars($cfg->getConfig());
$warn=array();
if($config['allow_attachments'] && !$config['upload_dir']) {
    $errors['allow_attachments']='Необходимо настроить каталог загрузок.';
}else{
    if(!$config['allow_attachments'] && $config['allow_email_attachments'])
        $warn['allow_email_attachments']='*Вложения отключены.';
    if(!$config['allow_attachments'] && ($config['allow_online_attachments'] or $config['allow_online_attachments_onlogin']))
        $warn['allow_online_attachments']='<br>*Вложения отключены.';
}

if(!$errors['enable_captcha'] && $config['enable_captcha'] && !extension_loaded('gd'))
    $errors['enable_captcha']='Для работы CAPTCHA требуется библиотека GD';

if(!$errors['err'] &&!$msg && $warn )
    $errors['err']='Обнаружены возможные ошибки, проверьте предупреждения ниже';

$gmtime=Misc::gmtime();
$depts= db_query('SELECT dept_id,dept_name FROM '.DEPT_TABLE.' WHERE ispublic=1');
$templates=db_query('SELECT tpl_id,name FROM '.EMAIL_TEMPLATE_TABLE.' WHERE cfg_id='.db_input($cfg->getId()));
?>
<h1 class="text-2xl font-heading font-bold text-gray-900 mb-6">Системные Настройки&nbsp;&nbsp;(v<?=$config['thversion'] ?? $config['ostversion'] ?? ''?>)</h1>
<form action="admin.php?t=pref" method="post">
 <?php echo Misc::csrfField(); ?>
 <input type="hidden" name="t" value="pref">

<div class="card mb-6">
    <div class="card-header">
        <h2 class="font-heading font-semibold text-gray-900">Основные настройки</h2>
    </div>
    <div class="card-body space-y-4">
        <p class="text-gray-500 text-sm">Офлайн режим отключает клиентский интерфейс и разрешает <b>только администраторам</b> входить в Панель Управления</p>

        <div class="flex flex-col md:flex-row md:items-start gap-2 py-3 border-b border-gray-100">
            <label class="label md:w-1/3 md:pt-2"><b>Статус системы</b></label>
            <div class="md:w-2/3">
                <label class="flex items-center gap-1.5 text-sm text-gray-600 mb-1">
                    <input class="radio" type="radio" name="isonline" value="1" <?=$config['isonline']?'checked':''?> /> <b>Онлайн</b> (Включено)
                </label>
                <label class="flex items-center gap-1.5 text-sm text-gray-600">
                    <input class="radio" type="radio" name="isonline" value="0" <?=!$config['isonline']?'checked':''?> /> <b>Офлайн</b> (Выключено)
                </label>
                <span class="text-amber-500 text-sm">&nbsp;<?=$config['isoffline']?'TicketHub не в сети':''?></span>
            </div>
        </div>

        <div class="flex flex-col md:flex-row md:items-start gap-2 py-3 border-b border-gray-100">
            <label class="label md:w-1/3 md:pt-2">Адрес URL:</label>
            <div class="md:w-2/3">
                <input class="input" type="text" size="40" name="helpdesk_url" value="<?=$config['helpdesk_url']?>">
                <span class="text-red-500 text-sm">*&nbsp;<?=Format::htmlchars($errors['helpdesk_url'] ?? '')?></span>
            </div>
        </div>

        <div class="flex flex-col md:flex-row md:items-start gap-2 py-3 border-b border-gray-100">
            <label class="label md:w-1/3 md:pt-2">Имя/Заголовок системы:</label>
            <div class="md:w-2/3">
                <input class="input" type="text" size="40" name="helpdesk_title" value="<?=$config['helpdesk_title']?>">
            </div>
        </div>

        <div class="flex flex-col md:flex-row md:items-start gap-2 py-3 border-b border-gray-100">
            <label class="label md:w-1/3 md:pt-2">Email Шаблон:</label>
            <div class="md:w-2/3">
                <select class="select" name="default_template_id">
                    <option value=0>Выберите шаблон</option>
                    <?
                    while (list($id,$name) = db_fetch_row($templates)){
                        $selected = ($config['default_template_id']==$id)?'SELECTED':''; ?>
                        <option value="<?=(int)$id?>"<?=$selected?>><?=Format::htmlchars($name)?></option>
                    <?
                    }?>
                </select>
                <span class="text-red-500 text-sm">*&nbsp;<?=Format::htmlchars($errors['default_template_id'] ?? '')?></span>
            </div>
        </div>

        <div class="flex flex-col md:flex-row md:items-start gap-2 py-3 border-b border-gray-100">
            <label class="label md:w-1/3 md:pt-2">Отдел по умолчанию:</label>
            <div class="md:w-2/3">
                <select class="select" name="default_dept_id">
                    <option value=0>Выберите отдел</option>
                    <?
                    while (list($id,$name) = db_fetch_row($depts)){
                    $selected = ($config['default_dept_id']==$id)?'SELECTED':''; ?>
                    <option value="<?=(int)$id?>"<?=$selected?>><?=Format::htmlchars($name)?></option>
                    <?
                    }?>
                </select>
                <span class="text-red-500 text-sm">*&nbsp;<?=Format::htmlchars($errors['default_dept_id'] ?? '')?></span>
            </div>
        </div>

        <div class="flex flex-col md:flex-row md:items-start gap-2 py-3 border-b border-gray-100">
            <label class="label md:w-1/3 md:pt-2">Кол-во на страницу:</label>
            <div class="md:w-2/3">
                <select class="select" name="max_page_size">
                    <?
                     $pagelimit=$config['max_page_size'];
                    for ($i = 5; $i <= 50; $i += 5) {
                        ?>
                        <option <?=$config['max_page_size'] == $i ? 'SELECTED':''?> value="<?=$i?>"><?=$i?></option>
                        <?
                    }?>
                </select>
            </div>
        </div>

        <div class="flex flex-col md:flex-row md:items-start gap-2 py-3 border-b border-gray-100">
            <label class="label md:w-1/3 md:pt-2">Уровень Журналов:</label>
            <div class="md:w-2/3 flex flex-wrap items-center gap-2">
                <select class="select w-auto inline-block" name="log_level">
                    <option value=0 <?=$config['log_level'] == 0 ? 'selected="selected"':''?>>Нет (Отключены)</option>
                    <option value=3 <?=$config['log_level'] == 3 ? 'selected="selected"':''?>> DEBUG</option>
                    <option value=2 <?=$config['log_level'] == 2 ? 'selected="selected"':''?>> WARN</option>
                    <option value=1 <?=$config['log_level'] == 1 ? 'selected="selected"':''?>> ERROR</option>
                </select>
                <span class="text-sm text-gray-600">Очищать журналы после</span>
                <select class="select w-auto inline-block" name="log_graceperiod">
                    <option value=0 selected> Нет (Отключено)</option>
                    <?
                    for ($i = 1; $i <=12; $i++) {
                        ?>
                        <option <?=$config['log_graceperiod'] == $i ? 'SELECTED':''?> value="<?=$i?>"><?=$i?>&nbsp;<?=($i>1)?'Месяцев':'Месяца'?></option>
                        <?
                    }?>
                </select>
            </div>
        </div>

        <div class="flex flex-col md:flex-row md:items-start gap-2 py-3 border-b border-gray-100">
            <label class="label md:w-1/3 md:pt-2">Попыток входа персоналом:</label>
            <div class="md:w-2/3 flex flex-wrap items-center gap-2">
                <select class="select w-auto inline-block" name="staff_max_logins">
                  <?php
                    for ($i = 1; $i <= 10; $i++) {
                        echo sprintf('<option value="%d" %s>%d</option>',$i,(($config['staff_max_logins']==$i)?'selected="selected"':''),$i);
                    }
                    ?>
                </select>
                <span class="text-sm text-gray-600">попыток разрешено, после</span>
                <select class="select w-auto inline-block" name="staff_login_timeout">
                  <?php
                    for ($i = 1; $i <= 10; $i++) {
                        echo sprintf('<option value="%d" %s>%d</option>',$i,(($config['staff_login_timeout']==$i)?'selected="selected"':''),$i);
                    }
                    ?>
                </select>
                <span class="text-sm text-gray-600">минут блокировки</span>
            </div>
        </div>

        <div class="flex flex-col md:flex-row md:items-start gap-2 py-3 border-b border-gray-100">
            <label class="label md:w-1/3 md:pt-2">Время сессии персонала:</label>
            <div class="md:w-2/3 flex flex-wrap items-center gap-2">
                <input class="input w-auto inline-block" type="text" name="staff_session_timeout" size=6 value="<?=$config['staff_session_timeout']?>">
                <span class="text-xs text-gray-400">Макимальное время жизни сессии в минутах. Введите 0 для отключения.</span>
            </div>
        </div>

        <div class="flex flex-col md:flex-row md:items-start gap-2 py-3 border-b border-gray-100">
            <label class="label md:w-1/3 md:pt-2">Привязка сессии к IP адресу:</label>
            <div class="md:w-2/3">
                <label class="flex items-center gap-1.5 text-sm text-gray-600">
                    <input class="checkbox" type="checkbox" name="staff_ip_binding" <?=$config['staff_ip_binding']?'checked':''?>>
                    Привязать сессию персонала к его IP адресу
                </label>
            </div>
        </div>

        <div class="flex flex-col md:flex-row md:items-start gap-2 py-3 border-b border-gray-100">
            <label class="label md:w-1/3 md:pt-2">Попыток входа клиентом:</label>
            <div class="md:w-2/3 flex flex-wrap items-center gap-2">
                <select class="select w-auto inline-block" name="client_max_logins">
                  <?php
                    for ($i = 1; $i <= 10; $i++) {
                        echo sprintf('<option value="%d" %s>%d</option>',$i,(($config['client_max_logins']==$i)?'selected="selected"':''),$i);
                    }

                    ?>
                </select>
                <span class="text-sm text-gray-600">попыток разрешено, после</span>
                <select class="select w-auto inline-block" name="client_login_timeout">
                  <?php
                    for ($i = 1; $i <= 10; $i++) {
                        echo sprintf('<option value="%d" %s>%d</option>',$i,(($config['client_login_timeout']==$i)?'selected="selected"':''),$i);
                    }
                    ?>
                </select>
                <span class="text-sm text-gray-600">минут блокировки</span>
            </div>
        </div>

        <div class="flex flex-col md:flex-row md:items-start gap-2 py-3 border-b border-gray-100">
            <label class="label md:w-1/3 md:pt-2">Время сессии клиента:</label>
            <div class="md:w-2/3 flex flex-wrap items-center gap-2">
                <input class="input w-auto inline-block" type="text" name="client_session_timeout" size=6 value="<?=$config['client_session_timeout']?>">
                <span class="text-xs text-gray-400">Макимальное время жизни сессии в минутах. Введите 0 для отключения.</span>
            </div>
        </div>

        <div class="flex flex-col md:flex-row md:items-start gap-2 py-3 border-b border-gray-100">
            <label class="label md:w-1/3 md:pt-2">Использование URLs:</label>
            <div class="md:w-2/3">
                <label class="flex items-center gap-1.5 text-sm text-gray-600">
                    <input class="checkbox" type="checkbox" name="clickable_urls" <?=$config['clickable_urls']?'checked':''?>>
                    Сделать возможность использовать ссылки
                </label>
            </div>
        </div>

        <div class="flex flex-col md:flex-row md:items-start gap-2 py-3">
            <label class="label md:w-1/3 md:pt-2">Включить Авто Запуск:</label>
            <div class="md:w-2/3">
                <label class="flex items-center gap-1.5 text-sm text-gray-600">
                    <input class="checkbox" type="checkbox" name="enable_auto_cron" <?=$config['enable_auto_cron']?'checked':''?>>
                    Включить прием новых заявок когда менеджер активен
                </label>
            </div>
        </div>

    </div>
</div>

<div class="card mb-6">
    <div class="card-header">
        <h2 class="font-heading font-semibold text-gray-900">Дата &amp; Время</h2>
    </div>
    <div class="card-body space-y-4">
        <p class="text-gray-500 text-sm">Пожалуйста посмотрите <a href="http://php.net/date" target="_blank" class="text-blue-600 hover:underline">PHP Документацию</a> для определения параметров.</p>

        <div class="flex flex-col md:flex-row md:items-start gap-2 py-3 border-b border-gray-100">
            <label class="label md:w-1/3 md:pt-2">Формат Времени:</label>
            <div class="md:w-2/3 flex flex-wrap items-center gap-2">
                <input class="input w-auto inline-block" type="text" name="time_format" value="<?=$config['time_format']?>">
                <span class="text-red-500 text-sm">*&nbsp;<?=$errors['time_format']?></span>
                <span class="text-xs text-gray-400"><?=Format::date($config['time_format'],$gmtime,$config['timezone_offset'],$config['enable_daylight_saving'])?></span>
            </div>
        </div>

        <div class="flex flex-col md:flex-row md:items-start gap-2 py-3 border-b border-gray-100">
            <label class="label md:w-1/3 md:pt-2">Формат Даты:</label>
            <div class="md:w-2/3 flex flex-wrap items-center gap-2">
                <input class="input w-auto inline-block" type="text" name="date_format" value="<?=$config['date_format']?>">
                <span class="text-red-500 text-sm">*&nbsp;<?=$errors['date_format']?></span>
                <span class="text-xs text-gray-400"><?=Format::date($config['date_format'],$gmtime,$config['timezone_offset'],$config['enable_daylight_saving'])?></span>
            </div>
        </div>

        <div class="flex flex-col md:flex-row md:items-start gap-2 py-3 border-b border-gray-100">
            <label class="label md:w-1/3 md:pt-2">Формат Даты &amp; Времени:</label>
            <div class="md:w-2/3 flex flex-wrap items-center gap-2">
                <input class="input w-auto inline-block" type="text" name="datetime_format" value="<?=$config['datetime_format']?>">
                <span class="text-red-500 text-sm">*&nbsp;<?=$errors['datetime_format']?></span>
                <span class="text-xs text-gray-400"><?=Format::date($config['datetime_format'],$gmtime,$config['timezone_offset'],$config['enable_daylight_saving'])?></span>
            </div>
        </div>

        <div class="flex flex-col md:flex-row md:items-start gap-2 py-3 border-b border-gray-100">
            <label class="label md:w-1/3 md:pt-2">Формат Дня, Даты &amp; Времени:</label>
            <div class="md:w-2/3 flex flex-wrap items-center gap-2">
                <input class="input w-auto inline-block" type="text" name="daydatetime_format" value="<?=$config['daydatetime_format']?>">
                <span class="text-red-500 text-sm">*&nbsp;<?=$errors['daydatetime_format']?></span>
                <span class="text-xs text-gray-400"><?=Format::date($config['daydatetime_format'],$gmtime,$config['timezone_offset'],$config['enable_daylight_saving'])?></span>
            </div>
        </div>

        <div class="flex flex-col md:flex-row md:items-start gap-2 py-3 border-b border-gray-100">
            <label class="label md:w-1/3 md:pt-2">Временная Зона:</label>
            <div class="md:w-2/3">
                <select class="select" name="timezone_offset">
                    <?
                    $gmoffset = date("Z") / 3600;
                    echo"<option value=\"$gmoffset\">Серверное время (GMT $gmoffset:00)</option>";
                    $timezones= db_query('SELECT offset,timezone FROM '.TIMEZONE_TABLE);
                    while (list($offset,$tz) = db_fetch_row($timezones)){
                        $selected = ($config['timezone_offset'] ==$offset) ?'SELECTED':'';
                        $tag=($offset)?"GMT $offset ($tz)":" GMT ($tz)";
                        ?>
                        <option value="<?=$offset?>"<?=$selected?>><?=$tag?></option>
                        <?
                    }?>
                </select>
            </div>
        </div>

        <div class="flex flex-col md:flex-row md:items-start gap-2 py-3">
            <label class="label md:w-1/3 md:pt-2">Летнее Время:</label>
            <div class="md:w-2/3">
                <label class="flex items-center gap-1.5 text-sm text-gray-600">
                    <input class="checkbox" type="checkbox" name="enable_daylight_saving" <?=$config['enable_daylight_saving'] ? 'checked': ''?>>
                    Переходить на летнее время
                </label>
            </div>
        </div>

    </div>
</div>

<div class="card mb-6">
    <div class="card-header">
        <h2 class="font-heading font-semibold text-gray-900">Настройки и параметры заявок</h2>
    </div>
    <div class="card-body space-y-4">
        <p class="text-gray-500 text-sm">Основные параметры при создании заявок.</p>

        <div class="flex flex-col md:flex-row md:items-start gap-2 py-3 border-b border-gray-100">
            <label class="label md:w-1/3 md:pt-2">ID запроса:</label>
            <div class="md:w-2/3">
                <label class="flex items-center gap-1.5 text-sm text-gray-600 mb-1">
                    <input class="radio" type="radio" name="random_ticket_ids" value="0" <?=!$config['random_ticket_ids']?'checked':''?> /> Счетчик
                </label>
                <label class="flex items-center gap-1.5 text-sm text-gray-600">
                    <input class="radio" type="radio" name="random_ticket_ids" value="1" <?=$config['random_ticket_ids']?'checked':''?> /> Произвольный (рекомендуется)
                </label>
            </div>
        </div>

        <div class="flex flex-col md:flex-row md:items-start gap-2 py-3 border-b border-gray-100">
            <label class="label md:w-1/3 md:pt-2">Приоритет заявки:</label>
            <div class="md:w-2/3">
                <div class="flex flex-wrap items-center gap-2 mb-2">
                    <select class="select w-auto inline-block" name="default_priority_id">
                        <?
                        $priorities= db_query('SELECT priority_id,priority_desc FROM '.TICKET_PRIORITY_TABLE);
                        while (list($id,$tag) = db_fetch_row($priorities)){ ?>
                            <option value="<?=$id?>"<?=($config['default_priority_id']==$id)?'selected':''?>><?=$tag?></option>
                        <?
                        }?>
                    </select>
                    <span class="text-sm text-gray-600">приоритет по умолчанию</span>
                </div>
                <label class="flex items-center gap-1.5 text-sm text-gray-600 mb-1">
                    <input class="checkbox" type="checkbox" name="allow_priority_change" <?=$config['allow_priority_change'] ?'checked':''?>>
                    Разрешить пользователям изменять приоритет (для новых заявок через сайт)
                </label>
                <label class="flex items-center gap-1.5 text-sm text-gray-600">
                    <input class="checkbox" type="checkbox" name="use_email_priority" <?=$config['use_email_priority'] ?'checked':''?>>
                    Использовать email приоритет если установлен (для новых email заявок)
                </label>
            </div>
        </div>

        <div class="flex flex-col md:flex-row md:items-start gap-2 py-3 border-b border-gray-100">
            <label class="label md:w-1/3 md:pt-2">Максимум <b>открытых</b> заявок:</label>
            <div class="md:w-2/3 flex flex-wrap items-center gap-2">
                <input class="input w-auto inline-block" type="text" name="max_open_tickets" size=4 value="<?=$config['max_open_tickets']?>">
                <span class="text-sm text-gray-600">по email.</span>
                <span class="text-xs text-gray-400">Используется как спам и флуд контроль. Введите 0 для снятий ограничений</span>
            </div>
        </div>

        <div class="flex flex-col md:flex-row md:items-start gap-2 py-3 border-b border-gray-100">
            <label class="label md:w-1/3 md:pt-2">Время авто-блокировки:</label>
            <div class="md:w-2/3 flex flex-wrap items-center gap-2">
                <input class="input w-auto inline-block" type="text" name="autolock_minutes" size=4 value="<?=$config['autolock_minutes']?>">
                <span class="text-red-500 text-sm"><?=$errors['autolock_minutes']?></span>
                <span class="text-xs text-gray-400">Минут блокировки заявки при активности. Введите 0 для отключения блокировки</span>
            </div>
        </div>

        <div class="flex flex-col md:flex-row md:items-start gap-2 py-3 border-b border-gray-100">
            <label class="label md:w-1/3 md:pt-2">Период старения заявки:</label>
            <div class="md:w-2/3 flex flex-wrap items-center gap-2">
                <input class="input w-auto inline-block" type="text" name="overdue_grace_period" size=4 value="<?=$config['overdue_grace_period']?>">
                <span class="text-xs text-gray-400">Часов после которых заявки считается просроченной. Введите 0 для отключения.</span>
            </div>
        </div>

        <div class="flex flex-col md:flex-row md:items-start gap-2 py-3 border-b border-gray-100">
            <label class="label md:w-1/3 md:pt-2">Переоткрытые заявки:</label>
            <div class="md:w-2/3">
                <label class="flex items-center gap-1.5 text-sm text-gray-600">
                    <input class="checkbox" type="checkbox" name="auto_assign_reopened_tickets" <?=$config['auto_assign_reopened_tickets'] ? 'checked': ''?>>
                    Автоматически назначать переоткрытые заявки последнему доступному ответчику.
                </label>
                <span class="text-xs text-gray-400">Лимит 3 месяца</span>
            </div>
        </div>

        <div class="flex flex-col md:flex-row md:items-start gap-2 py-3 border-b border-gray-100">
            <label class="label md:w-1/3 md:pt-2">Назначенные заявки:</label>
            <div class="md:w-2/3">
                <label class="flex items-center gap-1.5 text-sm text-gray-600">
                    <input class="checkbox" type="checkbox" name="show_assigned_tickets" <?=$config['show_assigned_tickets']?'checked':''?>>
                    Показывать назначенные заявки в очереди открытых.
                </label>
            </div>
        </div>

        <div class="flex flex-col md:flex-row md:items-start gap-2 py-3 border-b border-gray-100">
            <label class="label md:w-1/3 md:pt-2">Отвеченные заявки:</label>
            <div class="md:w-2/3">
                <label class="flex items-center gap-1.5 text-sm text-gray-600">
                    <input class="checkbox" type="checkbox" name="show_answered_tickets" <?=$config['show_answered_tickets']?'checked':''?>>
                    Показывать отвеченные заявки в очереди открытых.
                </label>
            </div>
        </div>

        <div class="flex flex-col md:flex-row md:items-start gap-2 py-3 border-b border-gray-100">
            <label class="label md:w-1/3 md:pt-2">Журнал продвижения заявок:</label>
            <div class="md:w-2/3">
                <label class="flex items-center gap-1.5 text-sm text-gray-600">
                    <input class="checkbox" type="checkbox" name="log_ticket_activity" <?=$config['log_ticket_activity']?'checked':''?>>
                    Записывать продвижение заявок во внутренних сообщениях.
                </label>
            </div>
        </div>

        <div class="flex flex-col md:flex-row md:items-start gap-2 py-3 border-b border-gray-100">
            <label class="label md:w-1/3 md:pt-2">Идентификация персонала:</label>
            <div class="md:w-2/3">
                <label class="flex items-center gap-1.5 text-sm text-gray-600">
                    <input class="checkbox" type="checkbox" name="hide_staff_name" <?=$config['hide_staff_name']?'checked':''?>>
                    Скрывать имя персонала при ответах.
                </label>
            </div>
        </div>

        <div class="flex flex-col md:flex-row md:items-start gap-2 py-3">
            <label class="label md:w-1/3 md:pt-2">Анти Спам:</label>
            <div class="md:w-2/3">
                <?php
                   if($config['enable_captcha'] && !$errors['enable_captcha']) {?>
                        <img src="../captcha.php" border="0" align="left" class="mr-2">&nbsp;
                <?}?>
                <label class="flex items-center gap-1.5 text-sm text-gray-600">
                    <input class="checkbox" type="checkbox" name="enable_captcha" <?=$config['enable_captcha']?'checked':''?>>
                    Включить капчу для новых веб-запросов.
                </label>
                <span class="text-red-500 text-sm">&nbsp;<?=$errors['enable_captcha']?></span>
            </div>
        </div>

    </div>
</div>

<div class="card mb-6">
    <div class="card-header">
        <h2 class="font-heading font-semibold text-gray-900">Настройки Email</h2>
    </div>
    <div class="card-body space-y-4">
        <p class="text-gray-500 text-sm">Помните, что глобальные настройки могут быть отключены на уровне емаил/отдела.</p>

        <div class="flex flex-col md:flex-row md:items-start gap-2 py-3 border-b border-gray-100">
            <label class="label md:w-1/3 md:pt-2"><b>Входящие Emails</b>:</label>
            <div class="md:w-2/3">
                <span class="text-xs text-gray-400">Для работы получения почты (POP/IMAP) необходимо настроить cron-задачу или включить авто-запуск</span>
                <div class="space-y-1 mt-2">
                    <label class="flex items-center gap-1.5 text-sm text-gray-600">
                        <input class="checkbox" type="checkbox" name="enable_mail_fetch" value=1 <?=$config['enable_mail_fetch']? 'checked': ''?>>
                        Включить получение почты POP/IMAP
                    </label>
                    <span class="text-xs text-gray-400 ml-6">Глобальная настройка, может быть отключена на уровне email</span>
                    <label class="flex items-center gap-1.5 text-sm text-gray-600">
                        <input class="checkbox" type="checkbox" name="enable_email_piping" value=1 <?=$config['enable_email_piping']? 'checked': ''?>>
                        Включить перенаправление email
                    </label>
                    <span class="text-xs text-gray-400 ml-6">Политика приема перенаправленной почты</span>
                    <label class="flex items-center gap-1.5 text-sm text-gray-600">
                        <input class="checkbox" type="checkbox" name="strip_quoted_reply" <?=$config['strip_quoted_reply'] ? 'checked':''?>>
                        Удалять цитируемый ответ
                    </label>
                    <span class="text-xs text-gray-400 ml-6">зависит от тега ниже</span>
                </div>
                <div class="flex flex-wrap items-center gap-2 mt-2">
                    <input class="input w-auto inline-block" type="text" name="reply_separator" value="<?=$config['reply_separator']?>">
                    <span class="text-sm text-gray-600">Тег-разделитель ответа</span>
                    <span class="text-red-500 text-sm">&nbsp;<?=$errors['reply_separator']?></span>
                </div>
            </div>
        </div>

        <div class="flex flex-col md:flex-row md:items-start gap-2 py-3 border-b border-gray-100">
            <label class="label md:w-1/3 md:pt-2"><b>Исходящие Emails</b>:</label>
            <div class="md:w-2/3">
                <span class="text-xs text-gray-400"><b>Email по умолчанию:</b> Применяется только к исходящим письмам без настроек SMTP.</span>
                <div class="flex flex-wrap items-center gap-2 mt-2">
                    <select class="select w-auto inline-block" name="default_smtp_id"
                        onChange="document.getElementById('overwrite').style.display=(this.options[this.selectedIndex].value>0)?'block':'none';">
                        <option value=0>Выберите</option>
                        <option value=0 selected="selected">Нет: Использовать PHP mail функцию</option>
                        <?
                        $emails=db_query('SELECT email_id,email,name,smtp_host FROM '.EMAIL_TABLE.' WHERE smtp_active=1');
                        if($emails && db_num_rows($emails)) {
                            while (list($id,$email,$name,$host) = db_fetch_row($emails)){
                                $email=$name?"$name &lt;$email&gt;":$email;
                                $email=sprintf('%s (%s)',$email,$host);
                                ?>
                                <option value="<?=$id?>"<?=($config['default_smtp_id']==$id)?'selected="selected"':''?>><?=$email?></option>
                            <?
                            }
                        }?>
                     </select>
                     <span class="text-red-500 text-sm">&nbsp;<?=$errors['default_smtp_id']?></span>
                </div>
                <span id="overwrite" style="display:<?=($config['default_smtp_id']?'display':'none')?>">
                    <label class="flex items-center gap-1.5 text-sm text-gray-600 mt-2">
                        <input class="checkbox" type="checkbox" name="spoof_default_smtp" <?=$config['spoof_default_smtp'] ? 'checked':''?>>
                        Разрешить подмену (без перезаписи).
                    </label>
                    <span class="text-red-500 text-sm">&nbsp;<?=$errors['spoof_default_smtp']?></span>
                </span>
            </div>
        </div>

        <div class="flex flex-col md:flex-row md:items-start gap-2 py-3 border-b border-gray-100">
            <label class="label md:w-1/3 md:pt-2">Системный Email:</label>
            <div class="md:w-2/3">
                <select class="select" name="default_email_id">
                    <option value=0 disabled>Выберите</option>
                    <?
                    $emails=db_query('SELECT email_id,email,name FROM '.EMAIL_TABLE);
                    while (list($id,$email,$name) = db_fetch_row($emails)){
                        $email=$name?"$name &lt;$email&gt;":$email;
                        ?>
                     <option value="<?=$id?>"<?=($config['default_email_id']==$id)?'selected':''?>><?=$email?></option>
                    <?
                    }?>
                 </select>
                 <span class="text-red-500 text-sm">*&nbsp;<?=$errors['default_email_id']?></span>
            </div>
        </div>

        <div class="flex flex-col md:flex-row md:items-start gap-2 py-3 border-b border-gray-100">
            <label class="label md:w-1/3 md:pt-2">Email для уведомлений:</label>
            <div class="md:w-2/3">
                <select class="select" name="alert_email_id">
                    <option value=0 disabled>Выберите</option>
                    <option value=0 selected="selected">Использовать системный Email</option>
                    <?
                    $emails=db_query('SELECT email_id,email,name FROM '.EMAIL_TABLE.' WHERE email_id != '.db_input($config['default_email_id']));
                    while (list($id,$email,$name) = db_fetch_row($emails)){
                        $email=$name?"$name &lt;$email&gt;":$email;
                        ?>
                     <option value="<?=$id?>"<?=($config['alert_email_id']==$id)?'selected':''?>><?=$email?></option>
                    <?
                    }?>
                 </select>
                 <span class="text-red-500 text-sm">*&nbsp;<?=$errors['alert_email_id']?></span>
                <p class="text-xs text-gray-400 mt-1">Используется для отправки уведомлений персоналу.</p>
            </div>
        </div>

        <div class="flex flex-col md:flex-row md:items-start gap-2 py-3">
            <label class="label md:w-1/3 md:pt-2">Email адрес системного администратора:</label>
            <div class="md:w-2/3">
                <input class="input" type="text" size=25 name="admin_email" value="<?=$config['admin_email']?>">
                <span class="text-red-500 text-sm">*&nbsp;<?=$errors['admin_email']?></span>
            </div>
        </div>

    </div>
</div>

<div class="card mb-6">
    <div class="card-header">
        <h2 class="font-heading font-semibold text-gray-900">Автоответчики &nbsp;(Глобальная Настройка)</h2>
    </div>
    <div class="card-body space-y-4">
        <p class="text-gray-500 text-sm">Эта глобальная настройка может быть отключена на уровне отдела.</p>

        <div class="flex flex-col md:flex-row md:items-start gap-2 py-3 border-b border-gray-100">
            <label class="label md:w-1/3 md:pt-2">Новая заявка:</label>
            <div class="md:w-2/3">
                <span class="text-xs text-gray-400">Автоответ включает номер заявки для проверки статуса заявки</span>
                <div class="flex items-center gap-4 mt-2">
                    <label class="flex items-center gap-1.5 text-sm text-gray-600">
                        <input class="radio" type="radio" name="ticket_autoresponder" value="1" <?=$config['ticket_autoresponder']?'checked':''?> /> Включено
                    </label>
                    <label class="flex items-center gap-1.5 text-sm text-gray-600">
                        <input class="radio" type="radio" name="ticket_autoresponder" value="0" <?=!$config['ticket_autoresponder']?'checked':''?> /> Отключено
                    </label>
                </div>
            </div>
        </div>

        <div class="flex flex-col md:flex-row md:items-start gap-2 py-3 border-b border-gray-100">
            <label class="label md:w-1/3 md:pt-2">Новая заявка Менеджера:</label>
            <div class="md:w-2/3">
                <span class="text-xs text-gray-400">Уведомление отправляется когда менеджер создает заявку от имени пользователя (Менеджер может отключить)</span>
                <div class="flex items-center gap-4 mt-2">
                    <label class="flex items-center gap-1.5 text-sm text-gray-600">
                        <input class="radio" type="radio" name="ticket_notice_active" value="1" <?=$config['ticket_notice_active']?'checked':''?> /> Включено
                    </label>
                    <label class="flex items-center gap-1.5 text-sm text-gray-600">
                        <input class="radio" type="radio" name="ticket_notice_active" value="0" <?=!$config['ticket_notice_active']?'checked':''?> /> Отключено
                    </label>
                </div>
            </div>
        </div>

        <div class="flex flex-col md:flex-row md:items-start gap-2 py-3 border-b border-gray-100">
            <label class="label md:w-1/3 md:pt-2">Новое соообщение:</label>
            <div class="md:w-2/3">
                <span class="text-xs text-gray-400">Сообщение добавленное в существующую заявку</span>
                <div class="flex items-center gap-4 mt-2">
                    <label class="flex items-center gap-1.5 text-sm text-gray-600">
                        <input class="radio" type="radio" name="message_autoresponder" value="1" <?=$config['message_autoresponder']?'checked':''?> /> Включено
                    </label>
                    <label class="flex items-center gap-1.5 text-sm text-gray-600">
                        <input class="radio" type="radio" name="message_autoresponder" value="0" <?=!$config['message_autoresponder']?'checked':''?> /> Отключено
                    </label>
                </div>
            </div>
        </div>

        <div class="flex flex-col md:flex-row md:items-start gap-2 py-3">
            <label class="label md:w-1/3 md:pt-2">Уведомление о превышении лимита:</label>
            <div class="md:w-2/3">
                <span class="text-xs text-gray-400">Уведомление об отклонении заявки отправляется пользователю <b>только один раз</b> при нарушении лимита.</span>
                <div class="flex items-center gap-4 mt-2">
                    <label class="flex items-center gap-1.5 text-sm text-gray-600">
                        <input class="radio" type="radio" name="overlimit_notice_active" value="1" <?=$config['overlimit_notice_active']?'checked':''?> /> Включено
                    </label>
                    <label class="flex items-center gap-1.5 text-sm text-gray-600">
                        <input class="radio" type="radio" name="overlimit_notice_active" value="0" <?=!$config['overlimit_notice_active']?'checked':''?> /> Отключено
                    </label>
                </div>
                <p class="text-xs text-gray-400 mt-1"><b>Примечание:</b> Администратор получает уведомления обо ВСЕХ отклонениях по умолчанию.</p>
            </div>
        </div>

    </div>
</div>

<div class="card mb-6">
    <div class="card-header">
        <h2 class="font-heading font-semibold text-gray-900">Уведомления</h2>
    </div>
    <div class="card-body space-y-4">
        <p class="text-gray-500 text-sm">Уведомления отправляются пользователям с адреса 'Email без ответа', а оповещения персоналу - с 'Email для уведомлений', указанных выше в качестве адреса отправителя.</p>

        <div class="flex flex-col md:flex-row md:items-start gap-2 py-3 border-b border-gray-100">
            <label class="label md:w-1/3 md:pt-2">Уведомление о новой заявке:</label>
            <div class="md:w-2/3">
                <div class="flex items-center gap-4">
                    <label class="flex items-center gap-1.5 text-sm text-gray-600">
                        <input class="radio" type="radio" name="ticket_alert_active" value="1" <?=$config['ticket_alert_active']?'checked':''?> /> Включено
                    </label>
                    <label class="flex items-center gap-1.5 text-sm text-gray-600">
                        <input class="radio" type="radio" name="ticket_alert_active" value="0" <?=!$config['ticket_alert_active']?'checked':''?> /> Отключено
                    </label>
                </div>
                <span class="text-xs text-gray-400">Выберите получателей</span>&nbsp;<span class="text-red-500 text-sm">&nbsp;<?=$errors['ticket_alert_active']?></span>
                <div class="flex flex-wrap items-center gap-4 mt-1">
                    <label class="flex items-center gap-1.5 text-sm text-gray-600">
                        <input class="checkbox" type="checkbox" name="ticket_alert_admin" <?=$config['ticket_alert_admin']?'checked':''?>> Email администратора
                    </label>
                    <label class="flex items-center gap-1.5 text-sm text-gray-600">
                        <input class="checkbox" type="checkbox" name="ticket_alert_dept_manager" <?=$config['ticket_alert_dept_manager']?'checked':''?>> Менеджер отдела
                    </label>
                    <label class="flex items-center gap-1.5 text-sm text-gray-600">
                        <input class="checkbox" type="checkbox" name="ticket_alert_dept_members" <?=$config['ticket_alert_dept_members']?'checked':''?>> Сотрудники отдела (много писем)
                    </label>
                </div>
            </div>
        </div>

        <div class="flex flex-col md:flex-row md:items-start gap-2 py-3 border-b border-gray-100">
            <label class="label md:w-1/3 md:pt-2">Уведомление о новом сообщении:</label>
            <div class="md:w-2/3">
                <div class="flex items-center gap-4">
                    <label class="flex items-center gap-1.5 text-sm text-gray-600">
                        <input class="radio" type="radio" name="message_alert_active" value="1" <?=$config['message_alert_active']?'checked':''?> /> Включено
                    </label>
                    <label class="flex items-center gap-1.5 text-sm text-gray-600">
                        <input class="radio" type="radio" name="message_alert_active" value="0" <?=!$config['message_alert_active']?'checked':''?> /> Отключено
                    </label>
                </div>
                <span class="text-xs text-gray-400">Выберите получателей</span>&nbsp;<span class="text-red-500 text-sm">&nbsp;<?=$errors['message_alert_active']?></span>
                <div class="flex flex-wrap items-center gap-4 mt-1">
                    <label class="flex items-center gap-1.5 text-sm text-gray-600">
                        <input class="checkbox" type="checkbox" name="message_alert_laststaff" <?=$config['message_alert_laststaff']?'checked':''?>> Последнему ответчику
                    </label>
                    <label class="flex items-center gap-1.5 text-sm text-gray-600">
                        <input class="checkbox" type="checkbox" name="message_alert_assigned" <?=$config['message_alert_assigned']?'checked':''?>> Назначеному менеджеру
                    </label>
                    <label class="flex items-center gap-1.5 text-sm text-gray-600">
                        <input class="checkbox" type="checkbox" name="message_alert_dept_manager" <?=$config['message_alert_dept_manager']?'checked':''?>> Менеджеру отдела
                    </label>
                </div>
            </div>
        </div>

        <div class="flex flex-col md:flex-row md:items-start gap-2 py-3 border-b border-gray-100">
            <label class="label md:w-1/3 md:pt-2">Уведомление о новом внутреннем сообщении:</label>
            <div class="md:w-2/3">
                <div class="flex items-center gap-4">
                    <label class="flex items-center gap-1.5 text-sm text-gray-600">
                        <input class="radio" type="radio" name="note_alert_active" value="1" <?=$config['note_alert_active']?'checked':''?> /> Включено
                    </label>
                    <label class="flex items-center gap-1.5 text-sm text-gray-600">
                        <input class="radio" type="radio" name="note_alert_active" value="0" <?=!$config['note_alert_active']?'checked':''?> /> Отключено
                    </label>
                </div>
                <span class="text-xs text-gray-400">Выберите получателей</span>&nbsp;<span class="text-red-500 text-sm">&nbsp;<?=$errors['note_alert_active']?></span>
                <div class="flex flex-wrap items-center gap-4 mt-1">
                    <label class="flex items-center gap-1.5 text-sm text-gray-600">
                        <input class="checkbox" type="checkbox" name="note_alert_laststaff" <?=$config['note_alert_laststaff']?'checked':''?>> Последнему ответчику
                    </label>
                    <label class="flex items-center gap-1.5 text-sm text-gray-600">
                        <input class="checkbox" type="checkbox" name="note_alert_assigned" <?=$config['note_alert_assigned']?'checked':''?>> Назначеному менеджеру
                    </label>
                    <label class="flex items-center gap-1.5 text-sm text-gray-600">
                        <input class="checkbox" type="checkbox" name="note_alert_dept_manager" <?=$config['note_alert_dept_manager']?'checked':''?>> Менеджеру отдела
                    </label>
                </div>
            </div>
        </div>

        <div class="flex flex-col md:flex-row md:items-start gap-2 py-3 border-b border-gray-100">
            <label class="label md:w-1/3 md:pt-2">Уведомление о истекшей заявке:</label>
            <div class="md:w-2/3">
                <div class="flex items-center gap-4">
                    <label class="flex items-center gap-1.5 text-sm text-gray-600">
                        <input class="radio" type="radio" name="overdue_alert_active" value="1" <?=$config['overdue_alert_active']?'checked':''?> /> Включено
                    </label>
                    <label class="flex items-center gap-1.5 text-sm text-gray-600">
                        <input class="radio" type="radio" name="overdue_alert_active" value="0" <?=!$config['overdue_alert_active']?'checked':''?> /> Отключено
                    </label>
                </div>
                <span class="text-xs text-gray-400">На Email администратора отправляется по умолчанию. Выберите дополнительных получателей</span>&nbsp;<span class="text-red-500 text-sm">&nbsp;<?=$errors['overdue_alert_active']?></span>
                <div class="flex flex-wrap items-center gap-4 mt-1">
                    <label class="flex items-center gap-1.5 text-sm text-gray-600">
                        <input class="checkbox" type="checkbox" name="overdue_alert_assigned" <?=$config['overdue_alert_assigned']?'checked':''?>> Назначеному менеджеру
                    </label>
                    <label class="flex items-center gap-1.5 text-sm text-gray-600">
                        <input class="checkbox" type="checkbox" name="overdue_alert_dept_manager" <?=$config['overdue_alert_dept_manager']?'checked':''?>> Менеджеру отдела
                    </label>
                    <label class="flex items-center gap-1.5 text-sm text-gray-600">
                        <input class="checkbox" type="checkbox" name="overdue_alert_dept_members" <?=$config['overdue_alert_dept_members']?'checked':''?>> Сотрудники отдела (много писем)
                    </label>
                </div>
            </div>
        </div>

        <div class="flex flex-col md:flex-row md:items-start gap-2 py-3">
            <label class="label md:w-1/3 md:pt-2">Системные ошибки:</label>
            <div class="md:w-2/3">
                <span class="text-xs text-gray-400">Ошибки отправляются на email администратора</span>
                <div class="flex flex-wrap items-center gap-4 mt-2">
                    <label class="flex items-center gap-1.5 text-sm text-gray-600">
                        <input class="checkbox" type="checkbox" name="send_sys_errors" <?=$config['send_sys_errors']?'checked':'checked'?> disabled> Системные ошибки
                    </label>
                    <label class="flex items-center gap-1.5 text-sm text-gray-600">
                        <input class="checkbox" type="checkbox" name="send_sql_errors" <?=$config['send_sql_errors']?'checked':''?>> SQL ошибки
                    </label>
                    <label class="flex items-center gap-1.5 text-sm text-gray-600">
                        <input class="checkbox" type="checkbox" name="send_login_errors" <?=$config['send_login_errors']?'checked':''?>> Большое кол-во попыток входа
                    </label>
                </div>
            </div>
        </div>

    </div>
</div>

<div class="flex items-center gap-3 mt-6">
    <input class="btn-primary" type="submit" name="submit" value="Сохранить изменения">
    <input class="btn-secondary" type="reset" name="reset" value="Очистить">
</div>
</form>
