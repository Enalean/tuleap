<?php
/**
 * Copyright (c) Enalean, 2020-Present. All Rights Reserved.
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

namespace Tuleap\REST;

use Luracast\Restler\Data\ApiMethodInfo;
use Luracast\Restler\InvalidAuthCredentials;
use Psr\Log\LoggerInterface;
use Rest_Exception_InvalidTokenException;
use Tuleap\Authentication\SplitToken\SplitTokenException;
use Tuleap\User\AccessKey\AccessKeyException;
use Tuleap\User\OAuth2\OAuth2Exception;
use User_LoginException;

final class RESTAuthenticationFlowIsAllowedTest extends \Tuleap\Test\PHPUnit\TestCase
{
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&UserManager
     */
    private $user_manager;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&LoggerInterface
     */
    private $logger;

    /**
     * @var RESTAuthenticationFlowIsAllowed
     */
    private $rest_authentication_flow;

    protected function setUp(): void
    {
        $this->user_manager = $this->createMock(UserManager::class);
        $this->logger       = $this->createMock(LoggerInterface::class);

        $this->rest_authentication_flow = new RESTAuthenticationFlowIsAllowed($this->user_manager, $this->logger);
    }

    protected function tearDown(): void
    {
        unset($_SERVER['REQUEST_METHOD']);
    }

    public function testAuthenticatedUserIsAllowed(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $this->user_manager->method('getCurrentUser')->willReturn(
            new \PFUser(['user_id' => 102, 'language_id' => 'en'])
        );
        $this->assertTrue($this->rest_authentication_flow->isAllowed($this->createMock(ApiMethodInfo::class)));
    }

    public function testAnonymousUserIsNotAllowed(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $anonymous_user            = new \PFUser(['language_id' => 'en']);
        $this->user_manager->method('getCurrentUser')->willReturn($anonymous_user);

        $this->assertFalse($this->rest_authentication_flow->isAllowed($this->createMock(ApiMethodInfo::class)));
    }

    public function testCallingAnOptionRouteIsAllowed(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'OPTIONS';
        $anonymous_user            = new \PFUser(['language_id' => 'en']);
        $this->user_manager->method('getCurrentUser')->willReturn($anonymous_user);

        $this->assertTrue($this->rest_authentication_flow->isAllowed($this->createMock(ApiMethodInfo::class)));
    }

    /**
     * @dataProvider dataProviderExceptionsAuthentication
     */
    public function testAllExceptionsNotRelatedToADevelopmentIssueIsProperlyReturnedToTheUserAndLogged(\Exception $exception, int $expected_code): void
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $this->user_manager->method('getCurrentUser')->willThrowException($exception);
        $this->logger->expects(self::once())->method('debug');

        $this->expectException(InvalidAuthCredentials::class);
        $this->expectExceptionCode($expected_code);
        $this->rest_authentication_flow->isAllowed($this->createMock(ApiMethodInfo::class));
    }

    public static function dataProviderExceptionsAuthentication(): array
    {
        return [
            [
                new class extends User_LoginException {
                },
                403,
            ],
            [
                new Rest_Exception_InvalidTokenException(),
                401,
            ],
            [
                new class extends AccessKeyException {
                },
                401,
            ],
            [
                new class extends \RuntimeException implements OAuth2Exception {
                },
                401,
            ],
            [
                new class extends SplitTokenException {
                },
                401,
            ],
        ];
    }

    public function testExceptionThrownDueToAnIssueInTheCodeIsNotSilenced(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $this->user_manager->method('getCurrentUser')->willThrowException(new \LogicException());

        $this->expectException(\LogicException::class);
        $this->rest_authentication_flow->isAllowed($this->createMock(ApiMethodInfo::class));
    }
}
