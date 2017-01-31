<?php
/**
 * Copyright (c) Enalean, 2016. All Rights Reserved.
 * Copyright 1999-2000 (c) The SourceForge Crew
 *
 * This file is a part of Tuleap.
 *
 * Tuleap is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Tuleap is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */


$G_SESSION=array();

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

?>
