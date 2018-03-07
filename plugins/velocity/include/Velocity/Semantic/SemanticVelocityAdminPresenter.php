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

    public function __construct(
        array $possible_velocity_field,
        CSRFSynchronizerToken $csrf_token,
        Tracker $tracker,
        $has_semantic_done_defined
    ) {
        $this->possible_velocity_field   = $possible_velocity_field;
        $this->csrf_token                = $csrf_token;
        $this->has_semantic_done_defined = $has_semantic_done_defined;
        $this->back_url                  = TRACKER_BASE_URL . "?" . http_build_query(
            [
                "tracker" => $tracker->getId(),
                "func"    => "admin-semantic"
            ]
        );
    }
}
