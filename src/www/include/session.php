<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// $Id$
//
require_once('common/include/CookieManager.class.php');

//$Language->loadLanguageMsg('include/include');

$G_SESSION=array();
$G_USER=array();

$ALL_USERS_DATA = array();
$ALL_USERS_GROUPS = array();
$ALL_USERS_TRACKERS = array();

function session_login_valid($form_loginname,$form_pw,$allowpending=0) {
    $auth_success     = false;
    $auth_user_id     = null;
    $auth_user_status = null;

    $params = array();
    $params['loginname']        = $form_loginname;
    $params['passwd']           = $form_pw;
    $params['auth_success']     =& $auth_success;
    $params['auth_user_id']     =& $auth_user_id;
    $params['auth_user_status'] =& $auth_user_status;
    $em =& EventManager::instance();
    $em->processEvent('session_before_login', $params);
    
    $success = $auth_success;

    if(!$auth_success) {
        if ($success = session_login_valid_db($form_loginname,$form_pw,$allowpending,
                                              $auth_user_id,$auth_user_status)) {
            $allow_codex_login = true;
            $params = array('user_id'           => $auth_user_id,
                            'allow_codex_login' => &$allow_codex_login);
            $em->processEvent('session_after_login', $params);
            $success = $allow_codex_login;
        }
    }
    
    if($success) {
        // check status of this user
        if(session_login_valid_status($auth_user_status, $allowpending)) {
        
            //create a new session
            session_set_new($auth_user_id);
            
            return array(true, '');
        }
        else {
            return array(false, $auth_user_status);
        }
    }
    else {
        return array(false, '');
    }
}

// Standard CodeX authentication, based on password stored in DB
function session_login_valid_db($form_loginname,$form_pw,$allowpending=0)  {
  global $Language;
    $usr=null;

    if (!$form_loginname || !$form_pw) {
        $GLOBALS['Response']->addFeedback('error', $Language->getText('include_session','missing_pwd'));
        return false;
    }

    //get the user from the database using user_id and password
	$res = db_query("SELECT user_id,status FROM user WHERE "
                    . "user_name='$form_loginname' "
                    . "AND user_pw='" . md5($form_pw) . "'");
	if (!$res || db_numrows($res) < 1) {
		//invalid password or user_name
		$GLOBALS['Response']->addFeedback('error', $Language->getText('include_session','invalid_pwd'));
		return false;
	} 
    else {
        $user_id     = db_result($res,0,'user_id');
        $user_status = db_result($res,0,'status');
        return true;
    }
}

function session_login_valid_status($status, $allowpending=0) {
    global $Language;

    // if allowpending (for verify.php) then allow
    if (($status == 'A') || ($status == 'R') || ($allowpending && ($status == 'P'))) {
        return true;
    } else {
        if ($status == 'S') { 
            //acount suspended
            $GLOBALS['Response']->addFeedback('error', $Language->getText('include_session','account_suspended'));
            return false;
        }
        if ($status == 'P') { 
            //account pending
            $GLOBALS['Response']->addFeedback('error', $Language->getText('include_session','account_pending'));
            return false;
        } 
        if ($status == 'D') { 
            //account deleted
            $GLOBALS['Response']->addFeedback('error', $Language->getText('include_session','account_deleted'));
            return false;
        }
        if (($status != 'A')&&($status != 'R')) {
            //unacceptable account flag
            $GLOBALS['Response']->addFeedback('error', $Language->getText('include_session','account_not_active'));
            return false;
        }
    }
}

function session_checkip($oldip,$newip) {
	$eoldip = explode(".",$oldip);
	$enewip = explode(".",$newip);
	
	// ## require same class b subnet
	if (($eoldip[0]!=$enewip[0])||($eoldip[1]!=$enewip[1])) {
		return 0;
	} else {
		return 1;
	}
}

function session_issecure() {
	return (getenv('HTTPS') == 'on');
}

function session_make_url($loc) {
	 return get_server_url(). $loc;
}

function session_redirect($loc) {
	header('Location: ' . $loc);
	print("\n\n");
	exit;
}

function session_require($req) {
  global $Language;
	/*
		CodeX admins always return true
	*/
	if (user_is_super_user()) {
		return true;
	}

	if (isset($req['group']) && $req['group']) {
		$query = "SELECT user_id FROM user_group WHERE user_id=" . user_getid()
			. " AND group_id=$req[group]";
		if ($req['admin_flags']) {
		$query .= " AND admin_flags = '$req[admin_flags]'";	
		}
 
		if ((db_numrows(db_query($query)) < 1) || !$req['group']) {
			exit_error($Language->getText('include_session','insufficient_g_access'),$Language->getText('include_session','no_perm_to_view'));
		}
	}
	elseif (isset($req['user']) && $req['user']) {
		if (user_getid() != $req['user']) {	
			exit_error($Language->getText('include_session','insufficient_u_access'),$Language->getText('include_session','no_perm_to_view'));
		}
	}
        elseif (isset($req['isloggedin']) && $req['isloggedin']) {
		if (!user_isloggedin()) {
			exit_error($Language->getText('include_session','required_login'),$Language->getText('include_session','login'));
		}
	} else {
		exit_error($Language->getText('include_session','insufficient_access'),$Language->getText('include_session','no_access'));
	}
}

function session_setglobals($user_id) {
	global $G_USER;

//	unset($G_USER);

	if ($user_id > 0) {
		$result=db_query("SELECT user_id,user_name FROM user WHERE user_id='$user_id'");
		if (!$result || db_numrows($result) < 1) {
			//echo db_error();
			$G_USER = array();
		} else {
			$G_USER = db_fetch_array($result);
//			echo $G_USER['user_name'].'<BR>';
		}
	} else {
		$G_USER = array();
	}
}

function session_set_new($user_id) {
  global $G_SESSION,$Language;

//	unset($G_SESSION);

	// concatinate current time, and random seed for MD5 hash
	// continue until unique hash is generated (SHOULD only be once)
	do {

		$pre_hash = time() . rand() . $GLOBALS['REMOTE_ADDR'] . microtime();
		$GLOBALS['session_hash'] = md5($pre_hash);

	} while (db_numrows(db_query("SELECT session_hash FROM session WHERE session_hash='$GLOBALS[session_hash]'")) > 0);
		
	// If permanent login configured then cookie expires in one year from now
	$res = db_query('SELECT sticky_login from user where user_id = '.$user_id);
	if ($res) {
	    $expire = (db_result($res,0,'sticky_login') ? time()+$GLOBALS['sys_session_lifetime']:0);
	}

	// set session cookie
    $cookie_manager =& new CookieManager();
    $cookie_manager->setCookie('session_hash', $GLOBALS['session_hash'], $expire);

	// make new session entries into db
	db_query("INSERT INTO session (session_hash, ip_addr, time,user_id) VALUES "
		. "('$GLOBALS[session_hash]','$GLOBALS[REMOTE_ADDR]'," . time() . ",'$user_id')");

	// set global
	$res=db_query("SELECT * FROM session WHERE session_hash='$GLOBALS[session_hash]'");
	if (db_numrows($res) > 1) {
		session_delete($GLOBALS['session_hash']);
        exit_error($Language->getText('global','error'),$Language->getText('include_session','hash_err'));
	} else {
		$G_SESSION = db_fetch_array($res);
		session_setglobals($G_SESSION['user_id']);
	}
}

function session_set() {
	global $G_SESSION,$G_USER;

//	unset($G_SESSION);

	// assume bad session_hash and session. If all checks work, then allow
	// otherwise make new session
	$id_is_good = 0;

	// here also check for good hash, set if new session is needed
	if (isset($GLOBALS['session_hash']) && $GLOBALS['session_hash']) {
		$result=db_query("SELECT * FROM session WHERE session_hash='$GLOBALS[session_hash]'");
		$G_SESSION = db_fetch_array($result);

		// does hash exist?
		if ($G_SESSION['session_hash']) {
			if (session_checkip($G_SESSION['ip_addr'],$GLOBALS['REMOTE_ADDR'])) {
				$id_is_good = 1;
			} 
		} // else hash was not in database
	} // else (hash does not exist) or (session hash is bad)

	if ($id_is_good) {
		session_setglobals($G_SESSION['user_id']);
	} else {
		unset($G_SESSION);
		unset($G_USER);
	}
}

/**
 *	session_get_userid() - Wrapper function to return the User object for the logged in user.
 *	
 *	@return User
 *	@access public
 */
function session_get_userid() {
	global $G_USER;
	return $G_USER['user_id'];
}

/**
 *  session_continue - A utility method to carry on with an already established session with
 *  sessionKey
 * 
 * @param string The session key
 */
function session_continue($sessionKey) {
	$GLOBALS['session_hash'] = $sessionKey;
	session_set();
	$user_id = session_get_userid();
	if (isset($user_id) && $user_id) {
		return true;
	} else {
		return false;
	}
}

/**
 * session_delete - Delete from the database the session associated with the given session_hash
 *
 * @param string $sessionKey the session hash string associated with the session we want to delete
 * @return boolean true if the session is deleted in the database, false otherwise
 */
function session_delete($sessionKey) {
    return db_query("DELETE FROM session WHERE session_hash='$sessionKey'");
}

function session_hash() {
    return isset($GLOBALS['session_hash']) ? $GLOBALS['session_hash'] : false;
}
?>
