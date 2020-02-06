<?php
/**
 * Copyright (c) Enalean, 2016 - 2018. All Rights Reserved.
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

use GitRepository;
use PFUser;
use Psr\Log\LoggerInterface;
use Tuleap\Webhook\Emitter;

class WebhookRequestSender
{
    /**
     * @var Emitter
     */
    private $webhook_emitter;
    /**
     * @var LoggerInterface
     */
    private $logger;
    /**
     * @var WebhookFactory
     */
    private $webhook_factory;

    public function __construct(
        Emitter $webhook_emitter,
        WebhookFactory $webhook_factory,
        LoggerInterface $logger
    ) {
        $this->webhook_emitter = $webhook_emitter;
        $this->webhook_factory = $webhook_factory;
        $this->logger          = $logger;
    }

    public function sendRequests(GitRepository $repository, PFUser $user, $oldrev, $newrev, $refname)
    {
        $web_hooks = $this->webhook_factory->getWebhooksForRepository($repository);
        $payload   = new PushPayload($repository, $user, $oldrev, $newrev, $refname);
        $this->logger->info('Processing webhooks for repository #' . $repository->getId());
        $this->webhook_emitter->emit($payload, ...$web_hooks);
    }
}
