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

namespace Tuleap\Git\Webhook;

require_once __DIR__ . '/../../bootstrap.php';

use Tuleap\Webhook\Emitter;
use UserHelper;

class WebhookRequestSenderTest extends \PHPUnit\Framework\TestCase
{
    use \Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    protected function setUp(): void
    {
        parent::setUp();
        UserHelper::clearInstance();
        UserHelper::setInstance(\Mockery::spy(UserHelper::class));
    }

    protected function tearDown(): void
    {
        UserHelper::clearInstance();
        parent::tearDown();
    }

    public function testItSendsWebhooks(): void
    {
        $webhook_factory = \Mockery::mock(WebhookFactory::class);
        $webhook_emitter = \Mockery::mock(Emitter::class);
        $logger          = \Mockery::mock(\Psr\Log\LoggerInterface::class);

        $sender = new WebhookRequestSender($webhook_emitter, $webhook_factory, $logger);

        $repository = \Mockery::spy(\GitRepository::class);
        $user       = \Mockery::spy(\PFUser::class);
        $oldrev     = 'oldrev';
        $newrev     = 'newrev';
        $refname    = 'refs/heads/master';

        $web_hook_01 = new Webhook(1, 1, 'url_01');
        $web_hook_02 = new Webhook(2, 1, 'url_02');

        $webhook_factory->shouldReceive('getWebhooksForRepository')->andReturns([$web_hook_01, $web_hook_02]);

        $webhook_emitter->shouldReceive('emit')->once();
        $logger->shouldReceive('info')->once();

        $sender->sendRequests($repository, $user, $oldrev, $newrev, $refname);
    }
}
