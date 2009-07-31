<?php
/**
 * Copyright (c) STMicroelectronics, 2004-2009. All rights reserved
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

require_once 'UGroupDynamicManager.class.php';
require_once 'UGroupStaticManager.class.php';

class UGroupFactory {
    private static $instance = null;
    private $dynManager      = null;
    private $staManager      = null;
    
    private function __construct() {
    }
    
    private function __clone() {
        throw Exception('Cannot clone singleton');
    }
    
    /**
     * @return UGroupFactory
     */
    public static function instance() {
        if (!isset(self::$instance)) {
            self::$instance = new UGroupFactory();
        }
        return self::$instance;
    }
    
    /**
     * Return on ugroup based on it's id
     * 
     * @param Integer $id          UGroup id
     * @param Boolean $withMembers Return group with member list prefiled
     * 
     * @return Ugroup
     */
    public function getGroupById($id, $groupId, $withMembers=false) {
        return $this->getManagerById($id)->getGroupById($id, $groupId, $withMembers);
    }
    
    /**
     * Get right user group manager according to group id
     * 
     * @param Integer $id User group id
     * 
     * @return UGroupManager
     */
    protected function getManagerById($id) {
        if ($id > 100) {
            return $this->getStaticUGroupManager();
        } else {
            return $this->getDynamicUGroupManager();
        }
    } 
    
    /**
     * @return UGroupDynamicManager
     */
    protected function getDynamicUGroupManager() {
        if (!isset($this->dynManager)) {
            $this->dynManager = new UGroupDynamicManager();
        }
        return $this->dynManager;
    }
    
    /**
     * 
     * @return UGroupStaticManager
     */
    protected function getStaticUGroupManager() {
        if (!isset($this->staManager)) {
            $this->staManager = new UGroupStaticManager();
        }
        return $this->staManager;
    }
}

?>