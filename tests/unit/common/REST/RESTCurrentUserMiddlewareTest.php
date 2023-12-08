<?php
/**
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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

use Exception;
use PFUser;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Rest_Exception_InvalidTokenException;
use Tuleap\Authentication\SplitToken\SplitTokenException;
use Tuleap\Http\HTTPFactoryBuilder;
use Tuleap\Http\Server\NullServerRequest;
use Tuleap\Request\ForbiddenException;
use Tuleap\User\AccessKey\AccessKeyException;
use User_StatusInvalidException;

final class RESTCurrentUserMiddlewareTest extends \Tuleap\Test\PHPUnit\TestCase
{
    public function testRequestIsProcessedWhenCurrentUserIsNotRejected(): void
    {
        $basic_rest_auth   = $this->createMock(BasicAuthentication::class);
        $rest_user_manager = $this->createMock(UserManager::class);

        $rest_current_user_middleware = new RESTCurrentUserMiddleware($rest_user_manager, $basic_rest_auth);

        $expected_user = $this->createMock(PFUser::class);
        $basic_rest_auth->method('__isAllowed');
        $rest_user_manager->method('getCurrentUser')->willReturn($expected_user);

        $request_handler   = $this->createMock(RequestHandlerInterface::class);
        $expected_response = HTTPFactoryBuilder::responseFactory()->createResponse();
        $request_handler->method('handle')->with(self::callback(
            static function (ServerRequestInterface $request) use ($rest_current_user_middleware, $expected_user): bool {
                return $rest_current_user_middleware->getCurrentRequestUser($request) === $expected_user;
            }
        ))->willReturn($expected_response);

        $server_request = new NullServerRequest();
        $response       = $rest_current_user_middleware->process(
            $server_request,
            $request_handler
        );
        self::assertSame($expected_response, $response);
    }

    /**
     * @dataProvider restAuthenticationExceptionProvider
     */
    public function testRequestIsRejectedWhenTheCurrentUserCanNotBeAuthenticated(Exception $exception): void
    {
        $basic_rest_auth   = $this->createMock(BasicAuthentication::class);
        $rest_user_manager = $this->createMock(UserManager::class);

        $rest_current_user_middleware = new RESTCurrentUserMiddleware($rest_user_manager, $basic_rest_auth);

        $basic_rest_auth->method('__isAllowed');
        $rest_user_manager->method('getCurrentUser')->willThrowException($exception);

        $this->expectException(ForbiddenException::class);
        $rest_current_user_middleware->process(
            $this->createMock(ServerRequestInterface::class),
            $this->createMock(RequestHandlerInterface::class)
        );
    }

    public static function restAuthenticationExceptionProvider(): array
    {
        return [
            [new User_StatusInvalidException()],
            [
                new class extends AccessKeyException {
                },
            ],
            [new Rest_Exception_InvalidTokenException()],
            [
                new class extends SplitTokenException {
                },
            ],
        ];
    }
}
