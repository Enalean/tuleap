<?php //-*-php-*-
rcs_id('$Id$');

define ('USE_PREFS_IN_PAGE', true);

//include "lib/config.php";
require_once(dirname(__FILE__)."/stdlib.php");
require_once('lib/Request.php');
require_once('lib/WikiDB.php');
if (ENABLE_USER_NEW)
    require_once("lib/WikiUserNew.php");
else
    require_once("lib/WikiUser.php");
require_once("lib/WikiGroup.php");
require_once("lib/PagePerm.php");

class WikiRequest extends Request {
    // var $_dbi;

    function WikiRequest () {
        $this->_dbi = WikiDB::open($GLOBALS['DBParams']);
        if (USE_DB_SESSION) {
            include_once('lib/DB_Session.php');
            $prefix = isset($GLOBALS['DBParams']['prefix']) ? $GLOBALS['DBParams']['prefix'] : '';
            if (in_array('File',$GLOBALS['USER_AUTH_ORDER'])) {
                include_once('lib/pear/File_Passwd.php');
            }
            $dbi = $this->getDbh();
            $this->_dbsession = & new DB_Session($dbi,$prefix . $GLOBALS['DBParams']['db_session_table']);
        }
        // Fixme: Does pear reset the error mask to 1? We have to find the culprit
        $x = error_reporting();
$this->version = phpwiki_version();
        $this->Request();

        // Normalize args...
        $this->setArg('pagename', $this->_deducePagename());
        $this->setArg('action', $this->_deduceAction());

        // Restore auth state. This doesn't check for proper authorization!
        if (ENABLE_USER_NEW) {
            $userid = $this->_deduceUsername();	
            if (isset($this->_user) and 
                !empty($this->_user->_authhow) and 
                $this->_user->_authhow == 'session')
            {
                // users might switch in a session between the two objects.
                // restore old auth level here or in updateAuthAndPrefs?
                //$user = $this->getSessionVar('wiki_user');
                // revive db handle, because these don't survive sessions
                if (isset($this->_user) and 
                     ( ! isa($this->_user,WikiUserClassname())
                       or (strtolower(get_class($this->_user)) == '_passuser')))
                {
                    $this->_user = WikiUser($userid,$this->_user->_prefs);
                }
	        unset($this->_user->_HomePagehandle);
	        $this->_user->hasHomePage();
	        // update the lockfile filehandle
	        if (  isa($this->_user,'_FilePassUser') and 
	              $this->_user->_file->lockfile and 
	              !$this->_user->_file->fplock  )
	        {
		    $this->_user = new _FilePassUser($userid,$this->_user->_prefs,$this->_user->_file->filename);
	        }
	        /*
                if (!isa($user,WikiUserClassname()) or empty($this->_user->_level)) {
                    $user = UpgradeUser($this->_user,$user);
                }
                */
            	$this->_prefs = & $this->_user->_prefs;
            } else {
#                $user = WikiUser($userid);
#                $this->_user = & $user;
                $this->_user =& new _SessionPassUser(user_getname());
                $this->_prefs = & $this->_user->_prefs;
            }
        } else {
            $this->_user = new WikiUser($this, $this->_deduceUsername());
            $this->_prefs = $this->_user->getPreferences();
        }
    }

    function initializeLang () {
        if ($user_lang = $this->getPref('lang')) {
            //trigger_error("DEBUG: initializeLang() ". $user_lang ." calling update_locale()...");
            update_locale($user_lang);
            FindLocalizedButtonFile(".",'missing_ok','reinit');
        }
    }

    function initializeTheme () {
        global $Theme;

        // Load theme
        if ($user_theme = $this->getPref('theme'))
            include_once("themes/$user_theme/themeinfo.php");
        if (empty($Theme) and defined('THEME'))
            include_once("themes/" . THEME . "/themeinfo.php");
        if (empty($Theme))
            include_once("themes/default/themeinfo.php");
        assert(!empty($Theme));
    }

