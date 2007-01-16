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
require_once('service.php');
require_once('www/forum/forum_utils.php');
require_once('www/admin/admin_utils.php');
require_once('common/tracker/ArtifactType.class');
require_once('common/tracker/ArtifactTypeFactory.class');
require_once('common/tracker/ArtifactFieldFactory.class');
require_once('common/tracker/ArtifactField.class');
require_once('common/tracker/ArtifactFieldSetFactory.class');
require_once('common/tracker/ArtifactFieldSet.class');
require_once('common/tracker/ArtifactReport.class');
require_once('common/tracker/ArtifactReportFactory.class');
require_once('common/include/ReferenceManager.class');
require_once('trove.php');
require_once('pfamily.php');

$Language->loadLanguageMsg('register/register');

if (isset($show_confirm) && $show_confirm) {

    $HTML->header(array('title'=>$Language->getText('register_confirmation','registration_complete')));
    
    // Set the categories for the project.
    // there is at least a $root1[xxx]
	while (list($rootnode,$value) = each($root1)) {
		// check for array, then clear each root node for group
		db_query('DELETE FROM trove_group_link WHERE group_id='.$group_id
			 .' AND trove_cat_root='.$rootnode);
		
		for ($i=1;$i<=$GLOBALS['TROVE_MAXPERROOT'];$i++) {
			$varname = 'root'.$i;
			// check to see if exists first, then insert into DB
			if (${$varname}[$rootnode]) {
				trove_setnode($group_id,${$varname}[$rootnode],$rootnode);
			}
		}
	}
    
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
		"license_other='".htmlspecialchars($form_license_other)."' ".
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

        
	// Instanciate all services from the project template that are 'active'
	$group = group_get_object($group_id);
        if (!$group || !is_object($group) || $group->isError()) {
            exit_no_group();
        }

	$template_id = $group->getTemplate();

	$template_group = group_get_object($template_id);
	if (!$template_group || !is_object($template_group) || $template_group->isError()) {
	  exit_no_group();
        }

	$system_template = ($template_group->getStatus() == 's');

	
	if (!$system_template) {
	  $template_name = $template_group->getUnixName();
	}

        $sql="SELECT * FROM service WHERE group_id=$template_id AND is_active=1";
        $result=db_query($sql);
        while ($arr = db_fetch_array($result)) {
            if (!service_create_service($arr, $group_id, array(
                'system' => $system_template,
                'name'   => $system_template ? '' : $template_name,
                'id'     => $template_id
            ))) {
                exit_error($Language->getText('global','error'),$Language->getText('register_confirmation','cant_create_service'));
            }
        }

        // Activate other system references not associated with any service
        $reference_manager =& ReferenceManager::instance();
        $reference_manager->addSystemReferencesWithoutService($template_id,$group_id);


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

	//Copy ugroups
	ugroup_copy_ugroups($template_id,$group_id,$ugroup_mapping);

	//Set up some mailing lists
	//will be done at some point. needs to communicate with geocrawler
	// TBD
	
        // Generic Trackers Creation
        
        $atf = new ArtifactTypeFactory($template_group);
        //$tracker_error = "";
        // Add all trackers from template project (tracker templates) that need to be instanciated for new trackers.
        $res = $atf->getTrackerTemplatesForNewProjects();
        while ($arr_template = db_fetch_array($res)) {
            $ath_temp = new ArtifactType($template_group,$arr_template['group_artifact_id']);
	    $new_at_id = $atf->create($group_id,$template_id,$ath_temp->getID(),db_escape_string($ath_temp->getName()),db_escape_string($ath_temp->getDescription()),$ath_temp->getItemName(),$ugroup_mapping);
            if ( !$new_at_id ) {
                $feedback .= $atf->getErrorMessage()."<br>";
            } else {

	        // Copy all the artifacts from the template tracker to the new tracker
	        $ath_new = new ArtifactType($group,$new_at_id);
		
		// not now. perhaps one day
	        //if (!$ath_new->copyArtifacts($ath_temp->getID()) ) {
		//$feedback .= $ath_new->getErrorMessage()."<br>";
		//}
                
		// Create corresponding reference
                $ref=& new Reference(0, // no ID yet
                                     strtolower($ath_temp->getItemName()),
                                     $Language->getText('project_reference','reference_art_desc_key'), // description
                                     '/tracker/?func=detail&aid=$1&group_id=$group_id', // link
                                     'P', // scope is 'project'
                                     '',  // service ID - N/A
                                     '1', // is_used
                                     $group_id);
                $result=$reference_manager->createReference($ref,true); // Force reference creation because default trackers use reserved keywords
           }
        }

	//Create project specific references if template is not default site template
	if (!$system_template) {
	  $reference_manager =& ReferenceManager::instance();
	  $reference_manager->addProjectReferences($template_id,$group_id);
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

	$result=db_query("DELETE FROM trove_group_link WHERE group_id='$group_id'");
	pf_deleteAll($group_id);

	echo '
		<H2>'.$Language->getText('register_confirmation','project_deleted').'</H2>
		<P>
		<B>'.$Language->getText('register_confirmation','try_again').'</B>';
	$HTML->footer(array());

} else {
	exit_error($Language->getText('global','error'),$Language->getText('register_category','var_missing',$GLOBALS['sys_email_admin']));

}

?>

