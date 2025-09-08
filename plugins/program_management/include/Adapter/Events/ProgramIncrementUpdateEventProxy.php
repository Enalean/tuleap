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
use Tuleap\ProgramManagement\Domain\Events\PendingIterationCreation;
use Tuleap\ProgramManagement\Domain\Events\ProgramIncrementUpdateEvent;
use Tuleap\ProgramManagement\Domain\Program\Backlog\AsynchronousCreation\ChangesetIdentifier;
use Tuleap\ProgramManagement\Domain\Program\Backlog\AsynchronousCreation\DomainChangeset;
use Tuleap\ProgramManagement\Domain\Program\Backlog\AsynchronousCreation\VerifyIsChangeset;
use Tuleap\ProgramManagement\Domain\Program\Backlog\Iteration\VerifyIsIteration;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\ProgramIncrementIdentifier;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\ProgramIncrementNotFoundException;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\VerifyIsProgramIncrement;
use Tuleap\ProgramManagement\Domain\VerifyIsVisibleArtifact;
use Tuleap\ProgramManagement\Domain\Workspace\DomainUser;
use Tuleap\ProgramManagement\Domain\Workspace\UserIdentifier;
use Tuleap\ProgramManagement\Domain\Workspace\VerifyIsUser;
use Tuleap\Queue\WorkerEvent;

/**
 * I am a proxy to a certain kind of WorkerEvent.
 * @see WorkerEvent
 * @psalm-immutable
 */
final class ProgramIncrementUpdateEventProxy implements ProgramIncrementUpdateEvent
{
    /**
     * @var PendingIterationCreation[]
     */
    private array $iterations;

    private function __construct(
        private ProgramIncrementIdentifier $program_increment,
        private UserIdentifier $user,
        private ChangesetIdentifier $changeset,
        private ChangesetIdentifier $old_changeset,
        PendingIterationCreation ...$iterations,
    ) {
        $this->iterations = $iterations;
    }

    public static function fromWorkerEvent(
        LoggerInterface $logger,
        VerifyIsUser $user_verifier,
        VerifyIsProgramIncrement $program_increment_verifier,
        VerifyIsVisibleArtifact $visibility_verifier,
        VerifyIsIteration $iteration_verifier,
        VerifyIsChangeset $changeset_verifier,
        WorkerEvent $event,
    ): ?self {
        $event_name = $event->getEventName();
        if ($event_name !== self::TOPIC) {
            return null;
        }
        $payload = $event->getPayload();
        if (! isset($payload['program_increment_id'], $payload['user_id'], $payload['changeset_id'], $payload['old_changeset_id'], $payload['iterations'])) {
            $logger->warning("The payload for $event_name seems to be malformed, ignoring");
            $logger->debug("Malformed payload for $event_name: " . var_export($payload, true));
            return null;
        }
        $user_id              = $payload['user_id'];
        $program_increment_id = $payload['program_increment_id'];
        $changeset_id         = $payload['changeset_id'];
        $old_changeset_id     = $payload['old_changeset_id'];
        $iterations           = $payload['iterations'];
        $user                 = DomainUser::fromId($user_verifier, $payload['user_id']);
        if (! $user) {
            self::logInvalidData($logger, $program_increment_id, $user_id, $changeset_id, $old_changeset_id);
            return null;
        }
        try {
            $program_increment = ProgramIncrementIdentifier::fromId(
                $program_increment_verifier,
                $visibility_verifier,
                $program_increment_id,
                $user
            );
        } catch (ProgramIncrementNotFoundException $e) {
            $logger->debug(sprintf('Program increment #%d is no longer valid, skipping update', $program_increment_id));
            return null;
        }
        $changeset     = DomainChangeset::fromId($changeset_verifier, $changeset_id);
        $old_changeset = DomainChangeset::fromId($changeset_verifier, $old_changeset_id);
        if (! $changeset || ! $old_changeset) {
            self::logInvalidData($logger, $program_increment_id, $user_id, $changeset_id, $old_changeset_id);
            return null;
        }
        $pending_iterations = self::buildPendingIterations(
            $logger,
            $iteration_verifier,
            $visibility_verifier,
            $changeset_verifier,
            $user,
            $iterations
        );

        return new self($program_increment, $user, $changeset, $old_changeset, ...$pending_iterations);
    }

    private static function logInvalidData(
        LoggerInterface $logger,
        int $program_increment_id,
        int $user_id,
        int $changeset_id,
        int $old_changeset_id,
    ): void {
        $logger->error(
            sprintf(
                'Invalid data given in payload, skipping program increment update for artifact #%d, user #%d and changeset #%d (previous changeset id #%d)',
                $program_increment_id,
                $user_id,
                $changeset_id,
                $old_changeset_id
            )
        );
    }

    private static function buildPendingIterations(
        LoggerInterface $logger,
        VerifyIsIteration $iteration_verifier,
        VerifyIsVisibleArtifact $visibility_verifier,
        VerifyIsChangeset $changeset_verifier,
        UserIdentifier $user,
        array $iterations,
    ): array {
        $pending_iterations = [];
        foreach ($iterations as $iteration_payload) {
            $iteration_id       = $iteration_payload['id'];
            $iteration_creation = PendingIterationCreation::fromIds(
                $iteration_verifier,
                $visibility_verifier,
                $changeset_verifier,
                $iteration_id,
                $iteration_payload['changeset_id'],
                $user
            );
            if ($iteration_creation) {
                $pending_iterations[] = $iteration_creation;
            } else {
                $logger->debug(
                    sprintf('Iteration #%d is no longer valid, skipping creation', $iteration_id)
                );
            }
        }
        return $pending_iterations;
    }

    #[\Override]
    public function getProgramIncrement(): ProgramIncrementIdentifier
    {
        return $this->program_increment;
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

    #[\Override]
    public function getOldChangeset(): ChangesetIdentifier
    {
        return $this->old_changeset;
    }

    #[\Override]
    public function getIterations(): array
    {
        return $this->iterations;
    }
}
