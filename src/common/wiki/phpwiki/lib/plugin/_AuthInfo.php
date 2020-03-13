<?php
// -*-php-*-
rcs_id('$Id: _AuthInfo.php,v 1.19 2005/04/01 14:04:31 rurban Exp $');
/**
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

require_once('lib/Template.php');
/**
 * Used to debug auth problems and settings.
 * This plugin is only testing purposes.
 * if DEBUG is false, only admin can call it, which is of no real use.
 *
 * Warning! This may display db and user passwords in cleartext.
 */
class WikiPlugin__AuthInfo extends WikiPlugin
{
    public function getName()
    {
        return _("AuthInfo");
    }

    public function getDescription()
    {
        return _("Display general and user specific auth information.");
    }

    public function getVersion()
    {
        return preg_replace(
            "/[Revision: $]/",
            '',
            "\$Revision: 1.19 $"
        );
    }

    public function getDefaultArguments()
    {
        return array('userid' => '');
    }

    public function run($dbi, $argstr, &$request, $basepage)
    {
        $args = $this->getArgs($argstr, $request);
        extract($args);
        if (empty($userid) or $userid == $request->_user->UserName()) {
            $user = $request->_user;
            $userid = $user->UserName();
        } else {
            $user = WikiUser($userid);
        }
        if (!$user->isAdmin() and ! (DEBUG && _DEBUG_LOGIN)) {
            $request->_notAuthorized(WIKIAUTH_ADMIN);
            $this->disabled("! user->isAdmin");
        }

        $html = HTML(HTML::h3(fmt("General Auth Settings")));
        $table = HTML::table(array('border' => 1,
                                  'cellpadding' => 2,
                                  'cellspacing' => 0));
        $table->pushContent($this->_showhash(
            "AUTH DEFINES",
            $this->_buildConstHash(
                array("ENABLE_USER_NEW","ALLOW_ANON_USER",
                                          "ALLOW_ANON_EDIT","ALLOW_BOGO_LOGIN",
                                          "REQUIRE_SIGNIN_BEFORE_EDIT","ALLOW_USER_PASSWORDS",
                "PASSWORD_LENGTH_MINIMUM")
            )
        ));
        if ((defined('ALLOW_LDAP_LOGIN') && ALLOW_LDAP_LOGIN) or in_array("LDAP", $GLOBALS['USER_AUTH_ORDER'])) {
            $table->pushContent($this->_showhash(
                "LDAP DEFINES",
                $this->_buildConstHash(array("LDAP_AUTH_HOST","LDAP_BASE_DN"))
            ));
        }
        if ((defined('ALLOW_IMAP_LOGIN') && ALLOW_IMAP_LOGIN) or in_array("IMAP", $GLOBALS['USER_AUTH_ORDER'])) {
            $table->pushContent($this->_showhash("IMAP DEFINES", array("IMAP_AUTH_HOST" => IMAP_AUTH_HOST)));
        }
        if (defined('AUTH_USER_FILE') or in_array("File", $GLOBALS['USER_AUTH_ORDER'])) {
            $table->pushContent($this->_showhash(
                "AUTH_USER_FILE",
                $this->_buildConstHash(array("AUTH_USER_FILE",
                "AUTH_USER_FILE_STORABLE"))
            ));
        }
        if (defined('GROUP_METHOD')) {
            $table->pushContent($this->_showhash(
                "GROUP_METHOD",
                $this->_buildConstHash(array("GROUP_METHOD","AUTH_GROUP_FILE","GROUP_LDAP_QUERY"))
            ));
        }
        $table->pushContent($this->_showhash("\$USER_AUTH_ORDER[]", $GLOBALS['USER_AUTH_ORDER']));
        $table->pushContent($this->_showhash("USER_AUTH_POLICY", array("USER_AUTH_POLICY" => USER_AUTH_POLICY)));
        $html->pushContent($table);
        $html->pushContent(HTML(HTML::h3(fmt("Personal Auth Settings for '%s'", $userid))));
        if (!$user) {
            $html->pushContent(HTML::p(fmt("No userid")));
        } else {
            $table = HTML::table(array('border' => 1,
                                       'cellpadding' => 2,
                                       'cellspacing' => 0));
            //$table->pushContent(HTML::tr(HTML::td(array('colspan' => 2))));
            $userdata = obj2hash($user, array('_dbi','_request', 'password', 'passwd'));
            $table->pushContent($this->_showhash("User: Object of " . get_class($user), $userdata));
            if (ENABLE_USER_NEW) {
                $group = $request->getGroup();
                $groups = $group->getAllGroupsIn();
                $groupdata = obj2hash($group, array('_dbi','_request', 'password', 'passwd'));
                unset($groupdata['request']);
                $table->pushContent($this->_showhash("Group: Object of " . get_class($group), $groupdata));
                $groups = $group->getAllGroupsIn();
                $groupdata = array('getAllGroupsIn' => $groups);
                foreach ($groups as $g) {
                    $groupdata["getMembersOf($g)"] = $group->getMembersOf($g);
                    $groupdata["isMember($g)"] = $group->isMember($g);
                }
                $table->pushContent($this->_showhash("Group Methods: ", $groupdata));
            }
            $html->pushContent($table);
        }
        return $html;
    }

    public function _showhash($heading, $hash, $depth = 0)
    {
        static $seen = array();
        static $maxdepth = 0;
        $rows = array();
        $maxdepth++;
        if ($maxdepth > 35) {
            return $heading;
        }

        if ($heading) {
            $rows[] = HTML::tr(
                array('bgcolor' => '#ffcccc',
                                     'style' => 'color:#000000'),
                HTML::td(
                    array('colspan' => 2,
                                              'style' => 'color:#000000'),
                    $heading
                )
            );
        }
        if (is_object($hash)) {
            $hash = obj2hash($hash);
        }
        if (!empty($hash)) {
            ksort($hash);
            foreach ($hash as $key => $val) {
                if (is_object($val)) {
                    $heading = "Object of " . get_class($val);
                    if ($depth > 3) {
                        $val = $heading;
                    } elseif ($heading == "Object of wikidb_sql") {
                        $val = $heading;
                    } elseif (substr($heading, 0, 13) == "Object of db_") {
                        $val = $heading;
                    } elseif (!isset($seen[$heading])) {
                        //if (empty($seen[$heading])) $seen[$heading] = 1;
                        $val = HTML::table(
                            array('border' => 1,
                                                 'cellpadding' => 2,
                                                 'cellspacing' => 0),
                            $this->_showhash($heading, obj2hash($val), $depth + 1)
                        );
                    } else {
                        $val = $heading;
                    }
                } elseif (is_array($val)) {
                    $heading = $key . "[]";
                    if ($depth > 3) {
                        $val = $heading;
                    } elseif (!isset($seen[$heading])) {
                        //if (empty($seen[$heading])) $seen[$heading] = 1;
                        $val = HTML::table(
                            array('border' => 1,
                                                 'cellpadding' => 2,
                                                 'cellspacing' => 0),
                            $this->_showhash($heading, $val, $depth + 1)
                        );
                    } else {
                        $val = $heading;
                    }
                }
                $rows[] = HTML::tr(
                    HTML::td(
                        array('align' => 'right',
                                                  'bgcolor' => '#cccccc',
                                                  'style' => 'color:#000000'),
                        HTML(
                            HTML::raw('&nbsp;'),
                            $key,
                            HTML::raw('&nbsp;')
                        )
                    ),
                    HTML::td(
                        array('bgcolor' => '#ffffff',
                                                  'style' => 'color:#000000'),
                        $val ? $val : HTML::raw('&nbsp;')
                    )
                );
                //if (empty($seen[$key])) $seen[$key] = 1;
            }
        }
        return $rows;
    }

    public function _buildConstHash($constants)
    {
        $hash = array();
        foreach ($constants as $c) {
            $hash[$c] = defined($c) ? constant($c) : '<empty>';
            if ($hash[$c] === false) {
                $hash[$c] = 'false';
            } elseif ($hash[$c] === true) {
                $hash[$c] = 'true';
            }
        }
        return $hash;
    }
}

// $Log: _AuthInfo.php,v $
// Revision 1.19  2005/04/01 14:04:31  rurban
// use obj2hash exclude arg,
// fix minor security flaw: enable _AuthInfo only if Admin or DEBUG && _DEBUG_LOGIN
//   not on any DEBUG value
//
// Revision 1.18  2005/03/27 19:46:12  rurban
// security fixes (unknown why and where these get defined)
//
// Revision 1.17  2004/10/21 21:00:59  rurban
// fix recursion bug for old WikiUser:
//   limit max recursion depth (4) and overall recursions (35).
//
// Revision 1.16  2004/06/25 14:29:22  rurban
// WikiGroup refactoring:
//   global group attached to user, code for not_current user.
//   improved helpers for special groups (avoid double invocations)
// new experimental config option ENABLE_XHTML_XML (fails with IE, and document.write())
// fixed a XHTML validation error on userprefs.tmpl
//
// Revision 1.15  2004/06/16 10:38:59  rurban
// Disallow refernces in calls if the declaration is a reference
// ("allow_call_time_pass_reference clean").
//   PhpWiki is now allow_call_time_pass_reference = Off clean,
//   but several external libraries may not.
//   In detail these libs look to be affected (not tested):
//   * Pear_DB odbc
//   * adodb oracle
//
// Revision 1.14  2004/05/18 14:49:52  rurban
// Simplified strings for easier translation
//
// Revision 1.13  2004/04/02 15:06:56  rurban
// fixed a nasty ADODB_mysql session update bug
// improved UserPreferences layout (tabled hints)
// fixed UserPreferences auth handling
// improved auth stability
// improved old cookie handling: fixed deletion of old cookies with paths
//
// Revision 1.12  2004/03/12 15:48:08  rurban
// fixed explodePageList: wrong sortby argument order in UnfoldSubpages
// simplified lib/stdlib.php:explodePageList
//
// Revision 1.11  2004/03/12 11:18:25  rurban
// fixed ->membership chache
//
// Revision 1.10  2004/03/10 13:54:53  rurban
// adodb WikiGroup fix
//
// Revision 1.9  2004/03/08 18:17:10  rurban
// added more WikiGroup::getMembersOf methods, esp. for special groups
// fixed $LDAP_SET_OPTIONS
// fixed _AuthInfo group methods
//
// Revision 1.8  2004/03/08 16:35:23  rurban
// fixed "Undefined index: auth_dsn" warning
//
// Revision 1.7  2004/02/17 12:11:36  rurban
// added missing 4th basepage arg at plugin->run() to almost all plugins. This caused no harm so far, because it was silently dropped on normal usage. However on plugin internal ->run invocations it failed. (InterWikiSearch, IncludeSiteMap, ...)
//
// Revision 1.6  2004/02/15 15:21:24  rurban
// don't display the SQL dsn connection password
//
// Revision 1.5  2004/02/09 03:58:20  rurban
// for now default DB_SESSION to false
// PagePerm:
//   * not existing perms will now query the parent, and not
//     return the default perm
//   * added pagePermissions func which returns the object per page
//   * added getAccessDescription
// WikiUserNew:
//   * added global ->prepare (not yet used) with smart user/pref/member table prefixing.
//   * force init of authdbh in the 2 db classes
// main:
//   * fixed session handling (not triple auth request anymore)
//   * don't store cookie prefs with sessions
// stdlib: global obj2hash helper from _AuthInfo, also needed for PagePerm
//
// Revision 1.4  2004/02/07 10:41:25  rurban
// fixed auth from session (still double code but works)
// fixed GroupDB
// fixed DbPassUser upgrade and policy=old
// added GroupLdap
//
// Revision 1.3  2004/02/02 05:36:29  rurban
// Simplification and more options, but no passwd or admin protection yet.
//
// Revision 1.2  2004/02/01 09:14:11  rurban
// Started with Group_Ldap (not yet ready)
// added new _AuthInfo plugin to help in auth problems (warning: may display passwords)
// fixed some configurator vars
// renamed LDAP_AUTH_SEARCH to LDAP_BASE_DN
// changed PHPWIKI_VERSION from 1.3.8a to 1.3.8pre
// USE_DB_SESSION defaults to true on SQL
// changed GROUP_METHOD definition to string, not constants
// changed sample user DBAuthParams from UPDATE to REPLACE to be able to
//   create users. (Not to be used with external databases generally, but
//   with the default internal user table)
//
// fixed the IndexAsConfigProblem logic. this was flawed:
//   scripts which are the same virtual path defined their own lib/main call
//   (hmm, have to test this better, phpwiki.sf.net/demo works again)
//
// Revision 1.1  2004/02/01 01:04:34  rurban
// Used to debug auth problems and settings.
// This may display passwords in cleartext.
// DB Objects are not displayed anymore.
//
// Revision 1.21  2003/02/21 04:22:28  dairiki
// Make this work for array-valued data.  Make display of cached markup
// readable.  Some code cleanups.  (This still needs more work.)
//
// Revision 1.20  2003/01/18 21:19:24  carstenklapp
// Code cleanup:
// Reformatting; added copyleft, getVersion, getDescription
// (c-file-style: "gnu")
// Local Variables:
// mode: php
// tab-width: 8
// c-basic-offset: 4
// c-hanging-comment-ender-p: nil
// indent-tabs-mode: nil
// End:
