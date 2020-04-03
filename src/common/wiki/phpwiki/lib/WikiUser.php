<?php
//-*-php-*-
rcs_id('$Id: WikiUser.php,v 1.65 2005/06/05 05:38:02 rurban Exp $');

// It is anticipated that when userid support is added to phpwiki,
// this object will hold much more information (e-mail,
// home(wiki)page, etc.) about the user.

// There seems to be no clean way to "log out" a user when using HTTP
// authentication. So we'll hack around this by storing the currently
// logged in username and other state information in a cookie.

// 2002-09-08 11:44:04 rurban
// Todo: Fix prefs cookie/session handling:
//       _userid and _homepage cookie/session vars still hold the
//       serialized string.
//       If no homepage, fallback to prefs in cookie as in 1.3.3.

define('WIKIAUTH_FORBIDDEN', -1); // Completely not allowed.
define('WIKIAUTH_ANON', 0);
define('WIKIAUTH_BOGO', 1);     // any valid WikiWord is enough
define('WIKIAUTH_USER', 2);     // real auth from a database/file/server.

define('WIKIAUTH_ADMIN', 10);  // Wiki Admin
define('WIKIAUTH_UNOBTAINABLE', 100);  // Permissions that no user can achieve

if (!defined('COOKIE_EXPIRATION_DAYS')) {
    define('COOKIE_EXPIRATION_DAYS', 365);
}
if (!defined('COOKIE_DOMAIN')) {
    define('COOKIE_DOMAIN', '/');
}

$UserPreferences = array(
                         'userid'        => new _UserPreference(''), // really store this also?
                         'passwd'        => new _UserPreference(''),
                         'email'         => new _UserPreference(''),
                         'emailVerified' => new _UserPreference_bool(),
                         'notifyPages'   => new _UserPreference(''),
                         'theme'         => new _UserPreference_theme(THEME),
                         'lang'          => new _UserPreference_language(DEFAULT_LANGUAGE),
                         'editWidth'     => new _UserPreference_int(80, 30, 150),
                         'noLinkIcons'   => new _UserPreference_bool(),
                         'editHeight'    => new _UserPreference_int(22, 5, 80),
                         'timeOffset'    => new _UserPreference_numeric(0, -26, 26),
                         'relativeDates' => new _UserPreference_bool(),
                         'googleLink'    => new _UserPreference_bool(), // 1.3.10
                         'doubleClickEdit' => new _UserPreference_bool(), // 1.3.11
                         );

function WikiUserClassname()
{
    return 'WikiUser';
}

function UpgradeUser($olduser, $user)
{
    if (isa($user, 'WikiUser') and isa($olduser, 'WikiUser')) {
        // populate the upgraded class with the values from the old object
        foreach (get_object_vars($olduser) as $k => $v) {
            $user->$k = $v;
        }
        $GLOBALS['request']->_user = $user;
        return $user;
    } else {
        return false;
    }
}

class WikiUser
{
    public $_userid = false;
    public $_level  = false;
    public $_request;
    public $_dbi;
    public $_authdbi;
    public $_homepage;
    public $_authmethod = '';
    public $_authhow = '';

    /**
     *
     *
     * Populates the instance variables and calls $this->_ok()
     * to ensure that the parameters are valid.
     * @param mixed $userid String of username or WikiUser object.
     * @param int $authlevel Authorization level.
     */
    public function __construct(&$request, $userid = false, $authlevel = false)
    {
        $this->_request = &$request;
        $this->_dbi = &$this->_request->getDbh();

        if (isa($userid, 'WikiUser')) {
            $this->_userid   = $userid->_userid;
            $this->_level    = $userid->_level;
        } else {
            $this->_userid = $userid;
            $this->_level = $authlevel;
        }
        if (!$this->_ok()) {
            // Paranoia: if state is at all inconsistent, log out...
            $this->_userid = false;
            $this->_level = false;
            $this->_homepage = false;
            $this->_authhow .= ' paranoia logout';
        }
        if ($this->_userid) {
            $this->_homepage = $this->_dbi->getPage($this->_userid);
        }
    }

    /**
    * Get the string indicating how the user was authenticated.
    *
    * Get the string indicating how the user was authenticated.
    * Does not seem to be set - jbw
    * @return string The method of authentication.
    */
    public function auth_how()
    {
        return $this->_authhow;
    }

    /**
     * Invariant
     *
     * If the WikiUser object has a valid authorization level and the
     * userid is a string returns true, else false.
     * @return bool If valid level and username string true, else false
     */
    public function _ok()
    {
        if (
            (in_array($this->_level, array(WIKIAUTH_BOGO,
                                           WIKIAUTH_USER,
                                           WIKIAUTH_ADMIN))
            &&
            (is_string($this->_userid)))
        ) {
            return true;
        }
        return false;
    }

    public function UserName()
    {
        return $this->_userid;
    }

    public function getId()
    {
        return ( $this->isSignedIn()
                 ? $this->_userid
                 : $this->_request->get('REMOTE_ADDR') ); // FIXME: globals
    }

    public function getAuthenticatedId()
    {
        return ( $this->isAuthenticated()
                 ? $this->_userid
                 : $this->_request->get('REMOTE_ADDR') ); // FIXME: globals
    }

    public function isSignedIn()
    {
        return $this->_level >= WIKIAUTH_BOGO;
    }

    public function isAuthenticated()
    {
        return $this->_level >= WIKIAUTH_BOGO;
    }

    public function isAdmin()
    {
        return $this->_level == WIKIAUTH_ADMIN;
    }

    public function hasAuthority($require_level)
    {
        return $this->_level >= $require_level;
    }

    public function isValidName($userid = false)
    {
        if (!$userid) {
            $userid = $this->_userid;
        }
        return preg_match("/^[\w\.@\-]+$/", $userid) and strlen($userid) < 32;
    }

    public function AuthCheck($postargs)
    {
        // Normalize args, and extract.
        $keys = array('userid', 'passwd', 'require_level', 'login', 'logout',
                      'cancel');
        foreach ($keys as $key) {
            $args[$key] = isset($postargs[$key]) ? $postargs[$key] : false;
        }
        extract($args);
        $require_level = max(0, min(WIKIAUTH_ADMIN, (int) $require_level));

        if ($logout) {
            return new WikiUser($this->_request); // Log out
        } elseif ($cancel) {
            return false;        // User hit cancel button.
        } elseif (!$login && !$userid) {
            return false;       // Nothing to do?
        }

        if (!$this->isValidName($userid)) {
            return _("Invalid username.");
        }

        $authlevel = $this->_pwcheck($userid, $passwd);
        if (!$authlevel) {
            return _("Invalid password or userid.");
        } elseif ($authlevel < $require_level) {
            return _("Insufficient permissions.");
        }

        // Successful login.
        $user = new WikiUser($this->_request, $userid, $authlevel);
        return $user;
    }

    public function PrintLoginForm(
        &$request,
        $args,
        $fail_message = false,
        $seperate_page = true
    ) {
        include_once('lib/Template.php');
        // Call update_locale in case the system's default language is not 'en'.
        // (We have no user pref for lang at this point yet, no one is logged in.)
        update_locale(DEFAULT_LANGUAGE);
        $userid = '';
        $require_level = 0;
        extract($args); // fixme

        $require_level = max(0, min(WIKIAUTH_ADMIN, (int) $require_level));

        $pagename = $request->getArg('pagename');
        $login = new Template(
            'login',
            $request,
            compact(
                'pagename',
                'userid',
                'require_level',
                'fail_message',
                'pass_required'
            )
        );
        if ($seperate_page) {
            $request->discardOutput();
            $page = $request->getPage($pagename);
            $revision = $page->getCurrentRevision();
            return GeneratePage($login, _("Sign In"), $revision);
        } else {
            return $login;
        }
    }

    /**
     * Check password.
     */
    public function _pwcheck($userid, $passwd)
    {
        return false;
    }

    // Todo: try our WikiDB backends.
    public function getPreferences()
    {
        // Restore saved preferences.
        $prefs = $this->_request->getSessionVar('wiki_prefs');

        // before we get his prefs we should check if he is signed in
        if (USE_PREFS_IN_PAGE && $this->homePage()) { // in page metadata
            // old array
            if ($pref = $this->_homepage->get('pref')) {
                //trigger_error("pref=".$pref);//debugging
                $prefs = unserialize($pref);
            }
        }
        return new UserPreferences($prefs);
    }

    // No cookies anymore for all prefs, only the userid. PHP creates
    // a session cookie in memory, which is much more efficient,
    // but not persistent. Get persistency with a homepage or DB Prefs
    //
    // Return the number of changed entries
    public function setPreferences($prefs, $id_only = false)
    {
        if (!is_object($prefs)) {
            $prefs = new UserPreferences($prefs);
        }
        // update the session and id
        $this->_request->setSessionVar('wiki_prefs', $prefs);
        // simple unpacked cookie
        if ($this->_userid) {
            setcookie('WIKI_ID', $this->_userid, 365, '/');
        }

        // We must ensure that any password is encrypted.
        // We don't need any plaintext password.
        if (! $id_only) {
            if ($this->isSignedIn()) {
                if ($this->isAdmin()) {
                    $prefs->set('passwd', '');
                }
                // already stored in config/config.ini, and it might be
                // plaintext! well oh well
                if ($homepage = $this->homePage()) {
                    // check for page revision 0
                    if (! $this->_dbi->isWikiPage($this->_userid)) {
                        trigger_error(
                            _("Your home page has not been created yet so your preferences cannot not be saved."),
                            E_USER_WARNING
                        );
                    } else {
                        if ($this->isAdmin() || !$homepage->get('locked')) {
                            $homepage->set('pref', serialize($prefs->_prefs));
                            return sizeof($prefs->_prefs);
                        } else {
                            // An "empty" page could still be
                            // intentionally locked by admin to
                            // prevent its creation.
                            //
                            // FIXME: This permission situation should
                            // probably be handled by the DB backend,
                            // once the new WikiUser code has been
                            // implemented.
                            trigger_error(
                                _("Your home page is locked so your preferences cannot not be saved.")
                                          . " " . _("Please contact your PhpWiki administrator for assistance."),
                                E_USER_WARNING
                            );
                        }
                    }
                } else {
                    trigger_error(
                        "No homepage for user found. Creating one...",
                        E_USER_WARNING
                    );
                    $this->createHomepage($prefs);
                    //$homepage->set('pref', serialize($prefs->_prefs));
                    return sizeof($prefs->_prefs);
                }
            } else {
                trigger_error("you must be signed in", E_USER_WARNING);
            }
        }
        return 0;
    }

    // check for homepage with user flag.
    // can be overriden from the auth backends
    public function exists()
    {
        $homepage = $this->homePage();
        return ($this->_userid && $homepage && $homepage->get('pref'));
    }

    // doesn't check for existance!!! hmm.
    // how to store metadata in not existing pages? how about versions?
    public function homePage()
    {
        if (!$this->_userid) {
            return false;
        }
        if (!empty($this->_homepage)) {
            return $this->_homepage;
        } else {
            $this->_homepage = $this->_dbi->getPage($this->_userid);
            return $this->_homepage;
        }
    }

    public function hasHomePage()
    {
        return !$this->homePage();
    }

    // create user by checking his homepage
    public function createUser($pref, $createDefaultHomepage = true)
    {
        if ($this->exists()) {
            return;
        }
        if ($createDefaultHomepage) {
            $this->createHomepage($pref);
        } else {
            // empty page
            include "lib/loadsave.php";
            $pageinfo = array('pagedata' => array('pref' => serialize($pref->_pref)),
                              'versiondata' => array('author' => $this->_userid),
                              'pagename' => $this->_userid,
                              'content' => _('CategoryHomepage'));
            SavePage($this->_request, $pageinfo, false, false);
        }
        $this->setPreferences($pref);
    }

    // create user and default user homepage
    public function createHomepage($pref)
    {
        $pagename = $this->_userid;
        include "lib/loadsave.php";

        // create default homepage:
        //  properly expanded template and the pref metadata
        $template = Template('homepage.tmpl', $this->_request);
        $text  = $template->getExpansion();
        $pageinfo = array('pagedata' => array('pref' => serialize($pref->_pref)),
                          'versiondata' => array('author' => $this->_userid),
                          'pagename' => $pagename,
                          'content' => $text);
        SavePage($this->_request, $pageinfo, false, false);

        // create Calender
        $pagename = $this->_userid . SUBPAGE_SEPARATOR . _('Preferences');
        if (! isWikiPage($pagename)) {
            $pageinfo = array('pagedata' => array(),
                              'versiondata' => array('author' => $this->_userid),
                              'pagename' => $pagename,
                              'content' => "<?plugin Calender ?>\n");
            SavePage($this->_request, $pageinfo, false, false);
        }

        // create Preferences
        $pagename = $this->_userid . SUBPAGE_SEPARATOR . _('Preferences');
        if (! isWikiPage($pagename)) {
            $pageinfo = array('pagedata' => array(),
                              'versiondata' => array('author' => $this->_userid),
                              'pagename' => $pagename,
                              'content' => "<?plugin UserPreferences ?>\n");
            SavePage($this->_request, $pageinfo, false, false);
        }
    }

    public function tryAuthBackends()
    {
        return ''; // crypt('') will never be ''
    }

    // Auth backends must store the crypted password where?
    // Not in the preferences.
    public function checkPassword($passwd)
    {
        $prefs = $this->getPreferences();
        $stored_passwd = $prefs->get('passwd'); // crypted
        if (empty($prefs->_prefs['passwd'])) {    // not stored in the page
            // allow empty passwords? At least store a '*' then.
            // try other backend. hmm.
            $stored_passwd = $this->tryAuthBackends($this->_userid);
        }
        if (empty($stored_passwd)) {
            trigger_error(sprintf(
                _("Old UserPage %s without stored password updated with empty password. Set a password in your UserPreferences."),
                $this->_userid
            ), E_USER_NOTICE);
            $prefs->set('passwd', '*');
            return true;
        }
        if ($stored_passwd == '*') {
            return true;
        }
        if (
            !empty($passwd)
             && crypt($passwd, $stored_passwd) == $stored_passwd
        ) {
            return true;
        } else {
            return false;
        }
    }

    public function changePassword($newpasswd, $passwd2 = false)
    {
        trigger_error(sprintf(
            "Attempt to change an external password for '%s'. Not allowed!",
            $this->_userid
        ), E_USER_ERROR);
        return false;
    }

    public function mayChangePass()
    {
        // on external DBAuth maybe. on IMAP or LDAP not
        // on internal DBAuth yes
        if (in_array($this->_authmethod, array('IMAP', 'LDAP'))) {
            return false;
        }
        if ($this->isAdmin()) {
            return false;
        }
        if ($this->_authmethod == 'pagedata') {
            return true;
        }
        if ($this->_authmethod == 'authdb') {
            return true;
        }
    }
}

// create user and default user homepage
// FIXME: delete this, not used?
/*
function createUser ($userid, $pref) {
    global $request;
    $user = new WikiUser ($request, $userid);
    $user->createUser($pref);
}
*/

class _UserPreference
{
    public function __construct($default_value)
    {
        $this->default_value = $default_value;
    }

    public function sanify($value)
    {
        return (string) $value;
    }

    public function update($value)
    {
    }
}

class _UserPreference_numeric extends _UserPreference
{
    public function __construct(
        $default,
        $minval = false,
        $maxval = false
    ) {
        parent::__construct((double) $default);
        $this->_minval = (double) $minval;
        $this->_maxval = (double) $maxval;
    }

    public function sanify($value)
    {
        $value = (double) $value;
        if ($this->_minval !== false && $value < $this->_minval) {
            $value = $this->_minval;
        }
        if ($this->_maxval !== false && $value > $this->_maxval) {
            $value = $this->_maxval;
        }
        return $value;
    }
}

class _UserPreference_int extends _UserPreference_numeric
{
    public function __construct($default, $minval = false, $maxval = false)
    {
        parent::__construct(
            (int) $default,
            (int) $minval,
            (int) $maxval
        );
    }

    public function sanify($value)
    {
        return (int) parent::sanify((int) $value);
    }
}

class _UserPreference_bool extends _UserPreference
{
    public function __construct($default = false)
    {
        parent::__construct((bool) $default);
    }

    public function sanify($value)
    {
        if (is_array($value)) {
            /* This allows for constructs like:
             *
             *   <input type="hidden" name="pref[boolPref][]" value="0" />
             *   <input type="checkbox" name="pref[boolPref][]" value="1" />
             *
             * (If the checkbox is not checked, only the hidden input
             * gets sent. If the checkbox is sent, both inputs get
             * sent.)
             */
            foreach ($value as $val) {
                if ($val) {
                    return true;
                }
            }
            return false;
        }
        return (bool) $value;
    }
}

class _UserPreference_language extends _UserPreference
{
    public function __construct($default = DEFAULT_LANGUAGE)
    {
        parent::__construct($default);
    }

    // FIXME: check for valid locale
    public function sanify($value)
    {
        // Revert to DEFAULT_LANGUAGE if user does not specify
        // language in UserPreferences or chooses <system language>.
        if ($value == '' or empty($value)) {
            $value = DEFAULT_LANGUAGE;
        }

        return (string) $value;
    }
}

class _UserPreference_theme extends _UserPreference
{
    public function __construct($default = THEME)
    {
        parent::__construct($default);
    }

    public function sanify($value)
    {
        if (findFile($this->_themefile($value), true)) {
            return $value;
        }
        return $this->default_value;
    }

    public function update($newvalue)
    {
        global $WikiTheme;
        include_once($this->_themefile($newvalue));
        if (empty($WikiTheme)) {
            include_once($this->_themefile(THEME));
        }
    }

    public function _themefile($theme)
    {
        return "themes/$theme/themeinfo.php";
    }
}

// don't save default preferences for efficiency.
class UserPreferences
{
    public function __construct($saved_prefs = false)
    {
        $this->_prefs = array();

        if (isa($saved_prefs, 'UserPreferences') && $saved_prefs->_prefs) {
            foreach ($saved_prefs->_prefs as $name => $value) {
                $this->set($name, $value);
            }
        } elseif (is_array($saved_prefs)) {
            foreach ($saved_prefs as $name => $value) {
                $this->set($name, $value);
            }
        }
    }

    public function _getPref($name)
    {
        global $UserPreferences;
        if (!isset($UserPreferences[$name])) {
            if ($name == 'passwd2') {
                return false;
            }
            trigger_error("$name: unknown preference", E_USER_NOTICE);
            return false;
        }
        return $UserPreferences[$name];
    }

    public function get($name)
    {
        if (isset($this->_prefs[$name])) {
            return $this->_prefs[$name];
        }
        if (!($pref = $this->_getPref($name))) {
            return false;
        }
        return $pref->default_value;
    }

    public function set($name, $value)
    {
        if (!($pref = $this->_getPref($name))) {
            return false;
        }

        $newvalue = $pref->sanify($value);
        $oldvalue = $this->get($name);

        // update on changes
        if ($newvalue != $oldvalue) {
            $pref->update($newvalue);
        }

        // don't set default values to save space (in cookies, db and
        // sesssion)
        if ($value == $pref->default_value) {
            unset($this->_prefs[$name]);
        } else {
            $this->_prefs[$name] = $newvalue;
        }
    }

    public function pack($nonpacked)
    {
        return serialize($nonpacked);
    }
    public function unpack($packed)
    {
        if (!$packed) {
            return false;
        }
        if (substr($packed, 0, 2) == "O:") {
            // Looks like a serialized object
            return unserialize($packed);
        }
        //trigger_error("DEBUG: Can't unpack bad UserPreferences",
        //E_USER_WARNING);
        return false;
    }

    public function hash()
    {
        return wikihash($this->_prefs);
    }
}

// $Log: WikiUser.php,v $
// Revision 1.65  2005/06/05 05:38:02  rurban
// Default ENABLE_DOUBLECLICKEDIT = false. Moved to UserPreferences
//
// Revision 1.64  2005/02/08 13:25:50  rurban
// encrypt password. fix strict logic.
// both bugs reported by Mikhail Vladimirov
//
// Revision 1.63  2005/01/21 14:07:50  rurban
// reformatting
//
// Revision 1.62  2004/11/21 11:59:16  rurban
// remove final \n to be ob_cache independent
//
// Revision 1.61  2004/10/21 21:02:04  rurban
// fix seperate page login
//
// Revision 1.60  2004/06/15 09:15:52  rurban
// IMPORTANT: fixed passwd handling for passwords stored in prefs:
//   fix encrypted usage, actually store and retrieve them from db
//   fix bogologin with passwd set.
// fix php crashes with call-time pass-by-reference (references wrongly used
//   in declaration AND call). This affected mainly Apache2 and IIS.
//   (Thanks to John Cole to detect this!)
//
// Revision 1.59  2004/06/14 11:31:36  rurban
// renamed global $Theme to $WikiTheme (gforge nameclash)
// inherit PageList default options from PageList
//   default sortby=pagename
// use options in PageList_Selectable (limit, sortby, ...)
// added action revert, with button at action=diff
// added option regex to WikiAdminSearchReplace
//
// Revision 1.58  2004/06/04 20:32:53  rurban
// Several locale related improvements suggested by Pierrick Meignen
// LDAP fix by John Cole
// reanable admin check without ENABLE_PAGEPERM in the admin plugins
//
// Revision 1.57  2004/06/04 12:40:21  rurban
// Restrict valid usernames to prevent from attacks against external auth or compromise
// possible holes.
// Fix various WikiUser old issues with default IMAP,LDAP,POP3 configs. Removed these.
// Fxied more warnings
//
// Revision 1.56  2004/06/03 12:36:03  rurban
// fix eval warning on signin
//
// Revision 1.55  2004/06/03 09:39:51  rurban
// fix LDAP injection (wildcard in username) detected by Steve Christey, MITRE
//
// Revision 1.54  2004/04/29 17:18:19  zorloc
// Fixes permission failure issues.  With PagePermissions and Disabled Actions when user did not have permission WIKIAUTH_FORBIDDEN was returned.  In WikiUser this was ok because WIKIAUTH_FORBIDDEN had a value of 11 -- thus no user could perform that action.  But WikiUserNew has a WIKIAUTH_FORBIDDEN value of -1 -- thus a user without sufficent permission to do anything.  The solution is a new high value permission level (WIKIAUTH_UNOBTAINABLE) to be the default level for access failure.
//
// Revision 1.53  2004/04/10 05:34:35  rurban
// sf bug#830912
//
// Revision 1.52  2004/04/10 02:55:48  rurban
// fixed old WikiUser
//
// Revision 1.51  2004/04/06 20:00:10  rurban
// Cleanup of special PageList column types
// Added support of plugin and theme specific Pagelist Types
// Added support for theme specific UserPreferences
// Added session support for ip-based throttling
//   sql table schema change: ALTER TABLE session ADD sess_ip CHAR(15);
// Enhanced postgres schema
// Added DB_Session_dba support
//
// Revision 1.50  2004/02/26 01:32:03  rurban
// fixed session login with old WikiUser object. strangely, the errormask gets corruoted to 1, Pear???
//
// Revision 1.49  2004/02/15 21:34:37  rurban
// PageList enhanced and improved.
// fixed new WikiAdmin... plugins
// editpage, Theme with exp. htmlarea framework
//   (htmlarea yet committed, this is really questionable)
// WikiUser... code with better session handling for prefs
// enhanced UserPreferences (again)
// RecentChanges for show_deleted: how should pages be deleted then?
//
// Revision 1.48  2004/02/01 09:14:11  rurban
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
// Revision 1.47  2004/01/27 23:23:39  rurban
// renamed ->Username => _userid for consistency
// renamed mayCheckPassword => mayCheckPass
// fixed recursion problem in WikiUserNew
// fixed bogo login (but not quite 100% ready yet, password storage)
//
// Revision 1.46  2004/01/26 09:17:48  rurban
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
// Revision 1.45  2003/12/09 20:00:43  carstenklapp
// Bugfix: The last BogoUserPrefs-bugfix prevented the admin from saving
// prefs into his own homepage, fixed broken logic. Tightened up BogoUser
// prefs saving ability by checking for true existance of homepage
// (previously a page revision of 0 also counted as valid, again due to
// somewhat flawed logic).
//
// Revision 1.44  2003/12/06 04:56:23  carstenklapp
// Security bugfix (minor): Prevent BogoUser~s from saving extraneous
// _pref object meta-data within locked pages.
//
// Previously, BogoUser~s who signed in with a (valid) WikiWord such as
// "HomePage" could actually save preferences into that page, even though
// it was already locked by the administrator. Thus, any subsequent
// WikiLink~s to that page would become prefixed with "that nice little"
// UserIcon, as if that page represented a valid user.
//
// Note that the admin can lock (even) non-existant pages as desired or
// necessary (i.e. any DB page whose revision==0), to prevent the
// arbitrary BogoUser from saving preference metadata into such a page;
// for example, the silly WikiName "@qmgi`Vcft_x|" (that is the
// \$examplechars presented in login.tmpl, in case it is not visible here
// in the CVS comments).
//
// http://phpwiki.sourceforge.net/phpwiki/
// %C0%F1%ED%E7%E9%E0%D6%E3%E6%F4%DF%F8%FC?action=lock
//
// To remove the prefs metadata from a page, the admin can use the
// EditMetaData plugin, enter pref as the key, leave the value box empty
// and then submit the change. For example:
//
// http://phpwiki.sourceforge.net/phpwiki/
// _EditMetaData?page=%C0%F1%ED%E7%E9%E0%D6%E3%E6%F4%DF%F8%FC
//
// (It seems a rethinking of WikiUserNew.php with its WikiUser and
// UserPreferences classes is in order. Ideally the WikiDB would
// transparently handle such a situation, perhaps BogoUser~s should
// simply be restricted to saving preferences into a cookie until his/her
// e-mail address has been verified.)
//
// Revision 1.43  2003/12/04 19:33:30  carstenklapp
// Bugfix: Under certain PhpWiki installations (such as the PhpWiki at
// SF), the user was unable to select a theme other than the server's
// default. (Use the more robust Theme::findFile instead of PHP's
// file_exists function to detect installed themes).
//
// Revision 1.42  2003/11/30 18:18:13  carstenklapp
// Minor code optimization: use include_once instead of require_once
// inside functions that might not always called.
//
// Revision 1.41  2003/11/21 21:32:39  carstenklapp
// Bugfix: When DEFAULT_LANGUAGE was not 'en', a user's language prefs
// would revert to 'en' when the default <system language> was selected
// in UserPreferences and the user saved his preferences. (Check for
// empty or blank language pref in sanify function of class
// _UserPreference_language and return DEFAULT_LANGUAGE if nothing or
// default selected in UserPreferences.)
//
// Revision 1.40  2003/11/21 16:54:58  carstenklapp
// Bugfix: login.tmpl was always displayed in English despite
// DEFAULT_LANGUAGE set in index.php. (Added call to
// update_locale(DEFAULT_LANGUAGE) before printing login form).
//
// Revision 1.39  2003/10/28 21:13:46  carstenklapp
// Security bug fix for admin password, submitted by Julien Charbon.
//
// Revision 1.38  2003/09/13 22:25:38  carstenklapp
// Hook for new user preference 'noLinkIcons'.
//
// Revision 1.37  2003/02/22 20:49:55  dairiki
// Fixes for "Call-time pass by reference has been deprecated" errors.
//
// Revision 1.36  2003/02/21 22:50:51  dairiki
// Ensure that language preference is a string.
//
// Revision 1.35  2003/02/16 20:04:47  dairiki
// Refactor the HTTP validator generation/checking code.
//
// This also fixes a number of bugs with yesterdays validator mods.
//
// Revision 1.34  2003/02/15 02:21:54  dairiki
// API Change!  Explicit $request argument added to contructor for WikiUser.
//
// This seemed the best way to fix a problem whereby the WikiDB
// was being opened twice.  (Which while being merely inefficient
// when using an SQL backend causes hangage when using a dba backend.)
//
// Revision 1.33  2003/01/22 03:21:40  zorloc
// Modified WikiUser constructor to move the DB request for the homepage to
// the end of the logic to prevent it from being requested and then dropped.
// Added more phpdoc comments.
//
// Revision 1.32  2003/01/21 07:40:50  zorloc
// Modified WikiUser::_ok() -- Inverted the logic so the default is to return
// false and to return true only in the desired condition.  Added phpdoc
// comments
//
// Revision 1.31  2003/01/15 05:37:20  carstenklapp
// code reformatting
//
// Revision 1.30  2003/01/15 04:59:27  carstenklapp
// Bugfix: Previously stored preferences were not loading when user
// signed in. (Fixed... I hope.)
// Local Variables:
// mode: php
// tab-width: 8
// c-basic-offset: 4
// c-hanging-comment-ender-p: nil
// indent-tabs-mode: nil
// End:
