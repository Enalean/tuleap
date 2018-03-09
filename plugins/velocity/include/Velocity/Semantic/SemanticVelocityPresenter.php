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
     * @var array
     */
    public $backlog_trackers_without_done_semantic;
    /**
     * @var bool
     */
    public $has_backlog_trackers_without_done_semantic;

    public function __construct(
        $semantic_done_is_defined,
        array $incorrect_backlog_trackers,
        Tracker_FormElement_Field $velocity_field = null
    ) {
        $this->semantic_done_is_defined                   = $semantic_done_is_defined;
        $this->velocity_field                             = $velocity_field;
        $this->velocity_field_label                       = ($velocity_field !== null) ? $velocity_field->getLabel() : "";
        $this->backlog_trackers_without_done_semantic     = $incorrect_backlog_trackers;
        $this->has_backlog_trackers_without_done_semantic = count($incorrect_backlog_trackers) > 0;
    }
}
