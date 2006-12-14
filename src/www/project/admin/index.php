<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// $Id$

require_once('pre.php');    
require_once('www/project/admin/project_admin_utils.php');
require_once('account.php');
require_once('common/include/TemplateSingleton.class');
require_once('common/tracker/ArtifactType.class');
require_once('common/tracker/ArtifactTypeFactory.class');
require_once('www/project/admin/ugroup_utils.php');
require_once('pfamily.php');

$Language->loadLanguageMsg('project/project');

// get current information
$res_grp = group_get_result($group_id);
if (db_numrows($res_grp) < 1) {
  exit_error($Language->getText('project_admin_index','invalid_p'),$Language->getText('project_admin_index','p_not_found'));
}

//if the project isn't active, require you to be a member of the super-admin group
if (!(db_result($res_grp,0,'status') == 'A')) {
    session_require(array('group'=>1));
}

//must be a project admin
session_require(array('group'=>$group_id,'admin_flags'=>'A'));

//	  
//  get the Group object
//	  
$group = group_get_object($group_id);
if (!$group || !is_object($group) || $group->isError()) {
  exit_no_group();
}

if (isset($func)) {
    /*
      updating the database
    */
    if ($func=='adduser') {
        /*
	    add user to this project
        */
        $res = account_add_user_to_group ($group_id,$form_unix_name);
	
        if ($res) {
            group_add_history('added_user',$form_unix_name,$group_id,array($form_unix_name));
        }

    } else if ($func=='rmuser') {
        /*
	  remove a user from this portal
        */
        $res=db_query("DELETE FROM user_group WHERE group_id='$group_id' AND user_id='$rm_id' AND admin_flags <> 'A'");
        if (!$res || db_affected_rows($res) < 1) {
            $feedback .= ' '.$Language->getText('project_admin_index','user_not_removed').' ';
        } else {
            
            //	  
            //  get the Group object
            //	  
            $group = group_get_object($group_id);
            if (!$group || !is_object($group) || $group->isError()) {
                exit_no_group();
            }		   
            $atf = new ArtifactTypeFactory($group);
            if (!$group || !is_object($group) || $group->isError()) {
                $feedback .= $Language->getText('project_admin_index','not_get_atf');
            }
            
            // Get the artfact type list
            $at_arr = $atf->getArtifactTypes();
            
            if ($at_arr && count($at_arr) > 0) {
                for ($j = 0; $j < count($at_arr); $j++) {
                    if ( !$at_arr[$j]->deleteUser($rm_id) ) {
                        $feedback .= ' '.$Language->getText('project_admin_index','del_tracker_perm_fail',$at_arr[$j]->getName()).' ';
                    }
                }
            }
            
            // Remove user from ugroups attached to this project
            if (!ugroup_delete_user_from_project_ugroups($group_id,$rm_id)) {
                $feedback .= ' '.$Language->getText('project_admin_index','del_user_from_ug_fail').' ';
            }

            $feedback .= ' '.$Language->getText('project_admin_index','user_removed').' ';
            group_add_history ('removed_user',user_getname($rm_id)." ($rm_id)",$group_id);
        }


    } else if ($func == "change_group_type") {

      if ($group->getType() != $form_project_type) {
	group_add_history ('group_type',$group->getType(),$group_id);  

	$template =& TemplateSingleton::instance();
	$group->setType($form_project_type);

	//set also flag on trackers to be copied or not on project instanciation
	if ($template->isTemplate($form_project_type)) {
	  db_query("UPDATE artifact_group_list SET instantiate_for_new_projects='1' WHERE group_id='$group_id'");
	} else {
	  db_query("UPDATE artifact_group_list SET instantiate_for_new_projects='0' WHERE group_id='$group_id'");
	}

	// get current information, force update on group and project objects
	$group = group_get_object($group_id,false,true);
	$project = project_get_object($group_id,true);

	$feedback .= ' '.$Language->getText('project_admin_index','changed_group_type').' ';
      }

    } /* else if ($func == "import") {
       session_require(array('group'=>$group_id,'admin_flags'=>'A'));
      
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
       $mode = "admin";
       require('../../tracker/import.php');
    } */ else if (ProjectFamilyActionHandler($group_id, $func)) {
		// project family handler did it all!
	} else {
		exit_error("unknown action: ".$func, "");	//should not occur (no translation required) 
	}
}

project_admin_header(array('title'=>$Language->getText('project_admin_index','p_admin',group_getname($group_id)),'group'=>$group_id,
			   'help' => 'ProjectAdministration.html'));

/*
	Show top box listing trove and other info
*/

echo '<TABLE width=100% cellpadding=2 cellspacing=2 border=0>
<TR valign=top><TD width=50%>';

$HTML->box1_top($Language->getText('project_admin_index','p_edit',group_getname($group_id))); 


$project=new Project($group_id);


print '&nbsp;
<BR>
'.$Language->getText('project_admin_index','short_desc',db_result($res_grp,0,'short_description'));
if ($project->usesHomePage()) {
    print '<P>'.$Language->getText('project_admin_index','home_page_link',$project->getHomePage()).'</B>';
 }
print '<!-- Not implemented on CodeX
<P align=center>
<A HREF="http://'.$GLOBALS['sys_cvs_host'].'/cvstarballs/'. db_result($res_grp,0,'unix_group_name') .'-cvsroot.tar.gz">[ Download Your Nightly CVS Tree Tarball ]</A>
-->
<P>
<B>'.$Language->getText('project_admin_index','trove_cat_info').'

<UL>';

// list all trove categories
$res_trovecat = db_query('SELECT trove_cat.fullpath AS fullpath,'
			 .'trove_cat.trove_cat_id AS trove_cat_id '
			 .'FROM trove_cat,trove_group_link WHERE trove_cat.trove_cat_id='
			 .'trove_group_link.trove_cat_id AND trove_group_link.group_id='.$group_id
			 .' ORDER BY trove_group_id');
while ($row_trovecat = db_fetch_array($res_trovecat)) {
    print ('<LI>'.$row_trovecat['fullpath'].' '
	   .help_button('trove_cat',$row_trovecat['trove_cat_id'])."\n");
}

print '
</UL>
<P align="center">
<A href="/project/admin/group_trove.php?group_id='.$group_id.'">'
.'<B>'.$Language->getText('project_admin_index','edit_trove_cat').'</B></A>
';


// list all possible project types
// get current information
$template =& TemplateSingleton::instance(); 


print '
<HR NoShade SIZE="1">
<P>
<TABLE WIDTH="100%" BORDER="0">
 <TR>
  <TD><B>'.$Language->getText('project_admin_index','group_type').' '.help_button('ProjectAdministration.html#ProjectType').' : </B>
      <FORM action="'. $PHP_SELF .'" method="post">
      <INPUT TYPE="HIDDEN" NAME="func" VALUE="change_group_type">
      <INPUT TYPE="HIDDEN" NAME="group_id" VALUE="'.$group_id.'"></TD>
  <TD valign="top">'.$template->showTypeBox('form_project_type',$group->getType()).'
      <INPUT type="submit" name="Update" value="'.$Language->getText('global','btn_update').'">
      </FORM></TD>
 </TR>
</TABLE>
';

$template_group = group_get_object($group->getTemplate());
$template_name = $template_group->getPublicName();
print '
<HR NoShade SIZE="1">
<P>
'.$Language->getText('project_admin_index','built_from_template','<A href="/projects/'.$template_group->getUnixName().'"> <B> '.$template_name.' </B></A>');

$HTML->box1_bottom(); 

echo '
</TD><TD>&nbsp;</TD><TD width=50%>';


$HTML->box1_top($Language->getText('project_admin_editugroup','proj_members')."&nbsp;".help_button('UserPermissions.html'));

/*

	Show the members of this project

*/

