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
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

class GitRepositoryMirrorUpdater
{

    /**
     * @var ProjectHistoryDao
     */
    private $history_dao;

    /**
     * @var Git_Mirror_MirrorDataMapper
     */
    private $mirror_data_mapper;

    public function __construct(Git_Mirror_MirrorDataMapper $mirror_data_mapper, ProjectHistoryDao $history_dao)
    {
        $this->mirror_data_mapper = $mirror_data_mapper;
        $this->history_dao        = $history_dao;
    }

    public function updateRepositoryMirrors(GitRepository $repository, array $mirror_ids)
    {
        if (
            $this->mirror_data_mapper->doesAllSelectedMirrorIdsExist($mirror_ids)
            && $this->mirror_data_mapper->unmirrorRepository($repository->getId())
            && $this->mirror_data_mapper->mirrorRepositoryTo($repository->getId(), $mirror_ids)
        ) {
            $this->history_dao->groupAddHistory(
                "git_repo_mirroring_update",
                $repository->getName(),
                $repository->getProjectId()
            );

            return true;
        }

        return false;
    }
}
