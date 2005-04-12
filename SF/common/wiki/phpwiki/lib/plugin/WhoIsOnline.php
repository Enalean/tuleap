<?php // -*-php-*-
rcs_id('$Id$');
/*
 Copyright 2004 $ThePhpWikiProgrammingTeam
 
 This file is (not yet) part of PhpWiki.

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
 * Works with PearDB, ADODB and dba DB_Sessions.
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
                            "\$Revision$");
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
        global $Theme;
        $request->setArg('nocache',1);
        $args = $this->getArgs($argstr, $request);
        // use the "online.tmpl" template
        // todo: check which arguments are really needed in the template.
        $stats = $this->getStats($dbi,$request,$args['mode']);
        if ($src = $Theme->getImageURL("whosonline"))
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
        return $this->makeBox(WikiLink(_("WhoIsOnline"),'',_("Who is online")),
                              fmt("%d online users",$stats['NUM_USERS']));
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
                if (!empty($user)) {
                    if ($mode == 'summary' and in_array($user->UserName(),$uniquenames)) continue;
                    $uniquenames[] = $user->UserName();
                    $page = _("<unknown>");  // where is he?
	            $action = 'browse';
	            $objvars = array_keys(get_object_vars($user));
                    if (in_array('action',$objvars))
                        $action = $user->action;
                    if (in_array('page',$objvars))
                        $page = $user->page;
                    if ($user->_level and $user->UserName()) { // registered or guest or what?
                        //FIXME: htmlentitities name may not be called here. but where then?
                        $num_registered++;
                        $registered[] = array('name'  => $user->UserName(),
                                              'date'  => $date,
                                              'action'=> $action,
                                              'page'  => $page,
                                              'level' => $user->_level,
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
                                          'level' => $user->_level,
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

// $Log$
// Revision 1.1  2005/04/12 13:33:34  guerin
// First commit for wiki integration.
// Added Manuel's code as of revision 13 on Partners.
// Very little modification at the moment:
// - removed use of DOCUMENT_ROOT and SF_LOCAL_INC_PREFIX
// - simplified require syntax
// - removed ST-specific code (for test phase)
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