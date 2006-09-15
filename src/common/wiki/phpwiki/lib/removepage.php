<?php
rcs_id('$Id$');
require_once('lib/Template.php');
require_once(PHPWIKI_DIR.'/../lib/WikiPage.class'); // CodeX specific

function RemovePage (&$request) {
    global $Theme;

    $page = $request->getPage();
    $pagelink = WikiLink($page);

    if ($request->getArg('cancel')) {
        $request->redirect(WikiURL($page)); // noreturn
    }

    $current = $page->getCurrentRevision();
    $version = $current->getVersion();

    if (!$request->isPost() || !$request->getArg('verify')) {

        // FIXME: button should be class wikiadmin
        // Use the macosx button
        $removeB = Button('submit:verify', _("Remove Page"), 'wikiadmin');
        $cancelB = Button('submit:cancel', _("Cancel"), 'button'); // use generic wiki button look

        $html = HTML(HTML::h2(fmt("You are about to remove '%s' permanently!", $pagelink)),
                     HTML::form(array('method' => 'post',
                                      'action' => $request->getPostURL()),
                                HiddenInputs(array('currentversion' => $version,
                                                   'pagename' => $page->getName(),
                                                   'action' => 'remove')),
                                
                                HTML::div(array('class' => 'toolbar'),
                                          $removeB,
                                          $Theme->getButtonSeparator(),
                                          $cancelB)));
    }
    elseif ($request->getArg('currentversion') != $version) {
        $html = HTML(HTML::h2(_("Someone has edited the page!")),
                     HTML::p(fmt("Since you started the deletion process, someone has saved a new version of %s.  Please check to make sure you still want to permanently remove the page from the database.", $pagelink)));
    }
    else {
        // CodeX specific: remove permissions for this page
        $wiki_page = new WikiPage($_REQUEST['group_id'],$_REQUEST['pagename']);
        $wiki_page->resetPermissions() ;
        // Real delete.
        $pagename = $page->getName();
        $dbi = $request->getDbh();
        $dbi->deletePage($pagename);
        $dbi->touch();
        $html = HTML(HTML::h2(fmt("Removed page '%s' successfully.", $pagename)));
    }

    GeneratePage($html, _("Remove page"));
}


// For emacs users
// Local Variables:
// mode: php
// tab-width: 8
// c-basic-offset: 4
// c-hanging-comment-ender-p: nil
// indent-tabs-mode: nil
// End:
?>
