<?php
// -*-php-*-
rcs_id('$Id: WikiAdminRename.php,v 1.26 2005/04/01 16:06:41 rurban Exp $');
/*
 Copyright 2004,2005 $ThePhpWikiProgrammingTeam

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
 * Usage:   <?plugin WikiAdminRename ?> or called via WikiAdminSelect
 * @author:  Reini Urban <rurban@x-ray.at>
 *
 * KNOWN ISSUES:
 *   Requires PHP 4.2.
 */
require_once('lib/PageList.php');
require_once('lib/plugin/WikiAdminSelect.php');

class WikiPlugin_WikiAdminRename extends WikiPlugin_WikiAdminSelect
{
    public function getName()
    {
        return _("WikiAdminRename");
    }

    public function getDescription()
    {
        return _("Rename selected pages");
    }

    public function getVersion()
    {
        return preg_replace(
            "/[Revision: $]/",
            '',
            "\$Revision: 1.26 $"
        );
    }

    public function getDefaultArguments()
    {
        return array_merge(
            PageList::supportedArgs(),
            array(
                     's'     => false,
                     /* Columns to include in listing */
                     'info'     => 'pagename,mtime',
                     'updatelinks' => 0
            )
        );
    }

    public function renameHelper($name, $from, $to, $options = false)
    {
        if ($options['regex']) {
            $pattern = '/' . str_replace('/', '\/', $from) . '/' . ($options['icase'] ? 'i' : '');
            return preg_replace($pattern, $to, $name);
        } elseif ($options['icase']) {
            return str_ireplace($from, $to, $name);
        } else {
            return str_replace($from, $to, $name);
        }
    }

    public function renamePages(&$dbi, &$request, $pages, $from, $to, $updatelinks = false)
    {
        $ul = HTML::ul();
        $count = 0;
        $post_args = $request->getArg('admin_rename');
        $options = array('regex' => @$post_args['regex'],
                         'icase' => @$post_args['icase']);
        foreach ($pages as $name) {
            if (($newname = $this->renameHelper($name, $from, $to, $options))
                 and $newname != $name) {
                if ($dbi->isWikiPage($newname)) {
                    $ul->pushContent(HTML::li(fmt(
                        "Page %s already exists. Ignored.",
                        WikiLink($newname)
                    )));
                } elseif (! mayAccessPage('change', $name)) {
                    $ul->pushContent(HTML::li(fmt(
                        "Access denied to change page '%s'.",
                        WikiLink($name)
                    )));
                } elseif ($dbi->renamePage($name, $newname, $updatelinks)) {
                    /* not yet implemented for all backends */
                    $ul->pushContent(HTML::li(fmt(
                        "Renamed page '%s' to '%s'.",
                        $name,
                        WikiLink($newname)
                    )));
                    $count++;
                } else {
                    $ul->pushContent(HTML::li(fmt(
                        "Couldn't rename page '%s' to '%s'.",
                        $name,
                        $newname
                    )));
                }
            } else {
                $ul->pushContent(HTML::li(fmt(
                    "Couldn't rename page '%s' to '%s'.",
                    $name,
                    $newname
                )));
            }
        }
        if ($count) {
            $dbi->touch();
            return HTML($ul, HTML::p(fmt(
                "%s pages have been permanently renamed.",
                $count
            )));
        } else {
            return HTML($ul, HTML::p(fmt("No pages renamed.")));
        }
    }

