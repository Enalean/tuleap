<?php
/**
 * Copyright (c) Enalean, 2013-Present. All Rights Reserved.
 *
 * This file is a part of Tuleap.
 *
 * Tuleap is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Tuleap is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

use Tuleap\PHPWiki\WikiPage;

rcs_id('$Id: editpage.php,v 1.96 2005/05/06 17:54:22 rurban Exp $');

require_once('lib/Template.php');

// USE_HTMLAREA - Support for some WYSIWYG HTML Editor
// Not yet enabled, since we cannot convert HTML to Wiki Markup yet.
// (See HtmlParser.php for the ongoing efforts)
// We might use a HTML PageType, which is contra wiki, but some people might prefer HTML markup.
// TODO: Change from constant to user preference variable (checkbox setting),
//       when HtmlParser is finished.
if (!defined('USE_HTMLAREA')) {
    define('USE_HTMLAREA', false);
}
if (USE_HTMLAREA) {
    require_once('lib/htmlarea.php');
}

class PageEditor
{
    public function __construct(&$request)
    {
        $this->request = &$request;

        $this->user = $request->getUser();
        $this->page = $request->getPage();

        $this->current = $this->page->getCurrentRevision(false);

        // HACKish short circuit to browse on action=create
        if ($request->getArg('action') == 'create') {
            if (! $this->current->hasDefaultContents()) {
                $request->redirect(WikiURL($this->page->getName())); // noreturn
            }
        }

        $this->meta = array('author' => $this->user->getId(),
                            'author_id' => $this->user->getAuthenticatedId(),
                            'mtime' => time());

        $this->tokens = array();

        $version = $request->getArg('version');
        if ($version !== false) {
            $this->selected = $this->page->getRevision($version);
            $this->version = $version;
        } else {
            $this->version = $this->current->getVersion();
            $this->selected = $this->page->getRevision($this->version);
        }

        if ($this->_restoreState()) {
            $this->_initialEdit = false;
        } else {
            $this->_initializeState();
            $this->_initialEdit = true;

            // The edit request has specified some initial content from a template
            if (
                ($template = $request->getArg('template'))
                   and $request->_dbi->isWikiPage($template)
            ) {
                $page = $request->_dbi->getPage($template);
                $current = $page->getCurrentRevision();
                $this->_content = $current->getPackedContent();
            } elseif ($initial_content = $request->getArg('initial_content')) {
                $this->_content = $initial_content;
                $this->_redirect_to = $request->getArg('save_and_redirect_to');
            }
        }
        if (!headers_sent()) {
            header("Content-Type: text/html; charset=" . $GLOBALS['charset']);
        }
    }

    public function editPage()
    {
        global $WikiTheme;
        $saveFailed = false;
        $tokens = &$this->tokens;
        $tokens['PAGE_LOCKED_MESSAGE'] = '';
        $tokens['CONCURRENT_UPDATE_MESSAGE'] = '';
        $r = $this->request;

        if (
            isset($r->args['pref']['editWidth'])
            and ($r->getPref('editWidth') != $r->args['pref']['editWidth'])
        ) {
            $r->_prefs->set('editWidth', $r->args['pref']['editWidth']);
        }
        if (
            isset($r->args['pref']['editHeight'])
            and ($r->getPref('editHeight') != $r->args['pref']['editHeight'])
        ) {
            $r->_prefs->set('editHeight', $r->args['pref']['editHeight']);
        }

        if (! $this->canEdit()) {
            if ($this->isInitialEdit()) {
                return $this->viewSource();
            }
            $tokens['PAGE_LOCKED_MESSAGE'] = $this->getLockedMessage();
        } elseif ($r->getArg('save_and_redirect_to') != "") {
            if ($this->savePage()) {
                // noreturn
                $r->redirect(WikiURL($r->getArg('save_and_redirect_to')));
                return true;    // Page saved.
            }
            $saveFailed = true;
        } elseif ($this->editaction == 'save') {
            if ($this->savePage()) {
                return true;    // Page saved.
            } else {
                $saveFailed = true;
            }
        }

        if ($saveFailed and $this->isConcurrentUpdate()) {
            // Get the text of the original page, and the two conflicting edits
            // The diff3 class takes arrays as input.  So retrieve content as
            // an array, or convert it as necesary.
            $orig = $this->page->getRevision($this->_currentVersion);
            // FIXME: what if _currentVersion has be deleted?
            $orig_content = $orig->getContent();
            $this_content = explode("\n", $this->_content);
            $other_content = $this->current->getContent();
            include_once("lib/diff3.php");
            $diff = new Diff3($orig_content, $this_content, $other_content);
            $output = $diff->merged_output(_("Your version"), _("Other version"));
            // Set the content of the textarea to the merged diff
            // output, and update the version
            $this->_content = implode("\n", $output);
            $this->_currentVersion = $this->current->getVersion();
            $this->version = $this->_currentVersion;
            $unresolved = $diff->ConflictingBlocks;
            $tokens['CONCURRENT_UPDATE_MESSAGE']
                = $this->getConflictMessage($unresolved);
        } elseif ($saveFailed) {
            $tokens['CONCURRENT_UPDATE_MESSAGE'] =
                HTML(
                    HTML::h2(_("Some internal editing error")),
                    HTML::p(_("Your are probably trying to edit/create an invalid version of this page.")),
                    HTML::p(HTML::em(_("&version=-1 might help.")))
                );
        }

        if ($this->editaction == 'edit_convert') {
            $tokens['PREVIEW_CONTENT'] = $this->getConvertedPreview();
        }
        if ($this->editaction == 'preview') {
            $tokens['PREVIEW_CONTENT'] = $this->getPreview(); // FIXME: convert to _MESSAGE?
        }

        // FIXME: NOT_CURRENT_MESSAGE?
        $tokens = array_merge($tokens, $this->getFormElements());

        if (ENABLE_EDIT_TOOLBAR) {
            include_once("lib/EditToolbar.php");
            $toolbar = new EditToolbar();
            $tokens = array_merge($tokens, $toolbar->getTokens());
        }

        return $this->output('editpage', _("Edit: %s"));
    }

    public function output($template, $title_fs)
    {
        global $WikiTheme;
        $selected = &$this->selected;
        $current = &$this->current;

        if ($selected && $selected->getVersion() != $current->getVersion()) {
            $rev = $selected;
            $pagelink = WikiLink($selected);
        } else {
            $rev = $current;
            $pagelink = WikiLink($this->page);
        }

        $title = new FormattedText($title_fs, $pagelink);
        if (USE_HTMLAREA and $template == 'editpage') {
            $WikiTheme->addMoreHeaders(Edit_HtmlArea_Head());
            //$tokens['PAGE_SOURCE'] = Edit_HtmlArea_ConvertBefore($this->_content);
        }
        $template = Template($template, $this->tokens);
        GeneratePage($template, $title, $rev);
        return true;
    }


    public function viewSource()
    {
        assert($this->isInitialEdit());
        assert($this->selected);

        $this->tokens['PAGE_SOURCE'] = $this->_content;
        $this->tokens['HIDDEN_INPUTS'] = HiddenInputs($this->request->getArgs());
        return $this->output('viewsource', _("View Source: %s"));
    }

    public function updateLock()
    {
        if ((bool) $this->page->get('locked') == (bool) $this->locked) {
            return false;       // Not changed.
        }

        if (!$this->user->isAdmin()) {
            // FIXME: some sort of message
            return false;         // not allowed.
        }

        $this->page->set('locked', (bool) $this->locked);
        $this->tokens['LOCK_CHANGED_MSG']
            = $this->locked ? _("Page now locked.") : _("Page now unlocked.");

        return true;            // lock changed.
    }

    public function savePage()
    {
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
        $newrevision = $page->save(
            $this->_content,
            $this->version == -1
                                     ? -1
                                     : $this->_currentVersion + 1,
            // force new?
            $meta
        );
        if (!isa($newrevision, 'WikiDB_PageRevision')) {
            // Save failed.  (Concurrent updates).
            return false;
        } else {
            // Save succeded. We store cross references (if there are).
            $reference_manager = ReferenceManager::instance();
            $reference_manager->extractCrossRef($this->_content, $page->getName(), ReferenceManager::REFERENCE_NATURE_WIKIPAGE, GROUP_ID);

            // Save succeded. We raise an event.
            $new = $this->version + 1;
            $difflink = WikiURL($page->getName(), array('action' => 'diff'), true);
            $difflink .= "&versions%5b%5d=" . $this->version . "&versions%5b%5d=" . $new;
            $eM = EventManager::instance();
            $uM = UserManager::instance();
            $user = $uM->getCurrentUser();
            $wiki_page = new WikiPage(GROUP_ID, $page->getName());
            $eM->processEvent(
                "wiki_page_updated",
                array(
                    'group_id'         => GROUP_ID,
                    'wiki_page'        => $page->getName(),
                    'referenced'       => $wiki_page->isReferenced(),
                    'diff_link'        => $difflink,
                    'user'             => $user,
                    'version'          => $this->version
                )
            );
        }

        // New contents successfully saved...
        $this->updateLock();

        // Clean out archived versions of this page.
        include_once('lib/ArchiveCleaner.php');
        $cleaner = new ArchiveCleaner($GLOBALS['ExpireParams']);
        $cleaner->cleanPageRevisions($page);

        /* generate notification emails done in WikiDB::save to catch
         all direct calls (admin plugins) */

        // look at the errorstack
        $errors   = $GLOBALS['ErrorManager']->_postponed_errors;
        $warnings = $GLOBALS['ErrorManager']->getPostponedErrorsAsHTML();
        $GLOBALS['ErrorManager']->_postponed_errors = $errors;

        $dbi = $request->getDbh();
        $dbi->touch();

        global $WikiTheme;
        if (empty($warnings->_content) && ! $WikiTheme->getImageURL('signature')) {
            // Do redirect to browse page if no signature has
            // been defined.  In this case, the user will most
            // likely not see the rest of the HTML we generate
            // (below).
            $this->_redirectToBrowsePage();
        }

        // Force browse of current page version.
        $request->setArg('version', false);
        //$request->setArg('action', false);

        $pagename = WikiLink($page)->asString();

        $this->redirectAfterSavingPage($pagename);

        return true;
    }

    public function isConcurrentUpdate()
    {
        assert($this->current->getVersion() >= $this->_currentVersion);
        return $this->current->getVersion() != $this->_currentVersion;
    }

    public function canEdit()
    {
        return !$this->page->get('locked') || $this->user->isAdmin();
    }

    public function isInitialEdit()
    {
        return $this->_initialEdit;
    }

    public function isUnchanged()
    {
        $current = &$this->current;

        if ($this->meta['markup'] !=  $current->get('markup')) {
            return false;
        }

        return $this->_content == $current->getPackedContent();
    }

    public function getPreview()
    {
        include_once('lib/PageType.php');
        $this->_content = $this->getContent();
        return new TransformedText($this->page, $this->_content, $this->meta);
    }

    public function getConvertedPreview()
    {
        include_once('lib/PageType.php');
        $this->_content = $this->getContent();
        $this->meta['markup'] = 2.0;
        $this->_content = ConvertOldMarkup($this->_content);
        return new TransformedText($this->page, $this->_content, $this->meta);
    }

    // possibly convert HTMLAREA content back to Wiki markup
    public function getContent()
    {
        if (USE_HTMLAREA) {
            $xml_output = Edit_HtmlArea_ConvertAfter($this->_content);
            $this->_content = join("", $xml_output->_content);
            return $this->_content;
        } else {
            return $this->_content;
        }
    }

    public function getLockedMessage()
    {
        return
            HTML(
                HTML::h2(_("Page Locked")),
                HTML::p(_("This page has been locked by the administrator so your changes can not be saved.")),
                HTML::p(_("(Copy your changes to the clipboard. You can try editing a different page or save your text in a text editor.)")),
                HTML::p(_("Sorry for the inconvenience."))
            );
    }

    public function getConflictMessage($unresolved = false)
    {
        /*
         xgettext only knows about c/c++ line-continuation strings
         it does not know about php's dot operator.
         We want to translate this entire paragraph as one string, of course.
         */

        //$re_edit_link = Button('edit', _("Edit the new version"), $this->page);

        if ($unresolved) {
            $message =  HTML::p(fmt(
                "Some of the changes could not automatically be combined.  Please look for sections beginning with '%s', and ending with '%s'.  You will need to edit those sections by hand before you click Save.",
                "<<<<<<< " . _("Your version"),
                ">>>>>>> " . _("Other version")
            ));
        } else {
            $message = HTML::p(_("Please check it through before saving."));
        }

        /*$steps = HTML::ol(HTML::li(_("Copy your changes to the clipboard or to another temporary place (e.g. text editor).")),
          HTML::li(fmt("%s of the page. You should now see the most current version of the page. Your changes are no longer there.",
                       $re_edit_link)),
          HTML::li(_("Make changes to the file again. Paste your additions from the clipboard (or text editor).")),
          HTML::li(_("Save your updated changes.")));
        */
        return
            HTML(
                HTML::h2(_("Conflicting Edits!")),
                HTML::p(_("In the time since you started editing this page, another user has saved a new version of it.")),
                HTML::p(_("Your changes can not be saved as they are, since doing so would overwrite the other author's changes. So, your changes and those of the other author have been combined. The result is shown below.")),
                $message
            );
    }


    public function getTextArea()
    {
        $request = &$this->request;

        $readonly = ! $this->canEdit(); // || $this->isConcurrentUpdate();
        if (USE_HTMLAREA) {
            $html = $this->getPreview();
            $this->_wikicontent = $this->_content;
            $this->_content = $html->asXML();
        }

        $textarea = HTML::textarea(
            array('class' => 'wikiedit',
                                         'name' => 'edit[content]',
                                         'id'   => 'edit[content]',
                                         'rows' => $request->getPref('editHeight'),
                                         'cols' => $request->getPref('editWidth'),
                                         'readonly' => (bool) $readonly),
            $this->_content
        );
        if (USE_HTMLAREA) {
            return Edit_HtmlArea_Textarea($textarea, $this->_wikicontent, 'edit[content]');
        } else {
            return $textarea;
        }
    }

    public function getFormElements()
    {
        global $WikiTheme;
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
                                'id' => 'edit[summary]',
                                'name'  => 'edit[summary]',
                                'size'  => 50,
                                'maxlength' => 256,
                                'value' => $this->meta['summary']));
        $el['MINOR_EDIT_CB']
            = HTML::input(array('type' => 'checkbox',
                                'name'  => 'edit[minor_edit]',
                                'id' => 'edit[minor_edit]',
                                'checked' => (bool) $this->meta['is_minor_edit']));
        $el['OLD_MARKUP_CB']
            = HTML::input(array('type' => 'checkbox',
                                'name' => 'edit[markup]',
                                'value' => 'old',
                                'checked' => $this->meta['markup'] < 2.0,
                                'id' => 'useOldMarkup',
                                'onclick' => 'showOldMarkupRules(this.checked)'));
        $el['OLD_MARKUP_CONVERT'] = ($this->meta['markup'] < 2.0)
            ? Button('submit:edit[edit_convert]', _("Convert"), 'wikiaction') : '';
        $el['LOCKED_CB']
            = HTML::input(array('type' => 'checkbox',
                                'name' => 'edit[locked]',
                                'id'   => 'edit[locked]',
                                'disabled' => (bool) !$this->user->isadmin(),
                                'checked'  => (bool) $this->locked));

        $el['PREVIEW_B'] = Button(
            'submit:edit[preview]',
            _("Preview"),
            'wikiaction'
        );

        //if (!$this->isConcurrentUpdate() && $this->canEdit())
        $el['SAVE_B'] = Button('submit:edit[save]', _("Save"), 'wikiaction');

        $el['IS_CURRENT'] = $this->version == $this->current->getVersion();

        $el['WIDTH_PREF'] = HTML::input(array('type' => 'text',
                                    'size' => 3,
                                    'maxlength' => 4,
                                    'class' => "numeric",
                                    'name' => 'pref[editWidth]',
                                    'id'   => 'pref[editWidth]',
                                    'value' => $request->getPref('editWidth'),
                                    'onchange' => 'this.form.submit();'));
        $el['HEIGHT_PREF'] = HTML::input(array('type' => 'text',
                                     'size' => 3,
                                     'maxlength' => 4,
                                     'class' => "numeric",
                                     'name' => 'pref[editHeight]',
                                     'id'   => 'pref[editHeight]',
                                     'value' => $request->getPref('editHeight'),
                                     'onchange' => 'this.form.submit();'));
        $el['SEP'] = $WikiTheme->getButtonSeparator();
        $el['AUTHOR_MESSAGE'] = fmt("Author will be logged as %s.", HTML::em($this->user->getId()));

        return $el;
    }

    public function _redirectToBrowsePage()
    {
        $this->request->redirect(WikiURL($this->page, false, 'absolute_url'));
    }

    public function redirectAfterSavingPage($pagename)
    {
        $url     = WikiURL($this->page, false, 'absolute_url');
        $link    = '<a href="' . $url . '">' . $pagename . '</a>';
        $message = fmt("Saved: %s", $link)->asString();

        $GLOBALS['Response']->addFeedback('info', $message, CODENDI_PURIFIER_LIGHT);
        $GLOBALS['Response']->redirect($url);
    }

    public function _restoreState()
    {
        $request = &$this->request;

        $posted = $request->getArg('edit');
        $request->setArg('edit', false);

        if (
            !$posted || !$request->isPost()
            || $request->getArg('action') != 'edit'
        ) {
            return false;
        }

        if (!isset($posted['content']) || !is_string($posted['content'])) {
            return false;
        }
        $this->_content = preg_replace(
            '/[ \t\r]+\n/',
            "\n",
            rtrim($posted['content'])
        );
        $this->_content = $this->getContent();

        $this->_currentVersion = (int) $posted['current_version'];

        if ($this->_currentVersion < 0) {
            return false;
        }
        if ($this->_currentVersion > $this->current->getVersion()) {
            return false;       // FIXME: some kind of warning?
        }

        $is_old_markup = !empty($posted['markup']) && $posted['markup'] == 'old';
        $meta['markup'] = $is_old_markup ? false : 2.0;
        $meta['summary'] = trim(substr($posted['summary'], 0, 256));
        $meta['is_minor_edit'] = !empty($posted['minor_edit']);
        $meta['pagetype'] = !empty($posted['pagetype']) ? $posted['pagetype'] : false;

        $this->meta = array_merge($this->meta, $meta);
        $this->locked = !empty($posted['locked']);

        if (!empty($posted['preview'])) {
            $this->editaction = 'preview';
        } elseif (!empty($posted['save'])) {
            $this->editaction = 'save';
        } elseif (!empty($posted['edit_convert'])) {
            $this->editaction = 'edit_convert';
        } else {
            $this->editaction = 'edit';
        }

        return true;
    }

    public function _initializeState()
    {
        $request = &$this->request;
        $current = &$this->current;
        $selected = &$this->selected;
        $user = &$this->user;

        if (!$selected) {
            NoSuchRevision($request, $this->page, $this->version); // noreturn
        }

        $this->_currentVersion = $current->getVersion();
        $this->_content = $selected->getPackedContent();

        $this->locked = $this->page->get('locked');

        // If author same as previous author, default minor_edit to on.
        $age = $this->meta['mtime'] - $current->get('mtime');
        $this->meta['is_minor_edit'] = ( $age < MINOR_EDIT_TIMEOUT
                                         && $current->get('author') == $user->getId()
                                         );

        // Default for new pages is new-style markup.
        if ($selected->hasDefaultContents()) {
            $is_new_markup = true;
        } else {
            $is_new_markup = $selected->get('markup') >= 2.0;
        }

        $this->meta['markup'] = $is_new_markup ? 2.0 : false;
        $this->meta['pagetype'] = $selected->get('pagetype');
        if ($this->meta['pagetype'] == 'wikiblog') {
            $this->meta['summary'] = $selected->get('summary'); // keep blog title
        } else {
            $this->meta['summary'] = '';
        }
        $this->editaction = 'edit';
    }
}

