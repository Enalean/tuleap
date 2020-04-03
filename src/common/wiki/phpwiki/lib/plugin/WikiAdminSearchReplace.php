<?php
// -*-php-*-
rcs_id('$Id: WikiAdminSearchReplace.php,v 1.19 2004/11/26 18:39:02 rurban Exp $');
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
 *   Requires PHP 4.2 so far.
 */
require_once('lib/PageList.php');
require_once('lib/plugin/WikiAdminSelect.php');

class WikiPlugin_WikiAdminSearchReplace extends WikiPlugin_WikiAdminSelect
{
    public function getName()
    {
        return _("WikiAdminSearchReplace");
    }

    public function getDescription()
    {
        return _("Search and replace text in selected wiki pages.");
    }

    public function getVersion()
    {
        return preg_replace(
            "/[Revision: $]/",
            '',
            "\$Revision: 1.19 $"
        );
    }

    public function getDefaultArguments()
    {
        return array_merge(
            PageList::supportedArgs(),
            array(
                   's'     => false,
                   /* Columns to include in listing */
                   'info'     => 'some',
            )
        );
    }

    public function replaceHelper(&$dbi, $pagename, $from, $to, $case_exact = true, $regex = false)
    {
        $page = $dbi->getPage($pagename);
        if ($page->exists()) {// don't replace default contents
            $current = $page->getCurrentRevision();
            $version = $current->getVersion();
            $text = $current->getPackedContent();
            if ($regex) {
                $newtext = preg_replace('/' . str_replace('/', '\/', $from) . '/' . ($case_exact ? '' : 'i'), $to, $text);
            } else {
                if ($case_exact) {
                    $newtext = str_replace($from, $to, $text);
                } else {
                    //not all PHP have this enabled. use a workaround
                    $newtext = str_ireplace($from, $to, $text);
                }
            }
            if ($text != $newtext) {
                $meta = $current->_data;
                $meta['summary'] = sprintf(_("WikiAdminSearchReplace %s by %s"), $from, $to);
                return $page->save($newtext, $version + 1, $meta);
            }
        }
        return false;
    }

    public function searchReplacePages(&$dbi, &$request, $pages, $from, $to)
    {
        if (empty($from)) {
            return HTML::p(HTML::strong(fmt("Error: Empty search string.")));
        }
        $ul = HTML::ul();
        $count = 0;
        $post_args = $request->getArg('admin_replace');
        $case_exact = !empty($post_args['case_exact']);
        $regex = !empty($post_args['regex']);
        foreach ($pages as $pagename) {
            if (!mayAccessPage('edit', $pagename)) {
                $ul->pushContent(HTML::li(fmt("Access denied to change page '%s'.", $pagename)));
            } elseif (($result = $this->replaceHelper($dbi, $pagename, $from, $to, $case_exact, $regex))) {
                $ul->pushContent(HTML::li(fmt("Replaced '%s' with '%s' in page '%s'.", $from, $to, WikiLink($pagename))));
                $count++;
            } else {
                $ul->pushContent(HTML::li(fmt(
                    "Search string '%s' not found in content of page '%s'.",
                    $from,
                    WikiLink($pagename)
                )));
            }
        }
        if ($count) {
            $dbi->touch();
            return HTML(
                $ul,
                HTML::p(fmt("%s pages changed.", $count))
            );
        } else {
            return HTML(
                $ul,
                HTML::p(fmt("No pages changed."))
            );
        }
    }

    public function run($dbi, $argstr, &$request, $basepage)
    {
        // no action=replace support yet
        if ($request->getArg('action') != 'browse') {
            return $this->disabled("(action != 'browse')");
        }

        $args = $this->getArgs($argstr, $request);
        $this->_args = $args;

        //TODO: support p from <!plugin-list !>
        $this->preSelectS($args, $request);

        $p = $request->getArg('p');
        if (!$p) {
            $p = $this->_list;
        }
        $post_args = $request->getArg('admin_replace');
        $next_action = 'select';
        $pages = array();
        if ($p && !$request->isPost()) {
            $pages = $p;
        }
        if (
            $p && $request->isPost() &&
            empty($post_args['cancel'])
        ) {
            // without individual PagePermissions:
            if (!ENABLE_PAGEPERM and !$request->_user->isAdmin()) {
                $request->_notAuthorized(WIKIAUTH_ADMIN);
                $this->disabled("! user->isAdmin");
            }

            if ($post_args['action'] == 'verify' and !empty($post_args['from'])) {
                // Real action
                return $this->searchReplacePages($dbi, $request, array_keys($p), $post_args['from'], $post_args['to']);
            }
            if ($post_args['action'] == 'select') {
                if (!empty($post_args['from'])) {
                    $next_action = 'verify';
                }
                foreach ($p as $name => $c) {
                    $pages[$name] = 1;
                }
            }
        }
        if ($next_action == 'select' and empty($pages)) {
            // List all pages to select from.
            //TODO: check for permissions and list only the allowed
            $pages = $this->collectPages($pages, $dbi, $args['sortby'], $args['limit'], $args['exclude']);
        }

        if ($next_action == 'verify') {
            $args['info'] = "checkbox,pagename,hi_content";
        }
        $pagelist = new PageList_Selectable(
            $args['info'],
            $args['exclude'],
            array_merge(
                $args,
                array('types' => array
                                                   (
                                                    'hi_content' // with highlighted search for SearchReplace
                => new _PageList_Column_content('rev:hi_content', _("Content"))))
            )
        );

        $pagelist->addPageList($pages);

        $header = HTML::p();
        if (empty($post_args['from'])) {
            $header->pushContent(
                HTML::p(HTML::em(_("Warning: The search string cannot be empty!")))
            );
        }
        if ($next_action == 'verify') {
            $button_label = _("Yes");
            $header->pushContent(
                HTML::p(HTML::strong(
                    _("Are you sure you want to permanently search & replace text in the selected files?")
                ))
            );
            $this->replaceForm($header, $post_args);
        } else {
            $button_label = _("Search & Replace");
            $this->replaceForm($header, $post_args);
            $header->pushContent(HTML::p(_("Select the pages to search:")));
        }

        $buttons = HTML::p(
            Button('submit:admin_replace[rename]', $button_label, 'wikiadmin'),
            Button('submit:admin_replace[cancel]', _("Cancel"), 'button')
        );

        return HTML::form(
            array('action' => $request->getPostURL(),
                                'method' => 'post'),
            $header,
            $pagelist->getContent(),
            HiddenInputs(
                $request->getArgs(),
                false,
                array('admin_replace')
            ),
            HiddenInputs(array('admin_replace[action]' => $next_action)),
            ENABLE_PAGEPERM
                          ? ''
                          : HiddenInputs(array('require_authority_for_post' => WIKIAUTH_ADMIN)),
            $buttons
        );
    }

