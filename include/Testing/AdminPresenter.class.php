<?php
/**
 * Copyright (c) Enalean, 2014. All Rights Reserved.
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

namespace Tuleap\Testing;

class AdminPresenter {

    /** @var int */
    public $campaign_tracker_id;

    /** @var string */
    public $title;

    /** @var string */
    public $campaigns;

    /** @var string */
    public $submit;

    /** @var string */
    public $placeholder;

    public function __construct($campaign_tracker_id) {
        $this->campaign_tracker_id = $campaign_tracker_id;

        $this->title       = $GLOBALS['Language']->getText('global', 'Administration');
        $this->campaigns   = $GLOBALS['Language']->getText('plugin_testing', 'admin_campaign_tracker');
        $this->submit      = $GLOBALS['Language']->getText('global', 'btn_submit');
        $this->placeholder = $GLOBALS['Language']->getText('plugin_testing', 'admin_campaign_tracker_placeholder');
    }
}