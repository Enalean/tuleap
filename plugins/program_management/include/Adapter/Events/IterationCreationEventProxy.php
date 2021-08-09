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
use Tuleap\ProgramManagement\Domain\Events\IterationCreationEvent;
use Tuleap\Queue\WorkerEvent;

/**
 * I am a proxy to a certain kind of WorkerEvent. If it changes, I will protect classes that depend on me from changes,
 * and I will be the only one to change.
 * @see WorkerEvent
 * @psalm-immutable
 */
final class IterationCreationEventProxy implements IterationCreationEvent
{
    private int $artifact_id;
    private int $user_id;

    private function __construct(int $artifact_id, int $user_id)
    {
        $this->artifact_id = $artifact_id;
        $this->user_id     = $user_id;
    }

    public static function fromWorkerEvent(LoggerInterface $logger, WorkerEvent $event): ?self
    {
        $event_name = $event->getEventName();
        if ($event_name !== self::TOPIC) {
            return null;
        }
        $payload = $event->getPayload();
        if (! isset($payload['artifact_id'], $payload['user_id'])) {
            $logger->warning("The payload for $event_name seems to be malformed, ignoring");
            $logger->debug("Malformed payload for $event_name: " . var_export($payload, true));
            return null;
        }
        return new self($payload['artifact_id'], $payload['user_id']);
    }

    public function getArtifactId(): int
    {
        return $this->artifact_id;
    }

    public function getUserId(): int
    {
        return $this->user_id;
    }
}
