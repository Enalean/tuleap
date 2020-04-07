<?php
// -*-php-*-
rcs_id('$Id: AllUsers.php,v 1.18 2004/11/23 15:17:19 rurban Exp $');
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

require_once('lib/PageList.php');

/**
 * Based on AllPages and WikiGroup.
 *
 * We list all users,
 * either homepage users (prefs stored in a page),
 * users with db prefs and
 * externally authenticated users with a db users table, if auth_user_exists is defined.
 */
class WikiPlugin_AllUsers extends WikiPlugin
{
    public function getName()
    {
        return _("AllUsers");
    }

    public function getDescription()
    {
        return _("List all once authenticated users.");
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
        return array_merge(
            PageList::supportedArgs(),
            array('noheader'      => false,
                   'include_empty' => true,
                   'debug'         => false
            )
        );
    }
    // info arg allows multiple columns
    // info=mtime,hits,summary,version,author,locked,minor,markup or all
    // exclude arg allows multiple pagenames exclude=WikiAdmin,.SecretUser
    //
    // include_empty shows also users which stored their preferences,
    // but never saved their homepage
    //
    // sortby: [+|-] pagename|mtime|hits

    public function run($dbi, $argstr, &$request, $basepage)
    {
        $args = $this->getArgs($argstr, $request);
        extract($args);
        if ($debug) {
            $timer = new DebugTimer();
        }

        $group = $request->getGroup();
        if (method_exists($group, '_allUsers')) {
            $allusers = $group->_allUsers();
        } else {
            $allusers = array();
        }
        $args['count'] = count($allusers);
        // deleted pages show up as version 0.
        $pagelist = new PageList($info, $exclude, $args);
        if (!$noheader) {
            $pagelist->setCaption(_("Authenticated users on this wiki (%d total):"));
        }
        if ($include_empty and empty($info)) {
            $pagelist->_addColumn('version');
        }
        list($offset, $pagesize) = $pagelist->limit($args['limit']);
        if (!$pagesize) {
            $pagelist->addPageList($allusers);
        } else {
            for ($i = $offset; $i < $offset + $pagesize - 1; $i++) {
                if ($i >= $args['count']) {
                    break;
                }
                $pagelist->addPage($allusers[$i]);
            }
        }
        /*
        $page_iter = $dbi->getAllPages($include_empty, $sortby, $limit);
        while ($page = $page_iter->next()) {
            if ($page->isUserPage($include_empty))
                $pagelist->addPage($page);
        }
        */

        if ($debug) {
            return HTML(
                $pagelist,
                HTML::p(fmt("Elapsed time: %s s", $timer->getStats()))
            );
        } else {
            return $pagelist;
        }
    }
}

// $Log: AllUsers.php,v $
// Revision 1.18  2004/11/23 15:17:19  rurban
// better support for case_exact search (not caseexact for consistency),
// plugin args simplification:
//   handle and explode exclude and pages argument in WikiPlugin::getArgs
//     and exclude in advance (at the sql level if possible)
//   handle sortby and limit from request override in WikiPlugin::getArgs
// ListSubpages: renamed pages to maxpages
//
// Revision 1.17  2004/11/19 13:25:31  rurban
// clarify docs
//
// Revision 1.16  2004/09/25 16:37:18  rurban
// add support for all PageList options
//
// Revision 1.15  2004/07/08 13:50:33  rurban
// various unit test fixes: print error backtrace on _DEBUG_TRACE; allusers fix; new PHPWIKI_NOMAIN constant for omitting the mainloop
//
// Revision 1.14  2004/06/25 14:29:22  rurban
// WikiGroup refactoring:
//   global group attached to user, code for not_current user.
//   improved helpers for special groups (avoid double invocations)
// new experimental config option ENABLE_XHTML_XML (fails with IE, and document.write())
// fixed a XHTML validation error on userprefs.tmpl
//
// Revision 1.13  2004/06/16 10:38:59  rurban
// Disallow refernces in calls if the declaration is a reference
// ("allow_call_time_pass_reference clean").
//   PhpWiki is now allow_call_time_pass_reference = Off clean,
//   but several external libraries may not.
//   In detail these libs look to be affected (not tested):
//   * Pear_DB odbc
//   * adodb oracle
//
// Revision 1.12  2004/04/20 00:56:00  rurban
// more paging support and paging fix for shorter lists
//
// Revision 1.11  2004/03/10 13:54:54  rurban
// adodb WikiGroup fix
//
// Revision 1.10  2004/03/08 19:30:01  rurban
// fixed Theme->getButtonURL
// AllUsers uses now WikiGroup (also DB User and DB Pref users)
// PageList fix for empty pagenames
//
// Revision 1.9  2004/02/22 23:20:33  rurban
// fixed DumpHtmlToDir,
// enhanced sortby handling in PageList
//   new button_heading th style (enabled),
// added sortby and limit support to the db backends and plugins
//   for paging support (<<prev, next>> links on long lists)
//
// Revision 1.8  2004/02/17 12:11:36  rurban
// added missing 4th basepage arg at plugin->run() to almost all plugins. This caused no harm so far, because it was silently dropped on normal usage. However on plugin internal ->run invocations it failed. (InterWikiSearch, IncludeSiteMap, ...)
//
// Revision 1.7  2003/12/21 00:29:45  carstenklapp
// Minor bugfix: Fixed broken debug argument.
//
// Internal changes: Only create a DebugTimer when actually called for;
// moved debug message out of page content and into deferred page error
// notification via trigger_error. Memory management: Only include_once
// lib/PageList when absolutely necessary (at this time, this will
// probably only benefit the PluginManager as an incremental speedup &
// slightly reduced memory).
//
// Revision 1.6  2003/02/27 20:10:31  dairiki
// Disable profiling output when DEBUG is defined but false.
//
// Revision 1.5  2003/02/21 04:08:26  dairiki
// New class DebugTimer in prepend.php to help report timing.
//
// Revision 1.4  2003/01/18 21:19:25  carstenklapp
// Code cleanup:
// Reformatting; added copyleft, getVersion, getDescription
// Local Variables:
// mode: php
// tab-width: 8
// c-basic-offset: 4
// c-hanging-comment-ender-p: nil
// indent-tabs-mode: nil
// End:
