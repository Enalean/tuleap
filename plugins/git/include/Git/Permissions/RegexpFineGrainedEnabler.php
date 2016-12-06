<?php
/**
 * Copyright (c) Enalean, 2016. All Rights Reserved.
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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

namespace Tuleap\Git\Permissions;

use GitRepository;
use Project;

class RegexpFineGrainedEnabler
{
    /**
     * @var RegexpFineGrainedDao
     */
    private $regexp_dao;

    /**
     * @var RegexpRepositoryDao
     */
    private $regexp_repository_dao;
    /**
     * @var RegexpTemplateDao
     */
    private $regexp_template_dao;

    public function __construct(
        RegexpFineGrainedDao $regexp_dao,
        RegexpRepositoryDao $regexp_repository_dao,
        RegexpTemplateDao $regexp_template_dao
    ) {
        $this->regexp_dao            = $regexp_dao;
        $this->regexp_repository_dao = $regexp_repository_dao;
        $this->regexp_template_dao   = $regexp_template_dao;
    }

    public function enable()
    {
        return $this->regexp_dao->enable();
    }

    public function enableForRepository(GitRepository $repository)
    {
        $this->regexp_repository_dao->enable($repository->getId());
    }

    public function enableForTemplate(Project $project)
    {
        $this->enableForTemplateWithProjectId($project->getID());
    }

    public function enableForTemplateWithProjectId($project_id)
    {
        $this->regexp_template_dao->enable($project_id);
    }
}
