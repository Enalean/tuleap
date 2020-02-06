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
 */

namespace Tuleap\Tracker\Artifact\Changeset\PostCreation;

use Tracker_Artifact_Changeset;
use Tuleap\Webhook\Emitter as WebhookEmitter;
use Tuleap\Tracker\Webhook\WebhookFactory;

final class WebhookNotificationTask implements PostCreationTask
{
    /**
     * @var \Psr\Log\LoggerInterface
     */
    private $logger;
    /**
     * @var WebhookEmitter
     */
    private $webhook_emitter;
    /**
     * @var WebhookFactory
     */
    private $webhook_factory;

    public function __construct(\Psr\Log\LoggerInterface $logger, WebhookEmitter $webhook_emitter, WebhookFactory $webhook_factory)
    {
        $this->logger          = $logger;
        $this->webhook_emitter = $webhook_emitter;
        $this->webhook_factory = $webhook_factory;
    }

    public function execute(Tracker_Artifact_Changeset $changeset)
    {
        $tracker  = $changeset->getTracker();
        $webhooks = $this->webhook_factory->getWebhooksForTracker($tracker);

        $this->logger->debug('Start processing of ' . count($webhooks) . ' webhook(s) for changeset #' . $changeset->getId());

        $payload = new \Tuleap\Tracker\Webhook\ArtifactPayload($changeset);
        $this->webhook_emitter->emit($payload, ...$webhooks);

        $this->logger->debug('All webhooks for changeset #' . $changeset->getId() . 'has been been processed');
    }
}
