<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// $Id$

require_once('vars.php');
require_once('www/news/news_utils.php');
require_once('trove.php');
require_once('common/tracker/ArtifactType.class.php');
require_once('common/tracker/ArtifactTypeFactory.class.php');
require_once('common/frs/FileModuleMonitorFactory.class.php');
require_once('common/wiki/lib/Wiki.class.php');
require_once('www/project/admin/permissions.php');

$Language->loadLanguageMsg('include/include');

//make sure this project is NOT a foundry
if ($project->isFoundry()) {
	header ("Location: /foundry/". $project->getUnixName() ."/");
	exit;
}       

$title = $Language->getText('include_project_home','proj_info').' - '. $project->getPublicName();

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
	print '<P>'.$Language->getText('include_project_home','not_official_site',$GLOBALS['sys_name']);
}

// LJ Pointer to more detailed description added
if ($project->getDescription()) {
	print "<P>" . $project->getDescription();
	$details_prompt = '['.$Language->getText('include_project_home','more_info').'...]';
} else {
  print '<P>'.$Language->getText('include_project_home','no_short_desc',"/project/admin/editgroupinfo.php?group_id=$group_id");
	$details_prompt = '['.$Language->getText('include_project_home','other_info').'...]';
}

print '<a href="/project/showdetails.php?group_id='.$group_id.'"> '. $details_prompt .'</a>';

// trove info
print '<BR>&nbsp;<BR>';
trove_getcatlisting($group_id,0,1);
print '<BR>&nbsp;';

print $Language->getText('include_project_home','view_proj_activity',"/project/stats/?group_id=$group_id");

print '</TD><TD NoWrap VALIGN="top">';

if (! $project->hideMembers()) {
    // ########################### Developers on this project
    
    echo $HTML->box1_top($Language->getText('include_project_home','devel_info'));
    ?>
        <?php
              if (db_numrows($res_admin) > 0) {
                  
                  echo '<SPAN CLASS="develtitle">'.$Language->getText('include_project_home','proj_admins').':</SPAN><BR>';
                  while ($row_admin = db_fetch_array($res_admin)) {
                      print '<A href="/users/'.$row_admin['user_name'].'/">'.user_get_name_display_from_id($row_admin['user_id']).'</A><BR>';
                  }
                  ?>
                      <HR WIDTH="100%" SIZE="1" NoShade>
                           <?php
                           
                           }


    echo '<SPAN CLASS="develtitle">'.$Language->getText('include_project_home','devels').':</SPAN><BR>';
    
    //count of developers on this project
    $res_count = db_query("SELECT user_id FROM user_group WHERE group_id=$group_id");
    print db_numrows($res_count);


    echo ' <A HREF="/project/memberlist.php?group_id='.$group_id.'">['.$Language->getText('include_project_home','view_members').']</A>';


    echo $HTML->box1_bottom();
 } else {
    print "&nbsp;";
 }

print '
</TD></TR>
</TABLE>
<P>
';


// ############################# File Releases

if ($project->usesFile()) {
    if ($spc = $project->services['file']->getSummaryPageContent()) {
        echo $HTML->box1_top($spc['title']); 
        echo $spc['content'];
        echo $HTML->box1_bottom();
    }
}
?>
<P>
<TABLE WIDTH="100%" BORDER="0" CELLPADDING="0" CELLSPACING="0">
<TR><TD VALIGN="top">

<?php

// ############################## PUBLIC AREAS
echo $HTML->box1_top($Language->getText('include_project_home','public_areas')); 

// ################# Homepage Link

if ($project->usesHomePage()) {
    print "<A ";
    if (substr($project->getHomePage(), 0, 1)!="/") {
        // Absolute link -> open new window on click
        print "target=_blank ";
    }
    print 'href="' . $project->getHomePage() . '">';
    html_image("ic/home16b.png",array('width'=>'20', 'height'=>'20', 'alt'=>$Language->getText('include_project_home','homepage')));
    print '&nbsp;'.$Language->getText('include_project_home','proj_home').'</A>';
}

// ################## forums

