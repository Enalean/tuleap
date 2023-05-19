<?php
/**
 * Copyright (c) Enalean, 2020 - Present. All Rights Reserved.
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

namespace Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Fields;

use ColinODell\PsrTestLogger\TestLogger;
use Tuleap\ProgramManagement\Adapter\Workspace\MessageLog;
use Tuleap\ProgramManagement\Domain\Program\Admin\Configuration\ConfigurationErrorsCollector;
use Tuleap\ProgramManagement\Domain\TrackerReference;
use Tuleap\ProgramManagement\Domain\Workspace\UserIdentifier;
use Tuleap\ProgramManagement\Tests\Builder\SynchronizedFieldReferencesBuilder;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveProjectFromTrackerStub;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveTrackerFromFieldStub;
use Tuleap\ProgramManagement\Tests\Stub\SynchronizedFieldsStubPreparation;
use Tuleap\ProgramManagement\Tests\Stub\TrackerReferenceStub;
use Tuleap\ProgramManagement\Tests\Stub\UserIdentifierStub;
use Tuleap\ProgramManagement\Tests\Stub\VerifyFieldPermissionsStub;
use Tuleap\ProgramManagement\Tests\Stub\VerifyIsTeamStub;

final class SynchronizedFieldFromProgramAndTeamTrackersCollectionTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private const ARTIFACT_LINK_ID = 1;
    private const TITLE_ID         = 2;
    private const DESCRIPTION_ID   = 3;
    private const STATUS_ID        = 4;
    private const START_DATE_ID    = 5;
    private const END_PERIOD_ID    = 6;
    private TestLogger $logger;
    private UserIdentifier $user_identifier;
    private TrackerReference $tracker;
    private SynchronizedFieldFromProgramAndTeamTrackers $synchronized_fields;

    protected function setUp(): void
    {
        $this->user_identifier = UserIdentifierStub::buildGenericUser();
        $this->tracker         = TrackerReferenceStub::withDefaults();
        $this->logger          = new TestLogger();

        $this->synchronized_fields = new SynchronizedFieldFromProgramAndTeamTrackers(
            SynchronizedFieldReferencesBuilder::buildWithPreparations(
                SynchronizedFieldsStubPreparation::withAllFields(
                    self::TITLE_ID,
                    self::DESCRIPTION_ID,
                    self::STATUS_ID,
                    self::START_DATE_ID,
                    self::END_PERIOD_ID,
                    self::ARTIFACT_LINK_ID
                )
            )
        );
    }

    public function testCanUserSubmitAndUpdateAllFieldsReturnsTrue(): void
    {
        $collection = $this->getCollection(VerifyFieldPermissionsStub::withValidField());
        $collection->add($this->synchronized_fields);
        $this->assertTrue(
            $collection->canUserSubmitAndUpdateAllFields(
                $this->user_identifier,
                new ConfigurationErrorsCollector(VerifyIsTeamStub::withValidTeam(), false)
            )
        );
    }

    public function testItReturnsFalseWhenUserCantSubmitFields(): void
    {
        $collection = $this->getCollection(VerifyFieldPermissionsStub::userCantSubmit());
        $collection->add($this->synchronized_fields);
        $errors_collector = new ConfigurationErrorsCollector(VerifyIsTeamStub::withValidTeam(), true);
        $this->assertFalse(
            $collection->canUserSubmitAndUpdateAllFields($this->user_identifier, $errors_collector)
        );

        self::assertGreaterThan(1, $errors_collector->getNonSubmittableFields());
        self::assertSame(self::ARTIFACT_LINK_ID, $errors_collector->getNonSubmittableFields()[0]->field_id);
        self::assertFalse($this->logger->hasDebugRecords());
    }

    public function testItReturnsFalseWhenUserCantUpdateFields(): void
    {
        $collection = $this->getCollection(VerifyFieldPermissionsStub::userCantUpdate());
        $collection->add($this->synchronized_fields);
        $errors_collector = new ConfigurationErrorsCollector(VerifyIsTeamStub::withValidTeam(), true);
        $this->assertFalse(
            $collection->canUserSubmitAndUpdateAllFields($this->user_identifier, $errors_collector)
        );
        self::assertGreaterThan(1, $errors_collector->getNonUpdatableFields());
        self::assertSame(self::ARTIFACT_LINK_ID, $errors_collector->getNonUpdatableFields()[0]->field_id);
        self::assertFalse($this->logger->hasDebugRecords());
    }

    public function testItLogsErrorsForSubmission(): void
    {
        $collection = $this->getCollection(VerifyFieldPermissionsStub::userCantSubmit());
        $collection->add($this->synchronized_fields);
        $errors_collector = new ConfigurationErrorsCollector(VerifyIsTeamStub::withValidTeam(), false);
        $this->assertFalse(
            $collection->canUserSubmitAndUpdateAllFields($this->user_identifier, $errors_collector)
        );
        self::assertCount(1, $errors_collector->getNonSubmittableFields());
        self::assertTrue($this->logger->hasDebugRecords());
    }

    public function testItLogsErrorsForUpdate(): void
    {
        $collection = $this->getCollection(VerifyFieldPermissionsStub::userCantUpdate());
        $collection->add($this->synchronized_fields);
        $errors_collector = new ConfigurationErrorsCollector(VerifyIsTeamStub::withValidTeam(), false);
        $this->assertFalse(
            $collection->canUserSubmitAndUpdateAllFields($this->user_identifier, $errors_collector)
        );
        self::assertCount(1, $errors_collector->getNonUpdatableFields());
        self::assertTrue($this->logger->hasDebugRecords());
    }

    public function testCanDetermineIfAFieldIsSynchronized(): void
    {
        $collection = $this->getCollection(VerifyFieldPermissionsStub::withValidField());
        $collection->add($this->synchronized_fields);
        $this->assertTrue($collection->isFieldIdSynchronized(self::ARTIFACT_LINK_ID));

        $not_synchronized_field_id = 1024;
        $this->assertFalse($collection->isFieldIdSynchronized($not_synchronized_field_id));
    }

    public function testCanObtainsTheSynchronizedFieldIDs(): void
    {
        $collection = $this->getCollection(VerifyFieldPermissionsStub::withValidField());
        $collection->add($this->synchronized_fields);
        $this->assertEquals(
            [self::ARTIFACT_LINK_ID, self::TITLE_ID, self::DESCRIPTION_ID, self::STATUS_ID, self::START_DATE_ID, self::END_PERIOD_ID],
            $collection->getSynchronizedFieldIDs()
        );
    }

    private function getCollection(VerifyFieldPermissions $retrieve_field_permissions): SynchronizedFieldFromProgramAndTeamTrackersCollection
    {
        $retrieve_tracker_from_field = RetrieveTrackerFromFieldStub::withTracker($this->tracker);
        return new SynchronizedFieldFromProgramAndTeamTrackersCollection(
            MessageLog::buildFromLogger($this->logger),
            $retrieve_tracker_from_field,
            $retrieve_field_permissions,
            RetrieveProjectFromTrackerStub::buildGeneric()
        );
    }
}
