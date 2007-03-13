<?php
require_once('vars.php');
require_once('service.php');
require_once('www/forum/forum_utils.php');
require_once('www/admin/admin_utils.php');
require_once('common/tracker/ArtifactType.class.php');
require_once('common/tracker/ArtifactTypeFactory.class.php');
require_once('common/tracker/ArtifactFieldFactory.class.php');
require_once('common/tracker/ArtifactField.class.php');
require_once('common/tracker/ArtifactFieldSetFactory.class.php');
require_once('common/tracker/ArtifactFieldSet.class.php');
require_once('common/tracker/ArtifactReport.class.php');
require_once('common/tracker/ArtifactReportFactory.class.php');
require_once('common/include/ReferenceManager.class.php');
require_once('trove.php');
require_once('common/event/EventManager.class.php');

/**
* create_project
* 
* Create a new project
*
* @param  data  
*/
function create_project($data) {
    srand((double)microtime()*1000000);
    $random_num=rand(0,1000000);
    
    // Make sure default project privacy status is defined. If not
    // then default to "public"
    if (!isset($GLOBALS['sys_is_project_public'])) {
        $GLOBALS['sys_is_project_public'] = 1;
    }
    
    // make group entry
    $insert_data = array(
        'group_name'          => "'". htmlspecialchars(mysql_real_escape_string($data['project']['form_full_name'])) ."'",
        'is_public'           => $GLOBALS['sys_is_project_public'],
        'unix_group_name'     => "'". $data['project']['form_unix_name'] ."'",
        'http_domain'         => "'". $data['project']['form_unix_name'] .'.'. $GLOBALS['sys_default_domain'] ."'",
        'status'              => "'P'",
        'unix_box'            => "'shell1'",
        'cvs_box'             => "'cvs1'",
        'license'             => "'". htmlspecialchars(mysql_real_escape_string($data['project']['form_license'])) ."'",
        'license_other'       => "'". htmlspecialchars(mysql_real_escape_string($data['project']['form_license_other'])) ."'",
        'short_description'   => "'". htmlspecialchars(mysql_real_escape_string($data['project']['form_short_description'])) ."'",
        'register_purpose'    => "'". htmlspecialchars(mysql_real_escape_string($data['project']['form_purpose'])) ."'",
        'required_software'   => "'". htmlspecialchars(mysql_real_escape_string($data['project']['form_required_sw'])) ."'",
        'patents_ips'         => "'". htmlspecialchars(mysql_real_escape_string($data['project']['form_patents'])) ."'",
        'other_comments'      => "'". htmlspecialchars(mysql_real_escape_string($data['project']['form_comments'])) ."'",
        'register_time'       => time(),
        'rand_hash'           => "'". md5($random_num) ."'",
        'built_from_template' => $data['project']['built_from_template'],
    );
    $sql = 'INSERT INTO groups('. implode(', ', array_keys($insert_data)) .') VALUES ('. implode(', ', array_values($insert_data)) .')';
    $result=db_query($sql);
    if (!$result) {
        exit_error($GLOBALS['Language']->getText('global','error'),$GLOBALS['Language']->getText('register_confirmation','upd_fail',array($GLOBALS['sys_email_admin'],db_error())));
    } else {
        $group_id = db_insertid($result);
        
        // insert trove categories
        foreach($data['project']['trove'] as $root => $values) {
            foreach($values as $value) {
                db_query("INSERT INTO trove_group_link (trove_cat_id,trove_cat_version,"
                    ."group_id,trove_cat_root) VALUES (". $value .",". time() .",". $group_id .",". $root .")");
            }
        }
        
        // define a module
        $result=db_query("INSERT INTO filemodule (group_id,module_name) VALUES ('$group_id','".group_getunixname($group_id)."')");
        if (!$result) {
                list($host,$port) = explode(':',$GLOBALS['sys_default_domain']);		
                exit_error($GLOBALS['Language']->getText('global','error'),$GLOBALS['Language']->getText('register_confirmation','ins_file_fail',array($host,db_error())));
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
            exit_error($GLOBALS['Language']->getText('global','error'),$GLOBALS['Language']->getText('register_confirmation','set_owner_fail',array($GLOBALS['sys_email_admin'],db_error())));
        }
        
        
        /*//Add a couple of forums for this group and make the project creator 
        // (current user) monitor these forums
        $fid = forum_create_forum($group_id,addslashes($GLOBALS['Language']->getText('register_confirmation','open_discussion')),1,1,
                      addslashes($GLOBALS['Language']->getText('register_confirmation','general_discussion')), $need_feedback = false);
        if ($fid != -1) forum_add_monitor($fid, user_getid());
        
        $fid = forum_create_forum($group_id,addslashes($GLOBALS['Language']->getText('global','help')),1,1,
                      addslashes($GLOBALS['Language']->getText('register_confirmation','get_help')), $need_feedback = false);
        if ($fid != -1) forum_add_monitor($fid, user_getid());
        $fid = forum_create_forum($group_id,addslashes($GLOBALS['Language']->getText('register_confirmation','developers')),0,1,
                      addslashes($GLOBALS['Language']->getText('register_confirmation','proj_dev_discussion')), $need_feedback = false);
        if ($fid != -1) forum_add_monitor($fid, user_getid());
        */
            
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
        
        $system_template = ($template_group->getStatus() == 's' || $template_group->getStatus() == 'S');
        
        
        if (!$system_template) {
          $template_name = $template_group->getUnixName();
        }
        
        $sql="SELECT * FROM service WHERE group_id=$template_id AND is_active=1";
        $result=db_query($sql);
        while ($arr = db_fetch_array($result)) {
            $is_used   = isset($data['project']['services'][$arr['service_id']]['is_used'])   ? $data['project']['services'][$arr['service_id']]['is_used']   : '1';
            $server_id = 
                isset($data['project']['services'][$arr['service_id']]['server_id']) &&
                $data['project']['services'][$arr['service_id']]['server_id'] ? 
                $data['project']['services'][$arr['service_id']]['server_id'] : 
                'null';
            if (!service_create_service($arr, $group_id, array(
                'system' => $system_template,
                'name'   => $system_template ? '' : $template_name,
                'id'     => $template_id,
                'is_used'   => $is_used,
                'server_id' => $server_id,
            ))) {
                exit_error($GLOBALS['Language']->getText('global','error'),$GLOBALS['Language']->getText('register_confirmation','cant_create_service') .'<br>'. db_error());
            }
        }
        
        //Copy forums from template project 
        $sql = "SELECT forum_name, is_public, description FROM forum_group_list WHERE group_id=$template_id ";
        $result=db_query($sql);
        while ($arr = db_fetch_array($result)) {
            $fid = forum_create_forum($group_id,$arr['forum_name'],$arr['is_public'],1,
                      $arr['description'], $need_feedback = false);
            if ($fid != -1) forum_add_monitor($fid, user_getid());
        }
        
        //copy cvs infos
        $sql = "SELECT cvs_tracker, cvs_watch_mode, cvs_preamble FROM groups WHERE group_id=$template_id ";
        $result = db_query($sql);
        $arr = db_fetch_array($result);
        $query = "UPDATE groups SET cvs_tracker='".$arr['cvs_tracker']."',  cvs_watch_mode='".$arr['cvs_watch_mode']."' , cvs_preamble='".$arr['cvs_preamble']."' " .
                 "WHERE group_id = '$group_id'";
        var_dump($result);
        echo "<br>";
        var_dump($arr);
        echo "<br>".$query;
        $result=db_query($query);
        if (!$result) {
            exit_error($GLOBALS['Language']->getText('global','error'),$GLOBALS['Language']->getText('register_confirmation','cant_copy_cvs_info'));
        }
        
        //copy svn infos
        $sql = "SELECT svn_tracker, svn_preamble FROM groups WHERE group_id=$template_id ";
        $result = db_query($sql);
        $arr = db_fetch_array($result);
        $query = "UPDATE groups SET svn_tracker='".$arr['svn_tracker']."',  svn_preamble='".$arr['svn_preamble']."' " .
                 "WHERE group_id = $group_id";
        echo "<br>";
        var_dump($result);
        echo "<br>";
        var_dump($arr);
        echo "<br>".$query;
        $result=db_query($query);
        if (!$result) {
            exit_error($GLOBALS['Language']->getText('global','error'),$GLOBALS['Language']->getText('register_confirmation','cant_copy_svn_info'));
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
            exit_error($GLOBALS['Language']->getText('global','error'),$GLOBALS['Language']->getText('register_confirmation','cant_create_docgroup'));
        }
        
        //Copy ugroups
        $ugroup_mapping = array();
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
                $GLOBALS['Response']->addFeedback('error', $atf->getErrorMessage());
            } else {
                
                // Copy all the artifacts from the template tracker to the new tracker
                $ath_new = new ArtifactType($group,$new_at_id);
                
                // not now. perhaps one day
                    //if (!$ath_new->copyArtifacts($ath_temp->getID()) ) {
                //$GLOBALS['Response']->addFeedback('info', $ath_new->getErrorMessage());
                //}
                        
                // Create corresponding reference
                $ref=& new Reference(0, // no ID yet
                                     strtolower($ath_temp->getItemName()),
                                     $GLOBALS['Language']->getText('project_reference','reference_art_desc_key'), // description
                                     '/tracker/?func=detail&aid=$1&group_id=$group_id', // link
                                     'P', // scope is 'project'
                                     '',  // service ID - N/A
                                     '1', // is_used
                                     $group_id);
                $result = $reference_manager->createReference($ref,true); // Force reference creation because default trackers use reserved keywords
           }
        }
        
        //Create project specific references if template is not default site template
        if (!$system_template) {
            $reference_manager =& ReferenceManager::instance();
            $reference_manager->addProjectReferences($template_id,$group_id);
        }
        
        // Raise an event for plugin configuration
        $em =& EventManager::instance();
        $em->processEvent('register_project_creation', array(
            'ugroupsMapping' => $ugroup_mapping,
            'group_id'       => $group_id
        ));
        
        $content = '';
        include($GLOBALS['Language']->getContent('project/complete'));
        site_header(array('title'=>$GLOBALS['Language']->getText('register_confirmation','registration_complete')));
        echo $content;
        site_footer(array());
        exit(0);
    }
}

?>
