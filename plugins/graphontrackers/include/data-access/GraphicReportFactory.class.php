<?php
/*
 * Copyright (c) Enalean, 2016. All Rights Reserved.
 * Copyright (c) STMicroelectronics, 2006. All Rights Reserved.
 *
 * Originally written by Mahmoud MAALEJ, 2006
 * 
 * This file is a part of Tuleap.
 *
 * Tuleap is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Tuleap is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */
require_once('www/include/pre.php');
require_once('common/tracker/Artifact.class.php');

class GraphicReportFactory {

    var $group_artifact_id;
    var $user_id;
    var $reports;

    /**
	* class constructor
	*
	* 	@return null
    */ 
    
    function __construct($group_artifact_id,$user_id){
        $this->group_artifact_id = $group_artifact_id;
        $this->user_id = $user_id;
    }

    /**
	* function fetchData to fetch all properties of graphic reports from database
	*
	* 	@return array: result set
    */    
    
    function fetchData(){
        $sql = sprintf('SELECT report_graphic_id,group_artifact_id,user_id,name,description 
                        FROM plugin_graphontrackers_report_graphic 
                        WHERE report_graphic_id=%d',
                        db_ei($this->report_graphic_id)
                      );
        $res=db_query($sql);
        return $res;
    }

    /**
    *	Retrieve the artifact report list order by scope
    *
    *	@param	group_artifact_id: the artifact type
    *
    *	@return	array
    */

    function getReports_ids() {
        // If user is unknown then get only project-wide and system wide reports
        // else get personal reports in addition  project-wide and system wide.
        $sql = "SELECT report_graphic_id FROM plugin_graphontrackers_report_graphic WHERE ";
        if ($this->user_id == 100) {
            $sql .= "(group_artifact_id=".db_ei($this->group_artifact_id)." AND scope='P') OR scope='S' ".
            "ORDER BY report_graphic_id";
        } else {
            $sql .= "(group_artifact_id=".db_ei($this->group_artifact_id)." AND (user_id=".db_ei($this->user_id)." OR scope='P')) OR ".
            "scope='S' ORDER BY report_graphic_id";
        }
        $res=db_query($sql);
        return util_result_column_to_array($res);
    }

    /**
     * Display the report list
     *
     * @param : $reports      the list the reports within an artifact to display
     *
     * @return void
     */

    function getReportsAvailable() {
        // If user is unknown then get only project-wide and system wide reports
        // else get personal reports in addition  project-wide and system wide.
        $sql = "SELECT * FROM plugin_graphontrackers_report_graphic WHERE ";
        if ($this->user_id == 100) {
   	        $sql .= "(group_artifact_id=".db_ei($this->group_artifact_id)." AND scope='P') OR scope='S' ".
   	        "ORDER BY report_graphic_id";
        } else {
            $sql .= "(group_artifact_id=".db_ei($this->group_artifact_id)." AND (user_id=".db_ei($this->user_id)." OR scope='P')) OR ".
            "scope='S' ORDER BY report_graphic_id";
        }
        return db_query($sql);
    }

    /**
	* function getReportGraphicIdFromName to get graphic report name from a identifier
	*
	* 	@return String graphic report name
    */  
        
    function getReportGraphicIdFromName($report_name){
        $sql = sprintf('SELECT report_graphic_id 
                        FROM plugin_graphontrackers_report_graphic 
                        WHERE name="%s"',
                        db_es($report_name)
                      );
        $res = db_query($sql);
        $result[0] = db_fetch_array($res);
        return $result[0][0];
    }
}
