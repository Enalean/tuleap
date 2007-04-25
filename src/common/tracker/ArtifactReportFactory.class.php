<?php
//
// Copyright (c) Xerox Corporation, CodeX Team, 2001-2003. All rights reserved
//
// 
//
//  Written for CodeX by Stephane Bouhet
//

require_once('common/include/Error.class.php');
require_once('common/tracker/ArtifactReport.class.php');

$Language->loadLanguageMsg('tracker/tracker');

class ArtifactReportFactory extends Error {

	/**
	 *  Constructor.
	 *
	 *	@return	boolean	success.
	 */
	function ArtifactReportFactory() {
		// Error constructor
		$this->Error();
		
		return true;
	}
	
	/**
	 * Return a new ArtifactReport object 
	 *
	 * @param report_id: the report id to create the new ArtifactReport
	 *
	 * @return void
	 */
	function getArtifactReportHtml($report_id,$atid) {
		return new ArtifactReportHtml($report_id,$atid);
	}

	/**
     * 
	 *  Copy all the reports informations from a tracker to another.
	 *
	 *  @param atid_source: source tracker
	 *  @param atid_dest: destination tracker
	 *
	 *	@return	boolean
	 */
	function copyReports($atid_source,$atid_dest) {
	  global $Language;

		//
		// Copy artifact_report records which are not individual/personal
		//
	    $sql="SELECT report_id,user_id,name,description,scope ".
		"FROM artifact_report ".
		"WHERE group_artifact_id='$atid_source'" .
	        "AND scope != 'I'";
		
		//echo $sql;
		
	    $res = db_query($sql);
	
	    while ($report_array = db_fetch_array($res)) {
	    	$sql_insert = 'INSERT INTO artifact_report (group_artifact_id,user_id,name,description,scope) VALUES ('.$atid_dest.','.$report_array["user_id"].
	    				  ',"'.addslashes($report_array["name"]).'","'.addslashes($report_array["description"]).'","'.$report_array["scope"].'")';
	    				  
			$res_insert = db_query($sql_insert);
			if (!$res_insert || db_affected_rows($res_insert) <= 0) {
				$this->setError($Language->getText('tracker_common_reportfactory','ins_err',array($report_array["report_id"],$atid_dest,db_error())));
				return false;
			}
			
			$report_id = db_insertid($res_insert,'artifact_report','report_id');

			//
			// Copy artifact_report_field records
			//
		    $sql_fields='SELECT field_name,show_on_query,show_on_result,place_query,place_result,col_width '.
			'FROM artifact_report_field '.
			'WHERE report_id='.$report_array["report_id"];
			
			//echo $sql_fields;
			
		    $res_fields = db_query($sql_fields);
		
		    while ($field_array = db_fetch_array($res_fields)) {
		    	$show_on_query = ($field_array["show_on_query"] == ""?"null":$field_array["show_on_query"]);
		    	$show_on_result = ($field_array["show_on_result"] == ""?"null":$field_array["show_on_result"]);
		    	$place_query = ($field_array["place_query"] == ""?"null":$field_array["place_query"]);
		    	$place_result = ($field_array["place_result"] == ""?"null":$field_array["place_result"]);
		    	$col_width = ($field_array["col_width"] == ""?"null":$field_array["col_width"]);

		    	$sql_insert = 'INSERT INTO artifact_report_field VALUES ('.$report_id.',"'.$field_array["field_name"].
		    				  '",'.$show_on_query.','.$show_on_result.','.$place_query.
		    				  ','.$place_result.','.$col_width.')';
		    				  
		    	//echo $sql_insert;
				$res_insert = db_query($sql_insert);
				if (!$res_insert || db_affected_rows($res_insert) <= 0) {
					$this->setError($Language->getText('tracker_common_reportfactory','f_ind_err',array($report_array["report_id"],$field_array["field_name"],db_error())));
					return false;
				}
			} // while

		} // while
			
		return true;

	}

	/**
     * 
	 *  Delete all the reports informations for a tracker
	 *
	 *  @param atid: the tracker id
	 *
	 *	@return	boolean
	 */
	function deleteReports($atid) {
		
		//
		// Delete artifact_report_field records
		//
	    $sql='SELECT report_id '.
		'FROM artifact_report '.
		'WHERE group_artifact_id='.$atid;
		
		//echo $sql;
		
	    $res = db_query($sql);
	
	    while ($report_array = db_fetch_array($res)) {

		    $sql_fields='DELETE '.
			'FROM artifact_report_field '.
			'WHERE report_id='.$report_array["report_id"];
			
			//echo $sql_fields;
			
		    $res_fields = db_query($sql_fields);
		
		} // while
					
		//
		// Delete artifact_report records
		//
	    $sql='DELETE '.
		'FROM artifact_report '.
		'WHERE group_artifact_id='.$atid;
		
		//echo $sql;
		
	    $res = db_query($sql);
	
		return true;

	}
	
	/**
	 *  getReports - get an array of ArtifactReport objects
	 *
	 *	@param $group_artifact_id : the tracker id
	 *	@param $user_id  : the user id
	 *
	 *	@return	array	The array of ArtifactReport objects.
	 */
	function getReports($group_artifact_id, $user_id) {
	
	    $artifactreports = array();
	    $sql = 'SELECT report_id,name,description,scope FROM artifact_report WHERE ';
	    if (!$user_id || ($user_id == 100)) {
			$sql .= "(group_artifact_id=$group_artifact_id AND scope='P') OR scope='S' ".
			    'ORDER BY report_id';
	    } else {
			$sql .= "(group_artifact_id=$group_artifact_id AND (user_id=$user_id OR scope='P')) OR ".
			    "scope='S' ORDER BY scope,report_id";
	    }
	    
	    $result = db_query($sql);
	    $rows = db_numrows($result);
	    if (db_error()) {
			$this->setError($Language->getText('tracker_common_factory','db_err').': '.db_error());
			return false;
	    } else {
			while ($arr = db_fetch_array($result)) {
				$artifactreports[$arr['report_id']] = new ArtifactReport($arr['report_id'], $group_artifact_id);
			}
	    }
	    return $artifactreports;
	    
	}
}

?>
