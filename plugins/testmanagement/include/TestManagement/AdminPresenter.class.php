<?php
/**
 * Copyright (c) Enalean, 2014-2018. All Rights Reserved.
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

namespace Tuleap\TestManagement;

use CSRFSynchronizerToken;

class AdminPresenter
{

    /** @var int */
    public $campaign_tracker_id;

    /** @var int */
    public $test_definition_tracker_id;

    /** @var int */
    public $test_execution_tracker_id;

    /** @var int | null */
    public $issue_tracker_id;

    /** @var string */
    public $title;

    /** @var string */
    public $campaigns;

    /** @var string */
    public $definitions;

    /** @var string */
    public $executions;

    /** @var string */
    public $issues;

    /** @var string */
    public $submit;

    /** @var string */
    public $placeholder;

    /**
     * @var CSRFSynchronizerToken
     */
    public $csrf_token;

    /** @var bool */
    public $is_definition_disabled;

    /** @var bool */
    public $is_execution_disabled;

    /** @var string */
    public $definition_admin_url;

    /** @var string */
    public $execution_admin_url;

    public function __construct(
        int $campaign_tracker_id,
        int $test_definition_tracker_id,
        int $test_execution_tracker_id,
        ?int $issue_tracker_id,
        CSRFSynchronizerToken $csrf_token,
        bool $is_definition_disabled,
        bool $is_execution_disabled
    ) {
        $this->campaign_tracker_id        = $campaign_tracker_id;
        $this->test_definition_tracker_id = $test_definition_tracker_id;
        $this->test_execution_tracker_id  = $test_execution_tracker_id;
        $this->issue_tracker_id           = $issue_tracker_id;
        $this->csrf_token                 = $csrf_token;
        $this->is_definition_disabled     = $is_definition_disabled;
        $this->is_execution_disabled      = $is_execution_disabled;
        $this->definition_admin_url       = TRACKER_BASE_URL . '?' .
            http_build_query(
                [
                    'tracker' => $test_definition_tracker_id,
                    'func'    => 'admin-formElements'
                ]
            );
        $this->execution_admin_url       = TRACKER_BASE_URL . '?' .
            http_build_query(
                [
                    'tracker' => $test_execution_tracker_id,
                    'func'    => 'admin-formElements'
                ]
            );

        $this->title       = $GLOBALS['Language']->getText('global', 'Administration');
        $this->campaigns   = dgettext('tuleap-testmanagement', 'Test Campaigns Tracker');
        $this->definitions = dgettext('tuleap-testmanagement', 'Test Definitions Tracker');
        $this->executions  = dgettext('tuleap-testmanagement', 'Test Executions Tracker');
        $this->issues      = dgettext('tuleap-testmanagement', 'Issue Tracker');
        $this->submit      = $GLOBALS['Language']->getText('global', 'btn_submit');
        $this->placeholder = dgettext('tuleap-testmanagement', 'Enter the tracker id...');
    }
}
