<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// $Id$

require($DOCUMENT_ROOT.'/include/pre.php');
session_require(array('isloggedin'=>'1'));
require($DOCUMENT_ROOT.'/include/vars.php');
require($DOCUMENT_ROOT.'/forum/forum_utils.php');
require($DOCUMENT_ROOT.'/admin/admin_utils.php');
require($DOCUMENT_ROOT.'/../common/tracker/ArtifactType.class');
require($DOCUMENT_ROOT.'/../common/tracker/ArtifactFieldFactory.class');
require($DOCUMENT_ROOT.'/../common/tracker/ArtifactField.class');
require($DOCUMENT_ROOT.'/../common/tracker/ArtifactReport.class');
require($DOCUMENT_ROOT.'/../common/tracker/ArtifactReportFactory.class');

$LANG->loadLanguageMsg('register/register');

if ($show_confirm) {

    $HTML->header(array('title'=>$LANG->getText('register_confirmation','registration_complete')));

    include(util_get_content('register/confirmation'));

    $HTML->footer(array());

} else if ($i_agree && $group_id && $rand_hash && $form_short_description && $form_purpose) {
	/*

		Finalize the db entries

	*/

	$result=db_query("UPDATE groups SET status='P', ".
		"short_description='".$form_short_description."', ".
		"register_purpose='".htmlspecialchars($form_purpose)."', ".
		"required_software='".htmlspecialchars($form_required_sw)."', ".
		"patents_ips='".htmlspecialchars($form_patents)."', ".
		"other_comments='".htmlspecialchars($form_comments)."', ".
		"group_name='$form_full_name', license='$form_license', ".
		"license_other='".htmlspecialchars($form_license_other)."', ".
		"project_type='".$project_type."' ".
		"WHERE group_id='$group_id' AND rand_hash='__$rand_hash'");

	if (db_affected_rows($result) < 1) {
		exit_error($LANG->getText('global','error'),$LANG->getText('register_confirmation','upd_fail',array($GLOBALS['sys_email_admin'],db_error())));
	}

	// define a module
	$result=db_query("INSERT INTO filemodule (group_id,module_name) VALUES ('$group_id','".group_getunixname($group_id)."')");
	if (!$result) {
            list($host,$port) = explode(':',$GLOBALS['sys_default_domain']);		
            exit_error($LANG->getText('global','error'),$LANG->getText('register_confirmation','ins_file_fail',array($host,db_error())));
	}

	// make the current user a project admin ad well as admin
	// on all CodeX services
	$result=db_query("INSERT INTO user_group (user_id,group_id,admin_flags,bug_flags,forum_flags,project_flags,patch_flags,support_flags,doc_flags,file_flags) VALUES ("
		. user_getid() . ","
		. $group_id . ","
		. "'A'," // admin flags
		. "2," // bug flags
		. "2," // forum flags
		. "2," // project flags
		. "2," // patch flags
		. "2," // support flags
		. "1," // doc flags
		. "2)"); // file_flags	
	if (!$result) {
		exit_error($LANG->getText('global','error'),$LANG->getText('register_confirmation','set_owner',array($GLOBALS['sys_email_admin'],db_error())));
	}

	//Add a couple of forums for this group and make the project creator 
	// (current user) monitor these forums
	$fid = forum_create_forum($group_id,$LANG->getText('register_confirmation','open_discussion'),1,1,$LANG->getText('register_confirmation','general_discussion'));
	forum_add_monitor($fid, user_getid());

	$fid = forum_create_forum($group_id,$LANG->getText('global','help'),1,1,$LANG->getText('register_confirmation','get_help'));
	forum_add_monitor($fid, user_getid());
	$fid = forum_create_forum($group_id,$LANG->getText('register_confirmation','developers'),0,1,$LANG->getText('register_confirmation','proj_dev_discussion'));
	forum_add_monitor($fid, user_getid());

        // Instanciate all services from group 100 that are 'active'
        $sql="SELECT * FROM service WHERE group_id=100 AND is_active=1";
        $result=db_query($sql);
        while ($arr = db_fetch_array($result)) {
            // Convert link to real values
            // NOTE: if you change link variables here, change them also in SF/www/project/admin/servicebar.php and SF/www/include/Layout.class
            $link=$arr['link'];
            $link=str_replace('$projectname',group_getunixname($group_id),$link);
            $link=str_replace('$sys_default_domain',$GLOBALS['sys_default_domain'],$link);
            $link=str_replace('$group_id',$group_id,$link);
            if ($GLOBALS['sys_force_ssl']) {
                $sys_default_protocol='https'; 
            } else { $sys_default_protocol='http'; }
            $link=str_replace('$sys_default_protocol',$sys_default_protocol,$link);

            $sql2 = "INSERT INTO service (group_id, label, description, short_name, link, is_active, is_used, scope, rank) VALUES ($group_id, '".$arr['label']."', '".$arr['description']."', '".$arr['short_name']."', '".$link."', ".$arr['is_active'].", ".$arr['is_used'].", '".$arr['scope']."', ".$arr['rank'].")";
            $result2=db_query($sql2);
            
            if (!$result2) {
                exit_error($LANG->getText('global','error'),$LANG->getText('register_confirmation','cant_create_service'));
            }
        }

	//Set up some mailing lists
	//will be done at some point. needs to communicate with geocrawler
	// TBD
	
	if ( $sys_activate_tracker ) {
		// Generic Trackers Creation
		$group_100 = group_get_object(100);
		if (!$group_100 || !is_object($group_100) || $group_100->isError()) {
			exit_no_group();
		}
		$group = group_get_object($group_id);
		if (!$group || !is_object($group) || $group->isError()) {
			exit_no_group();
		}
		
		$ath = new ArtifactType($group);

		$tracker_error = "";
                
                // Add all trackers from project 100 (tracker templates) that need to be instanciated for new trackers.
                $res = $ath->getTrackerTemplatesForNewProjects();
		while ($arr_template = db_fetch_array($res)) {
		    $ath_new = new ArtifactType($group_100,$arr_template['group_artifact_id']);
		    if ( !$ath->create($group_id,100,$ath_new->getID(),$ath_new->getName(),$ath_new->getDescription(),$ath_new->getItemName()) ) {
			$tracker_error .= $ath->getErrorMessage()."<br>";
		    }
		}
	}
	
	// Show the final registration complete message and send email
	// notification (it's all in the content part)
	$HTML->header(array('title'=>$LANG->getText('register_confirmation','registration_complete')));

	include(util_get_content('register/complete'));
    
	$HTML->footer(array());

} else if ($i_disagree && $group_id && $rand_hash) {

	$HTML->header(array('title'=>$LANG->getText('register_confirmation','registration_deleted')));
	$result=db_query("DELETE FROM groups ".
		"WHERE group_id='$group_id' AND rand_hash='__$rand_hash'");

	echo '
		<H2>'.$LANG->getText('register_confirmation','project_deleted').'</H2>
		<P>
		<B>'.$LANG->getText('register_confirmation','try_again').'</B>';
	$HTML->footer(array());

} else {
	exit_error($LANG->getText('global','error'),$LANG->getText('register_category','var_missing',$GLOBALS['sys_email_admin']));

}

?>

