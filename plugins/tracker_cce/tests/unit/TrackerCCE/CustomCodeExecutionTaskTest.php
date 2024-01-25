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

namespace Tuleap\TrackerCCE;

use ColinODell\PsrTestLogger\TestLogger;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\TrackerCCE\Logs\ModuleLogLine;
use Tuleap\TrackerCCE\Stub\Administration\CheckModuleIsActivatedStub;
use Tuleap\TrackerCCE\Stub\Logs\SaveModuleLogStub;
use Tuleap\TrackerCCE\Stub\Notification\TrackerAdministratorNotificationSenderStub;
use Tuleap\TrackerCCE\Stub\WASM\WASMModuleCallerStub;
use Tuleap\TrackerCCE\Stub\WASM\WASMModulePathHelperStub;
use Tuleap\TrackerCCE\Stub\WASM\WASMResponseExecutorStub;
use Tuleap\TrackerCCE\WASM\WASMResponseRepresentation;
use Tuleap\Tracker\Test\Builders\ArtifactTestBuilder;
use Tuleap\Tracker\Test\Builders\ChangesetTestBuilder;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;
use Tuleap\Tracker\Test\Stub\Tracker\Webhook\ArtifactPayloadBuilderStub;
use Tuleap\User\CCEUser;

class CustomCodeExecutionTaskTest extends TestCase
{
    private const WASM_FILE = __FILE__;

    public function testItReturnsIfSubmitterIsCCEUser(): void
    {
        $logger    = new TestLogger();
        $caller    = WASMModuleCallerStub::withEmptyErrResult();
        $task      = new CustomCodeExecutionTask(
            $logger,
            ArtifactPayloadBuilderStub::withEmptyPayload(),
            WASMModulePathHelperStub::withPath(''),
            $caller,
            WASMResponseExecutorStub::buildOk(),
            SaveModuleLogStub::build(),
            CheckModuleIsActivatedStub::activated(),
            TrackerAdministratorNotificationSenderStub::build(),
        );
        $changeset = ChangesetTestBuilder::aChangeset('1')
            ->submittedBy(CCEUser::ID)
            ->build();

        $task->execute($changeset, true);
        self::assertTrue($logger->hasDebug('Changeset submitted by forge__cce -> skip'));
        self::assertFalse($caller->hasBeenCalled());
    }

    public function testItReturnsIfModuleIsNotActivated(): void
    {
        $logger    = new TestLogger();
        $caller    = WASMModuleCallerStub::withEmptyErrResult();
        $task      = new CustomCodeExecutionTask(
            $logger,
            ArtifactPayloadBuilderStub::withEmptyPayload(),
            WASMModulePathHelperStub::withPath(''),
            $caller,
            WASMResponseExecutorStub::buildOk(),
            SaveModuleLogStub::build(),
            CheckModuleIsActivatedStub::deactivated(),
            TrackerAdministratorNotificationSenderStub::build(),
        );
        $changeset = ChangesetTestBuilder::aChangeset('1')
            ->build();

        $task->execute($changeset, true);
        self::assertTrue($logger->hasDebug('Module is deactivated -> skip'));
        self::assertFalse($caller->hasBeenCalled());
    }

    public function testItFaultsIfItCannotGetModulePath(): void
    {
        $logger    = new TestLogger();
        $caller    = WASMModuleCallerStub::withEmptyErrResult();
        $task      = new CustomCodeExecutionTask(
            $logger,
            ArtifactPayloadBuilderStub::withEmptyPayload(),
            WASMModulePathHelperStub::withPath('non-existing-file'),
            $caller,
            WASMResponseExecutorStub::buildOk(),
            SaveModuleLogStub::build(),
            CheckModuleIsActivatedStub::activated(),
            TrackerAdministratorNotificationSenderStub::build(),
        );
        $tracker   = TrackerTestBuilder::aTracker()->withId(23)->build();
        $artifact  = ArtifactTestBuilder::anArtifact(15)->inTracker($tracker)->build();
        $changeset = ChangesetTestBuilder::aChangeset('1')
            ->ofArtifact($artifact)
            ->build();

        $task->execute($changeset, true);
        self::assertTrue($logger->hasDebug('WASM module for tracker #23 not found or not readable'));
        self::assertFalse($caller->hasBeenCalled());
    }

