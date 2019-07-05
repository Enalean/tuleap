<?php
/**
 * Copyright (c) Enalean, 2018-Present. All Rights Reserved.
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

namespace Tuleap\Project\Admin\ProjectUGroup;

use Tuleap\Event\Dispatchable;

class ApproveProjectAdministratorRemoval implements Dispatchable
{
    public const NAME = 'approveProjectAdministratorRemoval';

    /**
     * @var \Project
     */
    private $project;
    /**
     * @var \PFUser
     */
    private $project_admin_to_remove;

    public function __construct(\Project $project, \PFUser $project_admin_to_remove)
    {
        $this->project                   = $project;
        $this->project_admin_to_remove = $project_admin_to_remove;
    }

    /**
     * @return \Project
     */
    public function getProject()
    {
        return $this->project;
    }

    /**
     * @return \PFUser
     */
    public function getUserToRemove()
    {
        return $this->project_admin_to_remove;
    }
}
