<?php
/*
 * Copyright (c) Enalean, 2016. All Rights Reserved.
 * Copyright (c) STMicroelectronics, 2006. All Rights Reserved.
 *
 * Originally written by Mahmoud MAALEJ, 2006. STMicroelectronics.
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

require_once('GraphOnTrackers_ChartDao.class.php');

class GraphOnTrackers_Report {

    var $id;
    var $name;
    var $description;
    var $scope;
    var $group_artifact_id;
    var $user_id;
    protected $charts;
    protected $group_id;
    protected $plugin_charts;

    /**
    * class constructor
    *
    *     @return null
    */   
     
    function __construct($report_graphic_id){
        $this->id = $report_graphic_id;
        $sql = sprintf('SELECT group_id, rg.*
                        FROM plugin_graphontrackers_report_graphic AS rg INNER JOIN artifact_group_list USING (group_artifact_id)
                        WHERE report_graphic_id=%d',
                        db_ei($this->id)
                      );
        $res = db_query($sql);
        $arr = db_fetch_array($res);
        $this->group_id          = $arr['group_id'];
        $this->name              = $arr['name'];
        $this->scope             = $arr['scope'];
        $this->description       = $arr['description'];
        $this->group_artifact_id = $arr['group_artifact_id'];
        $this->user_id           = $arr['user_id'];
        
        $this->charts = null;
        
        $this->chart_factories = array();
        $em = EventManager::instance();
        $em->processEvent('graphontrackers_load_chart_factories', array('factories' => &$this->chart_factories));
    }

    /**
    * getter method to set report_graphic_id
    *     @return null
    */  
      
    function getId() {
        return $this->id;
    }
    
    /**
    * getter method to get graphic report name
    *
    *     @return String name : the graphic report name
    */   
      
    function getName() {
        return $this->name; 
    }

    /**
    * setter method to set name
    *  @param int name: the graphic report name
    *     @return null
    */ 
         
    function setName($name) {
        $this->name = $name;
    }    
    
    /**
    * getter method to get graphic report description
    *
    *     @return String description : the graphic report description
    */  
       
    function getDescription() {
        return $this->description;
    }
    
    /**
    * setter method to set description
    *  @param int description: the graphic report description
    *     @return null
    */  
       
    function setDescription($description) {
        $this->description = $description;
    }
    
    /**
    * getter method to get graphic report scope
    *
    *     @return int scope : the graphic report scope (Personal,Project)
    */  
       
    function getScope() {
        return $this->scope;    
    }

    /**
    * setter method to set scope
    *  @param int scope: the graphic report scope (Personal,Project)
    *     @return null
    */      
    
    function setScope($scope) {
        $this->scope = $scope;
    }
    
    /**
    * getter method to get graphic report artifact group identifier
    *
    *     @return int group_artifact_id : tracker type
    */
      
    function getGroup_artifact_id() {
        return $this->group_artifact_id;
    }
    function getAtid() {
        return $this->getGroup_artifact_id();
    }
    /**
    * setter method to set  graphic report artifact group identifier
    *  @param int group_artifact_id: the graphic report artifact group identifier
    *     @return null
    */  
      
    function setGroup_artifact_id($group_artifact_id) {
        $this->group_artifact_id = $group_artifact_id;
    }
    function setAtid($group_artifact_id) {
        $this->setGroup_artifact_id($group_artifact_id);
    }
    
    /**
    * getter method to get user identifier
    *
    *     @return int user_id : the user identifier
    */
    function getUserId() {
        return $this->user_id;
    }
    
    /**
    * setter method to set user identifier
    *  @param int user_id: the user identifier
    */  
       
    function setUserId($user_id) {
        return $this->user_id = $user_id;
    }
    
    function getGroupId() { 
        return $this->group_id; 
    }
    
    /**
    * function fetchData to fetch graphic report  properties  from database
    *
    * @return array
    */    

    function fetchData(){

        $sql = sprintf('SELECT *
                        FROM plugin_graphontrackers_report_graphic 
                        WHERE report_graphic_id=%d',
                        db_ei($this->report_graphic_id)
                      );
        $res = db_query($sql);
        $arr = db_fetch_array($res);
        return $arr;
    }
    
    /**
    * Return a label for the scope code
    *
    * param scope: the scope code
    *
    * @return string
    */

    function getScopeLabel($scope) {
        
        switch ( $scope ) {
            case 'P':
                return $GLOBALS['Language']->getText('global','Project');
            case 'I':
                return $GLOBALS['Language']->getText('global','Personal');
            case 'S':
                return $GLOBALS['Language']->getText('global','System');
        }
    }
    
    /**
    * update - use this to reset a Report in the database.
    * 
    * @param string The report name.
    * @param string The report description.
    * @return true on success, false on failure.
    */

    function update() {
        $sql = sprintf("UPDATE plugin_graphontrackers_report_graphic 
                        SET name='%s', description='%s',scope='%s' 
                        WHERE report_graphic_id = %d",
                        db_es($this->name),db_es($this->description),
                        db_es($this->scope),db_ei($this->id)
                      );
        $res = db_query($sql);
        return db_affected_rows($res);
    }
    
    /**
    *    delete - use this to remove a Report from the database.
    *
    *    @return true on success, false on failure.
    */

    function delete() {
        if (!$this->charts) {
            $this->loadCharts();
        }
        for($i=0;$i<sizeof($this->charts);$i++){
        	$this->deleteChart($this->charts[$i]->getId());
        }
        // then delete the report entry item
        
        $sql = sprintf('DELETE FROM plugin_graphontrackers_report_graphic 
                        WHERE report_graphic_id=%d',
                        db_ei($this->id)
                      );
                      
        $res = db_query($sql);
        
        $report_id = $this->id;
        // empty the object properties
        $this->name = '';
        $this->description = '';
        $this->scope = '';
        $this->id = '';
        $this->group_artifact_id = '';
        $this->user_id = '';
        
        // return the deleted report id
        return $report_id;
    }

    /**
     * create - use this to create a new Report in the database.
     * 
     * @param string The report name.
     * @param string The report description.
     * @return id on success, false on failure.
     */
    public static function create($atid, $user_id, $name, $description, $scope) {
        $sql = sprintf("INSERT INTO plugin_graphontrackers_report_graphic 
                       (group_artifact_id,user_id,name,description,scope) 
                        VALUES (%d,%d,'%s','%s','%s')",
                        db_ei($atid),db_ei($user_id),db_es($name),
                        db_es($description),db_es($scope)
                      );
        $res = db_query($sql);
        
        $report = null;
        if($res && db_affected_rows($res)) {
            $report = new GraphOnTrackers_Report(db_insertid($res));
        }
        return $report;
    }
    
    /**
     * retrieve the charts defined in this report
     * @return array
     */
    public function getCharts() {
        if (!$this->charts) {
            $this->loadCharts();
        }
        return $this->charts;
    }
    
    /**
     * retrieve a specific chart by its id
     */
    public function getChart($id) {
        $c = null;
        $dao = new GraphOnTrackers_ChartDao(CodendiDataAccess::instance());
        $dar = $dao->searchById($id);
        if ($dar && $dar->valid() && ($row = $dar->getRow())) {
            $c = $this->instanciateChart($row);
        }
        return $c;
    }
    
    protected function loadCharts() {
        $this->charts = array();
        $dao = new GraphOnTrackers_ChartDao(CodendiDataAccess::instance());
        $dar = $dao->searchByReportId($this->id);
        foreach($dar as $row) {
            if ($c = $this->instanciateChart($row)) {
                $this->charts[] = $c;
            }
        }
    }
    
    protected function instanciateChart($row) {
        $c = null;
        if ($chart_classname = $this->getChartClassname($row['chart_type'])) {
            $c = new $chart_classname($this, $row['id'], $row['rank'], $row['title'], $row['description'], $row['width'], $row['height']);
        }
        return $c;
    }
    
    public function deleteChart($id) {
        $ok = false;
        if ($c = $this->getChart($id)) {
            $dao = new GraphOnTrackers_ChartDao(CodendiDataAccess::instance());
            $dao->delete($id);
            $c->delete();
        }
    }
    
    public function createChart($chart_type) {
        $chart = null;
        if ($chart_classname = $this->getChartClassname($chart_type)) {
            $dao = new GraphOnTrackers_ChartDao(CodendiDataAccess::instance());
            $default_title       = 'Untitled '.$chart_type;
            $default_description = '';
            $default_width       = call_user_func(array($chart_classname, 'getDefaultWidth'));
            $default_height      = call_user_func(array($chart_classname, 'getDefaultHeight'));
            $id = $dao->create($this->id, $chart_type, $default_title, $default_description, $default_width, $default_height);
            $rank = $dao->getRank($id);
            
            $chart = call_user_func(array($chart_classname, 'create'), $this, $id, $rank, $default_title, $default_description, $default_width, $default_height);
        }
        return $chart;
    }
    
    protected function getChartClassname($chart_type) {
        $chart_classname = null;
        if (isset($this->chart_factories[$chart_type])) {
            $chart_classname = $this->chart_factories[$chart_type]['chart_classname'];
        }
        return $chart_classname;
    }
    
    public function getChartFactories() {
        return $this->chart_factories;
    }
}
