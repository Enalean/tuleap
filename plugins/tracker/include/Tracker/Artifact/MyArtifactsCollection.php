<?php
/**
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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

namespace Tuleap\Tracker\Artifact;

class MyArtifactsCollection implements \Countable
{
    /**
     * @var array<int, \Tracker>
     */
    private $trackers = [];
    private $trackers_has_title = [];
    private $artifacts = [];

    public function hasTracker(int $tracker_id): bool
    {
        return isset($this->trackers[$tracker_id]);
    }

    public function addTracker(int $tracker_id, \Tracker $tracker, bool $with_title): void
    {
        $this->trackers[$tracker_id] = $tracker;
        $this->trackers_has_title[$tracker_id] = $with_title;
    }

    public function trackerHasArtifact(int $tracker_id, int $artifact_id): bool
    {
        return isset($this->artifacts[$tracker_id][$artifact_id]);
    }

    public function trackerHasTitle(int $tracker_id): bool
    {
        return isset($this->trackers_has_title[$tracker_id]);
    }

    public function addArtifactForTracker(int $tracker_id, int $artifact_id, \Tracker_Artifact $artifact): void
    {
        $this->artifacts[$tracker_id][$artifact_id] = $artifact;
    }

    public function count(): int
    {
        return count($this->artifacts);
    }

    /**
     * @return \Tracker[]
     */
    public function getTrackers(): array
    {
        return $this->trackers;
    }

    public function getArtifactsInTrackerCount(\Tracker $tracker): int
    {
        return count($this->artifacts[(int) $tracker->getId()]);
    }

    /**
     * @param \Tracker $tracker
     * @return \Tracker_Artifact[]
     */
    public function getArtifactsInTracker(\Tracker $tracker): array
    {
        return $this->artifacts[(int) $tracker->getId()];
    }

    public function getArtifacts(): \Generator
    {
        foreach ($this->artifacts as $artifacts) {
            foreach ($artifacts as $artifact) {
                yield $artifact;
            }
        }
    }

    public function getArtifactTracker(\Tracker_Artifact $artifact): \Tracker
    {
        return $this->trackers[(int) $artifact->getTrackerId()];
    }
}
