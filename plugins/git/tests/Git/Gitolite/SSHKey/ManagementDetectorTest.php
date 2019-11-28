<?php
/**
 * Copyright (c) Enalean, 2017-Present. All Rights Reserved.
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

namespace Tuleap\Git\Gitolite\SSHKey;

use TuleapTestCase;
use Tuleap\Git\GlobalParameterDao;

require_once __DIR__ .'/../../../bootstrap.php';

class ManagementDetectorTest extends TuleapTestCase
{
    public function itCanNotManageAuthorizedKeysFileIfGitolite3IsNotUsed()
    {
        $version_detector     = \Mockery::spy(\Tuleap\Git\Gitolite\VersionDetector::class);
        $version_detector->shouldReceive('isGitolite3')->andReturns(false);
        $global_parameter_dao = safe_mock(GlobalParameterDao::class);
        $system_event_manager = \Mockery::spy(\SystemEventManager::class);

        $management_detector = new ManagementDetector($version_detector, $global_parameter_dao, $system_event_manager);

        $this->assertFalse($management_detector->isAuthorizedKeysFileManagedByTuleap());
    }

    public function itIsAbleToFindThatTuleapManagesAuthorizedKeysFile()
    {
        $version_detector     = \Mockery::spy(\Tuleap\Git\Gitolite\VersionDetector::class);
        $version_detector->shouldReceive('isGitolite3')->andReturns(true);
        $global_parameter_dao = safe_mock(GlobalParameterDao::class);
        $global_parameter_dao->shouldReceive('isAuthorizedKeysFileManagedByTuleap')->andReturns(false);
        $system_event_manager = \Mockery::spy(\SystemEventManager::class);

        $management_detector = new ManagementDetector($version_detector, $global_parameter_dao, $system_event_manager);

        $this->assertFalse($management_detector->isAuthorizedKeysFileManagedByTuleap());
    }

    public function itIsAbleToDetectThatTuleapManagesAuthorizedKeysFile()
    {
        $version_detector     = \Mockery::spy(\Tuleap\Git\Gitolite\VersionDetector::class);
        $version_detector->shouldReceive('isGitolite3')->andReturns(true);
        $global_parameter_dao = safe_mock(GlobalParameterDao::class);
        $global_parameter_dao->shouldReceive('isAuthorizedKeysFileManagedByTuleap')->andReturns(true);
        $system_event_manager = \Mockery::spy(\SystemEventManager::class);

        $management_detector = new ManagementDetector($version_detector, $global_parameter_dao, $system_event_manager);

        $this->assertTrue($management_detector->isAuthorizedKeysFileManagedByTuleap());
    }

    public function itCanNotBeRequestedToMigrateToTuleapManagementIfGitolite3IsNotUsed()
    {
        $version_detector     = \Mockery::spy(\Tuleap\Git\Gitolite\VersionDetector::class);
        $version_detector->shouldReceive('isGitolite3')->andReturns(false);
        $global_parameter_dao = safe_mock(GlobalParameterDao::class);
        $system_event_manager = \Mockery::spy(\SystemEventManager::class);

        $management_detector = new ManagementDetector($version_detector, $global_parameter_dao, $system_event_manager);

        $this->assertFalse($management_detector->canRequestAuthorizedKeysFileManagementByTuleap());
    }

    public function itCanNotBeRequestedToMigrateToTuleapManagementIfTuleapAlreadyManagesTheAuthorizedKeysFile()
    {
        $version_detector     = \Mockery::spy(\Tuleap\Git\Gitolite\VersionDetector::class);
        $version_detector->shouldReceive('isGitolite3')->andReturns(true);
        $global_parameter_dao = safe_mock(GlobalParameterDao::class);
        $global_parameter_dao->shouldReceive('isAuthorizedKeysFileManagedByTuleap')->andReturns(true);
        $system_event_manager = \Mockery::spy(\SystemEventManager::class);

        $management_detector = new ManagementDetector($version_detector, $global_parameter_dao, $system_event_manager);

        $this->assertFalse($management_detector->canRequestAuthorizedKeysFileManagementByTuleap());
    }

    public function itCanNotBeRequestedToMigrateToTuleapManagementIfThereIsAlreadyAMigrationRunning()
    {
        $version_detector     = \Mockery::spy(\Tuleap\Git\Gitolite\VersionDetector::class);
        $version_detector->shouldReceive('isGitolite3')->andReturns(true);
        $global_parameter_dao = safe_mock(GlobalParameterDao::class);
        $global_parameter_dao->shouldReceive('isAuthorizedKeysFileManagedByTuleap')->andReturns(false);
        $system_event_manager = \Mockery::spy(\SystemEventManager::class);
        $system_event_manager->shouldReceive('isThereAnEventAlreadyOnGoing')->andReturns(true);

        $management_detector = new ManagementDetector($version_detector, $global_parameter_dao, $system_event_manager);

        $this->assertFalse($management_detector->canRequestAuthorizedKeysFileManagementByTuleap());
    }

    public function itCanBeRequestedToMigrateToTuleapManagementIfThatsNotAlreadyTheCase()
    {
        $version_detector     = \Mockery::spy(\Tuleap\Git\Gitolite\VersionDetector::class);
        $version_detector->shouldReceive('isGitolite3')->andReturns(true);
        $global_parameter_dao = safe_mock(GlobalParameterDao::class);
        $global_parameter_dao->shouldReceive('isAuthorizedKeysFileManagedByTuleap')->andReturns(false);
        $system_event_manager = \Mockery::spy(\SystemEventManager::class);
        $system_event_manager->shouldReceive('isThereAnEventAlreadyOnGoing')->andReturns(false);

        $management_detector = new ManagementDetector($version_detector, $global_parameter_dao, $system_event_manager);

        $this->assertTrue($management_detector->canRequestAuthorizedKeysFileManagementByTuleap());
    }
}
