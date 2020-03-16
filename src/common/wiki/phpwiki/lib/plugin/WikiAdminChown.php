<?php
// -*-php-*-
rcs_id('$Id: WikiAdminChown.php,v 1.8 2005/01/29 19:48:14 rurban Exp $');
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
 * Usage:   <?plugin WikiAdminChown s||=* ?> or called via WikiAdminSelect
 * @author:  Reini Urban <rurban@x-ray.at>
 *
 * KNOWN ISSUES:
 * Requires PHP 4.2 so far.
 */
require_once('lib/PageList.php');
require_once('lib/plugin/WikiAdminSelect.php');

class WikiPlugin_WikiAdminChown extends WikiPlugin_WikiAdminSelect
{
    public function getName()
    {
        return _("WikiAdminChown");
    }

    public function getDescription()
    {
        return _("Chown selected pages.");
    }

    public function getVersion()
    {
        return preg_replace(
            "/[Revision: $]/",
            '',
            "\$Revision: 1.8 $"
        );
    }

    public function getDefaultArguments()
    {
        return array_merge(
            PageList::supportedArgs(),
            array(
                   's'         => false,
                   'user'     => false,
                   /* Columns to include in listing */
                   'info'     => 'pagename,owner,mtime',
            )
        );
    }

    public function chownPages(&$dbi, &$request, $pages, $newowner)
    {
        $ul = HTML::ul();
        $count = 0;
        foreach ($pages as $name) {
            $page = $dbi->getPage($name);
            if (($owner = $page->getOwner()) and
                 $newowner != $owner) {
                if (!mayAccessPage('change', $name)) {
                    $ul->pushContent(HTML::li(fmt(
                        "Access denied to change page '%s'.",
                        WikiLink($name)
                    )));
                } else {
                    $page->set('owner', $newowner);
                    if ($page->get('owner') === $newowner) {
                        $ul->pushContent(HTML::li(fmt(
                            "Chown page '%s' to '%s'.",
                            WikiLink($name),
                            WikiLink($newowner)
                        )));
                        $count++;
                    } else {
                        $ul->pushContent(HTML::li(fmt(
                            "Couldn't chown page '%s' to '%s'.",
                            WikiLink($name),
                            $newowner
                        )));
                    }
                }
            }
        }
        if ($count) {
            $dbi->touch();
            return HTML($ul, HTML::p(fmt(
                "%s pages have been permanently changed.",
                $count
            )));
        } else {
            return HTML($ul, HTML::p(fmt("No pages changed.")));
        }
    }

