<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// 

require_once('pre.php');    
require_once('account.php');
require_once('www/project/admin/ugroup_utils.php');
require_once('common/event/EventManager.class.php');
require_once('common/dao/UserDao.class.php');


session_require(array('group'=>'1','admin_flags'=>'A'));

$HTML->header(array('title'=>$Language->getText('admin_userlist','title')));

function show_users_list ($res, $offset, $limit, $user_name_search="") {
    $result = $res['users'];
    $hp = Codendi_HTMLPurifier::instance();
    global $Language;
    echo '<P>'.$Language->getText('admin_userlist','legend').'</P>
          <TABLE width=100% cellspacing=0 cellpadding=0 BORDER="1">';
    $odd_even = array('boxitem', 'boxitemalt');
    if ($user_name_search != "") {
        $user_name_param="&user_name_search=$user_name_search";
    } else {
        $user_name_param="";
    }

    $i = 0;
    echo "<tr><th>".$Language->getText('include_user_home','login_name')."</th>";
    echo "<th>".$Language->getText('include_user_home','real_name')."</th>";
    echo "<th>Profile</th>\n";
    echo "<th>".$Language->getText('admin_userlist','status')."</th>";
    
    if ($res['numrows'] > 0) {
        while ($usr = db_fetch_array($result)) {
            switch ($usr['status']) {
                case User::STATUS_ACTIVE:
                    $status = $Language->getText('admin_userlist','active');
                    $name   = '<strong>'.$usr['user_name'].'</strong>';
                    break;
                case User::STATUS_RESTRICTED:
                    $status = $Language->getText('admin_userlist','restricted');
                    $name   = '<em>'.$usr['user_name'].'</em>';
                    break;
                case User::STATUS_DELETED:
                    $status = $Language->getText('admin_userlist','deleted');
                    $name   = '<i>'.$usr['user_name'].'</i>';
                    break;
                case User::STATUS_SUSPENDED:
                    $status = $Language->getText('admin_userlist','suspended');
                    $name   = $usr['user_name'];
                    break;
                case User::STATUS_PENDING:
                    $status = $Language->getText('admin_userlist','pending');
                    $name   = '* '.$usr['user_name'];
                    break;
                case User::STATUS_VALIDATED:
                    $status = $Language->getText('admin_userlist','validated');
                    $name   = '(v) '.$usr['user_name'];
                    break;
                case User::STATUS_VALIDATED_RESTRICTED:
                    $status = $Language->getText('admin_userlist','validated_restricted');
                    $name   = '(vr) '.$usr['user_name'];
                    break;
            }
            echo "\n<TR class=\"". $odd_even[$i++ % count($odd_even)] ."\">";
            echo "\n<TD><a href=\"usergroup.php?user_id=".$usr['user_id']."\">".$name."</a></TD>";
            echo "\n<TD>". $hp->purify($usr['realname'], CODENDI_PURIFIER_CONVERT_HTML) ."</TD>";
            echo "\n<TD><A HREF=\"/users/".$usr['user_name']."/\">[DevProfile]</A></TD>";
            echo "\n<TD><span class=\"site_admin_user_status_".$usr['status']."\">&nbsp;</span>".$status."</TD>";
            echo "\n</TR>";
        }
    }
    echo "</TABLE>";
    echo '<div style="text-align:center" class="'. util_get_alt_row_color($i++) .'">';

    if ($offset > 0) {
        echo  '<a href="?offset='.($offset-$limit).$user_name_param.'">[ '.$Language->getText('project_admin_utils', 'previous').'  ]</a>';
        echo '&nbsp;';
    }
    if (($offset + $limit) < $res['numrows']) {
        echo '&nbsp;';
        echo '<a href="?offset='.($offset+$limit).$user_name_param.'">[ '.$Language->getText('project_admin_utils', 'next').' ]</a>';
    }
    echo '</div>';
    echo '<div style="text-align:center" class="'. util_get_alt_row_color($i++) .'">';
    echo ($offset+$i-2).'/'.$res['numrows'];
    echo '</div>';
}
// Administrative functions
if (!isset($action)) {
    $action = '';
 }

/*
	Add a user to this group
*/
if ($action=='add_to_group') {
    // Get user unix name
    $um = UserManager::instance();
    $res_newuser  = $um->getUserById($user_id);
    if ($res_newuser->getID() != 0) {
        $user_name = $res_newuser->getUserName();
        if (!account_add_user_to_group($group_id,$user_name)) {
            $feedback .= ' '.$Language->getText('admin_userlist','error_noadd');
        }
    } else {
	$feedback .= ' '.$Language->getText('admin_userlist','error_uid',array($user_id));
    }
}

/*
	Show list of users
*/
print "<p>".$Language->getText('admin_userlist','user_list').":  ";

$dao = new UserDao(CodendiDataAccess::instance());
$offset = $request->getValidated('offset', 'uint', 0);
if ( !$offset || $offset < 0 ) {
    $offset = 0;
}
$limit  = 100;

$vUserNameSearch = new Valid_String('user_name_search');
if($request->valid($vUserNameSearch)) {
    if ($request->exist('user_name_search'))
        $user_name_search = $request->get('user_name_search');
}

// Check if group_id is valid
$vGroupId = new Valid_GroupId();
if($request->valid($vGroupId)) {
    if ($request->exist('group_id')) {
        $group_id = $request->get('group_id');
    }
}

if (!$group_id) {
	print "<b>".$Language->getText('admin_userlist','all_groups')."</b>";
	print "\n<p>";
    

 
    if (isset($user_name_search) && $user_name_search) {
    $result = $dao->listAllUsers($user_name_search, $offset, $limit);
    
    } else {
        $user_name_search="";
        $result = $dao->listAllUsers(0, $offset, $limit);
    }
    show_users_list ($result, $offset, $limit, $user_name_search);
} else {
	/*
		Show list for one group
	*/
    $pm = ProjectManager::instance();
    print "<b>Group ".$Language->getText('admin_userlist','group',array($pm->getProject($group_id)->getPublicName()))."</b>";
	
	print "\n<p>";
    $result = $dao->listAllUsersForGroup($group_id, $offset, $limit);
    show_users_list ($result, $offset, $limit);

	/*
        	Show a form so a user can be added to this group
	*/
	?>
	<hr>
	<P>
	<form action="?" method="post">
	<input type="HIDDEN" name="action" VALUE="add_to_group">
	<p><?php echo $Language->getText('admin_userlist','uid_toadd'); ?>:&nbsp;
	<input name="user_id" type="TEXT" value="">
	<br>
	<input type="HIDDEN" name="group_id" VALUE="<?php print $group_id; ?>">
	<p>
	<input type="submit" name="Submit" value="<?php echo $Language->getText('global','btn_submit'); ?>">
	</form>

	<?php	
}

$HTML->footer(array());

?>
