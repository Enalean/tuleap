<?php
/*
 Copyright (C) 2003, 2004 $ThePhpWikiProgrammingTeam

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

if (!defined('GROUP_METHOD') or
    GROUP_METHOD !== 'NONE') {
    trigger_error(_("No or unsupported GROUP_METHOD defined"), E_USER_WARNING);
}

/* Special group names for ACL */
define('GROUP_EVERY', _("Every"));
define('GROUP_ANONYMOUS', _("Anonymous Users"));
define('GROUP_BOGOUSER', _("Bogo Users"));
define('GROUP_HASHOMEPAGE', _("HasHomePage"));
define('GROUP_SIGNED', _("Signed Users"));
define('GROUP_AUTHENTICATED', _("Authenticated Users"));
define('GROUP_ADMIN', _("Administrators"));
define('GROUP_OWNER', _("Owner"));
define('GROUP_CREATOR', _("Creator"));

/**
 * WikiGroup is an abstract class to provide the base functions for determining
 * group membership for a specific user. Some functions are user independent.
 *
 * Limitation: For the current user only. This must be fixed to be able to query
 * for membership of any user.
 *
 * WikiGroup is an abstract class with three functions:
 * <ol><li />Provide the static method getGroup with will return the proper
 *         subclass.
 *     <li />Provide an interface for subclasses to implement.
 *     <li />Provide fallover methods (with error msgs) if not impemented in subclass.
 * </ol>
 * Do not ever instantiate this class. Use: $group = &WikiGroup::getGroup();
 * This will instantiate the proper subclass.
 *
 */
class WikiGroup
{
    /** User name */
    public $username = '';
    /** User object if different from current user */
    public $user;
    /** The global WikiRequest object */
    //var $request;
    /** Array of groups $username is confirmed to belong to */
    public $membership;
    /** boolean if not the current user */
    public $not_current = false;

    /**
     * Initializes a WikiGroup object which should never happen.  Use:
     * $group = &WikiGroup::getGroup();
     * @param object $request The global WikiRequest object -- ignored.
     */
    public function __construct($not_current = false)
    {
        $this->not_current = $not_current;
    }

    /**
     * Gets the current username from the internal user object
     * and erases $this->membership if is different than
     * the stored $this->username
     * @return string Current username.
     */
    public function _getUserName()
    {
        global $request;
        $user = (!empty($this->user)) ? $this->user : $request->getUser();
        $username = $user->getID();
        if ($username != $this->username) {
            $this->membership = array();
            $this->username = $username;
        }
        if (!$this->not_current) {
            $this->user = $user;
        }
        return $username;
    }

    /**
     * Static method to return the WikiGroup subclass used in this wiki.  Controlled
     * by the constant GROUP_METHOD.
     * @param object $request The global WikiRequest object.
     * @return object Subclass of WikiGroup selected via GROUP_METHOD.
     */
    public function getGroup($not_current = false)
    {
        return new GroupNone($not_current);
    }

    /** ACL PagePermissions will need those special groups based on the User status only.
     *  translated
     */
    public function specialGroup($group)
    {
        return in_array($group, $this->specialGroups());
    }
    /** untranslated */
    public function _specialGroup($group)
    {
        return in_array($group, $this->_specialGroups());
    }
    /** translated */
    public function specialGroups()
    {
        return array(
                     GROUP_EVERY,
                     GROUP_ANONYMOUS,
                     GROUP_BOGOUSER,
                     GROUP_SIGNED,
                     GROUP_AUTHENTICATED,
                     GROUP_ADMIN,
                     GROUP_OWNER,
                     GROUP_CREATOR);
    }
    /** untranslated */
    public function _specialGroups()
    {
        return array(
                     "_EVERY",
                     "_ANONYMOUS",
                     "_BOGOUSER",
                     "_SIGNED",
                     "_AUTHENTICATED",
                     "_ADMIN",
                     "_OWNER",
                     "_CREATOR");
    }

    /**
     * Determines if the current user is a member of a group.
     *
     * This method is an abstraction.  The group is ignored, an error is sent, and
     * false (not a member of the group) is returned.
     * @param string $group Name of the group to check for membership (ignored).
     * @return bool True if user is a member, else false (always false).
     */
    public function isMember($group)
    {
        if (isset($this->membership[$group])) {
            return $this->membership[$group];
        }
        if ($this->specialGroup($group)) {
            return $this->isSpecialMember($group);
        } else {
            trigger_error(
                PHPWikiSprintf(
                    "Method '%s' not implemented in this GROUP_METHOD %s",
                    'isMember',
                    GROUP_METHOD
                ),
                E_USER_WARNING
            );
        }
        return false;
    }

