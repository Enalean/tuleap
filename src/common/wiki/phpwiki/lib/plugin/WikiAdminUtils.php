<?php
// -*-php-*-
rcs_id('$Id: WikiAdminUtils.php,v 1.18 2005/09/10 11:30:40 rurban Exp $');
/**
 Copyright 2003, 2004 $ThePhpWikiProgrammingTeam

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
  valid actions:
        purge-cache
        purge-bad-pagenames
        purge-empty-pages
        access-restrictions
        email-verification
        convert-cached-html
 */
class WikiPlugin_WikiAdminUtils extends WikiPlugin
{
    public function getName()
    {
        return _("WikiAdminUtils");
    }

    public function getDescription()
    {
        return _("Miscellaneous utility functions for the Administrator.");
    }

    public function getVersion()
    {
        return preg_replace(
            "/[Revision: $]/",
            '',
            "\$Revision: 1.18 $"
        );
    }

    public function getDefaultArguments()
    {
        return array('action'           => '',
                     'label'        => '',
                     );
    }

    public function run($dbi, $argstr, &$request, $basepage)
    {
        $args = $this->getArgs($argstr, $request);
        $args['action'] = strtolower($args['action']);
        extract($args);

        if (!$action) {
            $this->error("No action specified");
        }
        if (!($default_label = $this->_getLabel($action))) {
            $this->error("Bad action");
        }
        if ($request->getArg('action') != 'browse') {
            return $this->disabled("(action != 'browse')");
        }

        $posted = $request->getArg('wikiadminutils');

        if ($request->isPost() and $posted['action'] == $action) { // a different form. we might have multiple
            $user = $request->getUser();
            if (!$user->isAdmin()) {
                $request->_notAuthorized(WIKIAUTH_ADMIN);
                return $this->error(_("You must be an administrator to use this plugin."));
            }
            return $this->do_action($request, $posted);
        }
        if (empty($label)) {
            $label = $default_label;
        }

        return $this->_makeButton($request, $args, $label);
    }

    public function _makeButton(&$request, $args, $label)
    {
        $args['return_url'] = $request->getURLtoSelf();
        return HTML::form(
            array('action' => $request->getPostURL(),
                                'method' => 'post'),
            HTML::p(Button('submit:', $label, 'wikiadmin')),
            HiddenInputs($args, 'wikiadminutils'),
            HiddenInputs(array('require_authority_for_post' =>
                                             WIKIAUTH_ADMIN)),
            HiddenInputs($request->getArgs())
        );
    }

    public function do_action(&$request, $args)
    {
        $method = strtolower('_do_' . str_replace('-', '_', $args['action']));
        if (!method_exists($this, $method)) {
            return $this->error("Bad action");
        }

        $message = call_user_func(array(&$this, $method), $request, $args);

        // display as seperate page or as alert?
        $alert = new Alert(
            _("WikiAdminUtils says:"),
            $message,
            array(_("Okay") => $args['return_url'])
        );
        $alert->show();         // noreturn
    }

    public function _getLabel($action)
    {
        $labels = array('purge-cache' => _("Purge Markup Cache"),
                        'purge-bad-pagenames' => _("Purge all Pages With Invalid Names"),
                        'purge-empty-pages' => _("Purge all empty, unreferenced Pages"));
        return @$labels[$action];
    }

    public function _do_purge_cache(&$request, $args)
    {
        $dbi = $request->getDbh();
        $pages = $dbi->getAllPages('include_empty'); // Do we really want the empty ones too?
        while (($page = $pages->next())) {
            $page->set('_cached_html', false);
        }
        return _("Markup cache purged!");
    }

    public function _do_purge_bad_pagenames(&$request, $args)
    {
        // FIXME: this should be moved into WikiDB::normalize() or something...
        $dbi = $request->getDbh();
        $count = 0;
        $list = HTML::ol(array('align' => 'left'));
        $pages = $dbi->getAllPages('include_empty'); // Do we really want the empty ones too?
        while (($page = $pages->next())) {
            $pagename = $page->getName();
            $wpn = new WikiPageName($pagename);
            if (! $wpn->isValid()) {
                $dbi->purgePage($pagename);
                $list->pushContent(HTML::li($pagename));
                $count++;
            }
        }
        $pages->free();
        if (!$count) {
            return _("No pages with bad names had to be deleted.");
        } else {
            return HTML(
                fmt("Deleted %s pages with invalid names:", $count),
                HTML::div(array('align' => 'left'), $list)
            );
        }
    }

