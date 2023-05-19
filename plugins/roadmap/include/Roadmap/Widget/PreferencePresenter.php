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

/**
 * @psalm-immutable
 */
class PreferencePresenter
{
    public string $widget_id;
    public string $title;
    public ?string $json_encoded_trackers;
    public ?string $selected_tracker_ids;
    public readonly int|string $selected_filter_report_id;
    public int $selected_lvl1_iteration_tracker_id;
    public int $selected_lvl2_iteration_tracker_id;
    public string $selected_default_timescale;

    /**
     * @param null|int[] $selected_tracker_ids
     */
    public function __construct(
        string $widget_id,
        string $title,
        ?array $selected_tracker_ids,
        ?int $selected_filter_report_id,
        string $selected_default_timescale,
        ?int $selected_lvl1_iteration_tracker_id,
        ?int $selected_lvl2_iteration_tracker_id,
        array $trackers,
    ) {
        $this->widget_id = $widget_id;
        $this->title     = $title;

        $this->json_encoded_trackers = \json_encode($this->buildTrackerPresenters($trackers));

        $this->selected_tracker_ids               = \json_encode($selected_tracker_ids);
        $this->selected_filter_report_id          = $selected_filter_report_id ?: "";
        $this->selected_lvl1_iteration_tracker_id = (int) $selected_lvl1_iteration_tracker_id;
        $this->selected_lvl2_iteration_tracker_id = (int) $selected_lvl2_iteration_tracker_id;
        $this->selected_default_timescale         = $selected_default_timescale;
    }

    /**
     * @param Tracker[] $trackers
     *
     * @return TrackerPresenter[]
     */
    private function buildTrackerPresenters(array $trackers): array
    {
        $presenters = [];
        foreach ($trackers as $tracker) {
            if ($tracker->isDeleted()) {
                continue;
            }

            $presenters[] = new TrackerPresenter($tracker);
        }

        return $presenters;
    }
}
