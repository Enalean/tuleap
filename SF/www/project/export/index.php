<?php
//
// CodeX: Breaking Down the Barriers to Source Code Sharing inside Xerox
// Copyright (c) Xerox Corporation, CodeX / CodeX Team, 2001. All Rights Reserved
// http://codex.xerox.com
//
// $Id$
require('pre.php');
require('../admin/project_admin_utils.php');
require('project_export_utils.php');
require($DOCUMENT_ROOT.'/../common/tracker/ArtifactType.class');
require($DOCUMENT_ROOT.'/../common/tracker/ArtifactTypeFactory.class');

// Conditionally include the appropriate modules
if (ereg('^bug',$export) || ($export == 'project_db') ) {
    include($DOCUMENT_ROOT.'/bugs/bug_data.php');
    include($DOCUMENT_ROOT.'/bugs/bug_utils.php');
}
if (ereg('^task',$export) || ($export == 'project_db')){
    include($DOCUMENT_ROOT.'/pm/pm_data.php');
    include($DOCUMENT_ROOT.'/pm/pm_utils.php');
}
if (ereg('^support',$export) || ($export == 'project_db') ) {
    include($DOCUMENT_ROOT.'/support/support_data.php');
    include($DOCUMENT_ROOT.'/support/support_utils.php');
}

// Group ID must be defined and must be a project admin
if ( !$group_id ) {
    exit_error("Invalid Group","That group could not be found."); }

session_require(array('group'=>$group_id,'admin_flags'=>'A'));


$project=project_get_object($group_id);
$dbname = $groupname = $project->getUnixName();
$pg_title = 'Project Data Export '.$groupname;

