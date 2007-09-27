<?php // -*-php-*-
rcs_id('$Id: WhoIsOnline.php,v 1.11 2005/02/02 19:39:42 rurban Exp $');
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
 * Show summary information of the current user sessions.
 * We support two modes: summary and detail. The optional page argument 
 * links to the page with the other mode.
 *
 * Formatting and idea borrowed from postnuke. Requires USE_DB_SESSION.
 * Works with PearDB, ADODB and dba DbSessions.
 *
 * Author: Reini Urban
 */

class WikiPlugin_WhoIsOnline
extends WikiPlugin
{
    function getName () {
        return _("WhoIsOnline");
    }

    function getDescription () {
        return _("Show summary information of the current user sessions.");
    }

    function getVersion() {
        return preg_replace("/[Revision: $]/", '',
                            "\$Revision: 1.11 $");
    }

    function getDefaultArguments() {
        // two modes: summary and detail, page links to the page with the other mode
        return array(
                     'mode' 	    => 'summary',    // or "detail"
                     'pagename'     => '[pagename]', // refer to the page with the other mode
                     'allow_detail' => false,        // if false, page is ignored
                     'dispose_admin' => false,
                     );
    }

    function run($dbi, $argstr, &$request, $basepage) {
        global $WikiTheme;
        $request->setArg('nocache',1);
        $args = $this->getArgs($argstr, $request);
        // use the "online.tmpl" template
        // todo: check which arguments are really needed in the template.
        $stats = $this->getStats($dbi,$request,$args['mode']);
        if ($src = $WikiTheme->getImageURL("whosonline"))
            $img = HTML::img(array('src' => $src,
                                   'alt' => $this->getName(),
                                   'border' => 0));
        else $img = '';
        $other = array(); 
        $other['ONLINE_ICON'] = $img;
        return new Template('online', $request, array_merge($args, $stats, $other));
    }

    // box is used to display a fixed-width, narrow version with common header
    // just the number of online users.
    function box($args=false, $request=false, $basepage=false) {
        if (!$request) $request =& $GLOBALS['request'];
        $stats = $this->getStats($request->_dbi,$request,'summary');
        return $this->makeBox(_("Who is online"),
                              HTML(HTML::Raw('&middot; '),
                                   WikiLink(_("WhoIsOnline"),'auto',
                                            fmt("%d online users", $stats['NUM_USERS']))));
    }

    function getSessions($dbi, &$request) {
        // check the current user sessions and what they are doing
        ;
    }

    // check the current sessions
    function getStats($dbi, &$request, $mode='summary') {
        $num_pages = 0; $num_users = 0;
        $page_iter = $dbi->getAllPages();
        while ($page = $page_iter->next()) {
            if ($page->isUserPage()) $num_users++;
            $num_pages++;
        }
        //get session data from database
        $num_online = 0; $num_guests = 0; $num_registered = 0;
        $registered = array(); $guests = array();
        $admins = array(); $uniquenames = array();
        if (isset($request->_dbsession)) { // only ADODB and SQL backends
            $dbsession = &$request->_dbsession;
            $sessions = $dbsession->currentSessions();
            //$num_online = count($sessions);
            $guestname = _("Guest");
            foreach ($sessions as $row) {
                $data = $row['wiki_user'];
                $date = $row['date'];
                //Todo: Security issue: Expose REMOTE_ADDR?
                //      Probably only to WikiAdmin
                $ip   = $row['ip'];  
                if (empty($date)) continue;
                $num_online++;
                $user = @unserialize($data);
                if (!empty($user) and !isa($user, "__PHP_incomplete_Class")) {
                    // if "__PHP_incomplete_Class" try to avoid notice
                    $userid = @$user->_userid;
                    $level = @$user->_level;
                    if ($mode == 'summary' and in_array($userid, $uniquenames)) continue;
                    $uniquenames[] = $userid;
                    $page = _("<unknown>");  // where is he?
	            $action = 'browse';
	            $objvars = array_keys(get_object_vars($user));
                    if (in_array('action',$objvars))
                        $action = @$user->action;
                    if (in_array('page',$objvars))
                        $page = @$user->page;
                    if ($level and $userid) { // registered or guest or what?
                        //FIXME: htmlentitities name may not be called here. but where then?
                        $num_registered++;
                        $registered[] = array('name'  => $userid,
                                              'date'  => $date,
                                              'action'=> $action,
                                              'page'  => $page,
                                              'level' => $level,
                                              'ip'    => $ip,
                                              'x'     => 'x');
                        if ($user->_level == WIKIAUTH_ADMIN)
                           $admins[] = $registered[count($registered)-1];
                    } else {
                        $num_guests++;
                        $guests[] = array('name'  => $guestname,
                                          'date'  => $date,
                                          'action'=> $action,
                                          'page'  => $page,
                                          'level' => $level,
                                          'ip'    => $ip,
                                          'x'     => 'x');
                    }
                } else {
                    $num_guests++;
                    $guests[] = array('name'  => $guestname,
                                      'date'  => $date,
                                      'action'=> '',
                                      'page'  => '',
                                      'level' => 0,
                                      'ip'    => $ip,
                                      'x'     => 'x');
                }
            }
        }
        $num_users = $num_guests + $num_registered;

	$sess_time = ini_get('session.gc_maxlifetime'); // in seconds

        //TODO: get and sets max stats in global_data
        //$page = $dbi->getPage($request->getArg('pagename'));
        $stats = array(); $stats['max_online_num'] = 0;
        if ($stats = $dbi->get('stats')) {
            if ($num_users > $stats['max_online_num']) {
                $stats['max_online_num'] = $num_users;
                $stats['max_online_time'] = time();
                $dbi->set('stats',$stats);
            }
        } else {
            $stats['max_online_num'] = $num_users;
            $stats['max_online_time'] = time();
            $dbi->set('stats',$stats);
        }
        return array('SESSDATA_BOOL'    => !empty($dbsession),
                     'NUM_PAGES' 	=> $num_pages,
                     'NUM_USERS'  	=> $num_users,
                     'NUM_ONLINE' 	=> $num_online,
                     'NUM_REGISTERED' 	=> $num_registered,
                     'NUM_GUESTS'	=> $num_guests,
                     'NEWEST_USER' 	=> '', // todo
                     'MAX_ONLINE_NUM' 	=> $stats['max_online_num'],
                     'MAX_ONLINE_TIME' 	=> $stats['max_online_time'],
                     'REGISTERED' 	=> $registered,
                     'ADMINS' 	        => $admins,
                     'GUESTS'           => $guests,
                     'SESSION_TIME' 	=> sprintf(_("%d minutes"),$sess_time / 60),
                     );
    }
};

