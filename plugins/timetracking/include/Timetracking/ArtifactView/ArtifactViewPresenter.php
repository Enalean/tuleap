<?php
/**
 * Copyright (c) Enalean, 2017 - 2018. All Rights Reserved.
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

namespace Tuleap\Timetracking\ArtifactView;

use CSRFSynchronizerToken;
use Tuleap\Timetracking\Time\TimeChecker;
use Tuleap\Tracker\Artifact\Artifact;

class ArtifactViewPresenter
{
    /**
     * @var string
     */
    public $add_url;

    /**
     * @var CSRFSynchronizerToken
     */
    public $csrf_token;

    /**
     * @var bool
     */
    public $user_can_add_time;

    /**
     * @var array
     */
    public $times;

    /**
     * @var bool
     */
    public $has_times;

    /**
     * @var string
     */
    public $total_time;

    /**
     * @var string
     */
    public $purified_date_picker;

    /**
     * @var string
     */
    public $base_delete_url;

    /**
     * @var string
     */
    public $default_date_value;

    /**
     * @var string
     */
    public $edit_url;

    /**
     * @var string
     */
    public $pattern;

    public function __construct(
        Artifact $artifact,
        CSRFSynchronizerToken $csrf,
        array $times,
        $formatted_total_time,
        $user_can_add_time
    ) {
        $this->add_url = TIMETRACKING_BASE_URL . '/?' . http_build_query([
            'artifact' => $artifact->getId(),
            'action'   => 'add-time'
        ]);

        $this->base_delete_url = TIMETRACKING_BASE_URL . '/?' . http_build_query([
            'artifact' => $artifact->getId(),
            'action'   => 'delete-time'
        ]);

        $this->edit_url = TIMETRACKING_BASE_URL . '/?' . http_build_query([
            'artifact' => $artifact->getId(),
            'action'   => 'edit-time'
        ]);

        $this->csrf_token        = $csrf;
        $this->user_can_add_time = $user_can_add_time;
        $this->times             = $times;
        $this->has_times         = count($times) > 0;
        $this->total_time        = $formatted_total_time;
        $this->pattern           = TimeChecker::TIME_PATTERN;

        $request_time             = $_SERVER['REQUEST_TIME'];
        $this->default_date_value = date('Y-m-d', $request_time);
    }
}
