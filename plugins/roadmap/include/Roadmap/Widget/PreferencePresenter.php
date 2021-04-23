<?php
/**
 * Copyright (c) Enalean, 2021 - Present. All Rights Reserved.
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

namespace Tuleap\Roadmap\Widget;

use Tracker;

class PreferencePresenter
{
    /**
     * @var string
     */
    public $widget_id;
    /**
     * @var string
     */
    public $title;
    /**
     * @var TrackerPresenter[]
     */
    public $project_trackers;
    /**
     * @var bool
     */
    public $is_in_creation;

    public function __construct(
        string $widget_id,
        string $title,
        ?int $selected_tracker_id,
        array $trackers
    ) {
        $this->widget_id        = $widget_id;
        $this->title            = $title;
        $this->is_in_creation   = $selected_tracker_id === null;
        $this->project_trackers = $this->buildTrackerPresenters($trackers, $selected_tracker_id);
    }

    /**
     * @param Tracker[] $trackers
     * @return TrackerPresenter[]
     */
    private function buildTrackerPresenters(array $trackers, ?int $selected_tracker_id): array
    {
        $presenters = [];
        foreach ($trackers as $tracker) {
            if ($tracker->isDeleted()) {
                continue;
            }

            $presenters[] = TrackerPresenter::buildFromPreferencePresenter(
                $tracker,
                $selected_tracker_id
            );
        }

        return $presenters;
    }
}
