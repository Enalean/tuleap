<?php
/**
 * Copyright (c) Enalean, 2014-present. All Rights Reserved.
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

namespace Tuleap\TestManagement\Administration;

use CSRFSynchronizerToken;

/**
 * @psalm-immutable
 */
class AdminPresenter
{
    /** @var ListOfAdminTrackersPresenter */
    public $campaign_tracker_config;

    /** @var ListOfAdminTrackersPresenter */
    public $test_definition_tracker_config;

    /** @var ListOfAdminTrackersPresenter */
    public $test_execution_tracker_config;

    /** @var ListOfAdminTrackersPresenter */
    public $issue_tracker_config;

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
        ListOfAdminTrackersPresenter $campaign_tracker_config,
        ListOfAdminTrackersPresenter $test_definition_tracker_config,
        ListOfAdminTrackersPresenter $test_execution_tracker_config,
        ListOfAdminTrackersPresenter $issue_tracker_config,
        CSRFSynchronizerToken $csrf_token,
        bool $is_definition_disabled,
        bool $is_execution_disabled,
    ) {
        $this->campaign_tracker_config        = $campaign_tracker_config;
        $this->test_definition_tracker_config = $test_definition_tracker_config;
        $this->test_execution_tracker_config  = $test_execution_tracker_config;
        $this->issue_tracker_config           = $issue_tracker_config;
        $this->csrf_token                     = $csrf_token;
        $this->is_definition_disabled         = $is_definition_disabled;
        $this->is_execution_disabled          = $is_execution_disabled;

        if ($this->is_definition_disabled && $test_definition_tracker_config->selected_tracker !== null) {
            $this->definition_admin_url = TRACKER_BASE_URL . '?' .
                http_build_query(
                    [
                        'tracker' => $test_definition_tracker_config->selected_tracker->tracker_id,
                        'func'    => 'admin-formElements',
                    ]
                );
        }

        if ($this->is_execution_disabled && $test_execution_tracker_config->selected_tracker !== null) {
            $this->execution_admin_url = TRACKER_BASE_URL . '?' .
                http_build_query(
                    [
                        'tracker' => $test_execution_tracker_config->selected_tracker->tracker_id,
                        'func'    => 'admin-formElements',
                    ]
                );
        }

        $this->title       = dgettext('tuleap-testmanagement', 'Administration');
        $this->campaigns   = dgettext('tuleap-testmanagement', 'Test Campaigns Tracker');
        $this->definitions = dgettext('tuleap-testmanagement', 'Test Definitions Tracker');
        $this->executions  = dgettext('tuleap-testmanagement', 'Test Executions Tracker');
        $this->issues      = dgettext('tuleap-testmanagement', 'Issue Tracker');
        $this->submit      = dgettext('tuleap-testmanagement', 'Submit');
        $this->placeholder = dgettext('tuleap-testmanagement', 'Enter the tracker id...');
    }
}
