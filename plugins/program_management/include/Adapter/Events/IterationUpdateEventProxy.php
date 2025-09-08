<?php
/**
 * Copyright (c) Enalean, 2021 - present. All Rights Reserved.
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
 *
 */

declare(strict_types=1);

namespace Tuleap\ProgramManagement\Adapter\Events;

use Psr\Log\LoggerInterface;
use Tuleap\ProgramManagement\Domain\Events\IterationUpdateEvent;
use Tuleap\ProgramManagement\Domain\Program\Backlog\AsynchronousCreation\ChangesetIdentifier;
use Tuleap\ProgramManagement\Domain\Program\Backlog\AsynchronousCreation\DomainChangeset;
use Tuleap\ProgramManagement\Domain\Program\Backlog\AsynchronousCreation\VerifyIsChangeset;
use Tuleap\ProgramManagement\Domain\Program\Backlog\Iteration\IterationIdentifier;
use Tuleap\ProgramManagement\Domain\Program\Backlog\Iteration\VerifyIsIteration;
use Tuleap\ProgramManagement\Domain\VerifyIsVisibleArtifact;
use Tuleap\ProgramManagement\Domain\Workspace\DomainUser;
use Tuleap\ProgramManagement\Domain\Workspace\UserIdentifier;
use Tuleap\ProgramManagement\Domain\Workspace\VerifyIsUser;
use Tuleap\Queue\WorkerEvent;

/**
 * @psalm-immutable
 */
final class IterationUpdateEventProxy implements IterationUpdateEvent
{
    private function __construct(
        private IterationIdentifier $iteration,
        private UserIdentifier $user,
        private ChangesetIdentifier $changeset,
    ) {
    }

    public static function fromWorkerEvent(
        LoggerInterface $logger,
        VerifyIsUser $user_verififer,
        VerifyIsIteration $iteration_verifier,
        VerifyIsChangeset $changeset_verifier,
        VerifyIsVisibleArtifact $artifact_visibility_verifier,
        WorkerEvent $event,
    ): ?self {
        $event_name = $event->getEventName();
        if ($event_name !== self::TOPIC) {
            return null;
        }

        $payload = $event->getPayload();
        if (! isset($payload['iteration_id'], $payload['user_id'], $payload['changeset_id'])) {
            $logger->warning("The payload for $event_name seems to be malformed, ignoring");
            $logger->debug("Malformed payload for $event_name: " . var_export($payload, true));
            return null;
        }

        $user_id      = $payload['user_id'];
        $iteration_id = $payload['iteration_id'];
        $changeset_id = $payload['changeset_id'];

        $user = DomainUser::fromId($user_verififer, $user_id);
        if (! $user) {
            $logger->error(
                sprintf(
                    'User #%d, who updates the iteration #%d is no longer valid',
                    $user_id,
                    $iteration_id
                )
            );
            return null;
        }

        $iteration = IterationIdentifier::fromId(
            $iteration_verifier,
            $artifact_visibility_verifier,
            $iteration_id,
            $user
        );

        if (! $iteration) {
            $logger->debug(sprintf('Iteration #%d is no longer valid, skipping update', $iteration_id));
            return null;
        }

        $changeset = DomainChangeset::fromId($changeset_verifier, $changeset_id);
        if (! $changeset) {
            $logger->debug(sprintf('Changeset from iteration #%d is no longer valid, skipping update', $iteration_id));
            return null;
        }

        return new self($iteration, $user, $changeset);
    }

    #[\Override]
    public function getIteration(): IterationIdentifier
    {
        return $this->iteration;
    }

    #[\Override]
    public function getUser(): UserIdentifier
    {
        return $this->user;
    }

    #[\Override]
    public function getChangeset(): ChangesetIdentifier
    {
        return $this->changeset;
    }
}
