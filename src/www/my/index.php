<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// $Id: index.php 5857 2007-04-17 09:20:24 +0000 (Tue, 17 Apr 2007) nterray $

require_once('pre.php');
require('./my_utils.php');
require_once('common/survey/SurveySingleton.class.php');
require_once('common/tracker/Artifact.class.php');
require_once('common/tracker/ArtifactFile.class.php');
require_once('common/tracker/ArtifactType.class.php');
require_once('common/tracker/ArtifactCanned.class.php');
require_once('common/tracker/ArtifactTypeFactory.class.php');
require_once('common/tracker/ArtifactField.class.php');
require_once('common/tracker/ArtifactFieldFactory.class.php');
require_once('common/tracker/ArtifactReportFactory.class.php');
require_once('common/tracker/ArtifactReport.class.php');
require_once('common/tracker/ArtifactReportField.class.php');
require_once('common/tracker/ArtifactFactory.class.php');
require_once('common/event/EventManager.class.php');

$Language->loadLanguageMsg('my/my');
$em =& EventManager::instance();

// define undefined vars
if (!isset($hide_item_id)) {
    $hide_item_id = '';
}
if (!isset($hide_forum)) {
    $hide_forum = '';
}
if (!isset($hide_bug)) {
    $hide_bug = '';
}
//
if (user_isloggedin()) {

    // If it's super user and license terms have not yet been agreed then redirect
    // to license agreement page
    if (user_is_super_user() && !license_already_displayed()) {
	session_redirect("/admin/approve_license.php");
    }

        // Make sure this page is not cached because
        // it uses the exact same URL for all user's
        // personal page
        header("Cache-Control: no-cache, must-revalidate"); // for HTTP 1.1
        header("Pragma: no-cache");  // for HTTP 1.0
	
        if (browser_is_netscape4()) {
            $feedback.= $Language->getText('my_index', 'err_badbrowser');
        }
	$title = $Language->getText('my_index', 'title', array(user_getrealname(user_getid()).' ('.user_getname().')'));
    
    site_header(array('title'=>$title));
	?>

    <span class="small">
	 <H3>
         <?php echo $title.'&nbsp;'.help_button('LoginAndPersonalPage.html'); ?>
         </H3>
        <p>
	<?php
         echo $Language->getText('my_index', 'message');

	$atf = new ArtifactTypeFactory(false);
	if ( !$atf ) {
	    exit_error($Language->getText('include_exit', 'error'),
		       $Language->getText('my_index', 'err_artf'));
	}

	/*
		Bugs assigned to or submitted by this person
	*/
	
	$html_my_bugs = "";
	
	$sql='SELECT group_id,COUNT(bug_id) '.
		'FROM bug '.
		'WHERE status_id <> 3 '.
		'AND (assigned_to='.user_getid().
		' OR submitted_by='.user_getid().') GROUP BY group_id ORDER BY group_id ASC LIMIT 100';

	$result=db_query($sql);
	$rows=db_numrows($result);
	
	if ($result && $rows >= 1) {

		$html_my_bugs .= $HTML->box1_top($Language->getText('my_index', 'my_bugs'),0);

	    for ($j=0; $j<$rows; $j++) {

			$group_id = db_result($result,$j,'group_id');
	
			$sql2='SELECT bug_id,severity,assigned_to,submitted_by,date AS open_date,summary '.
			'FROM bug '.
			'WHERE group_id='.$group_id.' AND status_id <> 3 '.
			'AND (assigned_to='.user_getid().
			' OR submitted_by='.user_getid().') LIMIT 100';
	
			$result2 = db_query($sql2);
			$rows2 = db_numrows($result2);
	
			list($hide_now,$count_diff,$hide_url) = 
			    my_hide_url('bug',$group_id,$hide_item_id,$rows2,$hide_bug);
			$html_hdr = ($j ? '<tr class="boxitem"><td colspan="2">' : '').
			    $hide_url.'<A HREF="/bugs/?group_id='.$group_id.'"><B>'.
			    group_getname($group_id).'</B></A>&nbsp;&nbsp;&nbsp;&nbsp;';
			$html = '';
			$count_new = max(0, $count_diff);
			for ($i=0; $i<$rows2; $i++) {
	
			    if (!$hide_now) {
					// Form the 'Submitted by/Assigned to flag' for marking
					$AS_flag = my_format_as_flag(db_result($result2,$i,'assigned_to'), db_result($result2,$i,'submitted_by'));
		
					$html .= '
					
					<TR class="'.get_priority_color(db_result($result2,$i,'severity')).
					'"><TD class="small"><A HREF="/bugs/?func=detailbug&group_id='.
					$group_id.'&bug_id='.db_result($result2,$i,'bug_id').
					'">'.db_result($result2,$i,'bug_id').'</A></TD>'.
					'<TD class="small">'.stripslashes(db_result($result2,$i,'summary')).'&nbsp;'.$AS_flag.'</TD></TR>';
	
			    }
			}
	
			$html_hdr .= my_item_count($rows2,$count_new).'</td></tr>';
			$html_my_bugs .= $html_hdr.$html;
	    }

	    $html_my_bugs .= '<TR><TD COLSPAN="2">&nbsp;</TD></TR>';
		$html_my_bugs .= $HTML->box1_bottom(0);
	}

	/*
		SRs assigned to or submitted by this person
	*/
	$html_my_srs = "";

	$sql='SELECT group_id FROM support '.
	    'WHERE support_status_id = 1 '.
	    'AND (assigned_to='.user_getid().
	    ' OR submitted_by='.user_getid().') GROUP BY group_id ORDER BY group_id ASC LIMIT 100';

	$result=db_query($sql);
	$rows=db_numrows($result);
	if ($result && $rows >= 1) {

		$html_my_srs .= $HTML->box1_top($Language->getText('my_index', 'my_srs'),0);
	    for ($j=0; $j<$rows; $j++) {

			$group_id = db_result($result,$j,'group_id');
	
			$sql2="SELECT support_id,priority,assigned_to,submitted_by,open_date,summary ".
			    "FROM support ".
			    "WHERE group_id='$group_id' AND support_status_id = '1' ".
			    "AND (assigned_to='".user_getid()."' ".
			    "OR submitted_by='".user_getid()."') LIMIT 100";
			    
			$result2 = db_query($sql2);
			$rows2 = db_numrows($result2);
	
			list($hide_now,$count_diff,$hide_url) = 
			    my_hide_url('sr',$group_id,$hide_item_id,$rows2,$hide_sr);
	
			$html_hdr = ($j ? '<tr class="boxitem"><td colspan="2">' : '').
			    $hide_url.'<A HREF="/support/?group_id='.$group_id.'"><B>'.
			    group_getname($group_id).'</B></A>&nbsp;&nbsp;&nbsp;&nbsp;';
	
			$html = ''; $count_new = max(0, $count_diff);
			for ($i=0; $i<$rows2; $i++) {
				
			    if (!$hide_now) {
				// Form the 'Submitted by/Assigned to flag' for marking
				$AS_flag = my_format_as_flag(db_result($result2,$i,'assigned_to'), db_result($result2,$i,'submitted_by'));
	
				$html .= '
				<TR class="'.get_priority_color(db_result($result2,$i,'priority')).
				'"><TD class="small"><A HREF="/support/?func=detailsupport&group_id='.
				$group_id.'&support_id='.db_result($result2,$i,'support_id').
				'">'.db_result($result2,$i,'support_id').'</A></TD>'.
				'<TD class="small">'.stripslashes(db_result($result2,$i,'summary')).'&nbsp;'.$AS_flag.'</TD></TR>';
			    }
			}
	
			$html_hdr .= my_item_count($rows2,$count_new).'</td></tr>';
			$html_my_srs .= $html_hdr.$html;
	    }

	    $html_my_srs .= '<TR><TD COLSPAN="2">&nbsp;</TD></TR>';
		$html_my_srs .= $HTML->box1_bottom(0);
	}

	/*
		Forums that are actively monitored
	*/
	$html_my_monitored_forums = "";
	$html_my_monitored_forums .= $HTML->box1_top($Language->getText('my_index', 'my_forums'),0);

	$sql="SELECT groups.group_id, groups.group_name ".
	     "FROM groups,forum_group_list,forum_monitored_forums ".
	     "WHERE groups.group_id=forum_group_list.group_id ".
	     "AND groups.status = 'A' ".
         "AND forum_group_list.is_public <> 9 ".
	     "AND forum_group_list.group_forum_id=forum_monitored_forums.forum_id ".
	     "AND forum_monitored_forums.user_id='".user_getid()."' GROUP BY group_id ORDER BY group_id ASC LIMIT 100";

	$result=db_query($sql);
	$rows=db_numrows($result);
	if (!$result || $rows < 1) {
		$html_my_monitored_forums .= $Language->getText('my_index', 'my_forums_msg');
		$html_my_monitored_forums .= db_error();
	} else {

	    for ($j=0; $j<$rows; $j++) {

		$group_id = db_result($result,$j,'group_id');

		$sql2="SELECT forum_group_list.group_forum_id,forum_group_list.forum_name ".
		    "FROM groups,forum_group_list,forum_monitored_forums ".
		    "WHERE groups.group_id=forum_group_list.group_id ".
		    "AND groups.group_id=$group_id ".
            "AND forum_group_list.is_public <> 9 ".
		    "AND forum_group_list.group_forum_id=forum_monitored_forums.forum_id ".
		    "AND forum_monitored_forums.user_id='".user_getid()."' LIMIT 100";

		$result2 = db_query($sql2);
		$rows2 = db_numrows($result2);

		list($hide_now,$count_diff,$hide_url) = 
		    my_hide_url('forum',$group_id,$hide_item_id,$rows2,$hide_forum);

		$html_hdr = ($j ? '<tr class="boxitem"><td colspan="2">' : '').
		    $hide_url.'<A HREF="/forum/?group_id='.$group_id.'"><B>'.
		    db_result($result,$j,'group_name').'</B></A>&nbsp;&nbsp;&nbsp;&nbsp;';

		$html = '';
		$count_new = max(0, $count_diff);
		for ($i=0; $i<$rows2; $i++) {

		    if (!$hide_now) {

			$group_forum_id = db_result($result2,$i,'group_forum_id');
			$html .= '
			<TR class="'. util_get_alt_row_color($i) .'"><TD WIDTH="99%">'.
			    '&nbsp;&nbsp;&nbsp;-&nbsp;<A HREF="/forum/forum.php?forum_id='.$group_forum_id.'">'.
			    stripslashes(db_result($result2,$i,'forum_name')).'</A></TD>'.
			    '<TD ALIGN="center"><A HREF="/forum/monitor.php?forum_id='.$group_forum_id.
			    '" onClick="return confirm(\''.$Language->getText('my_index', 'stop_forum').'\')">'.
			    '<IMG SRC="'.util_get_image_theme("ic/trash.png").'" HEIGHT="16" WIDTH="16" '.
			    'BORDER=0 ALT="'.$Language->getText('my_index', 'stop_monitor').'"></A></TD></TR>';
		    }
		}

		$html_hdr .= my_item_count($rows2,$count_new).'</td></tr>';
		$html_my_monitored_forums .= $html_hdr.$html;
	    }

	}
    $html_my_monitored_forums .= '<TR><TD COLSPAN="2">&nbsp;</TD></TR>';
	$html_my_monitored_forums .= $HTML->box1_bottom(0);

	/*
		Filemodules that are actively monitored
	*/

	$html_my_monitored_fp = "";
	$html_my_monitored_fp .= $HTML->box1_top($Language->getText('my_index', 'my_files'),0);
	$sql="SELECT groups.group_name,groups.group_id ".
		"FROM groups,filemodule_monitor,frs_package ".
		"WHERE groups.group_id=frs_package.group_id ".
		"AND frs_package.package_id=filemodule_monitor.filemodule_id ".
		"AND filemodule_monitor.user_id='".user_getid()."' GROUP BY group_id ORDER BY group_id ASC LIMIT 100";

	$result=db_query($sql);
	$rows=db_numrows($result);
	if (!$result || $rows < 1) {
		$html_my_monitored_fp .= $Language->getText('my_index', 'my_files_msg');
		$html_my_monitored_fp .= db_error();
	} else {
	    for ($j=0; $j<$rows; $j++) {

		$group_id = db_result($result,$j,'group_id');

		$sql2="SELECT frs_package.name,filemodule_monitor.filemodule_id ".
		    "FROM groups,filemodule_monitor,frs_package ".
		    "WHERE groups.group_id=frs_package.group_id ".
		    "AND groups.group_id=$group_id ".
		    "AND frs_package.package_id=filemodule_monitor.filemodule_id ".
		    "AND filemodule_monitor.user_id='".user_getid()."'  LIMIT 100";
		$result2 = db_query($sql2);
		$rows2 = db_numrows($result2);

        if (!isset($hide_frs)) $hide_frs = null;
        list($hide_now,$count_diff,$hide_url) = 
		    my_hide_url('frs',$group_id,$hide_item_id,$rows2,$hide_frs);

		$html_hdr = ($j ? '<tr class="boxitem"><td colspan="2">' : '').
		    $hide_url.'<A HREF="/project/?group_id='.$group_id.'"><B>'.
		    db_result($result,$j,'group_name').'</B></A>&nbsp;&nbsp;&nbsp;&nbsp;';

		$html = '';
		$count_new = max(0, $count_diff);		
		for ($i=0; $i<$rows2; $i++) {

		    if (!$hide_now) {

			$html .='
			<TR class="'. util_get_alt_row_color($i) .'">'.
			    '<TD WIDTH="99%">-&nbsp;&nbsp;<A HREF="/file/showfiles.php?group_id='.$group_id.'">'.
			    db_result($result2,$i,'name').'</A></TD>'.
			    '<TD><A HREF="/file/filemodule_monitor.php?filemodule_id='.
			    db_result($result2,$i,'filemodule_id').
			    '" onClick="return confirm(\''.$Language->getText('my_index', 'stop_file').'\')">'.
			    '<IMG SRC="'.util_get_image_theme("ic/trash.png").'" HEIGHT="16" WIDTH="16" '.
			    'BORDER=0" ALT="'.$Language->getText('my_index', 'stop_monitor').'"></A></TD></TR>';
		    }
		}
		
		$html_hdr .= my_item_count($rows2,$count_new).'</td></tr>';
		$html_my_monitored_fp .= $html_hdr.$html;
	    }

	}
    $html_my_monitored_fp .= '<TR><TD COLSPAN="2">&nbsp;</TD></TR>';
	$html_my_monitored_fp .= $HTML->box1_bottom(0);

	/*
		Tasks assigned to me
	*/
	$html_my_tasks = "";
	$last_group=0;

	$sql = 'SELECT groups.group_id, groups.group_name, project_group_list.group_project_id, project_group_list.project_name '.
	    'FROM groups,project_group_list,project_task,project_assigned_to '.
	    'WHERE project_task.project_task_id=project_assigned_to.project_task_id '.
	    'AND project_assigned_to.assigned_to_id='.user_getid().
	    ' AND project_task.status_id=1 AND project_group_list.group_id=groups.group_id '.
	    "AND project_group_list.is_public!='9' ".
	  'AND project_group_list.group_project_id=project_task.group_project_id GROUP BY group_id,group_project_id';


	$result=db_query($sql);
	$rows=db_numrows($result);

	if ($result && $rows >= 1) {

		$html_my_tasks .= $HTML->box1_top($Language->getText('my_index', 'my_tasks'),0,'',3);
	
	    for ($j=0; $j<$rows; $j++) {

		$group_id = db_result($result,$j,'group_id');
		$group_project_id = db_result($result,$j,'group_project_id');

		$sql2 = 'SELECT project_task.project_task_id, project_task.priority, project_task.summary,project_task.percent_complete '.
		    'FROM groups,project_group_list,project_task,project_assigned_to '.
		    'WHERE project_task.project_task_id=project_assigned_to.project_task_id '.
		    "AND project_assigned_to.assigned_to_id='".user_getid()."' AND project_task.status_id='1'  ".
		    'AND project_group_list.group_id=groups.group_id '.
		    "AND groups.group_id=$group_id ".
		    'AND project_group_list.group_project_id=project_task.group_project_id '.
		    "AND project_group_list.is_public!='9' ".
		   "AND project_group_list.group_project_id= $group_project_id LIMIT 100";


		$result2 = db_query($sql2);
		$rows2 = db_numrows($result2);

		list($hide_now,$count_diff,$hide_url) = 
		    my_hide_url('pm',$group_project_id,$hide_item_id,$rows2,$hide_pm);

		$html_hdr = ($j ? '<tr class="boxitem"><td colspan="3">' : '').
		    $hide_url.'<A HREF="/pm/task.php?group_id='.$group_id.
		    '&group_project_id='.$group_project_id.'"><B>'.
		    db_result($result,$j,'group_name').' - '.
		    db_result($result,$j,'project_name').'</B></A>&nbsp;&nbsp;&nbsp;&nbsp;';
		$html = '';
		$count_new = max(0, $count_diff);
		for ($i=0; $i<$rows2; $i++) {
			
		    if (!$hide_now) {

			$html .= '
			<TR class="'.get_priority_color(db_result($result2,$i,'priority')).
			    '"><TD class="small"><A HREF="/pm/task.php/?func=detailtask&project_task_id='.
			    db_result($result2, $i, 'project_task_id').'&group_id='.
			    $group_id.'&group_project_id='.$group_project_id.
			    '">'.db_result($result2,$i,'project_task_id').'</A></TD>'.
			    '<TD class="small">'.stripslashes(db_result($result2,$i,'summary')).'</TD>'.
			    '<TD class="small">'.(db_result($result2,$i,'percent_complete')-1000).'%</TD></TR>';

		    }
		}

		$html_hdr .= my_item_count($rows2,$count_new).'</td></tr>';
		$html_my_tasks .= $html_hdr.$html;
	    }


	    $html_my_tasks .= '<TR><TD COLSPAN="3">&nbsp;</TD></TR>';
		$html_my_tasks .= $HTML->box1_bottom(0);
	}


	/*
		Artifact assigned to or submitted by this person
	*/
	$html_my_artifacts = "";

	// Trackers
	$uid = user_getid();
    
    $_artifact_show = user_get_preference('my_artifacts_show');
    if($_artifact_show === false) {
        $_artifact_show = 'AS';
        user_set_preference('my_artifacts_show', $_artifact_show);
    }
    else {
        if (isset($_GET['show'])) {
            switch($_GET['show']) {
                case 'A':
                    $_artifact_show = 'A';
                    break;
                case 'S':
                    $_artifact_show = 'S';
                    break;
                case 'N':
                    $_artifact_show = 'N';
                    break;
                case 'AS':
                default:
                    $_artifact_show = 'AS';
            }
            user_set_preference('my_artifacts_show', $_artifact_show);
        }
    }
    
	$my_artifacts = $atf->getMyArtifacts($uid, $_artifact_show);
	
    $my_artifact_title = '';
    
    $my_artifact_title .= $Language->getText('my_index', 'my_arts').'&nbsp;&nbsp;';
    
    $my_artifact_title .= '<select name="show" onchange="this.form.submit()">';
    $my_artifact_title .= '<option value="N"  '.($_artifact_show === 'N'?'selected="selected"':'').'>'.$Language->getText('my_index', 'no_info');
    $my_artifact_title .= '<option value="A"  '.($_artifact_show === 'A'?'selected="selected"':'').'>'.$Language->getText('my_index', 'a_info');
    $my_artifact_title .= '<option value="S"  '.($_artifact_show === 'S'?'selected="selected"':'').'>'.$Language->getText('my_index', 's_info');
    $my_artifact_title .= '<option value="AS" '.($_artifact_show === 'AS'?'selected="selected"':'').'>'.$Language->getText('my_index', 'as_info');
    $my_artifact_title .= '</select>';
    
    $my_artifact_title .= '<noscript>&nbsp;<input type="submit" value="Change" /></noscript>';
    
    $html_my_artifacts  = '<form name="my_select_showed_artifact" method="GET" action="'.$_SERVER['PHP_SELF'].'">';
    $html_my_artifacts .= $HTML->box1_top($my_artifact_title,0,'',3);
    if (db_numrows($my_artifacts) > 0) {
        $html_my_artifacts .= display_artifacts($my_artifacts, 0);
    }
    $html_my_artifacts .= '<TR><TD COLSPAN="3">'.(($_artifact_show == 'N' || db_numrows($my_artifacts) > 0)?'&nbsp;':$Language->getText('global', 'none')).'</TD></TR>';
    $html_my_artifacts .= $HTML->box1_bottom(0);
    $html_my_artifacts .= '</form>';
	/*
		DEVELOPER SURVEYS

		This needs to be updated manually to display any given survey
                Default behavior: get first survey from group #1 
	*/


        // Get id and title of the survey that will be promoted to user page. default = survey whose id=1
	if ($GLOBALS['sys_my_page_survey']) {
	    $developer_survey_id=$GLOBALS['sys_my_page_survey'];	
	} else {
	    $developer_survey_id="1";
	}
	
	$survey =& SurveySingleton::instance();
        $sql="SELECT * from surveys WHERE survey_id=".$developer_survey_id;
	$result=db_query($sql);
        $group_id=db_result($result,0,'group_id');
	$survey_title=$survey->getSurveyTitle(db_result($result, 0, 'survey_title'));
        
	// Check that the survey is active
        $devsurvey_is_active=db_result($result,0,'is_active');

        $html_my_survey = "";
        if ($devsurvey_is_active==1) {

            $sql="SELECT * FROM survey_responses ".
		"WHERE survey_id='".$developer_survey_id."' AND user_id='".user_getid()."'";
            $result=db_query($sql);
	    
            if (db_numrows($result) < 1) {
		$html_my_survey .= $HTML->box1_top($Language->getText('my_index', 'my_survey'),0);
		$html_my_survey .= '<A HREF="http://'.$GLOBALS['sys_default_domain'].'/survey/survey.php?group_id='.$group_id.'&survey_id='.$developer_survey_id.'">'.$survey_title.'</A>';
		$html_my_survey .= '<TR align=left><TD COLSPAN="2">&nbsp;</TD></TR>';
                $html_my_survey .= $HTML->box1_bottom(0);
            }             
        }


	/*
	       Personal bookmarks
	*/
	$html_my_bookmarks = "";
	$html_my_bookmarks .= $HTML->box1_top($Language->getText('my_index', 'my_bookmarks'),0);

	$result = db_query("SELECT bookmark_url, bookmark_title, bookmark_id from user_bookmarks where ".
		"user_id='". user_getid() ."' ORDER BY bookmark_title");
	$rows=db_numrows($result);
	if (!$result || $rows < 1) {
                $html_my_bookmarks .= $Language->getText('my_index', 'no_bookmark');
		$html_my_bookmarks .= db_error();
	} else {

		for ($i=0; $i<$rows; $i++) {
		    $html_my_bookmarks .= '<TR class="'. util_get_alt_row_color($i) .'"><TD>';
		    $html_my_bookmarks .= '
                                           <B><A HREF="'. db_result($result,$i,'bookmark_url') .'">'.
			db_result($result,$i,'bookmark_title') .'</A></B> '.
			'<SMALL><A HREF="/my/bookmark_edit.php?bookmark_id='. db_result($result,$i,'bookmark_id') .'">['.$Language->getText('my_index', 'edit_link').']</A></SMALL></TD>'.
			'<td><A HREF="/my/bookmark_delete.php?bookmark_id='. db_result($result,$i,'bookmark_id') .
			'" onClick="return confirm(\''.$Language->getText('my_index', 'del_bookmark').'\')">'.
			'<IMG SRC="'.util_get_image_theme("ic/trash.png").'" HEIGHT="16" WIDTH="16" BORDER="0" ALT="DELETE"></A>	</td></tr>';
			}
	}
	$html_my_bookmarks .= '<TR align=left><TD COLSPAN="2">&nbsp;</TD></TR>';
	$html_my_bookmarks .= $HTML->box1_bottom(0);

	/*
		PROJECT LIST
	*/

	$html_my_projects = "";
	$html_my_projects .= $HTML->box1_top($Language->getText('my_index', 'my_projects'),0);
	$result = db_query("SELECT groups.group_name,"
		. "groups.group_id,"
		. "groups.unix_group_name,"
		. "groups.status,"
		. "groups.is_public,"
		. "user_group.admin_flags "
		. "FROM groups,user_group "
		. "WHERE groups.group_id=user_group.group_id "
		. "AND user_group.user_id='". user_getid() ."' "
		. "AND groups.status='A' ORDER BY group_name");
	$rows=db_numrows($result);
	if (!$result || $rows < 1) {
		$html_my_projects .= $Language->getText('my_index', 'not_member');
		$html_my_projects .= db_error();
	} else {

		for ($i=0; $i<$rows; $i++) {
			$html_my_projects .= '
			       <TR class="'. util_get_alt_row_color($i) .'"><TD WIDTH="99%">'.
			    '<A href="/projects/'. db_result($result,$i,'unix_group_name') .'/"><b>'.
			    db_result($result,$i,'group_name') .'</b></A>';
			if ( db_result($result,$i,'admin_flags') == 'A' ) {
			    $html_my_projects .= ' <small><A HREF="/project/admin/?group_id='.db_result($result,$i,'group_id').'">['.$Language->getText('my_index', 'admin_link').']</A></small>';
			}
			if ( db_result($result,$i,'is_public') == 0 ) {
			    $html_my_projects .= ' (*)';
			    $private_shown = true;
			}
			if ( db_result($result,$i,'admin_flags') == 'A' ) {
                            // User can't exit of project if she is admin
                            $html_my_projects .= '</td><td>&nbsp;</td></TR>';
                        } else {
                            $html_my_projects .= '</TD>'.
                                '<td><A href="rmproject.php?group_id='. db_result($result,$i,'group_id').
                                '" onClick="return confirm(\''.$Language->getText('my_index', 'quit_proj').'\')">'.
                                '<IMG SRC="'.util_get_image_theme("ic/trash.png").'" HEIGHT="16" WIDTH="16" BORDER="0"></A></TD></TR>';
                        }
		}
		
		if (isset($private_shown) && $private_shown) {
		  $html_my_projects .= '
			       <TR class="'. util_get_alt_row_color($i) .'"><TD colspan="2" class="small">'.
		      '(*)&nbsp;'.$Language->getText('my_index', 'priv_proj').'</td></tr>';
		}
	}
    $html_my_projects .= '<TR><TD COLSPAN="2">&nbsp;</TD></TR>';
	$html_my_projects .= $HTML->box1_bottom(0);
	?>
	
	<TABLE width="100%" border="0">
	<TR><TD VALIGN="TOP" WIDTH="50%">
<?
	echo $html_my_survey;
	echo $html_my_projects;
	echo $html_my_bookmarks;
    $em->processEvent("my_page_after_bookmark", null);
	echo $html_my_monitored_forums;
	echo $html_my_monitored_fp;	
    $em->processEvent("my_page_left_column_bottom", null);
?>
	</TD><TD VALIGN="TOP" WIDTH="50%">
<?
	echo $html_my_artifacts;
	echo $html_my_bugs;
	echo $html_my_tasks;
	echo $html_my_srs;
    $em->processEvent("my_page_right_column_bottom", null);
?>
	</TD></TR><TR><TD COLSPAN=2>
	<?
	echo show_priority_colors_key();
	?>
	</TD></TR>
	
	</TABLE>
	</span>
	
<?php
	site_footer(array());

} else {

	exit_not_logged_in();

}


function display_artifacts($list_trackers, $print_box_begin) {
  global $hide_item_id, $hide_artifact;
  $j = $print_box_begin;
  $html_my_artifacts = "";
  $html = "";
  $html_hdr = "";

  $aid_old  = 0;
  $atid_old = 0;
  $group_id_old = 0;
  $count_aids = 0;
  $group_name = "";
  $tracker_name = "";
  
  $artifact_types = array();
  
  while ($trackers_array = db_fetch_array($list_trackers)) {
    $atid = $trackers_array['group_artifact_id'];
    $group_id = $trackers_array['group_id'];
    
    // {{{ check permissions
    //create group
    $group = group_get_object($group_id);
    if (!$group || !is_object($group) || $group->isError()) {
            exit_no_group();
    }
    //Create the ArtifactType object
    if (!isset($artifact_types[$group_id])) {
        $artifact_types[$group_id] = array();
    }
    if (!isset($artifact_types[$group_id][$atid])) {
        $artifact_types[$group_id][$atid] = array();
        $artifact_types[$group_id][$atid]['at'] =& new ArtifactType($group,$atid);
        $artifact_types[$group_id][$atid]['user_can_view_at']             = $artifact_types[$group_id][$atid]['at']->userCanView();
        $artifact_types[$group_id][$atid]['user_can_view_summary_or_aid'] = null;
    }
    //Check if user can view artifact
    if ($artifact_types[$group_id][$atid]['user_can_view_at'] && $artifact_types[$group_id][$atid]['user_can_view_summary_or_aid'] !== false) {
        if (is_null($artifact_types[$group_id][$atid]['user_can_view_summary_or_aid'])) {
            $at =& $artifact_types[$group_id][$atid]['at'];
            //Create ArtifactFieldFactory object
            if (!isset($artifact_types[$group_id][$atid]['aff'])) {
                $artifact_types[$group_id][$atid]['aff'] =& new ArtifactFieldFactory($at);
            }
            $aff =& $artifact_types[$group_id][$atid]['aff'];
            //Retrieve artifact_id field
            $field =& $aff->getFieldFromName('artifact_id');
            //Check if user can read it
            $user_can_view_aid = $field->userCanRead($group_id, $atid);
            //Retrieve percent_complete field
            $field =& $aff->getFieldFromName('percent_complete');
            //Check if user can read it
            $user_can_view_percent_complete = $field && $field->userCanRead($group_id, $atid);
            //Retriebe summary field
            $field =& $aff->getFieldFromName('summary');
            //Check if user can read it
            $user_can_view_summary = $field->userCanRead($group_id, $atid);
            $artifact_types[$group_id][$atid]['user_can_view_summary_or_aid'] = $user_can_view_aid || $user_can_view_summary;
        }
        if ($artifact_types[$group_id][$atid]['user_can_view_summary_or_aid']) {
            
            //work on the tracker of the last round if there was one
            if ($atid != $atid_old && $count_aids != 0) {
                list($hide_now,$count_diff,$hide_url) = 
                    my_hide_url('artifact',$atid_old,$hide_item_id,$count_aids,$hide_artifact);
                $html_hdr = ($j ? '<tr class="boxitem"><td colspan="3">' : '').
                $hide_url.'<A HREF="/tracker/?group_id='.$group_id_old.'&atid='.$atid_old.'"><B>'.
                $group_name." - ".$tracker_name.'</B></A>&nbsp;&nbsp;&nbsp;&nbsp;';
                $count_new = max(0, $count_diff);
                  
                $html_hdr .= my_item_count($count_aids,$count_new).'</td></tr>';
                $html_my_artifacts .= $html_hdr.$html;
                
                $count_aids = 0;
                $html = '';
                $j++;
              
            } 
            
            if ($count_aids == 0) {
              //have to call it to get at least the hide_now even if count_aids is false at this point
              $hide_now = my_hide('artifact',$atid,$hide_item_id,$hide_artifact);
            }
            
            $group_name   = $trackers_array['group_name'];
            $tracker_name = $trackers_array['name'];
            $aid          = $trackers_array['artifact_id'];
            $summary      = $trackers_array['summary'];
            $atid_old     = $atid;
            $group_id_old = $group_id;

            // If user is assignee and submitter of an artifact, it will
            // appears 2 times in the result set.
            if($aid != $aid_old) {
                $count_aids++;
            }

            if (!$hide_now && $aid != $aid_old) {
              
                // Form the 'Submitted by/Assigned to flag' for marking
                $AS_flag = my_format_as_flag2($trackers_array['assignee'],$trackers_array['submitter']);
                
                //get percent_complete if this field is used in the tracker
                $percent_complete = '';
                if ($user_can_view_percent_complete) {
                    $sql = 
                        "SELECT afvl.value ".
                        "FROM artifact_field_value afv,artifact_field af, artifact_field_value_list afvl, artifact_field_usage afu ".
                        "WHERE af.field_id = afv.field_id AND af.field_name = 'percent_complete' ".
                        "AND afv.artifact_id = $aid ".
                        "AND afvl.group_artifact_id = $atid AND af.group_artifact_id = $atid ".
                        "AND afu.group_artifact_id = $atid AND afu.field_id = af.field_id AND afu.use_it = 1 ".
                        "AND afvl.field_id = af.field_id AND afvl.value_id = afv.valueInt";
                    $res = db_query($sql);
                    if (db_numrows($res) > 0) {
                        $percent_complete = '<TD class="small">'.db_result($res,0,'value').'</TD>';
                    }
                }
                $html .= '
                    <TR class="'.get_priority_color($trackers_array['severity']).
                    '"><TD class="small"><A HREF="/tracker/?func=detail&group_id='.
                $group_id.'&aid='.$aid.'&atid='.$atid.
                    '">'.$aid.'</A></TD>'.
                    '<TD class="small"'.($percent_complete ? '>': ' colspan="2">');
                if ($user_can_view_summary) {
                    $html .= stripslashes($summary);
                }
                $html .= '&nbsp;'.$AS_flag.'</TD>'.$percent_complete.'</TR>';
              
            }
            $aid_old = $aid;
        }
    }
  }	
  
  //work on the tracker of the last round if there was one
  if ($atid_old != 0 && $count_aids != 0) {
    list($hide_now,$count_diff,$hide_url) = 
      my_hide_url('artifact',$atid_old,$hide_item_id,$count_aids,$hide_artifact);
    $html_hdr = ($j ? '<tr class="boxitem"><td colspan="3">' : '').
      $hide_url.'<A HREF="/tracker/?group_id='.$group_id_old.'&atid='.$atid_old.'"><B>'.
      $group_name." - ".$tracker_name.'</B></A>&nbsp;&nbsp;&nbsp;&nbsp;';
    $count_new = max(0, $count_diff);
    
    $html_hdr .= my_item_count($count_aids,$count_new).'</td></tr>';
    $html_my_artifacts .= $html_hdr.$html;
  }

  return $html_my_artifacts;
  
}


?>
