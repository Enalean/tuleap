<?php
// -*-php-*-
rcs_id('$Id: WikiAdminRemove.php,v 1.30 2004/11/23 15:17:19 rurban Exp $');
/*
 Copyright 2002,2004 $ThePhpWikiProgrammingTeam

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
 * Usage:   <?plugin WikiAdminRemove?>
 * Author:  Reini Urban <rurban@x-ray.at>
 *
 * KNOWN ISSUES:
 * Currently we must be Admin.
 * Future versions will support PagePermissions.
 * requires PHP 4.2 so far.
 */
// maybe display more attributes with this class...
require_once('lib/PageList.php');
require_once('lib/plugin/WikiAdminSelect.php');

class WikiPlugin_WikiAdminRemove extends WikiPlugin_WikiAdminSelect
{
    public function getName()
    {
        return _("WikiAdminRemove");
    }

    public function getDescription()
    {
        return _("Permanently remove all selected pages.");
    }

    public function getVersion()
    {
        return preg_replace(
            "/[Revision: $]/",
            '',
            "\$Revision: 1.30 $"
        );
    }

    public function getDefaultArguments()
    {
        return array_merge(
            PageList::supportedArgs(),
            array(
                   's'     => false,
                     /*
                      * Show only pages which have been 'deleted' this
                      * long (in days).  (negative or non-numeric
                      * means show all pages, even non-deleted ones.)
                      *
                      * FIXME: could use a better name.
                      */
                     'min_age' => 0,

                     /*
                      * Automatically check the checkboxes for files
                      * which have been 'deleted' this long (in days).
                      *
                      * FIXME: could use a better name.
                      */
                     'max_age' => 31,
                     /* Columns to include in listing */
                     'info'     => 'most',
            )
        );
    }

    public function collectPages(&$list, &$dbi, $sortby, $limit = 0)
    {
        extract($this->_args);

        $now = time();

        $allPages = $dbi->getAllPages('include_empty', $sortby, $limit);
        while ($pagehandle = $allPages->next()) {
            $pagename = $pagehandle->getName();
            $current = $pagehandle->getCurrentRevision();
            if ($current->getVersion() < 1) {
                continue;       // No versions in database
            }

            $empty = $current->hasDefaultContents();
            if ($empty) {
                $age = ($now - $current->get('mtime')) / (24 * 3600.0);
                $checked = $age >= $max_age;
            } else {
                $age = 0;
                $checked = false;
            }

            if ($age >= $min_age) {
                if (empty($list[$pagename])) {
                    $list[$pagename] = $checked;
                }
            }
        }
        return $list;
    }

    public function removePages(&$request, $pages)
    {
        $ul = HTML::ul();
        $dbi = $request->getDbh();
        $count = 0;
        foreach ($pages as $name) {
            $name = str_replace(array('%5B','%5D'), array('[',']'), $name);
            if (mayAccessPage('remove', $name)) {
                $dbi->deletePage($name);
                $ul->pushContent(HTML::li(fmt("Removed page '%s' successfully.", $name)));
                $count++;
            } else {
                $ul->pushContent(HTML::li(fmt("Didn't removed page '%s'. Access denied.", $name)));
            }
        }
        if ($count) {
            $dbi->touch();
        }
        return HTML(
            $ul,
            HTML::p(fmt("%d pages have been permanently removed.", $count))
        );
    }

