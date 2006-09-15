<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// $Id$

$Language->loadLanguageMsg('mail/mail');

function mail_header($params) {
  global $group_id, $Language;

	//required for site_project_header
	$params['group']=$group_id;
	$params['toptab']='mail';

	$project=project_get_object($group_id);

	if (!$project->usesMail()) {
		exit_error($Language->getText('global','error'),$Language->getText('mail_utils','mail_turned_off'));
	}


	site_project_header($params);
	echo '<P><B>';
    // admin link is only displayed if the user is a project administrator
    if (user_ismember($group_id, 'A')) {
        echo '<A HREF="/mail/admin/?group_id='.$group_id.'">'.$Language->getText('mail_utils','admin').'</A>';
        echo ' | ';
    }
	if ($params['help']) {
	    echo help_button($params['help'],false,$Language->getText('global','help'));
	}
	echo '</B><P>';
}
function mail_header_admin($params) {
	global $group_id, $Language;

	//required for site_project_header
	$params['group']=$group_id;
	$params['toptab']='mail';

	$project=project_get_object($group_id);

	if (!$project->usesMail()) {
		exit_error($Language->getText('global','error'),$Language->getText('mail_utils','mail_turned_off'));
	}


	site_project_header($params);
	echo '
		<P><B><A HREF="/mail/admin/?group_id='.$group_id.'">'.$Language->getText('mail_utils','admin').'</A></B>
 | <B><A HREF="/mail/admin/?group_id='.$group_id.'&add_list=1">'.$Language->getText('mail_utils','add_list').'</A></B>
 | <B><A HREF="/mail/admin/?group_id='.$group_id.'&change_status=1">'.$Language->getText('mail_utils','update_list').'</A></B>
';
	if ($params['help']) {
	    echo ' | <B>'.help_button($params['help'],false,$Language->getText('global','help')).'</B>';
	}

}

function mail_footer($params) {
	site_project_footer($params);
}

?>
