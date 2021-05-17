<?php
/**
 * Copyright (c) Enalean, 2021 - Present. All Rights Reserved.
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

namespace Tuleap\Gitlab\Repository\Webhook\Bot;

use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Tuleap\Gitlab\API\ClientWrapper;
use Tuleap\Gitlab\API\GitlabRequestException;
use Tuleap\Gitlab\Repository\GitlabRepository;
use Tuleap\Gitlab\Test\Builder\CredentialsTestBuilder;

class CommentSenderTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var \Tuleap\Gitlab\API\Credentials
     */
    private $credentials;
    /**
     * @var GitlabRepository
     */
    private $repository;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|ClientWrapper
     */
    private $client;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|InvalidCredentialsNotifier
     */
    private $notifier;
    /**
     * @var CommentSender
     */
    private $sender;

    protected function setUp(): void
    {
        $this->credentials = CredentialsTestBuilder::get()->build();

        $this->repository = new GitlabRepository(
            1,
            2,
            'winter-is-coming',
            'Need more blankets, we are going to freeze our asses',
            'the_full_url',
            new \DateTimeImmutable(),
            \Project::buildForTest(),
            false
        );

        $this->client   = Mockery::mock(ClientWrapper::class);
        $this->notifier = Mockery::mock(InvalidCredentialsNotifier::class);

        $this->sender = new CommentSender($this->client, $this->notifier);
    }

    public function testSendComment()
    {
        $this->client
            ->shouldReceive('postUrl')
            ->with($this->credentials, 'gitlab/api', [])
            ->once();

        $this->sender->sendComment($this->repository, $this->credentials, 'gitlab/api', []);
    }

    public function testNotifyAboutInvalidCredentials()
    {
        $exception = new GitlabRequestException(401, 'Unhautorized');

        $this->client
            ->shouldReceive('postUrl')
            ->with($this->credentials, 'gitlab/api', [])
            ->andThrow($exception);

        $this->notifier
            ->shouldReceive('notifyGitAdministratorsThatCredentialsAreInvalid')
            ->with($this->repository, $this->credentials)
            ->once();

        $this->expectExceptionObject($exception);

        $this->sender->sendComment($this->repository, $this->credentials, 'gitlab/api', []);
    }

    public function testDoesNotNotifyForOtherExceptionThan401()
    {
        $exception = new GitlabRequestException(404, 'Not found');

        $this->client
            ->shouldReceive('postUrl')
            ->with($this->credentials, 'gitlab/api', [])
            ->andThrow($exception);

        $this->notifier
            ->shouldReceive('notifyGitAdministratorsThatCredentialsAreInvalid')
            ->never();

        $this->expectExceptionObject($exception);

        $this->sender->sendComment($this->repository, $this->credentials, 'gitlab/api', []);
    }
}
