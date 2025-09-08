<?php
/**
 * Copyright (c) Enalean, 2024-Present. All Rights Reserved.
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

use Psr\Log\LoggerInterface;
use Tracker_Artifact_Changeset;
use Tuleap\Queue\QueueFactory;
use Tuleap\Queue\Worker;

final readonly class ActionsQueuer implements PostCreationActionsQueuer
{
    public function __construct(
        private QueueFactory $queue_factory,
    ) {
    }

    public static function build(LoggerInterface $logger): self
    {
        return new self(
            new QueueFactory($logger),
        );
    }

    #[\Override]
    public function queuePostCreation(Tracker_Artifact_Changeset $changeset, bool $send_notifications): void
    {
        $queue = $this->queue_factory->getPersistentQueue(Worker::EVENT_QUEUE_NAME);
        $queue->pushSinglePersistentMessage(
            AsynchronousActionsRunner::TOPIC,
            [
                'artifact_id'        => $changeset->getArtifact()->getId(),
                'changeset_id'       => (int) $changeset->getId(),
                'send_notifications' => $send_notifications,
            ]
        );
    }
}
