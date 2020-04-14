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

use PFUser;
use Tracker;
use Tracker_Artifact;
use Tracker_Semantic_Title;
use TrackerFactory;

class MyArtifactsCollection implements \Countable
{
    /**
     * @var array<int, \Tracker>
     */
    private $trackers = [];
    private $trackers_has_title = [];
    private $artifacts = [];
    /**
     * @var TrackerFactory
     */
    private $tracker_factory;
    /**
     * @var int
     */
    private $nb_max_artifacts = 0;

    public function __construct(TrackerFactory $tracker_factory)
    {
        $this->tracker_factory = $tracker_factory;
    }

    public function trackerHasArtifactId(Tracker $tracker, int $artifact_id): bool
    {
        return isset($this->artifacts[(int) $tracker->getId()][$artifact_id]);
    }

    public function addArtifactForTracker(Tracker $tracker, Tracker_Artifact $artifact): void
    {
        if ($artifact->userCanView()) {
            $this->artifacts[(int) $tracker->getId()][(int) $artifact->getId()] = $artifact;
        }
    }

    public function count(): int
    {
        return count($this->artifacts);
    }

    /**
     * @return Tracker[]
     */
    public function getTrackers(): array
    {
        return $this->trackers;
    }

    public function getArtifactsInTrackerCount(Tracker $tracker): int
    {
        return count($this->artifacts[(int) $tracker->getId()]);
    }

    /**
     * @return Tracker_Artifact[]
     */
    public function getArtifactsInTracker(Tracker $tracker): array
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

    public function getTrackerById(int $tracker_id): Tracker
    {
        return $this->trackers[$tracker_id];
    }

    public function setTracker(int $tracker_id, PFUser $user): Tracker
    {
        if (! isset($this->trackers[$tracker_id])) {
            $tracker = $this->tracker_factory->getTrackerById($tracker_id);
            if ($tracker === null) {
                throw new \RuntimeException('Tracker does not exist');
            }

            $with_title = false;
            if (($title_field = Tracker_Semantic_Title::load($tracker)->getField()) && $title_field->userCanRead($user)) {
                $with_title = true;
            }

            $this->trackers[(int) $tracker->getId()] = $tracker;
            $this->trackers_has_title[(int) $tracker->getId()] = $with_title;

            return $tracker;
        }
        return $this->trackers[$tracker_id];
    }

    public function getRowAccordingToTrackerPermissions(Tracker $tracker, array $row): array
    {
        if (! $this->trackerHasTitle($tracker)) {
            $row['title'] = '';
        }
        return $row;
    }

    private function trackerHasTitle(Tracker $tracker): bool
    {
        return isset($this->trackers_has_title[(int) $tracker->getId()]);
    }

    public function setTotalNumberOfArtifacts(int $nb_max_artifacts): void
    {
        $this->nb_max_artifacts = $nb_max_artifacts;
    }

    public function getTotalNumberOfArtifacts(): int
    {
        return $this->nb_max_artifacts;
    }
}
