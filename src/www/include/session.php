<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// 
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
            if (session_login_is_pwd_expired($auth_user_id, $form_pw)) {
                $GLOBALS['Response']->redirect('/account/change_pw.php?user_id='.$auth_user_id);
            } else {
                //create a new session
                session_set_new($auth_user_id);

                session_store_login_success();
                session_login_feedback();

                return array(true, '');
            }
        }
        else {
            return array(false, $auth_user_status);
        }
    }
    else {
        session_store_login_failure($form_loginname);
        session_login_delay($form_loginname);
        return array(false, '');
    }
}

function session_login_is_pwd_expired($user_id, $form_pw) {
    $expired = false;
    if (isset($GLOBALS['sys_password_lifetime']) && $GLOBALS['sys_password_lifetime']) {
        $sql = "SELECT last_pwd_update FROM user WHERE user_id = ". db_ei($user_id);
        $res = db_query($sql);
        if ($res && db_numrows($res)) {
            $now = time();
            $expiration_date = $now - 3600*24*$GLOBALS['sys_password_lifetime'];
            $warning_date = $expiration_date + 3600*24*10; //Warns 10 days before
            
            $data = db_fetch_array($res);
            //var_dump($data['last_pwd_update'], $now, $expiration_date, $warning_date);
            if ($data['last_pwd_update'] < $expiration_date) {
                $expired = true;
                $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('include_session', 'expired_password'));
            } else {
                if ($data['last_pwd_update'] < $warning_date) {
                    $GLOBALS['Response']->addFeedback('warning', $GLOBALS['Language']->getText('include_session', 'password_will_expire', ceil(($data['last_pwd_update'] - $expiration_date)/(3600*24))));
                }
            }
        }
    }
    return $expired;
}
// Standard CodeX authentication, based on password stored in DB
function session_login_valid_db($form_loginname, $form_pw, $allowpending = 0, &$user_id, &$user_status)  {
  global $Language;
    $usr=null;

    if (!$form_loginname || !$form_pw) {
        $GLOBALS['Response']->addFeedback('error', $Language->getText('include_session','missing_pwd'));
        return false;
    }

    //get the user from the database using user_id and password
    $sql = sprintf('SELECT user_id,status'.
                   ' FROM user'.
                   ' WHERE user_name = "%s" '.
                   ' AND user_pw= "%s"',
                   db_escape_string($form_loginname),
                   md5($form_pw));
	$res = db_query($sql);
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
    if (($status == 'A') || ($status == 'R') || 
        ($allowpending && ($status == 'V' || $status == 'W' ||
            ($GLOBALS['sys_user_approval']==0 && $status == 'P')))) {
        return true;
    } else {
        if ($status == 'S') { 
            //acount suspended
            $GLOBALS['Response']->addFeedback('error', $Language->getText('include_session','account_suspended'));
            return false;
        }
        if (($GLOBALS['sys_user_approval']==0 && ($status == 'P' || $status == 'V' || $status == 'W'))||
            ($GLOBALS['sys_user_approval']==1 && ($status == 'V' || $status == 'W'))) { 
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
	$GLOBALS['Response']->redirect($loc);
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
			. " AND group_id=".db_ei($req['group']);
		if (isset($req['admin_flags']) && $req['admin_flags']) {
            $query .= " AND admin_flags = '".db_escape_string($req['admin_flags'])."'";
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
		$result=db_query("SELECT user_id,user_name FROM user WHERE user_id=".db_ei($user_id));
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
		$pre_hash = time() . rand() . $_SERVER['REMOTE_ADDR'] . microtime();
		$GLOBALS['session_hash'] = md5($pre_hash);

	} while (db_numrows(db_query("SELECT session_hash FROM session WHERE session_hash='".db_escape_string($GLOBALS['session_hash'])."'")) > 0);
		
	// If permanent login configured then cookie expires in one year from now
	$res = db_query('SELECT sticky_login from user where user_id = '.db_ei($user_id));
	if ($res) {
	    $expire = (db_result($res,0,'sticky_login') ? time()+$GLOBALS['sys_session_lifetime']:0);
	}

	// set session cookie
    $cookie_manager =& new CookieManager();
    $cookie_manager->setCookie('session_hash', $GLOBALS['session_hash'], $expire);

	// make new session entries into db
	db_query("INSERT INTO session (session_hash, ip_addr, time,user_id) VALUES "
		. "('".db_escape_string($GLOBALS['session_hash'])."','".db_escape_string($_SERVER['REMOTE_ADDR'])."'," . time() . ",".db_ei($user_id).")");
	// set global
	$res=db_query("SELECT * FROM session WHERE session_hash='".db_escape_string($GLOBALS['session_hash'])."'");
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
        $sql = 'SELECT *'.
            ' FROM session'.
            ' WHERE session_hash="'.db_escape_string($GLOBALS['session_hash']).'"';
		$result=db_query($sql);
		$G_SESSION = db_fetch_array($result);

		// does hash exist?
		if ($G_SESSION['session_hash']) {
			if (session_checkip($G_SESSION['ip_addr'],$_SERVER['REMOTE_ADDR'])) {
				$id_is_good = 1;
			} 
		} // else hash was not in database
	} // else (hash does not exist) or (session hash is bad)

	if ($id_is_good) {
		session_setglobals($G_SESSION['user_id']);
                session_store_access();
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
    return db_query("DELETE FROM session WHERE session_hash='".db_escape_string($sessionKey)."'");
}

function session_hash() {
    return isset($GLOBALS['session_hash']) ? $GLOBALS['session_hash'] : false;
}

function session_store_access() {
    $current_date=time();
    $res=db_query("SELECT last_access_date FROM user WHERE user_id='".user_getid()."'");
    if (db_numrows($res)) {
        $last_access_date=db_result($res,0,'last_access_date');
        // Don't log access if already accessed in the past 6 hours (scalability+privacy)
        if (abs($current_date - $last_access_date) > 21600) {
            // make new date entries into db
            $upd_res=db_query("UPDATE user SET last_access_date='".$current_date."' WHERE user_id='".user_getid()."'");
        }
    }
}

/**
 * Store login success.
 *
 * Store last log-on success timestamp in 'last_auth_success' field and backup
 * the previous value in 'prev_auth_success'. In order to keep the failure
 * counter coherent, if the 'last_auth_success' is newer than the
 * 'last_auth_failure' it means that there was no bad attempts since the last
 * log-on and 'nb_auth_failure' can be reset to zero.
 *
 * @todo: define a global time object that would give the same time to all
 * actions on an execution.
 */
function session_store_login_success() {
    $time = time();
    $sql = 'UPDATE user '.
        ' SET nb_auth_failure = IF(last_auth_success>=last_auth_failure,0,nb_auth_failure),'.
        ' prev_auth_success = last_auth_success,'.
        ' last_auth_success = '.db_ei($time).
        ' WHERE user_id='.db_ei(user_getid());
    db_query($sql);
}

/**
 * Store login failure.
 *
 * Store last log-on failure and increment the number of failure. If the there
 * was no bad attemps since the last successful login (ie. 'last_auth_success'
 * newer than 'last_auth_failure') the counter is reset to 1.
 */
function session_store_login_failure($login) {
    $time = time();
    $sql = 'UPDATE user'.
        ' SET nb_auth_failure = IF(last_auth_success>=last_auth_failure,1,nb_auth_failure+1),'.
        ' last_auth_failure = '.db_ei($time).
        ' WHERE user_name="'.db_es($login).'"';
    db_query($sql);
}

/**
 * Populate response with details about login attempts.
 *
 * Always display the last succefull log-in. But if there was errors (number of
 * bad attempts > 0) display the number of bad attempts and the last
 * error. Moreover, in case of errors, messages are displayed as warning
 * instead of info.
 */
function session_login_feedback() {
    $um =& UserManager::instance();
    $user =& $um->getCurrentUser();
    $level = 'info';
    if($user->getNbAuthFailure() > 0) {
        $level = 'warning';
        $GLOBALS['Response']->addFeedback($level, $GLOBALS['Language']->getText('include_menu', 'auth_last_failure').' '.format_date($GLOBALS['sys_datefmt'], $user->getLastAuthFailure()));
        $GLOBALS['Response']->addFeedback($level, $GLOBALS['Language']->getText('include_menu', 'auth_nb_failure').' '.$user->getNbAuthFailure());
    }
    // Display nothing if no previous record.
    if($user->getPreviousAuthSuccess() > 0) {
        $GLOBALS['Response']->addFeedback($level, $GLOBALS['Language']->getText('include_menu', 'auth_prev_success').' '.format_date($GLOBALS['sys_datefmt'], $user->getPreviousAuthSuccess()));
    }
}

/**
 * Add a delay when use login fail.
 *
 * The delay is 2 sec/nb of bad attempt.
 */
function session_login_delay($login) {
    $sql = 'SELECT nb_auth_failure'.
        ' FROM user'.
        ' WHERE user_name="'.db_es($login).'"';
    $res = db_query($sql);
    if($res && !db_error() && db_numrows($res) == 1) {
        $row = db_fetch_array($res);
        sleep(2 * $row['nb_auth_failure']);
    }
}

?>
