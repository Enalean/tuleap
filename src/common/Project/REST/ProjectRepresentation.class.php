<?php
/**
 * Copyright (c) Enalean, 2013 - Present. All Rights Reserved.
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

namespace Tuleap\Project\REST;

use PFUser;
use Project;
use Tuleap\REST\JsonCast;

/**
 * @psalm-immutable
 */
class ProjectRepresentation extends MinimalProjectRepresentation
{
    /**
     * @var array {@type \Tuleap\Project\REST\ProjectResourceReference}
     */
    public $resources = [];

    /**
     * @var array
     */
    public $additional_informations = [];
    /**
     * @var bool
     */
    public $is_member_of;

    private function __construct(Project $project, bool $is_member_of, array $resources, array $informations)
    {
        parent::__construct($project);
        $this->is_member_of            = $is_member_of;
        $this->resources               = $resources;
        $this->additional_informations = $informations;
    }


    public static function build(Project $project, PFUser $user, array $resources, array $informations): self
    {
        return new self(
            $project,
            JsonCast::toBoolean(self::isProjectMember($user, $project)),
            $resources,
            $informations
        );
    }

    private static function isProjectMember(PFUser $user, Project $project): bool
    {
        $project_data = $user->getUserGroupData();

        return isset($project_data[$project->getID()]);
    }
}
