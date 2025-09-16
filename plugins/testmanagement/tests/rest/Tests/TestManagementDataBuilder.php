<?php
/**
 * Copyright (c) Enalean, 2014 - Present. All rights reserved
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

declare(strict_types=1);

namespace Tuleap\TestManagement\REST\Tests;

use Tuleap\Cryptography\ConcealedString;
use Tuleap\REST\RESTTestDataBuilder;

final class TestManagementDataBuilder extends RESTTestDataBuilder
{
    public const string PROJECT_TEST_MGMT_SHORTNAME                  = 'test-mgmt';
    public const string PROJECT_TEST_MGMT_WITH_ATTACHMENTS_SHORTNAME = 'test-mgmt-exec-attach';
    public const string ISSUE_TRACKER_SHORTNAME                      = 'bugs';
    public const string TEST_DEF_TRACKER_SHORTNAME                   = 'test_def';

    public const string USER_TESTER_NAME = 'rest_api_ttm_1';
    public const string USER_TESTER_PASS = 'welcome0';

    public function __construct()
    {
        parent::__construct();
        $this->instanciateFactories();

        $this->template_path = __DIR__ . '/_fixtures/';
    }

    public function setUp(): void
    {
        echo 'Setup TestManagement REST tests configuration' . PHP_EOL;

        $user = $this->user_manager->getUserByUserName(self::USER_TESTER_NAME);
        if ($user === null) {
            throw new \RuntimeException(sprintf('Could not find user with name "%s"', self::USER_TESTER_NAME));
        }
        $user->setPassword(new ConcealedString(self::USER_TESTER_PASS));
        $this->user_manager->updateDb($user);
    }
}