    // This really maybe should be part of the constructor, but since it
    // may involve HTML/template output, the global $request really needs
    // to be initialized before we do this stuff.
    function updateAuthAndPrefs () {

        if (isset($this->_user) and (!isa($this->_user,WikiUserClassname()))) {
            $this->_user = false;	
        }

        // Handle authentication request, if any.
        if ($auth_args = $this->getArg('auth')) {
            $this->setArg('auth', false);
            $this->_handleAuthRequest($auth_args); // possible NORETURN
        }
        elseif ( ! $this->_user or 
                 (isa($this->_user,WikiUserClassname()) and ! $this->_user->isSignedIn())) {
            // If not auth request, try to sign in as saved user.
            if (($saved_user = $this->getPref('userid')) != false) {
                $this->_signIn($saved_user);
            }
        }

        // Save preferences in session and cookie
        if (isset($this->_user) and 
            (!isset($this->_user->_authhow) or $this->_user->_authhow != 'session')) {
            $id_only = true; 
            $this->_user->setPreferences($this->_prefs, $id_only);
        } else {
            $this->setSessionVar('wiki_user', $this->_user);
            //$this->setSessionVar('wiki_prefs', $this->_prefs);
        }

        // Ensure user has permissions for action
        $require_level = $this->requiredAuthority($this->getArg('action'));
#        print $this->_user->_userid.' is '.$this->_user->_level.'<br>';
        if (! $this->_user->hasAuthority($require_level))
            $this->_notAuthorized($require_level); // NORETURN
    }

    function getUser () {
        if (isset($this->_user))
            return $this->_user;
        else
            return $GLOBALS['ForbiddenUser'];
    }

    function getPrefs () {
        return $this->_prefs;
    }

    // Convenience function:
    function getPref ($key) {
        if (isset($this->_prefs))
            return $this->_prefs->get($key);
    }

    function getDbh () {
        return $this->_dbi;
    }

    /**
     * Get requested page from the page database.
     * By default it will grab the page requested via the URL
     *
     * This is a convenience function.
     * @param string $pagename Name of page to get.
     * @return WikiDB_Page Object with methods to pull data from
     * database for the page requested.
     */
    function getPage ($pagename = false) {
        if (!isset($this->_dbi))
            $this->getDbh();
        if (!$pagename) 
            $pagename = $this->getArg('pagename');
        if(!empty($this->_dbi))
            return $this->_dbi->getPage($pagename);
        exit;
    }

    /** Get URL for POST actions.
     *
     * Officially, we should just use SCRIPT_NAME (or some such),
     * but that causes problems when we try to issue a redirect, e.g.
     * after saving a page.
     *
     * Some browsers (at least NS4 and Mozilla 0.97 won't accept
     * a redirect from a page to itself.)
     *
     * So, as a HACK, we include pagename and action as query args in
     * the URL.  (These should be ignored when we receive the POST
     * request.)
     */
    function getPostURL ($pagename=false) {
        if ($pagename === false)
            $pagename = $this->getArg('pagename');
        $action = $this->getArg('action');
        if (!empty($_GET['start_debug'])) // zend ide support
            return WikiURL($pagename, array('action' => $action, 'start_debug' => 1));
        else
            return WikiURL($pagename, array('action' => $action));
    }
    
    function _handleAuthRequest ($auth_args) {
        if (!is_array($auth_args))
            return;

        // Ignore password unless POST'ed.
        if (!$this->isPost())
            unset($auth_args['passwd']);

        $olduser = $this->_user;
        $user = $this->_user->AuthCheck($auth_args);
        if (isa($user,WikiUserClassname())) {
            // Successful login (or logout.)
            $this->_setUser($user);
        }
        elseif (is_string($user)) {
            // Login attempt failed.
            $fail_message = $user;
            $auth_args['pass_required'] = true;
            // If no password was submitted, it's not really
            // a failure --- just need to prompt for password...
            if (!ALLOW_USER_PASSWORDS 
                and ALLOW_BOGO_LOGIN 
                and !isset($auth_args['passwd'])) 
            {
                $fail_message = false;
            }
            $olduser->PrintLoginForm($this, $auth_args, $fail_message);
            $this->finish();    //NORETURN
        }
        else {
            // Login request cancelled.
        }
    }

    /**
     * Attempt to sign in (bogo-login).
     *
     * Fails silently.
     *
     * @param $userid string Userid to attempt to sign in as.
     * @access private
     */
    function _signIn ($userid) {
        if (ENABLE_USER_NEW) {
            if (! $this->_user )
                $this->_user = new _BogoUser($userid);
            if (! $this->_user )
                $this->_user = new _PassUser($userid);
        }
        $user = $this->_user->AuthCheck(array('userid' => $userid));
        if (isa($user,WikiUserClassname())) {
            $this->_setUser($user); // success!
        }
    }

    // login or logout or restore state
    function _setUser ($user) {
        $this->_user = $user;
        define('MAIN_setUser',true);
        $this->setCookieVar('WIKI_ID', $user->getAuthenticatedId(), COOKIE_EXPIRATION_DAYS, COOKIE_DOMAIN);
        $this->setSessionVar('wiki_user', $user);
        if ($user->isSignedIn())
            $user->_authhow = 'signin';

        // Save userid to prefs..
        if ( ! $this->_user->_prefs ) {
            $this->_user->_prefs = $this->_user->getPreferences();
            $this->_prefs =& $this->_user->_prefs;
        }
        $this->_prefs->set('userid',
                           $user->isSignedIn() ? $user->getId() : '');
        $this->initializeTheme();
    }

    /* Permission system */

    function _notAuthorized ($require_level) {
        // Display the authority message in the Wiki's default
        // language, in case it is not english.
        //
        // Note that normally a user will not see such an error once
        // logged in, unless the admin has altered the default
        // disallowed wikiactions. In that case we should probably
        // check the user's language prefs too at this point; this
        // would be a situation which is not really handled with the
        // current code.
        if (empty($GLOBALS['LANG']))
            update_locale(DEFAULT_LANGUAGE);

        // User does not have required authority.  Prompt for login.
        $what = $this->getActionDescription($this->getArg('action'));

        if ($require_level == WIKIAUTH_UNOBTAINABLE) {
            if (class_exists('PagePermission'))
                $this->finish(fmt("%s is disallowed on this wiki for user %s (level: %d).",
                                  $this->getDisallowedActionDescription($this->getArg('action')),
                                  $this->_user->UserName(),$this->_user->_level));
            else
                $this->finish(fmt("%s is disallowed on this wiki.",
                                  $this->getDisallowedActionDescription($this->getArg('action'))));
        }
        elseif ($require_level == WIKIAUTH_BOGO)
            $msg = fmt("You must sign in to %s.", $what);
        elseif ($require_level == WIKIAUTH_USER)
            $msg = fmt("You must log in to %s.", $what);
        else
            $msg = fmt("You must be an administrator to %s.", $what);
        $pass_required = ($require_level >= WIKIAUTH_USER);

        $this->_user->PrintLoginForm($this, compact('require_level','pass_required'), $msg);
        $this->finish();    // NORETURN
    }

    // Fixme: for PagePermissions we'll need other strings, 
    // relevant to the requested page, not just for the action on the whole wiki.
    function getActionDescription($action) {
        static $actionDescriptions;
        if (! $actionDescriptions) {
            $actionDescriptions
            = array('browse'     => _("view this page"),
                    'diff'       => _("diff this page"),
                    'dumphtml'   => _("dump html pages"),
                    'dumpserial' => _("dump serial pages"),
                    'edit'       => _("edit this page"),
                    'create'     => _("create this page"),
                    'loadfile'   => _("load files into this wiki"),
                    'lock'       => _("lock this page"),
                    'remove'     => _("remove this page"),
                    'unlock'     => _("unlock this page"),
                    'upload'     => _("upload a zip dump"),
                    'verify'     => _("verify the current action"),
                    'viewsource' => _("view the source of this page"),
                    'xmlrpc'     => _("access this wiki via XML-RPC"),
                    'soap'       => _("access this wiki via SOAP"),
                    'zip'        => _("download a zip dump from this wiki"),
                    'ziphtml'    => _("download an html zip dump from this wiki")
                    );
        }
        if (in_array($action, array_keys($actionDescriptions)))
            return $actionDescriptions[$action];
        else
            return $action;
    }
    function getDisallowedActionDescription($action) {
        static $disallowedActionDescriptions;
        if (! $disallowedActionDescriptions) {
            $disallowedActionDescriptions
            = array('browse'     => _("Browsing pages"),
                    'diff'       => _("Diffing pages"),
                    'dumphtml'   => _("Dumping html pages"),
                    'dumpserial' => _("Dumping serial pages"),
                    'edit'       => _("Editing pages"),
                    'create'     => _("Creating pages"),
                    'loadfile'   => _("Loading files"),
                    'lock'       => _("Locking pages"),
                    'remove'     => _("Removing pages"),
                    'unlock'     => _("Unlocking pages"),
                    'upload'     => _("Uploading zip dumps"),
                    'verify'     => _("Verify the current action"),
                    'viewsource' => _("Viewing the source of pages"),
                    'xmlrpc'     => _("XML-RPC access"),
                    'soap'       => _("SOAP access"),
                    'zip'        => _("Downloading zip dumps"),
                    'ziphtml'    => _("Downloading html zip dumps")
                    );
        }
        if (in_array($action, array_keys($disallowedActionDescriptions)))
            return $disallowedActionDescriptions[$action];
        else
            return $action;
    }

    function requiredAuthority ($action) {
        $auth = $this->requiredAuthorityForAction($action);
        if (!ALLOW_ANON_USER && $auth < WIKIAUTH_USER) return WIKIAUTH_USER;

        /*
         * This is a hook for plugins to require authority
         * for posting to them.
         *
         * IMPORTANT: this is not a secure check, so the plugin
         * may not assume that any POSTs to it are authorized.
         * All this does is cause PhpWiki to prompt for login
         * if the user doesn't have the required authority.
         */
        if ($this->isPost()) {
            $post_auth = $this->getArg('require_authority_for_post');
            if ($post_auth !== false)
                $auth = max($auth, $post_auth);
        }
        return $auth;
    }
        
    function requiredAuthorityForAction ($action) {
    	if (class_exists("PagePermission")) {
    	    return requiredAuthorityForPage($action);
    	} else {
          // FIXME: clean up. 
          switch ($action) {
            case 'browse':
            case 'viewsource':
            case 'diff':
            case 'select':
            case 'xmlrpc':
            case 'search':
            case 'pdf':
                return WIKIAUTH_ANON;

            case 'zip':
            case 'ziphtml':
                if (defined('ZIPDUMP_AUTH') && ZIPDUMP_AUTH)
                    return WIKIAUTH_ADMIN;
                return WIKIAUTH_ANON;

            case 'edit':
            case 'soap':
                if (defined('REQUIRE_SIGNIN_BEFORE_EDIT') && REQUIRE_SIGNIN_BEFORE_EDIT)
                    return WIKIAUTH_BOGO;
                return WIKIAUTH_ANON;
                // return WIKIAUTH_BOGO;

            case 'create':
                $page = $this->getPage();
                $current = $page->getCurrentRevision();
                if ($current->hasDefaultContents())
                    return $this->requiredAuthorityForAction('edit');
                return $this->requiredAuthorityForAction('browse');

            case 'upload':
            case 'dumpserial':
            case 'dumphtml':
            case 'loadfile':
            case 'remove':
            case 'lock':
            case 'unlock':
            case 'upgrade':
                return WIKIAUTH_ADMIN;
            default:
                global $WikiNameRegexp;
                if (preg_match("/$WikiNameRegexp\Z/A", $action))
                    return WIKIAUTH_ANON; // ActionPage.
                else
                    return WIKIAUTH_ADMIN;
          }
        }
    }
    /* End of Permission system */

    function possiblyDeflowerVirginWiki () {
        if ($this->getArg('action') != 'browse')
            return;
        if ($this->getArg('pagename') != HOME_PAGE)
            return;

        $page = $this->getPage();
        $current = $page->getCurrentRevision();
        if ($current->getVersion() > 0)
            return;             // Homepage exists.

        include('lib/loadsave.php');
        SetupWiki($this);
        $this->finish();        // NORETURN
    }

    function handleAction () {
        $action = $this->getArg('action');
        $method = "action_$action";
        if (method_exists($this, $method)) {
            $this->{$method}();
        }
        elseif ($page = $this->findActionPage($action)) {
            $this->actionpage($page);
        }
        else {
            $this->finish(fmt("%s: Bad action", $action));
        }
    }
    
    function finish ($errormsg = false) {
        static $in_exit = 0;

        if ($in_exit)
            exit();        // just in case CloseDataBase calls us
        $in_exit = true;

        global $ErrorManager;
        $ErrorManager->flushPostponedErrors();

        if (!empty($errormsg)) {
            PrintXML(HTML::br(),
                     HTML::hr(),
                     HTML::h2(_("Fatal PhpWiki Error")),
                     $errormsg);
            // HACK:
            echo "\n</body></html>";
        }
        if (is_object($this->_user)) {
            $this->_user->page   = $this->getArg('pagename');
            $this->_user->action = $this->getArg('action');
            unset($this->_user->_HomePagehandle);
            unset($this->_user->_auth_dbi);
	}
        if (!empty($this->_dbi)) {
            session_write_close();
            $this->_dbi->close();
            unset($this->_dbi);
        }
        Request::finish();
        //exit;
    }

    function _deducePagename () {
        if ($this->getArg('pagename'))
            return $this->getArg('pagename');

        if (USE_PATH_INFO) {
            $pathinfo = $this->get('PATH_INFO');
            $tail = substr($pathinfo, strlen(PATH_INFO_PREFIX));

            if ($tail != '' and $pathinfo == PATH_INFO_PREFIX . $tail) {
                return $tail;
            }
        }
        elseif ($this->isPost()) {
            /*
             * In general, for security reasons, HTTP_GET_VARS should be ignored
             * on POST requests, but we make an exception here (only for pagename).
             *
             * The justifcation for this hack is the following
             * asymmetry: When POSTing with USE_PATH_INFO set, the
             * pagename can (and should) be communicated through the
             * request URL via PATH_INFO.  When POSTing with
             * USE_PATH_INFO off, this cannot be done --- the only way
             * to communicate the pagename through the URL is via
             * QUERY_ARGS (HTTP_GET_VARS).
             */
            global $HTTP_GET_VARS;
            if (isset($HTTP_GET_VARS['pagename'])) { 
                return $HTTP_GET_VARS['pagename'];
            }
        }

        /*
         * Support for PhpWiki 1.2 style requests.
         */
        $query_string = $this->get('QUERY_STRING');
        if (preg_match('/^[^&=]+$/', $query_string)) {
            return urldecode($query_string);
        }

        return HOME_PAGE;
    }

    function _deduceAction () {
        if (!($action = $this->getArg('action'))) {
            // Detect XML-RPC requests
            if ($this->isPost()
                && $this->get('CONTENT_TYPE') == 'text/xml') {
                global $HTTP_RAW_POST_DATA;
                if (strstr($HTTP_RAW_POST_DATA, '<methodCall>')) {
                    return 'xmlrpc';
                }
            }

            return 'browse';    // Default if no action specified.
        }

        if (method_exists($this, "action_$action"))
            return $action;

        // Allow for, e.g. action=LikePages
        if ($this->isActionPage($action))
            return $action;

        trigger_error("$action: Unknown action", E_USER_NOTICE);
        return 'browse';
    }

    function _deduceUsername() {
        global $HTTP_SERVER_VARS, $HTTP_ENV_VARS;
        if (!empty($this->args['auth']) and !empty($this->args['auth']['userid']))
            return $this->args['auth']['userid'];
        if (!empty($HTTP_SERVER_VARS['PHP_AUTH_USER']))
            return $HTTP_SERVER_VARS['PHP_AUTH_USER'];
        if (!empty($HTTP_ENV_VARS['REMOTE_USER']))
            return $HTTP_ENV_VARS['REMOTE_USER'];
            
        if ($user = $this->getSessionVar('wiki_user')) {
            $this->_user = $user;
            $this->_user->_authhow = 'session';
            return ENABLE_USER_NEW ? $user->UserName() : $this->_user;
        }
        if ($userid = $this->getCookieVar('WIKI_ID')) {
            if (!empty($userid) and substr($userid,0,2) != 's:') {
                $this->_user->authhow = 'cookie';
                return $userid;
            }
        }
        return false;
    }
    
    function _isActionPage ($pagename) {
        $dbi = $this->getDbh();
        $page = $dbi->getPage($pagename);
        if(empty($page)) 
            return false;
        $rev = $page->getCurrentRevision();
        // FIXME: more restrictive check for sane plugin?
        if (strstr($rev->getPackedContent(), '<?plugin'))
            return true;
        if (!$rev->hasDefaultContents())
            trigger_error("$pagename: Does not appear to be an 'action page'", E_USER_NOTICE);
        return false;
    }

    function findActionPage ($action) {
        static $cache;

        // check for translated version, as per users preferred language
        // (or system default in case it is not en)
        $translation = gettext($action);

        if (isset($cache) and isset($cache[$translation]))
            return $cache[$translation];

        // check for cached translated version
        if ($this->_isActionPage($translation))
            return $cache[$action] = $translation;

        // Allow for, e.g. action=LikePages
        global $WikiNameRegexp;
        if (!preg_match("/$WikiNameRegexp\\Z/A", $action))
            return $cache[$action] = false;

        // check for translated version (default language)
        global $LANG;
        if ($LANG != DEFAULT_LANGUAGE and $LANG != "en") {
            $save_lang = $LANG;
            //trigger_error("DEBUG: findActionPage() ". DEFAULT_LANGUAGE." calling update_locale()...");
            update_locale(DEFAULT_LANGUAGE);
            $default = gettext($action);
            //trigger_error("DEBUG: findActionPage() ". $save_lang." restoring save_lang, calling update_locale()...");
            update_locale($save_lang);
            if ($this->_isActionPage($default))
                return $cache[$action] = $default;
        }
        else {
            $default = $translation;
        }
        
        // check for english version
        if ($action != $translation and $action != $default) {
            if ($this->_isActionPage($action))
                return $cache[$action] = $action;
        }

        trigger_error("$action: Cannot find action page", E_USER_NOTICE);
        return $cache[$action] = false;
    }
    
    function isActionPage ($pagename) {
        return $this->findActionPage($pagename);
    }

    function action_browse () {
        $this->buffer_output();
        include_once("lib/display.php");
        displayPage($this);
    }

    function action_verify () {
        $this->action_browse();
    }

    function actionpage ($action) {
        $this->buffer_output();
        include_once("lib/display.php");
        actionPage($this, $action);
    }

    function action_diff () {
        $this->buffer_output();
        include_once "lib/diff.php";
        showDiff($this);
    }

