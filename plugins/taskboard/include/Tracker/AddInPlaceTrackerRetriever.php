<?php
/**
 * Copyright (c) Enalean, 2019 - present. All Rights Reserved.
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

declare(strict_types = 1);

namespace Tuleap\Taskboard\Tracker;

class AddInPlaceTrackerRetriever
{
    /**
     * @var \Tracker_FormElementFactory
     */
    private $form_element_factory;

    public function __construct(\Tracker_FormElementFactory $form_element_factory)
    {
        $this->form_element_factory = $form_element_factory;
    }

    public function retrieveAddInPlaceTracker(TaskboardTracker $taskboard_tracker, \PFUser $user): ?\Tracker
    {
        $tracker        = $taskboard_tracker->getTracker();
        $child_trackers = $tracker->getChildren();

        if (count($child_trackers) !== 1) {
            return null;
        }

        $child_tracker = $child_trackers[0];
        $field_title   = \Tracker_Semantic_Title::load($child_tracker)->getField();

        if (! $field_title || ! $field_title->userCanSubmit($user)) {
            return null;
        }

        if (! $this->isOnlyTitleRequired($child_tracker, $field_title)) {
            return null;
        }

        return $child_tracker;
    }

    private function isOnlyTitleRequired(\Tracker $tracker, \Tracker_FormElement_Field $field_title) : bool
    {
        $title_field_id = $field_title->getId();
        $tracker_fields = $this->form_element_factory->getUsedFields($tracker);

        /** @var \Tracker_FormElement_Field $field */
        foreach ($tracker_fields as $field) {
            if ($field->isRequired() && $field->getId() !== $title_field_id) {
                return false;
            }
        }

        return true;
    }
}
