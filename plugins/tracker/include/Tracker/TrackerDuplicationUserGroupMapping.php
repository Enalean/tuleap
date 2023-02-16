<?php
/*
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
 *
 */

declare(strict_types=1);

namespace Tuleap\Tracker;

use ProjectManager;
use Tracker_PermissionsDao;
use Tracker_UgroupMappingBuilder;
use Tracker_UgroupPermissionsGoldenRetriever;
use UGroupManager;

final class TrackerDuplicationUserGroupMapping
{
    /**
     * @param array<int, int> $ugroup_mapping
     */
    private function __construct(public readonly TrackerDuplicationType $duplication_type, public readonly array $ugroup_mapping)
    {
    }

    public static function fromMapping(array|false $ugroup_mapping, \Tracker $template_tracker, int $project_id): self
    {
        if ($ugroup_mapping) {
            return new self(TrackerDuplicationType::DUPLICATE_NEW_PROJECT, $ugroup_mapping);
        }

        if ($project_id === (int) $template_tracker->getProject()->getID()) {
            return new self(TrackerDuplicationType::DUPLICATE_SAME_PROJECT, []);
        }

        $ugroup_manager = new UGroupManager();
        $builder        = new Tracker_UgroupMappingBuilder(
            new Tracker_UgroupPermissionsGoldenRetriever(new Tracker_PermissionsDao(), $ugroup_manager),
            $ugroup_manager
        );
        $ugroup_mapping = $builder->getMapping(
            $template_tracker,
            ProjectManager::instance()->getProject($project_id),
        );
        return new self(TrackerDuplicationType::DUPLICATE_OTHER_PROJECT, $ugroup_mapping);
    }

    public static function fromAnotherProjectWithoutMapping(): self
    {
        return new self(TrackerDuplicationType::DUPLICATE_OTHER_PROJECT, []);
    }

    public static function fromSameProjectWithoutMapping(): self
    {
        return new self(TrackerDuplicationType::DUPLICATE_SAME_PROJECT, []);
    }

    /**
     * @param array<int, int> $mapping
     */
    public static function fromSameProjectWithMapping(array $mapping): self
    {
        return new self(TrackerDuplicationType::DUPLICATE_SAME_PROJECT, $mapping);
    }

    /**
     * @param array<int, int> $mapping
     */
    public static function fromNewProjectWithMapping(array $mapping): self
    {
        return new self(TrackerDuplicationType::DUPLICATE_NEW_PROJECT, $mapping);
    }
}