    public function testItFaultsWhenWASMCallerReturnsErr(): void
    {
        $logger    = new TestLogger();
        $task      = new CustomCodeExecutionTask(
            $logger,
            ArtifactPayloadBuilderStub::withEmptyPayload(),
            WASMModulePathHelperStub::withPath(self::WASM_FILE),
            WASMModuleCallerStub::withErrResult('Caller error'),
            WASMResponseExecutorStub::buildOk(),
            SaveModuleLogStub::build(),
            CheckModuleIsActivatedStub::activated(),
            TrackerAdministratorNotificationSenderStub::build(),
        );
        $tracker   = TrackerTestBuilder::aTracker()->withId(23)->build();
        $artifact  = ArtifactTestBuilder::anArtifact(15)->inTracker($tracker)->build();
        $changeset = ChangesetTestBuilder::aChangeset('1')
            ->ofArtifact($artifact)
            ->build();

        $task->execute($changeset, true);
        self::assertTrue($logger->hasDebug('Caller error'));
    }

    public function testItFaultWhenWASMResponseExecutorReturnsErr(): void
    {
        $logger       = new TestLogger();
        $dao          = SaveModuleLogStub::build();
        $notification = TrackerAdministratorNotificationSenderStub::build();
        $task         = new CustomCodeExecutionTask(
            $logger,
            ArtifactPayloadBuilderStub::withEmptyPayload(),
            WASMModulePathHelperStub::withPath(self::WASM_FILE),
            WASMModuleCallerStub::withOkResult(new WASMResponseRepresentation([], null)),
            WASMResponseExecutorStub::buildErr("Executor error"),
            $dao,
            CheckModuleIsActivatedStub::activated(),
            $notification,
        );
        $tracker      = TrackerTestBuilder::aTracker()->withId(23)->build();
        $artifact     = ArtifactTestBuilder::anArtifact(15)->inTracker($tracker)->build();
        $changeset    = ChangesetTestBuilder::aChangeset('1')
            ->ofArtifact($artifact)
            ->build();

        $task->execute($changeset, true);
        self::assertTrue($logger->hasDebug("Executor error"));
        $line_saved = $dao->getLineSaved();
        self::assertNotNull($line_saved);
        self::assertEquals(ModuleLogLine::STATUS_ERROR, $line_saved->status);
        self::assertTrue($notification->hasBeenCalled());
    }

    public function testItLogsDebugIfAllGoesWell(): void
    {
        $logger       = new TestLogger();
        $dao          = SaveModuleLogStub::build();
        $notification = TrackerAdministratorNotificationSenderStub::build();
        $task         = new CustomCodeExecutionTask(
            $logger,
            ArtifactPayloadBuilderStub::withEmptyPayload(),
            WASMModulePathHelperStub::withPath(self::WASM_FILE),
            WASMModuleCallerStub::withOkResult(new WASMResponseRepresentation([], null)),
            WASMResponseExecutorStub::buildOk(),
            $dao,
            CheckModuleIsActivatedStub::activated(),
            $notification,
        );
        $tracker      = TrackerTestBuilder::aTracker()->withId(23)->build();
        $artifact     = ArtifactTestBuilder::anArtifact(15)->inTracker($tracker)->build();
        $changeset    = ChangesetTestBuilder::aChangeset('1')
            ->ofArtifact($artifact)
            ->build();

        $task->execute($changeset, true);
        self::assertFalse($logger->hasWarningRecords());
        self::assertTrue($logger->hasDebug('CustomCodeExecutionTask finished'));
        $line_saved = $dao->getLineSaved();
        self::assertNotNull($line_saved);
        self::assertEquals(ModuleLogLine::STATUS_PASSED, $line_saved->status);
        self::assertFalse($notification->hasBeenCalled());
    }
}
