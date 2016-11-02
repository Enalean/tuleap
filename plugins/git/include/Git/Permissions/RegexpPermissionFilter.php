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

class RegexpPermissionFilter
{
    /**
     * @var FineGrainedPermissionFactory
     */
    private $permission_factory;
    /**
     * @var PatternValidator
     */
    private $pattern_validator;
    /**
     * @var FineGrainedPermissionDestructor
     */
    private $permission_destructor;

    public function __construct(
        FineGrainedPermissionFactory $permission_factory,
        PatternValidator $pattern_validator,
        FineGrainedPermissionDestructor $permission_destructor
    ) {
        $this->permission_factory    = $permission_factory;
        $this->pattern_validator     = $pattern_validator;
        $this->permission_destructor = $permission_destructor;
    }

    public function filterNonRegexpPermissions(GitRepository $repository)
    {
        $removed = array();

        $branches_permissions = $this->permission_factory->getBranchesFineGrainedPermissionsForRepository($repository);
        $this->removeRegexpPermissions($repository, $branches_permissions, $removed);

        $tags_permissions = $this->permission_factory->getTagsFineGrainedPermissionsForRepository($repository);
        $this->removeRegexpPermissions($repository, $tags_permissions, $removed);

        return count($removed) > 0;
    }

    private function removeRegexpPermissions(GitRepository $repository, array $permissions, array &$removed)
    {
        foreach ($permissions as $permission) {
            if (! $this->pattern_validator->isValid($permission->getPattern())) {
                $this->permission_destructor->deleteRepositoryPermissions($repository, $permission->getId());
                $removed[] = $permission->getId();
            }
        }
    }
}
