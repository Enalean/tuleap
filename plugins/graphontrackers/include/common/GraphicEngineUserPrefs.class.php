<?php
/*
 * Copyright (c) Enalean, 2016-2018. All Rights Reserved.
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
require_once('common/tracker/ArtifactField.class.php');
require_once('common/tracker/ArtifactReport.class.php');


class graphicEngineUserPrefs {

    var $atid;
    var $prefs;
	var $advsrch;
	var $morder;
	var $report_id;

    /**
	 * Class constructor
	 *
	 * 	@param atid:artifact type id
	 */

	function __construct($atid) {
	    $this->atid = $atid;
	}

    /**
	 * function to set user preferences
	 *
	 * 	@return null
	 */


    function fetchPrefs(){

    	$prefs     = array();
        $advsrch   = 0;
        $morder    = "";
        $report_id = 100;

        //if (user_isloggedin()) {
    	    $custom_pref = user_get_preference('artifact_brow_cust'.$this->atid);
	        if ($custom_pref) {
	            $pref_arr = explode('&',substr($custom_pref,1));
                foreach ($pref_arr as $expr) {
    	            // Extract left and right parts of the assignment
		            // and remove the '[]' array symbol from the left part
		            list($field,$value_id) = explode('=',$expr);
		            $field = str_replace('[]','',$field);
		            if ($field == 'advsrch')
    		            $advsrch = ($value_id ? 1 : 0);
		            else if ($field == 'msort')
  		                $msort = ($value_id ? 1 : 0);
		            else if ($field == 'chunksz')
  		                $chunksz = $value_id;
		            else if ($field == 'report_id')
		                $report_id = $value_id;
		            else
		                $prefs[$field][] = urldecode($value_id);
		            //echo '<br>DBG restoring prefs : $prefs['.$field.'] []='.$value_id;
	            }
	        }
            $morder = user_get_preference('artifact_browse_order'.$this->atid);
        //}
        $this->prefs     = $prefs;
	    $this->advsrch   = $advsrch;
	    $this->morder    = $morder;
	    $this->report_id = $report_id;
    }

    /**
	 * function to get artifacts in specified preference order
	 *
	 * 	@return null
	 *
	 */

    public function getArtifactsInOrder()
	{
        $select   = null;
        $from     = null;
        $where    = null;
        $order_by = null;

        $ar  = new ArtifactReport($this->report_id,$this->atid);
        $ar->getResultQueryElements($this->prefs,$this->morder,$this->advsrch,$aids = false,$select,$from,$where,$order_by);
        
        //artifact permissions
        $sql_group_id = "SELECT group_id FROM artifact_group_list WHERE group_artifact_id=". db_ei($this->atid);
        $result_group_id = db_query($sql_group_id);
        if (db_numrows($result_group_id)>0) {
            $row = db_fetch_array($result_group_id);
            $group_id = $row['group_id'];
        }
        $user  = UserManager::instance()->getCurrentUser();
        $ugroups = $user->getUgroups($group_id, array('artifact_type' => $this->atid));

        $from  .= " LEFT JOIN permissions 
                         ON (permissions.object_id = CONVERT(a.artifact_id USING utf8)
                             AND 
                             permissions.permission_type = 'TRACKER_ARTIFACT_ACCESS') ";
        $where .= " AND (a.use_artifact_permissions = 0
                         OR 
                         (
                             permissions.ugroup_id IN (". implode(',', $ugroups) .")
                         )
                   ) ";
        

        if ($order_by == "") {
            $sql = "SELECT DISTINCT art.artifact_id FROM (SELECT STRAIGHT_JOIN a.artifact_id $from $where $order_by) AS art";
        } else {
        	$sql = "SELECT DISTINCT art.artifact_id FROM (SELECT STRAIGHT_JOIN a.artifact_id $from $where $order_by,a.artifact_id ASC) AS art";
        }

        return $ar->_ExecuteQueryForSelectReportItems($sql);
    }
}