// $Log: WhoIsOnline.php,v $
// Revision 1.11  2005/02/02 19:39:42  rurban
// better box layout
//
// Revision 1.10  2005/02/01 16:22:58  rurban
// avoid __PHP_incomplete_Class notice
//
// Revision 1.9  2004/12/18 17:04:24  rurban
// stabilize not to call UserName() of an incomplete (not loaded) object
//
// Revision 1.8  2004/06/14 11:31:39  rurban
// renamed global $Theme to $WikiTheme (gforge nameclash)
// inherit PageList default options from PageList
//   default sortby=pagename
// use options in PageList_Selectable (limit, sortby, ...)
// added action revert, with button at action=diff
// added option regex to WikiAdminSearchReplace
//
// Revision 1.7  2004/05/27 17:49:06  rurban
// renamed DB_Session to DbSession (in CVS also)
// added WikiDB->getParam and WikiDB->getAuthParam method to get rid of globals
// remove leading slash in error message
// added force_unlock parameter to File_Passwd (no return on stale locks)
// fixed adodb session AffectedRows
// added FileFinder helpers to unify local filenames and DATA_PATH names
// editpage.php: new edit toolbar javascript on ENABLE_EDIT_TOOLBAR
//
// Revision 1.6  2004/05/02 15:10:08  rurban
// new finally reliable way to detect if /index.php is called directly
//   and if to include lib/main.php
// new global AllActionPages
// SetupWiki now loads all mandatory pages: HOME_PAGE, action pages, and warns if not.
// WikiTranslation what=buttons for Carsten to create the missing MacOSX buttons
// PageGroupTestOne => subpages
// renamed PhpWikiRss to PhpWikiRecentChanges
// more docs, default configs, ...
//
// Revision 1.5  2004/04/06 20:27:05  rurban
// fixed guests (no wiki_user session)
// added ip (to help in ip-throttling)
//
// Revision 1.4  2004/03/30 02:14:04  rurban
// fixed yet another Prefs bug
// added generic PearDb_iter
// $request->appendValidators no so strict as before
// added some box plugin methods
// PageList commalist for condensed output
//
// Revision 1.3  2004/03/12 15:48:08  rurban
// fixed explodePageList: wrong sortby argument order in UnfoldSubpages
// simplified lib/stdlib.php:explodePageList
//
// Revision 1.2  2004/03/10 15:38:49  rurban
// store current user->page and ->action in session for WhoIsOnline
// better WhoIsOnline icon
// fixed WhoIsOnline warnings
//
// Revision 1.1  2004/02/26 19:15:37  rurban
// new WhoIsOnline plugin: session explorer (postnuke style)
//
//

// For emacs users
// Local Variables:
// mode: php
// tab-width: 8
// c-basic-offset: 4
// c-hanging-comment-ender-p: nil
// indent-tabs-mode: nil
// End:
?>