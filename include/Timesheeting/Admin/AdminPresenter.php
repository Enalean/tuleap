<?php
/**
 * Copyright (c) Enalean, 2017. All Rights Reserved.
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

namespace Tuleap\Timesheeting\Admin;

use CSRFSynchronizerToken;
use Tracker;

class AdminPresenter
{
    /**
     * @var string
     */
    public $enable_title;

    /**
     * @var string
     */
    public $form_url;

    /**
     * @var boolean
     */
    public $is_timesheeting_enabled;

    /**
     * @var string
     */
    public $submit_label;

    public function __construct(Tracker $tracker, CSRFSynchronizerToken $csrf_token, $is_timesheeting_enabled)
    {
        $this->enable_title            = dgettext('tuleap-timesheeting', 'Enable timesheeting for tracker');
        $this->is_timesheeting_enabled = $is_timesheeting_enabled;
        $this->submit_label            = dgettext('tuleap-timesheeting', 'Submit');
        $this->form_url                = TIMESHEETING_BASE_URL . '/?' . http_build_query(array(
                'tracker' => $tracker->getId(),
                'action'  => 'edit-timesheeting'
        ));

        $this->csrf_token = $csrf_token;

    }
}