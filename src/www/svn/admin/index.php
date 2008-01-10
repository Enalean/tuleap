<?php
//
// CodeX: Breaking Down the Barriers to Source Code Sharing inside Xerox
// Copyright (c) Xerox Corporation, CodeX / CodeX Team, 2004. All Rights Reserved
// http://codex.xerox.com
//
// 
//
//	Originally written by Laurent Julliard 2004, CodeX Team, Xerox
//

require_once('pre.php');    
require_once('../svn_data.php');    

$Language->loadLanguageMsg('svn/svn');

$vGroupId = new Valid_GroupId();
$vGroupId->required();

// need a group_id !!!
if (!$request->valid($vGroupId)) {
    exit_no_group();
} else {
    $group_id = $request->get('group_id');

}

// Must be at least Project Admin to configure this
if (!user_ismember($group_id,'A') && !user_ismember($group_id,'SVN_ADMIN')) {
    exit_permission_denied();
}

$vFunc = new Valid_WhiteList('func', array('general_settings', 'access_control', 'notification'));
$vFunc->required();
if($request->valid($vFunc)) {
    $func = $request->get('func');

switch ($func) {

 case 'general_settings' : {
   require('./general_settings.php');
   break;
 }

 case 'access_control' : {
   require('./access_control.php');
   break;
 }

 case 'notification' : {
   require('./notification.php');
   break;
 }
}
} else {
   // get project object
   $project = project_get_object($group_id);
   if (!$project || !is_object($project) || $project->isError()) {
       exit_no_group();
   }

   svn_header_admin(array ('title'=>$Language->getText('svn_admin_index','admin'),
		      'help' => 'SubversionAdministrationInterface.html'));

   echo '<H2>'.$Language->getText('svn_admin_index','admin').'</H2>';
   echo '<H3><a href="/svn/admin/?func=general_settings&group_id='.$group_id.'">'.$Language->getText('svn_admin_index','gen_sett').'</a></H3>';
   echo '<p>'.$Language->getText('svn_admin_index','welcome').'</p>';
   
   echo '<H3><a href="/svn/admin/?func=access_control&group_id='.$group_id.'">'.$Language->getText('svn_admin_index','access').'</a></H3>';
   echo '<P>'.$Language->getText('svn_admin_index','access_comment').'</P>';
   echo '<H3><a href="/svn/admin/?func=notification&group_id='.$group_id.'">'.$Language->getText('svn_admin_index','email_sett').'</a></H3>';
   echo '<p>'.$Language->getText('svn_admin_index','email_comment').'</P>';

   svn_footer(array());
}
 
?>
