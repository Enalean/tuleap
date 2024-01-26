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

namespace Tuleap\TrackerFunctions;

use ColinODell\PsrTestLogger\TestLogger;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\TrackerFunctions\Logs\FunctionLogLine;
use Tuleap\TrackerFunctions\Stubs\Administration\CheckFunctionIsActivatedStub;
use Tuleap\TrackerFunctions\Stubs\Logs\SaveFunctionLogStub;
use Tuleap\TrackerFunctions\Stubs\Notification\TrackerAdministratorNotificationSenderStub;
use Tuleap\TrackerFunctions\Stubs\WASM\WASMFunctionCallerStub;
use Tuleap\TrackerFunctions\Stubs\WASM\WASMFunctionPathHelperStub;
use Tuleap\TrackerFunctions\Stubs\WASM\WASMResponseExecutorStub;
use Tuleap\TrackerFunctions\WASM\WASMResponseRepresentation;
use Tuleap\Tracker\Test\Builders\ArtifactTestBuilder;
use Tuleap\Tracker\Test\Builders\ChangesetTestBuilder;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;
use Tuleap\Tracker\Test\Stub\Tracker\Webhook\ArtifactPayloadBuilderStub;
use Tuleap\User\TuleapFunctionsUser;

class CustomCodeExecutionTaskTest extends TestCase
{
    private const WASM_FILE = __FILE__;

    public function testItReturnsIfSubmitterIsFunctionUser(): void
    {
        $logger    = new TestLogger();
        $caller    = WASMFunctionCallerStub::withEmptyErrResult();
        $task      = new CustomCodeExecutionTask(
            $logger,
            ArtifactPayloadBuilderStub::withEmptyPayload(),
            WASMFunctionPathHelperStub::withPath(''),
            $caller,
            WASMResponseExecutorStub::buildOk(),
            SaveFunctionLogStub::build(),
            CheckFunctionIsActivatedStub::activated(),
            TrackerAdministratorNotificationSenderStub::build(),
        );
        $changeset = ChangesetTestBuilder::aChangeset('1')
            ->submittedBy(TuleapFunctionsUser::ID)
            ->build();

        $task->execute($changeset, true);
        self::assertTrue($logger->hasDebug('Changeset submitted by forge__function -> skip'));
        self::assertFalse($caller->hasBeenCalled());
    }

    public function testItReturnsIfFunctionIsNotActivated(): void
    {
        $logger    = new TestLogger();
        $caller    = WASMFunctionCallerStub::withEmptyErrResult();
        $task      = new CustomCodeExecutionTask(
            $logger,
            ArtifactPayloadBuilderStub::withEmptyPayload(),
            WASMFunctionPathHelperStub::withPath(''),
            $caller,
            WASMResponseExecutorStub::buildOk(),
            SaveFunctionLogStub::build(),
            CheckFunctionIsActivatedStub::deactivated(),
            TrackerAdministratorNotificationSenderStub::build(),
        );
        $changeset = ChangesetTestBuilder::aChangeset('1')->build();

        $task->execute($changeset, true);
        self::assertTrue($logger->hasDebug('Function is deactivated -> skip'));
        self::assertFalse($caller->hasBeenCalled());
    }

    public function testItFaultsIfItCannotGetFunctionPath(): void
    {
        $logger    = new TestLogger();
        $caller    = WASMFunctionCallerStub::withEmptyErrResult();
        $task      = new CustomCodeExecutionTask(
            $logger,
            ArtifactPayloadBuilderStub::withEmptyPayload(),
            WASMFunctionPathHelperStub::withPath('non-existing-file'),
            $caller,
            WASMResponseExecutorStub::buildOk(),
            SaveFunctionLogStub::build(),
            CheckFunctionIsActivatedStub::activated(),
            TrackerAdministratorNotificationSenderStub::build(),
        );
        $tracker   = TrackerTestBuilder::aTracker()->withId(23)->build();
        $artifact  = ArtifactTestBuilder::anArtifact(15)->inTracker($tracker)->build();
        $changeset = ChangesetTestBuilder::aChangeset('1')
            ->ofArtifact($artifact)
            ->build();

        $task->execute($changeset, true);
        self::assertTrue($logger->hasDebug('Tuleap function for tracker #23 not found or not readable'));
        self::assertFalse($caller->hasBeenCalled());
    }

    public function testItFaultsWhenWASMCallerReturnsErr(): void
    {
        $logger    = new TestLogger();
        $task      = new CustomCodeExecutionTask(
            $logger,
            ArtifactPayloadBuilderStub::withEmptyPayload(),
            WASMFunctionPathHelperStub::withPath(self::WASM_FILE),
            WASMFunctionCallerStub::withErrResult('Caller error'),
            WASMResponseExecutorStub::buildOk(),
            SaveFunctionLogStub::build(),
            CheckFunctionIsActivatedStub::activated(),
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
        $dao          = SaveFunctionLogStub::build();
        $notification = TrackerAdministratorNotificationSenderStub::build();
        $task         = new CustomCodeExecutionTask(
            $logger,
            ArtifactPayloadBuilderStub::withEmptyPayload(),
            WASMFunctionPathHelperStub::withPath(self::WASM_FILE),
            WASMFunctionCallerStub::withOkResult(new WASMResponseRepresentation([], null)),
            WASMResponseExecutorStub::buildErr("Executor error"),
            $dao,
            CheckFunctionIsActivatedStub::activated(),
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
        self::assertEquals(FunctionLogLine::STATUS_ERROR, $line_saved->status);
        self::assertTrue($notification->hasBeenCalled());
    }

    public function testItLogsDebugIfAllGoesWell(): void
    {
        $logger       = new TestLogger();
        $dao          = SaveFunctionLogStub::build();
        $notification = TrackerAdministratorNotificationSenderStub::build();
        $task         = new CustomCodeExecutionTask(
            $logger,
            ArtifactPayloadBuilderStub::withEmptyPayload(),
            WASMFunctionPathHelperStub::withPath(self::WASM_FILE),
            WASMFunctionCallerStub::withOkResult(new WASMResponseRepresentation([], null)),
            WASMResponseExecutorStub::buildOk(),
            $dao,
            CheckFunctionIsActivatedStub::activated(),
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
        self::assertEquals(FunctionLogLine::STATUS_PASSED, $line_saved->status);
        self::assertFalse($notification->hasBeenCalled());
    }
}
