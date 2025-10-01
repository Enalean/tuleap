<?php
/*
 * Copyright (c) Enalean, 2025 - Present. All Rights Reserved.
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

namespace Tuleap\CrossTracker\Widget;

/**
 * @psalm-immutable
 */
final readonly class ProjectCrossTrackerWidget
{
    private function __construct(
        private int $dashboard_id,
        private string $dashboard_type,
        private int $project_id,
    ) {
    }

    public static function build(int $dashboard_id, string $dashboard_type, int $project_id): self
    {
        return new self(
            $dashboard_id,
            $dashboard_type,
            $project_id
        );
    }

    public function getProjectId(): int
    {
        return $this->project_id;
    }

    public function getDashboardType(): string
    {
        return $this->dashboard_type;
    }

    public function getDashboardId(): int
    {
        return $this->dashboard_id;
    }
}
