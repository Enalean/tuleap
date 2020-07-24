<?php
/**
 * Copyright (c) Enalean, 2019. All Rights Reserved.
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

namespace Tuleap\Statistics\Events;

use Tuleap\Event\Dispatchable;

class StatisticsRefreshDiskUsage implements Dispatchable
{
    public const NAME = "statisticsRefreshDiskUsage";

    /**
     * @var array
     */
    private $services_usage;

    /**
     * @var int
     */
    private $project_id;

    public function __construct(int $project_id)
    {
        $this->services_usage = [];
        $this->project_id     = $project_id;
    }

    public function addUsageForService(string $service_name, int $usage): void
    {
        $this->services_usage[$service_name] = $usage;
    }

    public function getRefreshedUsages(): array
    {
        return $this->services_usage;
    }

    public function getProjectId(): int
    {
        return $this->project_id;
    }
}
