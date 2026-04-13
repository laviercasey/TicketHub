<?php
if(!defined('OSTAJAXINC') || !defined('INCLUDE_DIR')) die('!');

require_once(INCLUDE_DIR.'class.document.php');

class DocumentsAjaxAPI {

    function preview($params) {

        $id = isset($params['id']) ? intval($params['id']) : 0;
        if(!$id) return 'ID документа не указан';

        $doc = Document::lookup($id);
        if(!$doc || !$doc->getId()) return 'Документ не найден';

        $html = '<h4>' . Format::htmlchars($doc->getTitle()) . '</h4>';

        if($doc->getDescription()) {
            $html .= '<p class="text-muted">' . Format::htmlchars($doc->getDescription()) . '</p>';
        }

        $html .= '<p><strong>Тип:</strong> ' . $doc->getDocTypeLabel() .
                  ' | <strong>Аудитория:</strong> ' . $doc->getAudienceLabel() . '</p>';

        if($doc->isFile()) {
            $ext = strtolower(pathinfo($doc->getFileName(), PATHINFO_EXTENSION));

            if($ext == 'pdf') {
                $html .= '<iframe src="doc_attachment.php?id=' . $doc->getId() . '&inline=1" width="100%" height="450" style="border:1px solid #ddd;"></iframe>';
            } elseif(in_array($ext, array('jpg','jpeg','png','gif'))) {
                $html .= '<img src="doc_attachment.php?id=' . $doc->getId() . '&inline=1" class="img-responsive" style="max-height:450px; border:1px solid #ddd;">';
            } else {
                $html .= '<div class="text-center" style="padding:40px;">';
                $html .= '<i class="fa fa-file-o fa-5x text-muted"></i>';
                $html .= '<p style="margin-top:15px;"><strong>' . Format::htmlchars($doc->getFileName()) . '</strong>';
                $html .= ' (' . $doc->getFileSizeFormatted() . ')</p>';
                $html .= '<a href="doc_attachment.php?id=' . $doc->getId() . '" class="btn btn-primary">';
                $html .= '<i class="fa fa-download"></i> Скачать</a>';
                $html .= '</div>';
            }
        } elseif($doc->isLink()) {
            if($doc->isGoogleDoc()) {
                $embedUrl = $doc->getEmbedUrl();
                $html .= '<iframe src="' . Format::htmlchars($embedUrl) . '" width="100%" height="450" style="border:1px solid #ddd;" frameborder="0"></iframe>';
            } else {
                $html .= '<div class="text-center" style="padding:40px;">';
                $html .= '<i class="fa fa-external-link fa-5x text-muted"></i>';
                $html .= '<p style="margin-top:15px;">';
                $html .= '<a href="' . Format::htmlchars($doc->getExternalUrl()) . '" target="_blank" class="btn btn-primary">';
                $html .= '<i class="fa fa-external-link"></i> Открыть ссылку</a>';
                $html .= '</p></div>';
            }
        }

        return $html;
    }
}
?>
