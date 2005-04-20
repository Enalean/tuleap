<?php
//
// CodeX: Breaking Down the Barriers to Source Code Sharing inside Xerox
// Copyright (c) Xerox Corporation, CodeX / CodeX Team, 2001. All Rights Reserved
// http://codex.xerox.com
//
// $Id$
require_once('pre.php');
require('../admin/project_admin_utils.php');
require('./project_export_utils.php');
require_once('common/tracker/Artifact.class');
require_once('www/tracker/include/ArtifactHtml.class');
require_once('common/tracker/ArtifactType.class');
require_once('www/tracker/include/ArtifactTypeHtml.class');
require_once('common/tracker/ArtifactTypeFactory.class');
require_once('common/tracker/ArtifactField.class');
require_once('common/tracker/ArtifactFieldFactory.class');

$Language->loadLanguageMsg('project/project');


// Conditionally include the appropriate modules
if (ereg('^bug',$export) || ($export == 'project_db') ) {
    require_once('www/bugs/bug_data.php');
    require_once('www/bugs/bug_utils.php');
}
if (ereg('^task',$export) || ($export == 'project_db')){
    require_once('www/pm/pm_data.php');
    require_once('www/pm/pm_utils.php');
}
if (ereg('^support',$export) || ($export == 'project_db') ) {
    require_once('www/support/support_data.php');
    require_once('www/support/support_utils.php');
}

// Group ID must be defined and must be a project admin
if ( !$group_id ) {
    exit_error($Language->getText('project_admin_userperms','invalid_g'),$Language->getText('project_admin_userperms','group_not_exist')); }

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
	exit_error($Language->getText('global','error'),$Language->getText('project_admin_index','not_get_atf'));
}

$project=project_get_object($group_id);
$dbname = $groupname = $project->getUnixName();
$pg_title = $Language->getText('project_admin_utils','project_data_export').' '.$groupname;

