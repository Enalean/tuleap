<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// $Id$

require "pre.php";    
require ($DOCUMENT_ROOT.'/project/admin/project_admin_utils.php');
require($DOCUMENT_ROOT.'/../common/tracker/ArtifactType.class');
require($DOCUMENT_ROOT.'/../common/tracker/ArtifactTypeFactory.class');

//	  
//  get the Group object
//	  
$group = group_get_object($group_id);
if (!$group || !is_object($group) || $group->isError()) {
	exit_no_group();
}		   
$atf = new ArtifactTypeFactory($group);
if (!$group || !is_object($group) || $group->isError()) {
	exit_error('Error','Could Not Get ArtifactTypeFactory');
}
// Get the artfact type list
$at_arr = $atf->getArtifactTypes();
	
session_require(array('group'=>$group_id,'admin_flags'=>'A'));

$res_grp = db_query("SELECT * FROM groups WHERE group_id=$group_id");

//no results found
if (db_numrows($res_grp) < 1) {
	exit_error("Invalid Group","That group does not exist.");
}
$row_grp = db_fetch_array($res_grp);

// ########################### form submission, make updates
if ($submit) {
	group_add_history ('Changed Permissions','',$group_id);

	$res_dev = db_query("SELECT user_id FROM user_group WHERE group_id=$group_id");
	while ($row_dev = db_fetch_array($res_dev)) {
		//
		// cannot turn off their own admin flag if no other admin in project -- set it back to 'A'
		//
		if (user_getid() == $row_dev['user_id']) {
                    $admin_flags="admin_user_$row_dev[user_id]";
                    if ($$admin_flags != 'A') {
                        // Check that there is still at least one admin
                        $res_dev2 = db_query("SELECT user_id FROM user_group WHERE group_id=$group_id");
                        $other_admin_exists=false;
                        while ($row_dev2 = db_fetch_array($res_dev2)) {
                            // Go through all users and see if there is at least one with admin flag.
                            $flag_var="admin_user_$row_dev2[user_id]";
                            if ($$flag_var=='A') {
                                $other_admin_exists=true;
                                break;
                            }
                        }
                        if (!$other_admin_exists) {
                            $feedback .= ' Cannot remove your admin status: project needs at least one admin! ';
                            $$admin_flags='A';
                        }
                    }
		} else {
			$admin_flags="admin_user_$row_dev[user_id]";
		}
		$bug_flags="bugs_user_$row_dev[user_id]";
		$forum_flags="forums_user_$row_dev[user_id]";
		$project_flags="projects_user_$row_dev[user_id]";
		$patch_flags="patch_user_$row_dev[user_id]";
		$support_flags="support_user_$row_dev[user_id]";
		$doc_flags="doc_user_$row_dev[user_id]";

		$res = db_query('UPDATE user_group SET ' 
			."admin_flags='".$$admin_flags."',"
			."bug_flags='".$$bug_flags."',"
			."forum_flags='".$$forum_flags."',"
			."project_flags='".$$project_flags."', "
			."doc_flags='".$$doc_flags."', "
			."patch_flags='".$$patch_flags."', "
			."support_flags='".$$support_flags."' "
			."WHERE user_id='$row_dev[user_id]' AND group_id='$group_id'");

		$tracker_error = false;
		if ( $row_grp['use_trackers']&&$sys_activate_tracker&&$at_arr ) {
			for ($j = 0; $j < count($at_arr); $j++) {
				$atid = $at_arr[$j]->getID();
				$perm_level = "tracker_user_$row_dev[user_id]_$atid";
				//echo "Tracker ".$at_arr[$j]->getName()."(".$at_arr[$j]->getID()."): ".$perm_level."=".$$perm_level."<br>";
				if ( $at_arr[$j]->existUser($row_dev[user_id]) ) {
					if ( !$at_arr[$j]->updateUser($row_dev[user_id],$$perm_level) ) {
						echo $at_arr[$j]->getErrorMessage();
						$tracker_error = true;
					}
				} else {
					if ( !$at_arr[$j]->addUser($row_dev[user_id],$$perm_level) ) {
						$tracker_error = true;
					}
				}
			}
		}

		if (!$res || $tracker_error) {
			echo db_error();
			$feedback .= ' Permissions Failed For '.$row_dev['user_id'].' ';
		}
	}

	$feedback .= ' Permissions Updated ';
}

