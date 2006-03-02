<?php // -*-php-*-
rcs_id('$Id$');
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

class WikiPlugin_WikiAdminRemove
extends WikiPlugin
{
    function getName() {
        return _("WikiAdminRemove");
    }

    function getDescription() {
        return _("Permanently remove all selected pages.");
    }

    function getVersion() {
        return preg_replace("/[Revision: $]/", '',
                            "\$Revision$");
    }

    function getDefaultArguments() {
        return array(
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

                     /* Pages or regex to exclude */
                     'exclude'  => '',

                     /* Columns to include in listing */
                     'info'     => 'most',

                     /* How to sort */
                     'sortby'   => 'pagename',
                     'limit'    => 0
                     );
    }

    function collectPages(&$list, &$dbi, $sortby, $limit=0) {
        extract($this->_args);

        $now = time();
        
        $allPages = $dbi->getAllPages('include_deleted',$sortby,$limit);
        while ($pagehandle = $allPages->next()) {
            $pagename = $pagehandle->getName();
            $current = $pagehandle->getCurrentRevision();
            if ($current->getVersion() < 1)
                continue;       // No versions in database

            $empty = $current->hasDefaultContents();
            if ($empty) {
                $age = ($now - $current->get('mtime')) / (24 * 3600.0);
                $checked = $age >= $max_age;
            }
            else {
                $age = 0;
                $checked = false;
            }

            if ($age >= $min_age) {
                if (empty($list[$pagename]))
                    $list[$pagename] = $checked;
            }
        }
        return $list;
    }

    function removePages(&$request, $pages) {
        $ul = HTML::ul();
        $dbi = $request->getDbh();
        foreach ($pages as $name) {
            $name = str_replace(array('%5B','%5D'),array('[',']'),$name);
            $dbi->deletePage($name);
            $ul->pushContent(HTML::li(fmt("Removed page '%s' successfully.", $name)));
        }
        $dbi->touch();
        return HTML($ul,
                    HTML::p(_('All selected pages have been permanently removed.')));
    }
    
    function run($dbi, $argstr, &$request, $basepage) {
        if ($request->getArg('action') != 'browse')
            return $this->disabled("(action != 'browse')");
        
        $args = $this->getArgs($argstr, $request);
        if (!is_numeric($args['min_age']))
            $args['min_age'] = -1;
        $this->_args =& $args;
        
        if (!empty($args['exclude']))
            $exclude = explodePageList($args['exclude']);
        else
            $exclude = false;


        $p = $request->getArg('p');
        $post_args = $request->getArg('admin_remove');

        $next_action = 'select';
        $pages = array();
        
        if ($p && $request->isPost() &&
            !empty($post_args['remove']) && empty($post_args['cancel'])) {

            // FIXME: check individual PagePermissions
            if (!$request->_user->isAdmin()) {
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
                    $name = str_replace(array('%5B','%5D'),array('[',']'),$name);
                    $pages[$name] = $c;
                }
            }
        } elseif (is_array($p) && !$request->isPost()) { // from WikiAdminSelect
            $next_action = 'verify';
            foreach ($p as $name => $c) {
                $name = str_replace(array('%5B','%5D'),array('[',']'),$name);
                $pages[$name] = $c;
            }
            $request->setArg('p',false);
        }
        if ($next_action == 'select') {
            // List all pages to select from.
            $pages = $this->collectPages($pages, $dbi, $args['sortby'], $args['limit']);
        }
        $pagelist = new PageList_Selectable($args['info'], $exclude, 
                                            array('types' => 
                                                  array('remove'
                                                        => new _PageList_Column_remove('remove', _("Remove")))));
        $pagelist->addPageList($pages);

        $header = HTML::p();
        if ($next_action == 'verify') {
            $button_label = _("Yes");
            $header->pushContent(HTML::strong(
                _("Are you sure you want to permanently remove the selected files?")));
        }
        else {
            $button_label = _("Remove selected pages");
            $header->pushContent(_("Permanently remove the selected files:"),HTML::br());
            if ($args['min_age'] > 0) {
                $header->pushContent(
                    fmt("Also pages which have been deleted at least %s days.",
                        $args['min_age']));
            }
            else {
                $header->pushContent(_("List all pages."));
            }
            
            if ($args['max_age'] > 0) {
                $header->pushContent(
                    " ",
                    fmt("(Pages which have been deleted at least %s days are already checked.)",
                        $args['max_age']));
            }
        }


        $buttons = HTML::p(Button('submit:admin_remove[remove]', $button_label, 'wikiadmin'),
                           Button('submit:admin_remove[cancel]', _("Cancel"), 'button'));

        return HTML::form(array('action' => $request->getPostURL(),
                                'method' => 'post'),

                          $header,
                          
                          $pagelist->getContent(),

                          HiddenInputs($request->getArgs(),
                                        false,
                                        array('admin_remove')),

                          HiddenInputs(array('admin_remove[action]' => $next_action,
                                             'require_authority_for_post' => WIKIAUTH_ADMIN)),
                          $buttons);
    }
}

class _PageList_Column_remove extends _PageList_Column {
    function _getValue ($page_handle, &$revision_handle) {
        return Button(array('action' => 'remove'), _("Remove"),
                      $page_handle->getName());
    }
};


// $Log$
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
//

// Local Variables:
// mode: php
// tab-width: 8
// c-basic-offset: 4
// c-hanging-comment-ender-p: nil
// indent-tabs-mode: nil
// End:
?>
