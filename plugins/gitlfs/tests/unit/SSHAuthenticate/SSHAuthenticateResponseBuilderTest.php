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
 *
 */

declare(strict_types=1);

namespace Tuleap\GitLFS\SSHAuthenticate;

use Tuleap\Authentication\SplitToken\SplitToken;
use Tuleap\Authentication\SplitToken\SplitTokenVerificationString;
use Tuleap\Cryptography\ConcealedString;
use Tuleap\GitLFS\Authorization\User\Operation\UserOperation;
use Tuleap\GitLFS\Authorization\User\UserTokenCreator;
use Tuleap\Test\Builders\UserTestBuilder;

final class SSHAuthenticateResponseBuilderTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private \PHPUnit\Framework\MockObject\MockObject&UserTokenCreator $token_creator;
    private \PFUser $user;
    private \PHPUnit\Framework\MockObject\MockObject&\GitRepository $repository;
    private SSHAuthenticateResponseBuilder $response_builder;
    private \DateTimeImmutable $current_time;

    protected function setUp(): void
    {
        parent::setUp();
        $this->token_creator    = $this->createMock(UserTokenCreator::class);
        $this->response_builder = new SSHAuthenticateResponseBuilder(
            $this->token_creator
        );

        $this->user = UserTestBuilder::aUser()->build();

        $this->repository = $this->createMock(\GitRepository::class);
        $this->repository->method('getFullHTTPUrlWithDotGit')->willReturn('https://lfs-server/foo/bar');

        $this->current_time = new \DateTimeImmutable('@10');
    }

    public function testReturnsAResponseConformToSpec(): void
    {
        $verification_string = $this->createMock(SplitTokenVerificationString::class);
        $verification_string->method('getString')->willReturn(new ConcealedString('bar'));
        $this->token_creator->method('createUserAuthorizationToken')->willReturn(
            new SplitToken(100, $verification_string)
        );

        $response = $this->response_builder->getResponse(
            $this->repository,
            $this->user,
            $this->createMock(UserOperation::class),
            $this->current_time
        );

        self::assertEquals(
            [
                'href'       => 'https://lfs-server/foo/bar/info/lfs',
                'expires_in' => 600,
                'header'     => [
                    'Authorization' => 'RemoteAuth 100.626172',
                ],
            ],
            $response->jsonSerialize()
        );
    }

    public function testItCreatesTheToken(): void
    {
        $user_operation = $this->createMock(UserOperation::class);
        $this->token_creator->expects(self::atLeastOnce())->method('createUserAuthorizationToken')->with(
            $this->repository,
            (new \DateTimeImmutable())->setTimestamp($this->current_time->getTimestamp() + SSHAuthenticateResponseBuilder::EXPIRES_IN_SECONDS),
            $this->user,
            $user_operation
        )->willReturn($this->createMock(SplitToken::class));

        $this->response_builder->getResponse(
            $this->repository,
            $this->user,
            $user_operation,
            $this->current_time
        );
    }
}
