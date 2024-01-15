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
use Tracker;
use Tuleap\Tracker\TrackerColor;

final class TrackerTestBuilder
{
    private ?TrackerColor $color = null;
    private string $name         = 'Irrelevant';
    private string $short_name   = 'irrelevant';
    private ?Project $project    = null;
    private int $tracker_id      = 0;
    private ?int $deletion_date  = null;
    private ?\Workflow $workflow = null;

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
        $this->name       = $name;
        $this->short_name = strtolower($name);

        return $this;
    }

    public function withShortName(string $name): self
    {
        $this->short_name = $name;

        return $this;
    }

    public function withDeletionDate(int $deletion_date): self
    {
        $this->deletion_date = $deletion_date;

        return $this;
    }

    public function withColor(TrackerColor $color): self
    {
        $this->color = $color;

        return $this;
    }

    public function withWorkflow(\Workflow $workflow): self
    {
        $this->workflow = $workflow;

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
            $this->short_name,
            false,
            null,
            null,
            null,
            $this->deletion_date,
            true,
            false,
            \Tracker::NOTIFICATIONS_LEVEL_DEFAULT,
            $this->getColor(),
            false
        );

        if ($this->project) {
            $tracker->setProject($this->project);
        }

        if ($this->workflow) {
            $tracker->setWorkflow($this->workflow);
        }

        $tracker->setParent(Tracker::NO_PARENT);

        return $tracker;
    }
}
