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
     project_admin_header(array('title'=>$pg_title));
    // Display the welcome screen
?>
<P> Your project data can either be exported in
individual text files (CSV format) or in a project specific database that you can directly access from your desktop machine through an ODBC/JDBC connection.

<P>For more information about the Export Facility please read the document "<a href="/docman/display_doc.php?docid=85&group_id=1">Extracting your project data from <?php print $GLOBALS['sys_name']; ?></a>" first.

<h3><u>Text File Exports</u></h3>

     <P>Click on the links below to generate a text output, then save it to your local disk and import into your favorite application.
<P>
<ul>
<?php
    echo '<li><b><a href="'.$PHP_SELF."?group_id=$group_id&export=bug\">Bug export</a></b><br>\n";
?>
All bugs submitted to your project. Exported bug fields as well as
their format and meaning are <a href="<?php echo $PHP_SELF;
?>?group_id=<?php echo $group_id;?>&export=bug_format">available</a>.


<?php
    echo '<li><b><a href="'.$PHP_SELF."?group_id=$group_id&export=bug_history\">Bug History export</a></b><br>"."\n";
?>
A history of all the changes your project bugs have gone
through. Exported bug history fields as well as their format and
meaning are <a href="<?php echo $PHP_SELF; ?>?group_id=<?php echo
$group_id;?>&export=bug_history_format">available</a>.


<?php
    echo '<li><b><a href="'.$PHP_SELF."?group_id=$group_id&export=task\">Task export</a></b><br>"."\n";
?>
All tasks created in your project. Exported task fields as well as
their format and meaning are <a href="<?php echo $PHP_SELF;
?>?group_id=<?php echo $group_id;?>&export=task_format">available</a>.

<?php
    echo '<li><b><a href="'.$PHP_SELF."?group_id=$group_id&export=task_history\">Task History export</a></b><br>"."\n";
?>
A history of all the changes your project tasks have gone
through. Exported task history fields as well as their format and
meaning are <a href="<?php echo $PHP_SELF; ?>?group_id=<?php echo
$group_id;?>&export=task_history_format">available</a>.

<?php
    echo '<li><b><a href="'.$PHP_SELF."?group_id=$group_id&export=support_request\">Support Request export</a></b><br>"."\n";
?>
All support requests created in your project. Exported support request fields as well as
their format and meaning are <a href="<?php echo $PHP_SELF;
?>?group_id=<?php echo $group_id;?>&export=support_request_format">available</a>.

<?php
    echo '<li><b><a href="'.$PHP_SELF."?group_id=$group_id&export=survey_responses\">Survey Responses export</a></b><br>"."\n";
?>
A list of all the responses to all the surveys posted by your project. Exported survey response fields as well as their format and
meaning are <a href="<?php echo $PHP_SELF; ?>?group_id=<?php echo
$group_id;?>&export=survey_responses_format">available</a>.


</ul>

Optional data exports:

<ul>
<?php
    echo '<li><b><a href="'.$PHP_SELF."?group_id=$group_id&export=bug_bug_deps\">Bug-Bug Dependencies export</a></b><br>"."\n";
?>
A list of all bug to bug dependencies. Exported fields as well as
their format and meaning are <a href="<?php echo $PHP_SELF;
?>?group_id=<?php echo
$group_id;?>&export=bug_bug_deps_format">available</a>.

<?php
    echo '<li><b><a href="'.$PHP_SELF."?group_id=$group_id&export=bug_task_deps\">Bug-Task Dependencies export</a></b><br>"."\n";
?>
A list of all bug to task dependencies. Exported fields as well as
their format and meaning are <a href="<?php echo $PHP_SELF;
?>?group_id=<?php echo
$group_id;?>&export=bug_task_deps_format">available</a>.


<?php
    echo '<li><b><a href="'.$PHP_SELF."?group_id=$group_id&export=task_task_deps\">Task-Task Dependencies export</a></b><br>"."\n";
?>
A list of all task to task dependencies. Exported fields as well as
their format and meaning are <a href="<?php echo $PHP_SELF;
?>?group_id=<?php echo
$group_id;?>&export=task_task_deps_format">available</a>.

<?php
    echo '<li><b><a href="'.$PHP_SELF."?group_id=$group_id&export=task_assigned_to\">Task Assignees export</a></b><br>"."\n";
?>
A list of tasks and the project members in charge. Exported fields as
well as their format and meaning are <a href="<?php echo $PHP_SELF;
?>?group_id=<?php echo
$group_id;?>&export=task_assigned_to_format">available</a>.


</ul>

<h3><u>Direct Database Access</u></h3>

<P>Alternatively you can generate your own project database on the
<?php print $GLOBALS['sys_name']; ?> server and access it with any ODBC/JDBC (e.g MS-Access,
Excel,...) capable tool directly from your workstation. Depending on
the size of your project data the generation of the project database
may take a while.

<p>Note that in order to use your project database you must first
install the MySQL ODBC driver (or JDBC driver if you use a Java
enabled tool) on your desktop. See our <u><a
href="/docman/display_doc.php?docid=85&group_id=1">instructions</a></u>
for more information.

<ul>
<?php
    echo '<li><b><a href="'.$PHP_SELF."?group_id=$group_id&export=project_db\">Generate Full Project Database</a></b>"."\n";
?>
</ul>

<?php
    display_db_params ();
    site_project_footer( array() );
    break;
}
?>
