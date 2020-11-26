<?php
/**
 * Copyright (c) Enalean, 2020 - Present. All Rights Reserved.
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

declare(strict_types=1);

namespace Tuleap\AgileDashboard\Planning;

use Project;
use Tuleap\Event\Dispatchable;

class ConfigurationCheckDelegation implements Dispatchable
{
    public const NAME = "configurationCheckDelegation";

    /**
     * @var bool
     */
    private $is_planning_available = true;

    /**
     * @var \PFUser
     */
    private $user;
    /**
     * @var Project
     */
    private $project;

    public function __construct(\PFUser $user, Project $project)
    {
        $this->user    = $user;
        $this->project = $project;
    }

    public function disablePlanning(): void
    {
        $this->is_planning_available = false;
    }

    public function isPlanningAvailable(): bool
    {
        return $this->is_planning_available;
    }

    public function getUser(): \PFUser
    {
        return $this->user;
    }

    public function getProject(): Project
    {
        return $this->project;
    }
}
