<?php
/**
 * Copyright (c) Enalean, 2014-Present. All Rights Reserved.
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
use Tuleap\Project\REST\MinimalUserGroupRepresentation;
use GitRepository;
use GitRepositoryPermissionsManager;
use GitDao;
use UGroupManager;

/**
 * @psalm-immutable
 */
class GitRepositoryPermissionRepresentation extends GitRepositoryPermissionRepresentationBase
{
    /**
     * @param MinimalUserGroupRepresentation[] $read
     * @param MinimalUserGroupRepresentation[] $write
     * @param MinimalUserGroupRepresentation[] $rewind
     */
    private function __construct(array $read, array $write, array $rewind)
    {
        $this->read   = $read;
        $this->write  = $write;
        $this->rewind = $rewind;
    }

    public static function build(GitRepository $repository): self
    {
        $repository_permissions_manager = new GitRepositoryPermissionsManager(new GitDao());
        $ugroup_manager                 = new UGroupManager();

        return new self(
            self::getUGroupsReaders($repository_permissions_manager, $ugroup_manager, $repository),
            self::getUGroupsWriters($repository_permissions_manager, $ugroup_manager, $repository),
            self::getUGroupsRewinders($repository_permissions_manager, $ugroup_manager, $repository),
        );
    }

    /**
     * @return MinimalUserGroupRepresentation[]
     */
    private static function getUGroupsReaders(
        GitRepositoryPermissionsManager $repository_permissions_manager,
        UGroupManager $ugroup_manager,
        GitRepository $repository
    ): array {
        $read_ugroups_rows = $repository_permissions_manager->getUGroupsReaders($repository);

        return self::buildUGroupRepresentations($ugroup_manager, $repository, $read_ugroups_rows);
    }

    /**
     * @return MinimalUserGroupRepresentation[]
     */
    private static function getUGroupsWriters(
        GitRepositoryPermissionsManager $repository_permissions_manager,
        UGroupManager $ugroup_manager,
        GitRepository $repository
    ): array {
        $write_ugroups_rows = $repository_permissions_manager->getUGroupsWriters($repository);

        return self::buildUGroupRepresentations($ugroup_manager, $repository, $write_ugroups_rows);
    }

    /**
     * @return MinimalUserGroupRepresentation[]
     */
    private static function getUGroupsRewinders(
        GitRepositoryPermissionsManager $repository_permissions_manager,
        UGroupManager $ugroup_manager,
        GitRepository $repository
    ): array {
        $rewind_ugroups_rows = $repository_permissions_manager->getUGroupsRewinders($repository);

        return self::buildUGroupRepresentations($ugroup_manager, $repository, $rewind_ugroups_rows);
    }

    /**
     * @return MinimalUserGroupRepresentation[]
     */
    private static function buildUGroupRepresentations(UGroupManager $ugroup_manager, GitRepository $repository, $ugroup_rows): array
    {
        $ugroup_representations = [];
        foreach ($ugroup_rows as $row) {
            $ugroup = $ugroup_manager->getById($row['ugroup_id']);
            $rewind_ugroup_representation = new MinimalUserGroupRepresentation((int) $repository->getProjectId(), $ugroup);

            $ugroup_representations[] = $rewind_ugroup_representation;
        }

        return $ugroup_representations;
    }
}
