<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// $Id$

require($DOCUMENT_ROOT.'/include/vars.php');
require($DOCUMENT_ROOT.'/news/news_utils.php');
require($DOCUMENT_ROOT.'/include/trove.php');
require($DOCUMENT_ROOT.'/../common/tracker/ArtifactType.class');
require($DOCUMENT_ROOT.'/../common/tracker/ArtifactTypeFactory.class');
require($DOCUMENT_ROOT.'/project/admin/permissions.php');

//make sure this project is NOT a foundry
if (!$project->isProject()) {
	header ("Location: /foundry/". $project->getUnixName() ."/");
	exit;
}       

$title = 'Project Info - '. $project->getPublicName();

site_project_header(array('title'=>$title,'group'=>$group_id,'toptab'=>'summary'));


// ########################################### end top area

// two column deal
?>

<TABLE WIDTH="100%" BORDER="0">
<TR><TD WIDTH="99%" VALIGN="top">
<?php 

// ########################################## top area, not in box 
$res_admin = db_query("SELECT user.user_id AS user_id,user.user_name AS user_name "
	. "FROM user,user_group "
	. "WHERE user_group.user_id=user.user_id AND user_group.group_id=$group_id AND "
	. "user_group.admin_flags = 'A'");

if ($project->getStatus() == 'H') {
	print "<P>NOTE: This project entry is maintained by the ".$GLOBALS['sys_name']." staff. We are not "
		. "the official site "
		. "for this product. Additional copyright information may be found on this project's homepage.\n";
}

// LJ Pointer to more detailed description added
if ($project->getDescription()) {
	print "<P>" . $project->getDescription();
	$details_prompt = '[More information...]';
} else {
	print '<P>This project has not yet submitted a short description.You can <a href="/project/admin/editgroupinfo.php?group_id='.$group_id.'"> submit it</a> now.';
	$details_prompt = '[Other information...]';
}

print '<a href="/project/showdetails.php?group_id='.$group_id.'"> '. $details_prompt .'</a>';

// trove info
print '<BR>&nbsp;<BR>';
trove_getcatlisting($group_id,0,1);
print '<BR>&nbsp;';

print 'View project activity <a href="/project/stats/?group_id='.$group_id.'">statistics</a>';

print '</TD><TD NoWrap VALIGN="top">';

// ########################### Developers on this project

echo $HTML->box1_top("Developer Info");
?>
<?php
if (db_numrows($res_admin) > 0) {

	?>
	<SPAN CLASS="develtitle">Project Admins:</SPAN><BR>
	<?php
		while ($row_admin = db_fetch_array($res_admin)) {
			print "<A href=\"/users/$row_admin[user_name]/\">$row_admin[user_name]</A><BR>";
		}
	?>
	<HR WIDTH="100%" SIZE="1" NoShade>
	<?php

}

?>
<SPAN CLASS="develtitle">Developers:</SPAN><BR>
<?php
//count of developers on this project
$res_count = db_query("SELECT user_id FROM user_group WHERE group_id=$group_id");
print db_numrows($res_count);

?>

<A HREF="/project/memberlist.php?group_id=<?php print $group_id; ?>">[View Members]</A>
<?php 

echo $HTML->box1_bottom();

print '
</TD></TR>
</TABLE>
<P>
';


// ############################# File Releases

echo $HTML->box1_top('Latest File Releases'); 
$unix_group_name = $project->getUnixName();

echo '
	<TABLE cellspacing="1" cellpadding="5" width="100%" border="0">
		<TR class="boxitem">
		<TD align="left"">
			Package
		</td>
		<TD align="center">
			Version
		</td>
		<TD align="center">
			Notes / Monitor
		</td>
		<TD align="center">
			Download
		</td>
		</TR>';

$sql="SELECT frs_package.package_id,frs_package.name AS package_name,frs_release.name AS release_name,frs_release.release_id AS release_id,frs_release.release_date AS release_date ".
"FROM frs_package,frs_release ".
"WHERE frs_package.package_id=frs_release.package_id ".
"AND frs_package.group_id='$group_id' ".
"AND frs_release.status_id=1 ".
"ORDER BY frs_package.package_id,frs_release.release_date DESC, frs_release.release_id DESC";