switch ($export) {

 case 'bug':
     include('./bug_export.php');
     break;

 case 'bug_format':
     project_admin_header(array('title'=>$pg_title));
     include('./bug_export.php');
     site_project_footer( array() );
     break;

 case 'bug_history':
     include('./bug_history_export.php');
     break;

 case 'bug_history_format':
     project_admin_header(array('title'=>$pg_title));
     include('./bug_history_export.php');
     site_project_footer( array() );
     break;

 case 'bug_bug_deps':
     include('./bug_bug_deps_export.php');
     break;

 case 'bug_bug_deps_format':
     project_admin_header(array('title'=>$pg_title));
     include('./bug_bug_deps_export.php');
     site_project_footer( array() );
     break;

 case 'bug_task_deps':
     include('./bug_task_deps_export.php');
     break;

 case 'bug_task_deps_format':
     project_admin_header(array('title'=>$pg_title));
     include('./bug_task_deps_export.php');
     site_project_footer( array() );
     break;

 case 'task':
     include('./task_export.php');
     break;

 case 'task_format':
     project_admin_header(array('title'=>$pg_title));
     include('./task_export.php');
     site_project_footer( array() );
     break;

 case 'task_history':
     include('./task_history_export.php');
     break;

 case 'task_history_format':
     project_admin_header(array('title'=>$pg_title));
     include('./task_history_export.php');
     site_project_footer( array() );
     break;

 case 'task_task_deps':
     include('./task_task_deps_export.php');
     break;

 case 'task_task_deps_format':
     project_admin_header(array('title'=>$pg_title));
     include('./task_task_deps_export.php');
     site_project_footer( array() );
     break;

 case 'task_assigned_to':
     include('./task_assigned_to_export.php');
     break;

 case 'task_assigned_to_format':
     project_admin_header(array('title'=>$pg_title));
     include('./task_assigned_to_export.php');
     site_project_footer( array() );
     break;

 case 'survey_responses':
     include('./survey_responses_export.php');
     break;

 case 'survey_responses_format':
     project_admin_header(array('title'=>$pg_title));
     include('./survey_responses_export.php');
     site_project_footer( array() );
     break;

 case 'support_request':
     include('./support_request_export.php');
     break;

 case 'support_request_format':
     project_admin_header(array('title'=>$pg_title));
     include('./support_request_export.php');
     site_project_footer( array() );
     break;

 case 'project_db':
     project_admin_header(array('title'=>$pg_title));
     include('./bug_export.php');
     include('./bug_history_export.php');
     include('./bug_bug_deps_export.php');
     include('./bug_task_deps_export.php');
     include('./task_export.php');
     include('./task_history_export.php');
     include('./task_task_deps_export.php');
     include('./task_assigned_to_export.php');
     include('./survey_responses_export.php');
     include('./support_request_export.php');
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
  	echo '
  <tr class="'.util_get_alt_row_color($iu).'"> 
    <td><b>Legacy Bug Tracker</b></td>
    <td align="center"> 
      <a href="'.$PHP_SELF.'?group_id=$group_id&export=bug">Export</a>
      <br><a href="'.$PHP_SELF.'?group_id='.$group_id.'&export=bug_format">Show Format</a>
    </td>
    <td align="center"> 
      <a href="'.$PHP_SELF.'?group_id=$group_id&export=bug_history">Export</a>
      <br><a href="'.$PHP_SELF.'?group_id='.$group_id.'&export=bug_history_format">Show Format</a>
    </td>
    <td align="center"> 
      <a href="'.$PHP_SELF.'?group_id=$group_id&export=bug_bug_deps">Export Bug-Bug Deps</a>
      - <a href="'.$PHP_SELF.'?group_id='.$group_id.'&export=bug_bug_deps_format">Show Format</a>
      <br><a href="'.$PHP_SELF.'?group_id=$group_id&export=bug_task_deps">Export Bug-Task Deps</a>
      - <a href="'.$PHP_SELF.'?group_id='.$group_id.'&export=bug_task_deps_format">Show Format</a>
    </td>
  </tr>';
  	$iu ++;
  	echo '
  <tr class="'.util_get_alt_row_color($iu).'"> 
    <td><b>Legacy Task Manager</b></td>
    <td align="center"> 
      <a href="'.$PHP_SELF.'?group_id=$group_id&export=task">Export</a>
	  <br><a href="'.$PHP_SELF.'?group_id='.$group_id.'&export=task_format">Show Format</a>
    </td>
    <td align="center"> 
      <a href="'.$PHP_SELF.'?group_id=$group_id&export=task_history">Export</a>
      <br><a href="'.$PHP_SELF.'?group_id='.$group_id.'&export=task_history_format">Show Format</a>
    </td>
    <td align="center"> 
      <a href="'.$PHP_SELF.'?group_id=$group_id&export=task_task_deps">Export</a>
      <br><a href="'.$PHP_SELF.'?group_id='.$group_id.'&export=task_task_deps_format">Show Format</a>
    </td>
  </tr>';
  	$iu ++;
  	echo '
  <tr class="'.util_get_alt_row_color($iu).'"> 
    <td><b>Legacy Support Request</b></td>
    <td align="center"> 
      <a href="'.$PHP_SELF.'?group_id=$group_id&export=support_request">Export</a>
      <br><a href="'.$PHP_SELF.'?group_id='.$group_id.'&export=support_request_format">Show Format</a>
    </td>
    <td align="center">-<br>-</td>
    <td align="center">-<br>-</td>
  </tr>';
  	$iu ++;
  	echo '
  <tr class="'.util_get_alt_row_color($iu).'"> 
    <td><b>Survey Responses</b></td>
    <td align="center"> 
      <a href="'.$PHP_SELF.'?group_id=$group_id&export=survey_responses">Export</a>
      <br><a href="'.$PHP_SELF.'?group_id='.$group_id.'&export=survey_responses_format">Show Format</a>
    </td>
    <td align="center">-<br>-</td>
    <td align="center">-<br>-</td>
  </tr>';		
  
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
	
	if ($at_arr && count($at_arr) >= 1) {
		for ($j = 0; $j < count($at_arr); $j++) {
		  	$iu ++;
		  	echo '
		  <tr class="'.util_get_alt_row_color($iu).'"> 
		    <td><b>Tracker: '.$at_arr[$j]->getName().'</b></td>
		    <td align="center"> 
		      <a href="'.$PHP_SELF.'?group_id=$group_id&atid='.$at_arr[$j]->getID().'">Export</a>
		      <br><a href="'.$PHP_SELF.'?group_id=$group_id&atid='.$at_arr[$j]->getID().'">Show Format</a>
		    </td>
		    <td align="center"> 
		      <a href="'.$PHP_SELF.'?group_id=$group_id&atid='.$at_arr[$j]->getID().'">Export</a>
		      <br><a href="'.$PHP_SELF.'?group_id=$group_id&atid='.$at_arr[$j]->getID().'">Show Format</a>
		    </td>
		    <td align="center"> 
		      <a href="'.$PHP_SELF.'?group_id=$group_id&atid='.$at_arr[$j]->getID().'">Export</a>
		      <br><a href="'.$PHP_SELF.'?group_id=$group_id&atid='.$at_arr[$j]->getID().'">Show Format</a>
		    </td>
		  </tr>';
		}
	}

	echo '</TABLE>';
?>

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
