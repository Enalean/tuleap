<?php // -*-php-*-
rcs_id('$Id$');
/*
 Copyright 2004 $ThePhpWikiProgrammingTeam

 This file is not yet part of PhpWiki. It does not work yet.

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
 * Currently we must be Admin. Later we use PagePermissions authorization.
 *   (require_authority_for_post' => WIKIAUTH_ADMIN)
 * Requires PHP 4.2 so far.
 */
require_once('lib/PageList.php');
require_once('lib/plugin/WikiAdminSelect.php');

class WikiPlugin_WikiAdminSetAcl
extends WikiPlugin_WikiAdminSelect
{
    function getName() {
        return _("WikiAdminSetAcl");
    }

    function getDescription() {
        return _("Set individual page permissions.");
    }

    function getVersion() {
        return preg_replace("/[Revision: $]/", '',
                            "\$Revision$");
    }

    function getDefaultArguments() {
        return array(
                     'p'        => "[]",
                     'acl'      => false,
                     /* Pages to exclude in listing */
                     'exclude'  => '',
                     /* Columns to include in listing */
                     'info'     => 'pagename,perm,owner,group,mtime,author',
                     /* How to sort */
                     'sortby'   => 'pagename',
                     'limit'    => 0,
                     );
    }

    function setaclPages(&$dbi, &$request, $pages, $acl) {
        $ul = HTML::ul();
        $count = 0;
        if ($perm = new PagePermission($acl)) {
          foreach ($pages as $name) {
            if ( $perm->store($dbi->getPage($name)) ) {
                /* not yet implemented for all backends */
                $ul->pushContent(HTML::li(fmt("set acl for page '%s'.",$name)));
                $count++;
            } else {
                $ul->pushContent(HTML::li(fmt("Couldn't setacl page '%s'.", $name)));
            }
          }
        } else {
            $ul->pushContent(HTML::li(fmt("Invalid acl")));
        }
        if ($count) {
            $dbi->touch();
            return HTML($ul,
                        HTML::p(fmt("%s pages have been changed.",$count)));
        } else {
            return HTML($ul,
                        HTML::p(fmt("No pages changed.")));
        }
    }
    
    function run($dbi, $argstr, &$request, $basepage) {
        if (!DEBUG)
            return $this->disabled("WikiAdminSetAcl not yet enabled. Set DEBUG to try it.");
        
        $args = $this->getArgs($argstr, $request);
        $this->_args = $args;
        if (!empty($args['exclude']))
            $exclude = explodePageList($args['exclude']);
        else
            $exclude = false;

        $p = $request->getArg('p');
        $post_args = $request->getArg('admin_setacl');
        $next_action = 'select';
        $pages = array();
        if ($p && !$request->isPost())
            $pages = $p;
        if ($p && $request->isPost() &&
            !empty($post_args['acl']) && empty($post_args['cancel'])) {

            // FIXME: check individual PagePermissions
            if (!$request->_user->isAdmin()) {
                $request->_notAuthorized(WIKIAUTH_ADMIN);
                $this->disabled("! user->isAdmin");
            }

            if ($post_args['action'] == 'verify') {
                // Real action
                return $this->setaclPages($dbi, $request, array_keys($p), 
                                          $post_args['acl']);
            }
            if ($post_args['action'] == 'select') {
                if (!empty($post_args['acl']))
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
            $args['info'] = "checkbox,pagename,perm,owner,group,mtime,author";
        }
        $pagelist = new PageList_Selectable($args['info'], 
                                            $exclude,
                                            array('types' => array(
                                                  'perm'
                                                  => new _PageList_Column_perm('perm', _("Permission")),
                                                  'acl'
                                                  => new _PageList_Column_acl('acl', _("ACL")))));

        $pagelist->addPageList($pages);

        $header = HTML::p();
        if ($next_action == 'verify') {
            $button_label = _("Yes");
            $header = $this->setaclForm($header, $post_args, $pages);
            $header->pushContent(
              HTML::p(HTML::strong(
                                   _("Are you sure you want to permanently change the selected files?"))));
        }
        else {
            $button_label = _("SetAcl");
            $header = $this->setaclForm($header, $post_args, $pages);
            $header->pushContent(HTML::p(_("Select the pages to change:")));
        }

        $buttons = HTML::p(Button('submit:admin_setacl[acl]', $button_label, 'wikiadmin'),
                           Button('submit:admin_setacl[cancel]', _("Cancel"), 'button'));

        return HTML::form(array('action' => $request->getPostURL(),
                                'method' => 'post'),
                          $header,
                          $pagelist->getContent(),
                          HiddenInputs($request->getArgs(),
                                        false,
                                        array('admin_setacl')),
                          HiddenInputs(array('admin_setacl[action]' => $next_action,
                                             'require_authority_for_post' => WIKIAUTH_ADMIN)),
                          $buttons);
    }

    function setaclForm(&$header, $post_args, $pagehash) {
        $acl = $post_args['acl'];
        $header->pushContent(HTML::p(HTML::em(_("This plugin is currently under development and does not work!"))));
        //todo: find intersection of all page perms
        $pages = array();
        foreach ($pagehash as $name => $checked) {
	   if ($checked) $pages[] = $name;
        }
        $perm_tree = pagePermissions($name);
        $table = pagePermissionsAclFormat($perm_tree,true);
        $header->pushContent(HTML::p(fmt("Pages: %s",join(', ',$pages))));
        if (DEBUG) {
            $header->pushContent(HTML::pre("Permission tree for $name:\n",print_r($perm_tree,true)));
        }
        $type = $perm_tree[0];
        if ($type == 'inherited')
            $type = sprintf(_("page permission inherited from %s"),$perm_tree[1][0]);
        elseif ($type == 'page')
            $type = _("invidual page permission");
        elseif ($type == 'default')
            $type = _("default page permission");
        $header->pushContent(HTML::p(_("Type: "),$type));
        $header->pushContent(HTML::blockquote($table));
        //todo:
        // display array of checkboxes for existing perms
        // and a dropdown for user/group to add perms.
        // disabled if inherited, 
        // checkbox to disable inheritance, 
        // another checkbox to progate new permissions to all childs (if there exist some)
        // warn if more pages are selected and they have different perms
        //$header->pushContent(HTML::input(array('name' => 'admin_setacl[acl]',
        //                                       'value' => $post_args['acl'])));
        $header->pushContent(HTML::br());
        $checkbox = HTML::input(array('type' => 'checkbox',
                                      'name' => 'admin_setacl[updatechildren]',
                                      'value' => 1));
        if (!empty($post_args['updatechildren']))  $checkbox->setAttr('checked','checked');
        $header->pushContent($checkbox,
        	_("Propagate new permissions to all subpages?"),
        	HTML::raw("&nbsp;&nbsp;"),
        	HTML::em(_("(disable individual page permissions, enable inheritance)?")));
        $header->pushContent(HTML::hr(),HTML::p());
        return $header;
    }
}

class _PageList_Column_acl extends _PageList_Column {
    function _getValue ($page_handle, &$revision_handle) {
        $perm_tree = pagePermissions($page_handle->_pagename);
        return pagePermissionsAclFormat($perm_tree);
        if (0) {
            ob_start();
            var_dump($perm_array);
            $xml = ob_get_contents();
            ob_end_clean();
            return $xml;
        }
    }
};

class _PageList_Column_perm extends _PageList_Column {
    function _getValue ($page_handle, &$revision_handle) {
        $perm_array = pagePermissions($page_handle->_pagename);
        return pagePermissionsSimpleFormat($perm_array,
                                           $page_handle->get('author'),
                                           $page_handle->get('group'));
        if (0) {
            ob_start();
            var_dump($perm_array);
            $xml = ob_get_contents();
            ob_end_clean();
            return $xml;
        }
    }
};

// $Log$
// Revision 1.1  2005/04/12 13:33:34  guerin
// First commit for wiki integration.
// Added Manuel's code as of revision 13 on Partners.
// Very little modification at the moment:
// - removed use of DOCUMENT_ROOT and SF_LOCAL_INC_PREFIX
// - simplified require syntax
// - removed ST-specific code (for test phase)
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
