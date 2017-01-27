<?php
require_once('pre.php');
require_once('common/wiki/lib/WikiAttachment.class.php');


$attch = new WikiAttachment();
$attch->setUri($_SERVER['REQUEST_URI']);

PHPWikiPluginRedirector::redirect();

if($attch->exist() && $attch->isActive()) {
    if($attch->isAutorized(user_getid())) {
        $attch->htmlDump();
    }
}
else {
    exit_error($Language->getText('global','error'),
               $Language->getText('wiki_attachment_upload', 'err_not_exist'));
}

// Local Variables:
// mode: php
// End:
?>