switch ($export) {

 case 'artifact':
     require('./artifact_export.php');
     break;

 case 'artifact_format':
     project_admin_header(array('title'=>$pg_title));
     require('./artifact_export.php');
     site_project_footer( array() );
     break;

 case 'artifact_history':
     require('./artifact_history_export.php');
     break;

 case 'artifact_history_format':
     project_admin_header(array('title'=>$pg_title));
     require('./artifact_history_export.php');
     site_project_footer( array() );
     break;

 case 'artifact_deps':
     require('./artifact_deps_export.php');
     break;

 case 'artifact_deps_format':
     project_admin_header(array('title'=>$pg_title));
     require('./artifact_deps_export.php');
     site_project_footer( array() );
     break;

 case 'bug':
     require('./bug_export.php');
     break;

 case 'bug_format':
     project_admin_header(array('title'=>$pg_title));
     require('./bug_export.php');
     site_project_footer( array() );
     break;

 case 'bug_history':
     require('./bug_history_export.php');
     break;

 case 'bug_history_format':
     project_admin_header(array('title'=>$pg_title));
     require('./bug_history_export.php');
     site_project_footer( array() );
     break;

 case 'bug_bug_deps':
     require('./bug_bug_deps_export.php');
     break;

 case 'bug_bug_deps_format':
     project_admin_header(array('title'=>$pg_title));
     require('./bug_bug_deps_export.php');
     site_project_footer( array() );
     break;

 case 'bug_task_deps':
     require('./bug_task_deps_export.php');
     break;

 case 'bug_task_deps_format':
     project_admin_header(array('title'=>$pg_title));
     require('./bug_task_deps_export.php');
     site_project_footer( array() );
     break;

 case 'task':
     require('./task_export.php');
     break;

 case 'task_format':
     project_admin_header(array('title'=>$pg_title));
     require('./task_export.php');
     site_project_footer( array() );
     break;

 case 'task_history':
     require('./task_history_export.php');
     break;

 case 'task_history_format':
     project_admin_header(array('title'=>$pg_title));
     require('./task_history_export.php');
     site_project_footer( array() );
     break;

 case 'task_task_deps':
     require('./task_task_deps_export.php');
     break;

 case 'task_task_deps_format':
     project_admin_header(array('title'=>$pg_title));
     require('./task_task_deps_export.php');
     site_project_footer( array() );
     break;

 case 'task_assigned_to':
     require('./task_assigned_to_export.php');
     break;

 case 'task_assigned_to_format':
     project_admin_header(array('title'=>$pg_title));
     require('./task_assigned_to_export.php');
     site_project_footer( array() );
     break;

 case 'survey_responses':
     require('./survey_responses_export.php');
     break;

 case 'survey_responses_format':
     project_admin_header(array('title'=>$pg_title));
     require('./survey_responses_export.php');
     site_project_footer( array() );
     break;

 case 'support_request':
     require('./support_request_export.php');
     break;

 case 'support_request_format':
     project_admin_header(array('title'=>$pg_title));
     require('./support_request_export.php');
     site_project_footer( array() );
     break;

 case 'project_db':
     project_admin_header(array('title'=>$pg_title));
     require('./bug_export.php');
     require('./bug_history_export.php');
     require('./bug_bug_deps_export.php');
     require('./bug_task_deps_export.php');
     require('./task_export.php');
     require('./task_history_export.php');
     require('./task_task_deps_export.php');
     require('./task_assigned_to_export.php');
     require('./survey_responses_export.php');
     require('./support_request_export.php');
     require('./artifact_export.php');
     require('./artifact_history_export.php');
     require('./artifact_deps_export.php');

echo '
   <P>'.$Language->getText('project_export_index','proj_db_success').'
<p>';
     display_db_params ();
     site_project_footer( array() );
     break;

 default: 
     project_admin_header(array('title'=>$pg_title,
				'help' => 'ProjectDataExport.html'));
    // Display the welcome screen

echo '
<P> '.$Language->getText('project_export_index','export_to_csv_or_db',array(help_button('ProjectDataExport.html',false,$Language->getText('project_export_index','online_help')))).'</p>';

echo '
<h3> '.$Language->getText('project_export_index','export_to_csv_hdr',array(help_button('ProjectDataExport.html#TextFileExport'))).'</h3>';

echo '
<p> '.$Language->getText('project_export_index','export_to_csv_msg').'</p>';
		
	// Show all the fields currently available in the system

	echo '<p><TABLE WIDTH="100%" BORDER="0" CELLSPACING="1" CELLPADDING="2">';
	echo '
  <tr class="boxtable"> 
    <td class="boxtitle">&nbsp;</td>
    <td class="boxtitle"> 
      <div align="center"><b>'.$Language->getText('project_export_index','art_data').'</b></div>
    </td>
    <td class="boxtitle"> 
      <div align="center"><b>'.$Language->getText('project_export_index','history').'</b></div>
    </td>
    <td class="boxtitle"> 
      <div align="center"><b>'.$Language->getText('project_export_index','dependencies').'</b></div>
    </td>
  </tr>';
  	$iu = 0;
	$legacy = ( ($project->usesTracker()) ? $Language->getText('project_export_index','legacy'):"");

	if ($project->usesBugs()) {
  	echo '
  <tr class="'.util_get_alt_row_color($iu).'"> 
    <td><b>'.$legacy.' '.$Language->getText('project_export_index','bug_tracker').'</b></td>
    <td align="center"> 
      <a href="'.$PHP_SELF.'?group_id='.$group_id.'&export=bug">'.$Language->getText('project_export_index','export').'</a>
      <br><a href="'.$PHP_SELF.'?group_id='.$group_id.'&export=bug_format">'.$Language->getText('project_export_index','show_format').'</a>
    </td>
    <td align="center"> 
      <a href="'.$PHP_SELF.'?group_id='.$group_id.'&export=bug_history">'.$Language->getText('project_export_index','export').'</a>
      <br><a href="'.$PHP_SELF.'?group_id='.$group_id.'&export=bug_history_format">'.$Language->getText('project_export_index','show_format').'</a>
    </td>
    <td align="center"> 
      <a href="'.$PHP_SELF.'?group_id='.$group_id.'&export=bug_bug_deps">'.$Language->getText('project_export_index','export_x','Bug-Bug Deps').'</a>
      - <a href="'.$PHP_SELF.'?group_id='.$group_id.'&export=bug_bug_deps_format">'.$Language->getText('project_export_index','show_format').'</a>
      <br><a href="'.$PHP_SELF.'?group_id='.$group_id.'&export=bug_task_deps">'.$Language->getText('project_export_index','export_x','Bug-Task Deps').'</a>
      - <a href="'.$PHP_SELF.'?group_id='.$group_id.'&export=bug_task_deps_format">'.$Language->getText('project_export_index','show_format').'</a>
    </td>
  </tr>';
  	$iu ++;
	}

	if ($project->usesPm()) {
  	echo '
  <tr class="'.util_get_alt_row_color($iu).'"> 
    <td><b>'.$legacy.' '.$Language->getText('project_admin_userperms','task_man').'</b></td>
    <td align="center"> 
      <a href="'.$PHP_SELF.'?group_id='.$group_id.'&export=task">'.$Language->getText('project_export_index','export').'</a>
	  <br><a href="'.$PHP_SELF.'?group_id='.$group_id.'&export=task_format">'.$Language->getText('project_export_index','show_format').'</a>
    </td>
    <td align="center"> 
      <a href="'.$PHP_SELF.'?group_id='.$group_id.'&export=task_history">'.$Language->getText('project_export_index','export').'</a>
      <br><a href="'.$PHP_SELF.'?group_id='.$group_id.'&export=task_history_format">'.$Language->getText('project_export_index','show_format').'</a>
    </td>
    <td align="center"> 
      <a href="'.$PHP_SELF.'?group_id='.$group_id.'&export=task_task_deps">'.$Language->getText('project_export_index','export').'</a>
      <br><a href="'.$PHP_SELF.'?group_id='.$group_id.'&export=task_task_deps_format">'.$Language->getText('project_export_index','show_format').'</a>
    </td>
  </tr>';
  	$iu ++;
	}

	if ($project->usesSupport()) {
  	echo '
  <tr class="'.util_get_alt_row_color($iu).'"> 
    <td><b>'.$legacy.' '.$Language->getText('project_export_index','support_request').'</b></td>
    <td align="center"> 
      <a href="'.$PHP_SELF.'?group_id='.$group_id.'&export=support_request">'.$Language->getText('project_export_index','export').'</a>
      <br><a href="'.$PHP_SELF.'?group_id='.$group_id.'&export=support_request_format">'.$Language->getText('project_export_index','show_format').'</a>
    </td>
    <td align="center">-<br>-</td>
    <td align="center">-<br>-</td>
  </tr>';
  	$iu ++;
	}
        if ($project->usesSurvey()) {
  	echo '
  <tr class="'.util_get_alt_row_color($iu).'"> 
    <td><b>'.$Language->getText('project_export_index','survey_responses').'</b></td>
    <td align="center"> 
      <a href="'.$PHP_SELF.'?group_id='.$group_id.'&export=survey_responses">'.$Language->getText('project_export_index','export').'</a>
      <br><a href="'.$PHP_SELF.'?group_id='.$group_id.'&export=survey_responses_format">'.$Language->getText('project_export_index','show_format').'</a>
    </td>
    <td align="center">-<br>-</td>
    <td align="center">-<br>-</td>
  </tr>';
  	$iu ++;
        }		
  
	if ($project->usesTracker()) {
            // Get the artfact type list
            $at_arr = $atf->getArtifactTypes();
	
	if ($at_arr && count($at_arr) >= 1) {
		for ($j = 0; $j < count($at_arr); $j++) {
		  	$iu ++;
		  	echo '
		  <tr class="'.util_get_alt_row_color($iu).'"> 
		    <td><b>Tracker: '.$at_arr[$j]->getName().'</b></td>
		    <td align="center"><a href="'.$PHP_SELF.'?group_id='.$group_id.'&atid='.$at_arr[$j]->getID().'&export=artifact">'.$Language->getText('project_export_index','export').'</a>
		      <br><a href="'.$PHP_SELF.'?group_id='.$group_id.'&atid='.$at_arr[$j]->getID().'&export=artifact_format">'.$Language->getText('project_export_index','show_format').'</a>
		    </td>
		    <td align="center"> 
		      <a href="'.$PHP_SELF.'?group_id='.$group_id.'&atid='.$at_arr[$j]->getID().'&export=artifact_history">'.$Language->getText('project_export_index','export').'</a>
		      <br><a href="'.$PHP_SELF.'?group_id='.$group_id.'&atid='.$at_arr[$j]->getID().'&export=artifact_history_format">'.$Language->getText('project_export_index','show_format').'</a>
		    </td>
		    <td align="center"> 
		      <a href="'.$PHP_SELF.'?group_id='.$group_id.'&atid='.$at_arr[$j]->getID().'&export=artifact_deps">'.$Language->getText('project_export_index','export').'</a>
		      <br><a href="'.$PHP_SELF.'?group_id='.$group_id.'&atid='.$at_arr[$j]->getID().'&export=artifact_deps_format">'.$Language->getText('project_export_index','show_format').'</a>
		    </td>
		  </tr>';
		}
	}
        }

	echo '</TABLE>';
echo '
<br>
<h3>'.$Language->getText('project_export_index','direct_db_access').' '.help_button('ProjectDataExport.html#DirectDatabaseAccess').'</h3>

<ol>';

    echo '<li><b><a href="'.$PHP_SELF."?group_id=$group_id&export=project_db\">".$Language->getText('project_export_index','generate_full_db')."\n";
    echo '<li>'.$Language->getText('project_export_index','db_connection_params').' ';
?>
</ol>

<?php
    display_db_params ();
    site_project_footer( array() );
    break;
}
?>
