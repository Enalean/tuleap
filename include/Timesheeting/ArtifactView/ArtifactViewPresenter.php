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

namespace Tuleap\Timesheeting\ArtifactView;

use CSRFSynchronizerToken;
use Tracker_Artifact;

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
     * @var boolean
     */
    public $user_can_add_time;

    /**
     * @var array
     */
    public $times;

    /**
     * @var boolean
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

    public function __construct(
        Tracker_Artifact $artifact,
        CSRFSynchronizerToken $csrf,
        array $times,
        $formatted_total_time,
        $user_can_add_time
    ) {
        $this->add_url = TIMESHEETING_BASE_URL . '/?' . http_build_query(array(
            'artifact' => $artifact->getId(),
            'action'   => 'add-time'
        ));

        $this->base_delete_url = TIMESHEETING_BASE_URL . '/?' . http_build_query(array(
            'artifact' => $artifact->getId(),
            'action'   => 'delete-time'
        ));

        $this->csrf_token        = $csrf;
        $this->user_can_add_time = $user_can_add_time;
        $this->times             = $times;
        $this->has_times         = count($times) > 0;
        $this->total_time        = $formatted_total_time;

        $request_time  = $_SERVER['REQUEST_TIME'];
        $default_value = date('Y-m-d', $request_time);
        $this->purified_date_picker = $GLOBALS['HTML']->getBootstrapDatePicker(
            "timesheeting-new-time-date",
            "timesheeting-new-time-date",
            $default_value,
            array(),
            array(),
            false
        );
    }
}
