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

class IndexPresenter {

    /** @var int */
    public $project_id;

    /** @var int */
    public $campaign_tracker_id;

    /** @var int */
    public $test_definition_tracker_id;

    /** @var int */
    public $test_execution_tracker_id;

    /** @var string */
    public $misconfigured_title;

    /** @var string */
    public $misconfigured_message;
    
    /** @var Boolean */
    public $is_properly_configured;

    public function __construct($project_id, $campaign_tracker_id, $test_definition_tracker_id, $test_execution_tracker_id) {
        $this->project_id                 = $project_id;
        $this->campaign_tracker_id        = $campaign_tracker_id;
        $this->test_definition_tracker_id = $test_definition_tracker_id;
        $this->test_execution_tracker_id  = $test_execution_tracker_id;

        $this->misconfigured_title   = $GLOBALS['Language']->getText('plugin_testing', 'misconfigured_title');
        $this->misconfigured_message = $GLOBALS['Language']->getText('plugin_testing', 'misconfigured_message');
        
        $this->is_properly_configured = $this->campaign_tracker_id && $this->test_definition_tracker_id && $this->test_execution_tracker_id;
    }
}