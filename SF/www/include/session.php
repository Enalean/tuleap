<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// $Id$
//

$G_SESSION=array();
$G_USER=array();

function session_login_valid($form_loginname,$form_pw,$allowpending=0)  {
	global $session_hash,$feedback;

	if (!$form_loginname || !$form_pw) {
		$feedback = 'Missing Password Or User Name';
		return array(false,'');
	}

	//get the user from the database using user_id and password
	$res = db_query("SELECT user_id,status FROM user WHERE "
		. "user_name='$form_loginname' "
		. "AND user_pw='" . md5($form_pw) . "'");
	if (!$res || db_numrows($res) < 1) {
		//invalid password or user_name
		$feedback='Invalid Password Or User Name';
		return array(false,'');
	} else {
		// check status of this user
		$usr = db_fetch_array($res);
		$status = $usr['status'];

		// if allowpending (for verify.php) then allow
		if ($allowpending && ($status == 'P')) {
			//1;
		} else {
			if ($status == 'S') { 
				//acount suspended
				$feedback = 'Account Suspended';
				return array(false,$status);
			}
			if ($status == 'P') { 
				//account pending
				$feedback = 'Account Pending';
				return array(false,$status);
			} 
			if ($status == 'D') { 
				//account deleted
				$feedback = 'Account Deleted';
				return array(false,$status);
			}
			if (($usr['status'] != 'A')&&($usr['status'] != 'R')) {
				//unacceptable account flag
				$feedback = 'Account Not Active';
				return array(false,$status);
			}
		}
		//create a new session
		session_set_new(db_result($res,0,'user_id'));

		//if we got this far, the name/pw must be ok
		//db_query("UPDATE session SET user_id='" . db_result($res,0,'user_id') . "' WHERE session_hash='$session_hash'");

		return array(true,$status);
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

function session_cookie($n,$v, $expire = 0) {
    // Make sure there isn't a port number in the default domain name
    // or the setcookie for the entire domain won't work
    list($host,$port) = explode(':',$GLOBALS['sys_default_domain']);
    //setcookie($n,$v,$expire,'/',$host,0); Modified for PHP5 or for 'localhost' ?
    setcookie($n,$v,$expire,'/');
}

function session_make_url($loc) {
	 return 'http'
	 	. (session_issecure()?'s':'')
		. '://'
		. (session_issecure()?$GLOBALS['sys_https_host']:$GLOBALS['sys_default_domain'])
		. $loc
		;
}

function session_redirect($loc) {
	header('Location: ' . session_make_url($loc));
	print("\n\n");
	exit;
}

function session_require($req) {
	/*
		SF admins always return true
	*/
	if (user_is_super_user()) {
		return true;
	}

	if ($req['group']) {
		$query = "SELECT user_id FROM user_group WHERE user_id=" . user_getid()
			. " AND group_id=$req[group]";
		if ($req['admin_flags']) {
		$query .= " AND admin_flags = '$req[admin_flags]'";	
		}
 
		if ((db_numrows(db_query($query)) < 1) || !$req['group']) {
			exit_error("Insufficient Group Access","You do not have permission to "
				. "view this page.");
		}
	}
	elseif ($req['user']) {
		if (user_getid() != $req['user']) {	
			exit_error("Insufficient User Access","You do not have permission to "
				. "view this page.");
		}
	}
	elseif ($req['isloggedin']) {
		if (!user_isloggedin()) {
			exit_error("Required Login","In order to view this page, you must "
				. "be logged in.");
		}
	} else {
		exit_error("Insufficient Access","Probably by mangling a URL, you have attempted "
			. "to reach a part of the site for which you do not have access. This can "
			. "probably be fixed by properly navigating through the site.");
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
	global $G_SESSION;
	$lifetime = 3600*24*183; // about 6 months

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
	    // Caution!! Lifetime must also be changed in
	    // utils/underworld-root/db_project_cleanup.pl
	    $lifetime = 3600*24*183; // about 6 months
	    $expire = (db_result($res,0,'sticky_login') ? time()+$lifetime :0);
	}

	// set session cookie
	session_cookie("session_hash",$GLOBALS['session_hash'],$expire);

	// make new session entries into db
	db_query("INSERT INTO session (session_hash, ip_addr, time,user_id) VALUES "
		. "('$GLOBALS[session_hash]','$GLOBALS[REMOTE_ADDR]'," . time() . ",'$user_id')");

	// set global
	$res=db_query("SELECT * FROM session WHERE session_hash='$GLOBALS[session_hash]'");
	if (db_numrows($res) > 1) {
		db_query("DELETE FROM session WHERE session_hash='$GLOBALS[session_hash]'");
		exit_error("ERROR","ERROR - two people had the same hash - backarrow and re-login. It should never happen again");
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
	if ($GLOBALS['session_hash']) {
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

?>
