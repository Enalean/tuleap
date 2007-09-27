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

require_once('common/mail/Mail.class.php');
require_once('common/include/createntlm.inc');
require_once('common/password/PasswordStrategy.class.php');
require_once('common/password/PasswordRegexpValidator.class.php');

$Language->loadLanguageMsg('include/include');

// ***** function account_pwvalid()
// ***** check for valid password
function account_pwvalid($pw, &$errors) {
    $password_strategy =& new PasswordStrategy();
    include($GLOBALS['Language']->getContent('account/password_strategy'));
    $valid = $password_strategy->validate($pw);
    $errors = $password_strategy->errors;
    return $valid;
}

// Set user password (Unix, Web and Windows)
function account_set_password($user_id,$password) {
    $res = db_query("UPDATE user SET user_pw='" . md5($password) . "',"
                    . "unix_pw='" . account_genunixpw($password) . "',"
                    . "windows_pw='" . account_genwinpw($password) . "',"
                    . "last_pwd_update='".time()."'"
                    ." WHERE user_id=" . $user_id );          
    if (! $res) {
        return false;
    }
    return true;
}

// Add user to an existing project
function account_add_user_to_group ($group_id,&$user_unix_name) {
  global $Language;
	
	$ret = false;

    $user_unix_name = util_user_finder($user_unix_name, true);
	$res_newuser = db_query("SELECT status,user_id,unix_status,unix_uid FROM user WHERE user_name='$user_unix_name'");

	if (db_numrows($res_newuser) > 0) {

	    //user was found but if it's a pending account adding
	    //is not allowed
	    if (db_result($res_newuser,0,'status') == 'P') {
	      $GLOBALS['Response']->addFeedback('error', $Language->getText('include_account','account_pending',$user_unix_name));
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
			$GLOBALS['Response']->addFeedback('info', $Language->getText('include_account','user_added'));
            account_send_add_user_to_group_email($group_id,$form_newuid);
			$ret = true;
		} else {
			//user was a member
			$GLOBALS['Response']->addFeedback('error', $Language->getText('include_account','user_already_member'));
		}
	} else {
		//user doesn't exist
		$GLOBALS['Response']->addFeedback('error', $Language->getText('include_account','user_not_exist'));
	}

	return $ret;
}

// Warn user she has been added to a project
function account_send_add_user_to_group_email($group_id,$user_id) {
  global $Language;
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
            include($Language->getContent('include/add_user_to_group_email'));
            
            list($host,$port) = explode(':',$GLOBALS['sys_default_domain']);		
            $mail =& new Mail();
            $mail->setTo($email_address);
            $mail->setFrom($GLOBALS['sys_noreply']);
            $mail->setSubject($Language->getText('include_account','welcome',array($GLOBALS['sys_name'],$group_name)));
            $mail->setBody($message);
            if (!$mail->send()) {
                $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('global', 'mail_failed', array($GLOBALS['sys_email_admin'])));
            }
        }
    }
}


// Generate a valid Unix login name from the email address.
function account_make_login_from_email($email) {
    $pattern = "/^(.*)@.*$/";
    $replacement = "$1";
    $name=preg_replace($pattern, $replacement, $email);
    $name = substr($name, 0, 32);
    $name = strtr($name, ".:;,?%^*(){}[]<>+=$", "___________________");
    $name = strtr($name, "àaéèeùuç", "aaeeeuuc");
    return strtolower($name);
}


function account_namevalid($name, $key = '') {
  global $Language;
	// no spaces
	if (strrpos($name,' ') > 0) {
        if ($key == '') {
            $k = 'login_err';
        } else {
            $k = $key . '_spaces';
        }
		$GLOBALS['register_error'] = $Language->getText('include_account', $k);	
		return 0;
	}

	// must have at least one character
	if (strspn($name,"abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ") == 0) {
		$GLOBALS['register_error'] = $Language->getText('include_account','char_err');
		return 0;
	}

	// must contain all legal characters
	//if (strspn($name,"abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ!@#\$%^&*()-_\\/{}[]<>+=|;:?.,`~")
	if (strspn($name,"abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789-_")
		!= strlen($name)) {
		$GLOBALS['register_error'] = $Language->getText('include_account','illegal_char');
		return 0;
	}

	// min and max length
	if (strlen($name) < 3) {
		$GLOBALS['register_error'] = $Language->getText('include_account','name_too_short');
		return 0;
	}
	if (strlen($name) > 30) {
		$GLOBALS['register_error'] = $Language->getText('include_account','name_too_long');
		return 0;
	}

	// illegal names
	if (eregi("^((root)|(bin)|(daemon)|(adm)|(lp)|(sync)|(shutdown)|(halt)|(mail)|(news)"
		. "|(uucp)|(operator)|(games)|(mysql)|(httpd)|(nobody)|(dummy)"
		. "|(www)|(cvs)|(shell)|(ftp)|(irc)|(debian)|(ns)|(download))$",$name)) {
		$GLOBALS['register_error'] = $Language->getText('include_account','reserved');
		return 0;
	}
	if (eregi("^(anoncvs_)",$name)) {
		$GLOBALS['register_error'] = $Language->getText('include_account','reserved_cvs');
		return 0;
	}
		
	return 1;
}

function account_groupnamevalid($name) {
  global $Language;
	if (!account_namevalid($name, 'project')) return 0;
	
	// illegal names
	if (eregi("^((www[0-9]?)|(cvs[0-9]?)|(shell[0-9]?)|(ftp[0-9]?)|(irc[0-9]?)|(news[0-9]?)"
		. "|(mail[0-9]?)|(ns[0-9]?)|(download[0-9]?)|(pub)|(users)|(compile)|(lists)"
		. "|(slayer)|(orbital)|(tokyojoe)|(webdev)|(projects)|(cvs)|(slayer)|(monitor)|(mirrors?))$",$name)) {
		$GLOBALS['register_error'] = $Language->getText('include_account','reserved');
		return 0;
	}

    //Group name cannot contain underscore for DNS reasons.
	if (eregi("_",$name)) {
		$GLOBALS['register_error'] = $Language->getText('include_account','dns_error');
		return 0;
	}

	return 1;
}
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
// The following is a random salt generator
function account_gensalt(){
		   

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
    $smbhash=new smbHash();
    return $smbhash->lmhash($plainpw).":".$smbhash->nthash($plainpw);
}

// returns next userid
function account_nextuid() {
	db_query("SELECT max(unix_uid) AS maxid FROM user");
	$row = db_fetch_array();
	return ($row['maxid'] + 1);
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

function account_create($loginname=''
                        ,$pw=''
                        ,$ldap_id=''
                        ,$realname=''
                        ,$register_purpose=''
                        ,$email=''
                        ,$status='P'
                        ,$confirm_hash=''
                        ,$mail_site=0
                        ,$mail_va=0
                        ,$timezone='GMT'
                        ,$lang_id=1
                        ,$unix_uid
                        ,$unix_status='N'
                        ) {
    
    global $Language;

    $result=db_query("INSERT INTO user"
                     ." SET user_name='".$loginname."'"
                     ." ,user_pw='".md5($pw)."'"
                     ." ,unix_pw='".account_genunixpw($pw)."'"
                     ." ,windows_pw='".account_genwinpw($pw)."'"
                     ." ,ldap_id='".$ldap_id."'"
                     ." ,realname=".DataAccess::quoteSmart($realname).""
                     ." ,register_purpose='".$register_purpose."'"
                     ." ,email=".DataAccess::quoteSmart($email).""
                     ." ,add_date='". time()."'"
                    . " ,last_pwd_update='".time()."'"
                     ." ,status='".$status."'"
                     ." ,confirm_hash='".$confirm_hash."'"
                     ." ,mail_siteupdates='".$mail_site."'"
                     ." ,mail_va='".$mail_va."'"
                     ." ,timezone='".$timezone."'"
                     ." ,language_id='".$lang_id."'"
                     ." ,unix_uid='".$unix_uid."'"
                     ." ,unix_status='".$unix_status."'");
    
    if (!$result) {
        exit_error($Language->getText('include_exit', 'error'), db_error());
        return 0;
    } else {
        return db_insertid($result);
    }
}

function account_redirect_after_login() {
    global $pv;  
    
    if(array_key_exists('return_to', $_REQUEST) && $_REQUEST['return_to'] != '') {
        $returnToToken = parse_url($_REQUEST['return_to']);
        if(preg_match('{/my(/|/index.php|)}i', $returnToToken['path'])) {
            util_return_to('/my/index.php');
        }
        else {
            util_return_to('/my/redirect.php');
        }
    }
    else {
        if (isset($pv) && $pv == 2) {
            util_return_to('/my/index.php?pv=2');
	} else {
	    util_return_to('/my/index.php');
        }
    }
}

?>
