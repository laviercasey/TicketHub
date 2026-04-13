<?php
if(!defined('OSTADMININC') || !$thisuser->isadmin()) die('Доступ запрещён');
$config=($errors && $_POST)?Format::input($_POST):$cfg->getConfig();
$warn=array();
if(!$config['allow_attachments'] && $config['allow_email_attachments'])
    $warn['allow_email_attachments']='*Вложения отключены.';
if(!$config['allow_attachments'] && ($config['allow_online_attachments'] || $config['allow_online_attachments_onlogin']))
    $warn['allow_online_attachments']='*Вложения отключены.';
?>
<form action="admin.php?t=attach" method="post">
<?php echo Misc::csrfField(); ?>
<input type="hidden" name="t" value="attach">

<div class="card">
    <div class="card-header">
        <h2 class="font-heading font-semibold text-gray-900">Настройки Вложений</h2>
    </div>
    <div class="card-body space-y-5">
        <div class="bg-amber-50 border border-amber-200 rounded-lg p-4 text-sm text-amber-800">
            <i data-lucide="alert-triangle" class="w-4 h-4 inline-block mr-1"></i>
            Перед включением вложений убедитесь, что вы понимаете настройки безопасности, связанные с загрузкой файлов.
        </div>

        <div class="form-group">
            <label class="label">Разрешить Вложения</label>
            <div class="flex items-center gap-2">
                <input type="checkbox" name="allow_attachments" class="checkbox" <?=$config['allow_attachments'] ?'checked':''?>>
                <span class="text-sm text-gray-600">Разрешить Вложения <span class="text-gray-400">(Общие Настройки)</span></span>
            </div>
            <?php if($errors['allow_attachments']) { ?><span class="form-error"><?=$errors['allow_attachments']?></span><?php } ?>
        </div>

        <div class="form-group">
            <label class="label">Почтовые Вложения</label>
            <div class="flex items-center gap-2">
                <input type="checkbox" name="allow_email_attachments" class="checkbox" <?=$config['allow_email_attachments'] ? 'checked':''?>>
                <span class="text-sm text-gray-600">Принимать файлы с писем</span>
            </div>
            <?php if($warn['allow_email_attachments']) { ?><span class="text-amber-600 text-sm"><?=$warn['allow_email_attachments']?></span><?php } ?>
        </div>

        <div class="form-group">
            <label class="label">Web Вложения</label>
            <div class="space-y-2">
                <label class="flex items-center gap-2">
                    <input type="checkbox" name="allow_online_attachments" class="checkbox" <?=$config['allow_online_attachments'] ?'checked':''?>>
                    <span class="text-sm text-gray-600">Разрешить загрузку вложений</span>
                </label>
                <label class="flex items-center gap-2 ml-6">
                    <input type="checkbox" name="allow_online_attachments_onlogin" class="checkbox" <?=$config['allow_online_attachments_onlogin'] ?'checked':''?>>
                    <span class="text-sm text-gray-600">Только для зарегистрированных пользователей</span>
                </label>
            </div>
            <?php if($warn['allow_online_attachments']) { ?><span class="text-amber-600 text-sm"><?=$warn['allow_online_attachments']?></span><?php } ?>
        </div>

        <div class="form-group">
            <label class="label">Файлы ответа сотрудника</label>
            <div class="flex items-center gap-2">
                <input type="checkbox" name="email_attachments" class="checkbox" <?=$config['email_attachments']?'checked':''?>>
                <span class="text-sm text-gray-600">Отправить вложения пользователю по email</span>
            </div>
        </div>

        <div class="form-group">
            <label class="label">Максимальный Размер Файла</label>
            <div class="flex items-center gap-2">
                <input class="input w-48" type="text" name="max_file_size" value="<?=$config['max_file_size']?>">
                <span class="text-sm text-gray-500">байт</span>
            </div>
            <?php if($errors['max_file_size']) { ?><span class="form-error"><?=$errors['max_file_size']?></span><?php } ?>
        </div>

        <div class="form-group">
            <label class="label">Каталог для вложений</label>
            <p class="text-xs text-gray-400 mb-1">Web user (например: apache) должен иметь доступ для записи в данный каталог.</p>
            <input class="input" type="text" name="upload_dir" value="<?=$config['upload_dir']?>">
            <?php if($errors['upload_dir']) { ?><span class="form-error"><?=$errors['upload_dir']?></span><?php } ?>
            <?php if($attwarn) { ?><span class="text-red-500 text-sm"><?=$attwarn?></span><?php } ?>
        </div>

        <div class="form-group">
            <label class="label">Разрешенные Типы Файлов</label>
            <p class="text-xs text-gray-400 mb-1">Введите расширения разрешенных файлов, например: <code class="text-xs">.doc, .pdf</code>. Для всех типов: <code class="text-xs">.*</code> (не рекомендуется).</p>
            <textarea class="textarea" name="allowed_filetypes" rows="4"><?=$config['allowed_filetypes']?></textarea>
        </div>
    </div>
</div>

<div class="flex items-center gap-3 mt-6">
    <button class="btn-primary" type="submit" name="submit">
        <i data-lucide="save" class="w-4 h-4"></i> Сохранить Изменения
    </button>
    <button class="btn-secondary" type="reset">Отменить Изменения</button>
</div>
</form>