    public function run($dbi, $argstr, &$request, $basepage)
    {
        if ($request->getArg('action') != 'browse') {
            if ($request->getArg('action') != _("PhpWikiAdministration/Remove")) {
                return $this->disabled("(action != 'browse')");
            }
        }

        $args = $this->getArgs($argstr, $request);
        if (!is_numeric($args['min_age'])) {
            $args['min_age'] = -1;
        }
        $this->_args = $args;
        /*if (!empty($args['exclude']))
            $exclude = explodePageList($args['exclude']);
        else
        $exclude = false;*/
        $this->preSelectS($args, $request);

        $p = $request->getArg('p');
        if (!$p) {
            $p = $this->_list;
        }
        $post_args = $request->getArg('admin_remove');

        $next_action = 'select';
        $pages = array();
        if (
            $p && $request->isPost() &&
            !empty($post_args['remove']) && empty($post_args['cancel'])
        ) {
            // check individual PagePermissions
            if (!ENABLE_PAGEPERM and !$request->_user->isAdmin()) {
                $request->_notAuthorized(WIKIAUTH_ADMIN);
                $this->disabled("! user->isAdmin");
            }
            if ($post_args['action'] == 'verify') {
                // Real delete.
                return $this->removePages($request, array_keys($p));
            }

            if ($post_args['action'] == 'select') {
                $next_action = 'verify';
                foreach ($p as $name => $c) {
                    $name = str_replace(array('%5B','%5D'), array('[',']'), $name);
                    $pages[$name] = $c;
                }
            }
        } elseif ($p && is_array($p) && !$request->isPost()) { // from WikiAdminSelect
            $next_action = 'verify';
            foreach ($p as $name => $c) {
                $name = str_replace(array('%5B','%5D'), array('[',']'), $name);
                $pages[$name] = $c;
            }
            $request->setArg('p', false);
        }
        if ($next_action == 'select') {
            // List all pages to select from.
            $pages = $this->collectPages($pages, $dbi, $args['sortby'], $args['limit'], $args['exclude']);
        }
        $pagelist = new PageList_Selectable(
            $args['info'],
            $args['exclude'],
            array('types' =>
                                                  array('remove'
            => new _PageList_Column_remove('remove', _("Remove"))))
        );
        $pagelist->addPageList($pages);

        $header = HTML::p();
        if ($next_action == 'verify') {
            $button_label = _("Yes");
            $header->pushContent(HTML::strong(
                _("Are you sure you want to permanently remove the selected files?")
            ));
        } else {
            $button_label = _("Remove selected pages");
            $header->pushContent(_("Permanently remove the selected files:"), HTML::br());
            if ($args['min_age'] > 0) {
                $header->pushContent(
                    fmt(
                        "Also pages which have been deleted at least %s days.",
                        $args['min_age']
                    )
                );
            } else {
                $header->pushContent(_("List all pages."));
            }

            if ($args['max_age'] > 0) {
                $header->pushContent(
                    " ",
                    fmt(
                        "(Pages which have been deleted at least %s days are already checked.)",
                        $args['max_age']
                    )
                );
            }
        }

        $buttons = HTML::p(
            Button('submit:admin_remove[remove]', $button_label, 'wikiadmin'),
            Button('submit:admin_remove[cancel]', _("Cancel"), 'button')
        );

        // TODO: quick select by regex javascript?
        return HTML::form(
            array('action' => $request->getPostURL(),
                                'method' => 'post'),
            $header,
            $pagelist->getContent(),
            HiddenInputs(
                $request->getArgs(),
                false,
                array('admin_remove')
            ),
            HiddenInputs(array('admin_remove[action]' => $next_action,
                                             'require_authority_for_post' => WIKIAUTH_ADMIN)),
            $buttons
        );
    }
}

class _PageList_Column_remove extends _PageList_Column
{
    public function _getValue($page_handle, &$revision_handle)
    {
        return Button(
            array('action' => 'remove'),
            _("Remove"),
            $page_handle->getName()
        );
    }
}


