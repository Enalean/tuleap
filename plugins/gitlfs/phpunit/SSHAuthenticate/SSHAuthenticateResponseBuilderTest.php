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
 *
 */

namespace Tuleap\GitLFS\SSHAuthenticate;

use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Tuleap\Authentication\SplitToken\SplitToken;
use Tuleap\Authentication\SplitToken\SplitTokenVerificationString;
use Tuleap\Cryptography\ConcealedString;
use Tuleap\GitLFS\Authorization\User\Operation\UserOperation;
use Tuleap\GitLFS\Authorization\User\UserTokenCreator;

class SSHAuthenticateResponseBuilderTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    private $token_creator;
    private $user;
    private $repository;
    private $response_builder;
    private $current_time;

    protected function setUp() : void
    {
        parent::setUp();
        $this->token_creator = Mockery::mock(UserTokenCreator::class);
        $this->response_builder = new SSHAuthenticateResponseBuilder(
            $this->token_creator
        );

        $this->user = Mockery::mock(\PFUser::class);

        $this->repository = Mockery::mock(\GitRepository::class);
        $this->repository->shouldReceive('getFullHTTPUrlWithDotGit')->andReturns('https://lfs-server/foo/bar');

        $this->current_time = new \DateTimeImmutable();
    }

    public function testReturnsAResponseConformToSpec()
    {
        $this->token_creator->shouldReceive('createUserAuthorizationToken')->andReturns(
            Mockery::mock(
                SplitToken::class,
                [
                    'getId' => 100,
                    'getVerificationString' => Mockery::mock(
                        SplitTokenVerificationString::class,
                        [
                            'getString' => new ConcealedString('bar'),
                        ]
                    )
                ]
            )
        );

        $this->response_builder = $this->response_builder->getResponse(
            $this->repository,
            $this->user,
            \Mockery::mock(UserOperation::class),
            $this->current_time
        );

        $this->assertEquals(
            [
                'href'       => 'https://lfs-server/foo/bar/info/lfs',
                'expires_in' => 600,
                'header'     => [
                    'Authorization' => 'RemoteAuth 100.626172'
                ],
            ],
            $this->response_builder->jsonSerialize()
        );
    }

    public function testItCreatesTheToken()
    {
        $user_operation = \Mockery::mock(UserOperation::class);
        $this->token_creator->shouldReceive('createUserAuthorizationToken')->with(
            $this->repository,
            Mockery::on(function (\DateTimeImmutable $expiration_time) {
                if ($expiration_time->getTimestamp() === $this->current_time->getTimestamp() + SSHAuthenticateResponseBuilder::EXPIRES_IN_SECONDS) {
                    return true;
                }
                return false;
            }),
            $this->user,
            $user_operation
        )->andReturns(Mockery::mock(SplitToken::class));

        $this->response_builder->getResponse(
            $this->repository,
            $this->user,
            $user_operation,
            $this->current_time
        );
    }
}
