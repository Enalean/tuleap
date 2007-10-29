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
//require_once('common/tracker/Artifact.class.php');

$Language->loadLanguageMsg('tracker/tracker');

class ArtifactFactory extends Error {

	/**
	 * The ArtifactType object.
	 *
	 * @var	 object  $ArtifactType.
	 */
	var $ArtifactType;

	/**
	 *  Constructor.
	 *
	 *	@param	object	The ArtifactType object to which this ArtifactFactory is associated.
	 *	@return	boolean	success.
	 */
	function ArtifactFactory(&$ArtifactType) {
	  global $Language;

		$this->Error();
		if (!$ArtifactType || !is_object($ArtifactType)) {
			$this->setError('ArtifactFactory:: '.$Language->getText('tracker_common_canned','not_valid'));
			return false;
		}
		if ($ArtifactType->isError()) {
			$this->setError('ArtifactFactory:: '.$ArtifactType->getErrorMessage());
			return false;
		}
		$this->ArtifactType = $ArtifactType;

		return true;
	}

	/**
	 *	getMyArtifacts - get an array of Artifact objects submitted by a user or assigned to this user
	 *
	 *  @param user_id: the user id
	 *
	 *	@return	array	The array of Artifact objects.
	 */
	function getMyArtifacts($user_id) {
	  global $Language;

		$artifacts = array();

		// List of trackers - Check on assigned_to or multi_assigned_to or submitted by
		$sql = "SELECT a.*,afv.valueInt as assigned_to FROM artifact_group_list agl, artifact a, artifact_field af, artifact_field_value afv WHERE ".
			   "a.group_artifact_id = ".$this->ArtifactType->getID()." AND a.group_artifact_id = agl.group_artifact_id AND af.group_artifact_id = agl.group_artifact_id AND ".
			   "(af.field_name = 'assigned_to' OR af.field_name = 'multi_assigned_to') AND af.field_id = afv.field_id AND a.artifact_id = afv.artifact_id AND ".
			   "(afv.valueInt=".$user_id." OR a.submitted_by=".$user_id.") AND a.status_id <> 3 LIMIT 100";

		//echo $sql;
		$result=db_query($sql);
		$rows = db_numrows($result);
		$this->fetched_rows=$rows;
		if (db_error()) {
			$this->setError($Language->getText('tracker_common_factory','db_err').': '.db_error());
			return false;
		} else {
			while ($arr = db_fetch_array($result)) {
				$artifacts[$arr['artifact_id']] = new Artifact($this->ArtifactType, $arr['artifact_id']);
			}
			if ( count($artifacts) ) {
				return $artifacts;
			}
		}

		// List of trackers - Check on submitted_by
		$sql = "SELECT a.*, 0 as assigned_to FROM artifact_group_list agl, artifact a WHERE ".
			   "a.group_artifact_id = ".$this->ArtifactType->getID()." AND a.group_artifact_id = agl.group_artifact_id AND ".
			   "a.submitted_by=".$user_id." AND a.status_id <> 3 LIMIT 100";

		//echo $sql;
		$result=db_query($sql);
		$rows = db_numrows($result);
		$this->fetched_rows=$rows;
		if (db_error()) {
			$this->setError($Language->getText('tracker_common_factory','db_err').': '.db_error());
			return false;
		} else {
			while ($arr = db_fetch_array($result)) {
				$artifacts[$arr['artifact_id']] = new Artifact($this->ArtifactType, $arr['artifact_id']);
			}
		}

		return $artifacts;
	}
	/**
	 *  getArtifacts - get an array of Artifact objects
	 *
	 *	@param $criteria : array of items field => value
	 *	@param $offset   : the index of artifact to begin
	 *	@param $max_rows : number of artifacts to return
	 *
     *  @param OUT $total_artifacts : total number of artifacts (if offset and max_rows were not here) 
     *
	 *	@return	array	The array of Artifact objects.
	 */
	function getArtifacts($criteria, $offset, $max_rows, &$total_artifacts) {
		global $Language, $art_field_fact;
        
        $ACCEPTED_OPERATORS = array('=', '<', '>', '<>', '<=', '>=');
        
		$artifacts = array();
		if (is_array($criteria) && count($criteria) > 0) {
            $sql_select = "SELECT a.* ";
            $sql_from = " FROM artifact_group_list agl, artifact a ";
            $sql_where = " WHERE a.group_artifact_id = ".$this->ArtifactType->getID()." AND 
                          a.group_artifact_id = agl.group_artifact_id ";
            
            $cpt_criteria = 0;  // counter for criteria (used to build the SQL query)
			foreach ($criteria as $c => $cr) {
				$af = $art_field_fact->getFieldFromName($cr['field_name']);
				if (!$af || !is_object($af)) {
                    $this->setError('Cannot Get ArtifactField From Name : '.$cr['field_name']);
                    return false;
                } elseif ($art_field_fact->isError()) {
                    $this->setError($art_field_fact->getErrorMessage());
	        	    return false;
                }
                
                if ($af->isDateField() && ($cr['field_name'] != 'open_date' && $cr['field_name'] != 'close_date')) {
                    // The SQL query expects a timestamp, whereas the given date is in YYYY-MM-DD format
                    $cr['field_value'] = strtotime($cr['field_value']);
                }
                
                $operator = "=";    // operator by default
				if (isset($cr['operator']) && in_array($cr['operator'], $ACCEPTED_OPERATORS)) {
                    $operator = $cr['operator'];
                }
                
				if ($af->isStandardField()) {
                    if ($cr['operator'] == '=' && ($cr['field_name'] == 'open_date' || $cr['field_name'] == 'close_date')) {
                        // special case for open_date and close_date with operator = : the hours, minutes, and seconds are stored, so we have to compare an interval
                        list($year,$month,$day) = util_date_explode($cr['field_value']);
                        $time_end = mktime(23, 59, 59, $month, $day, $year);
                        $sql_where .= " AND (a.".$cr['field_name']." >= '".strtotime($cr['field_value'])."')";
                        $sql_where .= " AND (a.".$cr['field_name']." <= '".$time_end."')";
                    } else {
                        if ($af->isDateField()) {
                            $sql_where .= " AND (a.".$cr['field_name']." ".$operator." '".strtotime($cr['field_value'])."')";
                        } else {
                            $sql_where .= " AND (a.".$cr['field_name']." ".$operator." '".$cr['field_value']."')";
                        }
                    }
				} else {
                    $sql_select .= ", afv".$cpt_criteria.".valueInt ";
                    $sql_from .= ", artifact_field af".$cpt_criteria.", artifact_field_value afv".$cpt_criteria." ";
                    $sql_where .= " AND af".$cpt_criteria.".group_artifact_id = agl.group_artifact_id
                                    AND (af".$cpt_criteria.".field_name = '".$cr['field_name']."' 
                                    AND afv".$cpt_criteria.".".$af->getValueFieldName()." ".$operator." '".$cr['field_value']."') 
                                    AND af".$cpt_criteria.".field_id = afv".$cpt_criteria.".field_id 
                                    AND a.artifact_id = afv".$cpt_criteria.".artifact_id ";
				}
                $cpt_criteria += 1;
			}
			
            $sql = $sql_select . $sql_from . $sql_where;
            
		} else {
			$sql = "SELECT a.artifact_id 
                    FROM artifact_group_list agl, artifact a 
                    WHERE a.group_artifact_id = ".$this->ArtifactType->getID()." AND 
                          a.group_artifact_id = agl.group_artifact_id";
        }
        
        // we count the total number of artifact (without offset neither limit) to be able to perform the pagination 
        $result_count = db_query($sql);
        $rows_count = db_numrows($result_count); 
        $total_artifacts = $rows_count;
        
        $offset = intval($offset);
        $max_rows = intval($max_rows);
        if ($max_rows > 0) {
			if (!$offset || $offset < 0) {
				$offset=0;
			}
			$sql .=" LIMIT $offset,$max_rows";
		}
        
        $result=db_query($sql);
        $rows = db_numrows($result);
		$this->fetched_rows=$rows;
        if (db_error()) {
			$this->setError($Language->getText('tracker_common_factory','db_err').': '.db_error());
			return false;
		} else {
			while ($arr = db_fetch_array($result)) {
				$artifact = new Artifact($this->ArtifactType, $arr['artifact_id'], true);
                // artifact is not added if the user can't view it
                if ($artifact->userCanView()) {
                    $artifacts[$arr['artifact_id']] = $artifact;
                }
			}
		}
		return $artifacts;
	}
}
?>