// $Log: WikiAdminRemove.php,v $
// Revision 1.30  2004/11/23 15:17:19  rurban
// better support for case_exact search (not caseexact for consistency),
// plugin args simplification:
//   handle and explode exclude and pages argument in WikiPlugin::getArgs
//     and exclude in advance (at the sql level if possible)
//   handle sortby and limit from request override in WikiPlugin::getArgs
// ListSubpages: renamed pages to maxpages
//
// Revision 1.29  2004/11/09 17:11:17  rurban
// * revert to the wikidb ref passing. there's no memory abuse there.
// * use new wikidb->_cache->_id_cache[] instead of wikidb->_iwpcache, to effectively
//   store page ids with getPageLinks (GleanDescription) of all existing pages, which
//   are also needed at the rendering for linkExistingWikiWord().
//   pass options to pageiterator.
//   use this cache also for _get_pageid()
//   This saves about 8 SELECT count per page (num all pagelinks).
// * fix passing of all page fields to the pageiterator.
// * fix overlarge session data which got broken with the latest ACCESS_LOG_SQL changes
//
// Revision 1.28  2004/11/01 10:43:59  rurban
// seperate PassUser methods into seperate dir (memory usage)
// fix WikiUser (old) overlarge data session
// remove wikidb arg from various page class methods, use global ->_dbi instead
// ...
//
// Revision 1.27  2004/06/16 10:38:59  rurban
// Disallow refernces in calls if the declaration is a reference
// ("allow_call_time_pass_reference clean").
//   PhpWiki is now allow_call_time_pass_reference = Off clean,
//   but several external libraries may not.
//   In detail these libs look to be affected (not tested):
//   * Pear_DB odbc
//   * adodb oracle
//
// Revision 1.26  2004/06/14 11:31:39  rurban
// renamed global $Theme to $WikiTheme (gforge nameclash)
// inherit PageList default options from PageList
//   default sortby=pagename
// use options in PageList_Selectable (limit, sortby, ...)
// added action revert, with button at action=diff
// added option regex to WikiAdminSearchReplace
//
// Revision 1.25  2004/06/13 15:33:20  rurban
// new support for arguments owner, author, creator in most relevant
// PageList plugins. in WikiAdmin* via preSelectS()
//
// Revision 1.24  2004/06/08 10:05:11  rurban
// simplified admin action shortcuts
//
// Revision 1.23  2004/06/03 22:24:48  rurban
// reenable admin check on !ENABLE_PAGEPERM, honor s=Wildcard arg, fix warning after Remove
//
// Revision 1.22  2004/05/16 22:07:35  rurban
// check more config-default and predefined constants
// various PagePerm fixes:
//   fix default PagePerms, esp. edit and view for Bogo and Password users
//   implemented Creator and Owner
//   BOGOUSERS renamed to BOGOUSER
// fixed syntax errors in signin.tmpl
//
// Revision 1.21  2004/05/04 16:34:22  rurban
// prvent hidden p overwrite checked p
//
// Revision 1.20  2004/05/03 11:02:30  rurban
// fix passing args from WikiAdminSelect to WikiAdminRemove
//
// Revision 1.19  2004/04/12 09:12:23  rurban
// fix syntax errors
//
// Revision 1.18  2004/04/07 23:13:19  rurban
// fixed pear/File_Passwd for Windows
// fixed FilePassUser sessions (filehandle revive) and password update
//
// Revision 1.17  2004/03/17 20:23:44  rurban
// fixed p[] pagehash passing from WikiAdminSelect, fixed problem removing pages with [] in the pagename
//
// Revision 1.16  2004/03/12 13:31:43  rurban
// enforce PagePermissions, errormsg if not Admin
//
// Revision 1.15  2004/03/01 13:48:46  rurban
// rename fix
// p[] consistency fix
//
// Revision 1.14  2004/02/22 23:20:33  rurban
// fixed DumpHtmlToDir,
// enhanced sortby handling in PageList
//   new button_heading th style (enabled),
// added sortby and limit support to the db backends and plugins
//   for paging support (<<prev, next>> links on long lists)
//
// Revision 1.13  2004/02/17 12:11:36  rurban
// added missing 4th basepage arg at plugin->run() to almost all plugins. This caused no harm so far, because it was silently dropped on normal usage. However on plugin internal ->run invocations it failed. (InterWikiSearch, IncludeSiteMap, ...)
//
// Revision 1.12  2004/02/15 21:34:37  rurban
// PageList enhanced and improved.
// fixed new WikiAdmin... plugins
// editpage, Theme with exp. htmlarea framework
//   (htmlarea yet committed, this is really questionable)
// WikiUser... code with better session handling for prefs
// enhanced UserPreferences (again)
// RecentChanges for show_deleted: how should pages be deleted then?
//
// Revision 1.11  2004/02/11 20:00:16  rurban
// WikiAdmin... series overhaul. Rename misses the db backend methods yet. Chmod + Chwon still missing.
//
// Revision 1.9  2003/02/26 22:27:22  dairiki
// Fix and refactor FrameInclude plugin (more or less).
//
// (This should now generate valid HTML.  Woohoo!)
//
// The output when using the Sidebar theme is ugly enough that it should
// be considered broken.  (But the Sidebar theme appears pretty broken in
// general right now.)
//
// (Personal comment (not to be taken personally): I must say that I
// remain unconvinced of the usefulness of this plugin.)
//
// Revision 1.8  2003/02/17 17:23:59  dairiki
// Disable plugin unless action='browse'.
//
// Add a header to the output, and adjust the HTML formatting a bit.
//
// Revision 1.7  2003/02/17 06:06:33  dairiki
// Refactor & code cleanup.
//
// Added two new plugin arguments:
//
//   min_age - only display pages which have been "deleted" for at
//             least this many days.  (Use min_age=none to get all
//             pages, even non-deleted ones listed.)
//
//   max_age - automatically check the checkboxes of pages which
//             have been "deleted" this many days or more.
//
// ("Deleted" means the current version of the page is empty.
// For the most part, PhpWiki treats these "deleted" pages as
// if they didn't exist --- but, of course, the PageHistory is
// still available, allowing old versions of the page to be restored.)
//
// Revision 1.6  2003/02/16 19:47:17  dairiki
// Update WikiDB timestamp when editing or deleting pages.
//
// Revision 1.5  2003/01/18 22:14:28  carstenklapp
// Code cleanup:
// Reformatting & tabs to spaces;
// Added copyleft, getVersion, getDescription, rcs_id.
// Local Variables:
// mode: php
// tab-width: 8
// c-basic-offset: 4
// c-hanging-comment-ender-p: nil
// indent-tabs-mode: nil
// End:
