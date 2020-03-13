<?php
// -*-php-*-
rcs_id('$Id: WikiAdminSelect.php,v 1.23 2005/09/14 06:06:09 rurban Exp $');
/*
 Copyright 2002 $ThePhpWikiProgrammingTeam

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
 * Allows selection of multiple pages which get passed to other
 * WikiAdmin plugins then. Then do Rename, Remove, Chmod, Chown, ...
 *
 * Usage:   <?plugin WikiAdminSelect?>
 * Author:  Reini Urban <rurban@x-ray.at>
 *
 * This is the base class for most WikiAdmin* classes, using
 * collectPages() and preSelectS().
 * "list" PagePermissions supported implicitly by PageList.
 */
require_once('lib/PageList.php');

class WikiPlugin_WikiAdminSelect extends WikiPlugin
{
    public function getName()
    {
        return _("WikiAdminSelect");
    }

    public function getDescription()
    {
        return _("Allows selection of multiple pages which get passed to other WikiAdmin plugins.");
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
        return array('s'       => '', // preselect pages
                     /* select pages by meta-data: */
                     'author'   => false,
                     'owner'    => false,
                     'creator'  => false,

                     'only'    => '',
                     'exclude' => '',
                     'info'    => 'most',
                     'sortby'  => 'pagename',
                     'limit'    => 150,
                     'debug'   => false);
    }

    /**
     * Default collector for all WikiAdmin* plugins.
     * preSelectS() is similar, but fills $this->_list
     */
    public function collectPages(&$list, &$dbi, $sortby, $limit = 0, $exclude = false)
    {
        $allPages = $dbi->getAllPages(0, $sortby, $limit, $exclude);
        while ($pagehandle = $allPages->next()) {
            $pagename = $pagehandle->getName();
            if (empty($list[$pagename])) {
                $list[$pagename] = 0;
            }
        }
        return $list;
    }

    /**
     * Preselect a list of pagenames by the supporting the follwing args:
     * 's': comma-seperated list of pagename wildcards
     * 'author', 'owner', 'creator': from WikiDB_Page
     * 'only: forgot what the diffrrence to 's' was.
     * Sets $this->_list, which is picked up by collectPages() and is a default for p[]
     */
    public function preSelectS(&$args, &$request)
    {
        // override plugin argument by GET: probably not needed if s||="" is used
        // anyway, we force it for unique interface.
        if (!empty($request->getArg['s'])) {
            $args['s'] = $request->getArg['s'];
        }
        if (!empty($args['owner'])) {
            $sl = PageList::allPagesByOwner($args['owner'], false, $args['sortby'], $args['limit'], $args['exclude']);
        } elseif (!empty($args['author'])) {
            $sl = PageList::allPagesByAuthor($args['author'], false, $args['sortby'], $args['limit'], $args['exclude']);
        } elseif (!empty($args['creator'])) {
            $sl = PageList::allPagesByCreator($args['creator'], false, $args['sortby'], $args['limit'], $args['exclude']);
        } elseif (!empty($args['s']) or !empty($args['only'])) {
            // all pages by name
            $sl = explodePageList(empty($args['only']) ? $args['s'] : $args['only']);
        }
        $this->_list = array();
        if (!empty($sl)) {
            $request->setArg('verify', 1);
            foreach ($sl as $name) {
                if (!empty($args['exclude'])) {
                    if (!in_array($name, $args['exclude'])) {
                        $this->_list[$name] = 1;
                    }
                } else {
                    $this->_list[$name] = 1;
                }
            }
        }
        return $this->_list;
    }

    public function run($dbi, $argstr, &$request, $basepage)
    {
        //if ($request->getArg('action') != 'browse')
        //    return $this->disabled("(action != 'browse')");
        $args = $this->getArgs($argstr, $request);
        $this->_args = $args;
        extract($args);
        $this->preSelectS($args, $request);

        $info = $args['info'];
        $this->debug = $args['debug'];

        // array_multisort($this->_list, SORT_NUMERIC, SORT_DESC);
        $pagename = $request->getArg('pagename');
        // GetUrlToSelf() with all given params
        //$uri = $GLOBALS['HTTP_SERVER_VARS']['REQUEST_URI']; // without s would be better.
        //$uri = $request->getURLtoSelf();//false, array('verify'));
        $form = HTML::form(array('action' => $request->getPostURL(), 'method' => 'POST'));
        if ($request->getArg('WikiAdminSelect') == _("Go")) {
            $p = false;
        } else {
            $p = $request->getArg('p');
        }

        $form->pushContent(HTML::p(
            array('class' => 'wikitext'),
            _("Select: "),
            HTML::input(array('type' => 'text',
                                                     'name' => 's',
                                                     'value' => $args['s'])),
            HTML::input(array('type' => 'submit',
                                                     'name' => 'WikiAdminSelect',
            'value' => _("Go")))
        ));
        if ($request->isPost()
            && ! $request->getArg('wikiadmin')
            && !empty($p)) {
            $this->_list = array();
            // List all selected pages again.
            foreach ($p as $page => $name) {
                $this->_list[$name] = 1;
            }
        } elseif ($request->isPost()
            and $request->_user->isAdmin()
                and !empty($p)
                //and $request->getArg('verify')
                and ($request->getArg('action') == 'WikiAdminSelect')
                and $request->getArg('wikiadmin')
               ) {
            // handle external plugin
            $loader = new WikiPluginLoader();
            $a = array_keys($request->getArg('wikiadmin'));
            $plugin_action = $a[0];
            $single_arg_plugins = array("Remove");
            if (in_array($plugin_action, $single_arg_plugins)) {
                $plugin = $loader->getPlugin($plugin_action);
                $ul = HTML::ul();
                foreach ($p as $page => $name) {
                    $plugin_args = "run_page=$name";
                    $request->setArg($plugin_action, 1);
                    $request->setArg('p', array($page => $name));
                    // if the plugin requires more args than the pagename,
                    // then this plugin will not return. (Rename, SearchReplace, ...)
                    $action_result = $plugin->run($dbi, $plugin_args, $request, $basepage);
                    $ul->pushContent(HTML::li(fmt(
                        "Selected page '%s' passed to '%s'.",
                        $name,
                        $select
                    )));
                    $ul->pushContent(HTML::ul(HTML::li($action_result)));
                }
            } else {
                // redirect to the plugin page.
                // in which page is this plugin?
                $plugin_action = preg_replace("/^WikiAdmin/", "", $plugin_action);
                $args = array();
                foreach ($p as $page => $x) {
                    $args["p[$page]"] = 1;
                }
                header("Location: " .
                  WikiURL(_("PhpWikiAdministration") . "/" . _($plugin_action), $args, 1));
                exit();
            }
        } elseif (empty($args['s'])) {
            // List all pages to select from.
            $this->_list = $this->collectPages($this->_list, $dbi, $args['sortby'], $args['limit']);
        }
        $pagelist = new PageList_Selectable($info, $args['exclude'], $args);
        $pagelist->addPageList($this->_list);
        $form->pushContent($pagelist->getContent());
        foreach ($args as $k => $v) {
            if (!in_array($k, array('s','WikiAdminSelect','action','verify'))) {
                $form->pushContent(HiddenInputs(array($k => $v))); // plugin params
            }
        }
        /*
        foreach ($_GET as $k => $v) {
            if (!in_array($k,array('s','WikiAdminSelect','action')))
                $form->pushContent(HiddenInputs(array($k => $v))); // debugging params, ...
        }
        */
        if (! $request->getArg('verify')) {
            $form->pushContent(HTML::input(array('type' => 'hidden',
                                                 'name' => 'action',
                                                 'value' => 'verify')));
            $form->pushContent(
                Button(
                    'submit:verify',
                    _("Select pages"),
                    'wikiadmin'
                ),
                Button('submit:cancel', _("Cancel"), 'button')
            );
        } else {
            global $WikiTheme;
            $form->pushContent(HTML::input(array('type' => 'hidden',
                                                 'name' => 'action',
                                                 'value' => 'WikiAdminSelect')));
            // Add the Buttons for all registered WikiAdmin plugins
            $plugin_dir = 'lib/plugin';
            if (defined('PHPWIKI_DIR')) {
                $plugin_dir = PHPWIKI_DIR . "/$plugin_dir";
            }
            $fs = new fileSet($plugin_dir, 'WikiAdmin*.php');
            $actions = $fs->getFiles();
            foreach ($actions as $f) {
                $f = preg_replace('/.php$/', '', $f);
                $s = preg_replace('/^WikiAdmin/', '', $f);
                if (!in_array($s, array("Select","Utils"))) { // disable Select and Utils
                    $form->pushContent(Button("submit:wikiadmin[$f]", _($s), "wikiadmin"));
                    $form->pushContent($WikiTheme->getButtonSeparator());
                }
            }
            $form->pushContent(Button('submit:cancel', _("Cancel"), 'button'));
        }
        if (! $request->getArg('select')) {
            return $form;
        } else {//return $action_result;
        }
    }
}

// $Log: WikiAdminSelect.php,v $
// Revision 1.23  2005/09/14 06:06:09  rurban
// use a sane limit of 150
//
// Revision 1.22  2004/12/06 19:50:05  rurban
// enable action=remove which is undoable and seeable in RecentChanges: ADODB ony for now.
// renamed delete_page to purge_page.
// enable action=edit&version=-1 to force creation of a new version.
// added BABYCART_PATH config
// fixed magiqc in adodb.inc.php
// and some more docs
//
// Revision 1.21  2004/11/23 15:17:20  rurban
// better support for case_exact search (not caseexact for consistency),
// plugin args simplification:
//   handle and explode exclude and pages argument in WikiPlugin::getArgs
//     and exclude in advance (at the sql level if possible)
//   handle sortby and limit from request override in WikiPlugin::getArgs
// ListSubpages: renamed pages to maxpages
//
// Revision 1.20  2004/10/04 23:39:34  rurban
// just aesthetics
//
// Revision 1.19  2004/06/29 08:52:24  rurban
// Use ...version() $need_content argument in WikiDB also:
// To reduce the memory footprint for larger sets of pagelists,
// we don't cache the content (only true or false) and
// we purge the pagedata (_cached_html) also.
// _cached_html is only cached for the current pagename.
// => Vastly improved page existance check, ACL check, ...
//
// Now only PagedList info=content or size needs the whole content, esp. if sortable.
//
// Revision 1.18  2004/06/16 10:38:59  rurban
// Disallow refernces in calls if the declaration is a reference
// ("allow_call_time_pass_reference clean").
//   PhpWiki is now allow_call_time_pass_reference = Off clean,
//   but several external libraries may not.
//   In detail these libs look to be affected (not tested):
//   * Pear_DB odbc
//   * adodb oracle
//
// Revision 1.17  2004/06/14 11:31:39  rurban
// renamed global $Theme to $WikiTheme (gforge nameclash)
// inherit PageList default options from PageList
//   default sortby=pagename
// use options in PageList_Selectable (limit, sortby, ...)
// added action revert, with button at action=diff
// added option regex to WikiAdminSearchReplace
//
// Revision 1.16  2004/06/13 15:33:20  rurban
// new support for arguments owner, author, creator in most relevant
// PageList plugins. in WikiAdmin* via preSelectS()
//
// Revision 1.15  2004/06/01 15:28:01  rurban
// AdminUser only ADMIN_USER not member of Administrators
// some RateIt improvements by dfrankow
// edit_toolbar buttons
//
// Revision 1.14  2004/02/24 15:20:07  rurban
// fixed minor warnings: unchecked args, POST => Get urls for sortby e.g.
//
// Revision 1.13  2004/02/22 23:20:33  rurban
// fixed DumpHtmlToDir,
// enhanced sortby handling in PageList
//   new button_heading th style (enabled),
// added sortby and limit support to the db backends and plugins
//   for paging support (<<prev, next>> links on long lists)
//
// Revision 1.12  2004/02/19 22:05:57  rurban
// Allow s arg from get requests (plugin-form as in PhpWikiAdministration)
//
// Revision 1.11  2004/02/17 12:11:36  rurban
// added missing 4th basepage arg at plugin->run() to almost all plugins. This caused no harm so far, because it was silently dropped on normal usage. However on plugin internal ->run invocations it failed. (InterWikiSearch, IncludeSiteMap, ...)
//
// Revision 1.10  2004/02/15 21:34:37  rurban
// PageList enhanced and improved.
// fixed new WikiAdmin... plugins
// editpage, Theme with exp. htmlarea framework
//   (htmlarea yet committed, this is really questionable)
// WikiUser... code with better session handling for prefs
// enhanced UserPreferences (again)
// RecentChanges for show_deleted: how should pages be deleted then?
//
// Revision 1.9  2004/02/12 13:05:50  rurban
// Rename functional for PearDB backend
// some other minor changes
// SiteMap comes with a not yet functional feature request: includepages (tbd)
//
// Revision 1.8  2004/02/11 20:00:16  rurban
// WikiAdmin... series overhaul. Rename misses the db backend methods yet. Chmod + Chwon still missing.
//
// Revision 1.7  2004/01/27 23:23:39  rurban
// renamed ->Username => _userid for consistency
// renamed mayCheckPassword => mayCheckPass
// fixed recursion problem in WikiUserNew
// fixed bogo login (but not quite 100% ready yet, password storage)
//
// Revision 1.6  2004/01/26 19:15:29  rurban
// Interim fix
//
// Revision 1.5  2003/02/24 19:38:04  dairiki
// Get rid of unused method Request::debugVars().
//
// Revision 1.4  2003/02/24 01:36:27  dairiki
// Don't use PHPWIKI_DIR unless it's defined.
// (Also typo/bugfix in SystemInfo plugin.)
//
// Revision 1.3  2003/02/22 20:49:56  dairiki
// Fixes for "Call-time pass by reference has been deprecated" errors.
//
// Revision 1.2  2003/01/18 22:14:29  carstenklapp
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
