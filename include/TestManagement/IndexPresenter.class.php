<?php
/**
 * Copyright (c) Enalean, 2014 - present. All Rights Reserved.
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

use ForgeConfig as TuleapConfig;
use PFUser;
use stdClass;
use Tuleap\TestManagement\REST\v1\MilestoneRepresentation;
use Tuleap\User\REST\UserRepresentation;

class IndexPresenter
{

    /** @var int */
    public $project_id;

    /** @var int */
    public $campaign_tracker_id;

    /** @var int */
    public $test_definition_tracker_id;

    /** @var int */
    public $test_execution_tracker_id;

    /** @var int|null */
    public $issue_tracker_id;

    /** @var string */
    public $misconfigured_title;

    /** @var string */
    public $misconfigured_message;

    /** @var bool */
    public $is_properly_configured;

    /** @var string */
    public $current_user;

    /** @var string */
    public $lang;

    /** @var string */
    public $nodejs_server;

    /** @var  string */
    public $tracker_ids;

    /** @var  array */
    public $tracker_permissions;

    /** @var string */
    public $current_milestone;
    /**
     * @var false|string
     */
    public $issue_tracker_config;

    /**
     * @param int|false $campaign_tracker_id
     * @param int|false $test_definition_tracker_id
     * @param int|false $test_execution_tracker_id
     * @param int|false|null $issue_tracker_id
     */
    public function __construct(
        int $project_id,
        $campaign_tracker_id,
        $test_definition_tracker_id,
        $test_execution_tracker_id,
        $issue_tracker_id,
        array $issue_tracker_config,
        PFUser $current_user,
        ?\Planning_Milestone $current_milestone
    ) {
        $this->lang       = $this->getLanguageAbbreviation($current_user);
        $this->project_id = $project_id;

        $user_representation = new UserRepresentation();
        $user_representation->build($current_user);
        $this->current_user = json_encode($user_representation);

        $this->test_definition_tracker_id = intval($test_definition_tracker_id);
        $this->test_execution_tracker_id  = intval($test_execution_tracker_id);
        $this->campaign_tracker_id        = intval($campaign_tracker_id);
        $this->issue_tracker_id           = $issue_tracker_id ? intval($issue_tracker_id) : null;
        $this->nodejs_server              = TuleapConfig::get('nodejs_server');
        $this->tracker_ids = json_encode(
            [
                'definition_tracker_id' => $this->test_definition_tracker_id,
                'execution_tracker_id'  => $this->test_execution_tracker_id,
                'campaign_tracker_id'   => $this->campaign_tracker_id,
                'issue_tracker_id'      => $this->issue_tracker_id
            ]
        );

        $this->issue_tracker_config = json_encode($issue_tracker_config);

        if (isset($current_milestone)) {
            $milestone_representation = new MilestoneRepresentation($current_milestone);
        } else {
            $milestone_representation = new stdClass();
        }
        $this->current_milestone          = json_encode($milestone_representation, JSON_THROW_ON_ERROR);
    }

    private function getLanguageAbbreviation(PFUser $current_user): string
    {
        [$lang, $country] = explode('_', $current_user->getLocale());

        return $lang;
    }
}
