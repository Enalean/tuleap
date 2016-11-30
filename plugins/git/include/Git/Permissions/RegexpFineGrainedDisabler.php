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

class RegexpFineGrainedDisabler
{
    /**
     * @var RegexpRepositoryDao
     */
    private $regexp_repository_dao;

    /**
     * @var RegexpFineGrainedDao
     */
    private $regexp_dao;
    /**
     * @var RegexpDefaultDao
     */
    private $regexp_default_dao;

    public function __construct(
        RegexpRepositoryDao $regexp_repository_dao,
        RegexpFineGrainedDao $regexp_dao,
        RegexpDefaultDao $regexp_default_dao
    ) {
        $this->regexp_repository_dao = $regexp_repository_dao;
        $this->regexp_dao            = $regexp_dao;
        $this->regexp_default_dao    = $regexp_default_dao;
    }

    public function disableForRepository(GitRepository $repository)
    {
        $this->regexp_repository_dao->disable($repository->getId());
    }

    public function disableForDefault(Project $project)
    {
        $this->regexp_default_dao->disable($project->getId());
    }

    public function disableAtSiteLevel()
    {
        return $this->regexp_dao->disable();
    }
}