$res_files = db_query($sql);
$rows_files=db_numrows($res_files);
$nb_packages=0;
if (!$res_files || $rows_files < 1) {
    echo db_error();
    // No releases
    echo '<TR class="boxitem"><TD COLSPAN="4"><B>This Project Has Not Released Any Files</B></TD></TR>';
    
} else {
    /*
       This query actually contains ALL releases of all packages
       We will test each row and make sure the package has changed before printing the row
    */
    for ($f=0; $f<$rows_files; $f++) {
        $package_id=db_result($res_files,$f,'package_id');
        $release_id=db_result($res_files,$f,'release_id');

        if ($package_displayed[$package_id]) {
            //if ($package_id==db_result($res_files,($f-1),'package_id')) {
            //same package as last iteration - don't show this release
        } else {
            $authorized=false;
            // check access.
            if (permission_exist('RELEASE_READ', $release_id)) {
                $authorized=permission_is_authorized('RELEASE_READ',$release_id ,user_getid());
            } else {  
                $authorized=permission_is_authorized('PACKAGE_READ',$package_id ,user_getid());
            }
            if ($authorized) {
                $nb_packages++;
                echo '
                  <TR class="boxitem" ALIGN="center">
                  <TD ALIGN="left">
                  <B>' . db_result($res_files,$f,'package_name'). '</B></TD>';
                // Releases to display
                print '<TD>'.db_result($res_files,$f,'release_name') .'
                  </TD>
                  <TD align="center"><A href="/file/shownotes.php?group_id=' . $group_id . '&release_id=' . $release_id . '">';
                echo	html_image("ic/manual16c.png",array('width'=>'15', 'height'=>'15', 'alt'=>'Release Notes'));
                echo '</A> - <A HREF="/file/filemodule_monitor.php?filemodule_id=' .	$package_id . '">';
                echo html_image("ic/mail16d.png",array('width'=>'15', 'height'=>'15', 'alt'=>'Monitor This Package'));
                echo '</A>
                  </TD>
                  <TD align="center"><A HREF="/file/showfiles.php?group_id=' . $group_id . '&release_id=' . $release_id . '">Download</A></TD></TR>';
                $package_displayed[$package_id]=true;
            }
        }
    }
    
}
?></TABLE>
<div align="center">
<a href="/file/showfiles.php?group_id=<?php print $group_id; ?>">[View ALL Project Files]</A>
</div>
<?php
	echo $HTML->box1_bottom();

?>
<P>
<TABLE WIDTH="100%" BORDER="0" CELLPADDING="0" CELLSPACING="0">
<TR><TD VALIGN="top">

<?php

// ############################## PUBLIC AREAS
echo $HTML->box1_top("Public Areas"); 

// ################# Homepage Link

print "<A href=\"" . $project->getHomePage() . "\">";
html_image("ic/home16b.png",array('width'=>'20', 'height'=>'20', 'alt'=>'Homepage'));
print '&nbsp;Project Homepage</A>';

// ################## forums