    public function run($dbi, $argstr, &$request, $basepage)
    {
        return $this->disabled("This action is blocked by administrator. Sorry for the inconvenience !");
        if ($request->getArg('action') != 'browse') {
            if (!$request->getArg('action') == _("PhpWikiAdministration/Chown")) {
                return $this->disabled("(action != 'browse')");
            }
        }

        $args = $this->getArgs($argstr, $request);
        $this->_args = $args;
        if (empty($args['user'])) {
            $args['user'] = $request->_user->UserName();
        }
        /*if (!empty($args['exclude']))
            $exclude = explodePageList($args['exclude']);
        else
        $exclude = false;*/
        $this->preSelectS($args, $request);

        $p = $request->getArg('p');
        if (!$p) {
            $p = $this->_list;
        }
        $post_args = $request->getArg('admin_chown');
        if (!$request->isPost() and empty($post_args['user'])) {
            $post_args['user'] = $args['user'];
        }
        $next_action = 'select';
        $pages = array();
        if ($p && !$request->isPost()) {
            $pages = $p;
        }
        if ($p && $request->isPost() &&
            !empty($post_args['chown']) && empty($post_args['cancel'])) {
            // without individual PagePermissions:
            if (!ENABLE_PAGEPERM and !$request->_user->isAdmin()) {
                $request->_notAuthorized(WIKIAUTH_ADMIN);
                $this->disabled("! user->isAdmin");
            }
            // DONE: error message if not allowed.
            if ($post_args['action'] == 'verify') {
                // Real action
                return $this->chownPages(
                    $dbi,
                    $request,
                    array_keys($p),
                    $post_args['user']
                );
            }
            if ($post_args['action'] == 'select') {
                if (!empty($post_args['user'])) {
                    $next_action = 'verify';
                }
                foreach ($p as $name => $c) {
                    $pages[$name] = 1;
                }
            }
        }
        if ($next_action == 'select' and empty($pages)) {
            // List all pages to select from.
            $pages = $this->collectPages(
                $pages,
                $dbi,
                $args['sortby'],
                $args['limit'],
                $args['exclude']
            );
        }
        /* // let the user decide which info
         if ($next_action == 'verify') {
            $args['info'] = "checkbox,pagename,owner,mtime";
        }
        */
        $pagelist = new PageList_Selectable($args['info'], $args['exclude'], $args);
        $pagelist->addPageList($pages);

        $header = HTML::p();
        if ($next_action == 'verify') {
            $button_label = _("Yes");
            $header->pushContent(
                HTML::p(HTML::strong(
                    _("Are you sure you want to permanently chown the selected files?")
                ))
            );
            $header = $this->chownForm($header, $post_args);
        } else {
            $button_label = _("Chown selected pages");
            $header->pushContent(HTML::p(_("Select the pages to change the owner:")));
            $header = $this->chownForm($header, $post_args);
        }

        $buttons = HTML::p(
            Button('submit:admin_chown[chown]', $button_label, 'wikiadmin'),
            Button('submit:admin_chown[cancel]', _("Cancel"), 'button')
        );

        return HTML::form(
            array('action' => $request->getPostURL(),
                                'method' => 'post'),
            $header,
            $pagelist->getContent(),
            HiddenInputs(
                $request->getArgs(),
                false,
                array('admin_chown')
            ),
            HiddenInputs(array('admin_chown[action]' => $next_action)),
            ENABLE_PAGEPERM
                          ? ''
                          : HiddenInputs(array('require_authority_for_post' => WIKIAUTH_ADMIN)),
            $buttons
        );
    }

    public function chownForm(&$header, $post_args)
    {
        $header->pushContent(_("Chown") . " ");
        $header->pushContent(' ' . _("to") . ': ');
        $header->pushContent(HTML::input(array('name' => 'admin_chown[user]',
                                               'value' => $post_args['user'])));
        $header->pushContent(HTML::p());
        return $header;
    }
}

// $Log: WikiAdminChown.php,v $
// Revision 1.8  2005/01/29 19:48:14  rurban
// reformatting
//
// Revision 1.7  2004/11/23 15:17:19  rurban
// better support for case_exact search (not caseexact for consistency),
// plugin args simplification:
//   handle and explode exclude and pages argument in WikiPlugin::getArgs
//     and exclude in advance (at the sql level if possible)
//   handle sortby and limit from request override in WikiPlugin::getArgs
// ListSubpages: renamed pages to maxpages
//
// Revision 1.6  2004/06/16 10:38:59  rurban
// Disallow refernces in calls if the declaration is a reference
// ("allow_call_time_pass_reference clean").
//   PhpWiki is now allow_call_time_pass_reference = Off clean,
//   but several external libraries may not.
//   In detail these libs look to be affected (not tested):
//   * Pear_DB odbc
//   * adodb oracle
//
// Revision 1.5  2004/06/14 11:31:39  rurban
// renamed global $Theme to $WikiTheme (gforge nameclash)
// inherit PageList default options from PageList
//   default sortby=pagename
// use options in PageList_Selectable (limit, sortby, ...)
// added action revert, with button at action=diff
// added option regex to WikiAdminSearchReplace
//
// Revision 1.4  2004/06/13 15:33:20  rurban
// new support for arguments owner, author, creator in most relevant
// PageList plugins. in WikiAdmin* via preSelectS()
//
// Revision 1.3  2004/06/08 10:05:11  rurban
// simplified admin action shortcuts
//
// Revision 1.2  2004/06/07 18:59:42  rurban
// added Chown link to Owner in statusbar
//
// Revision 1.1  2004/06/07 17:58:58  rurban
// new chown plugin
// Local Variables:
// mode: php
// tab-width: 8
// c-basic-offset: 4
// c-hanging-comment-ender-p: nil
// indent-tabs-mode: nil
// End:
