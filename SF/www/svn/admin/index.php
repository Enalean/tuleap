<?php
//
// CodeX: Breaking Down the Barriers to Source Code Sharing inside Xerox
// Copyright (c) Xerox Corporation, CodeX / CodeX Team, 2004. All Rights Reserved
// http://codex.xerox.com
//
// $Id$
//
//	Originally written by Laurent Julliard 2004, CodeX Team, Xerox
//

require ('pre.php');    
require ('../svn_data.php');    
require ('../svn_utils.php');    

// need a group_id !!!
if (!$group_id) {
    exit_no_group();
}

// Must be at least Project Admin to configure this
if (!user_ismember($group_id,'A')) {
    exit_permission_denied();
}


switch ($func) {

 case 'general_settings' : {
   include './general_settings.php';
   break;
 }

 case 'access_control' : {
   include './access_control.php';
   break;
 }

 case 'notification' : {
   include './notification.php';
   break;
 }

 default:


   // get project object
   $project = project_get_object($group_id);
   if (!$project || !is_object($project) || $project->isError()) {
       exit_no_group();
   }

   svn_header_admin(array ('title'=>'Subversion Administration',
		      'help' => 'SubversionAdministrationInterface.html'));

   echo '<H2>Subversion Administration</H2>';
   echo '<H3><a href="/svn/admin/?func=general_settings&group_id='.$group_id.'">General Settings</a></H3>';
   echo '<p>Define the welcome message, database tracing...</p>';
   
   echo '<H3><a href="/svn/admin/?func=access_control&group_id='.$group_id.'">Access Control</a></H3>';
   echo '<P>Define user access permissions on the subversion repository.</P>';
   echo '<H3><a href="/svn/admin/?func=notification&group_id='.$group_id.'">Email Notification Settings</a></H3>';
   echo '<p>Define who is going to receive email notification of the changes commited to the subversion repository.</P>';

   svn_footer(array());
}
 
?>
