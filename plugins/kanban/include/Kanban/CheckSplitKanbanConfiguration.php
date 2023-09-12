<?php
/**
 * Copyright (c) Enalean, 2023 - Present. All Rights Reserved.
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

namespace Tuleap\Kanban;

use Psr\EventDispatcher\EventDispatcherInterface;

final class CheckSplitKanbanConfiguration implements SplitKanbanConfigurationChecker
{
    public function __construct(private readonly EventDispatcherInterface $dispatcher)
    {
    }

    public function isProjectAllowedToUseSplitKanban(\Project $project): bool
    {
        if ($this->dispatcher->dispatch(new ForceUsageOfSplitKanbanEvent($project))->isSplitKanbanMandatoryForProject()) {
            return true;
        }

        $list_of_project_ids_without_split_kanban = \ForgeConfig::getFeatureFlagArrayOfInt(SplitKanbanConfiguration::FEATURE_FLAG);

        if (! $list_of_project_ids_without_split_kanban) {
            return true;
        }

        if ($list_of_project_ids_without_split_kanban === [1]) {
            return false;
        }

        $is_deactivated = in_array((int) $project->getID(), $list_of_project_ids_without_split_kanban, true);

        return ! $is_deactivated;
    }
}
