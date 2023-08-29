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

namespace Tuleap\AgileDashboard;

use Project;
use Tuleap\Kanban\CheckSplitKanbanConfiguration;
use Tuleap\Kanban\SplitKanbanConfigurationChecker;

final class AgileDashboardServiceHomepageUrlBuilder
{
    private function __construct(private readonly SplitKanbanConfigurationChecker $split_kanban_configuration_checker)
    {
    }

    public static function buildSelf(): self
    {
        return self::buildWithSplitKanbanConfigurationChecker(new CheckSplitKanbanConfiguration());
    }

    public static function buildWithSplitKanbanConfigurationChecker(SplitKanbanConfigurationChecker $split_kanban_configuration_checker): self
    {
        return new self($split_kanban_configuration_checker);
    }

    public function getUrl(Project $project): string
    {
        if ($this->split_kanban_configuration_checker->isProjectAllowedToUseSplitKanban($project)) {
            return self::getTopBacklogUrl($project);
        }

        return '/plugins/agiledashboard/?' . http_build_query([
            'group_id' => $project->getID(),
        ]);
    }

    public static function getTopBacklogUrl(Project $project): string
    {
        return '/plugins/agiledashboard/?' . http_build_query([
            'group_id' => $project->getID(),
            'action'   => 'show-top',
            'pane'     => 'topplanning-v2',
        ]);
    }
}
