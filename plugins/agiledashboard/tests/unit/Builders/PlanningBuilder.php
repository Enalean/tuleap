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

namespace Tuleap\AgileDashboard\Test\Builders;

use Tuleap\Option\Option;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;

final class PlanningBuilder
{
    private int $id               = 34;
    private string $name          = 'Release Planning';
    private string $backlog_title = 'Product Backlog';
    private string $plan_title    = 'Release Plan';

    /**
     * @param Option<\Tracker> $milestone_tracker
     * @param \Tracker[]       $backlog_trackers
     */
    private function __construct(
        private int $project_id,
        private Option $milestone_tracker,
        private array $backlog_trackers,
    ) {
    }

    public static function aPlanning(int $project_id): self
    {
        $user_stories_tracker = TrackerTestBuilder::aTracker()
            ->withShortName('story')
            ->withId(30)
            ->build();
        $release_tracker      = TrackerTestBuilder::aTracker()
            ->withShortName('release')
            ->withId(21)
            ->build();

        return new self($project_id, Option::fromValue($release_tracker), [$user_stories_tracker]);
    }

    public function withMilestoneTracker(\Tracker $tracker): self
    {
        $this->milestone_tracker = Option::fromValue($tracker);
        return $this;
    }

    public function withBadConfigurationAndNoMilestoneTracker(): self
    {
        $this->milestone_tracker = Option::nothing(\Tracker::class);
        return $this;
    }

    /**
     * @no-named-arguments
     */
    public function withBacklogTrackers(\Tracker $first_backlog_tracker, \Tracker ...$other_backlog_trackers): self
    {
        $this->backlog_trackers = [$first_backlog_tracker, ...$other_backlog_trackers];
        return $this;
    }

    public function withId(int $id): self
    {
        $this->id = $id;
        return $this;
    }

    public function withName(string $name): self
    {
        $this->name = $name;
        return $this;
    }

    public function withBacklogTitle(string $backlog_title): self
    {
        $this->backlog_title = $backlog_title;
        return $this;
    }

    public function withPlanTitle(string $plan_title): self
    {
        $this->plan_title = $plan_title;
        return $this;
    }

    public function build(): \Planning
    {
        $planning = new \Planning(
            $this->id,
            $this->name,
            $this->project_id,
            $this->backlog_title,
            $this->plan_title,
            [],
            0
        );
        $this->milestone_tracker->apply($planning->setPlanningTracker(...));
        $planning->setBacklogTrackers($this->backlog_trackers);
        return $planning;
    }
}