    public function isSpecialMember($group)
    {
        global $request;

        if (isset($this->membership[$group])) {
            return $this->membership[$group];
        }
        $user = (!empty($this->user)) ? $this->user : $request->getUser();
        switch ($group) {
            case GROUP_EVERY:
                return $this->membership[$group] = true;
            case GROUP_ANONYMOUS:
                return $this->membership[$group] = ! $user->isSignedIn();
            case GROUP_BOGOUSER:
                return $this->membership[$group] = (isa($user, '_BogoUser')
                                                    and $user->_level >= WIKIAUTH_BOGO);
            case GROUP_SIGNED:
                return $this->membership[$group] = $user->isSignedIn();
            case GROUP_AUTHENTICATED:
                return $this->membership[$group] = $user->isAuthenticated();
            case GROUP_ADMIN:
                return $this->membership[$group] = (isset($user->_level)
                                                    and $user->_level == WIKIAUTH_ADMIN);
            case GROUP_OWNER:
            case GROUP_CREATOR:
                return false;
            default:
                trigger_error(
                    PHPWikiSprintf(
                        "Undefined method %s for special group %s",
                        'isMember',
                        $group
                    ),
                    E_USER_WARNING
                );
        }
        return false;
    }

    /**
     * Determines all of the groups of which the current user is a member.
     *
     * This method is an abstraction.  An error is sent and an empty
     * array is returned.
     * @return array Array of groups to which the user belongs (always empty).
     */
    public function getAllGroupsIn()
    {
        trigger_error(
            PHPWikiSprintf(
                "Method '%s' not implemented in this GROUP_METHOD %s",
                'getAllGroupsIn',
                GROUP_METHOD
            ),
            E_USER_WARNING
        );
        return array();
    }

    public function _allUsers()
    {
        static $result = array();
        if (!empty($result)) {
            return $result;
        }

        global $request;
        /* WikiPage users: */
        $dbh = $request->_dbi;
        $page_iter = $dbh->getAllPages();
        $users = array();
        while ($page = $page_iter->next()) {
            if ($page->isUserPage()) {
                $users[] = $page->_pagename;
            }
        }

        /* WikiDB users from prefs (not from users): */
        if (ENABLE_USER_NEW) {
            $dbi = _PassUser::getAuthDbh();
        } else {
            $dbi = false;
        }

        if ($dbi and $dbh->getAuthParam('pref_select')) {
            //get prefs table
            $sql = preg_replace(
                '/SELECT .+ FROM/i',
                'SELECT userid FROM',
                $dbh->getAuthParam('pref_select')
            );
            //don't strip WHERE, only the userid stuff.
            $sql = preg_replace('/(WHERE.*?)\s+\w+\s*=\s*["\']\$userid[\'"]/i', '\\1 AND 1', $sql);
            $sql = str_replace('WHERE AND 1', '', $sql);
            if (isa($dbi, 'ADOConnection')) {
                $db_result = $dbi->Execute($sql);
                foreach ($db_result->GetArray() as $u) {
                    $users = array_merge($users, array_values($u));
                }
            } elseif (isa($dbi, 'DB_common')) { // PearDB
                $users = array_merge($users, $dbi->getCol($sql));
            }
        }

        /* WikiDB users from users: */
        // Fixme: don't strip WHERE, only the userid stuff.
        if ($dbi and $dbh->getAuthParam('auth_user_exists')) {
            //don't strip WHERE, only the userid stuff.
            $sql = preg_replace(
                '/(WHERE.*?)\s+\w+\s*=\s*["\']\$userid[\'"]/i',
                '\\1 AND 1',
                $dbh->getAuthParam('auth_user_exists')
            );
            $sql = str_replace('WHERE AND 1', '', $sql);
            if (isa($dbi, 'ADOConnection')) {
                $db_result = $dbi->Execute($sql);
                foreach ($db_result->GetArray() as $u) {
                    $users = array_merge($users, array_values($u));
                }
            } elseif (isa($dbi, 'DB_common')) {
                $users = array_merge($users, $dbi->getCol($sql));
            }
        }

        // remove empty and duplicate users
        $result = array();
        foreach ($users as $u) {
            if (empty($u) or in_array($u, $result)) {
                continue;
            }
            $result[] = $u;
        }
        return $result;
    }

