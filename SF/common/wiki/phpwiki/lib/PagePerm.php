<?php // -*-php-*-
rcs_id('$Id$');
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

/* 
   Permissions per page and action based on current user, 
   ownership and group membership implemented with ACL's (Access Control Lists),
   opposed to the simplier unix like ugo:rwx system.
   The previous system was only based on action and current user. (lib/main.php)

   Permissions maybe inherited its parent pages, and ultimativly the 
   optional master page (".")
   Pagenames starting with "." have special default permissions.
   For Authentication see WikiUserNew.php, WikiGroup.php and main.php
   Page Permssions are in PhpWiki since v1.3.9 and enabled since v1.4.0

   This file might replace the following functions from main.php:
     Request::_notAuthorized($require_level)
       display the denied message and optionally a login form 
       to gain higher privileges
     Request::getActionDescription($action)
       helper to localize the _notAuthorized message per action, 
       when login is tried.
     Request::getDisallowedActionDescription($action)
       helper to localize the _notAuthorized message per action, 
       when it aborts
     Request::requiredAuthority($action)
       returns the needed user level
       has a hook for plugins on POST
     Request::requiredAuthorityForAction($action)
       just returns the level per action, will be replaced with the 
       action + page pair

     The defined main.php actions map to simplier access types:
       browse => view
       edit   => edit
       create => edit or create
       remove => remove
       rename => change
       store prefs => change
       list in PageList => list
*/

/* Symbolic special ACL groups. Untranslated to be stored in page metadata*/
define('ACL_EVERY',	   '_EVERY');
define('ACL_ANONYMOUS',	   '_ANONYMOUS');
define('ACL_BOGOUSERS',	   '_BOGOUSERS');
define('ACL_HASHOMEPAGE',  '_HASHOMEPAGE');
define('ACL_SIGNED',	   '_SIGNED');
define('ACL_AUTHENTICATED','_AUTHENTICATED');
define('ACL_ADMIN',	   '_ADMIN');
define('ACL_OWNER',	   '_OWNER');
define('ACL_CREATOR',	   '_CREATOR');

// Return an page permissions array for this page.
// To provide ui helpers to view and change page permissions:
//   <tr><th>Group</th><th>Access</th><th>Allow or Forbid</th></tr>
//   <tr><td>$group</td><td>_($access)</td><td> [ ] </td></tr>
function pagePermissions($pagename) {
    global $request;
    $page = $request->getPage($pagename);
    // Page not found (new page); returned inherited permissions, to be displayed in gray
    if (! $page->exists() ) {
        if ($pagename == '.') // stop recursion
            return array('default',new PagePermission());
        else {
            return array('inherited',pagePermissions(getParentPage($pagename)));
        }
    } elseif ($perm = getPagePermissions($page)) {
        return array('page',$perm);
    // or no permissions defined; returned inherited permissions, to be displayed in gray
    } else {
        return array('inherited',pagePermissions(getParentPage($pagename)));
    }
}

function pagePermissionsSimpleFormat($perm_tree,$owner,$group=false) {
    list($type,$perm) = pagePermissionsAcl($perm_tree[0], $perm_tree);
    /*
    $type = $perm_tree[0];
    $perm = pagePermissionsAcl($perm_tree);
    if (is_object($perm_tree[1]))
        $perm = $perm_tree[1];
    elseif (is_array($perm_tree[1])) {
        $perm_tree = pagePermissionsSimpleFormat($perm_tree[1],$owner,$group);
	if (isa($perm_tree[1],'pagepermission'))
	    $perm = $perm_tree[1];
	elseif (isa($perm_tree,'htmlelement'))
            return $perm_tree;
    }
    */
    if ($type == 'page')
        return HTML::tt(HTML::bold($perm->asRwxString($owner,$group).'+'));
    elseif ($type == 'default')
        return HTML::tt($perm->asRwxString($owner,$group));
    elseif ($type == 'inherited') {
        return HTML::tt(array('class'=>'inherited','style'=>'color:#aaa;'),
                        $perm->asRwxString($owner,$group));
    }
}

function pagePermissionsAcl($type,$perm_tree) {
    $perm = $perm_tree[1];
    while (!is_object($perm)) {
        $perm_tree = pagePermissionsAcl($type, $perm);
        $perm = $perm_tree[1];
    }
    return array($type,$perm);
}

