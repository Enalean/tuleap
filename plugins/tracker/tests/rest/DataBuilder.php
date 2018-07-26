<?php
/**
 * Copyright (c) Enalean, 2018. All Rights Reserved.
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

class DataBuilder extends REST_TestDataBuilder
{
    const USER_TESTER_NAME   = 'rest_api_tracker_admin_1';
    const USER_TESTER_PASS   = 'welcome0';

    /**
     * @var ArtifactsDeletionConfigDAO
     */
    private $config_dao;

    public function __construct()
    {
        parent::__construct();

        $this->config_dao = new ArtifactsDeletionConfigDAO();
    }

    public function setUp()
    {
        echo "Setup data for Tracker plugin tests" . PHP_EOL;

        $this->createUser();
        $this->setUpDeletableArtifactsLimit();
    }

    private function setUpDeletableArtifactsLimit()
    {
        $this->config_dao->updateDeletableArtifactsLimit(2);
    }

    private function createUser()
    {
        $user = $this->user_manager->getUserByUserName(self::USER_TESTER_NAME);
        $user->setPassword(self::USER_TESTER_PASS);
        $this->user_manager->updateDb($user);
    }
}