if ($project->usesForum()) {
	print '<HR SIZE="1" NoShade><A href="/forum/?group_id='.$group_id.'">';
	html_image("ic/notes16.png",array('width'=>'20', 'height'=>'20', 'alt'=>'Public Forums')); 
	print '&nbsp;Public Forums</A>';
	$res_count = db_query("SELECT count(forum.msg_id) AS count FROM forum,forum_group_list WHERE "
		. "forum_group_list.group_id=$group_id AND forum.group_forum_id=forum_group_list.group_forum_id "
		. "AND forum_group_list.is_public=1");
	$row_count = db_fetch_array($res_count);
	print " ( <B>$row_count[count]</B> messages in ";

	$res_count = db_query("SELECT count(*) AS count FROM forum_group_list WHERE group_id=$group_id "
		. "AND is_public=1");
	$row_count = db_fetch_array($res_count);
	print "<B>$row_count[count]</B> forums )\n";
/*
	$sql="SELECT * FROM forum_group_list WHERE group_id='$group_id' AND is_public=1";
	$res2 = db_query ($sql);
	$rows = db_numrows($res2);
	for ($j = 0; $j < $rows; $j++) {
		echo '<BR> &nbsp; - <A HREF="forum.php?forum_id='.db_result($res2, $j, 'group_forum_id').'&et=0">'.
			db_result($res2, $j, 'forum_name').'</A> ';
		//message count
		echo '('.db_result(db_query("SELECT count(*) FROM forum WHERE group_forum_id='".db_result($res2, $j, 'group_forum_id')."'"),0,0).' msgs)';
	}
*/
}

// ##################### Bug tracking (only for Active)

if ($project->usesBugs() && !($sys_activate_tracker && !$project->activateOldBug())) {
	print '<HR SIZE="1" NoShade><A href="/bugs/?group_id='.$group_id.'">';
	html_image("ic/bug16b.png",array('width'=>'20', 'height'=>'20', 'alt'=>'Bug Tracking')); 
	print '&nbsp;Bug Tracking</A>';
	$res_count = db_query("SELECT count(*) AS count FROM bug WHERE group_id=$group_id AND status_id != 3");
	$row_count = db_fetch_array($res_count);
	print " ( <B>$row_count[count]</B>";
	$res_count = db_query("SELECT count(*) AS count FROM bug WHERE group_id=$group_id");
	$row_count = db_fetch_array($res_count);
	print " open bugs, <B>$row_count[count]</B> total )";
}

// ##################### Support Manager (only for Active)
 
if ($project->usesSupport() && !($sys_activate_tracker && !$project->activateOldSR())) {
	print '
	<HR SIZE="1" NoShade>
	<A href="/support/?group_id='.$group_id.'">';
	html_image("ic/support16b.jpg",array('width'=>'20', 'height'=>'20', 'alt'=>'Support Manager'));
	print '&nbsp;Tech Support Manager</A>';
	$res_count = db_query("SELECT count(*) AS count FROM support WHERE group_id=$group_id");
	$row_count = db_fetch_array($res_count);
	$res_count = db_query("SELECT count(*) AS count FROM support WHERE group_id=$group_id AND support_status_id='1'");
	$row_count2 = db_fetch_array($res_count);
	print " ( <B>$row_count2[count]</B>";
	print " open requests, <B>$row_count[count]</B> total )";
}

// ##################### Doc Manager (only for Active)

if ($project->usesDocman()) {
	print '
	<HR SIZE="1" NoShade>
	<A href="/docman/?group_id='.$group_id.'">';
	html_image("ic/docman16b.png",array('width'=>'20', 'height'=>'20', 'alt'=>'Documentation'));
	print '&nbsp;DocManager: Project Documentation</A>';
/*
	$res_count = db_query("SELECT count(*) AS count FROM support WHERE group_id=$group_id");
	$row_count = db_fetch_array($res_count);
	$res_count = db_query("SELECT count(*) AS count FROM support WHERE group_id=$group_id AND support_status_id='1'");
	$row_count2 = db_fetch_array($res_count);
	print " ( <B>$row_count2[count]</B>";
	print " open requests, <B>$row_count[count]</B> total )";
*/
}

// ##################### Patch Manager (only for Active)

if ($project->usesPatch()) {
	print '
		<HR SIZE="1" NoShade>
		<A href="/patch/?group_id='.$group_id.'">';
	html_image("ic/patch.png",array('width'=>'20', 'height'=>'20', 'alt'=>'Patch Manager'));
	print '&nbsp;Patch Manager</A>';
	$res_count = db_query("SELECT count(*) AS count FROM patch WHERE group_id=$group_id");
	$row_count = db_fetch_array($res_count);
	$res_count = db_query("SELECT count(*) AS count FROM patch WHERE group_id=$group_id AND patch_status_id='1'");
	$row_count2 = db_fetch_array($res_count);
	print " ( <B>$row_count2[count]</B>";
	print " open patches, <B>$row_count[count]</B> total )";
}

// ##################### Mailing lists (only for Active)

if ($project->usesMail()) {
	print '<HR SIZE="1" NoShade><A href="/mail/?group_id='.$group_id.'">';
	html_image("ic/mail16b.png",array('width'=>'20', 'height'=>'20', 'alt'=>'Mailing Lists')); 
	print '&nbsp;Mailing Lists</A>';
	$res_count = db_query("SELECT count(*) AS count FROM mail_group_list WHERE group_id=$group_id AND is_public=1");
	$row_count = db_fetch_array($res_count);
	print " ( <B>$row_count[count]</B> public mailing lists )";
}

// ##################### Task Manager (only for Active)

if ($project->usesPm() && !($sys_activate_tracker && !$project->activateOldTask())) {
	print '<HR SIZE="1" NoShade><A href="/pm/?group_id='.$group_id.'">';
	html_image("ic/taskman16b.png",array('width'=>'20', 'height'=>'20', 'alt'=>'Task Manager'));
	print '&nbsp;Project/Task Manager</A>';
	$sql="SELECT * FROM project_group_list WHERE group_id='$group_id' AND is_public=1";
	$result = db_query ($sql);
	$rows = db_numrows($result);
	if (!$result || $rows < 1) {
		echo '<BR><I>There are no public projects available</I>';
	} else {
		for ($j = 0; $j < $rows; $j++) {
			echo '
			<BR> &nbsp; - <A HREF="/pm/task.php?group_project_id='.db_result($result, $j, 'group_project_id').
			'&group_id='.$group_id.'&func=browse">'.db_result($result, $j, 'project_name').'</A>';
		}

	}
}

// ######################### Surveys (only for Active)

if ($project->usesSurvey()) {
	print '<HR SIZE="1" NoShade><A href="/survey/?group_id='.$group_id.'">';
	html_image("ic/survey16b.png",array('width'=>'20', 'height'=>'20', 'alt'=>'Survey'));
	print " Surveys</A>";
	$sql="SELECT count(*) from surveys where group_id='$group_id' AND is_active='1'";
	$result=db_query($sql);
	echo ' ( <B>'.db_result($result,0,0).'</B> surveys )';
}

// ######################### CVS (only for Active)

if ($project->usesCVS()) {
	print '<HR SIZE="1" NoShade><A href="/cvs/?group_id='.$group_id.'">';
	html_image("ic/cvs16b.png",array('width'=>'20', 'height'=>'20', 'alt'=>'CVS'));
	print " CVS Repository</A>";
// LJ Cvs checkouts added 
	$sql = "SELECT SUM(cvs_commits) AS commits, SUM(cvs_adds) AS adds, SUM(cvs_checkouts) AS checkouts from stats_project where group_id='$group_id'";
	$result = db_query($sql);
        $cvs_commit_num=db_result($result,0,0);
        $cvs_add_num=db_result($result,0,1);
        $cvs_co_num=db_result($result,0,2);
        if (!$cvs_commit_num) $cvs_commit_num=0;
        if (!$cvs_add_num) $cvs_add_num=0;
        if (!$cvs_co_num) $cvs_co_num=0;
	$uri = session_make_url('/cgi-bin/viewcvs.cgi/?root='.$project->getUnixName().'&roottype=cvs');

        echo ' ( <B>'.$cvs_commit_num.'</B> commits, <B>'.$cvs_add_num.'</B> adds, <B>'.$cvs_co_num.'</B> checkouts )';
        if ($cvs_commit_num || $cvs_add_num || $cvs_co_num) {

            echo '<br> &nbsp; - <a href="'.$uri.'">Browse CVS</a>';
        }
}

// ######################### Subversion (only for Active)

if ($project->usesService('svn')) {
	print '<HR SIZE="1" NoShade><A href="/svn/?group_id='.$group_id.'">';
	html_image("ic/svn16b.png",array('width'=>'20', 'height'=>'20', 'alt'=>'Subversion'));
	print " Subversion Repository</A>";
	$sql = "SELECT SUM(svn_commits) AS commits, SUM(svn_adds) AS adds, SUM(svn_deletes) AS deletes, SUM(svn_checkouts) AS checkouts from stats_project where group_id='$group_id'";
	$result = db_query($sql);
        $svn_commit_num = db_result($result,0,0);
        $svn_add_num = db_result($result,0,1);
        $svn_del_num = db_result($result,0,2);
        $svn_co_num = db_result($result,0,3);
        if (!$svn_commit_num) $svn_commit_num=0;
        if (!$svn_add_num) $svn_add_num=0;
        if (!$svn_del_num) $svn_del_num=0;
        if (!$svn_co_num) $svn_co_num=0;
	$uri = session_make_url('/cgi-bin/viewcvs.cgi/?root='.$project->getUnixName().'&roottype=svn');

        echo ' ( <B>'.$svn_commit_num.'</B> commits, <B>'.$svn_add_num.'</B> adds, <B>'.$svn_del_num.'</B> deletes, <B>'.$svn_co_num.'</B> checkouts )';
        if ($svn_commit_num || $svn_add_num || $svn_del_num || $svn_co_num) {

            echo '<br> &nbsp; - <a href="'.$uri.'">Browse Subversion</a>';
        }
}

// ######################### File Releases (only for Active)

if ($project->usesFile()) {
	print '<HR SIZE="1" NoShade><A href="/file/showfiles.php?group_id='.$group_id.'">';
	html_image("ic/file.png",array('width'=>'20', 'height'=>'20', 'alt'=>'Files'));
	print " File Releases</A>";
        echo ' ( <B>'.$nb_packages.'</B> packages )';
}

// ######################### Trackers (only for Active)
if ( $project->usesTracker()&&$sys_activate_tracker ) {
	print '<HR SIZE="1" NoShade><A href="/tracker/?group_id='.$group_id.'">';
	html_image("ic/tracker20w.png",array('width'=>'20', 'height'=>'20', 'alt'=>'Trackers'));
	print " Trackers</A>";
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
		echo "<br><i>No Accessible Trackers Found</i>";
	} else {
		for ($j = 0; $j < count($at_arr); $j++) {
			echo '<br><i>-&nbsp;
			<a href="/tracker/?atid='. $at_arr[$j]->getID() .
			'&group_id='.$group_id.'&func=browse">' .
			$at_arr[$j]->getName() .'</a></i>';
		}
	}
}


// ######################## AnonFTP (only for Active)

if ($project->isActive()) {
	print '<HR SIZE="1" NoShade>';
// LJ replace the hardcoded 'sourceforge.net' with the real domain name
// LJ	print "<A href=\"ftp://" . $project->getUnixName() . ".sourceforge.net/pub/". $project->getUnixName() ."/\">";
// LJ

        list($host,$port) = explode(':',$GLOBALS['sys_default_domain']);		
	print "<A href=\"ftp://" . $project->getUnixName() . "." . $host ."/pub/". $project->getUnixName() ."/\">";
	print html_image("ic/ftp16b.png",array('width'=>'20', 'height'=>'20', 'alt'=>'Anonymous FTP Space'));
	print "Anonymous FTP Space</A>";
}

$HTML->box1_bottom();

if ($project->usesNews()) {
	// COLUMN BREAK
	?>

	</TD>
	<TD WIDTH="15">&nbsp;</TD>
	<TD VALIGN="top">

	<?php
	// ############################# Latest News

	echo $HTML->box1_top('Latest News&nbsp;<A href="/export/rss_sfnews.php?group_id='.$group_id.'" title="Latest News - RSS Format">[XML]</A>');

	echo news_show_latest($group_id,10,false);

	echo $HTML->box1_bottom();
}

?>
</TD>

</TR></TABLE>

<?php

site_project_footer(array());

?>
