<?php
/**
 * Copyright (c) Enalean, 2014. All Rights Reserved.
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

namespace Tuleap\Git\REST\v1;

use Tuleap\REST\v1\GitRepositoryPermissionRepresentationBase;
use Tuleap\Project\REST\UserGroupRepresentation;
use GitRepository;
use GitRepositoryPermissionsManager;
use GitDao;
use UGroupManager;

class GitRepositoryPermissionRepresentation extends GitRepositoryPermissionRepresentationBase
{

    /** @var GitRepositoryPermissionsManager */
    private $repository_permissions_manager;

    /** @var UGroupManager */
    private $ugroup_manager;

    public function __construct()
    {
        $this->repository_permissions_manager = new GitRepositoryPermissionsManager(new GitDao());
        $this->ugroup_manager                 = new UGroupManager();
    }

    public function build(GitRepository $repository)
    {
        $this->read   = $this->getUGroupsReaders($repository);
        $this->write  = $this->getUGroupsWriters($repository);
        $this->rewind = $this->getUGroupsRewinders($repository);
    }

    private function getUGroupsReaders(GitRepository $repository)
    {
        $read_ugroups_rows   = $this->repository_permissions_manager->getUGroupsReaders($repository);

        return $this->buildUGroupRepresentations($repository, $read_ugroups_rows);
    }

    private function getUGroupsWriters(GitRepository $repository)
    {
        $write_ugroups_rows  = $this->repository_permissions_manager->getUGroupsWriters($repository);

        return $this->buildUGroupRepresentations($repository, $write_ugroups_rows);
    }

    private function getUGroupsRewinders(GitRepository $repository)
    {
        $rewind_ugroups_rows = $this->repository_permissions_manager->getUGroupsRewinders($repository);

        return $this->buildUGroupRepresentations($repository, $rewind_ugroups_rows);
    }

    private function buildUGroupRepresentations(GitRepository $repository, $ugroup_rows)
    {
        $ugroup_representations = array();
        foreach ($ugroup_rows as $row) {
            $ugroup = $this->ugroup_manager->getById($row['ugroup_id']);
            $rewind_ugroup_representation = new UserGroupRepresentation();
            $rewind_ugroup_representation->build((int) $repository->getProjectId(), $ugroup);

            $ugroup_representations[] = $rewind_ugroup_representation;
        }

        return $ugroup_representations;
    }
}