    function action_search () {
        // This is obsolete: reformulate URL and redirect.
        // FIXME: this whole section should probably be deleted.
        if ($this->getArg('searchtype') == 'full') {
            $search_page = _("FullTextSearch");
        }
        else {
            $search_page = _("TitleSearch");
        }
        $this->redirect(WikiURL($search_page,
                                array('s' => $this->getArg('searchterm')),
                                'absolute_url'));
    }

    function action_edit () {
        $this->buffer_output();
        include "lib/editpage.php";
        $e = new PageEditor ($this);
        $e->editPage();
    }

    function action_create () {
        $this->action_edit();
    }
    
    function action_viewsource () {
        $this->buffer_output();
        include "lib/editpage.php";
        $e = new PageEditor ($this);
        $e->viewSource();
    }

    function action_lock () {
        $page = $this->getPage();
        $page->set('locked', true);
        $this->action_browse();
    }

    function action_unlock () {
        // FIXME: This check is redundant.
        //$user->requireAuth(WIKIAUTH_ADMIN);
        $page = $this->getPage();
        $page->set('locked', false);
        $this->action_browse();
    }

    function action_remove () {
        // FIXME: This check is redundant.
        //$user->requireAuth(WIKIAUTH_ADMIN);
        $pagename = $this->getArg('pagename');
        if (strstr($pagename,_('PhpWikiAdministration'))) {
            $this->action_browse();
        } else {
            include('lib/removepage.php');
            RemovePage($this);
        }
    }

    function action_xmlrpc () {
        include_once("lib/XmlRpcServer.php");
        $xmlrpc = new XmlRpcServer($this);
        $xmlrpc->service();
    }
    
    function action_zip () {
        include_once("lib/loadsave.php");
        MakeWikiZip($this);
        // I don't think it hurts to add cruft at the end of the zip file.
        //echo "\n========================================================\n";
        //echo "PhpWiki " . PHPWIKI_VERSION . " source:\n$GLOBALS[RCS_IDS]\n";
    }

    function action_ziphtml () {
        include_once("lib/loadsave.php");
        MakeWikiZipHtml($this);
        // I don't think it hurts to add cruft at the end of the zip file.
        echo "\n========================================================\n";
        echo "PhpWiki " . PHPWIKI_VERSION . " source:\n$GLOBALS[RCS_IDS]\n";
    }

    function action_dumpserial () {
        include_once("lib/loadsave.php");
        DumpToDir($this);
    }

    function action_dumphtml () {
        include_once("lib/loadsave.php");
        DumpHtmlToDir($this);
    }

    function action_upload () {
        include_once("lib/loadsave.php");
        LoadPostFile($this);
    }

    function action_upgrade () {
        include_once("lib/loadsave.php");
        include_once("lib/upgrade.php");
        DoUpgrade($this);
    }

    function action_loadfile () {
        include_once("lib/loadsave.php");
        LoadFileOrDir($this);
    }

    function action_pdf () {
        $this->buffer_output('nocompress');
        if ($GLOBALS['LANG'] == 'ja') {
            include_once("lib/fpdf/japanese.php");
            $pdf = new PDF_Japanese;
        } elseif ($GLOBALS['LANG'] == 'zh') {
            include_once("lib/fpdf/chinese.php");
            $pdf = new PDF_Chinese;
        } else {
            include_once("lib/pdf.php");
            $pdf = new PDF;
        }
        include_once("lib/display.php");
        displayPage($this);
        $html = ob_get_contents();
    	$pdf->Open();
    	$pdf->AddPage();
        $pdf->ConvertFromHTML($html);
        $this->discardOutput();
        
        $this->buffer_output('nocompress');
        $pdf->Output();
        $GLOBALS['ErrorManager']->flushPostponedErrors();
        if (!empty($errormsg)) {
            $this->discardOutput();
            PrintXML(HTML::br(),
                     HTML::hr(),
                     HTML::h2(_("Fatal PhpWiki Error")),
                     $errormsg);
            echo "\n</body></html>";
    } else {
            ob_end_flush();
        }
    }
}
?>