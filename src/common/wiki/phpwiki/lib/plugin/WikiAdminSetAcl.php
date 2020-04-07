<?php
// -*-php-*-
rcs_id('$Id: WikiAdminSetAcl.php,v 1.23 2005/02/12 17:24:24 rurban Exp $');
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
 * Set individual PagePermissions
 *
 * Usage:   <?plugin WikiAdminSetAcl ?> or called via WikiAdminSelect
 * Author:  Reini Urban <rurban@x-ray.at>
 *
 * KNOWN ISSUES:
 * Requires PHP 4.2 so far.
 */
require_once('lib/PageList.php');
require_once('lib/plugin/WikiAdminSelect.php');

class WikiPlugin_WikiAdminSetAcl extends WikiPlugin_WikiAdminSelect
{
    public function getName()
    {
        return _("WikiAdminSetAcl");
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
            "\$Revision: 1.23 $"
        );
    }

    public function getDefaultArguments()
    {
        return array_merge(
            PageList::supportedArgs(),
            array(
                     'p'        => "[]",  // list of pages
                     's'     => false, /* select by pagename */
                     /* Columns to include in listing */
                     'info'     => 'pagename,perm,mtime,owner,author',
            )
        );
    }

    public function setaclPages(&$request, $pages, $acl)
    {
        $ul = HTML::ul();
        $count = 0;
        $dbi = $request->_dbi;
        // check new_group and new_perm
        if (isset($acl['_add_group'])) {
        //add groups with perm
            foreach ($acl['_add_group'] as $access => $dummy) {
                $group = $acl['_new_group'][$access];
                $acl[$access][$group] = isset($acl['_new_perm'][$access]) ? 1 : 0;
            }
            unset($acl['_add_group']);
        }
        unset($acl['_new_group']);
        unset($acl['_new_perm']);
        if (isset($acl['_del_group'])) {
        //del groups with perm
            foreach ($acl['_del_group'] as $access => $del) {
                foreach ($del as $group => $dummy) {
                    unset($acl[$access][$group]);
                }
            }
            unset($acl['_del_group']);
        }
        if ($perm = new PagePermission($acl)) {
            $perm->sanify();
            foreach ($pages as $pagename) {
                // check if unchanged? we need a deep array_equal
                $page = $dbi->getPage($pagename);
                $oldperm = getPagePermissions($page);
                if ($oldperm) {
                    $oldperm->sanify();
                }
                if ($oldperm and $perm->equal($oldperm->perm)) { // (serialize($oldperm->perm) == serialize($perm->perm))
                    $ul->pushContent(HTML::li(fmt("ACL not changed for page '%s'.", $pagename)));
                } elseif (mayAccessPage('change', $pagename)) {
                    setPagePermissions($page, $perm);
                    $ul->pushContent(HTML::li(fmt("ACL changed for page '%s'.", $pagename)));
                    $count++;
                } else {
                    $ul->pushContent(HTML::li(fmt("Access denied to change page '%s'.", $pagename)));
                }
            }
        } else {
            $ul->pushContent(HTML::li(fmt("Invalid ACL")));
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
    //if (!DEBUG)
        //    return $this->disabled("WikiAdminSetAcl not yet enabled. Set DEBUG to try it.");
        if ($request->getArg('action') != 'browse') {
            if ($request->getArg('action') != _("PhpWikiAdministration/SetAcl")) {
                return $this->disabled("(action != 'browse')");
            }
        }
        if (!ENABLE_PAGEPERM) {
            return $this->disabled("ENABLE_PAGEPERM = false");
        }

        $args = $this->getArgs($argstr, $request);
        $this->_args = $args;
        $this->preSelectS($args, $request);

        $p = $request->getArg('p');
        $post_args = $request->getArg('admin_setacl');
        $next_action = 'select';
        $pages = array();
        if ($p && !$request->isPost()) {
            $pages = $p;
        } elseif ($this->_list) {
            $pages = $this->_list;
        }
        $header = HTML::p();
        if (
            $p && $request->isPost() &&
            !empty($post_args['acl']) && empty($post_args['cancel'])
        ) {
            // without individual PagePermissions:
            if (!ENABLE_PAGEPERM and !$request->_user->isAdmin()) {
                $request->_notAuthorized(WIKIAUTH_ADMIN);
                $this->disabled("! user->isAdmin");
            }
            if ($post_args['action'] == 'verify') {
                // Real action
                $header->pushContent(
                    $this->setaclPages(
                        $request,
                        array_keys($p),
                        $request->getArg('acl')
                    )
                );
            }
            if ($post_args['action'] == 'select') {
                if (!empty($post_args['acl'])) {
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
            $args['info'] = "checkbox,pagename,perm,mtime,owner,author";
        }
        $pagelist = new PageList_Selectable(
            $args['info'],
            $args['exclude'],
            array('types' => array(
                                                  'perm'
                                                  => new _PageList_Column_perm('perm', _("Permission")),
                                                  'acl'
            => new _PageList_Column_acl('acl', _("ACL"))))
        );

        $pagelist->addPageList($pages);
        if ($next_action == 'verify') {
            $button_label = _("Yes");
            $header = $this->setaclForm($header, $post_args, $pages);
            $header->pushContent(
                HTML::p(HTML::strong(
                    _("Are you sure you want to permanently change access to the selected files?")
                ))
            );
        } else {
            $button_label = _("SetAcl");
            $header = $this->setaclForm($header, $post_args, $pages);
            $header->pushContent(HTML::p(_("Select the pages to change:")));
        }

        $buttons = HTML::p(
            Button('submit:admin_setacl[acl]', $button_label, 'wikiadmin'),
            Button('submit:admin_setacl[cancel]', _("Cancel"), 'button')
        );

        return HTML::form(
            array('action' => $request->getPostURL(),
                                'method' => 'post'),
            $header,
            $pagelist->getContent(),
            HiddenInputs(
                $request->getArgs(),
                false,
                array('admin_setacl')
            ),
            HiddenInputs(array('admin_setacl[action]' => $next_action)),
            ENABLE_PAGEPERM
                          ? ''
                          : HiddenInputs(array('require_authority_for_post' => WIKIAUTH_ADMIN)),
            $buttons
        );
    }

    public function setaclForm(&$header, $post_args, $pagehash)
    {
        $acl = $post_args['acl'];

        //FIXME: find intersection of all pages perms, not just from the last pagename
        $pages = array();
        foreach ($pagehash as $name => $checked) {
            if ($checked) {
                $pages[] = $name;
            }
        }
        $perm_tree = pagePermissions($name);
        $table = pagePermissionsAclFormat($perm_tree, !empty($pages));
        $header->pushContent(HTML::strong(_("Selected Pages: ")), HTML::tt(join(', ', $pages)), HTML::br());
        $first_page = $GLOBALS['request']->_dbi->getPage($name);
        $owner = $first_page->getOwner();
        list($type, $perm) = pagePermissionsAcl($perm_tree[0], $perm_tree);
        //if (DEBUG) $header->pushContent(HTML::pre("Permission tree for $name:\n",print_r($perm_tree,true)));
        if ($type == 'inherited') {
            $type = sprintf(_("page permission inherited from %s"), $perm_tree[1][0]);
        } elseif ($type == 'page') {
            $type = _("invidual page permission");
        } elseif ($type == 'default') {
            $type = _("default page permission");
        }
        $header->pushContent(HTML::strong(_("Type") . ': '), HTML::tt($type), HTML::br());
        $header->pushContent(HTML::strong(_("getfacl") . ': '), pagePermissionsSimpleFormat($perm_tree, $owner), HTML::br());
        $header->pushContent(HTML::strong(_("ACL") . ': '), HTML::tt($perm->asAclLines()), HTML::br());

        $header->pushContent(HTML::p(
            HTML::strong(_("Description") . ': '),
            _("Selected Grant checkboxes allow access, unselected checkboxes deny access."),
            _("To ignore delete the line."),
            _("To add check 'Add' near the dropdown list.")
        ));
        $header->pushContent(HTML::blockquote($table));
        // display array of checkboxes for existing perms
        // and a dropdown for user/group to add perms.
        // disabled if inherited,
        // checkbox to disable inheritance,
        // another checkbox to progate new permissions to all childs (if there exist some)
        //Todo:
        // warn if more pages are selected and they have different perms
        //$header->pushContent(HTML::input(array('name' => 'admin_setacl[acl]',
        //                                       'value' => $post_args['acl'])));
        $header->pushContent(HTML::br());
        if (!empty($pages) and DEBUG) {
            $checkbox = HTML::input(array('type' => 'checkbox',
                                        'name' => 'admin_setacl[updatechildren]',
                                        'value' => 1));
            if (!empty($post_args['updatechildren'])) {
                $checkbox->setAttr('checked', 'checked');
            }
            $header->pushContent(
                $checkbox,
                _("Propagate new permissions to all subpages?"),
                HTML::raw("&nbsp;&nbsp;"),
                HTML::em(_("(disable individual page permissions, enable inheritance)?")),
                HTML::br(),
                HTML::em(_("(Currently not working)"))
            );
        }
        $header->pushContent(HTML::hr(), HTML::p());
        return $header;
    }
}

class _PageList_Column_acl extends _PageList_Column
{
    public function _getValue($page_handle, &$revision_handle)
    {
        $perm_tree = pagePermissions($page_handle->_pagename);
        return pagePermissionsAclFormat($perm_tree);
    }
}

class _PageList_Column_perm extends _PageList_Column
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

// $Log: WikiAdminSetAcl.php,v $
// Revision 1.23  2005/02/12 17:24:24  rurban
// locale update: missing . : fixed. unified strings
// proper linebreaks
//
// Revision 1.22  2005/01/25 08:05:17  rurban
// protect against !ENABLE_PAGEPERM
//
// Revision 1.21  2004/11/23 15:17:20  rurban
// better support for case_exact search (not caseexact for consistency),
// plugin args simplification:
//   handle and explode exclude and pages argument in WikiPlugin::getArgs
//     and exclude in advance (at the sql level if possible)
//   handle sortby and limit from request override in WikiPlugin::getArgs
// ListSubpages: renamed pages to maxpages
//
// Revision 1.20  2004/11/01 10:43:59  rurban
// seperate PassUser methods into seperate dir (memory usage)
// fix WikiUser (old) overlarge data session
// remove wikidb arg from various page class methods, use global ->_dbi instead
// ...
//
// Revision 1.19  2004/06/16 10:38:59  rurban
// Disallow refernces in calls if the declaration is a reference
// ("allow_call_time_pass_reference clean").
//   PhpWiki is now allow_call_time_pass_reference = Off clean,
//   but several external libraries may not.
//   In detail these libs look to be affected (not tested):
//   * Pear_DB odbc
//   * adodb oracle
//
// Revision 1.18  2004/06/14 11:31:39  rurban
// renamed global $Theme to $WikiTheme (gforge nameclash)
// inherit PageList default options from PageList
//   default sortby=pagename
// use options in PageList_Selectable (limit, sortby, ...)
// added action revert, with button at action=diff
// added option regex to WikiAdminSearchReplace
//
// Revision 1.17  2004/06/13 15:33:20  rurban
// new support for arguments owner, author, creator in most relevant
// PageList plugins. in WikiAdmin* via preSelectS()
//
// Revision 1.16  2004/06/08 13:50:43  rurban
// show getfacl and acl line
//
// Revision 1.15  2004/06/08 10:05:12  rurban
// simplified admin action shortcuts
//
// Revision 1.14  2004/06/07 22:28:06  rurban
// add acl field to mimified dump
//
// Revision 1.13  2004/06/04 20:32:54  rurban
// Several locale related improvements suggested by Pierrick Meignen
// LDAP fix by John Cole
// reanable admin check without ENABLE_PAGEPERM in the admin plugins
//
// Revision 1.12  2004/06/03 22:24:48  rurban
// reenable admin check on !ENABLE_PAGEPERM, honor s=Wildcard arg, fix warning after Remove
//
// Revision 1.11  2004/06/01 15:28:02  rurban
// AdminUser only ADMIN_USER not member of Administrators
// some RateIt improvements by dfrankow
// edit_toolbar buttons
//
// Revision 1.10  2004/05/27 17:49:06  rurban
// renamed DB_Session to DbSession (in CVS also)
// added WikiDB->getParam and WikiDB->getAuthParam method to get rid of globals
// remove leading slash in error message
// added force_unlock parameter to File_Passwd (no return on stale locks)
// fixed adodb session AffectedRows
// added FileFinder helpers to unify local filenames and DATA_PATH names
// editpage.php: new edit toolbar javascript on ENABLE_EDIT_TOOLBAR
//
// Revision 1.9  2004/05/24 17:34:53  rurban
// use ACLs
//
// Revision 1.8  2004/05/16 22:32:54  rurban
// setacl icons
//
// Revision 1.7  2004/05/16 22:07:35  rurban
// check more config-default and predefined constants
// various PagePerm fixes:
//   fix default PagePerms, esp. edit and view for Bogo and Password users
//   implemented Creator and Owner
//   BOGOUSERS renamed to BOGOUSER
// fixed syntax errors in signin.tmpl
//
// Revision 1.5  2004/04/07 23:13:19  rurban
// fixed pear/File_Passwd for Windows
// fixed FilePassUser sessions (filehandle revive) and password update
//
// Revision 1.4  2004/03/17 20:23:44  rurban
// fixed p[] pagehash passing from WikiAdminSelect, fixed problem removing pages with [] in the pagename
//
// Revision 1.3  2004/03/12 13:31:43  rurban
// enforce PagePermissions, errormsg if not Admin
//
// Revision 1.2  2004/02/24 04:02:07  rurban
// Better warning messages
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
