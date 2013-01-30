<?php
/**
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
 *
 * This file is a part of Codendi.
 *
 * Codendi is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Codendi is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Codendi. If not, see <http://www.gnu.org/licenses/>.
 */

require_once('common/include/Error.class.php');

class Tracker_ReportFactory extends Error {
    
    /**
     * A protected constructor; prevents direct creation of object
     */
    protected function __construct() {
    }

    /**
     * Hold an instance of the class
     */
    protected static $_instance;
    
    /**
     * The singleton method
     * 
     * @return Tracker_ReportFactory
     */
    public static function instance() {
        if (!isset(self::$_instance)) {
            $c = __CLASS__;
            self::$_instance = new $c;
        }
        return self::$_instance;
    }

    // {{{ Public
    
    /**
     * @param int $id the id of the report to retrieve
     * @return Report identified by id (null if not found)
     */
    public function getReportById($id, $user_id, $store_in_session = true) {
        $row = $this->getDao()
                    ->searchById($id, $user_id)
                    ->getRow();
        $r = null;
        if ($row) {
            $r = $this->getInstanceFromRow($row, $store_in_session);
        }
        return $r;
    }
    
    /**
     * @param int $tracker_id the id of the tracker
     * @param int $user_id the user who are searching for reports. He cannot access to other user's reports
     *                   if null then project reports instead of user ones
     * @param array
     */
    public function getReportsByTrackerId($tracker_id, $user_id) {
        $reports = array();
        foreach ($this->getDao()->searchByTrackerId($tracker_id, $user_id) as $row) {
            $reports[$row['id']] = $this->getInstanceFromRow($row);
        }
        return $reports;
    }
    /**
     * @param int $tracker_id the id of the tracker
     * @param array
     */
    public function getDefaultReportsByTrackerId($tracker_id) {
        $report = null;
        if ($row = $this->getDao()->searchDefaultByTrackerId($tracker_id)->getRow()) {
            $report = $this->getInstanceFromRow($row);
        }
        return $report;
    }
    
    /**
     * @param int $tracker_id the id of the tracker
     * @param Tracker_Report
     */
    public function getDefaultReportByTrackerId($tracker_id) {
        $default_report = null;
        if ($row = $this->getDao()->searchDefaultReportByTrackerId($tracker_id)->getRow()) {
            $default_report = $this->getInstanceFromRow($row);
        }
        return $default_report;
    }
    
    /**
     * @param int $user_id the user who are searching for reports. He cannot access to other user's reports
     * @param array of reports
     */
    public function getReportsByUserId($user_id) {
        $reports = array();
        foreach ($this->getDao()->searchByUserId($user_id) as $row) {
            $reports[$row['id']] = $this->getInstanceFromRow($row);
        }
        return $reports;
    }
    
    /**
     * Return the list of Report the user can run on a Tracker in SOAP format.
     *
     * @param Tracker $tracker The Tracker to pick report from
     * @param User    $user    The user who does the request
     *
     * @return Array of soap report
     */
    public function exportToSoap(Tracker $tracker, User $user) {
        $soap_tracker_reports = array();
        foreach ($this->getReportsByTrackerId($tracker->getId(), $user->getId()) as $report) {
            $soap_tracker_reports[] = $report->exportToSoap();
        }
        return $soap_tracker_reports;
    }

    /**
     * Save a report
     *
     * @param Report $report the report to save
     *
     * @return boolean true if the save succeed
     */
    public function save(Tracker_Report $report) {
        $user = UserManager::instance()->getCurrentUser();
        return $this->getDao()->save(
            $report->id,
            $report->name,
            $report->description,
            $report->current_renderer_id,
            $report->parent_report_id,
            $report->user_id,
            $report->is_default,
            $report->tracker_id,
            $report->is_query_displayed,
            $user->getId()
        );
    }
    
    public function duplicate($from_tracker_id, $to_tracker_id, $field_mapping) {
        foreach($this->getReportsByTrackerId($from_tracker_id, null) as $from_report) {
            $new_report = $this->duplicateReport($from_report, $to_tracker_id, $field_mapping, null);
            //TODO: change the parent report
        }
    }
    
    /**
     * Duplicate a report. The new report will have $from_report as parent.
     *
     * @param Tracker_Report $from_report   The report to copy
     * @param int            $tracker_id    The id of the target tracker
     * @param array          $field_mapping The mapping of the field, if any
     * @param int            $current_user  The id of the current user
     *
     * @return Tracker_Report the new report
     */
    public function duplicateReport($from_report, $to_tracker_id, $field_mapping, $current_user_id) {
        $report = null;
        //duplicate report info
        if ($id = $this->getDao()->duplicate($from_report->id, $to_tracker_id)) {
            //duplicate report
            $report = $this->getReportById($id, $current_user_id);
            $report->duplicate($from_report, $field_mapping);
        }
        return $report;
    }
    // }}}
    
    public function duplicateReportSkeleton($from_report, $to_tracker_id, $current_user_id) {
        $report = null;
        //duplicate report info
        if ($id = $this->getDao()->duplicate($from_report->id, $to_tracker_id)) {
            $report = $this->getReportById($id, $current_user_id);
        }
        return $report;
    }
    
    
    
    // {{{ Protected
    
    protected $dao;
    /**
     * @return Tracker_ReportDao
     */
    protected function getDao() {
        if (!$this->dao) {
            $this->dao = new Tracker_ReportDao();
        }
        return $this->dao;
    }
    
    /**
     * @return Tracker_Report_CriteriaFactory
     */
    protected function getCriteriaFactory() {
        return Tracker_Report_CriteriaFactory::instance();
    }
    
    /**
     * @return Tracker_Report_RendererFactory
     */
    protected function getRendererFactory() {
        return Tracker_Report_RendererFactory::instance();
    }
    
    /**
     * @param array the row identifing a report
     * @return Tracker_Report
     */
    protected function getInstanceFromRow($row, $store_in_session = true) {
        $r = new Tracker_Report(
            $row['id'],
            $row['name'],
            $row['description'],
            $row['current_renderer_id'],
            $row['parent_report_id'],
            $row['user_id'],
            $row['is_default'],
            $row['tracker_id'],
            $row['is_query_displayed'],
            $row['updated_by'],
            $row['updated_at']
        );
        if ($store_in_session) {
            $r->registerInSession();
        }
        
        return $r;
    }
    
    /**
     * Creates a Tracker_Report Object
     * 
     * @param SimpleXMLElement $xml         containing the structure of the imported report
     * @param array            &$xmlMapping containig the newly created formElements idexed by their XML IDs
     * @param int              $group_id    the Id of the project
     * 
     * @return Tracker_Report Object 
     */
    public function getInstanceFromXML($xml, &$xmlMapping, $group_id) {
        $att = $xml->attributes();
        $row = array('name' => (string)$xml->name,
                     'description' => (string)$xml->description);
        $row['is_default'] = isset($att['is_default']) ? (int)$att['is_default'] : 0;
        $row['is_query_displayed'] = isset($att['is_query_displayed']) ? (int)$att['is_query_displayed'] : 1;
        // in case old id values are important modify code here
        if (false) {
            foreach ($xml->attributes() as $key => $value) {
                $row[$key] = (int)$value;
            }
        } else {
            $row['id'] = 0;
            $row['current_renderer_id'] = 0;
            $row['parent_report_id'] = 0;
            $row['tracker_id'] = 0;
            $row['user_id'] = null;
            $row['group_id'] = $group_id;
        }
        $row['updated_by'] = null;
        $row['updated_at'] = null;
        $report = $this->getInstanceFromRow($row);
        // create criteria
        $report->criterias = array();
        foreach ($xml->criterias->criteria as $criteria) {
            $report->criterias[] = $this->getCriteriaFactory()->getInstanceFromXML($criteria, $xmlMapping);
        }
        // create renderers
        $report->renderers = array();
        foreach ($xml->renderers->renderer as $renderer) {
            $rend = $this->getRendererFactory()->getInstanceFromXML($renderer, $report, $xmlMapping);
            $report->renderers[] = $rend; 
        }
        return $report;
    }
    
    /**
     * Create new default report in the DataBase
     * 
     * @param int trackerId of the created tracker
     * @param Object report
     * 
     * @return id of the newly created Report
     */
    public function saveObject($trackerId, $report) { 
        $reportId = $this->getDao()->create( $report->name,
                                        $report->description,
                                        $report->current_renderer_id,
                                        $report->parent_report_id,
                                        $report->user_id,
                                        $report->is_default,
                                        $trackerId,
                                        $report->is_query_displayed);
        //create criterias
        $reportDB = Tracker_ReportFactory::instance()->getReportById($reportId, null);
        if ($report->criterias) {
            foreach ($report->criterias as $criteria){
                $reportDB->addCriteria($criteria);
            }
        }
        //create renderers
        if ($report->renderers) {
            foreach ($report->renderers as $renderer) {
                if ($renderer) {
                    $rendererId = $reportDB->addRenderer($renderer->name, $renderer->description, $renderer->getType());
                    $rendererDB = Tracker_Report_RendererFactory::instance()->getReportRendererById($rendererId, $reportDB);
                    $rendererDB->afterSaveObject($renderer);
                }
	        }
        }
        return $reportDB->id;
    }
    
    /**
     * Delete a report
     *
     * @return bool true if success
     */
    public function delete($report_id) {
        return $this->getDao()->delete($report_id);
    }
    // }}}
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
	
	/**
	 * Return a new Tracker_Report object 
	 *
	 * @param report_id: the report id to create the new Tracker_Report
	 *
	 * @return void
	 */
	function getArtifactReportHtml($report_id,$atid) {
        $sql = "SELECT * FROM tracker_report ".
			   "WHERE report_id=". db_ei($report_id) ;
		//echo $sql.'<br>';
		$res=db_query($sql);
		if (!$res || db_numrows($res) < 1) {
			return false;
		}
		return new Tracker_ReportHtml($report_id,$atid);
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
        $report_mapping = array(100 => 100); //The system report 'Default' (sic)
		//
		// Copy tracker_report records which are not individual/personal
		//
	    $sql="SELECT report_id,user_id,name,description,scope,is_default ".
		"FROM tracker_report ".
		"WHERE group_artifact_id='". db_ei($atid_source) ."'" .
	        "AND scope != 'I'";
		
		//echo $sql;
		
	    $res = db_query($sql);
	
	    while ($report_array = db_fetch_array($res)) {
	    	$sql_insert = 'INSERT INTO tracker_report (group_artifact_id,user_id,name,description,scope,is_default) VALUES ('. db_ei($atid_dest) .','. db_ei($report_array["user_id"]) .
	    				  ',"'. db_es($report_array["name"]) .'","'. db_es($report_array["description"]) .'","'. db_es($report_array["scope"]) .'","'. db_es($report_array["is_default"]) .'")';
	    				  
			$res_insert = db_query($sql_insert);
			if (!$res_insert || db_affected_rows($res_insert) <= 0) {
				$this->setError($Language->getText('plugin_tracker_common_reportfactory','ins_err',array($report_array["report_id"],$atid_dest,db_error())));
				return false;
			}
			
			$report_id = db_insertid($res_insert,'tracker_report','report_id');
            $report_mapping[$report_array["report_id"]] = $report_id;
			//
			// Copy tracker_report_field records
			//
		    $sql_fields='SELECT field_name,show_on_query,show_on_result,place_query,place_result,col_width '.
			'FROM tracker_report_field '.
			'WHERE report_id='. db_ei($report_array["report_id"]) ;
			
			//echo $sql_fields;
			
		    $res_fields = db_query($sql_fields);
		
		    while ($field_array = db_fetch_array($res_fields)) {
		    	$show_on_query = ($field_array["show_on_query"] == ""?"null":$field_array["show_on_query"]);
		    	$show_on_result = ($field_array["show_on_result"] == ""?"null":$field_array["show_on_result"]);
		    	$place_query = ($field_array["place_query"] == ""?"null":$field_array["place_query"]);
		    	$place_result = ($field_array["place_result"] == ""?"null":$field_array["place_result"]);
		    	$col_width = ($field_array["col_width"] == ""?"null":$field_array["col_width"]);

		    	$sql_insert = 'INSERT INTO tracker_report_field VALUES ('. db_ei($report_id) .',"'. db_es($field_array["field_name"]) .
		    				  '",'. db_ei($show_on_query) .','. db_ei($show_on_result) .','. db_ei($place_query) .
		    				  ','. db_ei($place_result) .','. db_ei($col_width) .')';
		    				  
		    	//echo $sql_insert;
				$res_insert = db_query($sql_insert);
				if (!$res_insert || db_affected_rows($res_insert) <= 0) {
					$this->setError($Language->getText('plugin_tracker_common_reportfactory','f_ind_err',array($report_array["report_id"],$field_array["field_name"],db_error())));
					return false;
				}
			} // while

		} // while
			
		return $report_mapping;

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
		// Delete tracker_report_field records
		//
	    $sql='SELECT report_id '.
		'FROM tracker_report '.
		'WHERE group_artifact_id='. db_ei($atid) ;
		
		//echo $sql;
		
	    $res = db_query($sql);
	
	    while ($report_array = db_fetch_array($res)) {

		    $sql_fields='DELETE '.
			'FROM tracker_report_field '.
			'WHERE report_id='. db_ei($report_array["report_id"]) ;
			
			//echo $sql_fields;
			
		    $res_fields = db_query($sql_fields);
		
		} // while
					
		//
		// Delete tracker_report records
		//
	    $sql='DELETE '.
		'FROM tracker_report '.
		'WHERE group_artifact_id='. db_ei($atid) ;
		
		//echo $sql;
		
	    $res = db_query($sql);
	
		return true;

	}
	
	/**
	 *  getReports - get an array of Tracker_Report objects
	 *
	 *	@param $group_artifact_id : the tracker id
	 *	@param $user_id  : the user id
	 *
	 *	@return	array	The array of Tracker_Report objects.
	 */
	function getReports($group_artifact_id, $user_id) {
	
	    $artifactreports = array();
	    $sql = 'SELECT report_id,name,description,scope,is_default FROM tracker_report WHERE ';
	    if (!$user_id || ($user_id == 100)) {
			$sql .= "(group_artifact_id=".  db_ei($group_artifact_id)  ." AND scope='P') OR scope='S' ".
			    'ORDER BY report_id';
	    } else {
			$sql .= "(group_artifact_id= ". db_ei($group_artifact_id) ." AND (user_id=". db_ei($user_id) ." OR scope='P')) OR ".
			    "scope='S' ORDER BY scope,report_id";
	    }
	    
	    $result = db_query($sql);
	    $rows = db_numrows($result);
	    if (db_error()) {
			$this->setError($GLOBALS ['Language']->getText('plugin_tracker_common_factory','db_err').': '.db_error());
			return false;
	    } else {
			while ($arr = db_fetch_array($result)) {
				$artifactreports[$arr['report_id']] = new Tracker_Report($arr['report_id'], $group_artifact_id);
			}
	    }
	    return $artifactreports;
	    
	}
		 
    /**
     *  getDefaultReport - get report_id of the default report
     *
     *  @param group_artifact_id : the tracker id
     *  @return int     report_id
     */

    function getDefaultReport($group_artifact_id) {
        $report_id = null;
        $sql = "SELECT report_id FROM tracker_report WHERE group_artifact_id=".db_ei($group_artifact_id)." AND is_default = 1";
        $result = db_query($sql);
        while ($arr = db_fetch_array($result)) {
            $report_id = $arr['report_id'];
        }
        return $report_id;
    }
    
}

?>
