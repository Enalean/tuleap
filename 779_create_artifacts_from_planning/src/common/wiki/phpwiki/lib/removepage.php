<?php
rcs_id('$Id: removepage.php,v 1.26 2004/12/20 12:12:31 rurban Exp $');
require_once('lib/Template.php');

function RemovePage (&$request) {
    global $WikiTheme;
    
    $page = $request->getPage();
    $pagelink = WikiLink($page);

    if ($request->getArg('cancel')) {
        $request->redirect(WikiURL($page)); // noreturn
    }

    $current = $page->getCurrentRevision();

    if (!$current or !($version = $current->getVersion())) {
        $html = HTML(HTML::h2(_("Already deleted")),
                     HTML::p(_("Sorry, this page is not in the database.")));
    }
    elseif (!$request->isPost() || !$request->getArg('verify')) {

        $removeB = Button('submit:verify', _("Remove Page"), 'wikiadmin');
        $cancelB = Button('submit:cancel', _("Cancel"), 'button'); // use generic wiki button look

        $html = HTML(HTML::h2(fmt("You are about to remove '%s'!", $pagelink)),
                     HTML::form(array('method' => 'post',
                                      'action' => $request->getPostURL()),
                                HiddenInputs(array('currentversion' => $version,
                                                   'pagename' => $page->getName(),
                                                   'action' => 'remove')),
                                
                                HTML::div(array('class' => 'toolbar'),
                                          $removeB,
                                          $WikiTheme->getButtonSeparator(),
                                          $cancelB)),
                     HTML::hr()
                     );
        $sample = HTML::div(array('class' => 'transclusion'));
        // simple and fast preview expanding only newlines
        foreach (explode("\n", firstNWordsOfContent(100, $current->getPackedContent())) as $s) {
            $sample->pushContent($s, HTML::br());
        }
        $html->pushContent(HTML::div(array('class' => 'wikitext'), 
                                     $sample));
    }
    elseif ($request->getArg('currentversion') != $version) {
        $html = HTML(HTML::h2(_("Someone has edited the page!")),
                     HTML::p(fmt("Since you started the deletion process, someone has saved a new version of %s.  Please check to make sure you still want to permanently remove the page from the database.", $pagelink)));
    }
    else {
        // Codendi specific: remove the deleted wiki page from ProjectWantedPages       
        $projectPageName='ProjectWantedPages';
        $pagename = $page->getName();
        
        $dbi = $request->getDbh();
        require_once(PHPWIKI_DIR."/lib/loadsave.php");
        $pagehandle = $dbi->getPage($projectPageName);
        if ($pagehandle->exists()) {// don't replace default contents
            $current = $pagehandle->getCurrentRevision();
            $version = $current->getVersion();
            $text = $current->getPackedContent();
            $meta = $current->_data;
        }
        
        $text = str_replace("* [$pagename]", "", $text);
       
        $meta['summary'] =  $GLOBALS['Language']->getText('wiki_lib_wikipagewrap',
                                                      'page_added',
                                                      array($pagename));
        $meta['author'] = user_getname();
        $pagehandle->save($text, $version + 1, $meta);
        
        //Codendi specific: remove permissions for this page @codenditodo: may be transferable otherwhere.
        require_once('common/wiki/lib/WikiPage.class.php');
        $wiki_page = new WikiPage(GROUP_ID, $_REQUEST['pagename']);
        
        $wiki_page->resetPermissions();
        // Real delete.
        //$pagename = $page->getName();
        $dbi = $request->getDbh();
        $dbi->deletePage($pagename);
        $dbi->touch();
        $link = HTML::a(array('href' => 'javascript:history.go(-2)'), 
                        _("Back to the previous page."));
        $html = HTML(HTML::h2(fmt("Removed page '%s' successfully.", $pagename)),
        	     HTML::div($link), HTML::hr());
    }

    GeneratePage($html, _("Remove Page"));
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