<?php
/**
 * Copyright (c) Enalean, 2013-Present. All rights reserved
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

class ProjectCreationTemplatePresenter
{

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
     * @var int
     */
    private $selected_template_id;

    public function __construct(Project $project, $selected_template_id)
    {
        $this->project       = $project;
        $this->text_purifier = Codendi_HTMLPurifier::instance();
        $this->selected_template_id = $selected_template_id;
    }

    /**
     *
     * @return int
     */
    public function getGroupId()
    {
        return $this->project->getID();
    }

    /**
     *
     * @return string coma separated list of names of admin users for this template
     */
    public function getAdminUserNames()
    {
        $ugroup_manager = new UGroupManager();
        $admin_ugroup   = $ugroup_manager->getProjectAdminsUGroup($this->project);
        $user_helper    = UserHelper::instance();
        $users          = array();
        foreach ($admin_ugroup->getMembers() as $user) {
            $users[] = $user_helper->getLinkOnUser($user);
        }
        return implode(', ', $users);
    }

    /**
     *
     * @return string coma separated list of names
     */
    public function getServicesUsed()
    {
        return implode(', ', $this->project->getAllUsedServices());
    }

    public function getServicesUsedTitle()
    {
        return $GLOBALS['Language']->getText('register_project_one_step', 'services_used');
    }
    /**
     *
     * @return string
     */
    public function getPurifiedProjectName()
    {
        return $this->text_purifier->purify(
            $this->project->getPublicName(),
            CODENDI_PURIFIER_CONVERT_HTML
        );
    }

    /**
     *
     * @return string
     */
    public function getPurifiedShortDescription()
    {
        return $this->text_purifier->purify(
            $this->project->getDescription(),
            CODENDI_PURIFIER_LIGHT,
            $this->project->getID()
        );
    }

    /**
     * @return bool
     */
    public function isSelectedTemplate()
    {
        return $this->selected_template_id == $this->project->getID();
    }
}
