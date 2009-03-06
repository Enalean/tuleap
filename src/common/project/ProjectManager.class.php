<?php
/*
 * Copyright (c) Xerox, 2008. All Rights Reserved.
 *
 * Originally written by Nicolas Terray, 2008. Xerox Codendi Team.
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
 * along with CodeX; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

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
    protected function createProjectInstance($group_id) {
        require_once('Project.class.php');
        return new Project($group_id);
    }
    
    /**
     * Clear the cache for project $group_id
     */
    public function clear($group_id) {
        unset($this->_cached_projects[$group_id]);
    }
}
?>