    /**
     * Purge all non-referenced empty pages. Mainly those created by bad link extraction.
     */
    public function _do_purge_empty_pages(&$request, $args)
    {
        $dbi = $request->getDbh();
        $count = 0;
        $notpurgable = 0;
        $list = HTML::ol(array('align' => 'left'));
        $pages = $dbi->getAllPages('include_empty');
        while (($page = $pages->next())) {
            if (!$page->exists() and ($links = $page->getBackLinks('include_empty')) and !$links->next()) {
                $pagename = $page->getName();
                if ($pagename == 'global_data' or $pagename == '.') {
                    continue;
                }
                if ($dbi->purgePage($pagename)) {
                    $list->pushContent(HTML::li($pagename . ' ' . _("[purged]")));
                } else {
                    $list->pushContent(HTML::li($pagename . ' ' . _("[not purgable]")));
                    $notpurgable++;
                }
                $count++;
            }
        }
        $pages->free();
        if (!$count) {
            return _("No empty, unreferenced pages were found.");
        } else {
            return HTML(
                fmt("Deleted %s unreferenced pages:", $count),
                HTML::div(array('align' => 'left'), $list),
                ($notpurgable ?
                fmt(
                    "The %d not-purgable pages/links are links in some page(s). You might want to edit them.",
                    $notpurgable
                )
                : '')
            );
        }
    }


    public function _do_convert_cached_html(&$request, $args)
    {
        return $this->disabled("This action is blocked by administrator. Sorry for the inconvenience !");
    }


    //TODO: We need a seperate plugin for this.
    //      Too many options.
    public function _do_access_restrictions(&$request, &$args)
    {
        return _("Sorry. Access Restrictions not yet implemented");
    }

    // pagelist with enable/disable button
    public function _do_email_verification(&$request, &$args)
    {
        return $this->disabled("This action is blocked by administrator. Sorry for the inconvenience !");
        $dbi = $request->getDbh();
        $pagelist = new PageList('pagename', 0, $args);
        //$args['return_url'] = 'action=email-verification-verified';
        $email = new _PageList_Column_email('email', _("E-Mail"), 'left');
        $emailVerified = new _PageList_Column_emailVerified(
            'emailVerified',
            _("Verification Status"),
            'center'
        );
        $pagelist->_columns[] = $email;
        $pagelist->_columns[] = $emailVerified;
        //This is the best method to find all users (Db and PersonalPage)
        $current_user = $request->_user;
        if (empty($args['verify'])) {
            $group = $request->getGroup();
            $allusers = $group->_allUsers();
        } else {
            $allusers = array_keys($args['user']);
        }
        foreach ($allusers as $username) {
            if (ENABLE_USER_NEW) {
                $user = WikiUser($username);
            } else {
                $user = new WikiUser($request, $username);
            }
            $prefs = $user->getPreferences();
            if ($prefs->get('email')) {
                if (!$prefs->get('userid')) {
                    $prefs->set('userid', $username);
                }
                $group = (int) (count($pagelist->_rows) / $pagelist->_group_rows);
                $class = ($group % 2) ? 'oddrow' : 'evenrow';
                $row = HTML::tr(array('class' => $class));
                $page_handle = $dbi->getPage($username);
                $row->pushContent($pagelist->_columns[0]->format(
                    $pagelist,
                    $page_handle,
                    $page_handle
                ));
                $row->pushContent($email->format($pagelist, $prefs, $page_handle));
                if (!empty($args['verify'])) {
                    $prefs->_prefs['email']->set(
                        'emailVerified',
                        empty($args['verified'][$username]) ? 0 : 2
                    );
                    $user->setPreferences($prefs);
                }
                $row->pushContent($emailVerified->format($pagelist, $prefs, $args['verify']));
                $pagelist->_rows[] = $row;
            }
        }
        $request->_user = $current_user;
        if (!empty($args['verify'])) {
            return HTML($pagelist->_generateTable(false));
        } else {
            $args['verify'] = 1;
            $args['return_url'] = $request->getURLtoSelf();
            return HTML::form(
                array('action' => $request->getPostURL(),
                                    'method' => 'post'),
                HiddenInputs($args, 'wikiadminutils'),
                HiddenInputs(array('require_authority_for_post' =>
                                             WIKIAUTH_ADMIN)),
                HiddenInputs($request->getArgs()),
                $pagelist->_generateTable(false),
                HTML::p(
                    Button(
                        'submit:',
                        _("Change Verification Status"),
                        'wikiadmin'
                    ),
                    HTML::Raw('&nbsp;'),
                    Button('cancel', _("Cancel"))
                )
            );
        }
    }
}

require_once("lib/PageList.php");

class _PageList_Column_email extends _PageList_Column
{
    public function _getValue(&$prefs, $dummy)
    {
        return $prefs->get('email');
    }
}

class _PageList_Column_emailVerified extends _PageList_Column
{
    public function _getValue(&$prefs, $status)
    {
        $name = $prefs->get('userid');
        $input = HTML::input(array('type' => 'checkbox',
                                   'name' => 'wikiadminutils[verified][' . $name . ']',
                                   'value' => 1));
        if ($prefs->get('emailVerified')) {
            $input->setAttr('checked', '1');
        }
        if ($status) {
            $input->setAttr('disabled', '1');
        }
        return HTML($input, HTML::input(array('type' => 'hidden',
                           'name' => 'wikiadminutils[user][' . $name . ']',
        'value' => $name)));
    }
}


// For emacs users
// Local Variables:
// mode: php
// tab-width: 8
// c-basic-offset: 4
// c-hanging-comment-ender-p: nil
// indent-tabs-mode: nil
// End:
