<?php
/**
 * Copyright (c) Enalean, 2018. All Rights Reserved.
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

namespace Tuleap\AgileDashboard\PermissionsPerGroup;

use PFUser;
use PlanningFactory;
use Project;
use ProjectUGroup;
use UGroupManager;

class AgileDashboardPermissionsRepresentationBuilder
{
    /**
     * @var UGroupManager
     */
    private $ugroup_manager;

    /**
     * @var PlanningFactory
     */
    private $planning_factory;
    /**
     * @var PlanningPermissionsRepresentationBuilder
     */
    private $planning_builder;

    public function __construct(
        UGroupManager $ugroup_manager,
        PlanningFactory $planning_factory,
        PlanningPermissionsRepresentationBuilder $planning_builder
    ) {
        $this->ugroup_manager   = $ugroup_manager;
        $this->planning_factory = $planning_factory;
        $this->planning_builder = $planning_builder;
    }

    public function build(
        Project $project,
        PFUser $user,
        $ugroup_id = null
    ) {
        $ugroup = $this->ugroup_manager->getUGroup($project, $ugroup_id);

        return new AgileDashboardPermissionsRepresentation(
            $this->getPlanningPermissionsRepresentation(
                $project,
                $user,
                $ugroup
            )
        );
    }

    private function getPlanningPermissionsRepresentation(
        Project $project,
        PFUser $user,
        ?ProjectUGroup $user_group = null
    ) {
        $plannings      = $this->planning_factory->getPlannings($user, $project->getID());
        $planning_names = [];

        foreach ($plannings as $project_planning) {
            $representation   = $this->planning_builder->build($project, $project_planning, $user_group);
            if ($representation) {
                $planning_names[] = $representation;
            }
        }

        return $planning_names;
    }
}
