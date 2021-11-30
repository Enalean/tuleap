<?php
/**
 * Copyright (c) Enalean, 2021 - present. All Rights Reserved.
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

namespace Tuleap\Roadmap\REST\v1;

use DateTimeImmutable;
use Tuleap\Tracker\Artifact\Artifact;

class RoadmapTasksOutOfDateFilter
{
    /**
     * @var IDetectIfArtifactIsOutOfDate
     */
    private $out_of_date_detector;

    public function __construct(
        IDetectIfArtifactIsOutOfDate $out_of_date_detector,
    ) {
        $this->out_of_date_detector = $out_of_date_detector;
    }

    /**
     * @param Artifact[] $artifacts
     * @return Artifact[]
     */
    public function filterOutOfDateArtifacts(
        array $artifacts,
        DateTimeImmutable $now,
        \PFUser $user,
        TrackersWithUnreadableStatusCollection $trackers_with_unreadable_status_collection,
    ): array {
        return array_filter(
            $artifacts,
            fn(Artifact $artifact): bool => ! $this->out_of_date_detector->isArtifactOutOfDate($artifact, $now, $user, $trackers_with_unreadable_status_collection)
        );
    }
}