// view => who
// edit => who
function pagePermissionsAclFormat($perm_tree,$editable=false) {
    list($type,$perm) = pagePermissionsAcl($perm_tree[0], $perm_tree);
    if ($editable)
        return $perm->asEditableTable($type);
    else
        return $perm->asTable($type);
}

/**
 * Check permission per page.
 * Returns true or false.
 */
function mayAccessPage ($access,$pagename) {
    return _requiredAuthorityForPagename($access,$pagename);
}

/** Check the permissions for the current action.
 * Walk down the inheritance tree. Collect all permissions until 
 * the minimum required level is gained, which is not 
 * overruled by more specific forbid rules.
 * Todo: cache result per access and page in session?
 */
function requiredAuthorityForPage ($action) {
    if (_requiredAuthorityForPagename(action2access($action),
                                      $GLOBALS['request']->getArg('pagename')))
        return $GLOBALS['request']->_user->_level;
    else
        return WIKIAUTH_UNOBTAINABLE;
}

// Translate action or plugin to the simplier access types:
function action2access ($action) {
    global $request;
    switch ($action) {
    case 'browse':
    case 'viewsource':
    case 'diff':
    case 'select':
    case 'xmlrpc':
    case 'search':
    case 'pdf':
        return 'view';
    case 'zip':
    case 'ziphtml':
    case 'dumpserial':
    case 'dumphtml':
        return 'dump';
    case 'edit':
        return 'edit';
    case 'create':
        $page = $request->getPage();
        $current = $page->getCurrentRevision();
        if ($current->hasDefaultContents())
            return 'edit';
        else
            return 'view'; 
        break;
    case 'upload':
    case 'loadfile': 
        // probably create/edit but we cannot check all page permissions, can we?
    case 'remove':
    case 'lock':
    case 'unlock':
    case 'upgrade':
            return 'change';
    default:
        //Todo: Plugins should be able to override its access type
        if (isWikiWord($action))
            return 'view';
        else
            return 'change';
        break;
    }
}

// Recursive helper to do the real work
function _requiredAuthorityForPagename($access, $pagename) {
    global $request;
    $page = $request->getPage($pagename);
    // Page not found; check against default permissions
    if (! $page->exists() ) {
        $perm = new PagePermission();
        return ($perm->isAuthorized($access,$request->_user) === true);
    }
    // no ACL defined; check for special dotfile or walk down
    if (! ($perm = getPagePermissions($page))) { 
        if ($pagename[0] == '.') {
            $perm = new PagePermission(PagePermission::dotPerms());
            return ($perm->isAuthorized($access,$request->_user) === true);
        }
        return _requiredAuthorityForPagename($access,getParentPage($pagename));
    }
    // ACL defined; check if isAuthorized returns true or false or undecided
    $authorized = $perm->isAuthorized($access,$request->_user);
    if ($authorized != -1) // -1 for undecided
        return $authorized;
    else
        return _requiredAuthorityForPagename($access,getParentPage($pagename));
}

/**
 * @param  string $pagename   page from which the parent page is searched.
 * @return string parent      pagename or the (possibly pseudo) dot-pagename.
 */
function getParentPage($pagename) {
    if (isSubPage($pagename)) {
        return subPageSlice($pagename,0);
    } else {
        return '.';
    }
}

// Read the ACL from the page
// Done: Not existing pages should NOT be queried. 
// Check the parent page instead and don't take the default ACL's
function getPagePermissions ($page) {
    if ($hash = $page->get('perm'))  // hash => object
        return new PagePermission(unserialize($hash));
    else 
        return false;
}

// Store the ACL in the page
function setPagePermissions ($page,$perm) {
    $perm->store($page);
}

function getAccessDescription($access) {
    static $accessDescriptions;
    if (! $accessDescriptions) {
        $accessDescriptions = array(
                                    'list'     => _("List this page and all subpages"),
                                    'view'     => _("View this page and all subpages"),
                                    'edit'     => _("Edit this page and all subpages"),
                                    'create'   => _("Create a new (sub)page"),
                                    'dump'     => _("Download the page contents"),
                                    'change'   => _("Change page attributes"),
                                    'remove'   => _("Remove this page"),
                                    );
    }
    if (in_array($access, array_keys($accessDescriptions)))
        return $accessDescriptions[$access];
    else
        return $access;
}

/**
 * The ACL object per page. It is stored in a page, but can also 
 * be merged with ACL's from other pages or taken from the master (pseudo) dot-file.
 *
 * A hash of "access" => "requires" pairs.
 *   "access"   is a shortcut for common actions, which map to main.php actions
 *   "requires" required username or groupname or any special group => true or false
 *
 * Define any special rules here, like don't list dot-pages.
 */ 
class PagePermission {
    var $perm;

    function PagePermission($hash = array()) {
        if (is_array($hash) and !empty($hash)) {
            $accessTypes = $this->accessTypes();
            foreach ($hash as $access => $requires) {
                if (in_array($access,$accessTypes))
                    $this->perm[$access] = $requires;
                else
                    trigger_error(sprintf(_("Unsupported ACL access type %s ignored."),$access),
                                  E_USER_WARNING);
            }
        } else {
            // set default permissions, the so called dot-file acl's
            $this->perm = $this->defaultPerms();
        }
        return $this;
    }

    /**
     * The workhorse to check the user against the current ACL pairs.
     * Must translate the various special groups to the actual users settings 
     * (userid, group membership).
     */
    function isAuthorized($access,$user) {
        if (!empty($this->perm{$access})) {
            foreach ($this->perm[$access] as $group => $bool) {
                if ($this->isMember($user,$group)) {
                    return $bool;
                }
            }
        }
        return -1; // undecided
    }

    /**
     * Translate the various special groups to the actual users settings 
     * (userid, group membership).
     */
    function isMember($user,$group) {
        global $request;
        if ($group === ACL_EVERY) return true;
        $member = &WikiGroup::getGroup($request);
        //$user = & $request->_user;
        if ($group === ACL_ADMIN)   // WIKI_ADMIN or member of _("Administrators")
            return $user->isAdmin() or
                   $member->isMember(GROUP_ADMIN);
        if ($group === ACL_ANONYMOUS) 
            return ! $user->isSignedIn();
        if ($group === ACL_BOGOUSERS)
            if (ENABLE_USER_NEW) return isa($user,'_BogoUser');
            else return isWikiWord($user->UserName());
        if ($group === ACL_HASHOMEPAGE)
            return $user->hasHomePage();
        if ($group === ACL_SIGNED)
            return $user->isSignedIn();
        if ($group === ACL_AUTHENTICATED)
            return $user->isAuthenticated();
        if ($group === ACL_OWNER) {
            $page = $request->getPage();
            return $page->get('author') === $user->UserName();
        }
        if ($group === ACL_CREATOR) {
            $page = $request->getPage();
            $rev = $page->getRevision(1);
            return $rev->get('author') === $user->UserName();
        }
        /* Or named groups or usernames.
         Note: We don't seperate groups and users here. 
         Users overrides groups with the same name. */
        return $user->UserName() === $group or
               $member->isMember($group);
    }

    /**
     * returns hash of default permissions.
     * check if the page '.' exists and returns this instead.
     */
    function defaultPerms() {
        //Todo: check for the existance of '.' and take this instead.
        //Todo: honor more index.php auth settings here
        $perm = array('view'   => array(ACL_EVERY => true),
                      'edit'   => array(ACL_EVERY => true),
                      'create' => array(ACL_EVERY => true),
                      'list'   => array(ACL_EVERY => true),
                      'remove' => array(ACL_ADMIN => true,
                                        ACL_OWNER => true),
                      'change' => array(ACL_ADMIN => true,
                                        ACL_OWNER => true));
        if (defined('ZIPDUMP_AUTH') && ZIPDUMP_AUTH)
            $perm['dump'] = array(ACL_ADMIN => true,
                                  ACL_OWNER => true);
        else
            $perm['dump'] = array(ACL_EVERY => true);
        if (defined('REQUIRE_SIGNIN_BEFORE_EDIT') && REQUIRE_SIGNIN_BEFORE_EDIT)
            $perm['edit'] = array(ACL_SIGNED => true);
        if (defined('ALLOW_ANON_USER') && ! ALLOW_ANON_USER) {
            if (defined('ALLOW_BOGO_USER') && ALLOW_BOGO_USER) {
                $perm['view'] = array(ACL_BOGOUSER => true);
                $perm['edit'] = array(ACL_BOGOUSER => true);
            } elseif (defined('ALLOW_USER_PASSWORDS') && ALLOW_USER_PASSWORDS) {
                $perm['view'] = array(ACL_AUTHENTICATED => true);
                $perm['edit'] = array(ACL_AUTHENTICATED => true);
            } else {
                $perm['view'] = array(ACL_SIGNED => true);
                $perm['edit'] = array(ACL_SIGNED => true);
            }
        }
        return $perm;
    }

    /**
     * returns list of all supported access types.
     */
    function accessTypes() {
        return array_keys($this->defaultPerms());
    }

    /**
     * special permissions for dot-files, beginning with '.'
     * maybe also for '_' files?
     */
    function dotPerms() {
        $def = array(ACL_ADMIN => true,
                     ACL_OWNER => true);
        $perm = array();
        foreach ($this->accessTypes() as $access) {
            $perm[$access] = $def;
        }
        return $perm;
    }

    /**
     *  dead code. not needed inside the object. see getPagePermissions($page)
     */
    function retrieve($page) {
        $hash = $page->get('perm');
        if ($hash)  // hash => object
            return new PagePermission(unserialize($hash));
        else 
            return new PagePermission();
    }

    function store($page) {
        // object => hash
        return $page->set('perm',serialize(obj2hash($this->perm)));
    }

    /* type: page, default, inherited */
    function asTable($type) {
        $table = HTML::table();
        foreach ($this->perm as $access => $perms) {
            $td = HTML::table(array('class' => 'cal','valign' => 'top'));
            foreach ($perms as $group => $bool) {
                $td->pushContent(HTML::tr(HTML::td(array('align'=>'right'),$group),
                                                   HTML::td($bool ? '[X]' : '[ ]')));
            }
            $table->pushContent(HTML::tr(array('valign' => 'top'),
                                         HTML::td($access),HTML::td($td)));
        }
        if ($type == 'default')
            $table->setAttr('style','border: dotted thin black; background-color:#eee;');
        elseif ($type == 'inherited')
            $table->setAttr('style','border: dotted thin black; background-color:#ddd;');
        elseif ($type == 'page')
            $table->setAttr('style','border: solid thin black; font-weight: bold;');
        return $table;
    }

    /* type: page, default, inherited */
    function asEditableTable($type) {
        $table = HTML::table();
        $table->pushContent(HTML::tr(array('valign' => 'top'),
                                     HTML::th(array('align' => 'left'),'access'),
                                     HTML::th(array('align'=>'right'),
                                              'Group/User'),
                                     HTML::th(),
                                     HTML::th('Description')));
        foreach ($this->perm as $access => $perms) {
            //$permlist = HTML::table(array('class' => 'cal','valign' => 'top'));
            $first_only = true;
            foreach ($perms as $group => $bool) {
                $checkbox = HTML::input(array('type' => 'checkbox',
                                              'name' => "acl[$access][$group]",
                                              'value' => true));
                if ($bool) $checkbox->setAttr('checked','checked');
                if ($first_only) {
                    $table->pushContent(HTML::tr(array('valign' => 'top'),
                                                 HTML::td(HTML::strong($access.":")),
                                                 HTML::td(array('class' => 'cal-today','align'=>'right'),
                                                          constant("GROUP".$group)),
                                                 HTML::td($checkbox),
                                                 HTML::td(HTML::em(getAccessDescription($access)))));
                    $first_only = false;
                } else {
                    $table->pushContent(HTML::tr(array('valign' => 'top'),
                                                 HTML::td(),
                                                 HTML::td(array('class' => 'cal-today','align'=>'right'),
                                                          constant("GROUP".$group)),
                                                 HTML::td($checkbox),
                                                 HTML::td()));
                }
            }
            /*
            $table->pushContent(HTML::tr(array('valign' => 'top'),
                                         HTML::td(HTML::strong($access)),
                                         HTML::td($permlist),
                                         HTML::td(HTML::em(getAccessDescription($access)))));
            */
        }
        if ($type == 'default')
            $table->setAttr('style','border: dotted thin black; background-color:#eee;');
        elseif ($type == 'inherited')
            $table->setAttr('style','border: dotted thin black; background-color:#ddd;');
        elseif ($type == 'page')
            $table->setAttr('style','border: solid thin black; font-weight: bold;');
        return $table;
    }

