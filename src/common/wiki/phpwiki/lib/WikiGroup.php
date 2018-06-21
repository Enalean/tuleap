<?php
rcs_id('$Id: WikiGroup.php,v 1.52 2004/11/28 15:59:17 rurban Exp $');
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
    !in_array(GROUP_METHOD,
              array('NONE','WIKIPAGE','DB','FILE','LDAP')))
    trigger_error(_("No or unsupported GROUP_METHOD defined"), E_USER_WARNING);
    
/* Special group names for ACL */    
define('GROUP_EVERY',		_("Every"));
define('GROUP_ANONYMOUS',	_("Anonymous Users"));
define('GROUP_BOGOUSER',	_("Bogo Users"));
define('GROUP_HASHOMEPAGE',     _("HasHomePage"));
define('GROUP_SIGNED',		_("Signed Users"));
define('GROUP_AUTHENTICATED',	_("Authenticated Users"));
define('GROUP_ADMIN',		_("Administrators"));
define('GROUP_OWNER',		_("Owner"));
define('GROUP_CREATOR',	   	_("Creator"));

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
 * @author Joby Walker <zorloc@imperium.org>
 * @author Reini Urban
 */ 
class WikiGroup{
    /** User name */
    var $username = '';
    /** User object if different from current user */
    var $user;
    /** The global WikiRequest object */
    //var $request;
    /** Array of groups $username is confirmed to belong to */
    var $membership;
    /** boolean if not the current user */
    var $not_current = false;
    
    /**
     * Initializes a WikiGroup object which should never happen.  Use:
     * $group = &WikiGroup::getGroup();
     * @param object $request The global WikiRequest object -- ignored.
     */ 
    function __construct($not_current = false) {
    	$this->not_current = $not_current;
        //$this->request =& $GLOBALS['request'];
    }

    /**
     * Gets the current username from the internal user object
     * and erases $this->membership if is different than
     * the stored $this->username
     * @return string Current username.
     */ 
    function _getUserName(){
        global $request;
        $user = (!empty($this->user)) ? $this->user : $request->getUser();
        $username = $user->getID();
        if ($username != $this->username) {
            $this->membership = array();
            $this->username = $username;
        }
        if (!$this->not_current)
           $this->user = $user;
        return $username;
    }
    
    /**
     * Static method to return the WikiGroup subclass used in this wiki.  Controlled
     * by the constant GROUP_METHOD.
     * @param object $request The global WikiRequest object.
     * @return object Subclass of WikiGroup selected via GROUP_METHOD.
     */ 
    function getGroup($not_current = false){
        switch (GROUP_METHOD){
            case "NONE": 
                return new GroupNone($not_current);
                break;
            case "WIKIPAGE":
                return new GroupWikiPage($not_current);
                break;
            case "DB":
                if ($GLOBALS['DBParams']['dbtype'] == 'ADODB') {
                    return new GroupDB_ADODB($not_current);
                } elseif ($GLOBALS['DBParams']['dbtype'] == 'SQL') {
                    return new GroupDb_PearDB($not_current);
                } else {
                    trigger_error("GROUP_METHOD = DB: Unsupported dbtype " 
                                  . $GLOBALS['DBParams']['dbtype'],
                                  E_USER_ERROR);
                }
                break;
            case "FILE": 
                return new GroupFile($not_current);
                break;
            case "LDAP": 
                return new GroupLDAP($not_current);
                break;
            default:
                trigger_error(_("No or unsupported GROUP_METHOD defined"), E_USER_WARNING);
                return new WikiGroup($not_current);
        }
    }

    /** ACL PagePermissions will need those special groups based on the User status only.
     *  translated 
     */
    function specialGroup($group){
    	return in_array($group,$this->specialGroups());
    }
    /** untranslated */
    function _specialGroup($group){
    	return in_array($group,$this->_specialGroups());
    }
    /** translated */
    function specialGroups(){
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
    function _specialGroups(){
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
     * @return boolean True if user is a member, else false (always false).
     */ 
    function isMember($group){
        if (isset($this->membership[$group]))
            return $this->membership[$group];
    	if ($this->specialGroup($group)) {
    	    return $this->isSpecialMember($group);
        } else {
            trigger_error(__sprintf("Method '%s' not implemented in this GROUP_METHOD %s",
                                    'isMember', GROUP_METHOD),
                          E_USER_WARNING);
        }
        return false;
    }

    function isSpecialMember($group){
        global $request;

        if (isset($this->membership[$group]))
            return $this->membership[$group];
        $user = (!empty($this->user)) ? $this->user : $request->getUser();
        switch ($group) {
            case GROUP_EVERY:
                return $this->membership[$group] = true;
            case GROUP_ANONYMOUS:
                return $this->membership[$group] = ! $user->isSignedIn();
            case GROUP_BOGOUSER:
                return $this->membership[$group] = (isa($user,'_BogoUser') 
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
                trigger_error(__sprintf("Undefined method %s for special group %s",
                                        'isMember',$group),
                              E_USER_WARNING);
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
    function getAllGroupsIn(){
        trigger_error(__sprintf("Method '%s' not implemented in this GROUP_METHOD %s",
                                'getAllGroupsIn', GROUP_METHOD),
                      E_USER_WARNING);
        return array();
    }

    function _allUsers() {
    	static $result = array();
    	if (!empty($result))
    		return $result;

        global $request;
        /* WikiPage users: */
        $dbh =& $request->_dbi;
        $page_iter = $dbh->getAllPages();
        $users = array();
        while ($page = $page_iter->next()) {
            if ($page->isUserPage())
                $users[] = $page->_pagename;
        }

        /* WikiDB users from prefs (not from users): */
        if (ENABLE_USER_NEW)
            $dbi = _PassUser::getAuthDbh();
        else 
            $dbi = false;

        if ($dbi and $dbh->getAuthParam('pref_select')) {
            //get prefs table
            $sql = preg_replace('/SELECT .+ FROM/i','SELECT userid FROM',
                                $dbh->getAuthParam('pref_select'));
            //don't strip WHERE, only the userid stuff.
            $sql = preg_replace('/(WHERE.*?)\s+\w+\s*=\s*["\']\$userid[\'"]/i','\\1 AND 1', $sql);
            $sql = str_replace('WHERE AND 1','',$sql);
            if (isa($dbi, 'ADOConnection')) {
                $db_result = $dbi->Execute($sql);
                foreach ($db_result->GetArray() as $u) {
                    $users = array_merge($users,array_values($u));
                }
            } elseif (isa($dbi, 'DB_common')) { // PearDB
                $users = array_merge($users, $dbi->getCol($sql));
            }
        }

        /* WikiDB users from users: */
        // Fixme: don't strip WHERE, only the userid stuff.
        if ($dbi and $dbh->getAuthParam('auth_user_exists')) {
            //don't strip WHERE, only the userid stuff.
            $sql = preg_replace('/(WHERE.*?)\s+\w+\s*=\s*["\']\$userid[\'"]/i','\\1 AND 1',
                                $dbh->getAuthParam('auth_user_exists'));
            $sql = str_replace('WHERE AND 1','', $sql);
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
            if (empty($u) or in_array($u,$result)) continue;
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
    function getMembersOf($group){
    	if ($this->specialGroup($group)) {
    	    return $this->getSpecialMembersOf($group);
    	}
        trigger_error(__sprintf("Method '%s' not implemented in this GROUP_METHOD %s",
                                'getMembersOf', GROUP_METHOD),
                      E_USER_WARNING);
        return array();
    }
        	
    function getSpecialMembersOf($group) {
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
                if (isWikiWord($u)) $users[] = $u;
            }
            return $users;
        case GROUP_SIGNED:    	
            foreach ($all as $u) {
                $user = WikiUser($u);
                if ($user->isSignedIn()) $users[] = $u;
            }
            return $users;
        case GROUP_AUTHENTICATED:
            foreach ($all as $u) {
                $user = WikiUser($u);
                if ($user->isAuthenticated()) $users[] = $u;
            }
            return $users;
        case GROUP_ADMIN:		
            foreach ($all as $u) {
                $user = WikiUser($u);
                if (isset($user->_level) and $user->_level == WIKIAUTH_ADMIN) 
                    $users[] = $u;
            }
            return $users;
        case GROUP_OWNER:
        case GROUP_CREATOR:
            // this could get complex so just return an empty array
            return false;
        default:
            trigger_error(__sprintf("Unknown special group '%s'", $group),
                          E_USER_WARNING);
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
    function setMemberOf($group, $user = false){
        trigger_error(__sprintf("Method '%s' not implemented in this GROUP_METHOD %s",
                                'setMemberOf', GROUP_METHOD),
                      E_USER_WARNING);
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
    function removeMemberOf($group, $user = false){
        trigger_error(__sprintf("Method '%s' not implemented in this GROUP_METHOD %s",
                                'removeMemberOf', GROUP_METHOD),
                      E_USER_WARNING);
        return false;
    }
}

/**
 * GroupNone disables all Group funtionality
 * 
 * All of the GroupNone functions return false or empty values to indicate failure or 
 * no results.  Use GroupNone if group controls are not desired.
 * @author Joby Walker <zorloc@imperium.org>
 */ 
class GroupNone extends WikiGroup{

    /**
     * Constructor
     * 
     * Ignores the parameter provided.
     * @param object $request The global WikiRequest object - ignored.
     */ 
    function __construct() {
        //$this->request = &$GLOBALS['request'];
        return;
    }    

    /**
     * Determines if the current user is a member of a group.
     * 
     * The group is ignored and false (not a member of the group) is returned.
     * @param string $group Name of the group to check for membership (ignored).
     * @return boolean True if user is a member, else false (always false).
     */ 
    function isMember($group){
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
    function getAllGroupsIn(){
        return array();
    }

    /**
     * Determines all of the members of a particular group.
     * 
     * The group is ignored and an empty array (a member of no groups) is returned.
     * @param string $group Name of the group to check for membership (ignored).
     * @return array Array of groups user belongs to (always empty).
     */ 
    function getMembersOf($group){
        return array();
    }

}

/**
 * GroupWikiPage provides group functionality via pages within the Wiki.
 * 
 * GroupWikiPage is the Wiki way of managing a group.  Every group will have 
 * a page. To modify the membership of the group, one only needs to edit the 
 * membership list on the page.
 * @author Joby Walker <zorloc@imperium.org>
 */ 
class GroupWikiPage extends WikiGroup{
    
    /**
     * Constructor
     * 
     * Initializes the three superclass instance variables
     * @param object $request The global WikiRequest object.
     */ 
    function __construct() {
        //$this->request = &$GLOBALS['request'];
        $this->username = $this->_getUserName();
        //$this->username = null;
        $this->membership = array();
    }

    /**
     * Determines if the current user is a member of a group.
     * 
     * To determine membership in a particular group, this method checks the 
     * superclass instance variable $membership to see if membership has 
     * already been determined.  If not, then the group page is parsed to 
     * determine membership.
     * @param string $group Name of the group to check for membership.
     * @return boolean True if user is a member, else false.
     */ 
    function isMember($group){
        if (isset($this->membership[$group])) {
            return $this->membership[$group];
        }
        global $request;
        $group_page = $request->getPage($group);
        if ($this->_inGroupPage($group_page)) {
            $this->membership[$group] = true;
            return true;
        }
        $this->membership[$group] = false;
        // Let grouppages override certain defaults, such as members of admin
    	if ($this->specialGroup($group)) {
    	    return $this->isSpecialMember($group);
    	}
        return false;
    }
    
    /**
    * Private method to take a WikiDB_Page and parse to determine if the
    * current_user is a member of the group.
    * @param object $group_page WikiDB_Page object for the group's page
    * @return boolean True if user is a member, else false.
    * @access private
    */
    function _inGroupPage($group_page, $strict=false){
        $group_revision = $group_page->getCurrentRevision();
        if ($group_revision->hasDefaultContents()) {
            $group = $group_page->getName();
            if ($strict) trigger_error(sprintf(_("Group page '%s' does not exist"), $group), 
                                       E_USER_WARNING);
            return false;
        }
        $contents = $group_revision->getContent();
        $match = '/^\s*[\*\#]+\s*\[?' . $this->username . '\]?\s*$/';
        foreach ($contents as $line){
            if (preg_match($match, $line)) {
                return true;
            }
        }
        return false;
    }
    
    /**
     * Determines all of the groups of which the current user is a member.
     * 
     * Checks the root Group page ('CategoryGroup') for the list of all groups, 
     * then checks each group to see if the current user is a member.
     * @param string $group Name of the group to check for membership.
     * @return array Array of groups to which the user belongs.
     */ 
    function getAllGroupsIn(){
        $membership = array();

    	$specialgroups = $this->specialGroups();
        foreach ($specialgroups as $group) {
            $this->membership[$group] = $this->isSpecialMember($group);
        }

        global $request;
        $dbh = &$request->_dbi;
        $master_page = $request->getPage(CATEGORY_GROUP_PAGE);
        $master_list = $master_page->getLinks(true);
        while ($group_page = $master_list->next()){
            $group = $group_page->getName();
            $this->membership[$group] = $this->_inGroupPage($group_page);
        }
        foreach ($this->membership as $g => $bool) {
            if ($bool) $membership[] = $g;
        }
        return $membership;
    }

    /**
     * Determines all of the members of a particular group.
     * 
     * Checks a group's page to return all the current members.  Currently this
     * method is disabled and triggers an error and returns an empty array.
     * @param string $group Name of the group to get the full membership list of.
     * @return array Array of usernames that have joined the group (always empty).
     */ 
    function getMembersOf($group){
    	if ($this->specialGroup($group))
            return $this->getSpecialMembersOf($group);

        $group_page = $GLOBALS['request']->getPage($group);
        $group_revision = $group_page->getCurrentRevision();
        if ($group_revision->hasDefaultContents()) {
            trigger_error(sprintf(_("Group %s does not exist"),$group), E_USER_WARNING);
            return array();
        }
        $contents = $group_revision->getContent();
        // This is not really a reliable way to check if a string is a username. But better than nothing.
        $match = '/^(\s*[\*\#]+\s*\[?)(\w+)(\]?\s*)$/';
        $members = array();
        foreach ($contents as $line){
            if (preg_match($match, $line, $matches)){
                $members[] = $matches[2];
            }
        }
        return $members;
    }
}

/**
 * GroupDb is configured by $DbAuthParams[] statements
 * 
 * Fixme: adodb
 * @author ReiniUrban
 */ 
class GroupDb extends WikiGroup {
    
    var $_is_member, $_group_members, $_user_groups;

    /**
     * Constructor
     * 
     * @param object $request The global WikiRequest object. ignored
     */ 
    function __construct() {
    	global $DBAuthParams, $DBParams;
        //$this->request = &$GLOBALS['request'];
        $this->username = $this->_getUserName();
        $this->membership = array();

        if (empty($DBAuthParams['group_members']) or 
            empty($DBAuthParams['user_groups']) or
            empty($DBAuthParams['is_member'])) {
            trigger_error(_("No or not enough GROUP_DB SQL statements defined"), 
                          E_USER_WARNING);
            return new GroupNone();
        }
        // FIXME: This only works with ENABLE_USER_NEW
        if (empty($this->user)) {
            // use _PassUser::prepare instead
            if (isa($request->getUser(),'_PassUser'))
                $user = $request->getUser();
            else
                $user = new _PassUser($this->username);
        } elseif (!isa($this->user, '_PassUser')) {
            $user = new _PassUser($this->username);
        } else {
            $user =& $this->user;
        }
        if (isa($this->user, '_PassUser')) { // TODO: safety by Charles Corrigan
            $this->_is_member = $user->prepare($DBAuthParams['is_member'],
                                           array('userid','groupname'));
            $this->_group_members = $user->prepare($DBAuthParams['group_members'],'groupname');
            $this->_user_groups = $user->prepare($DBAuthParams['user_groups'],'userid');
            $this->dbh = $user->_auth_dbi;
        }
    }
}

/**
 * PearDB methods
 * 
 * @author ReiniUrban
 */ 
class GroupDb_PearDB extends GroupDb {
    
    /**
     * Determines if the current user is a member of a database group.
     * 
     * @param string $group Name of the group to check for membership.
     * @return boolean True if user is a member, else false.
     */ 
    function isMember($group) {
        if (isset($this->membership[$group])) {
            return $this->membership[$group];
        }
        $dbh = & $this->dbh;
        $db_result = $dbh->query(sprintf($this->_is_member,
                                         $dbh->quote($this->username),
                                         $dbh->quote($group)));
        if ($db_result->numRows() > 0) {
            $this->membership[$group] = true;
            return true;
        }
        $this->membership[$group] = false;
        // Let override certain defaults, such as members of admin
    	if ($this->specialGroup($group))
            return $this->isSpecialMember($group);
        return false;
    }
    
    /**
     * Determines all of the groups of which the current user is a member.
     * 
     * then checks each group to see if the current user is a member.
     * @param string $group Name of the group to check for membership.
     * @return array Array of groups to which the user belongs.
     */ 
    function getAllGroupsIn(){
    	$membership = array();

        $dbh = & $this->dbh;
        $db_result = $dbh->query(sprintf($this->_user_groups, $dbh->quote($this->username)));
        if ($db_result->numRows() > 0) {
            while (list($group) = $db_result->fetchRow(DB_FETCHMODE_ORDERED)) {
                $membership[] = $group;
                $this->membership[$group] = true;
            }
        }

    	$specialgroups = $this->specialGroups();
        foreach ($specialgroups as $group) {
            if ($this->isMember($group)) {
                $membership[] = $group;
            }
        }
        return $membership;
    }

    /**
     * Determines all of the members of a particular group.
     * 
     * Checks a group's page to return all the current members.  Currently this
     * method is disabled and triggers an error and returns an empty array.
     * @param string $group Name of the group to get the full membership list of.
     * @return array Array of usernames that have joined the group.
     */ 
    function getMembersOf($group){

        $members = array();
        $dbh = & $this->dbh;
        $db_result = $dbh->query(sprintf($this->_group_members,$dbh->quote($group)));
        if ($db_result->numRows() > 0) {
            while (list($userid) = $db_result->fetchRow(DB_FETCHMODE_ORDERED)) {
                $members[] = $userid;
            }
        }
        // add certain defaults, such as members of admin
    	if ($this->specialGroup($group))
            $members = array_merge($members, $this->getSpecialMembersOf($group));
        return $members;
    }
}

/**
 * ADODB methods
 * 
 * @author ReiniUrban
 */ 
class GroupDb_ADODB extends GroupDb {

    /**
     * Determines if the current user is a member of a database group.
     * 
     * @param string $group Name of the group to check for membership.
     * @return boolean True if user is a member, else false.
     */ 
    function isMember($group) {
        if (isset($this->membership[$group])) {
            return $this->membership[$group];
        }
        $dbh = & $this->dbh;
        $rs = $dbh->Execute(sprintf($this->_is_member,$dbh->qstr($this->username),
                                    $dbh->qstr($group)));
        if ($rs->EOF) {
            $rs->Close();
        } else {
            if ($rs->numRows() > 0) {
                $this->membership[$group] = true;
                $rs->Close();
                return true;
            }
        }
        $this->membership[$group] = false;
    	if ($this->specialGroup($group))
            return $this->isSpecialMember($group);

        return false;
    }
    
    /**
     * Determines all of the groups of which the current user is a member.
     * then checks each group to see if the current user is a member.
     *
     * @param string $group Name of the group to check for membership.
     * @return array Array of groups to which the user belongs.
     */ 
    function getAllGroupsIn(){
    	$membership = array();

        $dbh = & $this->dbh;
        $rs = $dbh->Execute(sprintf($this->_user_groups, $dbh->qstr($this->username)));
        if (!$rs->EOF and $rs->numRows() > 0) {
            while (!$rs->EOF) {
                $group = reset($rs->fields);
                $membership[] = $group;
                $this->membership[$group] = true;
                $rs->MoveNext();
            }
        }
        $rs->Close();

    	$specialgroups = $this->specialGroups();
        foreach ($specialgroups as $group) {
            if ($this->isMember($group)) {
                $membership[] = $group;
            }
        }
        return $membership;
    }

    /**
     * Determines all of the members of a particular group.
     * 
     * @param string $group Name of the group to get the full membership list of.
     * @return array Array of usernames that have joined the group.
     */ 
    function getMembersOf($group){
        $members = array();
        $dbh = & $this->dbh;
        $rs = $dbh->Execute(sprintf($this->_group_members,$dbh->qstr($group)));
        if (!$rs->EOF and $rs->numRows() > 0) {
            while (!$rs->EOF) {
                $members[] = reset($rs->fields);
                $rs->MoveNext();
            }
        }
        $rs->Close();
        // add certain defaults, such as members of admin
    	if ($this->specialGroup($group))
            $members = array_merge($members, $this->getSpecialMembersOf($group));
        return $members;
    }
}

/**
 * GroupFile is configured by AUTH_GROUP_FILE
 * groupname: user1 user2 ...
 * 
 * @author ReiniUrban
 */ 
class GroupFile extends WikiGroup {
    
    /**
     * Constructor
     * 
     * @param object $request The global WikiRequest object.
     */ 
    function __construct(){
        //$this->request = &$GLOBALS['request'];
        $this->username = $this->_getUserName();
        //$this->username = null;
        $this->membership = array();

        if (!defined('AUTH_GROUP_FILE')) {
            trigger_error(_("AUTH_GROUP_FILE not defined"), E_USER_WARNING);
            return false;
        }
        if (!file_exists(AUTH_GROUP_FILE)) {
            trigger_error(sprintf(_("Cannot open AUTH_GROUP_FILE %s"), AUTH_GROUP_FILE), 
                          E_USER_WARNING);
            return false;
        }
        require_once('lib/pear/File_Passwd.php');
        $this->_file = new File_Passwd(AUTH_GROUP_FILE,false,AUTH_GROUP_FILE.".lock");
    }

    /**
     * Determines if the current user is a member of a group.
     * 
     * To determine membership in a particular group, this method checks the 
     * superclass instance variable $membership to see if membership has 
     * already been determined.  If not, then the group file is parsed to 
     * determine membership.
     * @param string $group Name of the group to check for membership.
     * @return boolean True if user is a member, else false.
     */ 
    function isMember($group) {
        //$request = $this->request;
        //$username = $this->username;
        if (isset($this->membership[$group])) {
            return $this->membership[$group];
        }

        if (is_array($this->_file->users)) {
          foreach ($this->_file->users as $g => $u) {
            $users = explode(' ',$u);
            if (in_array($this->username,$users)) {
                $this->membership[$group] = true;
                return true;
            }
          }
        }
        $this->membership[$group] = false;
    	if ($this->specialGroup($group))
            return $this->isSpecialMember($group);
        return false;
    }
    
    /**
     * Determines all of the groups of which the current user is a member.
     * 
     * then checks each group to see if the current user is a member.
     * @param string $group Name of the group to check for membership.
     * @return array Array of groups to which the user belongs.
     */ 
    function getAllGroupsIn(){
        //$username = $this->_getUserName();
        $membership = array();

        if (is_array($this->_file->users)) {
          foreach ($this->_file->users as $group => $u) {
            $users = explode(' ',$u);
            if (in_array($this->username,$users)) {
                $this->membership[$group] = true;
                $membership[] = $group;
            }
          }
        }

    	$specialgroups = $this->specialGroups();
        foreach ($specialgroups as $group) {
            if ($this->isMember($group)) {
                $this->membership[$group] = true;
                $membership[] = $group;
            }
        }
        return $membership;
    }

    /**
     * Determines all of the members of a particular group.
     * 
     * Return all the current members.
     * @param string $group Name of the group to get the full membership list of.
     * @return array Array of usernames that have joined the group.
     */ 
    function getMembersOf($group){
        $members = array();
        if (!empty($this->_file->users[$group])) {
            $members = explode(' ',$this->_file->users[$group]);
        }
    	if ($this->specialGroup($group)) {
            $members = array_merge($members, $this->getSpecialMembersOf($group));
        }
        return $members;
    }
}

/**
 * Ldap is configured in index.php
 * 
 * @author ReiniUrban
 */ 
class GroupLdap extends WikiGroup {
    
    /**
     * Constructor
     * 
     * @param object $request The global WikiRequest object.
     */ 
    function __construct(){
        //$this->request = &$GLOBALS['request'];
        $this->username = $this->_getUserName();
        $this->membership = array();

        if (!defined("LDAP_AUTH_HOST")) {
            trigger_error(_("LDAP_AUTH_HOST not defined"), E_USER_WARNING);
            return false;
        }
        // We should ignore multithreaded environments, not generally windows.
        // CGI does work.
        if (! function_exists('ldap_connect') and (!isWindows() or isCGI())) {
            // on MacOSX >= 4.3 you'll need PHP_SHLIB_SUFFIX instead.
            dl("ldap".defined('PHP_SHLIB_SUFFIX') ? PHP_SHLIB_SUFFIX : DLL_EXT); 
            if (! function_exists('ldap_connect')) {
                trigger_error(_("No LDAP in this PHP version"), E_USER_WARNING);
                return false;
            }
        }
        if (!defined("LDAP_BASE_DN"))
            define("LDAP_BASE_DN",'');
        $this->base_dn = LDAP_BASE_DN;
        // if no users ou (organizational unit) is defined,
        // then take out the ou= from the base_dn (if exists) and append a default
        // from users and group
        if (!LDAP_OU_USERS)
            if (strstr(LDAP_BASE_DN, "ou="))
                $this->base_dn = preg_replace("/(ou=\w+,)?()/","\$2", LDAP_BASE_DN);

        if (!isset($this->user) or !isa($this->user, '_LDAPPassUser'))
            $this->_user = new _LDAPPassUser('LdapGroupTest'); // to have a valid username
        else 
            $this->_user =& $this->user;
    }

    /**
     * Determines if the current user is a member of a group.
     * Not ready yet!
     * 
     * @param string $group Name of the group to check for membership.
     * @return boolean True if user is a member, else false.
     */ 
    function isMember($group) {
        if (isset($this->membership[$group])) {
            return $this->membership[$group];
        }
        //$request = $this->request;
        //$username = $this->_getUserName();
        $this->membership[$group] = in_array($this->username,$this->getMembersOf($group));
        if ($this->membership[$group])
            return true;
    	if ($this->specialGroup($group))
            return $this->isSpecialMember($group);
    }
    
    /**
     * Determines all of the groups of which the current user is a member.
     *
     * @param string $group Name of the group to check for membership.
     * @return array Array of groups to which the user belongs.
     */ 
    function getAllGroupsIn(){
        //$request = &$this->request;
        //$username = $this->_getUserName();
        $membership = array();

    	$specialgroups = $this->specialGroups();
        foreach ($specialgroups as $group) {
            if ($this->isMember($group)) {
                $this->membership[$group] = true;
                $membership[] = $group;
            }
        }
        
        // must be a valid LDAP server, and username must not contain a wildcard
        if ($ldap = $this->_user->_init()) {
            $st_search = LDAP_SEARCH_FIELD ? LDAP_SEARCH_FIELD."=".$this->username
                			   : "uid=".$this->username;
            $sr = ldap_search($ldap, (LDAP_OU_USERS ? LDAP_OU_USERS : "ou=Users")
                              .($this->base_dn ? ",".$this->base_dn : ''), 
                              $st_search);
            if (!$sr) {
 		$this->_user->_free();
                return $this->membership;
            }
            $info = ldap_get_entries($ldap, $sr);
            if (empty($info["count"])) {
 		$this->_user->_free();
                return $this->membership;
            }
            for ($i = 0; $i < $info["count"]; $i++) {
            	if ($info[$i]["gidNumber"]["count"]) {
                    $gid = $info[$i]["gidnumber"][0];
                    $sr2 = ldap_search($ldap, (LDAP_OU_GROUP ? LDAP_OU_GROUP : "ou=Groups")
                                       .($this->base_dn ? ",".$this->base_dn : ''),
                                       "gidNumber=$gid");
                    if ($sr2) {
                        $info2 = ldap_get_entries($ldap, $sr2);
                        if (!empty($info2["count"]))
                            $membership[] =  $info2[0]["cn"][0];
                    }
                }
            }
        } else {
            trigger_error(fmt("Unable to connect to LDAP server %s", LDAP_AUTH_HOST), 
                          E_USER_WARNING);
        }
        $this->_user->_free();
        //ldap_close($ldap);
        $this->membership = $membership;
        return $membership;
    }

    /**
     * Determines all of the members of a particular group.
     * 
     * Return all the members of the given group. LDAP just returns the gid of each user
     * @param string $group Name of the group to get the full membership list of.
     * @return array Array of usernames that have joined the group.
     */ 
    function getMembersOf($group){
        $members = array();
        if ($ldap = $this->_user->_init()) {
            $base_dn = (LDAP_OU_GROUP ? LDAP_OU_GROUP : "ou=Groups")
                .($this->base_dn ? ",".$this->base_dn : '');
            $sr = ldap_search($ldap, $base_dn, "cn=$group");
            if ($sr)
                $info = ldap_get_entries($ldap, $sr);
            else {
                $info = array('count' => 0);
                trigger_error("LDAP_SEARCH: base=\"$base_dn\" \"(cn=$group)\" failed", E_USER_NOTICE);
            }
            $base_dn = (LDAP_OU_USERS ? LDAP_OU_USERS : "ou=Users")
                .($this->base_dn ? ",".$this->base_dn : '');
            for ($i = 0; $i < $info["count"]; $i++) {
                $gid = $info[$i]["gidNumber"][0];
                //uid=* would be better probably
                $sr2 = ldap_search($ldap, $base_dn, "gidNumber=$gid");
                if ($sr2) {
                    $info2 = ldap_get_entries($ldap, $sr2);
                    for ($j = 0; $j < $info2["count"]; $j++) {
                        $members[] = $info2[$j]["cn"][0];
                    }
                } else {
                    trigger_error("LDAP_SEARCH: base=\"$base_dn\" \"(gidNumber=$gid)\" failed", E_USER_NOTICE);
                }
            }
        }
        $this->_user->_free();
        //ldap_close($ldap);

    	if ($this->specialGroup($group)) {
            $members = array_merge($members, $this->getSpecialMembersOf($group));
        }
        return $members;
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
//

// Local Variables:
// mode: php
// tab-width: 8
// c-basic-offset: 4
// c-hanging-comment-ender-p: nil
// indent-tabs-mode: nil
// End:
?>