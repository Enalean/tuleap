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
     * @var array
     */
    public $backlog_trackers_without_done_semantic;
    /**
     * @var bool
     */
    public $has_backlog_trackers_without_done_semantic;

    public function __construct(
        array $possible_velocity_field,
        CSRFSynchronizerToken $csrf_token,
        Tracker $tracker,
        $has_semantic_done_defined,
        $selected_velocity_field_id,
        array $incorrect_backlog_trackers
    ) {
        $this->possible_velocity_field                    = $this->buildPossibleVelocityField(
            $possible_velocity_field,
            $selected_velocity_field_id
        );
        $this->csrf_token                                 = $csrf_token;
        $this->has_semantic_done_defined                  = $has_semantic_done_defined;
        $this->has_velocity_field                         = $selected_velocity_field_id !== null;
        $this->back_url                                   = TRACKER_BASE_URL . "?" . http_build_query(
            [
                "tracker" => $tracker->getId(),
                "func"    => "admin-semantic"
            ]
        );
        $this->backlog_trackers_without_done_semantic     = $incorrect_backlog_trackers;
        $this->has_backlog_trackers_without_done_semantic = count($incorrect_backlog_trackers) > 0;
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
}
