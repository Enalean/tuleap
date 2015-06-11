<?php
/**
 * Copyright (c) Enalean, 2014-2015. All Rights Reserved.
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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

namespace Tuleap\AgileDashboard\REST\v1\Kanban;

use AgileDashboard_Kanban;
use AgileDashboard_KanbanColumnFactory;
use PFUser;
use Tracker;
use TrackerFactory;
use Tracker_FormElementFactory;
use Tracker_Semantic_Title;
use AgileDashboard_KanbanUserPreferences;

class KanbanRepresentationBuilder {

    /**
     * @var AgileDashboard_KanbanUserPreferences
     */
    private $user_preferences;

    /**
     * @var Tracker_FormElementFactory
     */
    private $form_element_factory;

    /**
     * @var Tracker_Factory
     */
    private $tracker_factory;

    /**
     * @var AgileDashboard_KankanColumnFactory
     */
    private $kanban_column_factory;

    public function __construct(
        AgileDashboard_KanbanUserPreferences $user_preferences,
        AgileDashboard_KanbanColumnFactory $kanban_column_factory,
        TrackerFactory $tracker_factory,
        Tracker_FormElementFactory $form_element_factory
    ) {
        $this->kanban_column_factory = $kanban_column_factory;
        $this->form_element_factory  = $form_element_factory;
        $this->tracker_factory       = $tracker_factory;
        $this->user_preferences      = $user_preferences;
    }

    /**
     * @return Tuleap\AgileDashboard\REST\v1\Kanban\KanbanRepresentation
     */
    public function build(AgileDashboard_Kanban $kanban, PFUser $user) {
        $user_can_add_in_place = $this->canUserAddInPlace($user, $kanban);

        $kanban_representation = new KanbanRepresentation();
        $kanban_representation->build(
            $kanban,
            $this->kanban_column_factory,
            $this->user_preferences,
            $user_can_add_in_place,
            $user
        );

        return $kanban_representation;
    }

    private function canUserAddInPlace(PFUser $user, AgileDashboard_Kanban $kanban) {
        $tracker = $this->getTrackerForKanban($kanban);
        if (! $tracker) {
            return;
        }

        $semantic_title = $this->getSemanticTitle($tracker);
        if (! $semantic_title) {
            return;
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
