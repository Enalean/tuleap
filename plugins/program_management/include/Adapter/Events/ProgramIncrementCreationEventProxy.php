<?php
/**
 * Copyright (c) Enalean, 2021-Present. All Rights Reserved.
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

namespace Tuleap\ProgramManagement\Adapter\Events;

use Psr\Log\LoggerInterface;
use Tuleap\ProgramManagement\Adapter\Workspace\UserProxy;
use Tuleap\ProgramManagement\Domain\Events\ProgramIncrementCreationEvent;
use Tuleap\ProgramManagement\Domain\Workspace\UserIdentifier;
use Tuleap\Queue\WorkerEvent;

/**
 * I am a proxy to a certain kind of WorkerEvent.
 * @see WorkerEvent
 * @psalm-immutable
 */
final class ProgramIncrementCreationEventProxy implements ProgramIncrementCreationEvent
{
    private function __construct(private int $artifact_id, private UserIdentifier $user, private int $changeset_id)
    {
    }

    public static function fromWorkerEvent(
        LoggerInterface $logger,
        \UserManager $user_manager,
        WorkerEvent $event,
    ): ?self {
        $event_name = $event->getEventName();
        if ($event_name !== self::TOPIC) {
            return null;
        }
        $payload = $event->getPayload();
        if (! isset($payload['artifact_id'], $payload['user_id'], $payload['changeset_id'])) {
            $logger->warning("The payload for $event_name seems to be malformed, ignoring");
            $logger->debug("Malformed payload for $event_name: " . var_export($payload, true));
            return null;
        }
        $user_id = $payload['user_id'];
        $pfuser  = $user_manager->getUserById($user_id);
        if (! $pfuser) {
            $logger->error("Could not find user with id #$user_id");
            return null;
        }
        $user = UserProxy::buildFromPFUser($pfuser);

        return new self($payload['artifact_id'], $user, $payload['changeset_id']);
    }

    #[\Override]
    public function getArtifactId(): int
    {
        return $this->artifact_id;
    }

    #[\Override]
    public function getUser(): UserIdentifier
    {
        return $this->user;
    }

    #[\Override]
    public function getChangesetId(): int
    {
        return $this->changeset_id;
    }
}
