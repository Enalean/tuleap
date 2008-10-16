<?php
//
// Copyright (c) Xerox Corporation, CodeX Team, 2001-2003. All rights reserved
//
// 
//
//
//	Originally by to the SourceForge Team,1999-2000
//
//  Written for CodeX by Stephane Bouhet
//

//require_once('common/include/Error.class.php');
require_once('common/tracker/ArtifactType.class.php');
require_once('common/tracker/ArtifactCanned.class.php');


class ArtifactTypeFactory extends Error {

	/**
	 * The Group object.
	 *
	 * @var	 object  $Group.
	 */
	var $Group;

	/**
	 * The ArtifactTypes array.
	 *
	 * @var	 array	ArtifactTypes.
	 */
	var $ArtifactTypes;

	/**
	 *  Constructor.
	 *
	 *	@param	object	The Group object to which this ArtifactTypeFactory is associated
	 *	@return	boolean	success.
	 */
	function ArtifactTypeFactory($Group) {
		$this->Error();
		if ( $Group ) {
			if ($Group->isError()) {
				$this->setError('ArtifactTypeFactory:: '.$Group->getErrorMessage());
				return false;
			}
			$this->Group = $Group;
		}
		
		return true;
	}

	/**
	 *	getGroup - get the Group object this ArtifactType is associated with.
	 *
	 *	@return	object	The Group object.
	 */
	function getGroup() {
		return $this->Group;
	}

	/**
	 *	getStatusIdCount - return a array of each status_id count.
	 *
	 *	@param	group_artifact_id
	 *
	 *	@return	array of counts
	 */
	function getStatusIdCount($group_artifact_id) {
		$count_array=array();
		$sql="select status_id,count(*) from artifact where group_artifact_id = ". db_ei($group_artifact_id).
			 " group by status_id";
		$result = db_query ($sql);

		$rows = db_numrows($result);

		if (!$result || $rows < 1) {
			$this->setError('None Found '.db_error());
			return false;
		} else {
			$count_array['count'] = 0;
			while ($arr = db_fetch_array($result)) {
				if ( $arr['status_id'] == 1 ) {
					$count_array['open_count'] = $arr[1];
				}
				$count_array['count'] += $arr[1];
			}
			return $count_array;
		}
	}		

	/**
	 *	getArtifactTypes - return an array of ArtifactType objects of the current group
	 *
	 *	@return	array	The array of ArtifactType objects.
	 */
	function getArtifactTypes() {
	  global $Language;

		if ($this->ArtifactTypes) {
			return $this->ArtifactTypes;
		}

		$sql="SELECT *, 0 as open_count, 0 as count FROM artifact_group_list
			WHERE group_id='". db_ei($this->Group->getID()) ."'
			AND status != 'D'
			ORDER BY name ASC";

		$result = db_query ($sql);

		$rows = db_numrows($result);

		if (!$result || $rows < 1) {
			$this->setError($Language->getText('tracker_common_type','none_found').' '.db_error());
			return false;
		} else {
			while ($arr = db_fetch_array($result)) {
				// Retrieve status counts
				$arr_count = $this->getStatusIdCount($arr['group_artifact_id']);
				if ( $arr_count ) {
					$arr['open_count'] = array_key_exists('open_count', $arr_count)?$arr_count['open_count']:0;
					$arr['count'] = $arr_count['count'];
				}
                                $new_at=new ArtifactType($this->Group, $arr['group_artifact_id'], $arr);
                                if ($new_at->userCanView()) {
                                    $this->ArtifactTypes[] = $new_at;
                                }
			}
		}
		return $this->ArtifactTypes;
	}

