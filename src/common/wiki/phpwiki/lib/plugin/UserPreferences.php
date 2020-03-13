<?php
// -*-php-*-
rcs_id('$Id: UserPreferences.php,v 1.35 2004/10/13 14:13:55 rurban Exp $');
/**
 Copyright (C) 2001,2002,2003,2004,2005 $ThePhpWikiProgrammingTeam

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
 * Plugin to allow any user to adjust his own preferences.
 * This must be used in the page "UserPreferences".
 * Prefs are stored in metadata in the current session,
 *  within the user's home page or in a database.
 *
 * Theme extension: Themes are able to extend the predefined list
 * of preferences.
 */
class WikiPlugin_UserPreferences extends WikiPlugin
{
    public $bool_args;

    public function getName()
    {
        return _("UserPreferences");
    }

    public function getVersion()
    {
        return preg_replace(
            "/[Revision: $]/",
            '',
            "\$Revision: 1.35 $"
        );
    }

    public function getDefaultArguments()
    {
        global $request;
        $pagename = $request->getArg('pagename');
        $user = $request->getUser();
        if (isset($user->_prefs) and
             isset($user->_prefs->_prefs) and
             isset($user->_prefs->_method)) {
            $pref = $user->_prefs;
        } else {
            $pref = $user->getPreferences();
        }
        $prefs = array();
        //we need a hash of pref => default_value
        foreach ($pref->_prefs as $name => $obj) {
            $prefs[$name] = $obj->default_value;
        }
        return $prefs;
    }

    public function run($dbi, $argstr, &$request, $basepage)
    {
        $args = $this->getArgs($argstr, $request);
        $user = $request->_user;
        if (isa($request, 'MockRequest')) {
            return '';
        }
        if ((!$request->isActionPage($request->getArg('pagename'))
             and (!isset($user->_prefs->_method)
                  or !in_array($user->_prefs->_method, array('ADODB','SQL'))))
            or (in_array($request->getArg('action'), array('zip','ziphtml')))
            or (isa($user, '_ForbiddenUser'))) {
            $no_args = $this->getDefaultArguments();
// ?
//            foreach ($no_args as $key => $value) {
//                $no_args[$value] = false;
//            }
            $no_args['errmsg'] = HTML(HTML::h2(_("Error: The user HomePage must be a valid WikiWord. Sorry, UserPreferences cannot be saved."), HTML::hr()));
            $no_args['isForm'] = false;
            return Template('userprefs', $no_args);
        }
        $userid = $user->UserName();
        if (// ((defined('ALLOW_BOGO_LOGIN') && ALLOW_BOGO_LOGIN && $user->isSignedIn()) ||
             $user->isAuthenticated() and !empty($userid)) {
            $pref = &$request->_prefs;
            $args['isForm'] = true;
            //trigger_error("DEBUG: reading prefs from getPreferences".print_r($pref));

            if ($request->isPost()) {
                $errmsg = '';
                $delete = $request->getArg('delete');
                if ($delete and $request->getArg('verify')) {
                    // deleting prefs, verified
                    $default_prefs = $pref->defaultPreferences();
                    $default_prefs['userid'] = $user->UserName();
                    $user->setPreferences($default_prefs);
                    $request->_setUser($user);
                    $request->setArg("verify", false);
                    $request->setArg("delete", false);
                    $alert = new Alert(
                        _("Message"),
                        _("Your UserPreferences have been successfully deleted.")
                    );
                    $alert->show();
                    return;
                } elseif ($delete and !$request->getArg('verify')) {
                    return HTML::form(
                        array('action' => $request->getPostURL(),
                                            'method' => 'post'),
                        HiddenInputs(array('verify' => 1)),
                        HiddenInputs($request->getArgs()),
                        HTML::p(_("Do you really want to delete all your UserPreferences?")),
                        HTML::p(
                            Button('submit:delete', _("Yes"), 'delete'),
                            HTML::Raw('&nbsp;'),
                            Button('cancel', _("Cancel"))
                        )
                    );
                } elseif ($rp = $request->getArg('pref')) {
                    // replace only changed prefs in $pref with those from request
                    if (!empty($rp['passwd']) and ($rp['passwd2'] != $rp['passwd'])) {
                        $errmsg = _("Wrong password. Try again.");
                    } else {
                        //trigger_error("DEBUG: reading prefs from request".print_r($rp));
                        //trigger_error("DEBUG: writing prefs with setPreferences".print_r($pref));
                        if (empty($rp['passwd'])) {
                            unset($rp['passwd']);
                        }
                        // fix to set system pulldown's. empty values don't get posted
                        if (empty($rp['theme'])) {
                            $rp['theme'] = '';
                        }
                        if (empty($rp['lang'])) {
                            $rp['lang']  = '';
                        }
                        $num = $user->setPreferences($rp);
                        if (!empty($rp['passwd'])) {
                            $passchanged = false;
                            if ($user->mayChangePass()) {
                                if (method_exists($user, 'storePass')) {
                                    $passchanged = $user->storePass($rp['passwd']);
                                }
                                if (!$passchanged and method_exists($user, 'changePass')) {
                                    $passchanged = $user->changePass($rp['passwd']);
                                }
                                if ($passchanged) {
                                    $errmsg = _("Password updated.");
                                } else {
                                    $errmsg = _("Password was not changed.");
                                }
                            } else {
                                $errmsg = _("Password cannot be changed.");
                            }
                        }
                        if (!$num) {
                            $errmsg .= " " . _("No changes.");
                        } else {
                            $request->_setUser($user);
                            $pref = $user->_prefs;
                            $errmsg .= sprintf(_("%d UserPreferences fields successfully updated."), $num);
                        }
                    }
                    $args['errmsg'] = HTML(HTML::h2($errmsg), HTML::hr());
                }
            }
            $args['available_themes'] = listAvailableThemes();
            $args['available_languages'] = listAvailableLanguages();

            return Template('userprefs', $args);
        } else {
            // wrong or unauthenticated user
            return $request->_notAuthorized(WIKIAUTH_BOGO);
            //return $user->PrintLoginForm ($request, $args, false, false);
        }
    }
}

