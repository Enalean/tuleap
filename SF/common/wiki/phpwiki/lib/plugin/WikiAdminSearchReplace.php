<?php // -*-php-*-
rcs_id('$Id$');
/*
 Copyright 2004 $ThePhpWikiProgrammingTeam

 This file is part of PhpWiki.

 PhpWiki is free software; you can redistribute it and/or modify
 it under the terms of the GNU General Public License as published by
 the Free Software Foundation; either version 2 of the License, or
 (at your option) any later version.

 PhpWiki is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with PhpWiki; if not, write to the Free Software
 Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

/**
 * Usage:   <?plugin WikiAdminSearchReplace ?> or called via WikiAdminSelect
 * Author:  Reini Urban <rurban@x-ray.at>
 *
 * KNOWN ISSUES:
 * Currently we must be Admin.
 * Future versions will support PagePermissions.
 * requires PHP 4.2 so far.
 */
require_once('lib/PageList.php');
require_once('lib/plugin/WikiAdminSelect.php');

class WikiPlugin_WikiAdminSearchReplace
extends WikiPlugin_WikiAdminSelect
{
    function getName() {
        return _("WikiAdminSearchReplace");
    }

    function getDescription() {
        return _("Search and replace text in selected wiki pages.");
    }

    function getVersion() {
        return preg_replace("/[Revision: $]/", '',
                            "\$Revision$");
    }

    function getDefaultArguments() {
        return array(
                     /* Pages to exclude */
                     'exclude'  => '.',
                     /* Columns to include in listing */
                     'info'     => 'some',
                     /* How to sort */
                     'sortby'   => 'pagename',
                     'limit'    => 0,
                     );
    }

    function replaceHelper(&$dbi, $pagename, $from, $to, $caseexact = true) {
        $page = $dbi->getPage($pagename);
        if ($page->exists()) {// don't replace default contents
            $current = $page->getCurrentRevision();
            $version = $current->getVersion();
            $text = $current->getPackedContent();
            if ($caseexact) {
                $newtext = str_replace($from, $to, $text);
            } else {
                //not all PHP have this enabled. use a workaround
                if (function_exists('str_ireplace'))
                    $newtext = str_ireplace($from, $to, $text);
                else { // see eof
                    $newtext = stri_replace($from, $to, $text);
                }
            }
            if ($text != $newtext) {
                $meta = $current->_data;
                $meta['summary'] = sprintf(_("WikiAdminSearchReplace %s by %s"),$from,$to);
                return $page->save($newtext, $version + 1, $meta);
            }
        }
        return false;
    }

    function searchReplacePages(&$dbi, &$request, $pages, $from, $to) {
        if (empty($from)) return HTML::p(HTML::strong(fmt("Error: Empty search string.")));
        $ul = HTML::ul();
        $count = 0;
        $post_args = $request->getArg('admin_replace');
        $caseexact = !empty($post_args['caseexact']);
        foreach ($pages as $pagename) {
            if (($result = $this->replaceHelper(&$dbi,$pagename,$from,$to,$caseexact))) {
                $ul->pushContent(HTML::li(fmt("Replaced '%s' with '%s' in page '%s'.", $from, $to, WikiLink($pagename))));
                $count++;
            } else {
                $ul->pushContent(HTML::li(fmt("Search string '%s' not found in page '%s'.", $from, $to, WikiLink($pagename))));
            }
        }
        if ($count) {
            $dbi->touch();
            return HTML($ul,
                        HTML::p(fmt("%s pages changed.",$count)));
        } else {
            return HTML($ul,
                        HTML::p(fmt("No pages changed.")));
        }
    }
    
    function run($dbi, $argstr, &$request, $basepage) {
        if ($request->getArg('action') != 'browse')
            return $this->disabled("(action != 'browse')");
        
        $args = $this->getArgs($argstr, $request);
        $this->_args = $args;
        if (!empty($args['exclude']))
            $exclude = explodePageList($args['exclude']);
        else
            $exclude = false;


        $p = $request->getArg('p');
        $post_args = $request->getArg('admin_replace');
        $next_action = 'select';
        $pages = array();
        if ($p && !$request->isPost())
            $pages = $p;
        if ($p && $request->isPost() &&
            empty($post_args['cancel'])) {

            // FIXME: check individual PagePermissions
            if (!$request->_user->isAdmin()) {
                $request->_notAuthorized(WIKIAUTH_ADMIN);
                $this->disabled("! user->isAdmin");
            }

            if ($post_args['action'] == 'verify' and !empty($post_args['from'])) {
                // Real action
                return $this->searchReplacePages($dbi, $request, array_keys($p), $post_args['from'], $post_args['to']);
            }
            if ($post_args['action'] == 'select') {
                if (!empty($post_args['from']))
                    $next_action = 'verify';
                foreach ($p as $name => $c) {
                    $pages[$name] = 1;
                }
            }
        }
        if ($next_action == 'select' and empty($pages)) {
            // List all pages to select from.
            $pages = $this->collectPages($pages, $dbi, $args['sortby'], $args['limit']);
        }

        if ($next_action == 'verify') {
            $args['info'] = "checkbox,pagename,hi_content";
        }
        $pagelist = new PageList_Selectable($args['info'], $exclude,
                                            array('types' => array(
                                                  'hi_content' // with highlighted search for SearchReplace
                                                   => new _PageList_Column_content('rev:hi_content', _("Content")))));

        $pagelist->addPageList($pages);

        $header = HTML::p();
        if (empty($post_args['from']))
            $header->pushContent(
              HTML::p(HTML::em(_("Warning: The search string cannot be empty!"))));
        if ($next_action == 'verify') {
            $button_label = _("Yes");
            $header->pushContent(
              HTML::p(HTML::strong(
                                   _("Are you sure you want to permanently search & replace text in the selected files?"))));
            $this->replaceForm(&$header, $post_args);
        }
        else {
            $button_label = _("Search & Replace");
            $this->replaceForm(&$header, $post_args);
            $header->pushContent(HTML::p(_("Select the pages to search:")));
        }


        $buttons = HTML::p(Button('submit:admin_replace[rename]', $button_label, 'wikiadmin'),
                           Button('submit:admin_replace[cancel]', _("Cancel"), 'button'));

        return HTML::form(array('action' => $request->getPostURL(),
                                'method' => 'post'),
                          $header,
                          $pagelist->getContent(),
                          HiddenInputs($request->getArgs(),
                                        false,
                                        array('admin_replace')),
                          HiddenInputs(array('admin_replace[action]' => $next_action,
                                             'require_authority_for_post' => WIKIAUTH_ADMIN)),
                          $buttons);
    }

    function replaceForm(&$header, $post_args) {
        $header->pushContent(_("Replace: "));
        $header->pushContent(HTML::input(array('name' => 'admin_replace[from]',
                                               'value' => $post_args['from'])));
        $header->pushContent(' '._("by").': ');
        $header->pushContent(HTML::input(array('name' => 'admin_replace[to]',
                                               'value' => $post_args['to'])));
        $header->pushContent(' '._("(no regex) Case-exact: "));
        $checkbox = HTML::input(array('type' => 'checkbox',
                                      'name' => 'admin_replace[caseexact]',
                                      'value' => 1));
        if (!empty($post_args['caseexact']))
            $checkbox->setAttr('checked','checked');
        $header->pushContent($checkbox);
        $header->pushContent(HTML::br());
        return $header;
    }
}