	/**
	 *  getArtifactTypesFromId - return an array of ArtifactType objects of a group
	 *
	 *  @param group_id: the group id
	 *
	 *  @return	array
	 */
	function getArtifactTypesFromId($group_id) {
	  global $Language;

          $sql="SELECT group_artifact_id FROM artifact_group_list
			WHERE group_id='". db_ei($group_id) ."'
			AND status!='D'
			ORDER BY group_artifact_id ASC";
		
          $result = db_query ($sql);
          $rows = db_numrows($result);
          $myArtifactTypes=array();

          if (!$result || $rows < 1) {
              $this->setError($Language->getText('tracker_common_type','none_found').' '.db_error());
              return false;
          } else {
              while ($arr = db_fetch_array($result)) {
                  $new_at=new ArtifactType(group_get_object($group_id), $arr['group_artifact_id']);
                  if ($new_at->userCanView()) {
                      $myArtifactTypes[] = $new_at;
                  }
              }
          }
          return $myArtifactTypes;
	}
	

	/**
	 *	getPendingArtifactTypes - return an array of ArtifactType objects with 'D' status
	 *
	 *  @aparam group_id: the group id
	 *
	 *	@return	resultSet
	 */
	function getPendingArtifactTypes() {
	  global $Language;

		$sql="SELECT group_artifact_id,name, deletion_date, groups.group_name as project_name, groups.group_id FROM artifact_group_list, groups
			WHERE artifact_group_list.status='D'
			AND groups.group_id=artifact_group_list.group_id
			ORDER BY group_artifact_id ASC";

		//echo $sql;
		
		$result = db_query ($sql);
		$rows = db_numrows($result);
		if (!$result || $rows < 1) {
			$this->setError($Language->getText('tracker_common_type','none_found').' '.db_error());
			return false;
		}

		return $result;
	}
	
	/**
	 *	Delete a tracker
	 *
	 *  @aparam atid: the artifact type id
	 *
	 *	@return	boolean
	 */
	function deleteArtifactType($atid) {
		
		// Delete artifact_canned_responses 
		$sql = "DELETE FROM artifact_canned_responses 
			    WHERE group_artifact_id=". db_ei($atid);
		db_query ($sql);

		// Delete artifact_notification  
		$sql = "DELETE FROM artifact_notification  
			    WHERE group_artifact_id=". db_ei($atid);
		db_query ($sql);

		// Delete artifact_notification_event   
		$sql = "DELETE FROM artifact_notification_event   
			    WHERE group_artifact_id=". db_ei($atid);
		db_query ($sql);
		
		// Delete artifact_notification_role   
		$sql = "DELETE FROM artifact_notification_role   
			    WHERE group_artifact_id=". db_ei($atid);
		db_query ($sql);

		// Delete artifact_perm   
		$sql = "DELETE FROM artifact_perm   
			    WHERE group_artifact_id=". db_ei($atid);
		db_query ($sql);
		
        
        // We need to instanciate an artifactType to instanciate the factories
        $artifactType = new ArtifactType($this->getGroup(), $atid, false);
        $art_field_fact = new ArtifactFieldFactory($artifactType);
        $art_fieldset_fact = new ArtifactFieldSetFactory($artifactType);

        // Delete the fields of this tracker
        $art_field_fact->deleteFields($atid);
        // Delete the field sets of this tracker
        $art_fieldset_fact->deleteFieldSets();

        
        // Delete the artifact_report
        $art_report_fact = new ArtifactReportFactory();
        $art_report_fact->deleteReports($atid);
        
        //Generate an event 
        $em =& EventManager::instance();
		$pref_params = array('atid'   => $atid);
		$em->processEvent('artifactType_deleted',$pref_params);
        
        // Delete the artifact rules
        $art_rule_fact = ArtifactRuleFactory::instance();
        $art_rule_fact->deleteRulesByArtifactType($atid);
        
        // Delete artifact_watcher (be carefull, the column is named artifact_group_id)
        $sql = "DELETE FROM artifact_watcher   
			    WHERE artifact_group_id=". db_ei($atid);
		db_query ($sql);
        
        
		// Delete all records linked to artifact_id
	    $sql_artifacts='SELECT artifact_id '.
		'FROM artifact '.
		'WHERE group_artifact_id='. db_ei($atid);
		
		//echo $sql_artifacts;
		
	    $res = db_query($sql_artifacts);
	
	    while ($artifacts_array = db_fetch_array($res)) {
			$id = $artifacts_array["artifact_id"];

			// Delete artifact_cc records	    	
	    	$sql = "DELETE FROM artifact_cc WHERE artifact_id = ".db_ei($id);
	    	db_query($sql);
	    	
			// Delete artifact_dependencies records	    	
	    	$sql = "DELETE FROM artifact_dependencies WHERE artifact_id = ".db_ei($id);
	    	db_query($sql);

			// Delete artifact_field_value records	    	
	    	$sql = "DELETE FROM artifact_field_value WHERE artifact_id = ".db_ei($id);
	    	db_query($sql);
	    	
			// Delete artifact_file records	    	
	    	$sql = "DELETE FROM artifact_file WHERE artifact_id = ".db_ei($id);
	    	db_query($sql);

			// Delete artifact_history records	    	
	    	$sql = "DELETE FROM artifact_history WHERE artifact_id = ".db_ei($id);
	    	db_query($sql);

			// Delete artifact records	    	
	    	$sql = "DELETE FROM artifact WHERE artifact_id = ".db_ei($id);
	    	db_query($sql);

		} // while        

		// Delete artifact_group_list
		$sql = "DELETE FROM artifact_group_list
			    WHERE group_artifact_id=". db_ei($atid);
		//echo $sql;
		
		$result = db_query ($sql);

		if (!$result || db_affected_rows($result) <= 0) {
			$this->setError('Error: deleteArtifactType '.db_error());
			return false;
		}
		
        //Remove permissions
        permission_clear_all_tracker($this->Group->getID(), $atid);
        
		return true;
	}
	
	
	/**
	 *	Retrieve the artifacts where user $user_id is a submitter or assignee
     *  By default both are retreived but you can select if you want artifacts
     *  you are assigned or artifact you submitted or both.
	 *
	 *  @param user_id: the user id
	 *  @param view: 1 means assigned, 2 means Submitted, 3 means Both
	 *	@return	db_result
	 */
	function getMyArtifacts($user_id, $view='AS') {
        $assignee = false;
        $submitter = false;

        if(strpos($view, 'A') !== false)
            $assignee = true;

        if(strpos($view, 'S') !== false)
            $submitter = true;

        if(!$assignee && !$submitter)
            return false;

        $finalSql = '';
        
        // Only artifacts from active projects are returned.
        $sql = 'SELECT agl.group_artifact_id,agl.name,agl.group_id,g.group_name,a.summary, a.artifact_id, a.severity,'.
               '(a.submitted_by='. db_ei($user_id) .') as submitter,'.
               'MAX(afv.valueInt='. db_ei($user_id) .') as assignee'.
               ' FROM artifact a,artifact_group_list agl,artifact_field af,artifact_field_value afv,groups g'.
               ' WHERE agl.group_id = g.group_id'.
               ' AND af.group_artifact_id = agl.group_artifact_id'.
               ' AND agl.status = "A"'.
               ' AND g.status = "A"'.
               ' AND (af.field_name = "assigned_to"'.
               '  OR af.field_name = "multi_assigned_to")'.
               ' AND af.field_id = afv.field_id'.
               ' AND a.group_artifact_id = agl.group_artifact_id'.
               ' AND a.artifact_id = afv.artifact_id'.
               ' AND a.status_id <> 3';

        $assigneeSql  = '';
        $submitterSql = '';
        if($assignee) {
            $assigneeSql = $sql.
                           ' AND afv.valueInt='. db_ei($user_id).
                           ' GROUP BY a.artifact_id';
            $finalSql = $assigneeSql;
            if(!$submitter) {
                $finalSql .= ' ORDER BY group_name, name, artifact_id';
            }
        }
        
        if($submitter) {
            $submitterSql = $sql.
                            ' AND a.submitted_by='. db_ei($user_id).
                            ' GROUP BY a.artifact_id';
            if($assignee) {
                $finalSql = '('.$assigneeSql.') UNION ALL ('.$submitterSql.') ORDER BY group_name, name, artifact_id';
            }
            else {
                $finalSql = $submitterSql.' ORDER BY group_name, name, artifact_id';
            }
        }
       
        $res = db_query($finalSql);
        
	    return $res;
	}

    /**
     * Returns the ArtifactType named $tracker_name in the project of ID $gropup_id, or false if such a tracker does not exist or if the user can not view this tracker
     *
     * @param int $group_id th ID of the group
     * @param string $tracker_name the name of the tracker we are lokking for
     * @return the ArtifactType named $tracker_name in the project of ID $gropup_id, or false if such a tracker does not exist or if the user can not view this tracker
     */
    function getArtifactTypeFromName($group_id, $tracker_name) {
        global $Language;

        $sql = "SELECT group_artifact_id 
                FROM artifact_group_list 
                WHERE group_id='". db_ei($group_id) ."' AND 
                      item_name='". db_es($tracker_name) ."' AND 
                      status!='D'";
        
        $result = db_query($sql);
        $rows = db_numrows($result);
        if (!$result || $rows != 1) {
            $this->setError($Language->getText('tracker_common_type','none_found').' '.db_error());
            return false;
        } else {
            while ($arr = db_fetch_array($result)) {
                $new_at = new ArtifactType(group_get_object($group_id), $arr['group_artifact_id']);
                if ($new_at->userCanView()) {
                    return $new_at;
                } else {
                    $this->setError($Language->getText('tracker_common_type','no_view_permission').' '.db_error());
                    return false;
                }
            }
        }
    }

	/**
	 *  fetch all tracker templates that need to be instantiated for new projects.
	 *
	 *  @return query result.
	 */
	function getTrackerTemplatesForNewProjects() {
	  $sql = "SELECT group_artifact_id FROM artifact_group_list WHERE group_id=".db_ei($this->Group->getGroupId()) ." AND instantiate_for_new_projects=1 AND status = 'A'";
	    return db_query($sql);
	}


	/**
	 *	create - use this to create a new ArtifactType in the database.
	 *
	 *  @param  group_id: the group id of the new tracker
	 *	@param	group_id_template: the template group id (used for the copy)
	 *	@param	atid_template: the template artfact type id 
	 *	@param	name: the name of the new tracker
	 *	@param	description: the description of the new tracker
	 *	@param	itemname: the itemname of the new tracker
	 *	@return id on success, false on failure.
	 */
	function create($group_id,$group_id_template,$atid_template,$name,$description,$itemname,$ugroup_mapping=false,&$report_mapping=array()) {
		global $Language;

		if (!$name || !$description || !$itemname || trim($name) == "" || trim($description) == "" || trim($itemname) == ""  ) {
			$this->setError('ArtifactTypeFactory: '.$Language->getText('tracker_common_type','name_requ'));
			return false;
		}

                // Necessary test to avoid issues when exporting the tracker to a DB (e.g. '-' not supported as table name)
                if (!eregi("^[a-zA-Z0-9_]+$",$itemname)) {
                    $this->setError($Language->getText('tracker_common_type','invalid_shortname',$itemname));
			return false;
                }

		//	get the template Group object
		$template_group = group_get_object($group_id_template);
		if (!$template_group || !is_object($template_group) || $template_group->isError()) {
			$this->setError('ArtifactTypeFactory: '.$Language->getText('tracker_common_type','invalid_templ'));
		}

		// get the Group object of the new tracker
		$group = group_get_object($group_id);
		if (!$group || !is_object($group) || $group->isError()) {
			$this->setError('ArtifactTypeFactory: '.$Language->getText('tracker_common_type','invalid_templ'));
		}

		// We retrieve allow_copy from template
		$at_template = new ArtifactType($template_group,$atid_template);
		
		// First, we create a new ArtifactType into artifact_group_list
                // By default, set 'instantiate_for_new_projects' to '1', so that a project that is not yet a 
                // template will be able to have its trackers cloned by default when it becomes a template.
		$sql="INSERT INTO 
			artifact_group_list 
			(group_id, name, description, item_name, allow_copy,
                         submit_instructions,browse_instructions,instantiate_for_new_projects,stop_notification
                         ) 
			VALUES 
			('". db_ei($group_id) ."',
			'". db_es($name) ."',
			'". db_es($description) ."',
			'". db_es($itemname) ."',
                        '". db_ei($at_template->allowsCopy()) ."',
                        '". db_es($at_template->getSubmitInstructions())."',
                        '". db_es($at_template->getBrowseInstructions())."',1,0)";
		//echo $sql;
		$res = db_query($sql);
		if (!$res || db_affected_rows($res) <= 0) {
			$this->setError('ArtifactTypeFactory: '.db_error());
			return false;
		} else {
			$id = db_insertid($res,'artifact_group_list','group_artifact_id');
			$at_new = new ArtifactType($group,$id);
			if (!$at_new->fetchData($id)) {
				$this->setError('ArtifactTypeFactory: '.$Language->getText('tracker_common_type','load_fail'));
				return false;
			} else {
                
                //create global notifications
                $sql = "INSERT INTO artifact_global_notification (tracker_id, addresses, all_updates, check_permissions)
                SELECT ". db_ei($id) .", addresses, all_updates, check_permissions
                FROM artifact_global_notification
                WHERE tracker_id = ". db_ei($atid_template);
                $res = db_query($sql);
                if (!$res || db_affected_rows($res) <= 0) {
                    $this->setError('ArtifactTypeFactory: '.db_error());
                }
                
                // Create fieldset factory
                $art_fieldset_fact = new ArtifactFieldSetFactory($at_template);
                // Then copy all the field sets.
                $mapping_field_set_array = $art_fieldset_fact->copyFieldSets($atid_template, $id);
                if ( ! $mapping_field_set_array) {
		  $this->setError('ArtifactTypeFactory: '.$art_fieldset_fact->getErrorMessage());
		  return false;
		}
                
		// Create field factory
		$art_field_fact = new ArtifactFieldFactory($at_template);
		
		// Then copy all the fields informations
		if ( !$art_field_fact->copyFields($id, $mapping_field_set_array,$ugroup_mapping) ) {
		  $this->setError('ArtifactTypeFactory: '.$art_field_fact->getErrorMessage());
		  return false;
		}
		
		// Then copy all the reports informations
		// Create field factory
		$art_report_fact = new ArtifactReportFactory();
		
		if ( !$report_mapping = $art_report_fact->copyReports($atid_template,$id) ) {
		  $this->setError('ArtifactTypeFactory: '.$art_report_fact->getErrorMessage());
		  return false;
		}
		$em =& EventManager::instance();
		$pref_params = array('atid_source'   => $atid_template,
                     		 'atid_dest'     => $id);
		$em->processEvent('artifactType_created',$pref_params);
	
		
		// Copy artifact_notification_event and artifact_notification_role
		if ( !$at_new->copyNotificationEvent($id) ) {
		  return false;
		}
		if ( !$at_new->copyNotificationRole($id) ) {
		  return false;
		}
		
		// Create user permissions: None for group members and Admin for group admin
		if ( !$at_new->createUserPerms($id) ) {
		  return false;
		}

		// Create canned responses
		$canned_new = new ArtifactCanned($at_new);
		$canned_template = $at_template->getCannedResponses();
		if ($canned_template && db_numrows($canned_template) > 0) {
		  while ($row = db_fetch_array($canned_template)) {
		    $canned_new->create($row['title'],$row['body']);
		  }
		}
                
                //Copy template permission
                permission_copy_tracker_and_field_permissions($atid_template, $id, $group_id_template, $group_id, $ugroup_mapping);
                
                //Copy Rules
                require_once('ArtifactRulesManager.class.php');
                $arm =& new ArtifactRulesManager();
                $arm->copyRules($atid_template, $id);
		return $id;
			}
		}
	}

}

?>
