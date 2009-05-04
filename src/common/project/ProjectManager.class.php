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
require_once('Project.class.php');
require_once('common/dao/ProjectDao.class.php');

/**
 * Provide access to projects
 */
class ProjectManager {
    
    /**
     * The Projects dao used to fetch data
     */
    //protected $_dao;
    
    /**
     * stores the fetched projects
     */
    protected $_cached_projects;
    
    /**
     * Hold an instance of the class
     */
    private static $_instance;
    
    /**
     * A private constructor; prevents direct creation of object
     */
    private function __construct() {
    //    $this->_dao = $this->getDao();
        $this->_cached_projects = array();
    }
    
    /**
     * ProjectManager is a singleton
     */
    public static function instance() {
        if (!isset(self::$_instance)) {
            $c = __CLASS__;
            self::$_instance = new $c;
        }
        return self::$_instance;
    }
    
    /**
     * @return DataAccessObject the projects dao
     */
    //public function getDao() {
    //    return new GroupDao(CodendiDataAccess::instance());
    //}
    
    /**
     * @param $group_id int The id of the project to look for
     * @return Project
     */
    public function getProject($group_id) {
        if (!isset($this->_cached_projects[$group_id])) {
            $p = $this->createProjectInstance($group_id);
            $this->_cached_projects[$group_id] = $p;
        }
        return $this->_cached_projects[$group_id];
    }
    
    /**
     * @param $group_id int The id of the project to look for
     * @return Project
     */
    protected function createProjectInstance($group_id_or_row) {
        return new Project($group_id_or_row);
    }
    
    /**
     * Clear the cache for project $group_id
     */
    public function clear($group_id) {
        unset($this->_cached_projects[$group_id]);
    }
    
    public function getProjectsByStatus($status) {
        $projects = array();
        $dao = new ProjectDao(CodendiDataAccess::instance());
        foreach($dao->searchByStatus($status) as $row) {
            if (!isset($this->_cached_projects[$row['group_id']])) {
                $p = $this->createProjectInstance($row);
                $this->_cached_projects[$row['group_id']] = $p;
            }
            $projects[$row['group_id']] = $this->_cached_projects[$row['group_id']];
        }
        return $projects;
    }
}
?>
