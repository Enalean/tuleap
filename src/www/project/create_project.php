<?php
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
require_once('common/reference/ReferenceManager.class.php');
require_once('trove.php');
require_once('common/event/EventManager.class.php');
require_once('common/wiki/lib/WikiCloner.class.php');
require_once('common/widget/WidgetLayoutManager.class.php');

define('PROJECT_APPROVAL_BY_ADMIN', 'P');
define('PROJECT_APPROVAL_AUTO',     'A');

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
    if (isset($GLOBALS['sys_disable_subdomains']) && $GLOBALS['sys_disable_subdomains']) {
      $http_domain=$GLOBALS['sys_default_domain'];
    } else {
      $http_domain=$data['project']['form_unix_name'].'.'.$GLOBALS['sys_default_domain'];
    }

    //Verify if the approbation of the new project is automatic or not
    $auto_approval = ForgeConfig::get('sys_project_approval', 1) ? PROJECT_APPROVAL_BY_ADMIN : PROJECT_APPROVAL_AUTO;

    if (isset($data['project']['is_public'])) {
        $access = ($data['project']['is_public']) ? Project::ACCESS_PUBLIC : Project::ACCESS_PRIVATE;
    } else {
        $access = ForgeConfig::get('sys_is_project_public') ? Project::ACCESS_PUBLIC : Project::ACCESS_PRIVATE;
    }

    // make group entry
    $insert_data = array(
        'group_name'          => "'". htmlspecialchars(mysql_real_escape_string($data['project']['form_full_name'])) ."'",
        'access'              => "'".$access."'",
        'unix_group_name'     => "'". db_es($data['project']['form_unix_name']) ."'",
        'http_domain'         => "'". db_es($http_domain) ."'",
        'status'              => "'P'",
        'unix_box'            => "'shell1'",
        'cvs_box'             => "'cvs1'",
        'short_description'   => "'". htmlspecialchars(mysql_real_escape_string($data['project']['form_short_description'])) ."'",
        'register_time'       => time(),
        'rand_hash'           => "'". md5($random_num) ."'",
        'built_from_template' => db_ei($data['project']['built_from_template']),
        'type'                => ($data['project']['is_test'] ? 3 : 1),
    );
    $sql = 'INSERT INTO groups('. implode(', ', array_keys($insert_data)) .') VALUES ('. implode(', ', array_values($insert_data)) .')';
    $result=db_query($sql);

    if (!$result) {
        exit_error($GLOBALS['Language']->getText('global','error'),$GLOBALS['Language']->getText('register_confirmation','upd_fail',array($GLOBALS['sys_email_admin'],db_error())));
    } else {
        $group_id = db_insertid($result);
        
        // insert descriptions 
        $descfieldsinfos = getProjectsDescFieldsInfos();

		for($i=0;$i<sizeof($descfieldsinfos);$i++){
			if(isset($data['project']["form_".$descfieldsinfos[$i]["group_desc_id"]]) && ($data['project']["form_".$descfieldsinfos[$i]["group_desc_id"]]!='')){
				$sql="INSERT INTO group_desc_value (group_id, group_desc_id, value) VALUES ('".db_ei($group_id)."','".db_ei($descfieldsinfos[$i]["group_desc_id"])."','".db_escape_string(trim($data['project']["form_".$descfieldsinfos[$i]["group_desc_id"]]))."')";
				$result=db_query($sql);
        		
        		
        		if (!$result) {
                	list($host,$port) = explode(':',$GLOBALS['sys_default_domain']);		
                	exit_error($GLOBALS['Language']->getText('global','error'),$GLOBALS['Language']->getText('register_confirmation','ins_desc_fail',array($host,db_error())));
        		}
			}
		}
        
        
        
        // insert trove categories
        if (isset($data['project']['trove'])) {
            foreach($data['project']['trove'] as $root => $values) {
                foreach($values as $value) {
                    db_query("INSERT INTO trove_group_link (trove_cat_id,trove_cat_version,"
                             ."group_id,trove_cat_root) VALUES (". db_ei($value) .",". time() .",". db_ei($group_id) .",". db_ei($root) .")");
                }
            }
        }

        // define a module
        $project_manager = ProjectManager::instance();
        $result=db_query("INSERT INTO filemodule (group_id,module_name) VALUES ('$group_id','".$project_manager->getProject($group_id)->getUnixName()."')");
        if (!$result) {
                list($host,$port) = explode(':',$GLOBALS['sys_default_domain']);		
                exit_error($GLOBALS['Language']->getText('global','error'),$GLOBALS['Language']->getText('register_confirmation','ins_file_fail',array($host,db_error())));
        }
        
        // make the current user a project admin as well as admin
        // on all Codendi services
        $result=db_query("INSERT INTO user_group (user_id,group_id,admin_flags,bug_flags,forum_flags,project_flags,patch_flags,support_flags,doc_flags,file_flags,wiki_flags,svn_flags,news_flags) VALUES ("
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
            . "2," // wiki_flags
            . "2," // svn_flags	
            . "2)"); // news_flags	
        if (!$result) {
            exit_error($GLOBALS['Language']->getText('global','error'),$GLOBALS['Language']->getText('register_confirmation','set_owner_fail',array($GLOBALS['sys_email_admin'],db_error())));
        }
        
        // clear the user data to take into account this new group.
        $user = UserManager::instance()->getCurrentUser();
        $user->clearGroupData();
            
        // Instanciate all services from the project template that are 'active'
        $group = $project_manager->getProject($group_id);
        if (!$group || !is_object($group)) {
            exit_no_group();
        }
        //set up the group_id
        $_REQUEST['group_id'] = $_GET['group_id'] = $group_id;
        $request =& HTTPRequest::instance();
        $request->params['group_id'] = $_REQUEST['group_id'];

        $template_id = $group->getTemplate();
        
        $template_group = $project_manager->getProject($template_id);
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
            if (isset($data['project']['services'][$arr['service_id']]['is_used'])) {
                 $is_used = $data['project']['services'][$arr['service_id']]['is_used'];
            } else {
               $is_used = '0';
               if ($arr['short_name'] == 'admin' || $arr['short_name'] == 'summary') {
                   $is_used = '1';
               }
            }
            
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
        //Add the import of the message to requester from the parent project if defined
        $dar = $project_manager->getMessageToRequesterForAccessProject($template_id);
        if ($dar && !$dar->isError() && $dar->rowCount() == 1) {
            $row = $dar->getRow();
            $result = $project_manager->setMessageToRequesterForAccessProject($group_id, $row['msg_to_requester']);
        } else {
            $result = $project_manager->setMessageToRequesterForAccessProject($group_id, 'member_request_delegation_msg_to_requester');
        }
        if (!$result) {
            exit_error($GLOBALS['Language']->getText('global','error'),$GLOBALS['Language']->getText('register_confirmation','cant_copy_msg_to_requester'));
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
        $sql = "SELECT cvs_tracker, cvs_watch_mode, cvs_preamble, cvs_is_private FROM groups WHERE group_id=$template_id ";
        $result = db_query($sql);
        $arr = db_fetch_array($result);
        $query = "UPDATE groups 
                  SET cvs_tracker='".db_ei($arr['cvs_tracker'])."',
                      cvs_watch_mode='".db_ei($arr['cvs_watch_mode'])."' ,
                      cvs_preamble='".db_escape_string($arr['cvs_preamble'])."',
                      cvs_is_private = ".db_escape_int($arr['cvs_is_private']) ."
                  WHERE group_id = '$group_id'";
        
        $result=db_query($query);
        if (!$result) {
            exit_error($GLOBALS['Language']->getText('global','error'),$GLOBALS['Language']->getText('register_confirmation','cant_copy_cvs_infos'));
        }
        
        //copy svn infos
        $current_timestamp = db_escape_int($_SERVER['REQUEST_TIME']);

        $sql = "INSERT INTO svn_accessfile_history (version_number, group_id, version_date)
                VALUES (1, $group_id, $current_timestamp)";

        $result = db_query($sql);
        if (!$result) {
            exit_error($GLOBALS['Language']->getText('global','error'),$GLOBALS['Language']->getText('register_confirmation','cant_copy_svn_infos'));
        }

        $sql = "SELECT svn_tracker, svn_preamble, svn_mandatory_ref, svn_commit_to_tag_denied FROM groups WHERE group_id=$template_id ";
        $result = db_query($sql);
        $arr = db_fetch_array($result);
        $query = "UPDATE groups, svn_accessfile_history
                  SET svn_tracker='".db_ei($arr['svn_tracker'])."',
                      svn_mandatory_ref='".db_ei($arr['svn_mandatory_ref'])."',
                      svn_preamble='".db_escape_string($arr['svn_preamble'])."',
                      svn_commit_to_tag_denied='".db_ei($arr['svn_commit_to_tag_denied'])."',
                      svn_accessfile_version_id = svn_accessfile_history.id
                  WHERE groups.group_id = $group_id
                      AND groups.group_id = svn_accessfile_history.group_id";
        
        $result=db_query($query);
        if (!$result) {
            exit_error($GLOBALS['Language']->getText('global','error'),$GLOBALS['Language']->getText('register_confirmation','cant_copy_svn_infos'));
        }

        // Activate other system references not associated with any service
        $reference_manager =& ReferenceManager::instance();
        $reference_manager->addSystemReferencesWithoutService($template_id,$group_id);

        //Copy ugroups
        $ugroup_mapping = array();
        ugroup_copy_ugroups($template_id,$group_id,$ugroup_mapping);
        
        $sql_ugroup_mapping = ' ugroup_id ';
        if (is_array($ugroup_mapping) && count($ugroup_mapping)) {
            $sql_ugroup_mapping = ' CASE ugroup_id ';
            foreach($ugroup_mapping as $key => $val) {
                $sql_ugroup_mapping .= ' WHEN '. $key .' THEN '. $val;
            }
            $sql_ugroup_mapping .= ' ELSE ugroup_id END ';
        }
        //Copy packages from template project
        $sql  = "SELECT package_id, name, status_id, rank, approve_license FROM frs_package WHERE group_id = $template_id";
        if ($result = db_query($sql)) {
            while($p_data = db_fetch_array($result)) {
                $template_package_id = $p_data['package_id'];
                $sql = sprintf("INSERT INTO frs_package(group_id, name, status_id, rank, approve_license) VALUES (%s, '%s', %s, %s, %s)",
                    $group_id,
                    db_escape_string($p_data['name']),
                    db_ei($p_data['status_id']),
                    db_ei($p_data['rank']),
                    db_ei($p_data['approve_license'])
                );
                $rid = db_query($sql);
                if ($rid) {
                    $package_id = db_insertid($rid);
                    $sql = "INSERT INTO permissions(permission_type, object_id, ugroup_id) 
                      SELECT permission_type, $package_id, $sql_ugroup_mapping
                      FROM permissions
                      WHERE permission_type = 'PACKAGE_READ'
                        AND object_id = $template_package_id";
                    db_query($sql);
                }
            }
        }
        
        //Set up some mailing lists
        //will be done at some point. needs to communicate with geocrawler
        // TBD
        
        // Generic Trackers Creation
        $tracker_mapping = array();
        $report_mapping  = array();
        if (TrackerV3::instance()->available()) {
            $atf = new ArtifactTypeFactory($template_group);
            //$tracker_error = "";
            // Add all trackers from template project (tracker templates) that need to be instanciated for new trackers.
            $res = $atf->getTrackerTemplatesForNewProjects();
            while ($arr_template = db_fetch_array($res)) {
                $ath_temp = new ArtifactType($template_group,$arr_template['group_artifact_id']);
                $report_mapping_for_this_tracker = array();
                $new_at_id = $atf->create($group_id,$template_id,$ath_temp->getID(),db_escape_string($ath_temp->getName()),db_escape_string($ath_temp->getDescription()),$ath_temp->getItemName(),$ugroup_mapping,$report_mapping_for_this_tracker);
                if ( !$new_at_id ) {
                    $GLOBALS['Response']->addFeedback('error', $atf->getErrorMessage());
                } else {
                    $report_mapping = $report_mapping + $report_mapping_for_this_tracker;
                    $tracker_mapping[$ath_temp->getID()] = $new_at_id;

                    // Copy all the artifacts from the template tracker to the new tracker
                    $ath_new = new ArtifactType($group,$new_at_id);

                    // not now. perhaps one day
                        //if (!$ath_new->copyArtifacts($ath_temp->getID()) ) {
                    //$GLOBALS['Response']->addFeedback('info', $ath_new->getErrorMessage());
                    //}

                    // Create corresponding reference
                    $ref = new Reference(0, // no ID yet
                                         strtolower($ath_temp->getItemName()),
                                         $GLOBALS['Language']->getText('project_reference','reference_art_desc_key'), // description
                                         '/tracker/?func=detail&aid=$1&group_id=$group_id', // link
                                         'P', // scope is 'project'
                                         'tracker',  // service short name
                                         ReferenceManager::REFERENCE_NATURE_ARTIFACT,   // nature
                                         '1', // is_used
                                         $group_id);
                    $result = $reference_manager->createReference($ref,true); // Force reference creation because default trackers use reserved keywords
               }
            }
        }

        // Clone wiki from the template
        $clone = new WikiCloner($template_id, $group_id);

        // check if the template project has a wiki initialised
        if ($clone->templateWikiExists() and $clone->newWikiIsUsed()){
            //clone wiki.  
            $clone->CloneWiki();
        }
        
        //Create the summary page
        $lm = new WidgetLayoutManager();
        $lm->createDefaultLayoutForProject($group_id, $template_id);
        
        //Create project specific references if template is not default site template
        if (!$system_template) {
            $reference_manager =& ReferenceManager::instance();
            $reference_manager->addProjectReferences($template_id,$group_id);
        }

        // Copy Truncated email option
        $sql = "UPDATE groups AS g1
                JOIN groups AS g2
                  ON g2.group_id = ".db_ei($template_id)."
                SET g1.truncated_emails = g2.truncated_emails
                WHERE g1.group_id = ".db_ei($group_id);
        db_query($sql);

        $result = db_query($query);
        if (!$result) {
            exit_error($GLOBALS['Language']->getText('global','error'),$GLOBALS['Language']->getText('register_confirmation','cant_copy_truncated_emails'));
        }

        // Raise an event for plugin configuration
        $em =& EventManager::instance();
        $em->processEvent('register_project_creation', array(
            'reportMapping'  => $report_mapping, // Trackers v3
            'trackerMapping' => $tracker_mapping, // Trackers v3
            'ugroupsMapping' => $ugroup_mapping,
            'group_id'       => $group_id,
            'template_id'    => $template_id
        ));

        if ($auto_approval == PROJECT_APPROVAL_AUTO) {
            $project_manager->activate($group);
        }

        return $group_id;
    }
}
