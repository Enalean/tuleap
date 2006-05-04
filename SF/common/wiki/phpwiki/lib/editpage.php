<?php
rcs_id('$Id: editpage.php 2691 2006-03-02 15:31:51Z guerin $');

require_once('lib/Template.php');

// Not yet enabled, since we cannot convert HTML to Wiki Markup yet.
// We might use a HTML PageType, which is contra wiki, but some people might prefer HTML markup.
// Todo: change from constant to user preference variable. (or checkbox setting)
if (!defined('USE_HTMLAREA')) define('USE_HTMLAREA',false);
if (USE_HTMLAREA) require_once('lib/htmlarea.php');

class PageEditor
{
    function PageEditor (&$request) {
        $this->request = &$request;

        $this->user = $request->getUser();
        $this->page = $request->getPage();

        $this->current = $this->page->getCurrentRevision();

        // HACKish short circuit to browse on action=create
        if ($request->getArg('action') == 'create') {
            if (! $this->current->hasDefaultContents()) 
                $request->redirect(WikiURL($this->page->getName())); // noreturn
        }
        
        
        $this->meta = array('author' => $this->user->getId(),
                            'author_id' => $this->user->getAuthenticatedId(),
                            'mtime' => time());
        
        $this->tokens = array();
        
        $version = $request->getArg('version');
        if ($version !== false) {
            $this->selected = $this->page->getRevision($version);
            $this->version = $version;
        }
        else {
            $this->selected = $this->current;
            $this->version = $this->current->getVersion();
        }

        if ($this->_restoreState()) {
            $this->_initialEdit = false;
        }
        else {
            $this->_initializeState();
            $this->_initialEdit = true;

            // The edit request has specified some initial content from a template 
            if (  ($template = $request->getArg('template')) and 
                  $request->_dbi->isWikiPage($template)) {
                $page = $request->_dbi->getPage($template);
                $current = $page->getCurrentRevision();
                $this->_content = $current->getPackedContent();
            } elseif ($initial_content = $request->getArg('initial_content')) {
                $this->_content = $initial_content;
            }
        }
        if (!headers_sent())
            header("Content-Type: text/html; charset=" . $GLOBALS['charset']);
    }

