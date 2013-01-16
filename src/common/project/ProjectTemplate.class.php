<?php

/**
 * Copyright (c) Enalean, 2013. All rights reserved
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/
 */

require_once 'common/project/Project.class.php';

class ProjectTemplate {
    
    const ADMIN_GROUP = 100;
    
    /**
     *
     * @var int 
     */
    private $user_group_id;
    
    /**
     *
     * @var string 
     */
    private $user_group_name;
    
    /**
     *
     * @var string 
     */
    private $date_registered;
    
    /**
     *
     * @var string 
     */
    private $unix_group_name;
    
    /**
     *
     * @var string 
     */
    private $short_description;
    
    /**
     *
     * @var Codendi_HTMLPurifier 
     */
    private $text_purifier;

    public function __construct() {
        $this->text_purifier = Codendi_HTMLPurifier::instance();
    }
    
    /**
     * 
     * @param int $group_id
     * @return \Template
     */
    public function setUserGroupId($group_id) {
        $this->user_group_id = (int) $group_id;
        return $this;
    }
    
    /**
     * 
     * @param string $group_name
     * @return \Template
     */
    public function setUserGroupName($group_name) {
        $this->user_group_name = (string) $group_name;
        return $this;
    }
    
    /**
     * 
     * @param string $date
     * @return \Template
     */
    public function setDateRegistered($date) {
        $this->date_registered = $date;
        return $this;
    }
    
    /**
     * 
     * @param string $name
     * @return \Template
     */
    public function setUnixGroupName($name) {
        $this->unix_group_name = $name;
        return $this;
    }
    
    /**
     * 
     * @param string $description
     * @return \Template
     */
    public function setShortDescription($description) {
        $this->short_description = $description;
        return $this;
    }
    
    /**
     * 
     * @return bool
     */
    public function userBelongsToAdminGroup() {
        return $this->user_group_id == self::ADMIN_GROUP;
    }
    
    /**
     * 
     * @return int
     */
    public function getUserGroupId() {
        return $this->user_group_id;
    }
    
    /**
     * 
     * @return string
     */
    public function getUserGroupName() {
        return $this->user_group_name;
    }
    
    /**
     * 
     * @param string $format A valid php date format
     * @return string
     */
    public function getFormattedDateRegistered($format) {
        return date($format, $this->date_registered); 
    }
    
    /**
     * 
     * @return string
     */
    public function getUnixGroupName() {
        return $this->unix_group_name;
    }
    
    /**
     * 
     * @return array List of names of admin users for this template
     */
    public function getAdminUserNames() {
        $group_id = (int) $this->getUserGroupId();
        
        if ($this->userBelongsToAdminGroup()) {
            $res_admin = db_query("
                SELECT user_name AS user_name 
                FROM user
                WHERE user_id = 101"
                );
        } else {
            $res_admin = db_query("
                SELECT user.user_name AS user_name
                FROM user,user_group
                WHERE user_group.user_id=user.user_id 
                    AND user_group.group_id = $group_id 
                    AND user_group.admin_flags = 'A'"
                );
        }
        
        $admins = array();
        while ($row_admin = db_fetch_array($res_admin)) {
            $admins[] = $row_admin['user_name'];
        }
        
        return $admins;
    }
    
    public function getServicesUsed() {
        if($this->getUserGroupId() == null) {
            return array();
        }
        
        $template_project = new Project($this->getUserGroupId());
        return $template_project->getAllUsedServices();
    }
    
    /**
     * 
     * @return string
     */
    public function getPurifiedUserGroupName() {
        return $this->text_purifier->purify(
                util_unconvert_htmlspecialchars($this->user_group_name), 
                CODENDI_PURIFIER_CONVERT_HTML
                );
    }
    
    /**
     * 
     * @return string
     */
    public function getPurifiedShortDescription() {
        return $this->text_purifier->purify(
                util_unconvert_htmlspecialchars($this->short_description), 
                CODENDI_PURIFIER_LIGHT, 
                $this->user_group_id
                );
    }
}


?>