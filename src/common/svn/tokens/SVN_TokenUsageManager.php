<?php
/**
 * Copyright (c) Enalean, 2015. All Rights Reserved.
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

class SVN_TokenUsageManager {

    /**
     * @var SVN_TokenDao
     */
    private $dao;

    /**
     * @var ProjectManager
     */
    private $project_manager;

    public function __construct(SVN_TokenDao $dao, ProjectManager $project_manager) {
        $this->dao             = $dao;
        $this->project_manager = $project_manager;
    }

    public function canAuthorizeTokens(Project $project) {
        $result = $this->dao->isProjectAuthorizingTokens($project->getID());

        return $result && $result->rowCount() === 0;
    }

    public function setProjectAuthorizesTokens(Project $project) {
        return $this->dao->setProjectAuthorizesTokens($project->getID());
    }

    public function isProjectAuthorizingTokens(Project $project) {
        $result = $this->dao->isProjectAuthorizingTokens($project->getID());

        return $result && $result->rowCount() === 1;
    }

    /**
     * @return Project[]
     */
    public function getProjectsAuthorizingTokens() {
        $projects = array();

        foreach ($this->dao->getProjectsAuthorizingTokens() as $row) {
            $project_id = $row['project_id'];

            $projects[] = $this->project_manager->getProject($project_id);
        }

        return $projects;
    }

}