<?php
/**
 * Copyright (c) Enalean, 2018. All Rights Reserved.
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

namespace Tuleap\Velocity\Semantic;

use CSRFSynchronizerToken;
use Tracker;
use Tuleap\AgileDashboard\Semantic\SemanticDone;

class SemanticVelocityAdminPresenter
{
    /**
     * @var array
     */
    public $possible_velocity_field;
    /**
     * @var CSRFSynchronizerToken
     */
    public $csrf_token;
    /**
     * @var bool
     */
    public $has_semantic_done_defined;
    /**
     * @var string
     */
    public $back_url;
    /**
     * @var bool
     */
    public $has_velocity_field;
    /**
     * @var string
     */
    public $tracker_name;
    /**
     * @var Tracker[]
     */
    public $children_misconfigured_semantic;
    /**
     * @var bool
     */
    public $has_at_least_one_well_configured_children;
    /**
     * @var int
     */
    public $nb_children_without_velocity_semantic;
    /**
     * @var Tracker[]
     */
    public $backlog_trackers;
    /**
     * @var Tracker[]
     */
    public $backlog_misconfigured_semantics;
    /**
     * @var bool
     */
    public $are_all_backlog_trackers_misconfigured;
    /**
     * @var bool
     */
    public $has_at_least_one_tracker_correctly_configured;
    /**
     * @var int
     */
    public $nb_semantic_misconfigured;
    /**
     * @var array
     */
    public $semantics_not_correctly_set;
    /**
     * @var bool
     */
    public $has_a_selected_field;

    public $url_done_semantic;

    public function __construct(
        array $possible_velocity_field,
        CSRFSynchronizerToken $csrf_token,
        Tracker $tracker,
        $has_semantic_done_defined,
        $selected_velocity_field_id,
        BacklogRequiredTrackerCollection $backlog_required_tracker_collection,
        ChildrenRequiredTrackerCollection $children_required_tracker_collection,
        $has_at_least_one_tracker_correctly_configured
    ) {
        $this->possible_velocity_field   = $this->buildPossibleVelocityField(
            $possible_velocity_field,
            $selected_velocity_field_id
        );
        $this->has_a_selected_field      = $selected_velocity_field_id != 0;
        $this->csrf_token                = $csrf_token;
        $this->has_semantic_done_defined = $has_semantic_done_defined;
        $this->has_velocity_field        = $selected_velocity_field_id !== null;
        $this->tracker_name              = $tracker->getName();
        $this->back_url                  = TRACKER_BASE_URL . "/?" . http_build_query(
            [
                "tracker" => $tracker->getId(),
                "func"    => "admin-semantic"
            ]
        );
        $this->url_done_semantic         = TRACKER_BASE_URL . '/?' . http_build_query(
            [
                "tracker"  => $tracker->getId(),
                "func"     => "admin-semantic",
                "semantic" => SemanticDone::NAME,
            ]
        );

        $this->children_misconfigured_semantic           = $children_required_tracker_collection->getChildrenMisconfiguredTrackers();
        $this->has_at_least_one_well_configured_children = $children_required_tracker_collection->hasAtLeastOneChildrenWithVelocitySemanticForBacklogTrackers();
        $this->nb_children_without_velocity_semantic     = $children_required_tracker_collection->getNbTrackersWithoutVelocitySemantic();

        $this->backlog_trackers                       = $backlog_required_tracker_collection->getBacklogRequiredTrackers();
        $this->backlog_misconfigured_semantics        = $backlog_required_tracker_collection->getMisconfiguredBacklogTrackers();
        $this->are_all_backlog_trackers_misconfigured = $backlog_required_tracker_collection->areAllBacklogTrackersMisconfigured();

        $this->has_at_least_one_tracker_correctly_configured = $has_at_least_one_tracker_correctly_configured;
        $this->nb_semantic_misconfigured = $children_required_tracker_collection->getNbTrackersWithoutVelocitySemantic()
            + $backlog_required_tracker_collection->getNbMisconfiguredTrackers();
        $this->semantics_not_correctly_set = $this->getMisconfiguredSemantics($backlog_required_tracker_collection, $children_required_tracker_collection);
    }

    private function buildPossibleVelocityField(array $possible_velocity_field, $selected_velocity_field_id)
    {
        $built_field = [];
        foreach ($possible_velocity_field as $field) {
            $built_field[] = [
                "id"          => $field->getId(),
                "name"        => $field->getLabel(),
                "is_selected" => (int) $field->getId() === (int) $selected_velocity_field_id
            ];
        }

        return $built_field;
    }

    private function getMisconfiguredSemantics(BacklogRequiredTrackerCollection $backlog_required_tracker_collection, ChildrenRequiredTrackerCollection $children_required_tracker_collection)
    {
        $misconfigured_semantics = $backlog_required_tracker_collection->getSemanticMisconfiguredForAllTrackers();

        if (! $this->has_at_least_one_well_configured_children) {
            $misconfigured_semantics[] =  dgettext('tuleap-velocity', 'Velocity');
        }

        return $misconfigured_semantics;
    }
}
