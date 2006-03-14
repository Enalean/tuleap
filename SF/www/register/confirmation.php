<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// $Id$

require_once('pre.php');
session_require(array('isloggedin'=>'1'));
require_once('vars.php');
require_once('www/forum/forum_utils.php');
require_once('www/admin/admin_utils.php');
require_once('common/tracker/ArtifactType.class');
require_once('common/tracker/ArtifactFieldFactory.class');
require_once('common/tracker/ArtifactField.class');
require_once('common/tracker/ArtifactReport.class');
require_once('common/tracker/ArtifactReportFactory.class');
require_once('common/include/ReferenceManager.class');

$Language->loadLanguageMsg('register/register');

if (isset($show_confirm) && $show_confirm) {

    $HTML->header(array('title'=>$Language->getText('register_confirmation','registration_complete')));

    include($Language->getContent('register/confirmation'));

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
		"license_other='".htmlspecialchars($form_license_other)."'".
		"WHERE group_id='$group_id' AND rand_hash='__$rand_hash'");

	if (db_affected_rows($result) < 1) {
		exit_error($Language->getText('global','error'),$Language->getText('register_confirmation','upd_fail',array($GLOBALS['sys_email_admin'],db_error())));
	}

	// define a module
	$result=db_query("INSERT INTO filemodule (group_id,module_name) VALUES ('$group_id','".group_getunixname($group_id)."')");
	if (!$result) {
            list($host,$port) = explode(':',$GLOBALS['sys_default_domain']);		
            exit_error($Language->getText('global','error'),$Language->getText('register_confirmation','ins_file_fail',array($host,db_error())));
	}

	// make the current user a project admin as well as admin
	// on all CodeX services
	$result=db_query("INSERT INTO user_group (user_id,group_id,admin_flags,bug_flags,forum_flags,project_flags,patch_flags,support_flags,doc_flags,file_flags,wiki_flags) VALUES ("
		. user_getid() . ","
		. $group_id . ","
		. "'A'," // admin flags
		. "2," // bug flags
		. "2," // forum flags
		. "2," // project flags
		. "2," // patch flags
		. "2," // support flags
		. "2," // doc flags
		. "2," // file_flags	
		. "2)"); // wiki_flags	
	if (!$result) {
	    exit_error($Language->getText('global','error'),$Language->getText('register_confirmation','set_owner_fail',array($GLOBALS['sys_email_admin'],db_error())));
	}

	//Add a couple of forums for this group and make the project creator 
	// (current user) monitor these forums
	$fid = forum_create_forum($group_id,addslashes($Language->getText('register_confirmation','open_discussion')),1,1,
				  addslashes($Language->getText('register_confirmation','general_discussion')));
	if ($fid != -1) forum_add_monitor($fid, user_getid());

	$fid = forum_create_forum($group_id,addslashes($Language->getText('global','help')),1,1,
				  addslashes($Language->getText('register_confirmation','get_help')));
	if ($fid != -1) forum_add_monitor($fid, user_getid());
	$fid = forum_create_forum($group_id,addslashes($Language->getText('register_confirmation','developers')),0,1,
				  addslashes($Language->getText('register_confirmation','proj_dev_discussion')));
	if ($fid != -1) forum_add_monitor($fid, user_getid());

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
                exit_error($Language->getText('global','error'),$Language->getText('register_confirmation','cant_create_service'));
            }

            // activate corresponding references
            $reference_manager =& ReferenceManager::instance();
            $reference_manager->addSystemReferencesForService($group_id,$arr['short_name']);
        }

        // Activate other system references not associated with any service
        $reference_manager =& ReferenceManager::instance();
        $reference_manager->addSystemReferencesWithoutService($group_id);


        // Create default document group
        $query = "INSERT INTO doc_groups(groupname,group_id,group_rank) " 
	."values ("
	."'Documents',"
	."'$group_id',"
	."'10')";
		
        $result=db_query($query);
        if (!$result) {
            exit_error($Language->getText('global','error'),$Language->getText('register_confirmation','cant_create_docgroup'));
        }


	//Set up some mailing lists
	//will be done at some point. needs to communicate with geocrawler
	// TBD
	
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
	
	// Show the final registration complete message and send email
	// notification (it's all in the content part)
	include($Language->getContent('register/complete'));
	site_header(array('title'=>$Language->getText('register_confirmation','registration_complete')));
    echo $content;
	site_footer(array());

} else if ($i_disagree && $group_id && $rand_hash) {

	$HTML->header(array('title'=>$Language->getText('register_confirmation','registration_deleted')));
	$result=db_query("DELETE FROM groups ".
		"WHERE group_id='$group_id' AND rand_hash='__$rand_hash'");

	echo '
		<H2>'.$Language->getText('register_confirmation','project_deleted').'</H2>
		<P>
		<B>'.$Language->getText('register_confirmation','try_again').'</B>';
	$HTML->footer(array());

} else {
	exit_error($Language->getText('global','error'),$Language->getText('register_category','var_missing',$GLOBALS['sys_email_admin']));

}

?>