function stri_replace($find,$replace,$string) {
    if (!is_array($find)) $find = array($find);
    if (!is_array($replace))  {
        if (!is_array($find)) 
            $replace = array($replace);
        else {
            // this will duplicate the string into an array the size of $find
            $c = count($find);
            $rString = $replace;
            unset($replace);
            for ($i = 0; $i < $c; $i++) {
                $replace[$i] = $rString;
            }
        }
    }
    foreach ($find as $fKey => $fItem) {
        $between = explode(strtolower($fItem),strtolower($string));
        $pos = 0;
        foreach($between as $bKey => $bItem) {
            $between[$bKey] = substr($string,$pos,strlen($bItem));
            $pos += strlen($bItem) + strlen($fItem);
        }
        $string = implode($replace[$fKey],$between);
    }
    return $string;
}

// $Log$
// Revision 1.1  2005/04/12 13:33:34  guerin
// First commit for wiki integration.
// Added Manuel's code as of revision 13 on Partners.
// Very little modification at the moment:
// - removed use of DOCUMENT_ROOT and SF_LOCAL_INC_PREFIX
// - simplified require syntax
// - removed ST-specific code (for test phase)
//
// Revision 1.9  2004/04/07 23:13:19  rurban
// fixed pear/File_Passwd for Windows
// fixed FilePassUser sessions (filehandle revive) and password update
//
// Revision 1.8  2004/03/17 20:23:44  rurban
// fixed p[] pagehash passing from WikiAdminSelect, fixed problem removing pages with [] in the pagename
//
// Revision 1.7  2004/03/12 13:31:43  rurban
// enforce PagePermissions, errormsg if not Admin
//
// Revision 1.6  2004/02/24 15:20:07  rurban
// fixed minor warnings: unchecked args, POST => Get urls for sortby e.g.
//
// Revision 1.5  2004/02/17 12:11:36  rurban
// added missing 4th basepage arg at plugin->run() to almost all plugins. This caused no harm so far, because it was silently dropped on normal usage. However on plugin internal ->run invocations it failed. (InterWikiSearch, IncludeSiteMap, ...)
//
// Revision 1.4  2004/02/15 21:34:37  rurban
// PageList enhanced and improved.
// fixed new WikiAdmin... plugins
// editpage, Theme with exp. htmlarea framework
//   (htmlarea yet committed, this is really questionable)
// WikiUser... code with better session handling for prefs
// enhanced UserPreferences (again)
// RecentChanges for show_deleted: how should pages be deleted then?
//
// Revision 1.3  2004/02/12 17:05:39  rurban
// WikiAdminRename:
//   added "Change pagename in all linked pages also"
// PageList:
//   added javascript toggle for Select
// WikiAdminSearchReplace:
//   fixed another typo
//
// Revision 1.2  2004/02/12 11:47:51  rurban
// typo
//
// Revision 1.1  2004/02/12 11:25:53  rurban
// new WikiAdminSearchReplace plugin (requires currently Admin)
// removed dead comments from WikiDB
//
//

// Local Variables:
// mode: php
// tab-width: 8
// c-basic-offset: 4
// c-hanging-comment-ender-p: nil
// indent-tabs-mode: nil
// End:
?>
