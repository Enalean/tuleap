<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// 
//


$G_SESSION=array();

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
		Codendi admins always return true
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

/**
 * Mandate current session to be site admin otherwise redirect to an error page
 */
function session_require_site_admin() {
    session_require(array('group' => '1', 'admin_flags' => 'A'));
}

/**
 *  session_continue - A utility method to carry on with an already established session with
 *  sessionKey
 * 
 * @param string The session key
 */
function session_continue($sessionKey) {
    $user = UserManager::instance()->getCurrentUser($sessionKey);
    return $user->isLoggedIn();
}

function session_hash() {
    return UserManager::instance()->getCurrentUser()->getSessionHash();
}

?>
