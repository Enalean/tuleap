<?php
/**
 * Copyright (c) Enalean 2021 -  Present. All Rights Reserved.
 *
 *  This file is a part of Tuleap.
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

namespace Tuleap\ProgramManagement\Tests\Builder;

use Psr\Log\NullLogger;
use Tuleap\ProgramManagement\Adapter\Workspace\MessageLog;
use Tuleap\ProgramManagement\Domain\Program\Backlog\CreationCheck\TimeboxCreatorChecker;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Fields\SynchronizedFieldFromProgramAndTeamTrackersCollectionBuilder;
use Tuleap\ProgramManagement\Tests\Stub\GatherSynchronizedFieldsStub;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveProjectFromTrackerStub;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveTrackerFromFieldStub;
use Tuleap\ProgramManagement\Tests\Stub\SynchronizedFieldsStubPreparation;
use Tuleap\ProgramManagement\Tests\Stub\TrackerReferenceStub;
use Tuleap\ProgramManagement\Tests\Stub\VerifyFieldPermissionsStub;
use Tuleap\ProgramManagement\Tests\Stub\VerifyRequiredFieldsLimitedToSynchronizedFieldsStub;
use Tuleap\ProgramManagement\Tests\Stub\VerifySemanticsAreConfiguredStub;
use Tuleap\ProgramManagement\Tests\Stub\VerifySynchronizedFieldsAreNotUsedInWorkflowStub;
use Tuleap\ProgramManagement\Tests\Stub\VerifyUserCanSubmitStub;

final class TimeboxCreatorCheckerBuilder
{
    public static function buildValid(): TimeboxCreatorChecker
    {
        $user_can_submit = VerifyUserCanSubmitStub::userCanSubmit();

        return self::build($user_can_submit);
    }

    public static function buildInvalid(): TimeboxCreatorChecker
    {
        $user_can_submit = VerifyUserCanSubmitStub::userCanNotSubmit();

        return self::build($user_can_submit);
    }

    private static function build(VerifyUserCanSubmitStub $user_can_submit): TimeboxCreatorChecker
    {
        $program_increment_tracker = TrackerReferenceStub::withDefaults();

        $retrieve_tracker_from_field = RetrieveTrackerFromFieldStub::withTracker($program_increment_tracker);
        $fields_adapter              = GatherSynchronizedFieldsStub::withFieldsPreparations(
            SynchronizedFieldsStubPreparation::withAllFields(770, 362, 544, 436, 341, 245),
            SynchronizedFieldsStubPreparation::withAllFields(610, 360, 227, 871, 623, 440),
            SynchronizedFieldsStubPreparation::withAllFields(914, 977, 235, 435, 148, 475),
        );

        $field_collection_builder = new SynchronizedFieldFromProgramAndTeamTrackersCollectionBuilder(
            $fields_adapter,
            MessageLog::buildFromLogger(new NullLogger()),
            $retrieve_tracker_from_field,
            VerifyFieldPermissionsStub::withValidField(),
            RetrieveProjectFromTrackerStub::buildGeneric()
        );

        return new TimeboxCreatorChecker(
            $field_collection_builder,
            VerifySemanticsAreConfiguredStub::withValidSemantics(),
            VerifyRequiredFieldsLimitedToSynchronizedFieldsStub::withValidField(),
            VerifySynchronizedFieldsAreNotUsedInWorkflowStub::withoutAWorkflow(),
            $retrieve_tracker_from_field,
            RetrieveProjectFromTrackerStub::buildGeneric(),
            $user_can_submit
        );
    }
}
