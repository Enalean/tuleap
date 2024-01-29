<?php
/**
 * Copyright (c) Enalean, 2018 - Present. All Rights Reserved.
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

namespace Tuleap\Tracker\REST;

use REST_TestDataBuilder;
use Tuleap\Tracker\Admin\ArtifactDeletion\ArtifactsDeletionConfigDAO;
use Tuleap\Tracker\Tests\REST\TrackerBase;

require_once __DIR__ . '/TrackerBase.php';

class DataBuilder extends REST_TestDataBuilder
{
    public const USER_TESTER_NAME                   = 'rest_api_tracker_admin_1';
    public const MY_ARTIFACTS_USER_NAME             = 'rest_my_artifacts';
    public const PRIVATE_COMMENT_PROJECT_ADMIN_NAME = 'rest_private_comment_admin';
    public const PRIVATE_COMMENT_MEMBER_NAME        = 'rest_private_comment_member';
    public const PRIVATE_COMMENT_TRACKER_ADMIN_NAME = 'rest_private_comment_tracker_admin';
    public const PRIVATE_COMMENT_JOHN_SNOW_NAME     = 'rest_private_comment_john_snow';
    public const PRIVATE_COMMENT_DAENERYS_NAME      = 'rest_private_comment_daenerys';

    /**
     * @var ArtifactsDeletionConfigDAO
     */
    private $config_dao;

    public function __construct()
    {
        parent::__construct();
        $this->instanciateFactories();

        $this->config_dao = new ArtifactsDeletionConfigDAO();
    }

    public function setUp()
    {
        echo "Setup data for Tracker plugin tests" . PHP_EOL;

        $this->createUser();
        $this->setUpDeletableArtifactsLimit();
        $this->setUpWorkflowsInSimpleMode();
    }

    private function setUpDeletableArtifactsLimit()
    {
        $this->config_dao->updateDeletableArtifactsLimit(1);
    }

    private function createUser()
    {
        $this->initPassword(self::USER_TESTER_NAME, self::STANDARD_PASSWORD);
        $this->initPassword(self::MY_ARTIFACTS_USER_NAME, self::STANDARD_PASSWORD);
        $this->initPassword(self::PRIVATE_COMMENT_PROJECT_ADMIN_NAME, self::STANDARD_PASSWORD);
        $this->initPassword(self::PRIVATE_COMMENT_MEMBER_NAME, self::STANDARD_PASSWORD);
        $this->initPassword(self::PRIVATE_COMMENT_TRACKER_ADMIN_NAME, self::STANDARD_PASSWORD);
        $this->initPassword(self::PRIVATE_COMMENT_JOHN_SNOW_NAME, self::STANDARD_PASSWORD);
        $this->initPassword(self::PRIVATE_COMMENT_DAENERYS_NAME, self::STANDARD_PASSWORD);
    }

    private function setUpWorkflowsInSimpleMode()
    {
        $sql        = 'UPDATE tracker_workflow JOIN tracker ON (tracker.id = tracker_workflow.tracker_id) SET is_advanced = 0 WHERE tracker.item_name = ?';
        $connection = \Tuleap\DB\DBFactory::getMainTuleapDBConnection();

        $connection->getDB()->single($sql, [TrackerBase::TRACKER_WORKFLOW_SIMPLE_MODE_SHORTNAME]);
        $connection->getDB()->single($sql, [TrackerBase::TRACKER_WORKFLOW_SIMPLE_MODE_TO_SWITCH_SHORTNAME]);
    }
}
