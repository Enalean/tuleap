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

use Mockery as M;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Mockery\MockInterface;
use Psr\Log\Test\TestLogger;
use Tracker_FormElement_Field_ArtifactLink;
use Tracker_FormElement_Field_Date;
use Tracker_FormElement_Field_Selectbox;
use Tracker_FormElement_Field_Text;
use Tuleap\ProgramManagement\Domain\Program\Admin\Configuration\ConfigurationErrorsCollector;
use Tuleap\Test\Builders\UserTestBuilder;

final class SynchronizedFieldFromProgramAndTeamTrackersCollectionTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use MockeryPHPUnitIntegration;

    public function testCanUserSubmitAndUpdateAllFieldsReturnsTrue(): void
    {
        $user = UserTestBuilder::aUser()->build();

        $synchronized_field_data = $this->buildSynchronizedFieldDataFromProgramAndTeamTrackers(true, true);

        $collection = new SynchronizedFieldFromProgramAndTeamTrackersCollection(new TestLogger());
        $collection->add($synchronized_field_data);
        $this->assertTrue($collection->canUserSubmitAndUpdateAllFields($user, new ConfigurationErrorsCollector(false)));
    }

    public function testItReturnsFalseWhenUserCantSubmitOneField(): void
    {
        $user = UserTestBuilder::aUser()->build();

        $synchronized_field_data = $this->buildSynchronizedFieldDataFromProgramAndTeamTrackers(false, true);

        $logger     = new TestLogger();
        $collection = new SynchronizedFieldFromProgramAndTeamTrackersCollection($logger);
        $collection->add($synchronized_field_data);
        $errors_collector = new ConfigurationErrorsCollector(true);
        $this->assertFalse($collection->canUserSubmitAndUpdateAllFields($user, $errors_collector));
        self::assertCount(1, $errors_collector->getErrorMessages());
        self::assertStringContainsString("User can not submit the field", $errors_collector->getErrorMessages()[0]);
        self::assertFalse($logger->hasDebugRecords());
    }

    public function testItReturnsFalseWhenUserCantUpdateOneField(): void
    {
        $user = UserTestBuilder::aUser()->build();

        $synchronized_field_data = $this->buildSynchronizedFieldDataFromProgramAndTeamTrackers(true, false);

        $logger     = new TestLogger();
        $collection = new SynchronizedFieldFromProgramAndTeamTrackersCollection($logger);
        $collection->add($synchronized_field_data);
        $errors_collector = new ConfigurationErrorsCollector(true);
        $this->assertFalse($collection->canUserSubmitAndUpdateAllFields($user, $errors_collector));
        self::assertCount(1, $errors_collector->getErrorMessages());
        self::assertStringContainsString("User can not update the field", $errors_collector->getErrorMessages()[0]);
        self::assertFalse($logger->hasDebugRecords());
    }

    public function testItLogsErrorsForSubmission(): void
    {
        $user = UserTestBuilder::aUser()->build();

        $synchronized_field_data = $this->buildSynchronizedFieldDataFromProgramAndTeamTrackers(false, true);

        $logger     = new TestLogger();
        $collection = new SynchronizedFieldFromProgramAndTeamTrackersCollection($logger);
        $collection->add($synchronized_field_data);
        $errors_collector = new ConfigurationErrorsCollector(false);
        $this->assertFalse($collection->canUserSubmitAndUpdateAllFields($user, $errors_collector));
        self::assertCount(1, $errors_collector->getErrorMessages());
        self::assertTrue($logger->hasDebugRecords());
    }

    public function testItLogsErrorsForUpdate(): void
    {
        $user = UserTestBuilder::aUser()->build();

        $synchronized_field_data = $this->buildSynchronizedFieldDataFromProgramAndTeamTrackers(true, false);

        $logger     = new TestLogger();
        $collection = new SynchronizedFieldFromProgramAndTeamTrackersCollection($logger);
        $collection->add($synchronized_field_data);
        $errors_collector = new ConfigurationErrorsCollector(false);
        $this->assertFalse($collection->canUserSubmitAndUpdateAllFields($user, $errors_collector));
        self::assertCount(1, $errors_collector->getErrorMessages());
        self::assertTrue($logger->hasDebugRecords());
    }

    public function testCanDetermineIfAFieldIsSynchronized(): void
    {
        $field = M::mock(\Tracker_FormElement_Field::class);
        $field->shouldReceive('getId')->andReturn('1');

        $synchronized_field_data = $this->buildSynchronizedFieldDataFromProgramAndTeamTrackers(true, true);

        $collection = new SynchronizedFieldFromProgramAndTeamTrackersCollection(new TestLogger());
        $collection->add($synchronized_field_data);
        $this->assertTrue($collection->isFieldSynchronized($field));

        $not_synchronized_field = M::mock(\Tracker_FormElement_Field::class);
        $not_synchronized_field->shouldReceive('getId')->andReturn('1024');
        $this->assertFalse($collection->isFieldSynchronized($not_synchronized_field));
    }

    public function testCanObtainsTheSynchronizedFieldIDs(): void
    {
        $synchronized_field_data = $this->buildSynchronizedFieldDataFromProgramAndTeamTrackers(true, true);

        $collection = new SynchronizedFieldFromProgramAndTeamTrackersCollection(new TestLogger());
        $collection->add($synchronized_field_data);
        $this->assertEquals([1, 2, 3, 4, 5, 6], $collection->getSynchronizedFieldIDs());
    }

    private function buildSynchronizedFieldDataFromProgramAndTeamTrackers(bool $submitable, bool $updatable): SynchronizedFieldFromProgramAndTeamTrackers
    {
        $artifact_link = M::mock(Tracker_FormElement_Field_ArtifactLink::class);
        $artifact_link->shouldReceive('getLabel')->andReturn('Link');
        $artifact_link->shouldReceive('getTrackerId')->andReturn('49');
        $this->mockField($artifact_link, 1, $submitable, $updatable);
        $artifact_link_field_data = new Field($artifact_link);

        $title_field = M::mock(\Tracker_FormElement_Field_Text::class);
        $this->mockField($title_field, 2, true, true);
        $title_field_data = new Field($title_field);

        $description_field = M::mock(Tracker_FormElement_Field_Text::class);
        $this->mockField($description_field, 3, true, true);
        $description_field_data = new Field($description_field);

        $status_field = M::mock(Tracker_FormElement_Field_Selectbox::class);
        $this->mockField($status_field, 4, true, true);
        $status_field_data = new Field($status_field);

        $field_start_date = M::mock(Tracker_FormElement_Field_Date::class);
        $this->mockField($field_start_date, 5, true, true);
        $start_date_field_data = new Field($field_start_date);

        $field_end_date = M::mock(Tracker_FormElement_Field_Date::class);
        $this->mockField($field_end_date, 6, true, true);
        $end_date_field_data = new Field($field_end_date);

        $synchronized_field_data = new SynchronizedFields(
            $artifact_link_field_data,
            $title_field_data,
            $description_field_data,
            $status_field_data,
            $start_date_field_data,
            $end_date_field_data
        );

        return new SynchronizedFieldFromProgramAndTeamTrackers($synchronized_field_data);
    }

    private function mockField(MockInterface $field, int $id, bool $submitable, bool $updatable): void
    {
        $field->shouldReceive('getId')->andReturn($id);
        $field->shouldReceive('userCanSubmit')->andReturn($submitable);
        $field->shouldReceive('userCanUpdate')->andReturn($updatable);
    }
}
