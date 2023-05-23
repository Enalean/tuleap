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

use ColinODell\PsrTestLogger\TestLogger;
use Tuleap\ProgramManagement\Domain\Events\IterationUpdateEvent;
use Tuleap\ProgramManagement\Tests\Stub\VerifyIsChangesetStub;
use Tuleap\ProgramManagement\Tests\Stub\VerifyIsIterationStub;
use Tuleap\ProgramManagement\Tests\Stub\VerifyIsUserStub;
use Tuleap\ProgramManagement\Tests\Stub\VerifyIsVisibleArtifactStub;
use Tuleap\Queue\WorkerEvent;
use Tuleap\Test\PHPUnit\TestCase;

final class IterationUpdateEventProxyTest extends TestCase
{
    private const ITERATION_ID = 20;
    private const CHANGESET_ID = 100;
    private const USER_ID      = 300;
    private TestLogger $logger;
    private VerifyIsUserStub $user_verifier;
    private VerifyIsIterationStub $iteration_verifier;
    private VerifyIsChangesetStub $changeset_verifier;
    private VerifyIsVisibleArtifactStub $artifact_visibility_verifier;

    protected function setUp(): void
    {
        $this->logger                       = new TestLogger();
        $this->user_verifier                = VerifyIsUserStub::withValidUser();
        $this->iteration_verifier           = VerifyIsIterationStub::withValidIteration();
        $this->changeset_verifier           = VerifyIsChangesetStub::withValidChangeset();
        $this->artifact_visibility_verifier = VerifyIsVisibleArtifactStub::withAlwaysVisibleArtifacts();
    }

    private function getIterationUpdateEvent(WorkerEvent $event): ?IterationUpdateEventProxy
    {
        return IterationUpdateEventProxy::fromWorkerEvent(
            $this->logger,
            $this->user_verifier,
            $this->iteration_verifier,
            $this->changeset_verifier,
            $this->artifact_visibility_verifier,
            $event
        );
    }

    private function getValidWorkerEvent(): WorkerEvent
    {
        return new WorkerEvent(
            $this->logger,
            [
                'event_name' => IterationUpdateEvent::TOPIC,
                'payload'    => [
                    'user_id'      => self::USER_ID,
                    'iteration_id' => self::ITERATION_ID,
                    'changeset_id' => self::CHANGESET_ID,
                ],
            ]
        );
    }

    public function testItReturnsNullIfTheEventNameIsNotValid(): void
    {
        $bad_event_name = 'need.moula.bad.event';
        $event          = new WorkerEvent(
            $this->logger,
            [
                'event_name' => $bad_event_name,
                'payload'    => [
                    'user_id'      => self::USER_ID,
                    'iteration_id' => self::ITERATION_ID,
                    'changeset_id' => self::CHANGESET_ID,
                ],
            ]
        );

        $iteration_update_event = $this->getIterationUpdateEvent($event);
        self::assertNull($iteration_update_event);

        self::assertFalse($this->logger->hasWarningRecords());
        self::assertFalse($this->logger->hasDebugRecords());
    }

    public function testItReturnsNullIfTheIterationIdKeyInTheEventPayloadIsMissing(): void
    {
        $event = new WorkerEvent(
            $this->logger,
            [
                'event_name' => IterationUpdateEvent::TOPIC,
                'payload'    => [
                    'user_id'           => self::USER_ID,
                    'program_increment' => 1,
                    'changeset_id'      => self::CHANGESET_ID,
                ],
            ]
        );

        $iteration_update_event = $this->getIterationUpdateEvent($event);
        self::assertNull($iteration_update_event);

        self::assertTrue($this->logger->hasWarningRecords());
        self::assertTrue($this->logger->hasDebugRecords());
    }

    public function testItReturnsNullIfTheUserIdKeyInTheEventPayloadIsMissing(): void
    {
        $event = new WorkerEvent(
            $this->logger,
            [
                'event_name' => IterationUpdateEvent::TOPIC,
                'payload'    => [
                    'iteration_id' => self::ITERATION_ID,
                    'changeset_id' => self::CHANGESET_ID,
                ],
            ]
        );

        $iteration_update_event = $this->getIterationUpdateEvent($event);
        self::assertNull($iteration_update_event);

        self::assertTrue($this->logger->hasWarningRecords());
        self::assertTrue($this->logger->hasDebugRecords());
    }

    public function testItReturnsNullIfTheChangesetIdKeyInTheEventPayloadIsMissing(): void
    {
        $event = new WorkerEvent(
            $this->logger,
            [
                'event_name' => IterationUpdateEvent::TOPIC,
                'payload'    => [
                    'user_id'      => self::USER_ID,
                    'iteration_id' => self::ITERATION_ID,
                    'wololo'       => 'No Changeset',
                ],
            ]
        );

        $iteration_update_event = $this->getIterationUpdateEvent($event);
        self::assertNull($iteration_update_event);

        self::assertTrue($this->logger->hasWarningRecords());
        self::assertTrue($this->logger->hasDebugRecords());
    }

    public function testItReturnsNullIfTheUserIsNoLongerValid(): void
    {
        $this->user_verifier = VerifyIsUserStub::withNotValidUser();

        $event = $this->getValidWorkerEvent();

        $iteration_update_event = $this->getIterationUpdateEvent($event);
        self::assertNull($iteration_update_event);

        self::assertTrue($this->logger->hasErrorRecords());
    }

    public function testItReturnsNullIfTheIterationIsNoLongerValid(): void
    {
        $this->iteration_verifier = VerifyIsIterationStub::withNotIteration();

        $event = $this->getValidWorkerEvent();

        $iteration_update_event = $this->getIterationUpdateEvent($event);

        self::assertNull($iteration_update_event);

        self::assertTrue(
            $this->logger->hasDebug(sprintf('Iteration #%d is no longer valid, skipping update', self::ITERATION_ID))
        );
    }

    public function testItReturnsNullIfTheChangesetIsNoLongerValid(): void
    {
        $this->changeset_verifier = VerifyIsChangesetStub::withNotValidChangeset();

        $event = $this->getValidWorkerEvent();

        $iteration_update_event = $this->getIterationUpdateEvent($event);

        self::assertNull($iteration_update_event);

        self::assertFalse($this->logger->hasWarningRecords());
        self::assertTrue(
            $this->logger->hasDebug(
                sprintf('Changeset from iteration #%d is no longer valid, skipping update', self::ITERATION_ID)
            )
        );
        self::assertFalse($this->logger->hasErrorRecords());
    }

    public function testItReturnsTheIterationUpdateEventProxy(): void
    {
        $event = $this->getValidWorkerEvent();

        $iteration_update_event = $this->getIterationUpdateEvent($event);

        self::assertSame(self::USER_ID, $iteration_update_event?->getUser()->getId());
        self::assertSame(self::CHANGESET_ID, $iteration_update_event?->getChangeset()->getId());
        self::assertSame(self::ITERATION_ID, $iteration_update_event?->getIteration()->getId());

        self::assertFalse($this->logger->hasWarningRecords());
        self::assertFalse($this->logger->hasDebugRecords());
        self::assertFalse($this->logger->hasErrorRecords());
    }
}
