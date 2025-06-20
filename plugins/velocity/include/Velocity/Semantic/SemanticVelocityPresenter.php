<?php
/**
 * Copyright (c) Enalean, 2018 - Present. All Rights Reserved.
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

use Tracker_FormElement_Field;
use Tuleap\Tracker\Tracker;

class SemanticVelocityPresenter
{
    /**
     * @var bool
     */
    public $semantic_done_is_defined;
    /**
     * @var Tracker_FormElement_Field
     */
    public $velocity_field;
    /**
     * @var string
     */
    public $velocity_field_label;
    /**
     * @var string
     */
    public $tracker_name;
    /**
     * @var array
     */
    public $backlog_trackers;
    /**
     * @var bool
     */
    public $can_status_semantic_have_multiple_values;
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

    public function __construct(
        $semantic_done_is_defined,
        Tracker $tracker,
        $can_status_semantic_have_multiple_values,
        BacklogRequiredTrackerCollection $backlog_required_tracker_collection,
        ChildrenRequiredTrackerCollection $children_required_tracker_collection,
        $has_at_least_one_tracker_correctly_configured,
        ?Tracker_FormElement_Field $velocity_field = null,
    ) {
        $this->semantic_done_is_defined = $semantic_done_is_defined;
        $this->velocity_field           = $velocity_field;
        $this->velocity_field_label     = ($velocity_field !== null) ? $velocity_field->getLabel() : '';
        $this->tracker_name             = $tracker->getName();

        $this->can_status_semantic_have_multiple_values = $can_status_semantic_have_multiple_values;

        $this->children_misconfigured_semantic           = $children_required_tracker_collection->getChildrenMisconfiguredTrackers();
        $this->has_at_least_one_well_configured_children = $children_required_tracker_collection->hasAtLeastOneChildrenWithVelocitySemanticForBacklogTrackers();
        $this->nb_children_without_velocity_semantic     = $children_required_tracker_collection->getNbTrackersWithoutVelocitySemantic();

        $this->backlog_trackers                       = $backlog_required_tracker_collection->getBacklogRequiredTrackers();
        $this->backlog_misconfigured_semantics        = $backlog_required_tracker_collection->getMisconfiguredBacklogTrackers();
        $this->are_all_backlog_trackers_misconfigured = $backlog_required_tracker_collection->areAllBacklogTrackersMisconfigured();

        $this->has_at_least_one_tracker_correctly_configured = $has_at_least_one_tracker_correctly_configured;
        $this->nb_semantic_misconfigured                     = $children_required_tracker_collection->getNbTrackersWithoutVelocitySemantic()
            + $backlog_required_tracker_collection->getNbMisconfiguredTrackers();
        $this->semantics_not_correctly_set                   = $this->getMisconfiguredSemantics($backlog_required_tracker_collection);
    }

    private function getMisconfiguredSemantics(BacklogRequiredTrackerCollection $backlog_required_tracker_collection)
    {
        $misconfigured_semantics = $backlog_required_tracker_collection->getSemanticMisconfiguredForAllTrackers();

        if (! $this->has_at_least_one_well_configured_children) {
            $misconfigured_semantics[] =  dgettext('tuleap-velocity', 'Velocity');
        }

        return $misconfigured_semantics;
    }
}
