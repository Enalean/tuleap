<?php
/**
 * Copyright (c) Enalean, 2014 - 2018. All rights reserved
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/
 */


class TestManagementDataBuilder extends REST_TestDataBuilder
{
    public const PROJECT_TEST_MGMT_SHORTNAME = 'test-mgmt';
    public const ISSUE_TRACKER_SHORTNAME     = 'bugs';

    public const USER_TESTER_NAME   = 'rest_api_ttm_1';
    public const USER_TESTER_PASS   = 'welcome0';
    public const USER_TESTER_STATUS = 'A';

    public function __construct()
    {
        parent::__construct();
        $this->instanciateFactories();

        $this->template_path   = dirname(__FILE__) . '/_fixtures/';
    }

    public function setUp()
    {
        echo 'Setup TestManagement REST tests configuration' . PHP_EOL;

        $user = $this->user_manager->getUserByUserName(self::USER_TESTER_NAME);
        $user->setPassword(self::USER_TESTER_PASS);
        $this->user_manager->updateDb($user);
    }
}
