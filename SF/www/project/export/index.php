<?php
//
// CodeX: Breaking Down the Barriers to Source Code Sharing inside Xerox
// Copyright (c) Xerox Corporation, CodeX / CodeX Team, 2001. All Rights Reserved
// http://codex.xerox.com
//
// $Id$
require($DOCUMENT_ROOT.'/include/pre.php');
require('../admin/project_admin_utils.php');
require('./project_export_utils.php');
require($DOCUMENT_ROOT.'/../common/tracker/Artifact.class');
require($DOCUMENT_ROOT.'/tracker/include/ArtifactHtml.class');
require($DOCUMENT_ROOT.'/../common/tracker/ArtifactType.class');
require($DOCUMENT_ROOT.'/tracker/include/ArtifactTypeHtml.class');
require($DOCUMENT_ROOT.'/../common/tracker/ArtifactTypeFactory.class');
require($DOCUMENT_ROOT.'/../common/tracker/ArtifactField.class');
require($DOCUMENT_ROOT.'/../common/tracker/ArtifactFieldFactory.class');

// Conditionally include the appropriate modules
if (ereg('^bug',$export) || ($export == 'project_db') ) {
    require($DOCUMENT_ROOT.'/bugs/bug_data.php');
    require($DOCUMENT_ROOT.'/bugs/bug_utils.php');
}
if (ereg('^task',$export) || ($export == 'project_db')){
    require($DOCUMENT_ROOT.'/pm/pm_data.php');
    require($DOCUMENT_ROOT.'/pm/pm_utils.php');
}
if (ereg('^support',$export) || ($export == 'project_db') ) {
    require($DOCUMENT_ROOT.'/support/support_data.php');
    require($DOCUMENT_ROOT.'/support/support_utils.php');
}

// Group ID must be defined and must be a project admin
if ( !$group_id ) {
    exit_error("Invalid Group","That group could not be found."); }

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

$project=project_get_object($group_id);
$dbname = $groupname = $project->getUnixName();
$pg_title = 'Project Data Export '.$groupname;

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

?>
   <P>Your project database has been succesfully generated. You can now use 
your favorite desktop application and access your project database through 
the MySQL ODBC/JDBC driver installed on your desktop machine.
 The parameters to configure your ODBC/JDBC database 
connection are as follows:
<p>

<?php
     display_db_params ();
     site_project_footer( array() );
     break;

 default: 
     project_admin_header(array('title'=>$pg_title,
				'help' => 'ProjectDataExport.html'));
    // Display the welcome screen
?>
<P> Your project data can either be exported in
individual text files (CSV format) or in a project specific database that you can directly access from your desktop machine through an ODBC/JDBC connection. See <?php echo help_button('ProjectDataExport.html',false,'Online Help'); ?> for more information.

<h3>Text File Export <?php echo help_button('ProjectDataExport.html#TextFileExport'); ?></h3>

     <P>Click on the links below to generate a text file export (CSV format).
<P>
<?
		
	// Show all the fields currently available in the system

	echo '<p><TABLE WIDTH="100%" BORDER="0" CELLSPACING="1" CELLPADDING="2">';
	echo '
  <tr class="boxtable"> 
    <td class="boxtitle">&nbsp;</td>
    <td class="boxtitle"> 
      <div align="center"><b>Artifacts Data</b></div>
    </td>
    <td class="boxtitle"> 
      <div align="center"><b>History</b></div>
    </td>
    <td class="boxtitle"> 
      <div align="center"><b>Dependencies</b></div>
    </td>
  </tr>';
  	$iu = 0;
	$legacy = (($sys_activate_tracker == 1) ? "Legacy":"");

	if ($project->activateOldBug()) {
  	echo '
  <tr class="'.util_get_alt_row_color($iu).'"> 
    <td><b>'.$legacy.' Bug Tracker</b></td>
    <td align="center"> 
      <a href="'.$PHP_SELF.'?group_id='.$group_id.'&export=bug">Export</a>
      <br><a href="'.$PHP_SELF.'?group_id='.$group_id.'&export=bug_format">Show Format</a>
    </td>
    <td align="center"> 
      <a href="'.$PHP_SELF.'?group_id='.$group_id.'&export=bug_history">Export</a>
      <br><a href="'.$PHP_SELF.'?group_id='.$group_id.'&export=bug_history_format">Show Format</a>
    </td>
    <td align="center"> 
      <a href="'.$PHP_SELF.'?group_id='.$group_id.'&export=bug_bug_deps">Export Bug-Bug Deps</a>
      - <a href="'.$PHP_SELF.'?group_id='.$group_id.'&export=bug_bug_deps_format">Show Format</a>
      <br><a href="'.$PHP_SELF.'?group_id='.$group_id.'&export=bug_task_deps">Export Bug-Task Deps</a>
      - <a href="'.$PHP_SELF.'?group_id='.$group_id.'&export=bug_task_deps_format">Show Format</a>
    </td>
  </tr>';
  	$iu ++;
	}

	if ($project->activateOldTask()) {
  	echo '
  <tr class="'.util_get_alt_row_color($iu).'"> 
    <td><b>'.$legacy.' Task Manager</b></td>
    <td align="center"> 
      <a href="'.$PHP_SELF.'?group_id='.$group_id.'&export=task">Export</a>
	  <br><a href="'.$PHP_SELF.'?group_id='.$group_id.'&export=task_format">Show Format</a>
    </td>
    <td align="center"> 
      <a href="'.$PHP_SELF.'?group_id='.$group_id.'&export=task_history">Export</a>
      <br><a href="'.$PHP_SELF.'?group_id='.$group_id.'&export=task_history_format">Show Format</a>
    </td>
    <td align="center"> 
      <a href="'.$PHP_SELF.'?group_id='.$group_id.'&export=task_task_deps">Export</a>
      <br><a href="'.$PHP_SELF.'?group_id='.$group_id.'&export=task_task_deps_format">Show Format</a>
    </td>
  </tr>';
  	$iu ++;
	}

	if ($project->activateOldSR()) {
  	echo '
  <tr class="'.util_get_alt_row_color($iu).'"> 
    <td><b>'.$legacy.' Support Request</b></td>
    <td align="center"> 
      <a href="'.$PHP_SELF.'?group_id='.$group_id.'&export=support_request">Export</a>
      <br><a href="'.$PHP_SELF.'?group_id='.$group_id.'&export=support_request_format">Show Format</a>
    </td>
    <td align="center">-<br>-</td>
    <td align="center">-<br>-</td>
  </tr>';
  	$iu ++;
	}

  	echo '
  <tr class="'.util_get_alt_row_color($iu).'"> 
    <td><b>Survey Responses</b></td>
    <td align="center"> 
      <a href="'.$PHP_SELF.'?group_id='.$group_id.'&export=survey_responses">Export</a>
      <br><a href="'.$PHP_SELF.'?group_id='.$group_id.'&export=survey_responses_format">Show Format</a>
    </td>
    <td align="center">-<br>-</td>
    <td align="center">-<br>-</td>
  </tr>';		
  
	// Get the artfact type list
	$at_arr = $atf->getArtifactTypes();
	
	if ($at_arr && count($at_arr) >= 1) {
		for ($j = 0; $j < count($at_arr); $j++) {
		  	$iu ++;
		  	echo '
		  <tr class="'.util_get_alt_row_color($iu).'"> 
		    <td><b>Tracker: '.$at_arr[$j]->getName().'</b></td>
		    <td align="center"><a href="'.$PHP_SELF.'?group_id='.$group_id.'&atid='.$at_arr[$j]->getID().'&export=artifact">Export</a>
		      <br><a href="'.$PHP_SELF.'?group_id='.$group_id.'&atid='.$at_arr[$j]->getID().'&export=artifact_format">Show Format</a>
		    </td>
		    <td align="center"> 
		      <a href="'.$PHP_SELF.'?group_id='.$group_id.'&atid='.$at_arr[$j]->getID().'&export=artifact_history">Export</a>
		      <br><a href="'.$PHP_SELF.'?group_id='.$group_id.'&atid='.$at_arr[$j]->getID().'&export=artifact_history_format">Show Format</a>
		    </td>
		    <td align="center"> 
		      <a href="'.$PHP_SELF.'?group_id='.$group_id.'&atid='.$at_arr[$j]->getID().'&export=artifact_deps">Export</a>
		      <br><a href="'.$PHP_SELF.'?group_id='.$group_id.'&atid='.$at_arr[$j]->getID().'&export=artifact_deps_format">Show Format</a>
		    </td>
		  </tr>';
		}
	}

	echo '</TABLE>';
?>
<br>
<h3>Direct Database Access <?php echo help_button('ProjectDataExport.html#DirectDatabaseAccess'); ?></h3>

<ol>
<?php
    echo '<li><b><a href="'.$PHP_SELF."?group_id=$group_id&export=project_db\">Generate Full Project Database</a> </b> (<- Click to generate)"."\n";
    echo '<li>Database connection parameters: ';
?>
</ol>

<?php
    display_db_params ();
    site_project_footer( array() );
    break;
}
?>
