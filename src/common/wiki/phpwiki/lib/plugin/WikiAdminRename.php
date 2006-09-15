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
 * Usage:   <?plugin WikiAdminRename ?> or called via WikiAdminSelect
 * @author:  Reini Urban <rurban@x-ray.at>
 *
 * KNOWN ISSUES:
 * Currently we must be Admin.
 * Future versions will support PagePermissions.
 * requires PHP 4.2 so far.
 */
require_once('lib/PageList.php');
require_once('lib/plugin/WikiAdminSelect.php');

class WikiPlugin_WikiAdminRename
extends WikiPlugin_WikiAdminSelect
{
    function getName() {
        return _("WikiAdminRename");
    }

    function getDescription() {
        return _("Rename selected pages.");
    }

    function getVersion() {
        return preg_replace("/[Revision: $]/", '',
                            "\$Revision$");
    }

    function getDefaultArguments() {
        return array(
                     /* Pages to exclude in listing */
                     'exclude'  => '',
                     /* Columns to include in listing */
                     'info'     => 'pagename,mtime',
                     /* How to sort */
                     'sortby'   => 'pagename',
                     'limit'    => 0,
                     'updatelinks' => 0
                     );
    }

    function renameHelper($name, $from, $to) {
        return str_replace($from,$to,$name);
    }

    function renamePages(&$dbi, &$request, $pages, $from, $to, $updatelinks=false) {
        $ul = HTML::ul();
        $count = 0;
        foreach ($pages as $name) {
            if ( ($newname = $this->renameHelper($name,$from,$to)) and 
                  $newname != $name and
                 $dbi->renamePage($name,$newname,$updatelinks) ) {
                /* not yet implemented for all backends */
                $ul->pushContent(HTML::li(fmt("Renamed page '%s' to '%s'.",$name,WikiLink($newname))));
                $count++;
            } else {
                $ul->pushContent(HTML::li(fmt("Couldn't rename page '%s' to '%s'.", $name, $newname)));
            }
        }
        if ($count) {
            $dbi->touch();
            return HTML($ul,
                        HTML::p(fmt("%s pages have been permanently renamed.",$count)));
        } else {
            return HTML($ul,
                        HTML::p(fmt("No pages renamed.")));
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
        $post_args = $request->getArg('admin_rename');
        $next_action = 'select';
        $pages = array();
        if ($p && !$request->isPost())
            $pages = $p;
        if ($p && $request->isPost() &&
            !empty($post_args['rename']) && empty($post_args['cancel'])) {

            // FIXME: check individual PagePermissions
            if (!$request->_user->isAdmin()) {
                $request->_notAuthorized(WIKIAUTH_ADMIN);
                $this->disabled("! user->isAdmin");
            }

            // FIXME: error message if not admin.
            if ($post_args['action'] == 'verify') {
                // Real action
                return $this->renamePages($dbi, $request, array_keys($p), 
                                          $post_args['from'], $post_args['to'], !empty($post_args['updatelinks']));
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
            $args['info'] = "checkbox,pagename,renamed_pagename";
        }
        $pagelist = new PageList_Selectable
            (
             $args['info'], $exclude, 
             array('types' => 
                   array('renamed_pagename'
                         => new _PageList_Column_renamed_pagename('rename', _("Rename to")),
                         )));
        $pagelist->addPageList($pages);

        $header = HTML::p();
        if ($next_action == 'verify') {
            $button_label = _("Yes");
            $header->pushContent(
              HTML::p(HTML::strong(
                                   _("Are you sure you want to permanently rename the selected files?"))));
            $header = $this->renameForm($header, $post_args);
        }
        else {
            $button_label = _("Rename selected pages");
            $header->pushContent(HTML::p(_("Select the pages to rename:")));
            $header = $this->renameForm($header, $post_args);
        }


        $buttons = HTML::p(Button('submit:admin_rename[rename]', $button_label, 'wikiadmin'),
                           Button('submit:admin_rename[cancel]', _("Cancel"), 'button'));

        return HTML::form(array('action' => $request->getPostURL(),
                                'method' => 'post'),
                          $header,
                          $pagelist->getContent(),
                          HiddenInputs($request->getArgs(),
                                        false,
                                        array('admin_rename')),
                          HiddenInputs(array('admin_rename[action]' => $next_action,
                                             'require_authority_for_post' => WIKIAUTH_ADMIN)),
                          $buttons);
    }

    function renameForm(&$header, $post_args) {
        $header->pushContent(_("Rename")." "._("from").': ');
        $header->pushContent(HTML::input(array('name' => 'admin_rename[from]',
                                               'value' => $post_args['from'])));
        $header->pushContent(' '._("to").': ');
        $header->pushContent(HTML::input(array('name' => 'admin_rename[to]',
                                               'value' => $post_args['to'])));
        $header->pushContent(' '._("(no regex, case-sensitive)"));
        if (1) { // not yet tested
            $header->pushContent(HTML::br());
            $header->pushContent(_("Change pagename in all linked pages also?"));
            $checkbox = HTML::input(array('type' => 'checkbox',
                                          'name' => 'admin_rename[updatelinks]',
                                          'value' => 1));
            if (!empty($post_args['updatelinks']))
                $checkbox->setAttr('checked','checked');
            $header->pushContent($checkbox);
        }
        $header->pushContent(HTML::p());
        return $header;
    }
}

// moved from lib/PageList.php
class _PageList_Column_renamed_pagename extends _PageList_Column {
    function _getValue ($page_handle, &$revision_handle) {
        $post_args = $GLOBALS['request']->getArg('admin_rename');
        $value = str_replace($post_args['from'], $post_args['to'],$page_handle->getName());
        $div = HTML::div(" => ",HTML::input(array('type' => 'text',
                                                  'name' => 'rename[]',
                                                  'value' => $value)));
        $new_page = $GLOBALS['request']->getPage($value);
        if ($new_page->exists()) {
            $div->setAttr('class','error');
            $div->setAttr('title',_("This page already exists"));
        }
        return $div;
    }
};

// $Log$
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
//

// Local Variables:
// mode: php
// tab-width: 8
// c-basic-offset: 4
// c-hanging-comment-ender-p: nil
// indent-tabs-mode: nil
// End:
?>