    public function replaceForm(&$header, $post_args)
    {
        $header->pushContent(
            HTML::div(
                array('class' => 'hint'),
                _("Replace all occurences of the given string in the content of all pages.")
            ),
            HTML::br()
        );
        $header->pushContent(_("Replace: "));
        $header->pushContent(HTML::input(array('name' => 'admin_replace[from]',
                                               'value' => $post_args['from'])));
        $header->pushContent(' ' . _("by") . ': ');
        $header->pushContent(HTML::input(array('name' => 'admin_replace[to]',
                                               'value' => $post_args['to'])));
        $checkbox = HTML::input(array('type' => 'checkbox',
                                      'name' => 'admin_replace[case_exact]',
                                      'value' => 1));
        if (!empty($post_args['case_exact'])) {
            $checkbox->setAttr('checked', 'checked');
        }
        $header->pushContent(HTML::br(), $checkbox, " ", _("case-exact"));
        $checkbox_re = HTML::input(array('type' => 'checkbox',
                                         'name' => 'admin_replace[regex]',
                                         //'disabled' => 'disabled',
                                         'value' => 1));
        if (!empty($post_args['regex'])) {
            $checkbox_re->setAttr('checked', 'checked');
        }
        $header->pushContent(HTML::br(), HTML::span(//array('style'=>'color: #aaa'),
            $checkbox_re,
            " ",
            _("regex")
        ));
        $header->pushContent(HTML::br());
        return $header;
    }
}

// $Log: WikiAdminSearchReplace.php,v $
// Revision 1.19  2004/11/26 18:39:02  rurban
// new regex search parser and SQL backends (90% complete, glob and pcre backends missing)
//
// Revision 1.18  2004/11/23 15:17:20  rurban
// better support for case_exact search (not caseexact for consistency),
// plugin args simplification:
//   handle and explode exclude and pages argument in WikiPlugin::getArgs
//     and exclude in advance (at the sql level if possible)
//   handle sortby and limit from request override in WikiPlugin::getArgs
// ListSubpages: renamed pages to maxpages
//
// Revision 1.17  2004/09/17 14:24:06  rurban
// support exclude=<!plugin-list !>, p not yet
//
// Revision 1.16  2004/06/16 10:38:59  rurban
// Disallow refernces in calls if the declaration is a reference
// ("allow_call_time_pass_reference clean").
//   PhpWiki is now allow_call_time_pass_reference = Off clean,
//   but several external libraries may not.
//   In detail these libs look to be affected (not tested):
//   * Pear_DB odbc
//   * adodb oracle
//
// Revision 1.15  2004/06/14 11:31:39  rurban
// renamed global $Theme to $WikiTheme (gforge nameclash)
// inherit PageList default options from PageList
//   default sortby=pagename
// use options in PageList_Selectable (limit, sortby, ...)
// added action revert, with button at action=diff
// added option regex to WikiAdminSearchReplace
//
// Revision 1.14  2004/06/13 15:33:20  rurban
// new support for arguments owner, author, creator in most relevant
// PageList plugins. in WikiAdmin* via preSelectS()
//
// Revision 1.13  2004/06/13 14:30:26  rurban
// security fix: check permissions in SearchReplace
//
// Revision 1.12  2004/06/08 10:05:12  rurban
// simplified admin action shortcuts
//
// Revision 1.11  2004/06/04 20:32:54  rurban
// Several locale related improvements suggested by Pierrick Meignen
// LDAP fix by John Cole
// reanable admin check without ENABLE_PAGEPERM in the admin plugins
//
// Revision 1.10  2004/06/03 22:24:48  rurban
// reenable admin check on !ENABLE_PAGEPERM, honor s=Wildcard arg, fix warning after Remove
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
// Local Variables:
// mode: php
// tab-width: 8
// c-basic-offset: 4
// c-hanging-comment-ender-p: nil
// indent-tabs-mode: nil
// End:
