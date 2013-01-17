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
    
    /**
     * @var Project
     */
    private $project;

    /**
     *
     * @var Codendi_HTMLPurifier 
     */
    private $text_purifier;

    /**
     * @var integer
     */
    private $selected_template_id;

    public function __construct(Project $project, $selected_template_id) {
        $this->project       = $project;
        $this->text_purifier = Codendi_HTMLPurifier::instance();
        $this->selected_template_id = $selected_template_id;
    }

    /**
     * 
     * @return int
     */
    public function getGroupId() {
        return $this->project->getID();
    }
    
    /**
     * 
     * @return string
     */
    public function getUserGroupName() {
        return $this->project->getPublicName();
    }
    
    /**
     * 
     * @param string $format A valid php date format
     * @return string
     */
    public function getFormattedDateRegistered() {
        return date($GLOBALS['Language']->getText('system', 'datefmt_short'), $this->project->getStartDate());
    }
    
    /**
     * 
     * @return string
     */
    public function getUnixGroupName() {
        return $this->project->getUnixName();
    }
    
    /**
     * 
     * @return array List of names of admin users for this template
     */
    public function getAdminUserNames() {
        $ugroup_manager = new UGroupManager();
        $admin_ugroup   = $ugroup_manager->getUGroup($this->project, UGroup::PROJECT_ADMIN);
        return $admin_ugroup->getMembersUserName();
    }
    
    /**
     * 
     * @return array List of names
     */
    public function getServicesUsed() {
        return implode(', ', $this->project->getAllUsedServices());
    }
    
    /**
     * 
     * @return string
     */
    public function getPurifiedUserGroupName() {
        return $this->text_purifier->purify(
                util_unconvert_htmlspecialchars($this->project->getPublicName()),
                CODENDI_PURIFIER_CONVERT_HTML
                );
    }
    
    /**
     * 
     * @return string
     */
    public function getPurifiedShortDescription() {
        return $this->text_purifier->purify(
                util_unconvert_htmlspecialchars($this->project->getDescription()),
                CODENDI_PURIFIER_LIGHT, 
                $this->project->getID()
                );
    }

    public function isSelectedTemplate() {
        return $this->selected_template_id == $this->project->getID();
    }
}


?>