<?php
/**
 * Copyright (c) Enalean, 2018-Present. All Rights Reserved.
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

namespace Tuleap\Tracker\Artifact\Changeset\PostCreation;

use Tracker_Artifact_Changeset;
use Tuleap\Tracker\Webhook\ArtifactPayloadBuilder;
use Tuleap\Tracker\Webhook\WebhookFactory;
use Tuleap\Webhook\Emitter as WebhookEmitter;

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
    /**
     * @var ArtifactPayloadBuilder
     */
    private $payload_builder;

    public function __construct(
        \Psr\Log\LoggerInterface $logger,
        WebhookEmitter $webhook_emitter,
        WebhookFactory $webhook_factory,
        ArtifactPayloadBuilder $payload_builder,
    ) {
        $this->logger          = $logger;
        $this->webhook_emitter = $webhook_emitter;
        $this->webhook_factory = $webhook_factory;
        $this->payload_builder = $payload_builder;
    }

    #[\Override]
    public function execute(Tracker_Artifact_Changeset $changeset, PostCreationTaskConfiguration $configuration): void
    {
        $tracker  = $changeset->getTracker();
        $webhooks = $this->webhook_factory->getWebhooksForTracker($tracker);

        $this->logger->debug('Start processing of ' . count($webhooks) . ' webhook(s) for changeset #' . $changeset->getId());

        $payload = $this->payload_builder->buildPayload($changeset);
        $this->webhook_emitter->emit($payload, ...$webhooks);

        $this->logger->debug('All webhooks for changeset #' . $changeset->getId() . 'has been been processed');
    }
}