    /**
     * Determines all of the members of a particular group.
     *
     * This method is an abstraction.  The group is ignored, an error is sent,
     * and an empty array is returned
     * @param string $group Name of the group to get the full membership list of.
     * @return array Array of usernames that have joined the group (always empty).
     */
    public function getMembersOf($group)
    {
        if ($this->specialGroup($group)) {
            return $this->getSpecialMembersOf($group);
        }
        trigger_error(
            PHPWikiSprintf(
                "Method '%s' not implemented in this GROUP_METHOD %s",
                'getMembersOf',
                GROUP_METHOD
            ),
            E_USER_WARNING
        );
        return array();
    }

    public function getSpecialMembersOf($group)
    {
        //$request = &$this->request;
        $all = $this->_allUsers();
        $users = array();
        switch ($group) {
            case GROUP_EVERY:
                return $all;
            case GROUP_ANONYMOUS:
                return $users;
            case GROUP_BOGOUSER:
                foreach ($all as $u) {
                    if (isWikiWord($u)) {
                        $users[] = $u;
                    }
                }
                return $users;
            case GROUP_SIGNED:
                foreach ($all as $u) {
                    $user = WikiUser($u);
                    if ($user->isSignedIn()) {
                        $users[] = $u;
                    }
                }
                return $users;
            case GROUP_AUTHENTICATED:
                foreach ($all as $u) {
                    $user = WikiUser($u);
                    if ($user->isAuthenticated()) {
                        $users[] = $u;
                    }
                }
                return $users;
            case GROUP_ADMIN:
                foreach ($all as $u) {
                    $user = WikiUser($u);
                    if (isset($user->_level) and $user->_level == WIKIAUTH_ADMIN) {
                        $users[] = $u;
                    }
                }
                return $users;
            case GROUP_OWNER:
            case GROUP_CREATOR:
                // this could get complex so just return an empty array
                return false;
            default:
                trigger_error(
                    PHPWikiSprintf("Unknown special group '%s'", $group),
                    E_USER_WARNING
                );
        }
    }

    /**
     * Add the current or specified user to a group.
     *
     * This method is an abstraction.  The group and user are ignored, an error
     * is sent, and false (not added) is always returned.
     * @param string $group User added to this group.
     * @param string $user Username to add to the group (default = current user).
     * @return bool On true user was added, false if not.
     */
    public function setMemberOf($group, $user = false)
    {
        trigger_error(
            PHPWikiSprintf(
                "Method '%s' not implemented in this GROUP_METHOD %s",
                'setMemberOf',
                GROUP_METHOD
            ),
            E_USER_WARNING
        );
        return false;
    }

    /**
     * Remove the current or specified user to a group.
     *
     * This method is an abstraction.  The group and user are ignored, and error
     * is sent, and false (not removed) is always returned.
     * @param string $group User removed from this group.
     * @param string $user Username to remove from the group (default = current user).
     * @return bool On true user was removed, false if not.
     */
    public function removeMemberOf($group, $user = false)
    {
        trigger_error(
            PHPWikiSprintf(
                "Method '%s' not implemented in this GROUP_METHOD %s",
                'removeMemberOf',
                GROUP_METHOD
            ),
            E_USER_WARNING
        );
        return false;
    }
}

/**
 * GroupNone disables all Group funtionality
 *
 * All of the GroupNone functions return false or empty values to indicate failure or
 * no results.  Use GroupNone if group controls are not desired.
 */
class GroupNone extends WikiGroup
{

    /**
     * Constructor
     *
     * Ignores the parameter provided.
     * @param object $request The global WikiRequest object - ignored.
     */
    public function __construct()
    {
        //$this->request = &$GLOBALS['request'];
        return;
    }

    /**
     * Determines if the current user is a member of a group.
     *
     * The group is ignored and false (not a member of the group) is returned.
     * @param string $group Name of the group to check for membership (ignored).
     * @return bool True if user is a member, else false (always false).
     */
    public function isMember($group)
    {
        if ($this->specialGroup($group)) {
            return $this->isSpecialMember($group);
        } else {
            return false;
        }
    }

    /**
     * Determines all of the groups of which the current user is a member.
     *
     * The group is ignored and an empty array (a member of no groups) is returned.
     * @param string $group Name of the group to check for membership (ignored).
     * @return array Array of groups to which the user belongs (always empty).
     */
    public function getAllGroupsIn()
    {
        return array();
    }

    /**
     * Determines all of the members of a particular group.
     *
     * The group is ignored and an empty array (a member of no groups) is returned.
     * @param string $group Name of the group to check for membership (ignored).
     * @return array Array of groups user belongs to (always empty).
     */
    public function getMembersOf($group)
    {
        return array();
    }
}

