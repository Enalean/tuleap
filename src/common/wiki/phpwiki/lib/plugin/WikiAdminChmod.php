<?php
// -*-php-*-
rcs_id('$Id: WikiAdminChmod.php,v 1.14 2004/12/13 14:36:35 rurban Exp $');
/*
 Copyright 2004 $ThePhpWikiProgrammingTeam

 This file is not yet part of PhpWiki. It doesn't work yet.

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
 * Set individual PagePermissions, simplifying effective ACLs to
 * unix-like rwxr--r--+ permissions. (as in cygwin)
 *
 * Usage:   <?plugin WikiAdminChmod ?> or called via WikiAdminSelect
 * Author:  Reini Urban <rurban@x-ray.at>
 *
 * KNOWN ISSUES:
 * Requires PHP 4.2 so far.
 */
require_once('lib/PageList.php');
require_once('lib/plugin/WikiAdminSelect.php');

class WikiPlugin_WikiAdminChmod extends WikiPlugin_WikiAdminSelect
{
    public function getName()
    {
        return _("WikiAdminChmod");
    }

    public function getDescription()
    {
        return _("Set individual page permissions.");
    }

    public function getVersion()
    {
        return preg_replace(
            "/[Revision: $]/",
            '',
            "\$Revision: 1.14 $"
        );
    }

    public function getDefaultArguments()
    {
        return array_merge(
            PageList::supportedArgs(),
            array(
                   's'         => false,
                   'perm'     => false,
                   /* Columns to include in listing */
                   'info'     => 'pagename,perm,mtime,author',
            )
        );
    }

    // todo: change permstring to some kind of default ACL hash.
    // See PagePermission class
    public function chmodHelper($permstring)
    {
        $perm = array();
        return $perm;
    }

    public function chmodPages(&$dbi, &$request, $pages, $permstring)
    {
        $ul = HTML::ul();
        $count = 0;
        $acl = chmodHelper($permstring);
        if ($perm = new PagePermission($acl)) {
            foreach ($pages as $name) {
                if ($perm->store($dbi->getPage($name))) {
                    $ul->pushContent(HTML::li(fmt("chmod page '%s' to '%s'.", $name, $permstring)));
                    $count++;
                } else {
                    $ul->pushContent(HTML::li(fmt("Couldn't chmod page '%s' to '%s'.", $name, $permstring)));
                }
            }
        } else {
            $ul->pushContent(HTML::li(fmt("Invalid chmod string")));
        }
        if ($count) {
            $dbi->touch();
            return HTML(
                $ul,
                HTML::p(fmt("%s pages have been changed.", $count))
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
        return $this->disabled("This action is blocked by administrator. Sorry for the inconvenience !");
        if (!DEBUG) {
            return $this->disabled("WikiAdminChmod not yet enabled. Set DEBUG to try it.");
        }

        $args = $this->getArgs($argstr, $request);
        $this->_args = $args;
        $this->preSelectS($args, $request);

        $p = $request->getArg('p');
        if (!$p) {
            $p = $this->_list;
        }
        $post_args = $request->getArg('admin_chmod');
        $next_action = 'select';
        $pages = array();
        if ($p && !$request->isPost()) {
            $pages = $p;
        }
        if (
            $p && $request->isPost() &&
            !empty($post_args['chmod']) && empty($post_args['cancel'])
        ) {
            // without individual PagePermissions:
            if (!ENABLE_PAGEPERM and !$request->_user->isAdmin()) {
                $request->_notAuthorized(WIKIAUTH_ADMIN);
                $this->disabled("! user->isAdmin");
            }

            if ($post_args['action'] == 'verify') {
                // Real action
                return $this->chmodPages(
                    $dbi,
                    $request,
                    array_keys($p),
                    $post_args['perm']
                );
            }
            if ($post_args['action'] == 'select') {
                if (!empty($post_args['perm'])) {
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
            $args['info'] = "checkbox,pagename,perm,author,mtime";
        }
        $args['types'] = array('perm' => new _PageList_Column_chmod_perm('perm', _("Permission")));
        $pagelist = new PageList_Selectable($args['info'], $args['exclude'], $args);
        $pagelist->addPageList($pages);

        $header = HTML::p();
        if ($next_action == 'verify') {
            $button_label = _("Yes");
            $header = $this->chmodForm($header, $post_args);
            $header->pushContent(
                HTML::p(HTML::strong(
                    _("Are you sure you want to permanently change the selected files?")
                ))
            );
        } else {
            $button_label = _("Chmod");
            $header = $this->chmodForm($header, $post_args);
            $header->pushContent(HTML::p(_("Select the pages to change:")));
        }

        $buttons = HTML::p(
            Button('submit:admin_chmod[chmod]', $button_label, 'wikiadmin'),
            Button('submit:admin_chmod[cancel]', _("Cancel"), 'button')
        );

        return HTML::form(
            array('action' => $request->getPostURL(),
                                'method' => 'post'),
            $header,
            $pagelist->getContent(),
            HiddenInputs(
                $request->getArgs(),
                false,
                array('admin_chmod')
            ),
            HiddenInputs(array('admin_chmod[action]' => $next_action)),
            ENABLE_PAGEPERM
                          ? ''
                          : HiddenInputs(array('require_authority_for_post' => WIKIAUTH_ADMIN)),
            $buttons
        );
    }

    public function chmodForm(&$header, $post_args)
    {
        $header->pushContent(
            HTML::p(HTML::em(
                _("This plugin is currently under development and does not work!")
            ))
        );
        $header->pushContent(_("Chmod to permission:"));
        $header->pushContent(HTML::input(array('name' => 'admin_chmod[perm]',
                                               'value' => $post_args['perm'])));
        $header->pushContent(' ' . _("(ugo : rwx)"));
        $header->pushContent(HTML::p());
        $checkbox = HTML::input(array('type' => 'checkbox',
                                      'name' => 'admin_chmod[updatechildren]',
                                      'value' => 1));
        if (!empty($post_args['updatechildren'])) {
            $checkbox->setAttr('checked', 'checked');
        }
        $header->pushContent(
            $checkbox,
            HTML::raw("&nbsp;"),
            _("Propagate new permissions to all subpages?"),
            HTML::raw("&nbsp;&nbsp;"),
            HTML::em(_("(disable individual page permissions, enable inheritance)?"))
        );
        $header->pushContent(HTML::hr(), HTML::p());
        return $header;
    }
}

// conflicts with WikiAdminSetAcl
class _PageList_Column_chmod_perm extends _PageList_Column
{
    public function _getValue($page_handle, &$revision_handle)
    {
        $perm_array = pagePermissions($page_handle->_pagename);
        return pagePermissionsSimpleFormat(
            $perm_array,
            $page_handle->get('author'),
            $page_handle->get('group')
        );
    }
}

// $Log: WikiAdminChmod.php,v $
// Revision 1.14  2004/12/13 14:36:35  rurban
// avoid nameclash with SetAcl
//
// Revision 1.13  2004/12/06 19:50:05  rurban
// enable action=remove which is undoable and seeable in RecentChanges: ADODB ony for now.
// renamed delete_page to purge_page.
// enable action=edit&version=-1 to force creation of a new version.
// added BABYCART_PATH config
// fixed magiqc in adodb.inc.php
// and some more docs
//
// Revision 1.12  2004/11/23 15:17:19  rurban
// better support for case_exact search (not caseexact for consistency),
// plugin args simplification:
//   handle and explode exclude and pages argument in WikiPlugin::getArgs
//     and exclude in advance (at the sql level if possible)
//   handle sortby and limit from request override in WikiPlugin::getArgs
// ListSubpages: renamed pages to maxpages
//
// Revision 1.11  2004/06/16 10:38:59  rurban
// Disallow refernces in calls if the declaration is a reference
// ("allow_call_time_pass_reference clean").
//   PhpWiki is now allow_call_time_pass_reference = Off clean,
//   but several external libraries may not.
//   In detail these libs look to be affected (not tested):
//   * Pear_DB odbc
//   * adodb oracle
//
// Revision 1.10  2004/06/14 11:31:39  rurban
// renamed global $Theme to $WikiTheme (gforge nameclash)
// inherit PageList default options from PageList
//   default sortby=pagename
// use options in PageList_Selectable (limit, sortby, ...)
// added action revert, with button at action=diff
// added option regex to WikiAdminSearchReplace
//
// Revision 1.9  2004/06/13 15:33:20  rurban
// new support for arguments owner, author, creator in most relevant
// PageList plugins. in WikiAdmin* via preSelectS()
//
// Revision 1.8  2004/06/04 20:32:54  rurban
// Several locale related improvements suggested by Pierrick Meignen
// LDAP fix by John Cole
// reanable admin check without ENABLE_PAGEPERM in the admin plugins
//
// Revision 1.7  2004/06/03 22:24:42  rurban
// reenable admin check on !ENABLE_PAGEPERM, honor s=Wildcard arg, fix warning after Remove
//
// Revision 1.6  2004/03/17 20:23:44  rurban
// fixed p[] pagehash passing from WikiAdminSelect, fixed problem removing pages with [] in the pagename
//
// Revision 1.5  2004/03/12 13:31:43  rurban
// enforce PagePermissions, errormsg if not Admin
//
// Revision 1.4  2004/02/24 15:20:06  rurban
// fixed minor warnings: unchecked args, POST => Get urls for sortby e.g.
//
// Revision 1.3  2004/02/24 04:02:06  rurban
// Better warning messages
//
// Revision 1.2  2004/02/24 03:21:40  rurban
// enabled require_all check in WikiPoll
// better handling of <20 min visiting client: display results so far
//
// Revision 1.1  2004/02/23 21:30:25  rurban
// more PagePerm stuff: (working against 1.4.0)
//   ACL editing and simplification of ACL's to simple rwx------ string
//   not yet working.
// Local Variables:
// mode: php
// tab-width: 8
// c-basic-offset: 4
// c-hanging-comment-ender-p: nil
// indent-tabs-mode: nil
// End:
