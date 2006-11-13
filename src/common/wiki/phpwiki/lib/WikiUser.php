<?php //-*-php-*-
rcs_id('$Id$');

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
                         'relativeDates' => new _UserPreference_bool()
                         );

function WikiUserClassname() {
    return 'WikiUser';
}

function UpgradeUser ($olduser, $user) {
    if (isa($user,'WikiUser') and isa($olduser,'WikiUser')) {
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

/**
* 
*/
class WikiUser {
    var $_userid = false;
    var $_level  = false;
    var $_request, $_dbi, $_authdbi, $_homepage;
    var $_authmethod = '', $_authhow = '';

    /**
     * Constructor.
     * 
     * Populates the instance variables and calls $this->_ok() 
     * to ensure that the parameters are valid.
     * @param mixed $userid String of username or WikiUser object.
     * @param integer $authlevel Authorization level.
     */
    function WikiUser (&$request, $userid = false, $authlevel = false) {
        $this->_request = &$request;
        $this->_dbi = &$this->_request->getDbh();

        if (isa($userid, 'WikiUser')) {
            $this->_userid   = $userid->_userid;
            $this->_level    = $userid->_level;
        }
        else {
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
    function auth_how() {
        return $this->_authhow;
    }

    /**
     * Invariant
     * 
     * If the WikiUser object has a valid authorization level and the 
     * userid is a string returns true, else false.
     * @return boolean If valid level and username string true, else false
     */
    function _ok () {
        if ((in_array($this->_level, array(WIKIAUTH_BOGO,
                                           WIKIAUTH_USER,
                                           WIKIAUTH_ADMIN))
            &&
            (is_string($this->_userid)))) {
            return true;
        }
        return false;
    }

    function UserName() {
        return $this->_userid;
    }

    function getId () {
        return ( $this->isSignedIn()
                 ? $this->_userid
                 : $this->_request->get('REMOTE_ADDR') ); // FIXME: globals
    }

    function getAuthenticatedId() {
        return ( $this->isAuthenticated()
                 ? $this->_userid
                 : $this->_request->get('REMOTE_ADDR') ); // FIXME: globals
    }

    function isSignedIn () {
        return $this->_level >= WIKIAUTH_BOGO;
    }

    function isAuthenticated () {
        return $this->_level >= WIKIAUTH_BOGO;
    }

    function isAdmin () {
        return $this->_level == WIKIAUTH_ADMIN;
    }

    function hasAuthority ($require_level) {
        return $this->_level >= $require_level;
    }

    function AuthCheck ($postargs) {
        // Normalize args, and extract.
        $keys = array('userid', 'passwd', 'require_level', 'login', 'logout',
                      'cancel');
        foreach ($keys as $key)
            $args[$key] = isset($postargs[$key]) ? $postargs[$key] : false;
        extract($args);
        $require_level = max(0, min(WIKIAUTH_ADMIN, (int)$require_level));

        if ($logout)
            return new WikiUser($this->_request); // Log out
        elseif ($cancel)
            return false;        // User hit cancel button.
        elseif (!$login && !$userid)
            return false;       // Nothing to do?

        $authlevel = $this->_pwcheck($userid, $passwd);
        if (!$authlevel)
            return _("Invalid password or userid.");
        elseif ($authlevel < $require_level)
            return _("Insufficient permissions.");

        // Successful login.
        $user = new WikiUser($this->_request,$userid,$authlevel);
        return $user;
    }

    function PrintLoginForm (&$request, $args, $fail_message = false,
                             $seperate_page = true) {
        include_once('lib/Template.php');
        // Call update_locale in case the system's default language is not 'en'.
        // (We have no user pref for lang at this point yet, no one is logged in.)
        update_locale(DEFAULT_LANGUAGE);
        $userid = '';
        $require_level = 0;
        extract($args); // fixme

        $require_level = max(0, min(WIKIAUTH_ADMIN, (int)$require_level));

        $pagename = $request->getArg('pagename');
        $login = new Template('login', $request,
                              compact('pagename', 'userid', 'require_level',
                                      'fail_message', 'pass_required'));
        if ($seperate_page) {
            $top = new Template('html', $request,
                                array('TITLE' => _("Sign In")));
            return $top->printExpansion($login);
        } else {
            return $login;
        }
    }

    /**
     * Check password.
     */
    function _pwcheck ($userid, $passwd) {
        global $WikiNameRegexp;

        if (!empty($userid) && $userid == ADMIN_USER) {
            // $this->_authmethod = 'pagedata';
            if (defined('ENCRYPTED_PASSWD') && ENCRYPTED_PASSWD)
                if ( !empty($passwd)
                     && crypt($passwd, ADMIN_PASSWD) == ADMIN_PASSWD )
                    return WIKIAUTH_ADMIN;
                else
                    return false;
            if (!empty($passwd)) {
                if ($passwd == ADMIN_PASSWD)
                  return WIKIAUTH_ADMIN;
                else {
                    // maybe we forgot to enable ENCRYPTED_PASSWD?
                    if ( function_exists('crypt')
                         && crypt($passwd, ADMIN_PASSWD) == ADMIN_PASSWD ) {
                        trigger_error(_("You forgot to set ENCRYPTED_PASSWD to true. Please update your /index.php"),
                                      E_USER_WARNING);
                        return WIKIAUTH_ADMIN;
                    }
                }
            }
            return false;
        }
        // HTTP Authentication
        elseif (ALLOW_HTTP_AUTH_LOGIN && !empty($PHP_AUTH_USER)) {
            // if he ignored the password field, because he is already
            // authenticated try the previously given password.
            if (empty($passwd))
                $passwd = $PHP_AUTH_PW;
        }

        // WikiDB_User DB/File Authentication from $DBAuthParams
        // Check if we have the user. If not try other methods.
        if (ALLOW_USER_LOGIN) { // && !empty($passwd)) {
            $request = $this->_request;
            // first check if the user is known
            if ($this->exists($userid)) {
                $this->_authmethod = 'pagedata';
                return ($this->checkPassword($passwd)) ? WIKIAUTH_USER : false;
            } else {
                // else try others such as LDAP authentication:
                if (ALLOW_LDAP_LOGIN && !empty($passwd)) {
                    if ($ldap = ldap_connect(LDAP_AUTH_HOST)) { // must be a valid LDAP server!
                        $r = @ldap_bind($ldap); // this is an anonymous bind
                        $st_search = "uid=$userid";
                        // Need to set the right root search information. see ../index.php
                        $sr = ldap_search($ldap, LDAP_BASE_DN,
                                          "$st_search");
                        $info = ldap_get_entries($ldap, $sr); // there may be more hits with this userid. try every
                        for ($i = 0; $i < $info["count"]; $i++) {
                            $dn = $info[$i]["dn"];
                            // The password is still plain text.
                            if ($r = @ldap_bind($ldap, $dn, $passwd)) {
                                // ldap_bind will return TRUE if everything matches
                                ldap_close($ldap);
                                $this->_authmethod = 'LDAP';
                                return WIKIAUTH_USER;
                            }
                        }
                    } else {
                        trigger_error("Unable to connect to LDAP server "
                                      . LDAP_AUTH_HOST, E_USER_WARNING);
                    }
                }
                // imap authentication. added by limako
                if (ALLOW_IMAP_LOGIN && !empty($passwd)) {
                    $mbox = @imap_open( "{" . IMAP_AUTH_HOST . "}INBOX",
                                        $userid, $passwd, OP_HALFOPEN );
                    if($mbox) {
                        imap_close($mbox);
                        $this->_authmethod = 'IMAP';
                        return WIKIAUTH_USER;
                    }
                }
            }
        }
        if ( ALLOW_BOGO_LOGIN
             && preg_match('/\A' . $WikiNameRegexp . '\z/', $userid) ) {
            $this->_authmethod = 'BOGO';
            return WIKIAUTH_BOGO;
        }
        return false;
    }

    // Todo: try our WikiDB backends.
    function getPreferences() {
        // Restore saved preferences.

        // I'd rather prefer only to store the UserId in the cookie or
        // session, and get the preferences from the db or page.
        if (!($prefs = $this->_request->getCookieVar('WIKI_PREFS2')))
            $prefs = $this->_request->getSessionVar('wiki_prefs');

        //if (!$this->_userid && !empty($GLOBALS['HTTP_COOKIE_VARS']['WIKI_ID'])) {
        //    $this->_userid = $GLOBALS['HTTP_COOKIE_VARS']['WIKI_ID'];
        //}

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
    function setPreferences($prefs, $id_only = false) {
        if (!is_object($prefs)) {
            $prefs = new UserPreferences($prefs);
        }
        // update the session and id
        $this->_request->setSessionVar('wiki_prefs', $prefs);
        // $this->_request->setCookieVar('WIKI_PREFS2', $this->_prefs, 365);
        // simple unpacked cookie
        if ($this->_userid) setcookie('WIKI_ID', $this->_userid, 365, '/');

        // We must ensure that any password is encrypted.
        // We don't need any plaintext password.
        if (! $id_only ) {
            if ($this->isSignedIn()) {
                if ($this->isAdmin())
                    $prefs->set('passwd', '');
                // already stored in index.php, and it might be
                // plaintext! well oh well
                if ($homepage = $this->homePage()) {
                    // check for page revision 0
                    if (! $this->_dbi->isWikiPage($this->_userid)) {
                        trigger_error(_("Your home page has not been created yet so your preferences cannot not be saved."),
                                      E_USER_WARNING);
                    }
                    else {
                        if ($this->isAdmin() || !$homepage->get('locked')) {
                            $homepage->set('pref', serialize($prefs->_prefs));
                            return sizeof($prefs->_prefs);
                        }
                        else {
                            // An "empty" page could still be
                            // intentionally locked by admin to
                            // prevent its creation.
                            //                            
                            // FIXME: This permission situation should
                            // probably be handled by the DB backend,
                            // once the new WikiUser code has been
                            // implemented.
                            trigger_error(_("Your home page is locked so your preferences cannot not be saved.")
                                          . " " . _("Please contact your PhpWiki administrator for assistance."),
                                          E_USER_WARNING);
                        }
                    }
                } else {
                    trigger_error("No homepage for user found. Creating one...",
                                  E_USER_WARNING);
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
    function exists() {
        $homepage = $this->homePage();
        return ($this->_userid && $homepage && $homepage->get('pref'));
    }

    // doesn't check for existance!!! hmm.
    // how to store metadata in not existing pages? how about versions?
    function homePage() {
        if (!$this->_userid)
            return false;
        if (!empty($this->_homepage)) {
            return $this->_homepage;
        } else {
            $this->_homepage = $this->_dbi->getPage($this->_userid);
            return $this->_homepage;
        }
    }

    function hasHomePage() {
        return !$this->homePage();
    }

    // create user by checking his homepage
    function createUser ($pref, $createDefaultHomepage = true) {
        if ($this->exists())
            return;
        if ($createDefaultHomepage) {
            $this->createHomepage($pref);
        } else {
            // empty page
            include "lib/loadsave.php";
            $pageinfo = array('pagedata' => array('pref' => serialize($pref->_pref)),
                              'versiondata' => array('author' => $this->_userid),
                              'pagename' => $this->_userid,
                              'content' => _('CategoryHomepage'));
            SavePage ($this->_request, $pageinfo, false, false);
        }
        $this->setPreferences($pref);
    }

    // create user and default user homepage
    function createHomepage ($pref) {
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
        SavePage ($this->_request, $pageinfo, false, false);

        // create Calender
        $pagename = $this->_userid . SUBPAGE_SEPARATOR . _('Preferences');
        if (! isWikiPage($pagename)) {
            $pageinfo = array('pagedata' => array(),
                              'versiondata' => array('author' => $this->_userid),
                              'pagename' => $pagename,
                              'content' => "<?plugin Calender ?>\n");
            SavePage ($this->_request, $pageinfo, false, false);
        }

        // create Preferences
        $pagename = $this->_userid . SUBPAGE_SEPARATOR . _('Preferences');
        if (! isWikiPage($pagename)) {
            $pageinfo = array('pagedata' => array(),
                              'versiondata' => array('author' => $this->_userid),
                              'pagename' => $pagename,
                              'content' => "<?plugin UserPreferences ?>\n");
            SavePage ($this->_request, $pageinfo, false, false);
        }
    }

    function tryAuthBackends() {
        return ''; // crypt('') will never be ''
    }

    // Auth backends must store the crypted password where?
    // Not in the preferences.
    function checkPassword($passwd) {
        $prefs = $this->getPreferences();
        $stored_passwd = $prefs->get('passwd'); // crypted
        if (empty($prefs->_prefs['passwd']))    // not stored in the page
            // allow empty passwords? At least store a '*' then.
            // try other backend. hmm.
            $stored_passwd = $this->tryAuthBackends($this->_userid);
        if (empty($stored_passwd)) {
            trigger_error(sprintf(_("Old UserPage %s without stored password updated with empty password. Set a password in your UserPreferences."),
                                  $this->_userid), E_USER_NOTICE);
            $prefs->set('passwd','*');
            return true;
        }
        if ($stored_passwd == '*')
            return true;
        if ( !empty($passwd)
             && crypt($passwd, $stored_passwd) == $stored_passwd )
            return true;
        else
            return false;
    }

    function changePassword($newpasswd, $passwd2 = false) {
        if (! $this->mayChangePass() ) {
            trigger_error(sprintf("Attempt to change an external password for '%s'. Not allowed!",
                                  $this->_userid), E_USER_ERROR);
            return;
        }
        if ($passwd2 && $passwd2 != $newpasswd) {
            trigger_error("The second password must be the same as the first to change it",
                          E_USER_ERROR);
            return;
        }
        $prefs = $this->getPreferences();
        //$oldpasswd = $prefs->get('passwd');
        $prefs->set('passwd', crypt($newpasswd));
        $this->setPreferences($prefs);
    }

    function mayChangePass() {
        // on external DBAuth maybe. on IMAP or LDAP not
        // on internal DBAuth yes
        if (in_array($this->_authmethod, array('IMAP', 'LDAP')))
            return false;
        if ($this->isAdmin())
            return false;
        if ($this->_authmethod == 'pagedata')
            return true;
        if ($this->_authmethod == 'authdb')
            return true;
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
    function _UserPreference ($default_value) {
        $this->default_value = $default_value;
    }

    function sanify ($value) {
        return (string)$value;
    }

    function update ($value) {
    }
}

class _UserPreference_numeric
extends _UserPreference
{
    function _UserPreference_numeric ($default, $minval = false,
                                      $maxval = false) {
        $this->_UserPreference((double)$default);
        $this->_minval = (double)$minval;
        $this->_maxval = (double)$maxval;
    }

    function sanify ($value) {
        $value = (double)$value;
        if ($this->_minval !== false && $value < $this->_minval)
            $value = $this->_minval;
        if ($this->_maxval !== false && $value > $this->_maxval)
            $value = $this->_maxval;
        return $value;
    }
}

class _UserPreference_int
extends _UserPreference_numeric
{
    function _UserPreference_int ($default, $minval = false, $maxval = false) {
        $this->_UserPreference_numeric((int)$default, (int)$minval,
                                       (int)$maxval);
    }

    function sanify ($value) {
        return (int)parent::sanify((int)$value);
    }
}

class _UserPreference_bool
extends _UserPreference
{
    function _UserPreference_bool ($default = false) {
        $this->_UserPreference((bool)$default);
    }

    function sanify ($value) {
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
                if ($val)
                    return true;
            }
            return false;
        }
        return (bool) $value;
    }
}

class _UserPreference_language
extends _UserPreference
{
    function _UserPreference_language ($default = DEFAULT_LANGUAGE) {
        $this->_UserPreference($default);
    }

    // FIXME: check for valid locale
    function sanify ($value) {
        // Revert to DEFAULT_LANGUAGE if user does not specify
        // language in UserPreferences or chooses <system language>.
        if ($value == '' or empty($value))
            $value = DEFAULT_LANGUAGE;

        return (string) $value;
    }
}

class _UserPreference_theme
extends _UserPreference
{
    function _UserPreference_theme ($default = THEME) {
        $this->_UserPreference($default);
    }

    function sanify ($value) {
        if (findFile($this->_themefile($value), true))
            return $value;
        return $this->default_value;
    }

    function update ($newvalue) {
        global $Theme;
        include_once($this->_themefile($newvalue));
        if (empty($Theme))
            include_once($this->_themefile(THEME));
    }

    function _themefile ($theme) {
        return "themes/$theme/themeinfo.php";
    }
}

// don't save default preferences for efficiency.
class UserPreferences {
    function UserPreferences ($saved_prefs = false) {
        $this->_prefs = array();

        if (isa($saved_prefs, 'UserPreferences') && $saved_prefs->_prefs) {
            foreach ($saved_prefs->_prefs as $name => $value)
                $this->set($name, $value);
        } elseif (is_array($saved_prefs)) {
            foreach ($saved_prefs as $name => $value)
                $this->set($name, $value);
        }
    }

    function _getPref ($name) {
        global $UserPreferences;
        if (!isset($UserPreferences[$name])) {
            if ($name == 'passwd2') return false;
            trigger_error("$name: unknown preference", E_USER_NOTICE);
            return false;
        }
        return $UserPreferences[$name];
    }

    function get ($name) {
        if (isset($this->_prefs[$name]))
            return $this->_prefs[$name];
        if (!($pref = $this->_getPref($name)))
            return false;
        return $pref->default_value;
    }

    function set ($name, $value) {
        if (!($pref = $this->_getPref($name)))
            return false;

        $newvalue = $pref->sanify($value);
        $oldvalue = $this->get($name);

        // update on changes
        if ($newvalue != $oldvalue)
            $pref->update($newvalue);

        // don't set default values to save space (in cookies, db and
        // sesssion)
        if ($value == $pref->default_value)
            unset($this->_prefs[$name]);
        else
            $this->_prefs[$name] = $newvalue;
    }

    function pack ($nonpacked) {
        return serialize($nonpacked);
    }
    function unpack ($packed) {
        if (!$packed)
            return false;
        if (substr($packed,0,2) == "O:") {
            // Looks like a serialized object
            return unserialize($packed);
        }
        //trigger_error("DEBUG: Can't unpack bad UserPreferences",
        //E_USER_WARNING);
        return false;
    }

    function hash () {
        return hash($this->_prefs);
    }
}

// $Log$
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
//

// Local Variables:
// mode: php
// tab-width: 8
// c-basic-offset: 4
// c-hanging-comment-ender-p: nil
// indent-tabs-mode: nil
// End:
?>
