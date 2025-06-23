<?php
/*
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
 *
 */

declare(strict_types=1);

namespace Tuleap\Tracker;

use Tracker_UgroupMappingBuilder;
use Tuleap\Project\Duplication\DuplicationType;

/**
 * @psalm-immutable
 */
final readonly class TrackerDuplicationUserGroupMapping
{
    /**
     * @param array<int, int> $ugroup_mapping
     */
    private function __construct(public DuplicationType $duplication_type, public array $ugroup_mapping)
    {
    }

    public static function fromMapping(Tracker_UgroupMappingBuilder $builder, array|false $ugroup_mapping, \Tuleap\Tracker\Tracker $template_tracker, \Project $project): self
    {
        if ($ugroup_mapping) {
            return new self(DuplicationType::DUPLICATE_NEW_PROJECT, $ugroup_mapping);
        }

        if ((int) $project->getID() === (int) $template_tracker->getProject()->getID()) {
            return new self(DuplicationType::DUPLICATE_SAME_PROJECT, []);
        }

        $ugroup_mapping = $builder->getMapping(
            $template_tracker,
            $project
        );
        return new self(DuplicationType::DUPLICATE_OTHER_PROJECT, $ugroup_mapping);
    }
}
