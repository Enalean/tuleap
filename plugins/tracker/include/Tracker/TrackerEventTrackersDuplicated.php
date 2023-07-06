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

namespace Tuleap\Tracker;

use Tuleap\Project\MappingRegistry;

final class TrackerEventTrackersDuplicated implements \Tuleap\Event\Dispatchable
{
    /**
     * @param list<array{from: int, to: int, values: array, workflow: bool}> $field_mapping
     */
    public function __construct(
        public readonly array $tracker_mapping,
        public readonly array $field_mapping,
        public readonly array $report_mapping,
        public readonly int $project_id,
        public readonly array $ugroups_mapping,
        public readonly int $source_project_id,
        public readonly MappingRegistry $mapping_registry,
    ) {
    }
}
