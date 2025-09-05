<?php
/**
 * Copyright (c) Enalean, 2022 - present. All Rights Reserved.
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
use Tuleap\ProgramManagement\Domain\Events\TeamSynchronizationEvent;
use Tuleap\Queue\WorkerEvent;

final class TeamSynchronizationEventProxy implements TeamSynchronizationEvent
{
    private function __construct(
        private int $program_id,
        private int $team_id,
        private int $user_id,
    ) {
    }

    public static function fromWorkerEvent(LoggerInterface $logger, WorkerEvent $worker_event): ?self
    {
        $event_name = $worker_event->getEventName();
        if ($event_name !== self::TOPIC) {
            return null;
        }

        $payload = $worker_event->getPayload();
        if (! isset($payload['program_id'], $payload['team_id'], $payload['user_id'])) {
            $logger->warning("The payload for $event_name seems to be malformed, ignoring");
            $logger->debug("Malformed payload for $event_name: " . var_export($payload, true));
            return null;
        }

        return new self(
            $payload['program_id'],
            $payload['team_id'],
            $payload['user_id'],
        );
    }

    #[\Override]
    public function getProgramId(): int
    {
        return $this->program_id;
    }

    #[\Override]
    public function getTeamId(): int
    {
        return $this->team_id;
    }

    #[\Override]
    public function getUserId(): int
    {
        return $this->user_id;
    }
}
