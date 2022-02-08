<?php
/**
 * Copyright (c) Enalean, 2016 - Present. All Rights Reserved.
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
use User_UGroup;

class FineGrainedUpdater
{
    /**
     * @var FineGrainedDao
     */
    private $dao;

    public function __construct(FineGrainedDao $dao)
    {
        $this->dao = $dao;
    }

    public function enableRepository(GitRepository $repository)
    {
        $this->dao->enableRepository($repository->getId());
    }

    public function disableRepository(GitRepository $repository)
    {
        $this->dao->disableRepository($repository->getId());
    }

    public function enableProject(Project $project)
    {
        $this->dao->enableProject($project->getID());
    }

    public function disableProject(Project $project)
    {
        $this->dao->disableProject($project->getID());
    }

    public function deleteUgroupPermissions(User_UGroup $ugroup, $project_id)
    {
        $this->dao->deleteUgroupPermissions($ugroup->getId(), $project_id);
    }
}
