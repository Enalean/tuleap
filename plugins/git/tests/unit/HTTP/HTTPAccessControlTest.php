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

declare(strict_types=1);

namespace Tuleap\Git\HTTP;

use ColinODell\PsrTestLogger\TestLogger;
use ForgeAccess;
use Git_RemoteServer_NotFoundException;
use GitRepository;
use LogicException;
use PermissionsManager;
use PFO_User;
use PHPUnit\Framework\MockObject\MockObject;
use ProjectUGroup;
use RuntimeException;
use Tuleap\Git\Gerrit\ReplicationHTTPUserAuthenticator;
use Tuleap\Git\Tests\Builders\GitRepositoryTestBuilder;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\User\AccessKey\HTTPBasicAuth\HTTPBasicAuthUserAccessKeyAuthenticator;
use Tuleap\User\AccessKey\HTTPBasicAuth\HTTPBasicAuthUserAccessKeyMisusageException;
use User_InvalidPasswordException;
use User_LoginManager;
use UserDao;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class HTTPAccessControlTest extends TestCase
{
    private TestLogger $logger;
    private ForgeAccess&MockObject $forge_access;
    private User_LoginManager&MockObject $user_login_manager;
    private ReplicationHTTPUserAuthenticator&MockObject $replication_http_user_authenticator;
    private HTTPBasicAuthUserAccessKeyAuthenticator&MockObject $access_key_authenticator;
    private PermissionsManager&MockObject $permissions_manager;
    private UserDao&MockObject $user_dao;
    private HTTPAccessControl $http_access_control;
    private GitRepository $git_repository;
    private GitHTTPOperation&MockObject $git_operation;
    private GitHTTPAskBasicAuthenticationChallenge&MockObject $ask_basic_authentication_challenge;

    #[\Override]
    protected function setUp(): void
    {
        $this->logger                              = new TestLogger();
        $this->forge_access                        = $this->createMock(ForgeAccess::class);
        $this->user_login_manager                  = $this->createMock(User_LoginManager::class);
        $this->replication_http_user_authenticator = $this->createMock(ReplicationHTTPUserAuthenticator::class);
        $this->access_key_authenticator            = $this->createMock(HTTPBasicAuthUserAccessKeyAuthenticator::class);
        $this->permissions_manager                 = $this->createMock(PermissionsManager::class);
        $this->user_dao                            = $this->createMock(UserDao::class);
        $this->ask_basic_authentication_challenge  = $this->createMock(GitHTTPAskBasicAuthenticationChallenge::class);

        $this->http_access_control = new HTTPAccessControl(
            $this->logger,
            $this->forge_access,
            $this->user_login_manager,
            $this->replication_http_user_authenticator,
            $this->access_key_authenticator,
            $this->permissions_manager,
            $this->user_dao,
            $this->ask_basic_authentication_challenge,
        );

        $this->git_repository = GitRepositoryTestBuilder::aProjectRepository()->withId(1)
            ->inProject(ProjectTestBuilder::aProject()->withAccessPublic()->build())->build();
        $this->git_operation  = $this->createMock(GitHTTPOperation::class);
    }

    #[\Override]
    protected function tearDown(): void
    {
        unset($_SERVER['PHP_AUTH_USER'], $_SERVER['PHP_AUTH_PW'], $_SERVER['REMOTE_ADDR']);
    }

    public function testHTTPReplicationUserCanBeAuthenticated(): void
    {
        $this->forge_access->method('doesPlatformRequireLogin')->willReturn(true);
        $_SERVER['PHP_AUTH_USER'] = 'forge__gerrit_1';
        $_SERVER['PHP_AUTH_PW']   = 'password';
        $expected_user            = $this->createMock(PFO_User::class);
        $expected_user->method('getUserName');
        $this->replication_http_user_authenticator->method('authenticate')->willReturn($expected_user);

        $authenticated_user = $this->http_access_control->getUser($this->git_repository, $this->git_operation);

        self::assertSame($expected_user, $authenticated_user);
        self::assertTrue($this->logger->hasDebugRecords());
    }

    public function testTuleapUserCanBeAuthenticated(): void
    {
        $this->forge_access->method('doesPlatformRequireLogin')->willReturn(true);
        $_SERVER['PHP_AUTH_USER'] = 'user1';
        $_SERVER['PHP_AUTH_PW']   = 'password';
        $this->replication_http_user_authenticator->method('authenticate')
            ->willThrowException(new Git_RemoteServer_NotFoundException(2));
        $_SERVER['REMOTE_ADDR'] = '2001:db8::3';
        $this->access_key_authenticator->method('getUser')->willReturn(null);
        $expected_user = UserTestBuilder::buildWithDefaults();
        $this->user_login_manager->method('authenticate')->willReturn($expected_user);
        $this->user_dao->expects($this->once())->method('storeLastAccessDate');

        $authenticated_user = $this->http_access_control->getUser($this->git_repository, $this->git_operation);

        self::assertSame($expected_user, $authenticated_user);
        self::assertTrue($this->logger->hasDebugRecords());
    }

    public function testTuleapUserCanBeAuthenticatedFromAnAccessKey(): void
    {
        $this->forge_access->method('doesPlatformRequireLogin')->willReturn(true);
        $_SERVER['PHP_AUTH_USER'] = 'user1';
        $_SERVER['PHP_AUTH_PW']   = 'access_key';
        $this->replication_http_user_authenticator->method('authenticate')
            ->willThrowException(new Git_RemoteServer_NotFoundException(2));
        $_SERVER['REMOTE_ADDR'] = '2001:db8::3';
        $expected_user          = UserTestBuilder::buildWithDefaults();
        $this->access_key_authenticator->method('getUser')->willReturn($expected_user);

        $authenticated_user = $this->http_access_control->getUser($this->git_repository, $this->git_operation);

        self::assertSame($expected_user, $authenticated_user);
        self::assertTrue($this->logger->hasDebugRecords());
    }

    public function testAuthenticationIsDeniedWhenAnAccessKeyMisusageIsDetected(): void
    {
        $this->forge_access->method('doesPlatformRequireLogin')->willReturn(true);
        $_SERVER['PHP_AUTH_USER'] = 'user1';
        $_SERVER['PHP_AUTH_PW']   = 'access_key';
        $this->replication_http_user_authenticator->method('authenticate')
            ->willThrowException(new Git_RemoteServer_NotFoundException(2));
        $_SERVER['REMOTE_ADDR'] = '2001:db8::3';
        $found_user             = UserTestBuilder::aUser()->withUserName('username')->build();
        $this->access_key_authenticator->method('getUser')
            ->willThrowException(new HTTPBasicAuthUserAccessKeyMisusageException('user1', $found_user));

        $not_supposed_to_return = new LogicException();
        $this->ask_basic_authentication_challenge->expects($this->once())->method('askBasicAuthenticationChallenge')->willThrowException($not_supposed_to_return);
        $this->expectExceptionObject($not_supposed_to_return);

        $this->http_access_control->getUser($this->git_repository, $this->git_operation);
        self::assertTrue($this->logger->hasDebugRecords());
    }

    public function testAuthenticationIsDeniedWhenNoValidUserIsFound(): void
    {
        $this->forge_access->method('doesPlatformRequireLogin')->willReturn(true);
        $_SERVER['PHP_AUTH_USER'] = 'user1';
        $_SERVER['PHP_AUTH_PW']   = 'invalid_password';
        $this->replication_http_user_authenticator->method('authenticate')
            ->willThrowException(new Git_RemoteServer_NotFoundException(2));
        $_SERVER['REMOTE_ADDR'] = '2001:db8::3';
        $this->access_key_authenticator->method('getUser')->willReturn(null);
        $this->user_login_manager->method('authenticate')
            ->willThrowException(new User_InvalidPasswordException());

        $ask_auth_throwable_test = new RuntimeException('Thrown exception for test purposes');
        $this->ask_basic_authentication_challenge->method('askBasicAuthenticationChallenge')->willThrowException($ask_auth_throwable_test);

        $this->expectExceptionObject($ask_auth_throwable_test);

        $this->http_access_control->getUser($this->git_repository, $this->git_operation);
        self::assertTrue($this->logger->hasDebugRecords());
    }

    public function testAuthenticationIsRequestedWhenEmptyCredentialIsGiven(): void
    {
        $this->forge_access->method('doesPlatformRequireLogin')->willReturn(true);
        $_SERVER['PHP_AUTH_USER'] = '';
        $_SERVER['PHP_AUTH_PW']   = '';

        $ask_auth_throwable_test = new RuntimeException('Thrown exception for test purposes');
        $this->ask_basic_authentication_challenge->method('askBasicAuthenticationChallenge')->willThrowException($ask_auth_throwable_test);

        $this->expectExceptionObject($ask_auth_throwable_test);

        $this->http_access_control->getUser($this->git_repository, $this->git_operation);
        self::assertTrue($this->logger->hasDebugRecords());
    }

    public function testNoAuthenticationIsRequiredForAReadAccessOfPublicRepoOnAnInstanceAccessibleToAnonymous(): void
    {
        $this->forge_access->method('doesPlatformRequireLogin')->willReturn(false);
        $this->git_operation->method('isWrite')->willReturn(false);
        $this->permissions_manager->method('getAuthorizedUgroupIds')->willReturn([ProjectUGroup::ANONYMOUS]);

        self::assertNull($this->http_access_control->getUser($this->git_repository, $this->git_operation));
        self::assertFalse($this->logger->hasDebugRecords());
    }
}
