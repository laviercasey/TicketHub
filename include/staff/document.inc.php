<?php
if(!defined('OSTSCPINC') or !$thisuser->canManageKb()) die('Доступ запрещён');

$info=($errors && $_POST)?Format::input($_POST):($document?Format::htmlchars($document->getInfo()):array());
if($document && $document->getId() && $_REQUEST['a']!='add'){
    $title='Редактирование документа';
    $action='update';
}else {
    $title='Новый документ';
    $action='add';
    if(!isset($info['isenabled'])) $info['isenabled']=1;
    if(!isset($info['doc_type'])) $info['doc_type']='file';
    if(!isset($info['audience'])) $info['audience']='all';
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

<form action="documents.php" method="POST" name="docform" enctype="multipart/form-data">
    <?=Misc::csrfField()?>
    <input type="hidden" name="a" value="<?=$action?>">
    <input type="hidden" name="id" value="<?=$info['doc_id']?>">

<div class="card">
    <div class="card-header">
        <h2 class="font-heading font-semibold text-gray-900"><?=$title?></h2>
    </div>
    <div class="card-body space-y-5">
        <div class="form-group">
            <label class="label">Название <span class="text-red-500">*</span></label>
            <input class="input" type="text" name="title" value="<?=$info['title']?>">
            <?php if($errors['title']) { ?><span class="form-error"><?=$errors['title']?></span><?php } ?>
        </div>

        <div class="form-group">
            <label class="label">Описание</label>
            <textarea class="textarea" name="description" rows="4"><?=$info['description']?></textarea>
        </div>

        <div class="form-group">
            <label class="label">Тип документа</label>
            <div class="flex items-center gap-4">
                <label class="flex items-center gap-1.5 text-sm text-gray-600">
                    <input type="radio" name="doc_type" value="file" class="radio" <?=$info['doc_type']=='file'?'checked':''?>
                           onchange="toggleDocType(this.value)"> Файл
                </label>
                <label class="flex items-center gap-1.5 text-sm text-gray-600">
                    <input type="radio" name="doc_type" value="link" class="radio" <?=$info['doc_type']=='link'?'checked':''?>
                           onchange="toggleDocType(this.value)"> Ссылка
                </label>
            </div>
            <?php if($errors['doc_type']) { ?><span class="form-error"><?=$errors['doc_type']?></span><?php } ?>
        </div>

        <!-- File Upload (shown when type=file) -->
        <div class="form-group" id="file_upload_group" style="<?=$info['doc_type']=='link'?'display:none':''?>">
            <label class="label">Файл</label>
            <?php if($document && $document->isFile() && $document->getFileName()){ ?>
                <div class="flex items-center gap-2 mb-2 text-sm text-gray-600">
                    <i data-lucide="file" class="w-4 h-4 text-gray-400"></i>
                    <?=Format::htmlchars($document->getFileName())?> (<?=$document->getFileSizeFormatted()?>)
                    <span class="text-gray-400">— загрузите новый файл для замены</span>
                </div>
            <?php } ?>
            <input type="file" name="doc_file"
                   class="block w-full text-sm text-gray-500 file:mr-4 file:py-2.5 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-indigo-50 file:text-indigo-600 hover:file:bg-indigo-100 cursor-pointer">
            <?php if($errors['file']) { ?><span class="form-error"><?=$errors['file']?></span><?php } ?>
            <p class="text-xs text-gray-400 mt-1">Разрешённые типы: pdf, doc, docx, xls, xlsx, ppt, pptx, txt, jpg, png, gif</p>
        </div>

        <!-- External URL (shown when type=link) -->
        <div class="form-group" id="external_url_group" style="<?=$info['doc_type']=='file'?'display:none':''?>">
            <label class="label">Ссылка</label>
            <input class="input" type="text" name="external_url" value="<?=$info['external_url']?>"
                   placeholder="https://docs.google.com/document/d/...">
            <?php if($errors['external_url']) { ?><span class="form-error"><?=$errors['external_url']?></span><?php } ?>
            <p class="text-xs text-gray-400 mt-1">Поддерживаются: Google Docs, Google Sheets, Google Slides и любые другие URL</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
            <div class="form-group">
                <label class="label">Аудитория</label>
                <select class="select" name="audience">
                    <option value="all" <?=$info['audience']=='all'?'selected':''?>>Все (Менеджеры и Пользователи)</option>
                    <option value="staff" <?=$info['audience']=='staff'?'selected':''?>>Только Менеджеры</option>
                    <option value="client" <?=$info['audience']=='client'?'selected':''?>>Только Пользователи</option>
                </select>
                <?php if($errors['audience']) { ?><span class="form-error"><?=$errors['audience']?></span><?php } ?>
            </div>

            <div class="form-group">
                <label class="label">Отдел</label>
                <select class="select" name="dept_id">
                    <option value="0">Все отделы (Общие документы)</option>
                    <?php
                    $depts_q = db_query('SELECT dept_id,dept_name FROM '.DEPT_TABLE.' ORDER BY dept_name');
                    while (list($deptId,$deptName) = db_fetch_row($depts_q)){
                        $ck=($info['dept_id']==$deptId)?'selected':''; ?>
                        <option value="<?=$deptId?>" <?=$ck?>><?=Format::htmlchars($deptName)?></option>
                    <?php } ?>
                </select>
            </div>
        </div>

        <div class="form-group">
            <label class="label">Статус</label>
            <div class="flex items-center gap-4">
                <label class="flex items-center gap-1.5 text-sm text-gray-600">
                    <input type="radio" name="isenabled" value="1" class="radio" <?=$info['isenabled']?'checked':''?>> Включен
                </label>
                <label class="flex items-center gap-1.5 text-sm text-gray-600">
                    <input type="radio" name="isenabled" value="0" class="radio" <?=!$info['isenabled']?'checked':''?>> Отключен
                </label>
            </div>
        </div>

        <?php if($document && $document->getId()){ ?>
        <!-- Preview Section -->
        <div class="form-group">
            <label class="label">Превью</label>
            <div class="border border-gray-200 rounded-xl overflow-hidden">
            <?php if($document->isFile()){
                $ext = strtolower(pathinfo($document->getFileName(), PATHINFO_EXTENSION));
                if($ext=='pdf'){ ?>
                    <iframe src="doc_attachment.php?id=<?=$document->getId()?>&inline=1" class="w-full h-[500px] border-0"></iframe>
                <?php }elseif(in_array($ext, array('jpg','jpeg','png','gif'))){ ?>
                    <div class="flex justify-center p-6">
                        <img src="doc_attachment.php?id=<?=$document->getId()?>&inline=1" class="max-w-full max-h-[500px] rounded-lg" alt="">
                    </div>
                <?php }else{ ?>
                    <div class="flex flex-col items-center justify-center py-12">
                        <i data-lucide="file" class="w-12 h-12 text-gray-300 mb-3"></i>
                        <a href="doc_attachment.php?id=<?=$document->getId()?>" class="btn-primary btn-sm">
                            <i data-lucide="download" class="w-4 h-4"></i> Скачать <?=Format::htmlchars($document->getFileName())?>
                        </a>
                    </div>
                <?php }
            }elseif($document->isLink()){
                $embedUrl = $document->getEmbedUrl();
                if($document->isGoogleDoc()){ ?>
                    <iframe src="<?=Format::htmlchars($embedUrl)?>" class="w-full h-[500px] border-0" allowfullscreen></iframe>
                <?php }else{ ?>
                    <div class="flex flex-col items-center justify-center py-12">
                        <i data-lucide="external-link" class="w-12 h-12 text-gray-300 mb-3"></i>
                        <a href="<?=Format::htmlchars($document->getExternalUrl())?>" target="_blank" class="btn-primary btn-sm">
                            <i data-lucide="external-link" class="w-4 h-4"></i> Открыть ссылку
                        </a>
                    </div>
                <?php }
            } ?>
            </div>
        </div>
        <?php } ?>
    </div>
</div>

<div class="flex items-center gap-3 mt-6">
    <button class="btn-primary" type="submit" name="submit">
        <i data-lucide="save" class="w-4 h-4"></i> Сохранить
    </button>
    <button class="btn-secondary" type="reset">Очистить</button>
    <button class="btn-ghost" type="button" onclick='window.location.href="documents.php"'>Отмена</button>
</div>
</form>

<script type="text/javascript">
function toggleDocType(type) {
    if (type == 'file') {
        document.getElementById('file_upload_group').style.display = '';
        document.getElementById('external_url_group').style.display = 'none';
    } else {
        document.getElementById('file_upload_group').style.display = 'none';
        document.getElementById('external_url_group').style.display = '';
    }
}
</script>
