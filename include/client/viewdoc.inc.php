<?php
if(!defined('OSTCLIENTINC') || !$doc || !$doc->getId()) die('Доступ запрещён');
?>

<div class="mb-6">
    <a href="docs.php" class="inline-flex items-center gap-1 text-sm text-gray-500 hover:text-indigo-600 transition-colors mb-3">
        <i data-lucide="arrow-left" class="w-4 h-4"></i> Назад к документам
    </a>
    <h1 class="text-2xl font-heading font-bold text-gray-900">
        <?=Format::htmlchars($doc->getTitle())?>
    </h1>
    <div class="flex items-center gap-3 mt-2 text-sm text-gray-500">
        <span class="flex items-center gap-1">
            <i data-lucide="calendar" class="w-3.5 h-3.5"></i> <?=Format::db_datetime($doc->getCreated())?>
        </span>
        <?php
        $deptName = 'Общий';
        if($doc->getDeptId()){
            $dq = db_query('SELECT dept_name FROM '.DEPT_TABLE.' WHERE dept_id='.db_input($doc->getDeptId()));
            if($dq && ($dr = db_fetch_array($dq))) $deptName = $dr['dept_name'] ?: 'Общий';
        }
        ?>
        <span class="flex items-center gap-1">
            <i data-lucide="building-2" class="w-3.5 h-3.5"></i> <?=Format::htmlchars($deptName)?>
        </span>
    </div>
</div>

<?php if($doc->getDescription()){ ?>
<div class="bg-indigo-50/50 border border-indigo-100 rounded-xl p-5 mb-6 text-sm text-gray-700 leading-relaxed">
    <?=nl2br(Format::htmlchars($doc->getDescription()))?>
</div>
<?php } ?>

<div class="card">
    <div class="card-header">
        <div class="flex items-center justify-between">
            <span class="font-semibold text-gray-900 text-sm">Содержимое документа</span>
            <?php if($doc->isFile()){ ?>
                <a href="doc_attachment.php?id=<?=$doc->getId()?>" class="btn-primary btn-sm">
                    <i data-lucide="download" class="w-3.5 h-3.5"></i> Скачать (<?=$doc->getFileSizeFormatted()?>)
                </a>
            <?php } elseif($doc->isLink() && !$doc->isGoogleDoc()){ ?>
                <a href="<?=Format::htmlchars($doc->getExternalUrl())?>" target="_blank" class="btn-primary btn-sm">
                    <i data-lucide="external-link" class="w-3.5 h-3.5"></i> Открыть ссылку
                </a>
            <?php } ?>
        </div>
    </div>
    <div class="p-0">
        <?php if($doc->isFile()){
            $ext = strtolower(pathinfo($doc->getFileName(), PATHINFO_EXTENSION));
            if($ext == 'pdf'){ ?>
                <iframe src="doc_attachment.php?id=<?=$doc->getId()?>&inline=1" class="w-full h-[600px] border-0"></iframe>
            <?php } elseif(in_array($ext, array('jpg','jpeg','png','gif'))){ ?>
                <div class="flex justify-center p-6">
                    <img src="doc_attachment.php?id=<?=$doc->getId()?>&inline=1" class="max-w-full max-h-[600px] rounded-lg" alt="<?=Format::htmlchars($doc->getTitle())?>">
                </div>
            <?php } else { ?>
                <div class="flex flex-col items-center justify-center py-16">
                    <i data-lucide="file" class="w-16 h-16 text-gray-300 mb-4"></i>
                    <h4 class="text-lg font-semibold text-gray-700 mb-1"><?=Format::htmlchars($doc->getFileName())?></h4>
                    <p class="text-sm text-gray-400 mb-6"><?=$doc->getFileSizeFormatted()?></p>
                    <a href="doc_attachment.php?id=<?=$doc->getId()?>" class="btn-primary btn-lg">
                        <i data-lucide="download" class="w-4 h-4"></i> Скачать файл
                    </a>
                </div>
            <?php }
        } elseif($doc->isLink()){
            if($doc->isGoogleDoc()){
                $embedUrl = $doc->getEmbedUrl();
            ?>
                <iframe src="<?=Format::htmlchars($embedUrl)?>" class="w-full h-[600px] border-0" allowfullscreen></iframe>
            <?php } else { ?>
                <div class="flex flex-col items-center justify-center py-16">
                    <i data-lucide="external-link" class="w-16 h-16 text-gray-300 mb-4"></i>
                    <h4 class="text-lg font-semibold text-gray-700 mb-1">Внешняя ссылка</h4>
                    <p class="text-sm text-gray-400 mb-6"><?=Format::htmlchars(Format::truncate($doc->getExternalUrl(), 80))?></p>
                    <a href="<?=Format::htmlchars($doc->getExternalUrl())?>" target="_blank" class="btn-primary btn-lg">
                        <i data-lucide="external-link" class="w-4 h-4"></i> Открыть
                    </a>
                </div>
            <?php }
        } ?>
    </div>
</div>