class LoadFileConflictPageEditor extends PageEditor
{
    public function editPage($saveFailed = true)
    {
        $tokens = &$this->tokens;

        if (!$this->canEdit()) {
            if ($this->isInitialEdit()) {
                return $this->viewSource();
            }
            $tokens['PAGE_LOCKED_MESSAGE'] = $this->getLockedMessage();
        } elseif ($this->editaction == 'save') {
            if ($this->savePage()) {
                return true;    // Page saved.
            }
            $saveFailed = true;
        }

        if ($saveFailed || $this->isConcurrentUpdate()) {
            // Get the text of the original page, and the two conflicting edits
            // The diff class takes arrays as input.  So retrieve content as
            // an array, or convert it as necesary.
            $orig = $this->page->getRevision($this->_currentVersion);
            $this_content = explode("\n", $this->_content);
            $other_content = $this->current->getContent();
            include_once("lib/diff.php");
            $diff2 = new Diff($other_content, $this_content);
            $context_lines = max(
                4,
                count($other_content) + 1,
                count($this_content) + 1
            );
            $fmt = new BlockDiffFormatter($context_lines);

            $this->_content = $fmt->format($diff2);
            // FIXME: integrate this into class BlockDiffFormatter
            $this->_content = str_replace(
                ">>>>>>>\n<<<<<<<\n",
                "=======\n",
                $this->_content
            );
            $this->_content = str_replace(
                "<<<<<<<\n>>>>>>>\n",
                "=======\n",
                $this->_content
            );

            $this->_currentVersion = $this->current->getVersion();
            $this->version = $this->_currentVersion;
            $tokens['CONCURRENT_UPDATE_MESSAGE'] = $this->getConflictMessage();
        }

        if ($this->editaction == 'edit_convert') {
            $tokens['PREVIEW_CONTENT'] = $this->getConvertedPreview();
        }
        if ($this->editaction == 'preview') {
            $tokens['PREVIEW_CONTENT'] = $this->getPreview(); // FIXME: convert to _MESSAGE?
        }

        // FIXME: NOT_CURRENT_MESSAGE?
        $tokens = array_merge($tokens, $this->getFormElements());

        return $this->output('editpage', _("Merge and Edit: %s"));
        // FIXME: this doesn't display
    }

