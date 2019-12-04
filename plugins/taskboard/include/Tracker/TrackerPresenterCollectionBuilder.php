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
 */

declare(strict_types=1);

namespace Tuleap\Taskboard\Tracker;

use PFUser;
use Planning_Milestone;
use Tuleap\Taskboard\Column\FieldValuesToColumnMapping\MappedFieldRetriever;

class TrackerPresenterCollectionBuilder
{
    /** @var TrackerCollectionRetriever */
    private $trackers_retriever;
    /** @var MappedFieldRetriever */
    private $mapped_field_retriever;
    /** @var AddInPlaceTrackerRetriever*/
    private $add_in_place_tracker_retriever;

    public function __construct(
        TrackerCollectionRetriever $trackers_retriever,
        MappedFieldRetriever $mapped_field_retriever,
        AddInPlaceTrackerRetriever $add_in_place_tracker_retriever
    ) {
        $this->trackers_retriever             = $trackers_retriever;
        $this->mapped_field_retriever         = $mapped_field_retriever;
        $this->add_in_place_tracker_retriever = $add_in_place_tracker_retriever;
    }

    public static function build(): self
    {
        return new self(
            TrackerCollectionRetriever::build(),
            MappedFieldRetriever::build(),
            new AddInPlaceTrackerRetriever(
                \Tracker_FormElementFactory::instance()
            )
        );
    }

    /**
     * @return TrackerPresenter[]
     */
    public function buildCollection(Planning_Milestone $milestone, PFUser $user): array
    {
        return $this->trackers_retriever->getTrackersForMilestone($milestone)->map(
            function (TaskboardTracker $taskboard_tracker) use ($user) {
                $mapped_field         = $this->mapped_field_retriever->getField($taskboard_tracker);
                $title_field          = $this->getTitleField($taskboard_tracker, $user);
                $add_in_place_tracker = $this->add_in_place_tracker_retriever->retrieveAddInPlaceTracker(
                    $taskboard_tracker,
                    $user
                );

                if (! $mapped_field) {
                    return new TrackerPresenter($taskboard_tracker, false, $title_field, $add_in_place_tracker);
                }
                return new TrackerPresenter($taskboard_tracker, $mapped_field->userCanUpdate($user), $title_field, $add_in_place_tracker);
            }
        );
    }

    private function getTitleField(TaskboardTracker $taskboard_tracker, \PFUser $user): ?TitleFieldPresenter
    {
        $field_title = \Tracker_Semantic_Title::load($taskboard_tracker->getTracker())->getField();

        return ($field_title !== null && $field_title->userCanUpdate($user))
            ? new TitleFieldPresenter($field_title)
            : null;
    }
}
