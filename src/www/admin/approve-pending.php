<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// 

require_once('pre.php');    
require_once('vars.php');
require_once('account.php');
require_once('proj_email.php');
require_once('www/admin/admin_utils.php');
require_once('www/project/admin/project_admin_utils.php');

$Language->loadLanguageMsg('admin/admin');

session_require(array('group'=>'1','admin_flags'=>'A'));

$action = '';
if (isset($_REQUEST['action'])) {
    $action = $_REQUEST['action'];
}

// group public choice
if ($action=='activate') {
	/*
		update the project flag to active
	*/
	db_query("UPDATE groups SET status='A'"
		. " WHERE group_id IN ($list_of_groups)");


	/*
		now activate the admin's unix account if it isn't already
	*/
	//list of user_id's for these admins
	$admin_res=db_query("SELECT DISTINCT user.user_id FROM user,user_group ".
		"WHERE user_group.group_id IN ($list_of_groups) ".
		"AND user_group.admin_flags='A' ".
		"AND user.unix_status='N' ".
		"AND user.user_id=user_group.user_id ".
		"AND user.unix_uid='0'");

	if (db_numrows($admin_res) > 0) {

		$admin_list=result_column_to_array($admin_res,0);
		$count=count($admin_list);

		for ($i=0; $i<$count; $i++) {
			$res_user=db_query("UPDATE user SET unix_uid='" . account_nextuid() . "',unix_status='A' WHERE user_id='$admin_list[$i]'");
			if (!$res_user || db_affected_rows($res_user) < 1) {
				echo db_error();
			}
		}
	} else {
		echo db_error();
	}

	/*
		Now send the project approval emails
	*/
	$groups=explode(',',$list_of_groups);
	$count=count($groups);
    for ($i=0; $i<$count; $i++) {
		group_add_history ('approved','x',$groups[$i]);
		if (!send_new_project_email($groups[$i])) {
            $group = group_get_object($groups[$i]);
            if ($group && is_object($group) && !$group->isError()) {
                $GLOBALS['feedback'] .= "<p>".$group->getPublicName()." - ".$GLOBALS['Language']->getText('global', 'mail_failed', array($GLOBALS['sys_email_admin']))."</p>";
            }
        }
		usleep(250000);
	}

} else if ($action=='delete') {
	group_add_history ('deleted','x',$group_id);
	db_query("UPDATE groups SET status='D'"
		. " WHERE group_id='$group_id'");
}


// get current information
$res_grp = db_query("SELECT * FROM groups WHERE status='P'");

if (db_numrows($res_grp) < 1) {
    site_admin_header(array('title'=>$Language->getText('admin_approve_pending','no_pending')));
    echo $Language->getText('admin_approve_pending','no_pending');
} else {
    site_admin_header(array('title'=>$Language->getText('admin_approve_pending','title')));
    
    while ($row_grp = db_fetch_array($res_grp)) {
    
        ?>
        <fieldset>
            <legend style="font-size:1.3em; font-weight: bold;"><?php echo $row_grp['group_name']; ?></legend>
        
<?php
        $group = new Group($row_grp['group_id']);
        $members_id = $group->getMembersId();
        if (count($members_id) > 0) {
            $admin_id = $members_id[0]; // the first (and normally the only one) is the project creator)
            $admin = new User($admin_id);
            if ($admin->getID() != 0) {
                $project_date_creation = util_timestamp_to_userdateformat($group->getStartDate());
                // Display the project admin (the project creator) and the creation date
                echo $Language->getText('admin_approve_pending', 'creator_and_creation_date', array($admin->getID(), $admin->getName(), $project_date_creation));
            }
        }
?>
    
        <p>
        <A href="/admin/groupedit.php?group_id=<?php echo $row_grp['group_id']; ?>"><b><?php echo $Language->getText('admin_groupedit','proj_edit'); ?></b></A> | 
        <A href="/project/admin/?group_id=<?php echo $row_grp['group_id']; ?>"><b><?php echo $Language->getText('admin_groupedit','proj_admin'); ?></b></A> | 
        <A href="userlist.php?group_id=<?php print $row_grp['group_id']; ?>"><b><?php echo $Language->getText('admin_groupedit','proj_member'); ?></b></A>
    
        <p>

        <B><?php 
        // Get project type label
        $template =& TemplateSingleton::instance(); 
        echo $Language->getText('admin_groupedit','group_type'); ?>: <?php echo $template->getLabel($row_grp['type']); ?></B>
        <BR><B><?php echo $Language->getText('admin_groupedit','license'); ?>: <?php echo $row_grp['license']; ?></B>
        <BR><B><?php echo $Language->getText('admin_groupedit','home_box'); ?>: <?php print $row_grp['unix_box']; ?></B>
        <BR><B><?php echo $Language->getText('admin_groupedit','http_domain'); ?>: <?php print $row_grp['http_domain']; ?></B>
    
        <br>
        &nbsp;
        <?php
        $res_cat = db_query("SELECT category.category_id AS category_id,"
            . "category.category_name AS category_name FROM category,group_category "
            . "WHERE category.category_id=group_category.category_id AND "
            . "group_category.group_id=$row_grp[group_id]");
        while ($row_cat = db_fetch_array($res_cat)) {
            print "<br>$row_cat[category_name] "
            . '<A href="groupedit.php?group_id='. $row_grp['group_id'] 
            .'&amp;group_idrm='. $row_grp['group_id'] 
            .'&amp;form_catrm='. $row_cat['category_id'] .'">'
            . "[".$Language->getText('admin_approve_pending','remove_category')."]</A>";
        }
    
        // ########################## OTHER INFO
    
        print "<P><B>".$Language->getText('admin_groupedit','other_info')."</B>";
        print "<br><u>".$Language->getText('admin_groupedit','unix_grp')."</u>: $row_grp[unix_group_name]";
    
        print "<br><u>".$Language->getText('admin_groupedit','description')."</u>:<br> $row_grp[register_purpose]";
    
        print "<br><u>".$Language->getText('admin_groupedit','license_other')."</u>: <br> $row_grp[license_other]";
        
        $sf =& new ServerFactory();
        if (count($sf->getAllServers()) > 1) {
            $p =& project_get_object($row_grp['group_id']);
            if ($p->usesFile() || $p->usesSVN()) {
                print '<br><u>'. $Language->getText('admin_approve_pending','distributed_services') .'</u>:<br><ul>';
                if ($p->usesFile()) {
                    $s =& $sf->getServerById($p->services['file']->getServerId());
                    print '<li>'. $Language->getText('project_admin_editservice', 'service_file_lbl_key') .': '. $s->getName() .'</li>';
                }
                if ($p->usesSVN()) {
                    print '<li>'. $Language->getText('project_admin_editservice', 'service_svn_lbl_key') .': '. $s->getName() .'</li>';
                }
                print '</ul>';
            }
        }
        ?>
                    <TABLE WIDTH="70%">
            <TR>
            <TD style="text-align:center">
        <FORM action="<?php echo $PHP_SELF; ?>" method="POST">
        <INPUT TYPE="HIDDEN" NAME="action" VALUE="activate">
        <INPUT TYPE="HIDDEN" NAME="list_of_groups" VALUE="<?php print $row_grp['group_id']; ?>">
        <INPUT type="submit" name="submit" value="<?php echo $Language->getText('admin_approve_pending','approve'); ?>">
        </FORM>
        </TD>
    
            <TD> 
        <FORM action="<?php echo $PHP_SELF; ?>" method="POST">
        <INPUT TYPE="HIDDEN" NAME="action" VALUE="delete">
        <INPUT TYPE="HIDDEN" NAME="group_id" VALUE="<?php print $row_grp['group_id']; ?>">
        <INPUT type="submit" name="submit" value="<?php echo $Language->getText('admin_approve_pending','delete'); ?>">
        </FORM>
            </TD>
            </TR>
            </TABLE>
        </fieldset><br />
        <?php
    
    }
    
    //list of group_id's of pending projects
    $arr=result_column_to_array($res_grp,0);
    $group_list=implode($arr,',');
    
    echo '
        <CENTER>
        <FORM action="'.$PHP_SELF.'" method="POST">
        <INPUT TYPE="HIDDEN" NAME="action" VALUE="activate">
        <INPUT TYPE="HIDDEN" NAME="list_of_groups" VALUE="'.$group_list.'">
        <INPUT type="submit" name="submit" value="'.$Language->getText('admin_approve_pending','approve_all').'">
        </FORM>
        </center>
        ';
}
site_admin_footer(array());

?>
