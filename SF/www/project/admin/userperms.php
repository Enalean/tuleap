<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// $Id$

require_once('pre.php');    
require_once('www/project/admin/project_admin_utils.php');
require_once('common/tracker/ArtifactType.class');
require_once('common/tracker/ArtifactTypeFactory.class');
require_once('www/project/admin/ugroup_utils.php');

$Language->loadLanguageMsg('project/project');

//	  
//  get the Group object
//	  
$group = group_get_object($group_id);
if (!$group || !is_object($group) || $group->isError()) {
	exit_no_group();
}		   
$atf = new ArtifactTypeFactory($group);
if (!$group || !is_object($group) || $group->isError()) {
	exit_error($Language->getText('global','error'),$Language->getText('project_admin_index','not_get_atf'));
}
// Get the artfact type list
$at_arr = $atf->getArtifactTypes();
	
session_require(array('group'=>$group_id,'admin_flags'=>'A'));

$res_grp = db_query("SELECT * FROM groups WHERE group_id=$group_id");

//no results found
if (db_numrows($res_grp) < 1) {
	exit_error($Language->getText('project_admin_userperms','invalid_g'),$Language->getText('project_admin_userperms','group_not_exist'));
}
$project=project_get_object($group_id);
if ($project->isError()) {
        //wasn't found or some other problem
        echo $Language->getText('project_admin_userperms','unable_load_p')."<br>";
    	return;
}
$row_grp = db_fetch_array($res_grp);

// ########################### form submission, make updates
if ($submit) {
    group_add_history ($Language->getText('project_admin_userperms','changed_member_perm'),'',$group_id); // Used to be 'Changed Permissions'

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
                            $feedback .= ' '.$Language->getText('project_admin_userperms','cannot_remove_admin_stat').' ';
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
		$file_flags="file_user_$row_dev[user_id]";
		$wiki_flags="wiki_user_$row_dev[user_id]";

		$res = db_query('UPDATE user_group SET ' 
			."admin_flags='".$$admin_flags."',"
			."bug_flags='".$$bug_flags."',"
			."forum_flags='".$$forum_flags."',"
			."project_flags='".$$project_flags."', "
			."doc_flags='".$$doc_flags."', "
			."file_flags='".$$file_flags."', "
			."patch_flags='".$$patch_flags."', "
			."wiki_flags='".$$wiki_flags."', "
			."support_flags='".$$support_flags."' "
			."WHERE user_id='$row_dev[user_id]' AND group_id='$group_id'");

		$tracker_error = false;
		if ( $project->usesTracker()&&$at_arr ) {
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
			$feedback .= ' '.$Language->getText('project_admin_userperms','perm_fail_for',$row_dev['user_id']).' ';
		}
	}

	$feedback .= ' '.$Language->getText('project_admin_userperms','perm_upd').' ';
}

$res_dev = db_query("SELECT user.user_name AS user_name,"
	. "user.user_id AS user_id, "
	. "user_group.admin_flags, "
	. "user_group.bug_flags, "
	. "user_group.forum_flags, "
	. "user_group.project_flags, "
	. "user_group.patch_flags, "
	. "user_group.doc_flags, "
	. "user_group.file_flags, "
	. "user_group.support_flags, "
        . "user_group.wiki_flags "
	. "FROM user,user_group WHERE "
	. "user.user_id=user_group.user_id AND user_group.group_id=$group_id "
	. "ORDER BY user.user_name");

project_admin_header(array('title'=>$Language->getText('project_admin_utils','user_perms'),'group'=>$group_id,
		     'help' => 'UserPermissions.html'));

/*$project=project_get_object($group_id);
if ($project->isError()) {
        //wasn't found or some other problem
        echo "Unable to load project object<br>";
    	return;
}
*/

echo '
<h2>'.$Language->getText('project_admin_utils','user_perms').'</h2>
<FORM action="userperms.php" method="post">
<INPUT type="hidden" name="group_id" value="'.$group_id.'">
<TABLE width="100%" cellspacing=0 cellpadding=3 border=0>
<TR><TD><B>'.$Language->getText('project_admin_userperms','devel_name').'</B></TD>
<TD><B>'.$Language->getText('project_admin_userperms','proj_admin').'</B></TD>';

if ($project->usesCVS()) {
    print '<TD><B>'.$Language->getText('project_admin_userperms','cvs_write').'</B></TD>';
}
if ($project->usesBugs()) {
	print '<TD><B>'.$Language->getText('project_admin_userperms','bug_track').'</B></TD>';
}
if ($project->usesForum()) {
    print '<TD><B>'.$Language->getText('project_admin_userperms','forums').'</B></TD>';
}
if ($project->usesWiki()) {
    print '<TD><B>Wiki</B></TD>';//XXX
}
if ($project->usesPm()) {
	print '<TD><B>'.$Language->getText('project_admin_userperms','task_man').'</B></TD>';
}

if ($project->usesPatch()) {
    print '<TD><B>'.$Language->getText('project_admin_userperms','patch_man').'</B></TD>';
}
if ($project->usesSupport()) {
	print '<TD><B>'.$Language->getText('project_admin_userperms','supp_man').'</B></TD>';
}

if ($project->usesDocman()) {
    print '<TD><B>'.$Language->getText('project_admin_userperms','doc_man').'</B></TD>';
}

if ($project->usesFile()) {
    print '<TD><B>'.$Language->getText('project_admin_userperms','file_man').'</B></TD>';
}

if ( $project->usesTracker()&&$at_arr ) {
	for ($j = 0; $j < count($at_arr); $j++) {
		echo '<TD><B>'.$Language->getText('project_admin_userperms','tracker',$at_arr[$j]->getName()).'</B></TD>';
	}
}
print '<TD><B>'.$Language->getText('project_admin_userperms','member_ug').'</B></TD>';

?>
</TR>

<?php

if (!$res_dev || db_numrows($res_dev) < 1) {
    echo '<H2>'.$Language->getText('project_admin_userperms','no_users_found').'</H2>';
} else {

    while ($row_dev = db_fetch_array($res_dev)) {
        $i++;
        print '<TR class="'. util_get_alt_row_color($i) .'"><TD>'.$row_dev['user_name'].'</TD>';
        print '
			<TD>
			<INPUT TYPE="RADIO" NAME="admin_user_'.$row_dev['user_id'].'" VALUE="A" '.(($row_dev['admin_flags']=='A')?'CHECKED':'').'>&nbsp;'.$Language->getText('global','yes').'<BR>
			<INPUT TYPE="RADIO" NAME="admin_user_'.$row_dev['user_id'].'" VALUE="" '.(($row_dev['admin_flags']=='')?'CHECKED':'').'>&nbsp;'.$Language->getText('global','no').'
			</TD>';
        if ($project->usesCVS()) { print '<TD>'.$Language->getText('global','yes').'</TD>'; }
        // bug selects
        if ($project->usesBugs()) {
            print '<TD><FONT size="-1"><SELECT name="bugs_user_'.$row_dev['user_id'].'">';
            print '<OPTION value="0"'.(($row_dev['bug_flags']==0)?" selected":"").'>'.$Language->getText('global','none');
            print '<OPTION value="1"'.(($row_dev['bug_flags']==1)?" selected":"").'>'.$Language->getText('project_admin_userperms','tech_only');
            print '<OPTION value="2"'.(($row_dev['bug_flags']==2)?" selected":"").'>'.$Language->getText('project_admin_userperms','tech&admin');
            print '<OPTION value="3"'.(($row_dev['bug_flags']==3)?" selected":"").'>'.$Language->getText('project_admin_userperms','admin_only');
            print '</SELECT></FONT></TD>';
        } else {
            print '<input type="Hidden" name="bugs_user_'.$row_dev['user_id'].'" value="'.$row_dev['bug_flags'].'">';
        }
        // forums
        if ($project->usesForum()) {
            print '<TD><FONT size="-1"><SELECT name="forums_user_'.$row_dev['user_id'].'">';
            print '<OPTION value="0"'.(($row_dev['forum_flags']==0)?" selected":"").'>'.$Language->getText('global','none');
            print '<OPTION value="2"'.(($row_dev['forum_flags']==2)?" selected":"").'>'.$Language->getText('project_admin_userperms','moderator');
            print '</SELECT></FONT></TD>';
        }
       // wiki
	if ($project->usesWiki()) {
            print '<TD><FONT size="-1"><SELECT name="wiki_user_'.$row_dev['user_id'].'">';
            print '<OPTION value="0"'.(($row_dev['wiki_flags']==0)?" selected":"").'>'.$Language->getText('global','none');
            print '<OPTION value="2"'.(($row_dev['wiki_flags']==2)?" selected":"").'>'.$Language->getText('project_admin_index','admin');
            print '</SELECT></FONT></TD>';
        }

        // project selects
        if ($project->usesPm()) {
            print '<TD><FONT size="-1"><SELECT name="projects_user_'.$row_dev['user_id'].'">';
            print '<OPTION value="0"'.(($row_dev['project_flags']==0)?" selected":"").'>'.$Language->getText('global','none');
            print '<OPTION value="1"'.(($row_dev['project_flags']==1)?" selected":"").'>'.$Language->getText('project_admin_userperms','tech_only');
            print '<OPTION value="2"'.(($row_dev['project_flags']==2)?" selected":"").'>'.$Language->getText('project_admin_userperms','tech&admin');
            print '<OPTION value="3"'.(($row_dev['project_flags']==3)?" selected":"").'>'.$Language->getText('project_admin_userperms','admin_only');
            print '</SELECT></FONT></TD>';
        } else {
            print '<input type="Hidden" name="projects_user_'.$row_dev['user_id'].'" value="'.$row_dev['project_flags'].'">';
        }
		
        // patch selects
        if ($project->usesPatch()) {
	    print '<TD><FONT size="-1"><SELECT name="patch_user_'.$row_dev['user_id'].'">';
	    print '<OPTION value="0"'.(($row_dev['patch_flags']==0)?" selected":"").'>'.$Language->getText('global','none');
	    print '<OPTION value="1"'.(($row_dev['patch_flags']==1)?" selected":"").'>'.$Language->getText('project_admin_userperms','tech_only');
	    print '<OPTION value="2"'.(($row_dev['patch_flags']==2)?" selected":"").'>'.$Language->getText('project_admin_userperms','tech&admin');
	    print '<OPTION value="3"'.(($row_dev['patch_flags']==3)?" selected":"").'>'.$Language->getText('project_admin_userperms','admin_only');
	    print '</SELECT></FONT></TD>';
	}

        // support selects
        if ($project->usesSupport()) {
            print '<TD><FONT size="-1"><SELECT name="support_user_'.$row_dev['user_id'].'">';
            print '<OPTION value="0"'.(($row_dev['support_flags']==0)?" selected":"").'>'.$Language->getText('global','none');
            print '<OPTION value="1"'.(($row_dev['support_flags']==1)?" selected":"").'>'.$Language->getText('project_admin_userperms','tech_only');
            print '<OPTION value="2"'.(($row_dev['support_flags']==2)?" selected":"").'>'.$Language->getText('project_admin_userperms','tech&admin');
            print '<OPTION value="3"'.(($row_dev['support_flags']==3)?" selected":"").'>'.$Language->getText('project_admin_userperms','admin_only');
            print '</SELECT></FONT></TD>';
        } else {
            print '<input type="Hidden" name="support_user_'.$row_dev['user_id'].'" value="'.$row_dev['support_flags'].'">';
        }
	
        //documentation states
        if ($project->usesDocman()) {
            print '<TD><FONT size="-1"><SELECT name="doc_user_'.$row_dev['user_id'].'">';
            print '<OPTION value="0"'.(($row_dev['doc_flags']==0)?" selected":"").'>'.$Language->getText('global','none');
            print '<OPTION value="1"'.(($row_dev['doc_flags']==1)?" selected":"").'>'.$Language->getText('project_admin_userperms','tech_only');
            print '<OPTION value="2"'.(($row_dev['doc_flags']==2)?" selected":"").'>'.$Language->getText('project_admin_userperms','tech&admin');
            print '<OPTION value="3"'.(($row_dev['doc_flags']==3)?" selected":"").'>'.$Language->getText('project_admin_userperms','admin_only');
            print '</SELECT></FONT></TD>';
        }
        
        // File release manager: nothing or admin
        if ($project->usesFile()) {
            print '<TD><FONT size="-1"><SELECT name="file_user_'.$row_dev['user_id'].'">';
            print '<OPTION value="0"'.(($row_dev['file_flags']==0)?" selected":"").'>'.$Language->getText('global','none');
            print '<OPTION value="2"'.(($row_dev['file_flags']==2)?" selected":"").'>'.$Language->getText('project_admin_index','admin');
            print '</SELECT></FONT></TD>
	';
        }
           
	
        if ( $project->usesTracker()&&$at_arr ) {
            // Loop on tracker
            for ($j = 0; $j < count($at_arr); $j++) {
                $perm = $at_arr[$j]->getUserPerm($row_dev['user_id']);
                print '<TD><FONT size="-1"><SELECT name="tracker_user_'.$row_dev['user_id'].'_'.$at_arr[$j]->getID().'">';
                print '<OPTION value="0"'.(($perm==0)?" selected":"").'>'.$Language->getText('global','none');
                print '<OPTION value="1"'.(($perm==1)?" selected":"").'>'.$Language->getText('project_admin_userperms','tech_only');
                print '<OPTION value="2"'.(($perm==2)?" selected":"").'>'.$Language->getText('project_admin_userperms','tech&admin');
                print '<OPTION value="3"'.(($perm==3)?" selected":"").'>'.$Language->getText('project_admin_userperms','admin_only');
                print '</SELECT></FONT></TD>';
            }
        }
        
        print '<TD><FONT size="-1">';
        $res_ugroups=ugroup_db_list_all_ugroups_for_user($group_id,$row_dev['user_id']);
        $is_first=true;
        if (db_numrows($res_ugroups)<1) {
            print '-';
        } else {
            while ($row = db_fetch_array($res_ugroups)) {
                if (!$is_first) { print ', '; }
                print '<a href="/project/admin/editugroup.php?group_id='.$group_id.'&ugroup_id='.$row['ugroup_id'].'&func=edit">'.
                    $row['name'].'</a>';
                $is_first=false;
            }
        }
        print '</FONT></TD>';

        print '</TR>
	';
    } // while

}

echo '
</TABLE>
<P align="center"><INPUT type="submit" name="submit" value="'.$Language->getText('project_admin_userperms','upd_user_perm').'">
</FORM>',


project_admin_footer(array());
?>
