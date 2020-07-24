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

use Tuleap\Git\AccessRightsPresenterOptionsBuilder;
use Project;

class FineGrainedRepresentationBuilder
{

    /**
     * @var AccessRightsPresenterOptionsBuilder
     */
    private $option_builder;

    public function __construct(AccessRightsPresenterOptionsBuilder $option_builder)
    {
        $this->option_builder = $option_builder;
    }

    public function buildRepositoryPermission(FineGrainedPermission $permission, Project $project)
    {
        return [
            'id'        => $permission->getId(),
            'pattern'   => $permission->getPatternWithoutPrefix(),
            'writers'   => $this->option_builder->getWriteOptionsForFineGrainedPermissions($permission, $project),
            'rewinders' => $this->option_builder->getRewindOptionsForFineGrainedPermissions($permission, $project),
        ];
    }

    public function buildDefaultPermission(DefaultFineGrainedPermission $permission, Project $project)
    {
        return [
            'id'        => $permission->getId(),
            'pattern'   => $permission->getPatternWithoutPrefix(),
            'writers'   => $this->option_builder->getWriteOptionsForDefaultFineGrainedPermissions($permission, $project),
            'rewinders' => $this->option_builder->getRewindOptionsForDefaultFineGrainedPermissions($permission, $project),
        ];
    }
}
