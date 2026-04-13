<?php
require('client.inc.php');
require_once(INCLUDE_DIR.'class.document.php');
require_once(INCLUDE_DIR.'class.dept.php');

if(!defined('KB_DOCUMENTS_TABLE'))
    define('KB_DOCUMENTS_TABLE', TABLE_PREFIX.'kb_documents');

$errors=array();
$msg='';

if(isset($_GET['id']) && is_numeric($_GET['id'])) {
    $doc = Document::lookup(intval($_GET['id']));
    if($doc && $doc->getId() && $doc->isEnabled() && in_array($doc->getAudience(), array('client','all'))) {
        require(CLIENTINC_DIR.'header.inc.php');
        require(CLIENTINC_DIR.'viewdoc.inc.php');
        require(CLIENTINC_DIR.'footer.inc.php');
        exit;
    } else {
        $errors['err'] = 'Документ не найден';
    }
}

require(CLIENTINC_DIR.'header.inc.php');
require(CLIENTINC_DIR.'docs.inc.php');
require(CLIENTINC_DIR.'footer.inc.php');
?>
