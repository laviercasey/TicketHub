<?php
if(!defined('OSTCLIENTINC')) die('Kwaheri rafiki!');

$info=($_POST && $errors)?Format::input($_POST):array();
?>

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
<?php } elseif($warn) { ?>
    <div class="alert-warning mb-6">
        <i data-lucide="alert-circle" class="w-4 h-4 flex-shrink-0"></i>
        <span><?=Format::htmlchars($warn)?></span>
    </div>
<?php } ?>

<div class="card">
    <div class="card-header">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-lg bg-indigo-50 flex items-center justify-center">
                <i data-lucide="edit-3" class="w-5 h-5 text-indigo-600"></i>
            </div>
            <div>
                <h2 class="font-heading font-semibold text-gray-900 text-lg">Создать новую заявку</h2>
                <p class="text-sm text-gray-500">Пожалуйста заполните форму для создания новой заявки</p>
            </div>
        </div>
    </div>
    <div class="card-body">
        <form action="open.php" method="POST" enctype="multipart/form-data" class="space-y-5">
            <?=Misc::csrfField()?>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <!-- Имя -->
                <div class="form-group">
                    <label for="name" class="label">Имя <span class="text-red-500">*</span></label>
                    <?php if ($thisclient && ($name=$thisclient->getName())) { ?>
                        <input type="hidden" name="name" value="<?=Format::htmlchars($name)?>">
                        <p class="text-sm text-gray-900 py-2.5"><?=Format::htmlchars($name)?></p>
                    <?php } else { ?>
                        <input type="text" name="name" id="name" class="input <?=$errors['name']?'input-error':''?>"
                               value="<?=$info['name']?>" placeholder="Ваше имя" required>
                        <?php if($errors['name']) { ?>
                            <span class="form-error"><?=$errors['name']?></span>
                        <?php } ?>
                    <?php } ?>
                </div>

                <!-- Email -->
                <div class="form-group">
                    <label for="email" class="label">Email <span class="text-red-500">*</span></label>
                    <?php if ($thisclient && ($email=$thisclient->getEmail())) { ?>
                        <input type="hidden" name="email" value="<?=Format::htmlchars($email)?>">
                        <p class="text-sm text-gray-900 py-2.5"><?=Format::htmlchars($email)?></p>
                    <?php } else { ?>
                        <input type="email" name="email" id="email" class="input <?=$errors['email']?'input-error':''?>"
                               value="<?=$info['email']?>" placeholder="your@email.com" required>
                        <?php if($errors['email']) { ?>
                            <span class="form-error"><?=$errors['email']?></span>
                        <?php } ?>
                    <?php } ?>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <!-- Номер кабинета -->
                <div class="form-group">
                    <label for="phone" class="label">Номер кабинета <span class="text-red-500">*</span></label>
                    <input type="text" name="phone" id="phone" class="input <?=$errors['phone']?'input-error':''?>"
                           value="<?=$info['phone']?>" placeholder="Номер кабинета">
                    <?php if($errors['phone']) { ?>
                        <span class="form-error"><?=$errors['phone']?></span>
                    <?php } ?>
                </div>

                <!-- Тема обращения -->
                <div class="form-group">
                    <label for="asdTopicId" class="label">Тема обращения <span class="text-red-500">*</span></label>
                    <select name="topicId" id="asdTopicId" class="select <?=$errors['topicId']?'input-error':''?>" onchange="changeMessage();" required>
                        <option value="">-- Выберите тему --</option>
                        <?php
                        $services= db_query('SELECT topic_id,topic FROM '.TOPIC_TABLE.' WHERE isactive=1 ORDER BY topic');
                        if($services && db_num_rows($services)) {
                            while (list($topicId,$topic) = db_fetch_row($services)){
                                $selected = ($info['topicId']==$topicId)?'selected':'';
                                ?>
                                <option value="<?=intval($topicId)?>" <?=$selected?>><?=Format::htmlchars($topic)?></option>
                                <?php
                            }
                        } else { ?>
                            <option value="0">Все вопросы</option>
                        <?php } ?>
                    </select>
                    <?php if($errors['topicId']) { ?>
                        <span class="form-error"><?=$errors['topicId']?></span>
                    <?php } ?>
                </div>
            </div>

            <!-- Заголовок -->
            <div class="form-group">
                <label for="subject" class="label">Заголовок <span class="text-red-500">*</span></label>
                <input type="text" name="subject" id="subject" class="input <?=$errors['subject']?'input-error':''?>"
                       value="<?=$info['subject']?>" placeholder="Краткое описание проблемы" required>
                <?php if($errors['subject']) { ?>
                    <span class="form-error"><?=$errors['subject']?></span>
                <?php } ?>
            </div>

            <!-- Сообщение -->
            <div class="form-group">
                <label for="asdMessage" class="label">Сообщение <span class="text-red-500">*</span></label>
                <textarea name="message" id="asdMessage" class="textarea <?=$errors['message']?'input-error':''?>"
                          rows="12" required><?=$info['message']?></textarea>
                <?php if($errors['message']) { ?>
                    <span class="form-error"><?=$errors['message']?></span>
                <?php } ?>
                <span class="form-hint">Подробно опишите вашу проблему или запрос.</span>
            </div>

            <?php
            if($cfg->allowPriorityChange()) {
                $sql='SELECT priority_id,priority_desc FROM '.TICKET_PRIORITY_TABLE.' WHERE ispublic=1 ORDER BY priority_urgency DESC';
                if(($priorities=db_query($sql)) && db_num_rows($priorities)){ ?>
                    <div class="form-group">
                        <label for="pri" class="label">Приоритет</label>
                        <select name="pri" id="pri" class="select">
                            <?php
                            $info['pri']=$info['pri']?$info['pri']:$cfg->getDefaultPriorityId();
                            while($row=db_fetch_array($priorities)){ ?>
                                <option value="<?=intval($row['priority_id'])?>" <?=$info['pri']==$row['priority_id']?'selected':''?>>
                                    <?=Format::htmlchars($row['priority_desc'])?>
                                </option>
                            <?php } ?>
                        </select>
                    </div>
                <?php }
            } ?>

            <?php if(($cfg->allowOnlineAttachments() && !$cfg->allowAttachmentsOnlogin())
                    || ($cfg->allowAttachmentsOnlogin() && ($thisclient && $thisclient->isValid()))){ ?>
                <div class="form-group">
                    <label for="attachment" class="label">Вложение</label>
                    <input type="file" name="attachment" id="attachment"
                           class="block w-full text-sm text-gray-500 file:mr-4 file:py-2.5 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-indigo-50 file:text-indigo-600 hover:file:bg-indigo-100 cursor-pointer <?=$errors['attachment']?'ring-2 ring-red-300':''?>">
                    <?php if($errors['attachment']) { ?>
                        <span class="form-error"><?=$errors['attachment']?></span>
                    <?php } ?>
                </div>
            <?php } ?>

            <?php if($cfg && $cfg->enableCaptcha() && (!$thisclient || !$thisclient->isValid())) {
                if($_POST && $errors && !$errors['captcha'])
                    $errors['captcha']='Пожалуйста введите текст еще раз';
                ?>
                <div class="form-group">
                    <label for="captcha" class="label">Антиспам <span class="text-red-500">*</span></label>
                    <div class="flex flex-col sm:flex-row gap-4 items-start">
                        <img src="captcha.php" alt="CAPTCHA" class="rounded-lg border border-gray-200">
                        <div class="flex-1 w-full">
                            <input type="text" name="captcha" id="captcha" class="input <?=$errors['captcha']?'input-error':''?>"
                                   placeholder="Введите текст с изображения" required>
                            <span class="form-hint">Введите текст показанный на изображении.</span>
                            <?php if($errors['captcha']) { ?>
                                <span class="form-error"><?=$errors['captcha']?></span>
                            <?php } ?>
                        </div>
                    </div>
                </div>
            <?php } ?>

            <!-- Кнопки -->
            <div class="flex flex-wrap gap-3 pt-4 border-t border-gray-100">
                <button type="submit" name="submit_x" class="btn-primary btn-lg">
                    <i data-lucide="send" class="w-4 h-4"></i> Создать заявку
                </button>
                <button type="reset" class="btn-secondary">
                    <i data-lucide="eraser" class="w-4 h-4"></i> Очистить
                </button>
                <button type="button" class="btn-ghost" onclick="window.location.href='index.php'">
                    <i data-lucide="x" class="w-4 h-4"></i> Отмена
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Шаблоны заявок -->
<div class="mt-8" x-data="{ openTemplate: null }">
    <h3 class="text-lg font-heading font-semibold text-gray-900 mb-4">Шаблоны заявок</h3>
    <p class="text-sm text-gray-500 mb-4">Вы можете скопировать любой шаблон в форму. Заполнены в качестве примера!</p>

    <div class="space-y-3">
        <!-- Копи-центр -->
        <div class="card">
            <button @click="openTemplate = openTemplate === 'cc' ? null : 'cc'" class="w-full px-5 py-3 flex items-center justify-between text-left hover:bg-gray-50 transition-colors rounded-xl">
                <span class="font-medium text-gray-900">Шаблон заявки на копи-центр</span>
                <i data-lucide="chevron-down" class="w-4 h-4 text-gray-400 transition-transform" :class="openTemplate === 'cc' ? 'rotate-180' : ''"></i>
            </button>
            <div x-show="openTemplate === 'cc'" x-cloak class="px-5 pb-4 border-t border-gray-100">
                <div class="overflow-x-auto mt-3">
                    <table class="table-modern w-full">
                        <tbody>
                            <tr><td class="table-td font-medium">Вид работ</td><td class="table-td">Печать</td></tr>
                            <tr><td class="table-td font-medium">Размещение исходных материалов:</td><td class="table-td">O:\Отдел автоматизации\Вставский А.Н.\тест.doc</td></tr>
                            <tr><td class="table-td font-medium">Дата и время выполнения:</td><td class="table-td">Вчера. Мероприятие уже 15 минут как идёт!</td></tr>
                            <tr><td class="table-td font-medium">Количество:</td><td class="table-td">Много... Штук 200</td></tr>
                            <tr><td class="table-td font-medium">Формат:</td><td class="table-td">А4</td></tr>
                            <tr><td class="table-td font-medium">Плотность:</td><td class="table-td">160 ну или просто плотная</td></tr>
                            <tr><td class="table-td font-medium">Цвет бумаги:</td><td class="table-td">Синий</td></tr>
                            <tr><td class="table-td font-medium">Цветная печать:</td><td class="table-td">Да</td></tr>
                            <tr><td class="table-td font-medium">Двухсторонняя печать:</td><td class="table-td">Да</td></tr>
                            <tr><td class="table-td font-medium">Краткое описание:</td><td class="table-td">Очень нужно. Внизу печать в форме котёнка.</td></tr>
                        </tbody>
                    </table>
                </div>
                <div class="mt-3">
                    <button onclick="insertCC();" class="btn-primary btn-sm">
                        <i data-lucide="copy" class="w-3.5 h-3.5"></i> Вставить в форму
                    </button>
                </div>
            </div>
        </div>

        <!-- Мероприятие -->
        <div class="card">
            <button @click="openTemplate = openTemplate === 'event' ? null : 'event'" class="w-full px-5 py-3 flex items-center justify-between text-left hover:bg-gray-50 transition-colors rounded-xl">
                <span class="font-medium text-gray-900">Шаблон заявки на мероприятие</span>
                <i data-lucide="chevron-down" class="w-4 h-4 text-gray-400 transition-transform" :class="openTemplate === 'event' ? 'rotate-180' : ''"></i>
            </button>
            <div x-show="openTemplate === 'event'" x-cloak class="px-5 pb-4 border-t border-gray-100">
                <div class="overflow-x-auto mt-3">
                    <table class="table-modern w-full">
                        <tbody>
                            <tr><td class="table-td font-medium">Название:</td><td class="table-td">Веселые дети</td></tr>
                            <tr><td class="table-td font-medium">Место проведения:</td><td class="table-td">Закоулок где-то в цоколе...</td></tr>
                            <tr><td class="table-td font-medium">Дата и время проведения:</td><td class="table-td">2015.12.31 23:59:59</td></tr>
                            <tr><td class="table-td font-medium">Время окончания:</td><td class="table-td">2015.12.31 23:59:59</td></tr>
                            <tr><td class="table-td font-medium">Краткое описание:</td><td class="table-td">Дети собираются вместе и пытаются выбраться.</td></tr>
                            <tr><td class="table-td font-medium">Оборудование. Список:</td><td class="table-td">Лифт. Два микрофона и телевизор.</td></tr>
                            <tr><td class="table-td font-medium">Техническая поддержка:</td><td class="table-td">Специалист по звуку и свету.</td></tr>
                        </tbody>
                    </table>
                </div>
                <div class="mt-3">
                    <button onclick="insertEvent();" class="btn-primary btn-sm">
                        <i data-lucide="copy" class="w-3.5 h-3.5"></i> Вставить в форму
                    </button>
                </div>
            </div>
        </div>

        <!-- Уборка и мебель -->
        <div class="card">
            <button @click="openTemplate = openTemplate === 'its' ? null : 'its'" class="w-full px-5 py-3 flex items-center justify-between text-left hover:bg-gray-50 transition-colors rounded-xl">
                <span class="font-medium text-gray-900">Шаблон заявки на уборку и перенос мебели</span>
                <i data-lucide="chevron-down" class="w-4 h-4 text-gray-400 transition-transform" :class="openTemplate === 'its' ? 'rotate-180' : ''"></i>
            </button>
            <div x-show="openTemplate === 'its'" x-cloak class="px-5 pb-4 border-t border-gray-100">
                <div class="overflow-x-auto mt-3">
                    <table class="table-modern w-full">
                        <tbody>
                            <tr><td class="table-td font-medium">Место:</td><td class="table-td">Всё там же в цоколе... или в 220</td></tr>
                            <tr><td class="table-td font-medium">Дата и время:</td><td class="table-td">2015.12.31 23:59:59</td></tr>
                            <tr><td class="table-td font-medium">Уборка:</td><td class="table-td">да</td></tr>
                            <tr><td class="table-td font-medium">Мебель откуда:</td><td class="table-td">два стула с третьего этажа</td></tr>
                            <tr><td class="table-td font-medium">Мебель куда:</td><td class="table-td">220 комната</td></tr>
                            <tr><td class="table-td font-medium">Краткое описание:</td><td class="table-td">Стулья получить у человека в пальто...</td></tr>
                        </tbody>
                    </table>
                </div>
                <div class="mt-3">
                    <button onclick="insertITS();" class="btn-primary btn-sm">
                        <i data-lucide="copy" class="w-3.5 h-3.5"></i> Вставить в форму
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
	var event='';
	function changeMessage(){
		switch (document.getElementById('asdTopicId').value){
		    case "6": insertCC(); break;
		    case "5": insertEvent(); break;
		    case "4": insertITS(); break;
		    case "10": insertJUR(); break;
		    case "18": insertMACH(); break;
		    default: document.getElementById('asdMessage').value="";
		}
	}

	function insertCC(){
		event='Вид работ:'+'\t\n'+'Размещение исходных материалов:'+'\t\n'+'Дата и время выполнения:'+'\t\n'+'Количество:'+'\t\n'+'Формат:'+'\t\n'+'Плотность:'+'\t\n'+'Цвет бумаги:'+'\t\n'+'Цветная печать:'+'\t\n'+'Двухсторонняя печать:'+'\t\n'+'Краткое описание:'+'\t\n';
		document.getElementById('asdMessage').value=event;
	}

	function insertEvent(){
		event='Название:'+'\t\n'+'Место проведения:'+'\t\n'+'Предполагаемое кол-во посетителей:'+'\t\n'+'Контакты куратора от РГДБ:'+'\t\n'+'Дата и время проведения (+окончание):'+'\t\n'+'Дата и время начало репетиции (+окончание):'+'\t\n'+'Время на монтаж/демонтаж реквизита до и после:'+'\t\n'+'Краткое описание происходящего:'+'\t\n'+'Необходимость тех.поддержки кроме звуковиков:'+'\t\n'+'Оборудование от РГДБ. Список:'+'\t\n'+'\n'+'Необходимость уборки (до и после):'+'\t\n'+'Необходимость переноса мебели (что, куда, откуда, унести обратно):'+'\t\n'+'\n'+'Отключение турникетов (да/нет, + отдельная заявка в Support):'+'\t\n'+'\n'+'\n'+'ВНИМАНИЕ! СЛЕДУЮЩИЕ ПОЛЯ ТРЕБУЮТ ДОПОЛНИТЕЛЬНОЙ ПОДАЧИ'+'\t\n'+'БУМАЖНОЙ СЛУЖЕБНОЙ ЗАПИСКИ АНУФРИЕВУ В.И. ЭТО ДЛЯ ПОЛИЦИИ НА ВХОДЕ.'+'\t\n'+'Парковка (да/нет):'+'\t\n'+'Внос реквизита, оборудования (да/нет, список):'+'\t\n'+'Проход в нерабочее время (да/нет):'+'\t\n'+'\n'+'\n'+'ЕСЛИ МЕРОПРИЯТИЕ ПРОВОДИТ СТОРОННИЙ ОРГАНИЗАТОР (СторОрг),'+'\t\n'+'ЗАПОЛНИТЕ ДОПОЛНИТЕЛЬНЫЕ ПОЛЯ'+'\t\n'+'\n'+'Контакты куратора от СторОрг:'+'\t\n'+'Оборудование СторОрг (да/нет, список для согласования с гл. инженером, дополнительно бумажная):'+'\t\n';
		document.getElementById('asdMessage').value=event;
	}

	function insertITS(){
		event='Место:'+'\t\n'+'Дата и время:'+'\t\n'+'\t\n'+'Уборка:'+'\t\n'+'\t'+'или'+'\t\n'+'Мебель откуда:'+'\t\n'+'Мебель куда:'+'\t\n'+'\t\n'+'Краткое описание:'+'\t\n';
		document.getElementById('asdMessage').value=event;
	}

	function insertJUR(){
		event='Заказчик:'+'\t\n'+'Название мероприятия:'+'\t\n'+'Краткое описание:'+'\t\n'+'Дата мероприятия:'+'\t\n'+'\tВремя начала:'+'\t\n'+'\tВремя окончания:'+'\t\n'+'Если есть Дата репетиции, если есть:'+'\t\n'+'\tВремя начала репетиции, есди есть:'+'\t\n'+'\tВремя окончания репетиции, есди есть:'+'\t\n'+'Сумма договора:'+'\t\n'+'ФИО и телефон куратора от РГДБ:'+'\t\n'+'ФИО и телефон куратора от заказчика:'+'\t\n'+'Условия оплаты (предоплата, оплата по факту):'+'\t\n'+'Полное наименование от контрагента:'+'\t\n'+'Должность и ФИО Руководителя заказчика:'+'\t\n'+'На основании чего действует руководитель:'+'\t\n'+'Юр.адрес заказчика:'+'\t\n'+'ИНН КПП заказчика:'+'\t\n'+'ОГРН заказчика:'+'\t\n'+'Наименование банка заказчика:'+'\t\n'+'БИК:'+'\t\n'+'Расчетный счёт заказчика:'+'\t\n'+'Корресп. счёт заказчика:'+'\t\n'+'Система налогообложения (ОСНО, УСНО+ссылка на статью НК РФ):'+'\t\n';
		document.getElementById('asdMessage').value=event;
	}
	function insertMACH(){
		event='Дата поездки: '+'\t\n'+'Время поездки (отбытие, прибытие): '+'\t\n'+'Откуда (адрес, компания): '+'\t\n'+'Куда (адрес, компания): '+'\t\n'+'Примерный вес/ Размер/ Количество: '+'\t\n'+'Нужен ли пропуск на машину/водителя по месту поездки: '+'\t\n'+'ФИО и номер телефона ответственного от РГДБ: '+'\t\n';
		document.getElementById('asdMessage').value=event;
	}
</script>
