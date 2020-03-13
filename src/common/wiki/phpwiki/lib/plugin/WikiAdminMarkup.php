<?php
// -*-php-*-
rcs_id('$Id: WikiAdminMarkup.php,v 1.1 2005/09/18 13:06:24 rurban Exp $');
/*
 Copyright 2005 $ThePhpWikiProgrammingTeam

 This file is not yet part of PhpWiki.

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
 * Usage:   <?plugin WikiAdminMarkup s||=* ?> or called via WikiAdminSelect
 * @author:  Reini Urban <rurban@x-ray.at>
 *
 * KNOWN ISSUES:
 * Requires PHP 4.2 so far.
 */
require_once('lib/PageList.php');
require_once('lib/plugin/WikiAdminSelect.php');

class WikiPlugin_WikiAdminMarkup extends WikiPlugin_WikiAdminSelect
{
    public function getName()
    {
        return _("WikiAdminMarkup");
    }

    public function getDescription()
    {
        return _("Change the markup type of selected pages.");
    }

    public function getVersion()
    {
        return preg_replace(
            "/[Revision: $]/",
            '',
            "\$Revision: 1.1 $"
        );
    }

    public function getDefaultArguments()
    {
        return array_merge(
            PageList::supportedArgs(),
            array(
                   's'         => false,
                   'markup'     => 2,
                   /* Columns to include in listing */
                   'info'     => 'pagename,markup,mtime',
            )
        );
    }

    public function chmarkupPages(&$dbi, &$request, $pages, $newmarkup)
    {
        $ul = HTML::ul();
        $count = 0;
        foreach ($pages as $name) {
            $page = $dbi->getPage($name);
            $current = $page->getCurrentRevision();
            $markup = $current->get('markup');
            if (!$markup or $newmarkup != $markup) {
                if (!mayAccessPage('change', $name)) {
                    $ul->pushContent(HTML::li(fmt(
                        "Access denied to change page '%s'.",
                        WikiLink($name)
                    )));
                } else {
                    $version = $current->getVersion();
                    $meta = $current->_data;
                    $meta['markup'] = $newmarkup;
                    // convert text?
                    $text = $current->getPackedContent();
                    $meta['summary'] = sprintf(_("WikiAdminMarkup from %s to %s"), $markup, $newmarkup);
                    $page->save($text, $version + 1, $meta);
                    $current = $page->getCurrentRevision();
                    if ($current->get('markup') === $newmarkup) {
                        $ul->pushContent(HTML::li(fmt(
                            "change page '%s' to markup type '%s'.",
                            WikiLink($name),
                            $newmarkup
                        )));
                        $count++;
                    } else {
                        $ul->pushContent(HTML::li(fmt(
                            "Couldn't change page '%s' to markup type '%s'.",
                            WikiLink($name),
                            $newmarkup
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
        if ($request->getArg('action') != 'browse') {
            if (!$request->getArg('action') == _("PhpWikiAdministration/Markup")) {
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
        $post_args = $request->getArg('admin_markup');
        if (!$request->isPost() and empty($post_args['markup'])) {
            $post_args['markup'] = $args['markup'];
        }
        $next_action = 'select';
        $pages = array();
        if ($p && !$request->isPost()) {
            $pages = $p;
        }
        if ($p && $request->isPost() &&
            !empty($post_args['button']) && empty($post_args['cancel'])) {
            // without individual PagePermissions:
            if (!ENABLE_PAGEPERM and !$request->_user->isAdmin()) {
                $request->_notAuthorized(WIKIAUTH_ADMIN);
                $this->disabled("! user->isAdmin");
            }
            // DONE: error message if not allowed.
            if ($post_args['action'] == 'verify') {
                // Real action
                return $this->chmarkupPages(
                    $dbi,
                    $request,
                    array_keys($p),
                    $post_args['markup']
                );
            }
            if ($post_args['action'] == 'select') {
                if (!empty($post_args['markup'])) {
                    $next_action = 'verify';
                }
                foreach ($p as $name => $c) {
                    $pages[$name] = 1;
                }
            }
        }
        if ($next_action == 'select' and empty($pages)) {
            $pages = $this->collectPages(
                $pages,
                $dbi,
                $args['sortby'],
                $args['limit'],
                $args['exclude']
            );
        }
        $pagelist = new PageList_Selectable($args['info'], $args['exclude'], $args);
        $pagelist->addPageList($pages);

        $header = HTML::p();
        if ($next_action == 'verify') {
            $button_label = _("Yes");
            $header->pushContent(
                HTML::p(HTML::strong(
                    _("Are you sure you want to permanently change the markup type of the selected files?")
                ))
            );
            $header = $this->chmarkupForm($header, $post_args);
        } else {
            $button_label = _("Change markup type");
            $header->pushContent(HTML::p(_("Select the pages to change the markup type:")));
            $header = $this->chmarkupForm($header, $post_args);
        }

        $buttons = HTML::p(
            Button('submit:admin_markup[button]', $button_label, 'wikiadmin'),
            Button('submit:admin_markup[cancel]', _("Cancel"), 'button')
        );

        return HTML::form(
            array('action' => $request->getPostURL(),
                                'method' => 'post'),
            $header,
            $pagelist->getContent(),
            HiddenInputs(
                $request->getArgs(),
                false,
                array('admin_markup')
            ),
            HiddenInputs(array('admin_markup[action]' => $next_action)),
            ENABLE_PAGEPERM
                          ? ''
                          : HiddenInputs(array('require_authority_for_post' => WIKIAUTH_ADMIN)),
            $buttons
        );
    }

    public function chmarkupForm(&$header, $post_args)
    {
        $header->pushContent(_("Change markup") . " ");
        $header->pushContent(' ' . _("to") . ': ');
        $header->pushContent(HTML::input(array('name' => 'admin_markup[markup]',
                                               'value' => $post_args['markup'])));
        $header->pushContent(HTML::p());
        return $header;
    }
}

// $Log: WikiAdminMarkup.php,v $
// Revision 1.1  2005/09/18 13:06:24  rurban
// already added to 1.3.11
// Local Variables:
// mode: php
// tab-width: 8
// c-basic-offset: 4
// c-hanging-comment-ender-p: nil
// indent-tabs-mode: nil
// End:
