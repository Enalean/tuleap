<?php
//
// CodeX: Breaking Down the Barriers to Source Code Sharing inside Xerox
// Copyright (c) Xerox Corporation, CodeX/CodeX Team, 2002 All Rights Reserved
// http://codex.xerox.com
//
// $Id$

require_once('pre.php');
require('../pm_data.php');
require('../pm_utils.php');
require_once('www/project/admin/project_admin_utils.php');

$is_admin_page='y';

/*  ==================================================
    Check access permission
 ================================================== */
if (!$group_id) {
    exit_no_group(); // need a group_id !!!
}

if (!user_isloggedin()) {
    // Must be at least logged in to set up your personal notification
    // preference
    exit_permission_denied();
}
$is_user_a_member = user_ismember($group_id);

/*  ==================================================
    Set up some data structure needed throughout the script
 ================================================== */

$user_id = user_getid();
// get notification roles
$res_roles = pm_data_get_notification_roles();
$num_roles = db_numrows($res_roles);
$i=0;
while ($arr = db_fetch_array($res_roles)) {
    $arr_roles[$i] = $arr; $i++;
}

// get notification events
$res_events = pm_data_get_notification_events();
$num_events = db_numrows($res_events);
$i=0;
while ($arr = db_fetch_array($res_events)) {
    $arr_events[$i] = $arr; $i++;
}

// build the default notif settings in case the user has not yet defined her own
// By default it's all 'yes'
for ($i=0; $i<$num_roles; $i++) {
    $role_id = $arr_roles[$i]['role_id'];
    for ($j=0; $j<$num_events; $j++) {
	$event_id = $arr_events[$j]['event_id'];
	$arr_notif[$role_id][$event_id] = 1;
    }
}

// Overwrite with user settings if any
$res_notif = pm_data_get_notification($user_id);
while ($arr = db_fetch_array($res_notif)) {
    $arr_notif[$arr['role_id']][$arr['event_id']] = $arr['notify'];
}

/*  ==================================================
    The form has been submitted - update the database
 ================================================== */

if ($submit) {

    // email adresses for new tasks
    $res_new=db_query('UPDATE groups SET '
	."send_all_tasks='$send_all_tasks', "
	."new_task_address=".($new_task_address? "'$new_task_address' " : "''")
	." WHERE group_id=$group_id");

    // Users to watch
    $res_watch = true;
    if ($watchees) {
	$watchees = preg_replace("/\s+/","",$watchees);
 	$arr_user_names = split(',',$watchees);
	$arr_user_ids = array();
	while (list(,$user_name) = each($arr_user_names)) {
	    $res = user_get_result_set_from_unix($user_name);
	    if (!$res || (db_numrows($res) <= 0)) {
		// user doesn;t exist  so abort this step and give feedback
		$feedback .= " - Error Invalid user name '$user_name'";
		$res_watch = false;
		continue;
	    } else {
		// store in a hash to eliminate duplicates. skip user itself
		if (db_result($res,0,'user_id') != $user_id)
		    $arr_user_ids[db_result($res,0,'user_id')] = 1;
	    }
	}

	if ($res_watch) {
	    pm_data_delete_watchees($user_id); 
	    $res_watch = pm_data_insert_watchees($user_id, array_keys($arr_user_ids));
	}   
		
    } else {
	    pm_data_delete_watchees($user_id); 
    }


    // Event/Role specific settings
    for ($i=0; $i<$num_roles; $i++) {
	$role_id = $arr_roles[$i]['role_id'];
	for ($j=0; $j<$num_events; $j++) {
	    $event_id = $arr_events[$j]['event_id'];
	    $cbox_name = 'cb-'.$role_id.'-'.$event_id;
	    //echo "DBG $cbox_name -> '".$$cbox_name."'<br>";
	    $arr_notif[$role_id][$event_id] = ( $$cbox_name ? 1 : 0);
	}
    }
    pm_data_delete_notification($user_id);
    $res_notif = pm_data_insert_notification($user_id, $arr_roles, $arr_events, $arr_notif);

    // Give Feedback
    if ($res_watch && $res_notif && $res_new) {
	$feedback .= ' - Successful Update';
	group_add_history ('Changed Personal Notification Email Settings','',$group_id);
    } else {
	$feedback .= ' - Update Failed'.db_error();;
    }

} // end submit



/*  ==================================================
    Show Main Page
 ================================================== */

pm_header_admin(
       array ('title'=>'Task Administration - Personal Email Notification Settings',
	      'help' => 'BTSAdministration.html#BugEmailNotificationSettings'));

echo '<H2>Email Notification Settings</h2>';

// Get group information bur new task notification settings
$res_grp = db_query("SELECT * FROM groups WHERE group_id=$group_id");
if (db_numrows($res_grp) < 1) {
    exit_no_group();
}
$row_grp = db_fetch_array($res_grp);

// Build Wachees UI
$res = pm_data_get_watchees($user_id);
$arr_watchees = array();
while ($row_watchee = db_fetch_array($res)) {
    $arr_watchees[] = user_getname($row_watchee['watchee_id']);
}
$watchees = join(',',$arr_watchees);

echo '
<FORM action="'.$PHP_SELF.'" method="post">
<INPUT type="hidden" name="group_id" value="'.$group_id.'">';

echo '<h3><a name="GlobalEmailNotification"></a>Global Email Notification '.
help_button('TaskManagerAdministration.html#TaskEmailNotificationSettings').'</h3>';

if (user_ismember($group_id,'A')) {
    echo '
              <P><B>As a project administrator</B> you can provide email addresses (comma separated) to which new Task submissions (and possibly updates) will be systematically sent.<BR>
	<BR><INPUT TYPE="TEXT" NAME="new_task_address" VALUE="'.$row_grp['new_task_address'].'" SIZE="55"> 
	&nbsp;&nbsp;&nbsp;(send on all updates) <INPUT TYPE="CHECKBOX" NAME="send_all_tasks" VALUE="1" '. (($row_grp['send_all_tasks'])?'CHECKED':'') .'><BR><br>';
} else {
    if ($row_grp['new_task_address'])
	echo '
              <P>The project administrator have configured the following email addresses to receive
notification of new task submission (and possibly updates).<P><u>Recipients</u>: '.
	    $row_grp['new_task_address'].'
	&nbsp;&nbsp;&nbsp; (Send on all updates: '.(($row_grp['send_all_tasks'])?'Yes':'No').')<p>';
    else
	echo '
                <P>The project administrator hasn\'t yet specified email addresses that will systematically receive
 email notification of new tasks.<P>';
}
 

echo '<h3>Personal Email Notification</h3>';
  
if (user_ismember($group_id,'B1') || user_ismember($group_id,'B2') ||user_ismember($group_id,'A')) {
    // To watch other users you must have at least tech rights on
    echo'
<h4>Users To Watch '.
help_button('TaskManagerAdministration.html#TaskEmailNotificationSettings').'</h4>
<P>If you want to be the backup of someone when they\'re away from the office, or if you need to do the QA to all their tasks. '.$GLOBALS['sys_name'].' can send their email notification to you also. List the login name of any user you wish to watch, separated by commas.</b>
<p><INPUT TYPE="TEXT" NAME="watchees" VALUE="'.$watchees.'" SIZE="55" MAXLENGTH="255"><br></p>
';

    $res = pm_data_get_watchers($user_id);
    $arr_watchers = array();
    while ($row_watcher = db_fetch_array($res)) {
	$watcher_name = user_getname($row_watcher['user_id']);
	$watchers .= '<a href="/users/'.$watcher_name.'">'.$watcher_name.'</a>,';
    }
    $watchers = substr($watchers,0,-1); // remove extra comma at the end
    
    if ($watchers) {
	echo "<p>For your information your own task notifications are currently watched by: <u>$watchers</u>";
    } else {
	echo "<p>For your information <u>nobody</u> is currently watching your own task notifications ";
    }
    echo '<br><br>';
}

// Build Role/Event table 
// Rk: Can't use html_build_list_table_top because of the specific layout
echo '<h4>Event/Role Specific Settings '.
help_button('TaskManagerAdministration.html#TaskEmailNotificationSettings').'</h4>
              <P>You can tune your notification settings and decide what task changes you
want to be aware of depending on your role. <p>
<b><u>Note</u></b>: Notification of *new* task submission to people other than the assignee
 or the submitter can be configured by the project administrator in the <a href="#GlobalEmailNotification">Global Email Notification section</a> above.<p>';

echo '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
<table BORDER="0" CELLSPACING="1" CELLPADDING="2" class="small">
<tr class="boxtitle">
    <td colspan="'.$num_roles.'" align="center" width="50%"><b>If my role in a task is:</b></td>
    <td rowspan="2" width="50%"><b>&nbsp;&nbsp;&nbsp;I want to be notified when:</b></td>
</tr>';

for ($i=0; $i<$num_roles; $i++) {
    echo '<td align="center" width="10%"><b>'.$arr_roles[$i]['short_description']."</b></td>\n";
}
echo "</tr>\n";

for ($j=0; $j<$num_events; $j++) {
    $event_id = $arr_events[$j]['event_id'];
    $event_label = $arr_events[$j]['event_label'];
    echo '<tr class="'.util_get_alt_row_color($j)."\">\n";
    for ($i=0; $i<$num_roles; $i++) {
	$role_id = $arr_roles[$i]['role_id'];
	$role_label = $arr_roles[$i]['role_label'];
	$cbox_name = 'cb-'.$role_id.'-'.$event_id;
	if ( (($role_label == 'ASSIGNEE') && !$is_user_a_member) ||
	     (($event_label == 'NEW_TASK') && ($role_label != 'ASSIGNEE') && ($role_label != 'SUBMITTER')) ) {
	    // if the user is not a member then the ASSIGNEE column cannot
	    // be set. If it's not an assignee or a submitter the new_task event is meaningless
	    echo '   <td align="center"><input type="hidden" name="'.$cbox_name.'" value="1">-</td>'."\n";
	} else {
	    echo '   <td align="center"><input type="checkbox" name="'.$cbox_name.'" value="1" '.
		($arr_notif[$role_id][$event_id] ? 'checked':'')."></td>\n";
	}
    }
    echo '   <td>&nbsp;&nbsp;&nbsp;'.$arr_events[$j]['description']."</td>\n";
    echo "</tr>\n";
}

echo'
</table>

<HR>
<P align="center"><INPUT type="submit" name="submit" value="Submit Changes">
</FORM>';

pm_footer(array());



?>