    // this is just a bad hack for testing
    // simplify the ACL to a unix-like "rwx------" string
    function asRwxString($owner,$group=false) {
        global $request;
        // simplify object => rwxrw---x+ string as in cygwin (+ denotes additional ACLs)
        $perm =& $this->perm;
        // get effective user and group
        $s = '---------';
        if (isset($perm['view'][$owner]) or 
            (isset($perm['view'][ACL_AUTHENTICATED]) and $request->_user->isAuthenticated()))
            $s[0] = 'r';
        if (isset($perm['edit'][$owner]) or 
            (isset($perm['edit'][ACL_AUTHENTICATED]) and $request->_user->isAuthenticated()))
            $s[1] = 'w';
        if (isset($perm['change'][$owner]) or 
            (isset($perm['change'][ACL_AUTHENTICATED]) and $request->_user->isAuthenticated()))
            $s[2] = 'x';
        if (!empty($group)) {
            if (isset($perm['view'][$group]) or 
                (isset($perm['view'][ACL_AUTHENTICATED]) and $request->_user->isAuthenticated()))
                $s[3] = 'r';
            if (isset($perm['edit'][$group]) or 
                (isset($perm['edit'][ACL_AUTHENTICATED]) and $request->_user->isAuthenticated()))
                $s[4] = 'w';
            if (isset($perm['change'][$group]) or 
                (isset($perm['change'][ACL_AUTHENTICATED]) and $request->_user->isAuthenticated()))
                $s[5] = 'x';
        }
        if (isset($perm['view'][ACL_EVERY]) or 
            (isset($perm['view'][ACL_AUTHENTICATED]) and $request->_user->isAuthenticated()))
            $s[6] = 'r';
        if (isset($perm['edit'][ACL_EVERY]) or 
            (isset($perm['edit'][ACL_AUTHENTICATED]) and $request->_user->isAuthenticated()))
            $s[7] = 'w';
        if (isset($perm['change'][ACL_EVERY]) or 
            (isset($perm['change'][ACL_AUTHENTICATED]) and $request->_user->isAuthenticated()))
            $s[8] = 'x';
        return $s;
    }
}

// $Log$
// Revision 1.12  2004/05/04 22:34:25  rurban
// more pdf support
//
// Revision 1.11  2004/05/02 21:26:38  rurban
// limit user session data (HomePageHandle and auth_dbi have to invalidated anyway)
//   because they will not survive db sessions, if too large.
// extended action=upgrade
// some WikiTranslation button work
// revert WIKIAUTH_UNOBTAINABLE (need it for main.php)
// some temp. session debug statements
//
// Revision 1.10  2004/04/29 22:32:56  zorloc
// Slightly more elegant fix.  Instead of WIKIAUTH_FORBIDDEN, the current user's level + 1 is returned on a false.
//
// Revision 1.9  2004/04/29 17:18:19  zorloc
// Fixes permission failure issues.  With PagePermissions and Disabled Actions when user did not have permission WIKIAUTH_FORBIDDEN was returned.  In WikiUser this was ok because WIKIAUTH_FORBIDDEN had a value of 11 -- thus no user could perform that action.  But WikiUserNew has a WIKIAUTH_FORBIDDEN value of -1 -- thus a user without sufficent permission to do anything.  The solution is a new high value permission level (WIKIAUTH_UNOBTAINABLE) to be the default level for access failure.
//
// Revision 1.8  2004/03/14 16:24:35  rurban
// authenti(fi)cation spelling
//
// Revision 1.7  2004/02/28 22:25:07  rurban
// First PagePerm implementation:
//
// $Theme->setAnonEditUnknownLinks(false);
//
// Layout improvement with dangling links for mostly closed wiki's:
// If false, only users with edit permissions will be presented the
// special wikiunknown class with "?" and Tooltip.
// If true (default), any user will see the ?, but will be presented
// the PrintLoginForm on a click.
//
// Revision 1.6  2004/02/24 15:20:05  rurban
// fixed minor warnings: unchecked args, POST => Get urls for sortby e.g.
//
// Revision 1.5  2004/02/23 21:30:25  rurban
// more PagePerm stuff: (working against 1.4.0)
//   ACL editing and simplification of ACL's to simple rwx------ string
//   not yet working.
//
// Revision 1.4  2004/02/12 13:05:36  rurban
// Rename functional for PearDB backend
// some other minor changes
// SiteMap comes with a not yet functional feature request: includepages (tbd)
//
// Revision 1.3  2004/02/09 03:58:12  rurban
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
// Revision 1.2  2004/02/08 13:17:48  rurban
// This should be the functionality. Needs testing and some minor todos.
//
// Revision 1.1  2004/02/08 12:29:30  rurban
// initial version, not yet hooked into lib/main.php
//
//

// Local Variables:
// mode: php
// tab-width: 8
// c-basic-offset: 4
// c-hanging-comment-ender-p: nil
// indent-tabs-mode: nil
// End:
?>
