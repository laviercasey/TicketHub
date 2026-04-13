<?php
if(!defined('OSTSCPINC') || !is_object($thisuser) || !$rep) die('Kwaheri');
?>
<form action="profile.php" method="post">
 <?php echo Misc::csrfField(); ?>
 <input type="hidden" name="t" value="pref">
 <input type="hidden" name="id" value="<?=$thisuser->getId()?>">

<div class="card">
    <div class="card-header">
        <h2 class="font-heading font-semibold text-gray-900">Мои Настройки</h2>
    </div>
    <div class="card-body space-y-5">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
            <div class="form-group">
                <label class="label">Максимум на страницу</label>
                <div class="flex items-center gap-2">
                    <select class="select w-auto" name="max_page_size">
                        <?php
                        $pagelimit=$rep['max_page_size']?$rep['max_page_size']:$cfg->getPageSize();
                        for ($i = 5; $i <= 50; $i += 5) { ?>
                            <option <?=$pagelimit== $i ? 'selected':''?>><?=$i?></option>
                        <?php } ?>
                    </select>
                    <span class="text-sm text-gray-500">запросов/записей на страницу</span>
                </div>
            </div>

            <div class="form-group">
                <label class="label">Авто обновление</label>
                <div class="flex items-center gap-2">
                    <input class="input w-20" type="text" name="auto_refresh_rate" value="<?=$rep['auto_refresh_rate']?>">
                    <span class="text-sm text-gray-500">минут (0 — отключено)</span>
                </div>
            </div>
        </div>

        <div class="form-group">
            <label class="label">Временная зона</label>
            <select class="select" name="timezone_offset">
                <?php
                $gmoffset  = date("Z") / 3600;
                $currentoffset = ($rep['timezone_offset']==NULL)?$cfg->getTZOffset():$rep['timezone_offset'];
                echo "<option value=\"$gmoffset\">Server Time (GMT $gmoffset:00)</option>";
                $timezones= db_query('SELECT offset,timezone FROM '.TIMEZONE_TABLE);
                while (list($offset,$tz) = db_fetch_row($timezones)){
                    $selected = ($currentoffset==$offset) ?'selected':'';
                    $tag=($offset)?"GMT $offset ($tz)":" GMT ($tz)"; ?>
                    <option value="<?=$offset?>"<?=$selected?>><?=$tag?></option>
                <?php } ?>
            </select>
        </div>

        <div class="flex items-center gap-2">
            <input type="checkbox" name="daylight_saving" class="checkbox" <?=$rep['daylight_saving'] ? 'checked': ''?>>
            <span class="text-sm text-gray-600">Переход на летнее время</span>
        </div>

        <div class="form-group">
            <label class="label">Текущее Время</label>
            <p class="text-sm font-medium text-gray-900 py-2">
                <i data-lucide="clock" class="w-4 h-4 inline-block text-indigo-500 mr-1"></i>
                <?=Format::date($cfg->getDateTimeFormat(),Misc::gmtime(),$rep['timezone_offset'],$rep['daylight_saving'])?>
            </p>
        </div>
    </div>
</div>

<div class="flex items-center gap-3 mt-6">
    <button class="btn-primary" type="submit" name="submit">
        <i data-lucide="save" class="w-4 h-4"></i> Отправить
    </button>
    <button class="btn-secondary" type="reset">Очистить</button>
    <button class="btn-ghost" type="button" onclick='window.location.href="profile.php"'>Отмена</button>
</div>
</form>
