<?php
/**
 * Copyright (c) Enalean, 2018-Present. All Rights Reserved.
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

namespace Tuleap\Git\HTTP;

use PermissionsManager;
use PHPUnit\Framework\TestCase;
use Tuleap\Git\Gerrit\ReplicationHTTPUserAuthenticator;

class HTTPAccessControlTest extends TestCase
{
    use \Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    protected function tearDown() : void
    {
        unset($_SERVER['PHP_AUTH_USER'], $_SERVER['PHP_AUTH_PW']);
    }

    public function testHTTPReplicationUserCanBeAuthenticated()
    {
        $logger                              = \Mockery::mock(\Logger::class);
        $forge_access                        = \Mockery::mock(\ForgeAccess::class);
        $user_login_manager                  = \Mockery::mock(\User_LoginManager::class);
        $replication_http_user_authenticator = \Mockery::mock(ReplicationHTTPUserAuthenticator::class);
        $permissions_manager                 = \Mockery::mock(PermissionsManager::class);
        $user_dao                            = \Mockery::mock(\UserDao::class);

        $http_access_control = new HTTPAccessControl(
            $logger,
            $forge_access,
            $user_login_manager,
            $replication_http_user_authenticator,
            $permissions_manager,
            $user_dao
        );

        $git_repository   = \Mockery::mock(\GitRepository::class);
        $git_operation    = \Mockery::mock(GitHTTPOperation::class);

        $logger->shouldReceive('debug');
        $forge_access->shouldReceive('doesPlatformRequireLogin')->andReturns(true);
        $git_repository->shouldReceive('getFullName');
        $_SERVER['PHP_AUTH_USER'] = 'forge__gerrit_1';
        $_SERVER['PHP_AUTH_PW']   = 'password';
        $expected_user = \Mockery::mock(\PFO_User::class);
        $expected_user->shouldReceive('getUnixName');
        $replication_http_user_authenticator->shouldReceive('authenticate')->andReturns($expected_user);

        $authenticated_user = $http_access_control->getUser($git_repository, $git_operation);

        $this->assertSame($expected_user, $authenticated_user);
    }

    public function testTuleapUserCanBeAuthenticated()
    {
        $logger                              = \Mockery::mock(\Logger::class);
        $forge_access                        = \Mockery::mock(\ForgeAccess::class);
        $user_login_manager                  = \Mockery::mock(\User_LoginManager::class);
        $replication_http_user_authenticator = \Mockery::mock(ReplicationHTTPUserAuthenticator::class);
        $permissions_manager                 = \Mockery::mock(PermissionsManager::class);
        $user_dao                            = \Mockery::mock(\UserDao::class);

        $http_access_control = new HTTPAccessControl(
            $logger,
            $forge_access,
            $user_login_manager,
            $replication_http_user_authenticator,
            $permissions_manager,
            $user_dao
        );

        $git_repository   = \Mockery::mock(\GitRepository::class);
        $git_operation    = \Mockery::mock(GitHTTPOperation::class);

        $logger->shouldReceive('debug');
        $forge_access->shouldReceive('doesPlatformRequireLogin')->andReturns(true);
        $git_repository->shouldReceive('getFullName');
        $_SERVER['PHP_AUTH_USER'] = 'user1';
        $_SERVER['PHP_AUTH_PW']   = 'password';
        $replication_http_user_authenticator->shouldReceive('authenticate')->
            andThrows(\Mockery::spy(\Git_RemoteServer_NotFoundException::class));
        $expected_user = \Mockery::mock(\PFUser::class);
        $expected_user->shouldReceive('getUnixName');
        $expected_user->shouldReceive('getId');
        $user_login_manager->shouldReceive('authenticate')->andReturns($expected_user);
        $user_dao->shouldReceive('storeLastAccessDate')->once();

        $authenticated_user = $http_access_control->getUser($git_repository, $git_operation);

        $this->assertSame($expected_user, $authenticated_user);
    }

    /**
     * @runInSeparateProcess
     */
    public function testAuthenticationIsDeniedWhenNoValidUserIsFound()
    {
        $logger                              = \Mockery::mock(\Logger::class);
        $forge_access                        = \Mockery::mock(\ForgeAccess::class);
        $user_login_manager                  = \Mockery::mock(\User_LoginManager::class);
        $replication_http_user_authenticator = \Mockery::mock(ReplicationHTTPUserAuthenticator::class);
        $permissions_manager                 = \Mockery::mock(PermissionsManager::class);
        $user_dao                            = \Mockery::mock(\UserDao::class);

        $http_access_control = new HTTPAccessControl(
            $logger,
            $forge_access,
            $user_login_manager,
            $replication_http_user_authenticator,
            $permissions_manager,
            $user_dao
        );

        $git_repository   = \Mockery::mock(\GitRepository::class);
        $git_operation    = \Mockery::mock(GitHTTPOperation::class);

        $logger->shouldReceive('debug');
        $forge_access->shouldReceive('doesPlatformRequireLogin')->andReturns(true);
        $git_repository->shouldReceive('getFullName');
        $_SERVER['PHP_AUTH_USER'] = 'user1';
        $_SERVER['PHP_AUTH_PW']   = 'invalid_password';
        $replication_http_user_authenticator->shouldReceive('authenticate')->
            andThrows(\Mockery::spy(\Git_RemoteServer_NotFoundException::class));
        $user_login_manager->shouldReceive('authenticate')->
            andThrows(\Mockery::spy(\User_LoginException::class));

        $http_access_control->getUser($git_repository, $git_operation);

        $this->fail('The test should have exited to request a valid basic authentication');
    }

    /**
     * @runInSeparateProcess
     */
    public function testAuthenticationIsRequestedWhenEmptyCredentialIsGiven()
    {
        $logger                              = \Mockery::mock(\Logger::class);
        $forge_access                        = \Mockery::mock(\ForgeAccess::class);
        $user_login_manager                  = \Mockery::mock(\User_LoginManager::class);
        $replication_http_user_authenticator = \Mockery::mock(ReplicationHTTPUserAuthenticator::class);
        $permissions_manager                 = \Mockery::mock(PermissionsManager::class);
        $user_dao                            = \Mockery::mock(\UserDao::class);

        $http_access_control = new HTTPAccessControl(
            $logger,
            $forge_access,
            $user_login_manager,
            $replication_http_user_authenticator,
            $permissions_manager,
            $user_dao
        );

        $git_repository   = \Mockery::mock(\GitRepository::class);
        $git_operation    = \Mockery::mock(GitHTTPOperation::class);

        $logger->shouldReceive('debug');
        $forge_access->shouldReceive('doesPlatformRequireLogin')->andReturns(true);
        $git_repository->shouldReceive('getFullName');
        $_SERVER['PHP_AUTH_USER'] = '';
        $_SERVER['PHP_AUTH_PW']   = '';

        $http_access_control->getUser($git_repository, $git_operation);

        $this->fail('The test should have exited to request a valid basic authentication');
    }

    public function testNoAuthenticationIsRequiredForAReadAccessOfPublicRepoOnAnInstanceAccessibleToAnonymous()
    {
        $logger                              = \Mockery::mock(\Logger::class);
        $forge_access                        = \Mockery::mock(\ForgeAccess::class);
        $user_login_manager                  = \Mockery::mock(\User_LoginManager::class);
        $replication_http_user_authenticator = \Mockery::mock(ReplicationHTTPUserAuthenticator::class);
        $permissions_manager                 = \Mockery::mock(PermissionsManager::class);
        $user_dao                            = \Mockery::mock(\UserDao::class);

        $http_access_control = new HTTPAccessControl(
            $logger,
            $forge_access,
            $user_login_manager,
            $replication_http_user_authenticator,
            $permissions_manager,
            $user_dao
        );

        $git_repository   = \Mockery::mock(\GitRepository::class);
        $git_operation    = \Mockery::mock(GitHTTPOperation::class);

        $logger->shouldReceive('debug');
        $forge_access->shouldReceive('doesPlatformRequireLogin')->andReturns(false);
        $git_operation->shouldReceive('isWrite')->andReturns(false);
        $project = \Mockery::mock(\Project::class);
        $project->shouldReceive('isPublic')->andReturns(true);
        $git_repository->shouldReceive('getProject')->andReturns($project);
        $git_repository->shouldReceive('getId')->andReturns(1);
        $permissions_manager->shouldReceive('getAuthorizedUgroupIds')->andReturns([\ProjectUGroup::ANONYMOUS]);

        $this->assertNull($http_access_control->getUser($git_repository, $git_operation));
    }
}
