<?php
//
// Copyright (c) Xerox Corporation, CodeX Team, 2001-2003. All rights reserved
//
// $Id:ArtifactFactory.class.php 4446 2006-12-08 16:18:48 +0000 (Fri, 08 Dec 2006) ahardyau $
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
	 *	@return	array	The array of Artifact objects.
	 */
	function getArtifacts($criteria, $offset, $max_rows) {
		global $Language, $art_field_fact;
        
        $ACCEPTED_OPERATORS = array('=', '<', '>', '<>', '<=', '>=');
        
		$artifacts = array();
		if (is_array($criteria) && count($criteria) > 0) {
            $sql = "SELECT a.*,afv.valueInt as assigned_to 
                    FROM artifact_group_list agl, artifact a, artifact_field af, artifact_field_value afv 
                    WHERE a.group_artifact_id = ".$this->ArtifactType->getID()." AND 
                          a.group_artifact_id = agl.group_artifact_id AND 
                          af.group_artifact_id = agl.group_artifact_id";
			foreach ($criteria as $c => $cr) {
				$af = $art_field_fact->getFieldFromName($cr['field_name']);
				if (!$af || !is_object($af)) {
                    $this->setError('Cannot Get ArtifactField From Name : '.$cr['field_name']);
                    return false;
                } elseif ($art_field_fact->isError()) {
                    $this->setError($art_field_fact->getErrorMessage());
	        	    return false;
                }
                
                if ($af->isDateField()) {
                    // The SQL query expects a timestamp, whereas the given date is in YYYY-MM-DD format
                    $cr['field_value'] = strtotime($cr['field_value']);
                }
                
				if ($af->isStandardField()) {
					if (isset($cr['operator']) && in_array($cr['operator'], $ACCEPTED_OPERATORS)) {
						$sql .= " AND (a.".$cr['field_name']." ".$cr['operator']." '".$cr['field_value']."')";
                    } else {
						$sql .= " AND (a.".$cr['field_name']." = '".$cr['field_value']."')";
                    }
				} else {
					if (isset($cr['operator']) && in_array($cr['operator'], $ACCEPTED_OPERATORS)) {
						$sql .= " AND (af.field_name = '".$cr['field_name']."' AND afv.".$af->getValueFieldName()." ".$cr['operator']." '".$cr['field_value']."')";
                    } else {
						$sql .= " AND (af.field_name = '".$cr['field_name']."' AND afv.".$af->getValueFieldName()." = '".$cr['field_value']."')";
                    }
				}
			}
			$sql .= " AND af.field_id = afv.field_id AND a.artifact_id = afv.artifact_id" ;
		} else {
			$sql = "SELECT a.artifact_id FROM artifact_group_list agl, artifact a WHERE ".
			   "a.group_artifact_id = ".$this->ArtifactType->getID()." AND a.group_artifact_id = agl.group_artifact_id";
        }
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
