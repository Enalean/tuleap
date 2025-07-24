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

use Tuleap\Gitlab\API\ClientWrapper;
use Tuleap\Gitlab\API\Credentials;
use Tuleap\Gitlab\API\GitlabRequestException;
use Tuleap\Gitlab\Repository\GitlabRepositoryIntegration;
use Tuleap\Gitlab\Test\Builder\CredentialsTestBuilder;
use Tuleap\Test\Builders\ProjectTestBuilder;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class CommentSenderTest extends \Tuleap\Test\PHPUnit\TestCase
{
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&ClientWrapper
     */
    private $client;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&InvalidCredentialsNotifier
     */
    private $notifier;

    private Credentials $credentials;
    private GitlabRepositoryIntegration $integration;
    private CommentSender $sender;

    #[\Override]
    protected function setUp(): void
    {
        $this->credentials = CredentialsTestBuilder::get()->build();

        $this->integration = new GitlabRepositoryIntegration(
            1,
            2,
            'winter-is-coming',
            'Need more blankets, we are going to freeze our asses',
            'the_full_url',
            new \DateTimeImmutable(),
            ProjectTestBuilder::aProject()->build(),
            false
        );

        $this->client   = $this->createMock(ClientWrapper::class);
        $this->notifier = $this->createMock(InvalidCredentialsNotifier::class);

        $this->sender = new CommentSender($this->client, $this->notifier);
    }

    public function testSendComment(): void
    {
        $this->client
            ->expects($this->once())
            ->method('postUrl')
            ->with($this->credentials, 'gitlab/api', []);

        $this->sender->sendComment($this->integration, $this->credentials, 'gitlab/api', []);
    }

    public function testNotifyAboutInvalidCredentials(): void
    {
        $exception = new GitlabRequestException(401, 'Unhautorized');

        $this->client
            ->method('postUrl')
            ->with($this->credentials, 'gitlab/api', [])
            ->willThrowException($exception);

        $this->notifier
            ->expects($this->once())
            ->method('notifyGitAdministratorsThatCredentialsAreInvalid')
            ->with($this->integration, $this->credentials);

        $this->expectExceptionObject($exception);

        $this->sender->sendComment($this->integration, $this->credentials, 'gitlab/api', []);
    }

    public function testDoesNotNotifyForOtherExceptionThan401(): void
    {
        $exception = new GitlabRequestException(404, 'Not found');

        $this->client
            ->method('postUrl')
            ->with($this->credentials, 'gitlab/api', [])
            ->willThrowException($exception);

        $this->notifier
            ->expects($this->never())
            ->method('notifyGitAdministratorsThatCredentialsAreInvalid');

        $this->expectExceptionObject($exception);

        $this->sender->sendComment($this->integration, $this->credentials, 'gitlab/api', []);
    }
}
