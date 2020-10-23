<?php
/**
 * Copyright (c) Enalean, 2020 - Present. All Rights Reserved.
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

namespace Tuleap\Tracker\Test\Builders;

use Project;
use Tuleap\Tracker\TrackerColor;

class TrackerTestBuilder
{
    /**
     * @var TrackerColor
     */
    private $color;
    /**
     * @var string
     */
    private $name = 'Irrelevant';
    /**
     * @var Project
     */
    private $project;

    /**
     * @var int
     */
    private $tracker_id = 0;

    public static function aTracker(): self
    {
        return new self();
    }

    public function withId(int $id): self
    {
        $this->tracker_id = $id;

        return $this;
    }

    public function withProject(Project $project): self
    {
        $this->project = $project;

        return $this;
    }

    public function withName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function withColor(TrackerColor $color): self
    {
        $this->color = $color;

        return $this;
    }

    private function getProjectId(): int
    {
        if (! $this->project) {
            return 0;
        }

        return (int) $this->project->getId();
    }

    private function getColor(): TrackerColor
    {
        if (! $this->color) {
            return TrackerColor::default();
        }

        return $this->color;
    }

    public function build(): \Tracker
    {
        $tracker = new \Tracker(
            $this->tracker_id,
            $this->getProjectId(),
            $this->name,
            'Irrelevant',
            'irrelevant',
            false,
            null,
            null,
            null,
            null,
            true,
            false,
            \Tracker::NOTIFICATIONS_LEVEL_DEFAULT,
            $this->getColor(),
            false
        );

        if ($this->project) {
            $tracker->setProject($this->project);
        }

        return $tracker;
    }
}
