<?php
/**
 * Copyright (c) Enalean, 2023-Present. All Rights Reserved.
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

namespace Tuleap\AgileDashboard\ServiceAdministration;

/**
 * @psalm-immutable
 */
final class RedirectURI implements \Stringable
{
    private function __construct(private readonly string $uri)
    {
    }

    public static function buildScrumAdministration(\Project $project): self
    {
        $uri = '/plugins/agiledashboard/?' . http_build_query([
            'group_id' => $project->getID(),
            'action'   => 'admin',
            'pane'     => 'scrum',
        ]);
        return new self($uri);
    }

    public static function buildLegacyKanbanAdministration(\Project $project): self
    {
        $uri = '/plugins/agiledashboard/?' . http_build_query([
            'group_id' => $project->getID(),
            'action'   => 'admin',
            'pane'     => 'kanban',
        ]);
        return new self($uri);
    }

    public static function buildLegacyAgileDashboardHomepage(\Project $project): self
    {
        $uri = '/plugins/agiledashboard/?' . http_build_query(['group_id' => $project->getID()]);
        return new self($uri);
    }

    public static function buildProjectBacklog(\Project $project): self
    {
        $uri = '/plugins/agiledashboard/?' . http_build_query([
            'group_id' => $project->getID(),
            'action'   => 'show-top',
            'pane'     => 'topplanning-v2',
        ]);
        return new self($uri);
    }

    public function __toString(): string
    {
        return $this->uri;
    }
}