$res_dev = db_query("SELECT user.user_name AS user_name,"
	. "user.user_id AS user_id, "
	. "user_group.admin_flags, "
	. "user_group.bug_flags, "
	. "user_group.forum_flags, "
	. "user_group.project_flags, "
	. "user_group.patch_flags, "
	. "user_group.doc_flags, "
	. "user_group.support_flags "
	. "FROM user,user_group WHERE "
	. "user.user_id=user_group.user_id AND user_group.group_id=$group_id "
	. "ORDER BY user.user_name");

project_admin_header(array('title'=>'User Permissions','group'=>$group_id,
		     'help' => 'UserPermissions.html'));

$project=project_get_object($group_id);
if ($project->isError()) {
        //wasn't found or some other problem
        echo "Unable to load project object<br>";
    	return;
}
?>

<h2>User Permissions</h2>
<FORM action="userperms.php" method="post">
<INPUT type="hidden" name="group_id" value="<?php print $group_id; ?>">
<TABLE width="100%" cellspacing=0 cellpadding=3 border=0>
<TR><TD><B>Developer Name</B></TD>
<TD><B>Project<BR>Admin</B></TD>
<TD><B>CVS Write</B></TD>
<?
if ($project->usesBugs() && !($sys_activate_tracker && !$project->activateOldBug())) {
	print '<TD><B>Bug Tracking</B></TD>';
}
?>
<TD><B>Forums</B></TD>
<?
if ($project->usesPm() && !($sys_activate_tracker && !$project->activateOldTask())) {
	print '<TD><B>Task Manager</B></TD>';
}
?>
<TD><B>Patch Manager</B></TD>
<?
if ($project->usesSupport() && !($sys_activate_tracker && !$project->activateOldSR())) {
	print '<TD><B>Support Manager</B></TD>';
}
?>
<TD><B>Doc. Manager</B></TD>
<?
if ( $row_grp['use_trackers']&&$sys_activate_tracker&&$at_arr ) {
	for ($j = 0; $j < count($at_arr); $j++) {
		echo '<TD><B>Tracker:<br>'.$at_arr[$j]->getName().'</B></TD>';
	}
}
?>
</TR>

<?php

if (!$res_dev || db_numrows($res_dev) < 1) {
	echo '<H2>No Users Found</H2>';
} else {

	while ($row_dev = db_fetch_array($res_dev)) {
		$i++;
		print '<TR class="'. util_get_alt_row_color($i) .'"><TD>'.$row_dev['user_name'].'</TD>';
		print '
			<TD>
			<INPUT TYPE="RADIO" NAME="admin_user_'.$row_dev['user_id'].'" VALUE="A" '.(($row_dev['admin_flags']=='A')?'CHECKED':'').'>&nbsp;Yes<BR>
			<INPUT TYPE="RADIO" NAME="admin_user_'.$row_dev['user_id'].'" VALUE="" '.(($row_dev['admin_flags']=='')?'CHECKED':'').'>&nbsp;No
			</TD>';
		print '<TD>Yes</TD>';
		// bug selects
        if ($project->usesBugs() && !($sys_activate_tracker && !$project->activateOldBug())) {
			print '<TD><FONT size="-1"><SELECT name="bugs_user_'.$row_dev['user_id'].'">';
			print '<OPTION value="0"'.(($row_dev['bug_flags']==0)?" selected":"").'>None';
			print '<OPTION value="1"'.(($row_dev['bug_flags']==1)?" selected":"").'>Tech Only';
			print '<OPTION value="2"'.(($row_dev['bug_flags']==2)?" selected":"").'>Tech & Admin';
			print '<OPTION value="3"'.(($row_dev['bug_flags']==3)?" selected":"").'>Admin Only';
			print '</SELECT></FONT></TD>';
		} else {
			print '<input type="Hidden" name="bugs_user_'.$row_dev['user_id'].'" value="'.$row_dev['bug_flags'].'">';
		}
		// forums
		print '<TD><FONT size="-1"><SELECT name="forums_user_'.$row_dev['user_id'].'">';
		print '<OPTION value="0"'.(($row_dev['forum_flags']==0)?" selected":"").'>None';
		print '<OPTION value="2"'.(($row_dev['forum_flags']==2)?" selected":"").'>Moderator';
		print '</SELECT></FONT></TD>';
		
		// project selects
        if ($project->usesPm() && !($sys_activate_tracker && !$project->activateOldTask())) {
			print '<TD><FONT size="-1"><SELECT name="projects_user_'.$row_dev['user_id'].'">';
			print '<OPTION value="0"'.(($row_dev['project_flags']==0)?" selected":"").'>None';
			print '<OPTION value="1"'.(($row_dev['project_flags']==1)?" selected":"").'>Tech Only';
			print '<OPTION value="2"'.(($row_dev['project_flags']==2)?" selected":"").'>Tech & Admin';
			print '<OPTION value="3"'.(($row_dev['project_flags']==3)?" selected":"").'>Admin Only';
			print '</SELECT></FONT></TD>';
		} else {
			print '<input type="Hidden" name="projects_user_'.$row_dev['user_id'].'" value="'.$row_dev['project_flags'].'">';
		}
		
	    // patch selects
	    print '<TD><FONT size="-1"><SELECT name="patch_user_'.$row_dev['user_id'].'">';
	    print '<OPTION value="0"'.(($row_dev['patch_flags']==0)?" selected":"").'>None';
	    print '<OPTION value="1"'.(($row_dev['patch_flags']==1)?" selected":"").'>Tech Only';
	    print '<OPTION value="2"'.(($row_dev['patch_flags']==2)?" selected":"").'>Tech & Admin';
	    print '<OPTION value="3"'.(($row_dev['patch_flags']==3)?" selected":"").'>Admin Only';
	    print '</SELECT></FONT></TD>';
	
		// support selects
        if ($project->usesSupport() && !($sys_activate_tracker && !$project->activateOldSR())) {
			print '<TD><FONT size="-1"><SELECT name="support_user_'.$row_dev['user_id'].'">';
			print '<OPTION value="0"'.(($row_dev['support_flags']==0)?" selected":"").'>None';
			print '<OPTION value="1"'.(($row_dev['support_flags']==1)?" selected":"").'>Tech Only';
			print '<OPTION value="2"'.(($row_dev['support_flags']==2)?" selected":"").'>Tech & Admin';
			print '<OPTION value="3"'.(($row_dev['support_flags']==3)?" selected":"").'>Admin Only';
			print '</SELECT></FONT></TD>';
		} else {
			print '<input type="Hidden" name="support_user_'.$row_dev['user_id'].'" value="'.$row_dev['support_flags'].'">';
		}
	
		//documenation states - nothing or editor	
		print '<TD><FONT size="-1"><SELECT name="doc_user_'.$row_dev['user_id'].'">';
		print '<OPTION value="0"'.(($row_dev['doc_flags']==0)?" selected":"").'>None';
		print '<OPTION value="1"'.(($row_dev['doc_flags']==1)?" selected":"").'>Editor';
		print '</SELECT></FONT></TD>
	';
	
		if ( $row_grp['use_trackers']&&$sys_activate_tracker&&$at_arr ) {
			// Loop on tracker
			for ($j = 0; $j < count($at_arr); $j++) {
				$perm = $at_arr[$j]->getUserPerm($row_dev['user_id']);
				print '<TD><FONT size="-1"><SELECT name="tracker_user_'.$row_dev['user_id'].'_'.$at_arr[$j]->getID().'">';
				print '<OPTION value="0"'.(($perm==0)?" selected":"").'>None';
				print '<OPTION value="1"'.(($perm==1)?" selected":"").'>Tech Only';
				print '<OPTION value="2"'.(($perm==2)?" selected":"").'>Tech & Admin';
				print '<OPTION value="3"'.(($perm==3)?" selected":"").'>Admin Only';
				print '</SELECT></FONT></TD>';
			}
		}
		print '</TR>
	';
	} // while

}
?>

</TABLE>
<P align="center"><INPUT type="submit" name="submit" value="Update User Permissions">
</FORM>

<?php
project_admin_footer(array());
?>