    public function run($dbi, $argstr, &$request, $basepage)
    {
        if ($request->getArg('action') != 'browse') {
            if ($request->getArg('action') != _("PhpWikiAdministration/Rename")) {
                return $this->disabled("(action != 'browse')");
            }
        }

        $args = $this->getArgs($argstr, $request);
        $this->_args = $args;
        $this->preSelectS($args, $request);

        $p = $request->getArg('p');
        if (!$p) {
            $p = $this->_list;
        }
        $post_args = $request->getArg('admin_rename');
        $next_action = 'select';
        $pages = array();
        if ($p && !$request->isPost()) {
            $pages = $p;
        }
        if ($p && $request->isPost() &&
            !empty($post_args['rename']) && empty($post_args['cancel'])) {
            // without individual PagePermissions:
            if (!ENABLE_PAGEPERM and !$request->_user->isAdmin()) {
                $request->_notAuthorized(WIKIAUTH_ADMIN);
                $this->disabled("! user->isAdmin");
            }
            // DONE: error message if not allowed.
            if ($post_args['action'] == 'verify') {
                // Real action
                return $this->renamePages(
                    $dbi,
                    $request,
                    array_keys($p),
                    $post_args['from'],
                    $post_args['to'],
                    !empty($post_args['updatelinks'])
                );
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
            $pages = $this->collectPages($pages, $dbi, $args['sortby'], $args['limit'], $args['exclude']);
        }
        if ($next_action == 'verify') {
            $args['info'] = "checkbox,pagename,renamed_pagename";
        }
        $pagelist = new PageList_Selectable(
            $args['info'],
            $args['exclude'],
            array('types' =>
                   array('renamed_pagename'
                         => new _PageList_Column_renamed_pagename('rename', _("Rename to")),
            ))
        );
        $pagelist->addPageList($pages);

        $header = HTML::p();
        if ($next_action == 'verify') {
            $button_label = _("Yes");
            $header->pushContent(
                HTML::p(HTML::strong(
                    _("Are you sure you want to permanently rename the selected files?")
                ))
            );
            $header = $this->renameForm($header, $post_args);
        } else {
            $button_label = _("Rename selected pages");
            $header->pushContent(HTML::p(_("Select the pages to rename:")));
            if (!$post_args and count($pages) == 1) {
                list($post_args['from'],) = array_keys($pages);
                $post_args['to'] = $post_args['from'];
            }
            $header = $this->renameForm($header, $post_args);
        }

        $buttons = HTML::p(
            Button('submit:admin_rename[rename]', $button_label, 'wikiadmin'),
            Button('submit:admin_rename[cancel]', _("Cancel"), 'button')
        );

        return HTML::form(
            array('action' => $request->getPostURL(),
                                'method' => 'post'),
            $header,
            $pagelist->getContent(),
            HiddenInputs(
                $request->getArgs(),
                false,
                array('admin_rename')
            ),
            HiddenInputs(array('admin_rename[action]' => $next_action)),
            ENABLE_PAGEPERM
                          ? ''
                          : HiddenInputs(array('require_authority_for_post' => WIKIAUTH_ADMIN)),
            $buttons
        );
    }

    public function checkBox(&$post_args, $name, $msg)
    {
        $checkbox = HTML::input(array('type' => 'checkbox',
                                      'name' => 'admin_rename[' . $name . ']',
                                      'value' => 1));
        if (!empty($post_args[$name])) {
            $checkbox->setAttr('checked', 'checked');
        }
        return HTML::div($checkbox, ' ', HTML::span($msg));
    }

    public function renameForm(&$header, $post_args)
    {
        $header->pushContent(_("Rename") . " " . _("from") . ': ');
        $header->pushContent(HTML::input(array('name' => 'admin_rename[from]',
                                               'value' => $post_args['from'])));
        $header->pushContent(' ' . _("to") . ': ');
        $header->pushContent(HTML::input(array('name' => 'admin_rename[to]',
                                               'value' => $post_args['to'])));
        $header->pushContent($this->checkBox($post_args, 'regex', _("Regex?")));
        $header->pushContent($this->checkBox($post_args, 'icase', _("Case insensitive?")));
        $header->pushContent(HTML::br());
        $header->pushContent($this->checkBox(
            $post_args,
            'updatelinks',
            _("Change pagename in all linked pages also?")
        ));
        $header->pushContent(HTML::p());
        return $header;
    }
}

// TODO: grey out unchangeble pages, even in the initial list also?
// TODO: autoselect by matching name javascript in admin_rename[from]
// TODO: update rename[] fields when case-sensitive and regex is changed

// moved from lib/PageList.php
class _PageList_Column_renamed_pagename extends _PageList_Column
{
    public function _getValue($page_handle, &$revision_handle)
    {
        global $request;
        $post_args = $request->getArg('admin_rename');
        $options = array('regex' => @$post_args['regex'],
                         'icase' => @$post_args['icase']);
        $value = WikiPlugin_WikiAdminRename::renameHelper(
            $page_handle->getName(),
            $post_args['from'],
            $post_args['to'],
            $options
        );
        $div = HTML::div(" => ", HTML::input(array('type' => 'text',
                                                  'name' => 'rename[]',
                                                  'value' => $value)));
        $new_page = $request->getPage($value);
        if ($new_page->exists()) {
            $div->setAttr('class', 'error');
            $div->setAttr('title', _("This page already exists"));
        }
        return $div;
    }
}

// $Log: WikiAdminRename.php,v $
// Revision 1.26  2005/04/01 16:06:41  rurban
// do not trim spaces
//
// Revision 1.25  2005/04/01 15:22:20  rurban
// Implement icase and regex options.
// Change checkbox case message from "Case-Sensitive" to "Case-Insensitive"
//
// Revision 1.24  2005/04/01 15:03:01  rurban
// Optimize rename UI with one selected pagename
//
// Revision 1.23  2005/02/12 17:24:24  rurban
// locale update: missing . : fixed. unified strings
// proper linebreaks
//
// Revision 1.22  2004/11/23 15:17:20  rurban
// better support for case_exact search (not caseexact for consistency),
// plugin args simplification:
//   handle and explode exclude and pages argument in WikiPlugin::getArgs
//     and exclude in advance (at the sql level if possible)
//   handle sortby and limit from request override in WikiPlugin::getArgs
// ListSubpages: renamed pages to maxpages
//
// Revision 1.21  2004/11/01 10:43:59  rurban
// seperate PassUser methods into seperate dir (memory usage)
// fix WikiUser (old) overlarge data session
// remove wikidb arg from various page class methods, use global ->_dbi instead
// ...
//
// Revision 1.20  2004/06/16 10:38:59  rurban
// Disallow refernces in calls if the declaration is a reference
// ("allow_call_time_pass_reference clean").
//   PhpWiki is now allow_call_time_pass_reference = Off clean,
//   but several external libraries may not.
//   In detail these libs look to be affected (not tested):
//   * Pear_DB odbc
//   * adodb oracle
//
// Revision 1.19  2004/06/14 11:31:39  rurban
// renamed global $Theme to $WikiTheme (gforge nameclash)
// inherit PageList default options from PageList
//   default sortby=pagename
// use options in PageList_Selectable (limit, sortby, ...)
// added action revert, with button at action=diff
// added option regex to WikiAdminSearchReplace
//
// Revision 1.18  2004/06/13 15:33:20  rurban
// new support for arguments owner, author, creator in most relevant
// PageList plugins. in WikiAdmin* via preSelectS()
//
// Revision 1.17  2004/06/08 10:05:12  rurban
// simplified admin action shortcuts
//
// Revision 1.16  2004/06/07 18:57:31  rurban
// fix rename: Change pagename in all linked pages
//
// Revision 1.15  2004/06/04 20:32:54  rurban
// Several locale related improvements suggested by Pierrick Meignen
// LDAP fix by John Cole
// reanable admin check without ENABLE_PAGEPERM in the admin plugins
//
// Revision 1.14  2004/06/03 22:24:48  rurban
// reenable admin check on !ENABLE_PAGEPERM, honor s=Wildcard arg, fix warning after Remove
//
// Revision 1.13  2004/06/03 12:59:41  rurban
// simplify translation
// NS4 wrap=virtual only
//
// Revision 1.12  2004/06/01 15:28:01  rurban
// AdminUser only ADMIN_USER not member of Administrators
// some RateIt improvements by dfrankow
// edit_toolbar buttons
//
// Revision 1.11  2004/05/24 17:34:53  rurban
// use ACLs
//
// Revision 1.10  2004/04/06 20:00:11  rurban
// Cleanup of special PageList column types
// Added support of plugin and theme specific Pagelist Types
// Added support for theme specific UserPreferences
// Added session support for ip-based throttling
//   sql table schema change: ALTER TABLE session ADD sess_ip CHAR(15);
// Enhanced postgres schema
// Added DB_Session_dba support
//
// Revision 1.9  2004/03/12 13:31:43  rurban
// enforce PagePermissions, errormsg if not Admin
//
// Revision 1.8  2004/03/01 13:48:46  rurban
// rename fix
// p[] consistency fix
//
// Revision 1.7  2004/02/22 23:20:33  rurban
// fixed DumpHtmlToDir,
// enhanced sortby handling in PageList
//   new button_heading th style (enabled),
// added sortby and limit support to the db backends and plugins
//   for paging support (<<prev, next>> links on long lists)
//
// Revision 1.6  2004/02/17 12:11:36  rurban
// added missing 4th basepage arg at plugin->run() to almost all plugins. This caused no harm so far, because it was silently dropped on normal usage. However on plugin internal ->run invocations it failed. (InterWikiSearch, IncludeSiteMap, ...)
//
// Revision 1.5  2004/02/15 21:34:37  rurban
// PageList enhanced and improved.
// fixed new WikiAdmin... plugins
// editpage, Theme with exp. htmlarea framework
//   (htmlarea yet committed, this is really questionable)
// WikiUser... code with better session handling for prefs
// enhanced UserPreferences (again)
// RecentChanges for show_deleted: how should pages be deleted then?
//
// Revision 1.4  2004/02/12 17:05:39  rurban
// WikiAdminRename:
//   added "Change pagename in all linked pages also"
// PageList:
//   added javascript toggle for Select
// WikiAdminSearchReplace:
//   fixed another typo
//
// Revision 1.3  2004/02/12 13:05:50  rurban
// Rename functional for PearDB backend
// some other minor changes
// SiteMap comes with a not yet functional feature request: includepages (tbd)
//
// Revision 1.2  2004/02/12 11:45:11  rurban
// only WikiDB method missing
//
// Revision 1.1  2004/02/11 20:00:16  rurban
// WikiAdmin... series overhaul. Rename misses the db backend methods yet. Chmod + Chwon still missing.
// Local Variables:
// mode: php
// tab-width: 8
// c-basic-offset: 4
// c-hanging-comment-ender-p: nil
// indent-tabs-mode: nil
// End:
