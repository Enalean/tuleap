<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// $Id$

require($DOCUMENT_ROOT.'/include/pre.php');    
require($DOCUMENT_ROOT.'/project/admin/project_admin_utils.php');
require($DOCUMENT_ROOT.'/include/account.php');
require($DOCUMENT_ROOT.'/../common/tracker/ArtifactType.class');
require($DOCUMENT_ROOT.'/../common/tracker/ArtifactTypeFactory.class');
require($DOCUMENT_ROOT.'/project/admin/ugroup_utils.php');

// get current information
$res_grp = group_get_result($group_id);

if (db_numrows($res_grp) < 1) {
    exit_error("Invalid Project","That project could not be found.");
}

//if the project isn't active, require you to be a member of the super-admin group
if (!(db_result($res_grp,0,'status') == 'A')) {
    session_require(array('group'=>1));
}

//must be a project admin
session_require(array('group'=>$group_id,'admin_flags'=>'A'));

if ($func) {
    /*
      updating the database
    */
    if ($func=='adduser') {
        /*
	    add user to this project
        */
        $res = account_add_user_to_group ($group_id,$form_unix_name);
	
        if ($res) {
            group_add_history ('Added User',$form_unix_name,$group_id);
        }

    } else if ($func=='rmuser') {
        /*
	  remove a user from this portal
        */
        $res=db_query("DELETE FROM user_group WHERE group_id='$group_id' AND user_id='$rm_id' AND admin_flags <> 'A'");
        if (!$res || db_affected_rows($res) < 1) {
            $feedback .= ' User Not Removed - You cannot remove admins from a project. 
				You must first turn off their admin flag and/or find another admin for the project ';
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
                $feedback .= 'Could Not Get ArtifactTypeFactory';
            }
            
            // Get the artfact type list
            $at_arr = $atf->getArtifactTypes();
            
            if ($at_arr && count($at_arr) > 0) {
                for ($j = 0; $j < count($at_arr); $j++) {
                    if ( !$at_arr[$j]->deleteUser($rm_id) ) {
                        $feedback .= " Failed to delete tracker permission (".$at_arr[$j]->getName().") ";
                    }
                }
            }
            
            // Remove user from ugroups attached to this project
            if (!ugroup_delete_user_from_project_ugroups($group_id,$rm_id)) {
                $feedback .= " Failed to delete user from ugroups ";
            }

            $feedback .= ' Removed a User ';
            group_add_history ('removed user',$rm_id,$group_id);
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
    } */
}

project_admin_header(array('title'=>"Project Admin: ".group_getname($group_id),'group'=>$group_id,
			   'help' => 'ProjectAdministration.html'));

/*
	Show top box listing trove and other info
*/

echo '<TABLE width=100% cellpadding=2 cellspacing=2 border=0>
<TR valign=top><TD width=50%>';

$HTML->box1_top("Project Edit: " . group_getname($group_id)); 


$project=new Project($group_id);


print '&nbsp;
<BR>
Short Description: '. db_result($res_grp,0,'short_description') .'
<P>Homepage Link: <B>'.$project->getHomePage().'</B>
<!-- Not implemented on CodeX
<P align=center>
<A HREF="http://'.$GLOBALS['sys_cvs_host'].'/cvstarballs/'. db_result($res_grp,0,'unix_group_name') .'-cvsroot.tar.gz">[ Download Your Nightly CVS Tree Tarball ]</A>
-->
<P>
<B>Trove Categorization Info</B> - This project is in the following Trove categories:

<UL>';

// list all trove categories
$res_trovecat = db_query('SELECT trove_cat.fullpath AS fullpath,'
			 .'trove_cat.trove_cat_id AS trove_cat_id '
			 .'FROM trove_cat,trove_group_link WHERE trove_cat.trove_cat_id='
			 .'trove_group_link.trove_cat_id AND trove_group_link.group_id='.$group_id
			 .' ORDER BY trove_cat.fullpath');
while ($row_trovecat = db_fetch_array($res_trovecat)) {
    print ('<LI>'.$row_trovecat['fullpath'].' '
	   .help_button('trove_cat',$row_trovecat['trove_cat_id'])."\n");
}

print '
</UL>
<P align="center">
<A href="/project/admin/group_trove.php?group_id='.$group_id.'">'
.'<B>[Edit Trove Categorization]</B></A>
';

$HTML->box1_bottom(); 

echo '
</TD><TD>&nbsp;</TD><TD width=50%>';


$HTML->box1_top("Project Members&nbsp;".help_button('UserPermissions.html'));

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
          <TR><TD><B>Login Name:</B></TD><TD><INPUT TYPE="TEXT" NAME="form_unix_name" VALUE=""></TD></TR>
          <TR><TD COLSPAN="2" ALIGN="CENTER"><INPUT TYPE="SUBMIT" NAME="SUBMIT" VALUE="Add User"></TD></TR></FORM>
          </TABLE>

         <HR NoShade SIZE="1">
         <div align="center">
         <A href="/project/admin/userperms.php?group_id='. $group_id.'">[Edit Member Permissions]</A>
         </div>
         </TD></TR>';
 
$HTML->box1_bottom();


echo '</TD></TR>

	<TR valign=top><TD width=50%>';

/*
	Links to Services administration pages
*/

$HTML->box1_top('Services Administration&nbsp;'.help_button('ServicesAdministration.html'));


echo '
	<BR>';
if ($project->usesForum()) {
    echo '	<A HREF="/forum/admin/?group_id='.$group_id.'">Forum Admin</A><BR>';
}
if ($project->usesBugs() && !($sys_activate_tracker && !$project->activateOldBug())) {
    echo '	<A HREF="/bugs/admin/?group_id='.$group_id.'">Bug Admin</A><BR>';
}
if ($project->usesSupport() && !($sys_activate_tracker && !$project->activateOldSR())) {
    echo '	<A HREF="/support/admin/?group_id='.$group_id.'">Support Manager Admin</A><BR>';
}
if ($project->usesPatch()) {
    echo '	<A HREF="/patch/admin/?group_id='.$group_id.'">Patch Admin</A><BR>';
}
if ($project->usesMail()) {
    echo '	<A HREF="/mail/admin/?group_id='.$group_id.'">Lists Admin</A><BR>';
}
if ($project->usesPm() && !($sys_activate_tracker && !$project->activateOldTask())) {
    echo '	<A HREF="/pm/admin/?group_id='.$group_id.'">Task Manager Admin</A><BR>';
}
if ($project->usesDocman()) {
    echo '	<A HREF="/docman/admin/?group_id='.$group_id.'">DocManager Admin</A><BR>';
}
if ($project->usesSurvey()) {
    echo '	<A HREF="/survey/admin/?group_id='.$group_id.'">Survey Admin</A><BR>';
}
if ($project->usesNews()) {
    echo '	<A HREF="/news/admin/?group_id='.$group_id.'">News Admin</A><BR>';
}
if ($project->usesCVS()) {
    echo '	<A HREF="/cvs/?func=admin&group_id='.$group_id.'">CVS Admin</A><BR>';
}
if ($project->usesSVN()) {
    echo '	<A HREF="/svn/admin/?group_id='.$group_id.'">Subversion Admin</A><BR>';
}
if ($project->usesFile()) {
    echo '	<A HREF="/file/admin/?group_id='.$group_id.'">File Manager Admin</A><BR>';
}
if ( $project->usesTracker()&&$sys_activate_tracker ) {
    echo '	<A HREF="/tracker/admin/?group_id='.$group_id.'">Tracker Admin</A>';
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
    
    if (!$at_arr || count($at_arr) < 1) {
        echo "<br><i>-&nbsp;No Accessible Trackers Found</i>";
    } else {
        for ($j = 0; $j < count($at_arr); $j++) {
            echo '<br><i>-&nbsp;
			<a href="/tracker/admin/?atid='. $at_arr[$j]->getID() .
                '&group_id='.$group_id.'">' .
                $at_arr[$j]->getName() .' Admin</a></i>';
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

$HTML->box1_top("File Releases&nbsp;".help_button('FileRelease.html')); ?>
	&nbsp;<BR>
	<CENTER>
	<A href="/file/admin/editpackages.php?group_id=<?php print $group_id; ?>"><B>[Edit/Add File Releases]</B></A><BR> or... <BR>
	<A href="/file/admin/qrs.php?group_id=<?php print $group_id; ?>"><B>[Quick Add File Release]</B></A><BR>if you have only one file to release.
	</CENTER>

	<HR>
	<B>Package(s) currently available:</B>

     <P><?php

	$res_module = db_query("SELECT * FROM frs_package WHERE group_id=$group_id");
if (db_numrows($res_module) <= 0) {
    echo "None<br>";
} else {
    while ($row_module = db_fetch_array($res_module)) {
	print "$row_module[name]<BR>";
    }
}
echo $HTML->box1_bottom(); ?>
</TD>
</TR>
</TABLE>

<?php
project_admin_footer(array());

?>