    public function output($template, $title_fs)
    {
        $selected = &$this->selected;
        $current = &$this->current;

        if ($selected && $selected->getVersion() != $current->getVersion()) {
            $rev = $selected;
            $pagelink = WikiLink($selected);
        } else {
            $rev = $current;
            $pagelink = WikiLink($this->page);
        }

        //$title = new FormattedText ($title_fs, $pagelink);
        $template = Template($template, $this->tokens);

        //GeneratePage($template, $title, $rev);
        PrintXML($template);
        return true;
    }

    public function getConflictMessage($unresolved = false)
    {
        $message = HTML(HTML::p(
            fmt(
                "Some of the changes could not automatically be combined.  Please look for sections beginning with '%s', and ending with '%s'.  You will need to edit those sections by hand before you click Save.",
                "<<<<<<<",
                "======="
            ),
            HTML::p(_("Please check it through before saving."))
        ));
        return $message;
    }
}

/**
 $Log: editpage.php,v $
 Revision 1.106  2005/11/21 22:03:08  rurban
 fix syntax error inside ENABLE_SPAMBLOCKLIST

 Revision 1.105  2005/11/21 20:53:59  rurban
 beautify request pref lines, no antispam if admin (netznetz request), user is a member anyway

 Revision 1.101  2005/10/30 16:12:28  rurban
 simplify viewsource tokens

 Revision 1.100  2005/10/30 14:20:42  rurban
 move Captcha specific vars and methods into a Captcha object
 randomize Captcha chars positions and angles (smoothly)

 Revision 1.99  2005/10/29 08:21:58  rurban
 ENABLE_SPAMBLOCKLIST:
   Check for links to blocked external tld domains in new edits, against
   multi.surbl.org and bl.spamcop.net.

 Revision 1.96  2005/05/06 17:54:22  rurban
 silence Preview warnings for PAGE_LOCKED_MESSAGE, CONCURRENT_UPDATE_MESSAGE (thanks to schorni)

 Revision 1.95  2005/04/25 20:17:14  rurban
 captcha feature by Benjamin Drieu. Patch #1110699

 Revision 1.94  2005/02/28 20:23:31  rurban
 fix error_stack

 Revision 1.93  2005/02/27 19:31:52  rurban
 hack: display errorstack without sideeffects (save and restore)

 Revision 1.92  2005/01/29 20:37:21  rurban
 no edit toolbar at all if ENABLE_EDITTOOLBAR = false

 Revision 1.91  2005/01/25 07:05:49  rurban
 extract toolbar code, support new tags to get rid of php inside templates

 Revision 1.90  2005/01/22 12:46:15  rurban
 fix oldmakrup button label
 update pref[edit*] settings

 Revision 1.89  2005/01/21 14:07:49  rurban
 reformatting

 Revision 1.88  2004/12/17 16:39:03  rurban
 minor reformatting

 Revision 1.87  2004/12/16 18:28:05  rurban
 keep wikiblog summary = page title

 Revision 1.86  2004/12/11 14:50:15  rurban
 new edit_convert button, to get rid of old markup eventually

 Revision 1.85  2004/12/06 19:49:56  rurban
 enable action=remove which is undoable and seeable in RecentChanges: ADODB ony for now.
 renamed delete_page to purge_page.
 enable action=edit&version=-1 to force creation of a new version.
 added BABYCART_PATH config
 fixed magiqc in adodb.inc.php
 and some more docs

 Revision 1.84  2004/12/04 12:58:26  rurban
 enable babycart Blog::SpamAssassin module on ENABLE_SPAMASSASSIN=true
 (currently only for php >= 4.3.0)

 Revision 1.83  2004/12/04 11:55:39  rurban
 First simple AntiSpam prevention:
   No more than 20 new http:// links allowed

 Revision 1.82  2004/11/30 22:21:56  rurban
 changed gif to optimized (pngout) png

 Revision 1.81  2004/11/29 17:57:27  rurban
 translated pulldown buttons

 Revision 1.80  2004/11/25 17:20:51  rurban
 and again a couple of more native db args: backlinks

 Revision 1.79  2004/11/21 11:59:20  rurban
 remove final \n to be ob_cache independent

 Revision 1.78  2004/11/16 17:57:45  rurban
 fix search&replace button
 use new addTagButton machinery
 new showPulldown for categories, TODO: in a seperate request

 Revision 1.77  2004/11/15 15:52:35  rurban
 improve js stability

 Revision 1.76  2004/11/15 15:37:34  rurban
 fix JS_SEARCHREPLACE
   don't use document.write for replace, otherwise self.opener is not defined.

 Revision 1.75  2004/09/16 08:00:52  rurban
 just some comments

 Revision 1.74  2004/07/03 07:36:28  rurban
 do not get unneccessary content

 Revision 1.73  2004/06/16 21:23:44  rurban
 fixed non-object fatal #215

 Revision 1.72  2004/06/14 11:31:37  rurban
 renamed global $Theme to $WikiTheme (gforge nameclash)
 inherit PageList default options from PageList
   default sortby=pagename
 use options in PageList_Selectable (limit, sortby, ...)
 added action revert, with button at action=diff
 added option regex to WikiAdminSearchReplace

 Revision 1.71  2004/06/03 18:06:29  rurban
 fix file locking issues (only needed on write)
 fixed immediate LANG and THEME in-session updates if not stored in prefs
 advanced editpage toolbars (search & replace broken)

 Revision 1.70  2004/06/02 20:47:47  rurban
 dont use the wikiaction class

 Revision 1.69  2004/06/02 10:17:56  rurban
 integrated search/replace into toolbar
 added save+preview buttons

 Revision 1.68  2004/06/01 15:28:00  rurban
 AdminUser only ADMIN_USER not member of Administrators
 some RateIt improvements by dfrankow
 edit_toolbar buttons

 Revision _1.6  2004/05/26 15:48:00  syilek
 fixed problem with creating page with slashes from one true page

 Revision _1.5  2004/05/25 16:51:53  syilek
 added ability to create a page from the category page and not have to edit it

 Revision 1.67  2004/05/27 17:49:06  rurban
 renamed DB_Session to DbSession (in CVS also)
 added WikiDB->getParam and WikiDB->getAuthParam method to get rid of globals
 remove leading slash in error message
 added force_unlock parameter to File_Passwd (no return on stale locks)
 fixed adodb session AffectedRows
 added FileFinder helpers to unify local filenames and DATA_PATH names
 editpage.php: new edit toolbar javascript on ENABLE_EDIT_TOOLBAR

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
