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

namespace Tuleap\Timetracking\Admin;

use CSRFSynchronizerToken;
use Tracker;

class AdminPresenter
{
    /**
     * @var string
     */
    public $form_url;

    /**
     * @var bool
     */
    public $is_timetracking_enabled;

    /**
     * @var array
     */
    public $read_ugroups;

    /**
     * @var array
     */
    public $write_ugroups;

    public function __construct(
        Tracker $tracker,
        CSRFSynchronizerToken $csrf_token,
        $is_timetracking_enabled,
        array $read_ugroups,
        array $write_ugroups
    ) {
        $this->is_timetracking_enabled = $is_timetracking_enabled;
        $this->form_url                = TIMETRACKING_BASE_URL . '/?' . http_build_query(array(
                'tracker' => $tracker->getId(),
                'action'  => 'edit-timetracking'
        ));

        $this->csrf_token = $csrf_token;

        $this->read_ugroups  = $read_ugroups;
        $this->write_ugroups = $write_ugroups;
    }
}