    function editPage () {
        $saveFailed = false;
        $tokens = &$this->tokens;

        if (! $this->canEdit()) {
            if ($this->isInitialEdit())
                return $this->viewSource();
            $tokens['PAGE_LOCKED_MESSAGE'] = $this->getLockedMessage();
        }
        elseif ($this->editaction == 'save') {
            if ($this->savePage())
                return true;    // Page saved.
            $saveFailed = true;
        }

        if ($saveFailed || $this->isConcurrentUpdate())
        {
            // Get the text of the original page, and the two conflicting edits
            // The diff3 class takes arrays as input.  So retrieve content as
            // an array, or convert it as necesary.
            $orig = $this->page->getRevision($this->_currentVersion);
            // FIXME: what if _currentVersion has be deleted?
            $orig_content = $orig->getContent();
            $this_content = explode("\n", $this->_content);
            $other_content = $this->current->getContent();
            include_once("lib/diff3.php");
            $diff = new diff3($orig_content, $this_content, $other_content);
            $output = $diff->merged_output(_("Your version"), _("Other version"));
            // Set the content of the textarea to the merged diff
            // output, and update the version
            $this->_content = implode ("\n", $output);
            $this->_currentVersion = $this->current->getVersion();
            $this->version = $this->_currentVersion;
            $unresolved = $diff->ConflictingBlocks;
            $tokens['CONCURRENT_UPDATE_MESSAGE'] = $this->getConflictMessage($unresolved);
        }

        if ($this->editaction == 'preview')
            $tokens['PREVIEW_CONTENT'] = $this->getPreview(); // FIXME: convert to _MESSAGE?

        // FIXME: NOT_CURRENT_MESSAGE?

        $tokens = array_merge($tokens, $this->getFormElements());

        if (defined('JS_SEARCHREPLACE') and JS_SEARCHREPLACE) {
            $tokens['JS_SEARCHREPLACE'] = 1;
            $GLOBALS['Theme']->addMoreHeaders(Javascript("
var wart=0, d, f, x='', replacewin, pretxt=new Array(), pretxt_anzahl=0;
var fag='<font face=\"arial,helvetica,sans-serif\" size=\"-1\">', fr='<font color=\"#cc0000\">', spn='<span class=\"grey\">';

function define_f() {
   f=document.getElementById('editpage');
   f.editarea=document.getElementById('edit[content]');
   if(f.rck.style) f.rck.style.color='#ececec';
}

function replace() {
   replacewin=window.open('','','toolbar=no,location=no,directories=no,status=no,menubar=no,scrollbars=no,resizable=yes,copyhistory=no,height=90,width=450');
   replacewin.window.document.write('<html><head><title>"._("Search & Replace")."</title><style type=\"text/css\"><'+'!'+'-- input.btt {font-family:Tahoma,Verdana,Geneva,sans-serif;font-size:10pt} --'+'></style></head><body bgcolor=\"#dddddd\" onload=\"if(document.forms[0].ein.focus) document.forms[0].ein.focus()\"><form><center><table><tr><td align=\"right\">'+fag+'"._("Search").":</font></td><td align=\"left\"><input type=\"text\" name=\"ein\" size=\"50\" maxlength=\"500\"></td></tr><tr><td align=\"right\">'+fag+' "._("Replace with").":</font></td><td align=\"left\"><input type=\"text\" name=\"aus\" size=\"50\" maxlength=\"500\"></td></tr><tr><td colspan=\"2\" align=\"center\"><input class=\"btt\" type=\"button\" value=\" "._("OK")." \" onclick=\"self.opener.do_replace()\">&nbsp;&nbsp;&nbsp;<input class=\"btt\" type=\"button\" value=\""._("Close")."\" onclick=\"self.close()\"></td></tr></table></center></form></body></html>');
   replacewin.window.document.close();
}

function do_replace() {
   var txt=pretxt[pretxt_anzahl]=f.editarea.value, ein=new RegExp(replacewin.document.forms[0].ein.value,'g'), aus=replacewin.document.forms[0].aus.value;
   if(ein==''||ein==null) {
      replacewin.window.document.forms[0].ein.focus();
      return;
   }
   var z_repl=txt.match(ein)? txt.match(ein).length : 0;
   txt=txt.replace(ein,aus);
   ein=ein.toString().substring(1,ein.toString().length-2);
   result(z_repl, 'Substring \"'+ein+'\" found '+z_repl+' times. Replace with \"'+aus+'\"?', txt, 'String \"'+ein+'\" not found.');
   replacewin.window.focus();
   replacewin.window.document.forms[0].ein.focus();
}
function result(zahl,frage,txt,alert_txt) {
   if(wart!=0&&wart.window) {
      wart.window.close();
      wart=0;
   }
   if(zahl>0) {
      if(window.confirm(frage)==true) {
         f.editarea.value=txt;
         pretxt_anzahl++;
         if(f.rck.style) f.rck.style.color='#000000';
         f.rck.value='"._("Undo")."';
      }
   } else alert(alert_txt);
}
function rueck() {
   if(pretxt_anzahl==0) return;
   else if(pretxt_anzahl>0) {
      f.editarea.value=pretxt[pretxt_anzahl-1];
      pretxt[pretxt_anzahl]=null;
      pretxt_anzahl--;
      if(pretxt_anzahl==0) {
         alert('Operation undone.');
         if(f.rck.style) f.rck.style.color='#ececec';
         f.rck.value='("._("Undo").")';
         if(f.rck.blur) f.rck.blur();
      }
   }
}
function speich() {
   pretxt[pretxt_anzahl]=f.editarea.value;
   pretxt_anzahl++;
   if(f.rck.style) f.rck.style.color='#000000';
   f.rck.value='"._("Undo")."';
}
"));
            $GLOBALS['Theme']->addMoreAttr('body'," onload='define_f()'");
        }

        return $this->output('editpage', _("Edit: %s"));
    }

    function output ($template, $title_fs) {
        global $Theme;
        $selected = &$this->selected;
        $current = &$this->current;

        if ($selected && $selected->getVersion() != $current->getVersion()) {
            $rev = $selected;
            $pagelink = WikiLink($selected);
        }
        else {
            $rev = $current;
            $pagelink = WikiLink($this->page);
        }


        $title = new FormattedText ($title_fs, $pagelink);
        if ($template == 'editpage' and USE_HTMLAREA) {
            $Theme->addMoreHeaders(Edit_HtmlArea_Head());
            //$tokens['PAGE_SOURCE'] = Edit_HtmlArea_ConvertBefore($this->_content);
        }
        $template = Template($template, $this->tokens);
        GeneratePage($template, $title, $rev);
        return true;
    }


    function viewSource () {
        assert($this->isInitialEdit());
        assert($this->selected);

        $this->tokens['PAGE_SOURCE'] = $this->_content;
        return $this->output('viewsource', _("View Source: %s"));
    }

    function updateLock() {
        if ((bool)$this->page->get('locked') == (bool)$this->locked)
            return false;       // Not changed.

        if (!$this->user->isAdmin()) {
            // FIXME: some sort of message
            return false;         // not allowed.
        }

        $this->page->set('locked', (bool)$this->locked);
        $this->tokens['LOCK_CHANGED_MSG']
            = $this->locked ? _("Page now locked.") : _("Page now unlocked.");

        return true;            // lock changed.
    }

    function savePage () {
        $request = &$this->request;

        if ($this->isUnchanged()) {
            // Allow admin lock/unlock even if
            // no text changes were made.
            if ($this->updateLock()) {
                $dbi = $request->getDbh();
                $dbi->touch();
            }
            // Save failed. No changes made.
            $this->_redirectToBrowsePage();
            // user will probably not see the rest of this...
            include_once('lib/display.php');
            // force browse of current version:
            $request->setArg('version', false);
            displayPage($request, 'nochanges');
            return true;
        }

        $page = &$this->page;

        // Include any meta-data from original page version which
        // has not been explicitly updated.
        // (Except don't propagate pgsrc_version --- moot for now,
        //  because at present it never gets into the db...)
        $meta = $this->selected->getMetaData();
        unset($meta['pgsrc_version']);
        $meta = array_merge($meta, $this->meta);
        
        // Save new revision
        $this->_content = $this->getContent();
        $newrevision = $page->save($this->_content, $this->_currentVersion + 1, $meta);
        if (!isa($newrevision, 'wikidb_pagerevision')) {
            // Save failed.  (Concurrent updates).
            return false;
        }
        
        // New contents successfully saved...
        $this->updateLock();

        // Clean out archived versions of this page.
        include_once('lib/ArchiveCleaner.php');
        $cleaner = new ArchiveCleaner($GLOBALS['ExpireParams']);
        $cleaner->cleanPageRevisions($page);

        /* generate notification emails done in WikiDB::save to catch all direct calls 
          (admin plugins) */

        $dbi = $request->getDbh();
        $warnings = $dbi->GenericWarnings();
        $dbi->touch();
        
        global $Theme;
        if (empty($warnings) && ! $Theme->getImageURL('signature')) {
            // Do redirect to browse page if no signature has
            // been defined.  In this case, the user will most
            // likely not see the rest of the HTML we generate
            // (below).
            $this->_redirectToBrowsePage();
        }

        // Force browse of current page version.
        $request->setArg('version', false);
        //$request->setArg('action', false);

        $template = Template('savepage', $this->tokens);
        $template->replace('CONTENT', $newrevision->getTransformedContent());
        if (!empty($warnings))
            $template->replace('WARNINGS', $warnings);

        $pagelink = WikiLink($page);

        GeneratePage($template, fmt("Saved: %s", $pagelink), $newrevision);
        return true;
    }

    function isConcurrentUpdate () {
        assert($this->current->getVersion() >= $this->_currentVersion);
        return $this->current->getVersion() != $this->_currentVersion;
    }

    function canEdit () {
        return !$this->page->get('locked') || $this->user->isAdmin();
    }

    function isInitialEdit () {
        return $this->_initialEdit;
    }

    function isUnchanged () {
        $current = &$this->current;

        if ($this->meta['markup'] !=  $current->get('markup'))
            return false;

        return $this->_content == $current->getPackedContent();
    }

    function getPreview () {
        include_once('lib/PageType.php');
        $this->_content = $this->getContent();
	return new TransformedText($this->page, $this->_content, $this->meta);
    }

    // possibly convert HTMLAREA content back to Wiki markup
    function getContent () {
        if (USE_HTMLAREA) {
            $xml_output = Edit_HtmlArea_ConvertAfter($this->_content);
            $this->_content = join("",$xml_output->_content);
            return $this->_content;
        } else {
            return $this->_content;
        }
    }

    function getLockedMessage () {
        return
            HTML(HTML::h2(_("Page Locked")),
                 HTML::p(_("This page has been locked by the administrator so your changes can not be saved.")),
                 HTML::p(_("(Copy your changes to the clipboard. You can try editing a different page or save your text in a text editor.)")),
                 HTML::p(_("Sorry for the inconvenience.")));
    }

    function getConflictMessage ($unresolved = false) {
        /*
         xgettext only knows about c/c++ line-continuation strings
         it does not know about php's dot operator.
         We want to translate this entire paragraph as one string, of course.
         */

        //$re_edit_link = Button('edit', _("Edit the new version"), $this->page);

        if ($unresolved)
            $message =  HTML::p(fmt("Some of the changes could not automatically be combined.  Please look for sections beginning with '%s', and ending with '%s'.  You will need to edit those sections by hand before you click Save.",
                                "<<<<<<< ". _("Your version"),
                                ">>>>>>> ". _("Other version")));
        else
            $message = HTML::p(_("Please check it through before saving."));



        /*$steps = HTML::ol(HTML::li(_("Copy your changes to the clipboard or to another temporary place (e.g. text editor).")),
          HTML::li(fmt("%s of the page. You should now see the most current version of the page. Your changes are no longer there.",
                       $re_edit_link)),
          HTML::li(_("Make changes to the file again. Paste your additions from the clipboard (or text editor).")),
          HTML::li(_("Save your updated changes.")));
        */
        return
            HTML(HTML::h2(_("Conflicting Edits!")),
                 HTML::p(_("In the time since you started editing this page, another user has saved a new version of it.")),
                 HTML::p(_("Your changes can not be saved as they are, since doing so would overwrite the other author's changes. So, your changes and those of the other author have been combined. The result is shown below.")),
                 $message);
    }


    function getTextArea () {
        $request = &$this->request;

        // wrap=virtual is not HTML4, but without it NS4 doesn't wrap
        // long lines
        $readonly = ! $this->canEdit(); // || $this->isConcurrentUpdate();
        if (USE_HTMLAREA) {
            $html = $this->getPreview();
            $this->_wikicontent = $this->_content;
            $this->_content = $html->asXML();
        }
        $textarea = HTML::textarea(array('class' => 'wikiedit',
                                         'name' => 'edit[content]',
                                         'id'   => 'edit[content]',
                                         'rows' => $request->getPref('editHeight'),
                                         'cols' => $request->getPref('editWidth'),
                                         'readonly' => (bool) $readonly,
                                         'wrap' => 'virtual'),
                                   $this->_content);
        if (USE_HTMLAREA)
            return Edit_HtmlArea_Textarea($textarea,$this->_wikicontent,'edit[content]');
        else
            return $textarea;
    }

    function getFormElements () {
        $request = &$this->request;
        $page = &$this->page;


        $h = array('action'   => 'edit',
                   'pagename' => $page->getName(),
                   'version'  => $this->version,
                   'edit[pagetype]' => $this->meta['pagetype'],
                   'edit[current_version]' => $this->_currentVersion);

        $el['HIDDEN_INPUTS'] = HiddenInputs($h);


        $el['EDIT_TEXTAREA'] = $this->getTextArea();

        $el['SUMMARY_INPUT']
            = HTML::input(array('type'  => 'text',
                                'class' => 'wikitext',
                                'name'  => 'edit[summary]',
                                'size'  => 50,
                                'maxlength' => 256,
                                'value' => $this->meta['summary']));
        $el['MINOR_EDIT_CB']
            = HTML::input(array('type' => 'checkbox',
                                'name'  => 'edit[minor_edit]',
                                'checked' => (bool) $this->meta['is_minor_edit']));
        $el['OLD_MARKUP_CB']
            = HTML::input(array('type' => 'checkbox',
                                'name' => 'edit[markup]',
                                'value' => 'old',
                                'checked' => $this->meta['markup'] < 2.0,
                                'id' => 'useOldMarkup',
                                'onclick' => 'showOldMarkupRules(this.checked)'));

        $el['LOCKED_CB']
            = HTML::input(array('type' => 'checkbox',
                                'name' => 'edit[locked]',
                                'disabled' => (bool) !$this->user->isadmin(),
                                'checked'  => (bool) $this->locked));

        $el['PREVIEW_B'] = Button('submit:edit[preview]', _("Preview"),
                                  'wikiaction');

        //if (!$this->isConcurrentUpdate() && $this->canEdit())
        $el['SAVE_B'] = Button('submit:edit[save]', _("Save"), 'wikiaction');

        $el['IS_CURRENT'] = $this->version == $this->current->getVersion();

        return $el;
    }

    function _redirectToBrowsePage() {
        $this->request->redirect(WikiURL($this->page, false, 'absolute_url'));
    }
    

    function _restoreState () {
        $request = &$this->request;

        $posted = $request->getArg('edit');
        $request->setArg('edit', false);

        if (!$posted || !$request->isPost()
            || $request->getArg('action') != 'edit')
            return false;

        if (!isset($posted['content']) || !is_string($posted['content']))
            return false;
        $this->_content = preg_replace('/[ \t\r]+\n/', "\n",
                                        rtrim($posted['content']));
        $this->_content = $this->getContent();

        $this->_currentVersion = (int) $posted['current_version'];

        if ($this->_currentVersion < 0)
            return false;
        if ($this->_currentVersion > $this->current->getVersion())
            return false;       // FIXME: some kind of warning?

        $is_old_markup = !empty($posted['markup']) && $posted['markup'] == 'old';
        $meta['markup'] = $is_old_markup ? false : 2.0;
        $meta['summary'] = trim(substr($posted['summary'], 0, 256));
        $meta['is_minor_edit'] = !empty($posted['minor_edit']);
        $meta['pagetype'] = !empty($posted['pagetype']) ? $posted['pagetype'] : false;
        $this->meta = array_merge($this->meta, $meta);
        $this->locked = !empty($posted['locked']);

        if (!empty($posted['preview']))
            $this->editaction = 'preview';
        elseif (!empty($posted['save']))
            $this->editaction = 'save';
        else
            $this->editaction = 'edit';

        return true;
    }

    function _initializeState () {
        $request = &$this->request;
        $current = &$this->current;
        $selected = &$this->selected;
        $user = &$this->user;

        if (!$selected)
            NoSuchRevision($request, $this->page, $this->version); // noreturn

        $this->_currentVersion = $current->getVersion();
        $this->_content = $selected->getPackedContent();

        $this->meta['summary'] = '';
        $this->locked = $this->page->get('locked');

        // If author same as previous author, default minor_edit to on.
        $age = $this->meta['mtime'] - $current->get('mtime');
        $this->meta['is_minor_edit'] = ( $age < MINOR_EDIT_TIMEOUT
                                         && $current->get('author') == $user->getId()
                                         );

        // Default for new pages is new-style markup.
        if ($selected->hasDefaultContents())
            $is_new_markup = true;
        else
            $is_new_markup = $selected->get('markup') >= 2.0;

        $this->meta['markup'] = $is_new_markup ? 2.0: false;
        $this->meta['pagetype'] = $selected->get('pagetype');
        $this->editaction = 'edit';
    }
}

class LoadFileConflictPageEditor
extends PageEditor
{
    function editPage ($saveFailed = true) {
        $tokens = &$this->tokens;

        if (!$this->canEdit()) {
            if ($this->isInitialEdit())
                return $this->viewSource();
            $tokens['PAGE_LOCKED_MESSAGE'] = $this->getLockedMessage();
        }
        elseif ($this->editaction == 'save') {
            if ($this->savePage())
                return true;    // Page saved.
            $saveFailed = true;
        }

        if ($saveFailed || $this->isConcurrentUpdate())
        {
            // Get the text of the original page, and the two conflicting edits
            // The diff class takes arrays as input.  So retrieve content as
            // an array, or convert it as necesary.
            $orig = $this->page->getRevision($this->_currentVersion);
            $this_content = explode("\n", $this->_content);
            $other_content = $this->current->getContent();
            include_once("lib/diff.php");
            $diff2 = new Diff($other_content, $this_content);
            $context_lines = max(4, count($other_content) + 1,
                                 count($this_content) + 1);
            $fmt = new BlockDiffFormatter($context_lines);

            $this->_content = $fmt->format($diff2);
            // FIXME: integrate this into class BlockDiffFormatter
            $this->_content = str_replace(">>>>>>>\n<<<<<<<\n", "=======\n",
                                          $this->_content);
            $this->_content = str_replace("<<<<<<<\n>>>>>>>\n", "=======\n",
                                          $this->_content);

            $this->_currentVersion = $this->current->getVersion();
            $this->version = $this->_currentVersion;
            $tokens['CONCURRENT_UPDATE_MESSAGE'] = $this->getConflictMessage();
        }

        if ($this->editaction == 'preview')
            $tokens['PREVIEW_CONTENT'] = $this->getPreview(); // FIXME: convert to _MESSAGE?

        // FIXME: NOT_CURRENT_MESSAGE?

        $tokens = array_merge($tokens, $this->getFormElements());

        return $this->output('editpage', _("Merge and Edit: %s"));
        // FIXME: this doesn't display
    }

    function output ($template, $title_fs) {
        $selected = &$this->selected;
        $current = &$this->current;

        if ($selected && $selected->getVersion() != $current->getVersion()) {
            $rev = $selected;
            $pagelink = WikiLink($selected);
        }
        else {
            $rev = $current;
            $pagelink = WikiLink($this->page);
        }

        $title = new FormattedText ($title_fs, $pagelink);
        $template = Template($template, $this->tokens);

        //GeneratePage($template, $title, $rev);
        PrintXML($template);
        return true;
    }
    function getConflictMessage () {
        $message = HTML(HTML::p(fmt("Some of the changes could not automatically be combined.  Please look for sections beginning with '%s', and ending with '%s'.  You will need to edit those sections by hand before you click Save.",
                                    "<<<<<<<",
                                    "======="),
                                HTML::p(_("Please check it through before saving."))));
        return $message;
    }
}

/**
 $Log$
 Revision 1.66  2004/04/29 23:25:12  rurban
 re-ordered locale init (as in 1.3.9)
 fixed loadfile with subpages, and merge/restore anyway
   (sf.net bug #844188)

 Revision 1.65  2004/04/18 01:11:52  rurban
 more numeric pagename fixes.
 fixed action=upload with merge conflict warnings.
 charset changed from constant to global (dynamic utf-8 switching)

 Revision 1.64  2004/04/06 19:48:56  rurban
 temp workaround for action=edit AddComment form

 Revision 1.63  2004/03/24 19:39:02  rurban
 php5 workaround code (plus some interim debugging code in XmlElement)
   php5 doesn't work yet with the current XmlElement class constructors,
   WikiUserNew does work better than php4.
 rewrote WikiUserNew user upgrading to ease php5 update
 fixed pref handling in WikiUserNew
 added Email Notification
 added simple Email verification
 removed emailVerify userpref subclass: just a email property
 changed pref binary storage layout: numarray => hash of non default values
 print optimize message only if really done.
 forced new cookie policy: delete pref cookies, use only WIKI_ID as plain string.
   prefs should be stored in db or homepage, besides the current session.

 Revision 1.62  2004/03/17 18:41:05  rurban
 initial_content and template support for CreatePage

 Revision 1.61  2004/03/12 20:59:17  rurban
 important cookie fix by Konstantin Zadorozhny
 new editpage feature: JS_SEARCHREPLACE

 Revision 1.60  2004/02/15 21:34:37  rurban
 PageList enhanced and improved.
 fixed new WikiAdmin... plugins
 editpage, Theme with exp. htmlarea framework
   (htmlarea yet committed, this is really questionable)
 WikiUser... code with better session handling for prefs
 enhanced UserPreferences (again)
 RecentChanges for show_deleted: how should pages be deleted then?

 Revision 1.59  2003/12/07 20:35:26  carstenklapp
 Bugfix: Concurrent updates broken since after 1.3.4 release: Fatal
 error: Call to undefined function: gettransformedcontent() in
 /home/groups/p/ph/phpwiki/htdocs/phpwiki2/lib/editpage.php on line
 205.

 Revision 1.58  2003/03/10 18:25:22  dairiki
 Bug/typo fix.  If you use the edit page to un/lock a page, it
 failed with: Fatal error: Call to a member function on a
 non-object in editpage.php on line 136

 Revision 1.57  2003/02/26 03:40:22  dairiki
 New action=create.  Essentially the same as action=edit, except that if the
 page already exists, it falls back to action=browse.

 This is for use in the "question mark" links for unknown wiki words
 to avoid problems and confusion when following links from stale pages.
 (If the "unknown page" has been created in the interim, the user probably
 wants to view the page before editing it.)

 Revision 1.56  2003/02/21 18:07:14  dairiki
 Minor, nitpicky, currently inconsequential changes.

 Revision 1.55  2003/02/21 04:10:58  dairiki
 Fixes for new cached markup.
 Some minor code cleanups.

 Revision 1.54  2003/02/16 19:47:16  dairiki
 Update WikiDB timestamp when editing or deleting pages.

 Revision 1.53  2003/02/15 23:20:27  dairiki
 Redirect back to browse current version of page upon save,
 even when no changes were made.

 Revision 1.52  2003/01/03 22:22:00  carstenklapp
 Minor adjustments to diff block markers ("<<<<<<<"). Source reformatting.

 Revision 1.51  2003/01/03 02:43:26  carstenklapp
 New class LoadFileConflictPageEditor, for merging / comparing a loaded
 pgsrc file with an existing page.

 */

// Local Variables:
// mode: php
// tab-width: 8
// c-basic-offset: 4
// c-hanging-comment-ender-p: nil
// indent-tabs-mode: nil
// End:
?>
