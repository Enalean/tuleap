    <?php //-*-php-*-
rcs_id('$Id: HttpAuth.php,v 1.5 2005/02/28 20:35:45 rurban Exp $');
/* Copyright (C) 2004 ReiniUrban
 * This file is part of PhpWiki. Terms and Conditions see LICENSE. (GPL2)
 */

/**
 * We have two possibilities here.
 * 1) The webserver location is already HTTP protected (usually Basic). Then just 
 *    use the username and do nothing.
 * 2) The webserver location is not protected, so we enforce basic HTTP Protection
 *    by sending a 401 error and let the client display the login dialog.
 *    This makes only sense if HttpAuth is the last method in USER_AUTH_ORDER,
 *    since the other methods cannot be transparently called after this enforced 
 *    external dialog.
 *    Try the available auth methods (most likely Bogo) and sent this header back.
 *    header('Authorization: Basic '.base64_encode("$userid:$passwd")."\r\n";
 */
class _HttpAuthPassUser
extends _PassUser
{
    function __construct($UserName='',$prefs=false) {
        if ($prefs) $this->_prefs = $prefs;
        if (!isset($this->_prefs->_method))
           parent::__construct($UserName);
        if ($UserName) $this->_userid = $UserName;
        $this->_authmethod = 'HttpAuth';
        
        // Is this double check really needed? 
        // It is not expensive so we keep it for now.
        if ($this->userExists())
            return $this;
        else 
            return $GLOBALS['ForbiddenUser'];
    }

    // FIXME! This doesn't work yet!
    // Allow httpauth by other method: Admin for now only
    function _fake_auth($userid, $passwd) {
    	return false;
    	
        header('WWW-Authenticate: Basic realm="'.WIKI_NAME.'"');
        header("Authorization: Basic ".base64_encode($userid.":".$passwd));

        $GLOBALS['REMOTE_USER'] = $userid;
        $_SERVER['PHP_AUTH_USER'] = $userid;
        $_SERVER['PHP_AUTH_PW'] = $passwd;
        //$GLOBALS['request']->setStatus(200);
    }

    function logout() {
        // Maybe we should random the realm to really force a logout. 
        // But the next login will fail.
        // better_srand(); $realm = microtime().rand();
        header('WWW-Authenticate: Basic realm="'.WIKI_NAME.'"');
        if (strstr(php_sapi_name(), 'apache'))
            header('HTTP/1.0 401 Unauthorized'); 
        else    
            header("Status: 401 Access Denied"); //IIS and CGI need that
        unset($GLOBALS['REMOTE_USER']);
        unset($_SERVER['PHP_AUTH_USER']);
        unset($_SERVER['PHP_AUTH_PW']);
    }

    function _http_username() {
	if (!empty($_SERVER['PHP_AUTH_USER']))
	    return $_SERVER['PHP_AUTH_USER'];
	if (!empty($_SERVER['REMOTE_USER']))
	    return $_SERVER['REMOTE_USER'];
        if (!empty($_ENV['REMOTE_USER']))
	    return $_ENV['REMOTE_USER'];
	if (!empty($GLOBALS['REMOTE_USER']))
	    return $GLOBALS['REMOTE_USER'];
	//IIS:
	if (!empty($_SERVER['HTTP_AUTHORIZATION'])) {
            list($userid, $passwd) = explode(':', 
                base64_decode(substr($_SERVER['HTTP_AUTHORIZATION'], 6)));
            return $userid;
	}    
	return '';
    }
    
    // force http auth authorization
    function userExists() {
        $username = $this->_http_username();
        if (empty($username) 
            or strtolower($username) != strtolower($this->_userid)) 
        {
            $this->logout();
            $user = $GLOBALS['ForbiddenUser'];
            $user->_userid = $this->_userid =  "";
            $this->_level = WIKIAUTH_FORBIDDEN;
            return $user;
            //exit;
        }
        $this->_userid = $username;
        // we should check if he is a member of admin, 
        // because HttpAuth has its own logic.
        $this->_level = WIKIAUTH_USER;
        if ($this->isAdmin())
            $this->_level = WIKIAUTH_ADMIN;
        return $this;
    }
    
    // ignore password for now, this is checked by apache.
    function checkPass($submitted_password) {
        return $this->userExists() 
            ? ($this->isAdmin() ? WIKIAUTH_ADMIN : WIKIAUTH_USER)
            : WIKIAUTH_ANON;
    }

    function mayChangePass() {
        return false;
    }
}

// $Log: HttpAuth.php,v $
// Revision 1.5  2005/02/28 20:35:45  rurban
// linebreaks
//
// Revision 1.4  2004/12/26 17:11:16  rurban
// just copyright
//
// Revision 1.3  2004/12/19 00:58:02  rurban
// Enforce PASSWORD_LENGTH_MINIMUM in almost all PassUser checks,
// Provide an errormessage if so. Just PersonalPage and BogoLogin not.
// Simplify httpauth logout handling and set sessions for all methods.
// fix main.php unknown index "x" getLevelDescription() warning.
//
// Revision 1.2  2004/12/17 12:31:57  rurban
// better logout, fake httpauth not yet
//
// Revision 1.1  2004/11/01 10:43:58  rurban
// seperate PassUser methods into seperate dir (memory usage)
// fix WikiUser (old) overlarge data session
// remove wikidb arg from various page class methods, use global ->_dbi instead
// ...
//

// Local Variables:
// mode: php
// tab-width: 8
// c-basic-offset: 4
// c-hanging-comment-ender-p: nil
// indent-tabs-mode: nil
// End:
?>