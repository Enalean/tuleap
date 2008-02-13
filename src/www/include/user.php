<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// 

  //$Language->loadLanguageMsg('include/include');

unset($USER_IS_SUPER_USER);
$USER_RES=array();

function user_isloggedin() {
	global $G_USER;
	if (isset($G_USER['user_id']) && $G_USER['user_id']) {
		return true;
	} else {
		return false;
	}
}

function user_isrestricted() {
	if (!user_isloggedin()) {
		return false;
	}
	$user_id=user_getid();
	$res = db_query("SELECT status FROM user WHERE user_id='".db_es($user_id)."' AND status='R'");
	if (!$res || db_numrows($res) < 1) {
		//matching row wasn't found
		return false;
	} else return true;
}


function user_is_super_user() {
	global $USER_IS_SUPER_USER;
	/*
		members of group_id 1 who are admins have super-user privs site-wide
	*/

	if (isset($USER_IS_SUPER_USER)) {
		return $USER_IS_SUPER_USER;
	} else {
		if (user_isloggedin()) {
			$sql="SELECT * FROM user_group WHERE user_id='". user_getid() ."' AND group_id='1' AND admin_flags='A'";
			$result=db_query($sql);
			if (!$result || db_numrows($result) < 1) {
				$USER_IS_SUPER_USER=false;
				return $USER_IS_SUPER_USER;
			} else {
				//matching row was found - set and save this knowledge for later
				$USER_IS_SUPER_USER=true;
				return $USER_IS_SUPER_USER;
			}
		} else {
			$USER_IS_SUPER_USER=false;
			return $USER_IS_SUPER_USER;
		}
	}
}

function user_ismember($group_id,$type=0) {
	if (!user_isloggedin()) {
		return false;
	}
	$user_id=user_getid(); //optimization

	/*
		CodeX admins always return true
	*/
	if (user_is_super_user()) {
		return true;
	}

	/*
		for everyone else, do a query
	*/
	$query = "SELECT user_id FROM user_group "
		. "WHERE user_id='".db_es($user_id)."' AND group_id='".db_es($group_id)."'";

	$type=strtoupper($type);

	switch ($type) {
		/*
			list the supported permission types
		*/
		case 'B1' : {
			//bug tech
			$query .= ' AND bug_flags IN (1,2)';
			break;
		}
		case 'B2' : {
			//bug admin
			$query .= ' AND bug_flags IN (2,3)';
			break;
		}
		case 'P1' : {
			//pm tech
			$query .= ' AND project_flags IN (1,2)';
			break;
		}
		case 'P2' : {
			//pm admin
			$query .= ' AND project_flags IN (2,3)';
			break;
		}
		case 'C1' : {
			//patch tech
			$query .= ' AND patch_flags IN (1,2)';
			break;
		}
		case 'C2' : {
			//patch admin
			$query .= ' AND patch_flags IN (2,3)';
			break;
		}
		case 'F2' : {
			//forum admin
			$query .= ' AND forum_flags IN (2)';
			break;
		}
		case 'S1' : {
			//support tech
			$query .= ' AND support_flags IN (1,2)';
			break;
		}
		case 'S2' : {
			//support admin
			$query .= ' AND support_flags IN (2,3)';
			break;
		}
		case '0' : {
			//just in this group
			break;
		}
		case 'A' : {
			//admin for this group
			$query .= " AND admin_flags = 'A'";
			break;
		}
		case 'D1' : {
			//document tech
			$query .= " AND doc_flags IN (1,2)";
			break;
		}
		case 'D2' : {
			//document admin
			$query .= " AND doc_flags IN (2,3)";
			break;
		}
		case 'R2' : {
			//file release admin
			$query .= " AND file_flags = '2'";
			break;
                }
	        case 'W2': {
		        //wiki release admin
			$query .= " AND wiki_flags = '2'";
			break;
		}
		case 'N1': {
			//News write perm
			$query .= " AND news_flags = '1'";
			break;
		}
		case 'N2': {
			//News admin
			$query .= " AND news_flags = '2'";
			break;
		}
		case 'SVN_ADMIN': {
			//svn release admin
			$query .= " AND svn_flags = '2'";
			break;
		}
		default : {
			//fubar request
			return false;
		}
	}

	$res = db_query($query);
	if (!$res || db_numrows($res) < 1) {
		//matching row wasn't found
		return false;
	} else {
		//matching row was found
		return true;
	}
}

function user_getid() {
	global $G_USER;
	return ($G_USER?$G_USER['user_id']:0);
}

function user_getname($user_id = 0) {
    global $G_USER,$USER_NAMES,$Language;
	// use current user if one is not passed in
	if (!$user_id) {
		return ($G_USER?$G_USER['user_name']:"NA");
	}
	// else must lookup name
	else {
		if (isset($USER_NAMES["user_$user_id"]) && $USER_NAMES["user_$user_id"]) {
			//user name was fetched previously
			return $USER_NAMES["user_$user_id"];
		} else {
			//fetch the user name and store it for future reference
			$result = db_query("SELECT user_id,user_name FROM user WHERE user_id='".db_es($user_id)."'");
			if ($result && db_numrows($result) > 0) {
				//valid user - store and return
				$USER_NAMES["user_$user_id"]=db_result($result,0,"user_name");
				return $USER_NAMES["user_$user_id"];
			} else {
				//invalid user - store and return
				$USER_NAMES["user_$user_id"]="<B>".$Language->getText('include_user','invalid_u_id')."</B>";
				return $USER_NAMES["user_$user_id"];
			}
		}
	}
}

//quick hack - this entire library needs a rewrite similar to groups library
function user_getrealname($user_id) {
	global $Language;
        $result = user_get_result_set($user_id); 
	if ($result && db_numrows($result) > 0) {
		return db_result($result,0,"realname");
	} else {
		return $Language->getText('include_user','not_found');
	}
}

// LJ - Added here because we use the real e-mail addresse
// on CodeX - No e-mail aliases like on SF
function user_getemail($user_id) {
	global $Language;
        $result = user_get_result_set($user_id); 
	if ($result && db_numrows($result) > 0) {
		return db_result($result,0,"email");
	} else {
		return $Language->getText('include_user','email_not_found');
	}
}

function user_getid_from_email($email) {
	global $Language;
	$result = db_query("SELECT user_id FROM user WHERE email='".db_es($email)."'");
	if ($result && db_numrows($result) > 0) {
		return db_result($result,0,"user_id");
	} else {
		return $Language->getText('include_user','not_found');
	}
}


function user_getemail_from_unix($user_name) {
	global $Language;
        $result = user_get_result_set_from_unix($user_name); 
	if ($result && db_numrows($result) > 0) {
		return db_result($result,0,"email");
	} else {
		return $Language->getText('include_user','email_not_found');
	}
}

function user_get_result_set($user_id) {
	//create a common set of user result sets,
	//so it doesn't have to be fetched each time

	global $USER_RES;
	if (!isset($USER_RES["_".$user_id."_"]) || !$USER_RES["_".$user_id."_"]) {
		$USER_RES["_".$user_id."_"]=db_query("SELECT * FROM user WHERE user_id='".db_es($user_id)."'");
		return $USER_RES["_".$user_id."_"];
	} else {
		return $USER_RES["_".$user_id."_"];
	}
}

function user_get_result_set_from_unix($user_name) {
	//create a common set of user result sets,
	//so it doesn't have to be fetched each time
		
	global $USER_RES;
	$res = db_query("SELECT * FROM user WHERE user_name='".db_es($user_name)."'");
	$user_id = db_result($res,0,'user_id');
	$USER_RES["_".$user_id."_"] = $res;
	return $USER_RES["_".$user_id."_"];
}       
function user_get_result_set_from_email($email) {
	//create a common set of user result sets,
	//so it doesn't have to be fetched each time
		
	global $USER_RES;
    $sql = "SELECT * FROM user WHERE (user_name='".db_es($email)."' or email='".db_es($email)."')";
	$res = db_query($sql);
	$user_id = db_result($res,0,'user_id');
	$USER_RES["_".$user_id."_"] = $res;
	return $USER_RES["_".$user_id."_"];
}       

// Get user name from user id, according to the user prefs: Codex login or Real name
function user_get_name_display_from_id($user_id) {
    
    if ($user_id == 100) {
        return user_getname($user_id);
    }
    
    $u_display = user_get_preference("username_display");
    // 0 - realname (username)
    // 1 - username (realname)
    // 2 - username
    // 3 - realname
    if ($u_display == 2) {
        $uname = user_getname($user_id);
    } else if ($u_display == 3){
        $uname = user_getrealname($user_id);
    } else if ($u_display == 1){
        $uname = user_getname($user_id)." (".user_getrealname($user_id).")";
    } else {
        $uname = user_getrealname($user_id)." (".user_getname($user_id).")";
    }
    return $uname;
    
}

// Get user name from Codex login, according to the user prefs: Codex login or Real name
function user_get_name_display_from_unix($user_name) {
    
    if ($user_name == $GLOBALS['Language']->getText('global','none')) {
        //return None
    	return $user_name;
    }
    
    $u_display = user_get_preference("username_display");
    if ($u_display == 2) {
    	//Codex login
        return $user_name;
    } else {
    	// need Real name   
        // Note: this is not optimal! We should use some caching.
        // We should also factorize with user_get_name_display_from_id
        $sql = sprintf('SELECT realname FROM user'.
                       ' WHERE user_name = "%s"',
                       db_escape_string($user_name));
        $res = db_query($sql);
        if (db_numrows($res) < 1) {
            return $user_name;				
        } else {
            if ($u_display == 3) { return  db_result($res,0,'realname'); }
            else if ($u_display == 1) { return  $user_name." (".db_result($res,0,'realname').")"; }
            else return db_result($res,0,'realname')." ($user_name)";
        }
    }    
    
}

function user_get_timezone() {
	if (user_isloggedin()) {
		$result=user_get_result_set(user_getid());
		return db_result($result,0,'timezone');
	} else {
		return '';
	}
}

// Get user prefered language from the database
// if language not defined then return system default
function user_get_language() {
    if (user_isloggedin()) {
	$result=user_get_result_set(user_getid());
	$lang_id = db_result($result,0,'language_id');
    }
    if (!$lang_id) { $lang_id = $GLOBALS['sys_lang']; }
    return $lang_id;
}

function user_get_languagecode() {
    $res=db_query("SELECT * FROM supported_languages WHERE language_id='".user_get_language()."'");
	return db_result($res,0,'language_code');
}

function user_set_preference($preference_name,$value) {
	GLOBAL $user_pref;
	if (user_isloggedin()) {
		$preference_name=strtolower(trim($preference_name));
		$result=db_query("UPDATE user_preferences SET preference_value='".db_es($value)."' ".
			"WHERE user_id='".user_getid()."' AND preference_name='".db_es($preference_name)."'");
		if (db_affected_rows($result) < 1) {
			echo db_error();
			$result=db_query("INSERT INTO user_preferences (user_id,preference_name,preference_value) ".
				"VALUES ('".user_getid()."','".db_es($preference_name)."','".db_es($value)."')");
		}

		// Update the Preference cache if it was setup by a user_get_preference
		if (isset($user_pref)) { $user_pref[$preference_name] = $value; }

		return true;

	} else {
		return false;
	}
}

function user_get_preference($preference_name) {
	GLOBAL $user_pref;
	if (user_isloggedin()) {
		$preference_name=strtolower(trim($preference_name));
		/*
			First check to see if we have already fetched the preferences
		*/
		if ($user_pref) {
                    if (isset($user_pref["$preference_name"]) && $user_pref["$preference_name"]) {
				//we have fetched prefs - return part of array
				return $user_pref["$preference_name"];
			} else {
				//we have fetched prefs, but this pref hasn't been set
				return false;
			}
		} else {
			//we haven't returned prefs - go to the db
			$result=db_query("SELECT preference_name,preference_value FROM user_preferences ".
				"WHERE user_id='".user_getid()."'");
	
			if (db_numrows($result) < 1) {
				return false;
			} else {
				//iterate and put the results into an array
				for ($i=0; $i<db_numrows($result); $i++) {
					$user_pref[db_result($result,$i,'preference_name')]=db_result($result,$i,'preference_value');
				}
				if (isset($user_pref["$preference_name"])) {
					//we have fetched prefs - return part of array
			                return $user_pref["$preference_name"];
				} else {
					//we have fetched prefs, but this pref hasn't been set
					return false;
				}
			}
		}
	} else {
		return false;
	}
}

function user_del_preference($preference_name) {
    GLOBAL $user_pref;
    if (user_isloggedin()) {
        if ($user_pref && array_key_exists($preference_name, $user_pref)) {
            unset($user_pref[$preference_name]);
        }
        $sql = 'DELETE FROM user_preferences'
            .' WHERE preference_name="'.db_es($preference_name).'"'
            .' AND user_id='.user_getid();
        $res = db_query($sql);
        if(db_affected_rows($res) != 1) {
            return false;
        }
        else {
            return true;
        }
    }
    else {
        return false;
    }
}

function user_display_choose_password($user_id = false) {
    $GLOBALS['Language']->loadLanguageMsg('account/account');
    $request =& HTTPRequest::instance();
    ?>
    <table><tr valign='top'><td><? echo $GLOBALS['Language']->getText('account_change_pw', 'new_password'); ?>:
    <br><input type="password" value="" id="form_pw" name="form_pw">
    <p><? echo $GLOBALS['Language']->getText('account_change_pw', 'new_password2'); ?>:
    <br><input type="password" value="" name="form_pw2">
    </td><td>
    <fieldset>
        <legend><?=$GLOBALS['Language']->getText('account_check_pw', 'password_robustness')?> <span id="password_strategy_good_or_bad"></span></legend>
        <?php
        $password_strategy =& new PasswordStrategy();
        include($GLOBALS['Language']->getContent('account/password_strategy'));
        foreach($password_strategy->validators as $key => $v) {
            echo '<div id="password_validator_msg_'. $key .'">'. $v->description() .'</div>';
        }
        ?>
    </fieldset>
    </td></tr></table>
    <script type="text/javascript">
    var password_validators = [<?= implode(', ', array_keys($password_strategy->validators)) ?>];
    </script>
    <?php
    if ($user_id) {
        echo '<input type="hidden" name="user_id" value="'. $user_id .'" />';
    }
}

?>
