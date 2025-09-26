<?php
/**
 * Copyright (c) Enalean, 2016 - Present. All Rights Reserved.
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

namespace Tuleap\Git\Webhook;

use ColinODell\PsrTestLogger\TestLogger;
use Tuleap\Git\Tests\Builders\GitRepositoryTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\Stubs\User\Avatar\ProvideUserAvatarUrlStub;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Webhook\Emitter;
use UserHelper;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class WebhookRequestSenderTest extends TestCase
{
    #[\Override]
    protected function setUp(): void
    {
        UserHelper::setInstance($this->createMock(UserHelper::class));
    }

    #[\Override]
    protected function tearDown(): void
    {
        UserHelper::clearInstance();
    }

    public function testItSendsWebhooks(): void
    {
        $webhook_factory = $this->createMock(WebhookFactory::class);
        $webhook_emitter = $this->createMock(Emitter::class);
        $logger          = new TestLogger();

        $sender = new WebhookRequestSender($webhook_emitter, $webhook_factory, $logger, ProvideUserAvatarUrlStub::build());

        $repository = GitRepositoryTestBuilder::aProjectRepository()->build();
        $user       = UserTestBuilder::aUser()->build();
        $oldrev     = 'oldrev';
        $newrev     = 'newrev';
        $refname    = 'refs/heads/master';

        $web_hook_01 = new Webhook(1, 1, 'url_01');
        $web_hook_02 = new Webhook(2, 1, 'url_02');

        $webhook_factory->method('getWebhooksForRepository')->willReturn([$web_hook_01, $web_hook_02]);

        $webhook_emitter->expects($this->once())->method('emit');

        $sender->sendRequests($repository, $user, $oldrev, $newrev, $refname);
        self::assertTrue($logger->hasInfoRecords());
    }
}
