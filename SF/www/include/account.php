<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// $Id$
//
// adduser.php - All the forms and functions to manage unix users
//

// ***** function account_pwvalid()
// ***** check for valid password

function account_pwvalid($pw) {
	if (strlen($pw) < 6) {
		$GLOBALS['register_error'] = "Password must be at least 6 characters.";
		return 0;
	}
	return 1;
}


// Add user to an existing project
function account_add_user_to_group ($group_id,$user_unix_name) {
	global $feedback;
	
	$ret = false;

	$res_newuser = db_query("SELECT status,user_id,unix_status,unix_uid FROM user WHERE user_name='$user_unix_name'");

	if (db_numrows($res_newuser) > 0) {

	    //user was found but if it's a pending account adding
	    //is not allowed
	    if (db_result($res_newuser,0,'status') == 'P') {
		$feedback .= " Account '$user_unix_name' pending. Must first be confirmed by user. User not added.";
		return false;
	    }

		$form_newuid = db_result($res_newuser,0,'user_id');

		//if not already a member, add it
		$res_member = db_query("SELECT user_id FROM user_group WHERE user_id='$form_newuid' AND group_id='$group_id'");
		if (db_numrows($res_member) < 1) {
			//not already a member
			db_query("INSERT INTO user_group (user_id,group_id) VALUES ('$form_newuid','$group_id')");

			//if no unix account, give them a unix_uid
			if ((db_result($res_newuser,0,'unix_status') == 'N') || (!db_result($res_newuser,0,'unix_uid') )) {
				db_query("UPDATE user SET unix_status='A',unix_uid=" . account_nextuid() . " WHERE user_id=$form_newuid");
			}
			$feedback .= " User added ";
                        account_send_add_user_to_group_email($group_id,$form_newuid);
			$ret = true;
		} else {
			//user was a member
			$feedback .= " User is already a member ";
		}
	} else {
		//user doesn't exist
		$feedback .= "User does not exist";
	}

	return $ret;
}

// Warn user she has been added to a project
function account_send_add_user_to_group_email($group_id,$user_id) {
    $base_url = get_server_url();

    // Get email address
    $res = db_query("SELECT email FROM user WHERE user_id=$user_id");
    if (db_numrows($res) > 0) {
        $email_address = db_result($res,0,'email');
        $res2 = db_query("SELECT group_name,unix_group_name FROM groups WHERE group_id=$group_id");
        if (db_numrows($res2) > 0) {
            $group_name = db_result($res2,0,'group_name');
            $unix_group_name = db_result($res2,0,'unix_group_name');
            // $message is defined in the content file
            include(util_get_content('include/add_user_to_group_email'));
            
            list($host,$port) = explode(':',$GLOBALS['sys_default_domain']);		
            mail($email_address, $GLOBALS['sys_name']." - Welcome to Project ".$group_name,$message,"From: noreply@".$host);
        }
    }
}


function account_namevalid($name) {
	// no spaces
	if (strrpos($name,' ') > 0) {
		$GLOBALS['register_error'] = "There cannot be any spaces in the login name.";	
		return 0;
	}

	// must have at least one character
	if (strspn($name,"abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ") == 0) {
		$GLOBALS['register_error'] = "There must be at least one character.";
		return 0;
	}

	// must contain all legal characters
	//if (strspn($name,"abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ!@#\$%^&*()-_\\/{}[]<>+=|;:?.,`~")
	if (strspn($name,"abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789-_")
		!= strlen($name)) {
		$GLOBALS['register_error'] = "Illegal character in name.";
		return 0;
	}

	// min and max length
	if (strlen($name) < 3) {
		$GLOBALS['register_error'] = "Name is too short. It must be at least 3 characters.";
		return 0;
	}
	if (strlen($name) > 32) {
		$GLOBALS['register_error'] = "Name is too long. It must be less than 32 characters.";
		return 0;
	}

	// illegal names
	if (eregi("^((root)|(bin)|(daemon)|(adm)|(lp)|(sync)|(shutdown)|(halt)|(mail)|(news)"
		. "|(uucp)|(operator)|(games)|(mysql)|(httpd)|(nobody)|(dummy)"
		. "|(www)|(cvs)|(shell)|(ftp)|(irc)|(debian)|(ns)|(download))$",$name)) {
		$GLOBALS['register_error'] = "Name is reserved.";
		return 0;
	}
	if (eregi("^(anoncvs_)",$name)) {
		$GLOBALS['register_error'] = "Name is reserved for CVS.";
		return 0;
	}
		
	return 1;
}

function account_groupnamevalid($name) {
	if (!account_namevalid($name)) return 0;
	
	// illegal names
	if (eregi("^((www[0-9]?)|(cvs[0-9]?)|(shell[0-9]?)|(ftp[0-9]?)|(irc[0-9]?)|(news[0-9]?)"
		. "|(mail[0-9]?)|(ns[0-9]?)|(download[0-9]?)|(pub)|(users)|(compile)|(lists)"
		. "|(slayer)|(orbital)|(tokyojoe)|(webdev)|(projects)|(cvs)|(slayer)|(monitor)|(mirrors?))$",$name)) {
		$GLOBALS['register_error'] = "Name is reserved for DNS purposes.";
		return 0;
	}

	if (eregi("_",$name)) {
		$GLOBALS['register_error'] = "Group name cannot contain underscore for DNS reasons.";
		return 0;
	}

	return 1;
}

// The following is a random salt generator
function account_gensalt(){
	function rannum(){	     
		mt_srand((double)microtime()*1000000);		  
		$num = mt_rand(46,122);		  
		return $num;		  
	}	     
	function genchr(){
		do {	  
			$num = rannum();		  
		} while ( ( $num > 57 && $num < 65 ) || ( $num > 90 && $num < 97 ) );	  
		$char = chr($num);	  
		return $char;	  
	}	   

	$a = genchr(); 
	$b = genchr();
// (LJ) Adding $1$ at the beginning of the salt
// forces the MD5 encryption so the system has to
// have MD5 pam module installed for Unix passwd file.
	$salt = "$1$" . "$a$b";
	return $salt;	
}

// generate unix pw
function account_genunixpw($plainpw) {
	return crypt($plainpw,account_gensalt());
}

// generate the 2 windows passwords (win_passwd:winNT_passwd)
function account_genwinpw($plainpw) {
    $command = "/usr/local/bin/gensmbpasswd";
    $output = array();
    if (is_executable($command)) {
	$command .= ' "'.escapeshellcmd($plainpw).'"';
	exec($command, $output, $ret);
    }
    return rtrim($output[0]);
}

// returns next userid
function account_nextuid() {
	db_query("SELECT max(unix_uid) AS maxid FROM user");
	$row = db_fetch_array();
	return ($row[maxid] + 1);
}

// print out shell selects
function account_shellselects($current) {
	$shells = file("/etc/shells");

	for ($i = 0; $i < count($shells); $i++) {
		$this_shell = chop($shells[$i]);

		if ($current == $this_shell) {
			echo "<option selected value=$this_shell>$this_shell</option>\n";
		} else {
			echo "<option value=$this_shell>$this_shell</option>\n";
		}
	}
}

