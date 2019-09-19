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

class Git_MirrorResourceRestrictor
{

    /**
     * @var Git_RestrictedMirrorDao
     */
    private $restricted_mirror_dao;

    /**
     * @var Git_Mirror_MirrorDataMapper
     */
    private $mirror_data_mapper;

    /**
     * @var Git_SystemEventManager
     */
    private $git_system_event_manager;

    /**
     * @var ProjectHistoryDao
     */
    private $history_dao;


    public function __construct(
        Git_RestrictedMirrorDao $restricted_mirror_dao,
        Git_Mirror_MirrorDataMapper $mirror_data_mapper,
        Git_SystemEventManager $git_system_event_manager,
        ProjectHistoryDao $history_dao
    ) {
        $this->restricted_mirror_dao    = $restricted_mirror_dao;
        $this->mirror_data_mapper       = $mirror_data_mapper;
        $this->git_system_event_manager = $git_system_event_manager;
        $this->history_dao              = $history_dao;
    }

    public function isMirrorRestricted(Git_Mirror_Mirror $mirror)
    {
        return $this->restricted_mirror_dao->isResourceRestricted($mirror->id);
    }

    public function setMirrorRestricted(Git_Mirror_Mirror $mirror)
    {
        return $this->restricted_mirror_dao->setResourceRestricted($mirror->id);
    }

    public function unsetMirrorRestricted(Git_Mirror_Mirror $mirror)
    {
        return $this->restricted_mirror_dao->unsetResourceRestricted($mirror->id);
    }

    public function allowProjectOnMirror(Git_Mirror_Mirror $mirror, Project $project)
    {
        return $this->restricted_mirror_dao->allowProjectOnResource($mirror->id, $project->getId());
    }

    public function revokeProjectsFromMirror(Git_Mirror_Mirror $mirror, array $project_ids)
    {
        $this->restricted_mirror_dao->revokeProjectsFromResource($mirror->id, $project_ids);

        $repositories = $this->mirror_data_mapper->fetchAllProjectRepositoriesForMirror($mirror, $project_ids);

        foreach ($repositories as $repository) {
            $this->mirror_data_mapper->unmirrorRepository($repository->getId());
            $this->git_system_event_manager->queueRepositoryUpdate($repository);
            $this->history_dao->groupAddHistory(
                "git_repo_mirroring_update",
                $repository->getName(),
                $repository->getProjectId()
            );
        }

        return true;
    }

    public function searchAllowedProjectsOnMirror(Git_Mirror_Mirror $mirror)
    {
        $rows     = $this->restricted_mirror_dao->searchAllowedProjectsOnResource($mirror->id);
        $projects = array();

        foreach ($rows as $row) {
            $projects[] = new Project($row);
        }

        return $projects;
    }
}