// $Log: WikiGroup.php,v $
// Revision 1.52  2004/11/28 15:59:17  rurban
// patch by Charles Corrigan so that WikiGroup->isSpecialMember knows about CREATOR and OWNER
//
// Revision 1.51  2004/11/27 14:39:04  rurban
// simpified regex search architecture:
//   no db specific node methods anymore,
//   new sql() method for each node
//   parallel to regexp() (which returns pcre)
//   regex types bitmasked (op's not yet)
// new regex=sql
// clarified WikiDB::quote() backend methods:
//   ->quote() adds surrounsing quotes
//   ->qstr() (new method) assumes strings and adds no quotes! (in contrast to ADODB)
//   pear and adodb have now unified quote methods for all generic queries.
//
// Revision 1.50  2004/11/24 18:58:41  rurban
// bug #1063463
//
// Revision 1.49  2004/11/23 13:06:30  rurban
// several fixes and suggestions by Charles Corrigan:
// * fix GROUP_BOGO_USER check
// * allow group pages to have the link to the user page in [ ] brackets
// * fix up the implementation of GroupWikiPage::getMembersOf and allow the
//   user page to be linked in [ ] brackets
// * added _OWNER and _CREATOR to special wikigroups
// * check against those two for group membership also, not only the user.
//
// Revision 1.48  2004/11/19 19:22:03  rurban
// ModeratePage part1: change status
//
// Revision 1.47  2004/11/19 13:23:47  rurban
//
// Another catch by Charles Corrigan: check against the dbi backend, not the WikiDB class.
//
// Revision 1.46  2004/11/18 09:52:23  rurban
// more safety, requested by Charles Corrigan
//
// Revision 1.45  2004/11/17 20:06:30  rurban
// possible group fix
//
// Revision 1.44  2004/11/11 10:31:26  rurban
// Disable default options in config-dist.ini
// Add new CATEGORY_GROUP_PAGE root page: Default: Translation of "CategoryGroup"
// Clarify more options.
//
// Revision 1.43  2004/11/10 15:29:21  rurban
// * requires newer Pear_DB (as the internal one): quote() uses now escapeSimple for strings
// * ACCESS_LOG_SQL: fix cause request not yet initialized
// * WikiDB: moved SQL specific methods upwards
// * new Pear_DB quoting: same as ADODB and as newer Pear_DB.
//   fixes all around: WikiGroup, WikiUserNew SQL methods, SQL logging
//
// Revision 1.42  2004/11/01 10:43:56  rurban
// seperate PassUser methods into seperate dir (memory usage)
// fix WikiUser (old) overlarge data session
// remove wikidb arg from various page class methods, use global ->_dbi instead
// ...
//
// Revision 1.41  2004/09/17 14:21:28  rurban
// fix LDAP ou= issue, wrong strstr arg order
//
// Revision 1.40  2004/06/29 06:48:02  rurban
// Improve LDAP auth and GROUP_LDAP membership:
//   no error message on false password,
//   added two new config vars: LDAP_OU_USERS and LDAP_OU_GROUP with GROUP_METHOD=LDAP
//   fixed two group queries (this -> user)
// stdlib: ConvertOldMarkup still flawed
//
// Revision 1.39  2004/06/28 15:39:28  rurban
// fixed endless recursion in WikiGroup: isAdmin()
//
// Revision 1.38  2004/06/27 10:24:19  rurban
// suggestion by Paul Henry
//
// Revision 1.37  2004/06/25 14:29:18  rurban
// WikiGroup refactoring:
//   global group attached to user, code for not_current user.
//   improved helpers for special groups (avoid double invocations)
// new experimental config option ENABLE_XHTML_XML (fails with IE, and document.write())
// fixed a XHTML validation error on userprefs.tmpl
//
// Revision 1.36  2004/06/16 13:21:05  rurban
// stabilize on failing ldap queries or bind
//
// Revision 1.35  2004/06/16 12:08:25  rurban
// better desc
//
// Revision 1.34  2004/06/16 11:51:35  rurban
// catch dl error on Windows
//
// Revision 1.33  2004/06/15 10:40:35  rurban
// minor WikiGroup cleanup: no request param, start of current user independency
//
// Revision 1.32  2004/06/15 09:15:52  rurban
// IMPORTANT: fixed passwd handling for passwords stored in prefs:
//   fix encrypted usage, actually store and retrieve them from db
//   fix bogologin with passwd set.
// fix php crashes with call-time pass-by-reference (references wrongly used
//   in declaration AND call). This affected mainly Apache2 and IIS.
//   (Thanks to John Cole to detect this!)
//
// Revision 1.31  2004/06/03 18:06:29  rurban
// fix file locking issues (only needed on write)
// fixed immediate LANG and THEME in-session updates if not stored in prefs
// advanced editpage toolbars (search & replace broken)
//
// Revision 1.30  2004/06/03 09:39:51  rurban
// fix LDAP injection (wildcard in username) detected by Steve Christey, MITRE
//
// Revision 1.29  2004/05/16 22:07:35  rurban
// check more config-default and predefined constants
// various PagePerm fixes:
//   fix default PagePerms, esp. edit and view for Bogo and Password users
//   implemented Creator and Owner
//   BOGOUSERS renamed to BOGOUSER
// fixed syntax errors in signin.tmpl
//
// Revision 1.28  2004/05/15 22:54:49  rurban
// fixed important WikiDB bug with DEBUG > 0: wrong assertion
// improved SetAcl (works) and PagePerms, some WikiGroup helpers.
//
// Revision 1.27  2004/05/06 13:56:40  rurban
// Enable the Administrators group, and add the WIKIPAGE group default root page.
//
// Revision 1.26  2004/04/07 23:13:18  rurban
// fixed pear/File_Passwd for Windows
// fixed FilePassUser sessions (filehandle revive) and password update
//
// Revision 1.25  2004/03/29 10:40:36  rurban
// GroupDb_PearDB fetchmode fix
//
// Revision 1.24  2004/03/14 16:26:22  rurban
// copyright line
//
// Revision 1.23  2004/03/12 23:20:58  rurban
// pref fixes (base64)
//
// Revision 1.22  2004/03/12 15:48:07  rurban
// fixed explodePageList: wrong sortby argument order in UnfoldSubpages
// simplified lib/stdlib.php:explodePageList
//
// Revision 1.21  2004/03/12 11:18:24  rurban
// fixed ->membership chache
//
// Revision 1.20  2004/03/12 10:47:30  rurban
// fixed GroupDB for ADODB
//
// Revision 1.19  2004/03/11 16:27:30  rurban
// fixed GroupFile::getMembersOf for special groups
// added authenticated bind for GroupLdap (Windows AD) as in WikiUserNew
//
// Revision 1.18  2004/03/11 13:30:47  rurban
// fixed File Auth for user and group
// missing only getMembersOf(Authenticated Users),getMembersOf(Every),getMembersOf(Signed Users)
//
// Revision 1.17  2004/03/10 15:38:48  rurban
// store current user->page and ->action in session for WhoIsOnline
// better WhoIsOnline icon
// fixed WhoIsOnline warnings
//
// Revision 1.15  2004/03/09 12:11:57  rurban
// prevent from undefined DBAuthParams warning
//
// Revision 1.14  2004/03/08 19:30:01  rurban
// fixed Theme->getButtonURL
// AllUsers uses now WikiGroup (also DB User and DB Pref users)
// PageList fix for empty pagenames
//
// Revision 1.13  2004/03/08 18:17:09  rurban
// added more WikiGroup::getMembersOf methods, esp. for special groups
// fixed $LDAP_SET_OPTIONS
// fixed _AuthInfo group methods
//
// Revision 1.12  2004/02/23 21:30:25  rurban
// more PagePerm stuff: (working against 1.4.0)
//   ACL editing and simplification of ACL's to simple rwx------ string
//   not yet working.
//
// Revision 1.11  2004/02/07 10:41:25  rurban
// fixed auth from session (still double code but works)
// fixed GroupDB
// fixed DbPassUser upgrade and policy=old
// added GroupLdap
//
// Revision 1.10  2004/02/03 09:45:39  rurban
// LDAP cleanup, start of new Pref classes
//
// Revision 1.9  2004/02/01 09:14:11  rurban
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
// Revision 1.8  2004/01/27 23:23:39  rurban
// renamed ->Username => _userid for consistency
// renamed mayCheckPassword => mayCheckPass
// fixed recursion problem in WikiUserNew
// fixed bogo login (but not quite 100% ready yet, password storage)
//
// Revision 1.7  2004/01/26 16:52:40  rurban
// added GroupDB and GroupFile classes
//
// Revision 1.6  2003/12/07 19:29:11  carstenklapp
// Code Housecleaning: fixed syntax errors. (php -l *.php)
//
// Revision 1.5  2003/02/22 20:49:55  dairiki
// Fixes for "Call-time pass by reference has been deprecated" errors.
//
// Revision 1.4  2003/01/21 04:02:39  zorloc
// Added Log entry and page footer.
// Local Variables:
// mode: php
// tab-width: 8
// c-basic-offset: 4
// c-hanging-comment-ender-p: nil
// indent-tabs-mode: nil
// End:
