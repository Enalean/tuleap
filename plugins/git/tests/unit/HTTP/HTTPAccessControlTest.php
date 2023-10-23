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
use Tuleap\Git\Gerrit\ReplicationHTTPUserAuthenticator;
use Tuleap\User\AccessKey\HTTPBasicAuth\HTTPBasicAuthUserAccessKeyAuthenticator;
use Tuleap\User\AccessKey\HTTPBasicAuth\HTTPBasicAuthUserAccessKeyMisusageException;

final class HTTPAccessControlTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use \Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    protected function tearDown(): void
    {
        unset($_SERVER['PHP_AUTH_USER'], $_SERVER['PHP_AUTH_PW'], $_SERVER['REMOTE_ADDR']);
    }

    public function testHTTPReplicationUserCanBeAuthenticated(): void
    {
        $logger                              = \Mockery::mock(\Psr\Log\LoggerInterface::class);
        $forge_access                        = \Mockery::mock(\ForgeAccess::class);
        $user_login_manager                  = \Mockery::mock(\User_LoginManager::class);
        $replication_http_user_authenticator = \Mockery::mock(ReplicationHTTPUserAuthenticator::class);
        $access_key_authenticator            = \Mockery::mock(HTTPBasicAuthUserAccessKeyAuthenticator::class);
        $permissions_manager                 = \Mockery::mock(PermissionsManager::class);
        $user_dao                            = \Mockery::mock(\UserDao::class);

        $http_access_control = new HTTPAccessControl(
            $logger,
            $forge_access,
            $user_login_manager,
            $replication_http_user_authenticator,
            $access_key_authenticator,
            $permissions_manager,
            $user_dao,
            new GitHTTPAskBasicAuthenticationChallenge()
        );

        $git_repository = \Mockery::mock(\GitRepository::class);
        $git_operation  = \Mockery::mock(GitHTTPOperation::class);

        $logger->shouldReceive('debug');
        $forge_access->shouldReceive('doesPlatformRequireLogin')->andReturns(true);
        $git_repository->shouldReceive('getFullName');
        $_SERVER['PHP_AUTH_USER'] = 'forge__gerrit_1';
        $_SERVER['PHP_AUTH_PW']   = 'password';
        $expected_user            = \Mockery::mock(\PFO_User::class);
        $expected_user->shouldReceive('getUserName');
        $replication_http_user_authenticator->shouldReceive('authenticate')->andReturns($expected_user);

        $authenticated_user = $http_access_control->getUser($git_repository, $git_operation);

        $this->assertSame($expected_user, $authenticated_user);
    }

    public function testTuleapUserCanBeAuthenticated(): void
    {
        $logger                              = \Mockery::mock(\Psr\Log\LoggerInterface::class);
        $forge_access                        = \Mockery::mock(\ForgeAccess::class);
        $user_login_manager                  = \Mockery::mock(\User_LoginManager::class);
        $replication_http_user_authenticator = \Mockery::mock(ReplicationHTTPUserAuthenticator::class);
        $access_key_authenticator            = \Mockery::mock(HTTPBasicAuthUserAccessKeyAuthenticator::class);
        $permissions_manager                 = \Mockery::mock(PermissionsManager::class);
        $user_dao                            = \Mockery::mock(\UserDao::class);

        $http_access_control = new HTTPAccessControl(
            $logger,
            $forge_access,
            $user_login_manager,
            $replication_http_user_authenticator,
            $access_key_authenticator,
            $permissions_manager,
            $user_dao,
            new GitHTTPAskBasicAuthenticationChallenge()
        );

        $git_repository = \Mockery::mock(\GitRepository::class);
        $git_operation  = \Mockery::mock(GitHTTPOperation::class);

        $logger->shouldReceive('debug');
        $forge_access->shouldReceive('doesPlatformRequireLogin')->andReturns(true);
        $git_repository->shouldReceive('getFullName');
        $_SERVER['PHP_AUTH_USER'] = 'user1';
        $_SERVER['PHP_AUTH_PW']   = 'password';
        $replication_http_user_authenticator->shouldReceive('authenticate')
            ->andThrows(\Mockery::spy(\Git_RemoteServer_NotFoundException::class));
        $_SERVER['REMOTE_ADDR'] = '2001:db8::3';
        $access_key_authenticator->shouldReceive('getUser')->andReturnNull();
        $expected_user = \Mockery::mock(\PFUser::class);
        $expected_user->shouldReceive('getUserName');
        $expected_user->shouldReceive('getId');
        $user_login_manager->shouldReceive('authenticate')->andReturns($expected_user);
        $user_dao->shouldReceive('storeLastAccessDate')->once();

        $authenticated_user = $http_access_control->getUser($git_repository, $git_operation);

        $this->assertSame($expected_user, $authenticated_user);
    }

    public function testTuleapUserCanBeAuthenticatedFromAnAccessKey(): void
    {
        $logger                              = \Mockery::mock(\Psr\Log\LoggerInterface::class);
        $forge_access                        = \Mockery::mock(\ForgeAccess::class);
        $user_login_manager                  = \Mockery::mock(\User_LoginManager::class);
        $replication_http_user_authenticator = \Mockery::mock(ReplicationHTTPUserAuthenticator::class);
        $access_key_authenticator            = \Mockery::mock(HTTPBasicAuthUserAccessKeyAuthenticator::class);
        $permissions_manager                 = \Mockery::mock(PermissionsManager::class);
        $user_dao                            = \Mockery::mock(\UserDao::class);

        $http_access_control = new HTTPAccessControl(
            $logger,
            $forge_access,
            $user_login_manager,
            $replication_http_user_authenticator,
            $access_key_authenticator,
            $permissions_manager,
            $user_dao,
            new GitHTTPAskBasicAuthenticationChallenge()
        );

        $git_repository = \Mockery::mock(\GitRepository::class);
        $git_operation  = \Mockery::mock(GitHTTPOperation::class);

        $logger->shouldReceive('debug');
        $forge_access->shouldReceive('doesPlatformRequireLogin')->andReturns(true);
        $git_repository->shouldReceive('getFullName');
        $_SERVER['PHP_AUTH_USER'] = 'user1';
        $_SERVER['PHP_AUTH_PW']   = 'access_key';
        $replication_http_user_authenticator->shouldReceive('authenticate')
            ->andThrows(\Mockery::spy(\Git_RemoteServer_NotFoundException::class));
        $_SERVER['REMOTE_ADDR'] = '2001:db8::3';
        $expected_user          = \Mockery::mock(\PFUser::class);
        $expected_user->shouldReceive('getUserName');
        $expected_user->shouldReceive('getId');
        $access_key_authenticator->shouldReceive('getUser')->andReturn($expected_user);

        $authenticated_user = $http_access_control->getUser($git_repository, $git_operation);

        $this->assertSame($expected_user, $authenticated_user);
    }

    public function testAuthenticationIsDeniedWhenAnAccessKeyMisusageIsDetected(): void
    {
        $logger                              = \Mockery::mock(\Psr\Log\LoggerInterface::class);
        $forge_access                        = \Mockery::mock(\ForgeAccess::class);
        $user_login_manager                  = \Mockery::mock(\User_LoginManager::class);
        $replication_http_user_authenticator = \Mockery::mock(ReplicationHTTPUserAuthenticator::class);
        $access_key_authenticator            = \Mockery::mock(HTTPBasicAuthUserAccessKeyAuthenticator::class);
        $permissions_manager                 = \Mockery::mock(PermissionsManager::class);
        $user_dao                            = \Mockery::mock(\UserDao::class);
        $ask_basic_authentication_challenge  = \Mockery::mock(GitHTTPAskBasicAuthenticationChallenge::class);

        $http_access_control = new HTTPAccessControl(
            $logger,
            $forge_access,
            $user_login_manager,
            $replication_http_user_authenticator,
            $access_key_authenticator,
            $permissions_manager,
            $user_dao,
            $ask_basic_authentication_challenge
        );

        $git_repository = \Mockery::mock(\GitRepository::class);
        $git_operation  = \Mockery::mock(GitHTTPOperation::class);

        $logger->shouldReceive('debug');
        $forge_access->shouldReceive('doesPlatformRequireLogin')->andReturns(true);
        $git_repository->shouldReceive('getFullName');
        $_SERVER['PHP_AUTH_USER'] = 'user1';
        $_SERVER['PHP_AUTH_PW']   = 'access_key';
        $replication_http_user_authenticator->shouldReceive('authenticate')
            ->andThrows(\Mockery::spy(\Git_RemoteServer_NotFoundException::class));
        $_SERVER['REMOTE_ADDR'] = '2001:db8::3';
        $found_user             = \Mockery::mock(\PFUser::class);
        $found_user->shouldReceive('getUserName')->andReturn('username');
        $access_key_authenticator->shouldReceive('getUser')
            ->andThrow(new HTTPBasicAuthUserAccessKeyMisusageException('user1', $found_user));

        $not_supposed_to_return = new class extends \LogicException
        {
        };
        $ask_basic_authentication_challenge->shouldReceive('askBasicAuthenticationChallenge')->once()->andThrow($not_supposed_to_return);
        $this->expectException($not_supposed_to_return::class);

        $http_access_control->getUser($git_repository, $git_operation);
    }

    public function testAuthenticationIsDeniedWhenNoValidUserIsFound(): void
    {
        $logger                              = \Mockery::mock(\Psr\Log\LoggerInterface::class);
        $forge_access                        = \Mockery::mock(\ForgeAccess::class);
        $user_login_manager                  = \Mockery::mock(\User_LoginManager::class);
        $replication_http_user_authenticator = \Mockery::mock(ReplicationHTTPUserAuthenticator::class);
        $access_key_authenticator            = \Mockery::mock(HTTPBasicAuthUserAccessKeyAuthenticator::class);
        $permissions_manager                 = \Mockery::mock(PermissionsManager::class);
        $user_dao                            = \Mockery::mock(\UserDao::class);
        $ask_basic_authentication_challenge  = \Mockery::mock(GitHTTPAskBasicAuthenticationChallenge::class);

        $http_access_control = new HTTPAccessControl(
            $logger,
            $forge_access,
            $user_login_manager,
            $replication_http_user_authenticator,
            $access_key_authenticator,
            $permissions_manager,
            $user_dao,
            $ask_basic_authentication_challenge
        );

        $git_repository = \Mockery::mock(\GitRepository::class);
        $git_operation  = \Mockery::mock(GitHTTPOperation::class);

        $logger->shouldReceive('debug');
        $forge_access->shouldReceive('doesPlatformRequireLogin')->andReturns(true);
        $git_repository->shouldReceive('getFullName');
        $_SERVER['PHP_AUTH_USER'] = 'user1';
        $_SERVER['PHP_AUTH_PW']   = 'invalid_password';
        $replication_http_user_authenticator->shouldReceive('authenticate')
            ->andThrows(\Mockery::spy(\Git_RemoteServer_NotFoundException::class));
        $_SERVER['REMOTE_ADDR'] = '2001:db8::3';
        $access_key_authenticator->shouldReceive('getUser')->andReturnNull();
        $user_login_manager->shouldReceive('authenticate')
            ->andThrows(\Mockery::spy(\User_LoginException::class));

        $ask_auth_throwable_test = new \RuntimeException('Thrown exception for test purposes');
        $ask_basic_authentication_challenge->shouldReceive('askBasicAuthenticationChallenge')->andThrow($ask_auth_throwable_test);

        $this->expectExceptionObject($ask_auth_throwable_test);

        $http_access_control->getUser($git_repository, $git_operation);
    }

    public function testAuthenticationIsRequestedWhenEmptyCredentialIsGiven(): void
    {
        $logger                              = \Mockery::mock(\Psr\Log\LoggerInterface::class);
        $forge_access                        = \Mockery::mock(\ForgeAccess::class);
        $user_login_manager                  = \Mockery::mock(\User_LoginManager::class);
        $replication_http_user_authenticator = \Mockery::mock(ReplicationHTTPUserAuthenticator::class);
        $access_key_authenticator            = \Mockery::mock(HTTPBasicAuthUserAccessKeyAuthenticator::class);
        $permissions_manager                 = \Mockery::mock(PermissionsManager::class);
        $user_dao                            = \Mockery::mock(\UserDao::class);
        $ask_basic_authentication_challenge  = \Mockery::mock(GitHTTPAskBasicAuthenticationChallenge::class);

        $http_access_control = new HTTPAccessControl(
            $logger,
            $forge_access,
            $user_login_manager,
            $replication_http_user_authenticator,
            $access_key_authenticator,
            $permissions_manager,
            $user_dao,
            $ask_basic_authentication_challenge
        );

        $git_repository = \Mockery::mock(\GitRepository::class);
        $git_operation  = \Mockery::mock(GitHTTPOperation::class);

        $logger->shouldReceive('debug');
        $forge_access->shouldReceive('doesPlatformRequireLogin')->andReturns(true);
        $git_repository->shouldReceive('getFullName');
        $_SERVER['PHP_AUTH_USER'] = '';
        $_SERVER['PHP_AUTH_PW']   = '';

        $ask_auth_throwable_test = new \RuntimeException('Thrown exception for test purposes');
        $ask_basic_authentication_challenge->shouldReceive('askBasicAuthenticationChallenge')->andThrow($ask_auth_throwable_test);

        $this->expectExceptionObject($ask_auth_throwable_test);

        $http_access_control->getUser($git_repository, $git_operation);
    }

    public function testNoAuthenticationIsRequiredForAReadAccessOfPublicRepoOnAnInstanceAccessibleToAnonymous(): void
    {
        $logger                              = \Mockery::mock(\Psr\Log\LoggerInterface::class);
        $forge_access                        = \Mockery::mock(\ForgeAccess::class);
        $user_login_manager                  = \Mockery::mock(\User_LoginManager::class);
        $replication_http_user_authenticator = \Mockery::mock(ReplicationHTTPUserAuthenticator::class);
        $access_key_authenticator            = \Mockery::mock(HTTPBasicAuthUserAccessKeyAuthenticator::class);
        $permissions_manager                 = \Mockery::mock(PermissionsManager::class);
        $user_dao                            = \Mockery::mock(\UserDao::class);

        $http_access_control = new HTTPAccessControl(
            $logger,
            $forge_access,
            $user_login_manager,
            $replication_http_user_authenticator,
            $access_key_authenticator,
            $permissions_manager,
            $user_dao,
            new GitHTTPAskBasicAuthenticationChallenge()
        );

        $git_repository = \Mockery::mock(\GitRepository::class);
        $git_operation  = \Mockery::mock(GitHTTPOperation::class);

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