// $Log: UserPreferences.php,v $
// Revision 1.35  2004/10/13 14:13:55  rurban
// fix cannot edit prefs
//
// Revision 1.34  2004/10/05 00:10:49  rurban
// adjust for unittests. They finally pass all tests
//
// Revision 1.33  2004/10/04 23:39:34  rurban
// just aesthetics
//
// Revision 1.32  2004/06/28 12:51:41  rurban
// improved dumphtml and virgin setup
//
// Revision 1.31  2004/06/27 10:26:03  rurban
// oci8 patch by Philippe Vanhaesendonck + some ADODB notes+fixes
//
// Revision 1.30  2004/06/15 09:15:52  rurban
// IMPORTANT: fixed passwd handling for passwords stored in prefs:
//   fix encrypted usage, actually store and retrieve them from db
//   fix bogologin with passwd set.
// fix php crashes with call-time pass-by-reference (references wrongly used
//   in declaration AND call). This affected mainly Apache2 and IIS.
//   (Thanks to John Cole to detect this!)
//
// Revision 1.29  2004/05/06 13:26:01  rurban
// omit "Okay", this is default
//
// Revision 1.28  2004/05/05 13:38:09  rurban
// Support to remove all UserPreferences
//
// Revision 1.27  2004/05/03 13:16:47  rurban
// fixed UserPreferences update, esp for boolean and int
//
// Revision 1.26  2004/05/03 11:40:42  rurban
// put listAvailableLanguages() and listAvailableThemes() from SystemInfo and
// UserPreferences into Themes.php
//
// Revision 1.25  2004/04/07 23:13:19  rurban
// fixed pear/File_Passwd for Windows
// fixed FilePassUser sessions (filehandle revive) and password update
//
// Revision 1.24  2004/04/06 20:00:11  rurban
// Cleanup of special PageList column types
// Added support of plugin and theme specific Pagelist Types
// Added support for theme specific UserPreferences
// Added session support for ip-based throttling
//   sql table schema change: ALTER TABLE session ADD sess_ip CHAR(15);
// Enhanced postgres schema
// Added DB_Session_dba support
//
// Revision 1.23  2004/04/02 15:06:56  rurban
// fixed a nasty ADODB_mysql session update bug
// improved UserPreferences layout (tabled hints)
// fixed UserPreferences auth handling
// improved auth stability
// improved old cookie handling: fixed deletion of old cookies with paths
//
// Revision 1.22  2004/03/24 19:39:03  rurban
// php5 workaround code (plus some interim debugging code in XmlElement)
//   php5 doesn't work yet with the current XmlElement class constructors,
//   WikiUserNew does work better than php4.
// rewrote WikiUserNew user upgrading to ease php5 update
// fixed pref handling in WikiUserNew
// added Email Notification
// added simple Email verification
// removed emailVerify userpref subclass: just a email property
// changed pref binary storage layout: numarray => hash of non default values
// print optimize message only if really done.
// forced new cookie policy: delete pref cookies, use only WIKI_ID as plain string.
//   prefs should be stored in db or homepage, besides the current session.
//
// Revision 1.21  2004/03/14 16:26:21  rurban
// copyright line
//
// Revision 1.20  2004/03/12 23:20:58  rurban
// pref fixes (base64)
//
// Revision 1.19  2004/02/27 13:21:17  rurban
// several performance improvements, esp. with peardb
// simplified loops
// storepass seperated from prefs if defined so
// stacked and strict still not working
//
// Revision 1.18  2004/02/24 15:20:06  rurban
// fixed minor warnings: unchecked args, POST => Get urls for sortby e.g.
//
// Revision 1.17  2004/02/17 12:11:36  rurban
// added missing 4th basepage arg at plugin->run() to almost all plugins. This caused no harm so far, because it was silently dropped on normal usage. However on plugin internal ->run invocations it failed. (InterWikiSearch, IncludeSiteMap, ...)
//
// Revision 1.16  2004/02/15 21:34:37  rurban
// PageList enhanced and improved.
// fixed new WikiAdmin... plugins
// editpage, Theme with exp. htmlarea framework
//   (htmlarea yet committed, this is really questionable)
// WikiUser... code with better session handling for prefs
// enhanced UserPreferences (again)
// RecentChanges for show_deleted: how should pages be deleted then?
//
// Revision 1.15  2004/01/27 22:37:50  rurban
// fixed default args: no objects
//
// Revision 1.14  2004/01/26 09:18:00  rurban
// * changed stored pref representation as before.
//   the array of objects is 1) bigger and 2)
//   less portable. If we would import packed pref
//   objects and the object definition was changed, PHP would fail.
//   This doesn't happen with an simple array of non-default values.
// * use $prefs->retrieve and $prefs->store methods, where retrieve
//   understands the interim format of array of objects also.
// * simplified $prefs->get() and fixed $prefs->set()
// * added $user->_userid and class '_WikiUser' portability functions
// * fixed $user object ->_level upgrading, mostly using sessions.
//   this fixes yesterdays problems with loosing authorization level.
// * fixed WikiUserNew::checkPass to return the _level
// * fixed WikiUserNew::isSignedIn
// * added explodePageList to class PageList, support sortby arg
// * fixed UserPreferences for WikiUserNew
// * fixed WikiPlugin for empty defaults array
// * UnfoldSubpages: added pagename arg, renamed pages arg,
//   removed sort arg, support sortby arg
//
// Revision 1.13  2003/12/04 20:27:00  carstenklapp
// Use the API.
//
// Revision 1.12  2003/12/01 22:21:33  carstenklapp
// Bugfix: UserPreferences are no longer clobbered when signing in after
// the previous session has ended (i.e. user closed browser then signed
// in again). This is still a bit of a mess, and the preferences do not
// take effect until the next page browse/link has been clicked.
//
// Revision 1.11  2003/09/19 22:01:19  carstenklapp
// BOGO users allowed preferences too when ALLOW_BOGO_LOGIN == true.
//
// Revision 1.10  2003/09/13 21:57:26  carstenklapp
// Reformatting only.
//
// Revision 1.9  2003/09/13 21:53:41  carstenklapp
// Added lang and theme arguments, getVersion(), copyright and cvs log.
// For emacs users
// Local Variables:
// mode: php
// tab-width: 8
// c-basic-offset: 4
// c-hanging-comment-ender-p: nil
// indent-tabs-mode: nil
// End:
