<?php
/**
  * Copyright (c) Enalean, 2015. All rights reserved
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

/**
 * Presenter factory for one step registration project
 */
class Project_OneStepRegistration_OneStepRegistrationPresenterFactory
{

    /**
     * @var Project
     */
    private $project;

    public function __construct(Project $project)
    {
        $this->project = $project;
    }

    public function create()
    {
        if ($this->projectsMustBeApprovedByAdmin()) {
            $presenter = new Project_OneStepRegistration_OneStepRegistrationApprovalPresenter();
        } else {
            $presenter = new Project_OneStepRegistration_OneStepRegistrationPresenter($this->project);
        }

        return $presenter;
    }

    private function projectsMustBeApprovedByAdmin()
    {
        return ForgeConfig::get(\ProjectManager::CONFIG_PROJECT_APPROVAL, 1) == 1;
    }
}
