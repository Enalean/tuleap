<?php

function support_data_get_categories ($group_id) {
	/*
		List of possible support_categories set up for the project
	*/
	$sql="select support_category_id,category_name from support_category WHERE group_id='$group_id'";
	return db_query($sql);
}

function support_data_get_technicians ($group_id) {
	/*
		List of people that can be assigned this support request
	*/
	$sql="SELECT user.user_id,user.user_name ".
		"FROM user,user_group ".
		"WHERE user.user_id=user_group.user_id ".
		"AND user_group.support_flags IN (1,2) ".
		"AND user_group.group_id='$group_id' ".
		"ORDER BY user.user_name";
	return db_query($sql);
}


function support_data_get_canned_responses ($group_id) {
	/*
		show defined canned responses for this project
		and the site-wide canned responses
	*/
	$sql="SELECT support_canned_id,title,body ".
		"FROM support_canned_responses ".
		"WHERE (group_id='$group_id' OR group_id='0')";
	return db_query($sql);
}

function support_data_get_statuses() {
	$sql="select * from support_status";
	return db_query($sql);
}

function support_data_get_history ($support_id) {
	$sql="select support_history.field_name,support_history.old_value,support_history.date,user.user_name ".
		"FROM support_history,user ".
		"WHERE support_history.mod_by=user.user_id ".
		"AND support_id='$support_id' ORDER BY support_history.date DESC";
	return db_query($sql);
}

function support_data_get_status_name($string) {
	/*
		simply return status_name from support_status
	*/
	$sql="select * from support_status WHERE support_status_id='$string'";
	$result=db_query($sql);
	if ($result && db_numrows($result) > 0) {
		return db_result($result,0,'status_name');
	} else {
		return 'Error - Not Found';
	}
}

function support_data_get_category_name($string) {
	/*
		simply return the category_name from support_category
	*/
	$sql="select * from support_category WHERE support_category_id='$string'";
	$result=db_query($sql);
	if ($result && db_numrows($result) > 0) {
		return db_result($result,0,'category_name');
	} else {
		return 'Error - Not Found';
	}
}

function support_data_create_message ($body,$support_id,$by) {
	/*
		handle the insertion of history for these parameters
	*/

	$sql="insert into support_messages(support_id,body,from_email,date) ".
		"VALUES ('$support_id','". htmlspecialchars($body). "','$by','".time()."')";
	return db_query($sql);
}

function support_data_add_history ($field_name,$old_value,$support_id) {
	/*
		handle the insertion of history for these parameters
	*/
	if (!user_isloggedin()) {
		$user=100;
	} else {
		$user=user_getid();
	}

	$sql="insert into support_history(support_id,field_name,old_value,mod_by,date) ".
		"VALUES ('$support_id','$field_name','$old_value','$user','".time()."')";
	return db_query($sql);
}

function support_data_get_messages ($support_id) {
	$sql="select * ".
		"FROM support_messages ".
		"WHERE support_id='$support_id' ORDER BY date DESC";
	return db_query($sql);
}

function support_data_get_original_description ($support_id) {
	$sql="select * ".
		"FROM support_messages ".
		"WHERE support_id='$support_id' ORDER BY date ASC LIMIT 1";
	return db_query($sql);
}

function support_data_create_support ($group_id,$support_category_id,$user_email,$summary,$details) {
	global $feedback;
	if (!$group_id) {
		exit_missing_param();
	}
	if (!$support_category_id) {
		$support_category_id=100;
	}

	if (!user_isloggedin()) {
		$user=100;
		if (!$user_email) {
			//force them to fill in user_email if they aren't logged in
			exit_error('ERROR','Go Back and fill in the user_email address or login so that we know what your email address is.');
		}
	} else {
		$user=user_getid();
		//use their user_name if they are logged in
		// LJ No alias on CodeX. Use real e-mail
		// LJ $user_email=user_getname().'@'.$GLOBALS['sys_users_host'];
		$user_email=user_getname($user);
	}

	if (!$group_id || !$summary || !$details) {
		exit_error('Missing Info','Go Back and fill in all the information requested');
	}

	//make sure we aren't double-submitting this code
	$res=db_query("SELECT * FROM support WHERE submitted_by='$user' AND summary='". htmlspecialchars ($summary) ."'");
	if ($res && db_numrows($res) > 0) {
		$feedback .= ' ERROR - DOUBLE SUBMISSION. You are trying to double-submit this request. Please do not double-submit requests. ';
		return 0;
	}

	//now insert the request
	$sql="INSERT INTO support (priority,close_date,group_id,support_status_id,support_category_id,submitted_by,assigned_to,open_date,summary) ".
		"VALUES ('5','0','$group_id','1','$support_category_id','$user','100','". time() ."','". htmlspecialchars($summary) ."')";

	$result=db_query($sql);
	$support_id=db_insertid($result);

	if (!$result || !$support_id) {
		exit_error('Error','Data insertion failed '.db_error());
	} else {

		$support_id=db_insertid($result);

		if ($details != '') {
			//create the first message for this ticket
			$result2= support_data_create_message($details,$support_id,$user_email);
			if (!$result2) {
				$feedback .= ' Comment Failed ';
			} else {
				$feedback .= ' Comment added to support request ';
			}
		}

		$feedback .= ' Successfully Added Support Request ';
	}
	//sorry, have to return support_id instead of $result due to weirdness in PHP/MySQL
	return $support_id;
}

function support_data_handle_update ($group_id,$support_id,$priority,
				     $support_status_id,$support_category_id,
				     $assigned_to,$summary,$canned_response,
				     $details, &$changes) {
    global $feedback;

    if (!$group_id || !$support_id || !$priority || !$support_status_id || !$support_category_id || !$assigned_to || !$summary || !$canned_response) {
	exit_missing_param();
    }

    $sql="SELECT * FROM support WHERE support_id='$support_id'";
    $result=db_query($sql);

    if (!((db_numrows($result) > 0) && (user_ismember(db_result($result,0,'group_id'),'S1')))) {
	exit_permission_denied();
    }

    if (!user_isloggedin()) {
	$user=100;
	if (!$user_email) {
	    //force them to fill in user_email if they aren't logged in
	    exit_error('ERROR','Go Back and fill in the user_email address or login so that we know what your email address is.');
	}
    } else {
	$user_email=user_getname(user_getid());
    }

    // See which fields changed during the modification
    $changes = array();
    $flist = array( "priority" => 'Priority', "support_status_id" => 'Status',
	      "support_category_id" => 'Category',"assigned_to" => 'Assigned to',
	      "summary" => 'Summary');

    reset($flist);
    while (list($field,$label) = each($flist)) {

	$new_value = $$field;
	if ($field == 'summary') 
	  $new_value = stripslashes($new_value);
	if (db_result($result,0,$field) != $new_value){

	    support_data_add_history($field,db_result($result,0,$field),$support_id);

	    if ($field == 'support_category_id') {
		$changes[$field]['del'] = support_data_get_category_name(db_result($result,0,$field));
		$changes[$field]['add'] = support_data_get_category_name($new_value);
	    } else if ($field == 'support_status_id') {
		$changes[$field]['del'] = support_data_get_status_name(db_result($result,0,$field));
		$changes[$field]['add'] = support_data_get_status_name($new_value);
	    } else if ($field == 'assigned_to') {
		$changes[$field]['del'] = user_getname(db_result($result,0,$field));
		$changes[$field]['add'] = user_getname($new_value);
	    } else {
		$changes[$field]['del'] = db_result($result,0,$field);
		$changes[$field]['add'] = $new_value;
	    }

	    $changes[$field]['label'] = $label;
	}
    }

	/*
		handle canned responses
	*/
	if ($canned_response != 100) {
		//don't care if this response is for this group - could be hacked
		$sql="SELECT * FROM support_canned_responses WHERE support_canned_id='$canned_response'";
		$result2=db_query($sql);
		if ($result2 && db_numrows($result2) > 0) {
			support_data_create_message(util_unconvert_htmlspecialchars(db_result($result2,0,'body')),$support_id,$user_email);
			$feedback .= ' Canned Response Used ';
		} else {
			$feedback .= ' Unable to Use Canned Response ';
		}
	}

	/*
		Details field is handled a little differently
	*/
	if ($details != '') {
		//create the first message for this ticket
		support_data_create_message($details,$support_id,$user_email);
		$changes['details']['add'] = stripslashes($details);
		$feedback .= ' Comment added ';
	}

	/*
		Enter the timestamp if we are changing to closed
	*/
	if ($support_status_id == "2") {
		$now=time();
		$close_date=", close_date='$now' ";
		support_data_add_history('close_date',db_result($result,0,'close_date'),$support_id);
	} else {
		$close_date='';
	}

	/*
		Finally, update the support request itself
	*/
	$sql="UPDATE support SET support_status_id='$support_status_id'$close_date, support_category_id='$support_category_id', ".
		"assigned_to='$assigned_to', priority='$priority', summary='".htmlspecialchars($summary)."' ".
		"WHERE support_id='$support_id'";

	$result=db_query($sql);

	if (!$result) {
	    exit_error('Error','UPDATE FAILED '.db_error());
	    return false;
	} else {
	    $feedback .= " Successfully Updated Support Request ";
	    return true;
	}
}

?>
