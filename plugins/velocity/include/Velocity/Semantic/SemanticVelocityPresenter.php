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

use Tracker;
use Tracker_FormElement_Field;

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
     * @var bool
     */
    public $has_backlog_trackers_without_done_semantic;
    /**
     * @var string
     */
    public $tracker_name;
    /**
     * @var bool
     */
    public $are_all_backlog_trackers_missconfigured;
    /**
     * @var array
     */
    public $backlog_trackers;
    /**
     * @var int
     */
    public $nb_semantic_misconfigured;
    /**
     * @var array
     */
    public $misconfigured_semantics;
    /**
     * @var bool
     */
    public $can_status_semantic_have_multiple_values;
    /**
     * @var Tracker[]
     */
    public $children_trackers_without_velocity_semantic;
    /**
     * @var bool
     */
    public $has_children_trackers_without_velocity_semantic;
    /**
     * @var int
     */
    public $nb_children_trackers_without_velocity_semantic;

    public function __construct(
        $semantic_done_is_defined,
        array $incorrect_backlog_trackers,
        array $backlog_trackers,
        Tracker $tracker,
        array $misconfigured_semantics_for_all_trackers,
        $are_all_backlog_trackers_missconfigured,
        $can_status_semantic_have_multiple_values,
        array $children_trackers_without_velocity_semantic,
        Tracker_FormElement_Field $velocity_field = null
    ) {
        $this->semantic_done_is_defined                   = $semantic_done_is_defined;
        $this->velocity_field                             = $velocity_field;
        $this->velocity_field_label                       = ($velocity_field !== null) ? $velocity_field->getLabel(
        ) : "";
        $this->backlog_trackers                           = $backlog_trackers;
        $this->has_backlog_trackers_without_done_semantic = count($incorrect_backlog_trackers) > 0;
        $this->tracker_name                               = $tracker->getName();

        $this->nb_semantic_misconfigured                       = count($misconfigured_semantics_for_all_trackers);
        $this->are_all_backlog_trackers_missconfigured         = $are_all_backlog_trackers_missconfigured;
        $this->misconfigured_semantics                         = $misconfigured_semantics_for_all_trackers;
        $this->are_all_backlog_trackers_missconfigured         = $are_all_backlog_trackers_missconfigured;
        $this->can_status_semantic_have_multiple_values        = $can_status_semantic_have_multiple_values;
        $this->children_trackers_without_velocity_semantic     = $children_trackers_without_velocity_semantic;
        $this->has_children_trackers_without_velocity_semantic = count($children_trackers_without_velocity_semantic) > 0;
        $this->nb_children_trackers_without_velocity_semantic  = count($children_trackers_without_velocity_semantic);
    }
}
