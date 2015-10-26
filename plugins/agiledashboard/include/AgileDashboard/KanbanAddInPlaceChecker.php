<?php
/**
 * Copyright (c) Enalean, 2015. All Rights Reserved.
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

class AgileDashboard_KanbanAddInPlaceChecker {

    /**
     * @var Tracker_FormElementFactory
     */
    private $form_element_factory;

    /**
     * @var Tracker_Factory
     */
    private $tracker_factory;

    public function __construct(TrackerFactory $tracker_factory, Tracker_FormElementFactory $form_element_factory) {
        $this->form_element_factory = $form_element_factory;
        $this->tracker_factory      = $tracker_factory;
    }

    public function canUserAddInPlace(PFUser $user, AgileDashboard_Kanban $kanban) {
        $tracker = $this->getTrackerForKanban($kanban);
        if (! $tracker) {
            return false;
        }

        $semantic_title = $this->getSemanticTitle($tracker);
        if (! $semantic_title) {
            return false;
        }

        return $tracker->userCanSubmitArtifact($user) && $this->trackerHasOnlyTitleRequired($tracker, $semantic_title);
    }

    private function getSemanticTitle(Tracker $tracker) {
        $semantic = Tracker_Semantic_Title::load($tracker);
        if (! $semantic->getFieldId()) {
            return;
        }

        return $semantic;
    }

    private function getTrackerForKanban(AgileDashboard_Kanban $kanban) {
        return $this->tracker_factory->getTrackerById($kanban->getTrackerId());
    }

    private function trackerHasOnlyTitleRequired(Tracker $tracker, Tracker_Semantic_Title $semantic_title) {
        $used_fields = $this->form_element_factory->getUsedFields($tracker);

        foreach($used_fields as $used_field) {
            if ($used_field->isRequired() && $used_field->getId() != $semantic_title->getFieldId()) {
                return false;
            }
        }

        return true;
    }
}