if ($project->usesForum()) {
	print '<HR SIZE="1" NoShade><A href="/forum/?group_id='.$group_id.'">';
	html_image("ic/notes16.png",array('width'=>'20', 'height'=>'20', 'alt'=>$Language->getText('include_project_home','public_forums'))); 
	print '&nbsp;'.$Language->getText('include_project_home','public_forums').'</A>';
	$res_count = db_query("SELECT count(forum.msg_id) AS count FROM forum,forum_group_list WHERE "
		. "forum_group_list.group_id=$group_id AND forum.group_forum_id=forum_group_list.group_forum_id "
		. "AND forum_group_list.is_public=1");
	$row_count = db_fetch_array($res_count);
	print ' ( '.$Language->getText('include_project_home','msg',$row_count['count']).' ';

	$res_count = db_query("SELECT count(*) AS count FROM forum_group_list WHERE group_id=$group_id "
		. "AND is_public=1");
	$row_count = db_fetch_array($res_count);
	print $Language->getText('include_project_home','forums',$row_count['count'])." )\n";
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

// ##################### Bug tracking 

if ($project->usesBugs()) {
	print '<HR SIZE="1" NoShade><A href="/bugs/?group_id='.$group_id.'">';
	html_image("ic/bug16b.png",array('width'=>'20', 'height'=>'20', 'alt'=>$Language->getText('include_project_home','bug_track'))); 
	print '&nbsp;'.$Language->getText('include_project_home','bug_track').'</A>';
	$res_count = db_query("SELECT count(*) AS count FROM bug WHERE group_id=$group_id AND status_id != 3");
	$row_count = db_fetch_array($res_count);
	print " ( <B>$row_count[count]</B>";
	$res_count = db_query("SELECT count(*) AS count FROM bug WHERE group_id=$group_id");
	$row_count = db_fetch_array($res_count);
	print ' '.$Language->getText('include_project_home','open_bugs').', '.$Language->getText('include_project_home','total',$row_count['count']).' )';
}

// ##################### Support Manager (only for Active)
 
if ($project->usesSupport()) {
	print '
	<HR SIZE="1" NoShade>
	<A href="/support/?group_id='.$group_id.'">';
	html_image("ic/support16b.jpg",array('width'=>'20', 'height'=>'20', 'alt'=>$Language->getText('include_project_home','supp_manager')));
	print '&nbsp;'.$Language->getText('include_project_home','tech_supp_manager').'</A>';
	$res_count = db_query("SELECT count(*) AS count FROM support WHERE group_id=$group_id");
	$row_count = db_fetch_array($res_count);
	$res_count = db_query("SELECT count(*) AS count FROM support WHERE group_id=$group_id AND support_status_id='1'");
	$row_count2 = db_fetch_array($res_count);
	print ' ( '.$Language->getText('include_project_home','open_requ', $row_count2['count']).', '.$Language->getText('include_project_home','open_requ', $row_count['count']).' )';
}

// ##################### Doc Manager (only for Active)

if ($project->usesDocman()) {
	print '
	<HR SIZE="1" NoShade>
	<A href="/docman/?group_id='.$group_id.'">';
	html_image("ic/docman16b.png",array('width'=>'20', 'height'=>'20', 'alt'=>$Language->getText('include_project_home','doc')));
	print '&nbsp;'.$Language->getText('include_project_home','doc_man').'</A>';
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
	html_image("ic/patch.png",array('width'=>'20', 'height'=>'20', 'alt'=>$Language->getText('include_project_home','patch_manager')));
	print '&nbsp;'.$Language->getText('include_project_home','patch_manager').'</A>';
	$res_count = db_query("SELECT count(*) AS count FROM patch WHERE group_id=$group_id");
	$row_count = db_fetch_array($res_count);
	$res_count = db_query("SELECT count(*) AS count FROM patch WHERE group_id=$group_id AND patch_status_id='1'");
	$row_count2 = db_fetch_array($res_count);
	print ' ( '.$Language->getText('include_project_home','open_patches',$row_count2['count']).', '.$Language->getText('include_project_home','total',$row_count['count']).' )';
}

// ##################### Mailing lists (only for Active)

if ($project->usesMail()) {
	print '<HR SIZE="1" NoShade><A href="/mail/?group_id='.$group_id.'">';
	html_image("ic/mail16b.png",array('width'=>'20', 'height'=>'20', 'alt'=>$Language->getText('include_project_home','mail_lists'))); 
	print '&nbsp;'.$Language->getText('include_project_home','mail_lists').'</A>';
	$res_count = db_query("SELECT count(*) AS count FROM mail_group_list WHERE group_id=$group_id AND is_public=1");
	$row_count = db_fetch_array($res_count);
	print ' ( '.$Language->getText('include_project_home','public_mail_lists',$row_count['count']).' )';
}

// ##################### Task Manager (only for Active)

if ($project->usesPm()) {
	print '<HR SIZE="1" NoShade><A href="/pm/?group_id='.$group_id.'">';
	html_image("ic/taskman16b.png",array('width'=>'20', 'height'=>'20', 'alt'=>$Language->getText('include_project_home','task_manager')));
	print '&nbsp;'.$Language->getText('include_project_home','proj_task_man').'</A>';
	$sql="SELECT * FROM project_group_list WHERE group_id='$group_id' AND is_public=1";
	$result = db_query ($sql);
	$rows = db_numrows($result);
	if (!$result || $rows < 1) {
		echo '<BR><I>'.$Language->getText('include_project_home','no_public_proj').'</I>';
	} else {
		for ($j = 0; $j < $rows; $j++) {
			echo '
			<BR> &nbsp; - <A HREF="/pm/task.php?group_project_id='.db_result($result, $j, 'group_project_id').
			'&group_id='.$group_id.'&func=browse">'.db_result($result, $j, 'project_name').'</A>';
		}

	}
}

// ######################### Wiki (only for Active)

if ($project->usesWiki()) {
	print '<HR SIZE="1" NoShade><A href="/wiki/?group_id='.$group_id.'">';
	html_image("ic/wiki.png",array('width'=>'18', 'height'=>'12', 'alt'=>$Language->getText('include_project_home','wiki')));
	print ' '.$Language->getText('include_project_home','wiki').'</A>';
        $wiki=new Wiki($group_id);
	echo ' ( '.$Language->getText('include_project_home','nb_wiki_pages',$wiki->getProjectPageCount()).' )';
}

// ######################### Surveys (only for Active)

if ($project->usesSurvey()) {
	print '<HR SIZE="1" NoShade><A href="/survey/?group_id='.$group_id.'">';
	html_image("ic/survey16b.png",array('width'=>'20', 'height'=>'20', 'alt'=>$Language->getText('include_project_home','surveys')));
	print ' '.$Language->getText('include_project_home','surveys').'</A>';
	$sql="SELECT count(*) from surveys where group_id='$group_id' AND is_active='1'";
	$result=db_query($sql);
	echo ' ( '.$Language->getText('include_project_home','nb_surveys',db_result($result,0,0)).' )';
}

// ######################### CVS (only for Active)

if ($project->usesCVS()) {
	print '<HR SIZE="1" NoShade><A href="/cvs/?group_id='.$group_id.'">';
	html_image("ic/cvs16b.png",array('width'=>'20', 'height'=>'20', 'alt'=>'CVS'));
	print ' '.$Language->getText('include_project_home','cvs_repo').'</A>';
// LJ Cvs checkouts added 
	$sql = "SELECT SUM(cvs_commits) AS commits, SUM(cvs_adds) AS adds, SUM(cvs_checkouts) AS checkouts from stats_project where group_id='$group_id'";
	$result = db_query($sql);
        $cvs_commit_num=db_result($result,0,0);
        $cvs_add_num=db_result($result,0,1);
        $cvs_co_num=db_result($result,0,2);
        if (!$cvs_commit_num) $cvs_commit_num=0;
        if (!$cvs_add_num) $cvs_add_num=0;
        if (!$cvs_co_num) $cvs_co_num=0;
	$uri = session_make_url('/cvs/viewvc.php/?root='.$project->getUnixName().'&roottype=cvs');

        echo ' ( '.$Language->getText('include_project_home','commits',$cvs_commit_num).', '.$Language->getText('include_project_home','adds',$cvs_add_num).', '.$Language->getText('include_project_home','co',$cvs_co_num).' )';
        if ($cvs_commit_num || $cvs_add_num || $cvs_co_num) {

            echo '<br> &nbsp; - <a href="'.$uri.'">'.$Language->getText('include_project_home','browse_cvs').'</a>';
        }
}

// ######################### Subversion (only for Active)

if ($project->usesService('svn')) {
	print '<HR SIZE="1" NoShade><A href="/svn/?group_id='.$group_id.'">';
	html_image("ic/svn16b.png",array('width'=>'20', 'height'=>'20', 'alt'=>'Subversion'));
	print ' '.$Language->getText('include_project_home','svn_repo').'</A>';
	$sql = "SELECT SUM(svn_access_count) AS accesses from group_svn_full_history where group_id='$group_id'";
	$result = db_query($sql);
        $svn_accesses = db_result($result,0,0);
        if (!$svn_accesses) $svn_accesses=0;

        echo ' ( '.$Language->getText('include_project_home','accesses',$svn_accesses).' )';
        if ($svn_accesses) {
	    $uri = session_make_url('/svn/viewvc.php/?root='.$project->getUnixName().'&roottype=svn');
            echo '<br> &nbsp; - <a href="'.$uri.'">'.$Language->getText('include_project_home','browse_svn').'</a>';
        }
}

// ######################### File Releases (only for Active)

if ($project->usesFile()) {
    echo $project->services['file']->getPublicArea();
}

// ######################### Trackers (only for Active)
if ( $project->usesTracker() ) {
	print '<HR SIZE="1" NoShade><A href="/tracker/?group_id='.$group_id.'">';
	html_image("ic/tracker20w.png",array('width'=>'20', 'height'=>'20', 'alt'=>$Language->getText('include_project_home','trackers')));
	print ' '.$Language->getText('include_project_home','trackers').'</A>';
	//	  
	//  get the Group object
	//	  
	$group = group_get_object($group_id);
	if (!$group || !is_object($group) || $group->isError()) {
		exit_no_group();
	}		   
	$atf = new ArtifactTypeFactory($group);
	if (!$group || !is_object($group) || $group->isError()) {
		exit_error($Language->getText('global','error'),$Language->getText('include_project_home','no_arttypefact'));
	}
	
	// Get the artfact type list
	$at_arr = $atf->getArtifactTypes();
	
	if (!$at_arr || count($at_arr) < 1) {
		echo '<br><i>'.$Language->getText('include_project_home','no_trackers_accessible').'</i>';
	} else {
		for ($j = 0; $j < count($at_arr); $j++) {
                    if ($at_arr[$j]->userCanView()) {
			echo '<br><i>-&nbsp;
			<a href="/tracker/?atid='. $at_arr[$j]->getID() .
                            '&group_id='.$group_id.'&func=browse">' .
                            $at_arr[$j]->getName() .'</a></i>';
                    }
		}
	}
}


// ######################## AnonFTP (only for Active)

if ($project->isActive()) {
	print '<HR SIZE="1" NoShade>';

        list($host,$port) = explode(':',$GLOBALS['sys_default_domain']);		
	print "<A href=\"ftp://" . $project->getUnixName() . "." . $host ."/pub/". $project->getUnixName() ."/\">";
	print html_image("ic/ftp16b.png",array('width'=>'20', 'height'=>'20', 'alt'=>$Language->getText('include_project_home','anon_ftp_space')));
	print $Language->getText('include_project_home','anon_ftp_space').'</A>';
}

// ######################## Plugins

$areas = array();
$params = array('project' => &$project, 'areas' => &$areas);

$em =& EventManager::instance();
$em->processEvent('service_public_areas', $params);

foreach($areas as $area) {
    print '<HR SIZE="1" NoShade>';
    print $area;
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

				 echo $HTML->box1_top($Language->getText('include_project_home','latest_news').'&nbsp;<A href="/export/rss_sfnews.php?group_id='.$group_id.'" title="'.$Language->getText('include_project_home','latest_news').' - '.$Language->getText('include_features_boxes','rss_format').'">['.$Language->getText('include_features_boxes','xml').']</A>');

	echo news_show_latest($group_id,10,false);

	echo $HTML->box1_bottom();
}

?>
</TD>

</TR></TABLE>

<?php

site_project_footer(array());

?>