$res_memb = db_query("SELECT user.realname,user.user_id,user.user_name,user.status ".
		     "FROM user,user_group ".
		     "WHERE user.user_id=user_group.user_id ".
		     "AND user_group.group_id=$group_id");

print '<TABLE WIDTH="100%" BORDER="0">';

while ($row_memb=db_fetch_array($res_memb)) {
    $showname=$row_memb['realname'];
    print '<FORM ACTION="'. $PHP_SELF .'" METHOD="POST"><INPUT TYPE="HIDDEN" NAME="func" VALUE="rmuser">'.
	'<INPUT TYPE="HIDDEN" NAME="rm_id" VALUE="'.$row_memb['user_id'].'">'.
	'<INPUT TYPE="HIDDEN" NAME="group_id" VALUE="'. $group_id .'">'.
	'<TR><TD ALIGN="center"><INPUT TYPE="IMAGE" NAME="DELETE" SRC="'.util_get_image_theme("ic/trash.png").'" HEIGHT="16" WIDTH="16" BORDER="0"></TD></FORM>'.
	'<TD><A href="/users/'.$row_memb['user_name'].'/">'.$showname.'&nbsp;&nbsp;('.$row_memb['user_name'].') </A></TD></TR>';
}

print '</TABLE> <HR NoShade SIZE="1">';

/*
	Add member form
*/

echo '
          <FORM ACTION="'. $PHP_SELF .'" METHOD="POST">
          <INPUT TYPE="hidden" NAME="func" VALUE="adduser">
          <INPUT TYPE="HIDDEN" NAME="group_id" VALUE="'. $group_id .'">
          <TABLE WIDTH="100%" BORDER="0">
          <TR><TD><B>'.$Language->getText('project_admin_index','login_name').'</B></TD><TD><INPUT TYPE="TEXT" NAME="form_unix_name" VALUE=""></TD></TR>
          <TR><TD COLSPAN="2" ALIGN="CENTER"><INPUT TYPE="SUBMIT" NAME="SUBMIT" VALUE="'.$Language->getText('project_admin_index','add_user').'"></TD></TR></FORM>
          </TABLE>

         <HR NoShade SIZE="1">
         <div align="center">
         <A href="/project/admin/userperms.php?group_id='. $group_id.'">'.$Language->getText('project_admin_index','edit_member_perm').'</A>
         </div>
         </TD></TR>';
 
$HTML->box1_bottom();


echo '</TD></TR>

	<TR valign=top><TD width=50%>';

/*
	Links to Services administration pages
*/

$HTML->box1_top($Language->getText('project_admin_index','s_admin').'&nbsp;'.help_button('ServicesAdministration.html'));


echo '
	<BR>';
if ($project->usesForum()) {
    echo '	<A HREF="/forum/admin/?group_id='.$group_id.'">'.$Language->getText('project_admin_index','forum_admin').'</A><BR>';
}
if ($project->usesBugs()) {
    echo '	<A HREF="/bugs/admin/?group_id='.$group_id.'">'.$Language->getText('project_admin_index','bug_admin').'</A><BR>';
}
if ($project->usesSupport()) {
    echo '	<A HREF="/support/admin/?group_id='.$group_id.'">'.$Language->getText('project_admin_index','support_admin').'</A><BR>';
}
if ($project->usesPatch()) {
    echo '	<A HREF="/patch/admin/?group_id='.$group_id.'">'.$Language->getText('project_admin_index','patch_admin').'</A><BR>';
}
if ($project->usesMail()) {
    echo '	<A HREF="/mail/admin/?group_id='.$group_id.'">'.$Language->getText('project_admin_index','lists_admin').'</A><BR>';
}
if ($project->usesPm()) {
    echo '	<A HREF="/pm/admin/?group_id='.$group_id.'">'.$Language->getText('project_admin_index','task_admin').'</A><BR>';
}
if ($project->usesDocman()) {
    echo '	<A HREF="/docman/admin/?group_id='.$group_id.'">'.$Language->getText('project_admin_index','doc_admin').'</A><BR>';
}
if ($project->usesWiki()) {
    echo '	<A HREF="/wiki/admin/?group_id='.$group_id.'">'.$Language->getText('project_admin_index','wiki_admin').'</A><BR>';
}
if ($project->usesSurvey()) {
    echo '	<A HREF="/survey/admin/?group_id='.$group_id.'">'.$Language->getText('project_admin_index','survey_admin').'</A><BR>';
}
if ($project->usesNews()) {
    echo '	<A HREF="/news/admin/?group_id='.$group_id.'">'.$Language->getText('project_admin_index','news_admin').'</A><BR>';
}
if ($project->usesCVS()) {
    echo '	<A HREF="/cvs/?func=admin&group_id='.$group_id.'">'.$Language->getText('project_admin_index','cvs_admin').'</A><BR>';
}
if ($project->usesSVN()) {
    echo '	<A HREF="/svn/admin/?group_id='.$group_id.'">'.$Language->getText('project_admin_index','svn_admin').'</A><BR>';
}
if ($project->usesFile()) {
    echo '	<A HREF="/file/admin/?group_id='.$group_id.'">'.$Language->getText('project_admin_index','file_admin').'</A><BR>';
}
if ( $project->usesTracker()) {
    echo '	<A HREF="/tracker/admin/?group_id='.$group_id.'">'.$Language->getText('project_admin_index','tracker_admin').'</A>';
    //	  
    //  get the Group object
    //	  
    $group = group_get_object($group_id);
    if (!$group || !is_object($group) || $group->isError()) {
        exit_no_group();
    }		   
    $atf = new ArtifactTypeFactory($group);
    if (!$group || !is_object($group) || $group->isError()) {
        exit_error($Language->getText('global','error'),'Could Not Get ArtifactTypeFactory');
    }
    
    // Get the artfact type list
    $at_arr = $atf->getArtifactTypes();
    
    if (!$at_arr || count($at_arr) < 1) {
        echo "<br><i>-&nbsp;".$Language->getText('project_admin_index','no_tracker_found').'</i>';
    } else {
        for ($j = 0; $j < count($at_arr); $j++) {
            echo '<br><i>-&nbsp;
			<a href="/tracker/admin/?atid='. $at_arr[$j]->getID() .
                '&group_id='.$group_id.'">' .
                $at_arr[$j]->getName() .' '.$Language->getText('project_admin_index','admin').'</a></i>';
        }
    }

}

$HTML->box1_bottom(); 




echo '</TD>

	<TD>&nbsp;</TD>

	<TD width=50%>';

/*
	Show filerelease info
*/
if ($project->usesFile()) {
    $HTML->box1_top($Language->getText('project_admin_index','file_rel')."&nbsp;".help_button('FileRelease.html'));

    echo '
	&nbsp;<BR>
	<CENTER>
	<A href="/file/admin/editpackages.php?group_id='.$group_id.'"><B>'.$Language->getText('project_admin_index','edit_add_rel').'</B></A><BR> '.$Language->getText('project_admin_index','or').'... <BR>
	<A href="/file/admin/qrs.php?group_id='.$group_id.'"><B>'.$Language->getText('project_admin_index','quick_file_add').'</B></A><BR>'.$Language->getText('project_admin_index','if_1_file_in_rel').'
	</CENTER>

	<HR>
	<B>'.$Language->getText('project_admin_index','packages_available').'</B>

     <P>';

    $res_module = db_query("SELECT * FROM frs_package WHERE group_id=$group_id");
    if (db_numrows($res_module) <= 0) {
        echo $Language->getText('global','none').'<br>';
    } else {
        while ($row_module = db_fetch_array($res_module)) {
            print "$row_module[name]<BR>";
        }
    }
    echo $HTML->box1_bottom();
}
?>
</TD>
</TR>
</TABLE>

<?php
project_admin_footer(array());
?>
