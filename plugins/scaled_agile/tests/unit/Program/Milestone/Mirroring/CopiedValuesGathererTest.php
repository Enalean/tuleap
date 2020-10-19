<?php
/*
 * Copyright (c) Enalean, 2020-Present. All Rights Reserved.
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

namespace Tuleap\ScaledAgile\Program\Milestone\Mirroring;

use Mockery as M;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Tuleap\ScaledAgile\Program\Milestone\Mirroring\Status\NoStatusChangesetValueException;
use Tuleap\ScaledAgile\Program\Milestone\SynchronizedFields;
use Tuleap\ScaledAgile\Program\Milestone\SynchronizedFieldsGatherer;
use Tuleap\ScaledAgile\Program\Milestone\TimeframeFields;

final class CopiedValuesGathererTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var CopiedValuesGatherer
     */
    private $gatherer;
    /**
     * @var M\LegacyMockInterface|M\MockInterface|SynchronizedFieldsGatherer
     */
    private $fields_gatherer;

    protected function setUp(): void
    {
        $this->fields_gatherer = M::mock(SynchronizedFieldsGatherer::class);
        $this->gatherer        = new CopiedValuesGatherer($this->fields_gatherer);
    }

    public function testItReturnsCopiedValues(): void
    {
        $artifact = M::mock(\Tuleap\Tracker\Artifact\Artifact::class);
        $artifact->shouldReceive('getId')->andReturn(104);
        $changeset = new \Tracker_Artifact_Changeset(21, $artifact, 36, 123456789, '');
        $tracker   = $this->buildTestTracker(89);

        $synchronized_fields = $this->buildSynchronizedFields();

        $title_field       = $synchronized_fields->getTitleField();
        $title_field_value = new \Tracker_Artifact_ChangesetValue_String(10000, $changeset, $title_field, true, 'My awesome title', 'text');
        $changeset->setFieldValue($title_field, $title_field_value);

        $description_field       = $synchronized_fields->getDescriptionField();
        $description_field_value = new \Tracker_Artifact_ChangesetValue_Text(10001, $changeset, $description_field, true, 'My awesome description', 'text');
        $changeset->setFieldValue($description_field, $description_field_value);

        $status_field       = $synchronized_fields->getStatusField();
        $status_field_value = new \Tracker_Artifact_ChangesetValue_List(10002, $changeset, $status_field, true, ['Ongoing']);
        $changeset->setFieldValue($status_field, $status_field_value);

        $timeframe_fields = $synchronized_fields->getTimeframeFields();
        $start_date_field       = $timeframe_fields->getStartDateField();
        $start_date_field_value = new \Tracker_Artifact_ChangesetValue_Date(10003, $changeset, $start_date_field, true, 123456789);
        $changeset->setFieldValue($start_date_field, $start_date_field_value);

        $end_date_field       = $timeframe_fields->getEndPeriodField();
        $end_date_field_value = new \Tracker_Artifact_ChangesetValue_Date(10004, $changeset, $end_date_field, true, 123543189);
        $changeset->setFieldValue($end_date_field, $end_date_field_value);

        $this->fields_gatherer->shouldReceive('gather')
            ->once()
            ->with($tracker)
            ->andReturn($synchronized_fields);

        $values = $this->gatherer->gather($changeset, $tracker);

        self::assertSame($title_field_value, $values->getTitleValue());
        self::assertSame($description_field_value, $values->getDescriptionValue());
        self::assertSame($status_field_value, $values->getStatusValue());
        self::assertSame(123456789, $values->getSubmittedOn());
        self::assertEquals(104, $values->getArtifactId());
        self::assertSame($start_date_field_value, $values->getStartDateValue());
        self::assertSame($end_date_field_value, $values->getEndPeriodValue());
    }

    public function testItThrowsWhenChangesetHasNoValueForTitleField(): void
    {
        $artifact  = M::mock(\Tuleap\Tracker\Artifact\Artifact::class);
        $changeset = new \Tracker_Artifact_Changeset(21, $artifact, 36, 1, '');
        $tracker   = $this->buildTestTracker(89);

        $synchronized_fields = $this->buildSynchronizedFields();
        $this->fields_gatherer->shouldReceive('gather')->andReturn($synchronized_fields);
        $changeset->setNoFieldValue($synchronized_fields->getTitleField());

        $this->expectException(NoTitleChangesetValueException::class);
        $this->gatherer->gather($changeset, $tracker);
    }

    public function testItThrowsWhenTitleChangesetValueCannotBeCastToString(): void
    {
        $artifact  = M::mock(\Tuleap\Tracker\Artifact\Artifact::class);
        $changeset = new \Tracker_Artifact_Changeset(21, $artifact, 36, 1, '');
        $tracker   = $this->buildTestTracker(89);

        $synchronized_fields = $this->buildSynchronizedFields();
        $this->fields_gatherer->shouldReceive('gather')->andReturn($synchronized_fields);

        $title_field       = $synchronized_fields->getTitleField();
        $title_field_value = new \Tracker_Artifact_ChangesetValue_Text(10000, $changeset, $title_field, true, 'My awesome title', 'text');
        $changeset->setFieldValue($title_field, $title_field_value);

        $this->expectException(UnsupportedTitleFieldException::class);
        $this->gatherer->gather($changeset, $tracker);
    }

    public function testItThrowsWhenChangesetHasNoValueForDescriptionField(): void
    {
        $artifact  = M::mock(\Tuleap\Tracker\Artifact\Artifact::class);
        $changeset = new \Tracker_Artifact_Changeset(21, $artifact, 36, 1, '');
        $tracker   = $this->buildTestTracker(89);

        $synchronized_fields = $this->buildSynchronizedFields();
        $this->fields_gatherer->shouldReceive('gather')->andReturn($synchronized_fields);

        $title_field       = $synchronized_fields->getTitleField();
        $title_field_value = new \Tracker_Artifact_ChangesetValue_String(10000, $changeset, $title_field, true, 'My awesome title', 'text');
        $changeset->setFieldValue($title_field, $title_field_value);

        $changeset->setNoFieldValue($synchronized_fields->getDescriptionField());

        $this->expectException(NoDescriptionChangesetValueException::class);
        $this->gatherer->gather($changeset, $tracker);
    }

    public function testItThrowsWhenChangesetHasNoValueForStatusField(): void
    {
        $artifact  = M::mock(\Tuleap\Tracker\Artifact\Artifact::class);
        $changeset = new \Tracker_Artifact_Changeset(21, $artifact, 36, 1, '');
        $tracker   = $this->buildTestTracker(89);

        $synchronized_fields = $this->buildSynchronizedFields();
        $this->fields_gatherer->shouldReceive('gather')->andReturn($synchronized_fields);

        $title_field       = $synchronized_fields->getTitleField();
        $title_field_value = new \Tracker_Artifact_ChangesetValue_String(10000, $changeset, $title_field, true, 'My awesome title', 'text');
        $changeset->setFieldValue($title_field, $title_field_value);

        $description_field       = $synchronized_fields->getDescriptionField();
        $description_field_value = new \Tracker_Artifact_ChangesetValue_Text(10001, $changeset, $description_field, true, 'My awesome description', 'text');
        $changeset->setFieldValue($description_field, $description_field_value);

        $changeset->setNoFieldValue($synchronized_fields->getStatusField());

        $this->expectException(NoStatusChangesetValueException::class);
        $this->gatherer->gather($changeset, $tracker);
    }

    public function testItThrowsWhenChangesetHasNoValueForStartDateField(): void
    {
        $artifact  = M::mock(\Tuleap\Tracker\Artifact\Artifact::class);
        $changeset = new \Tracker_Artifact_Changeset(21, $artifact, 36, 1, '');
        $tracker   = $this->buildTestTracker(89);

        $synchronized_fields = $this->buildSynchronizedFields();
        $this->fields_gatherer->shouldReceive('gather')->andReturn($synchronized_fields);

        $title_field       = $synchronized_fields->getTitleField();
        $title_field_value = new \Tracker_Artifact_ChangesetValue_String(10000, $changeset, $title_field, true, 'My awesome title', 'text');
        $changeset->setFieldValue($title_field, $title_field_value);

        $description_field       = $synchronized_fields->getDescriptionField();
        $description_field_value = new \Tracker_Artifact_ChangesetValue_Text(10001, $changeset, $description_field, true, 'My awesome description', 'text');
        $changeset->setFieldValue($description_field, $description_field_value);

        $status_field       = $synchronized_fields->getStatusField();
        $status_field_value = new \Tracker_Artifact_ChangesetValue_List(10002, $changeset, $status_field, true, ['Ongoing']);
        $changeset->setFieldValue($status_field, $status_field_value);

        $start_date_field = $synchronized_fields->getTimeframeFields()->getStartDateField();
        $changeset->setNoFieldValue($start_date_field);

        $this->expectException(NoStartDateChangesetValueException::class);
        $this->gatherer->gather($changeset, $tracker);
    }

    public function testItThrowsWhenChangesetHasNoValueForEndPeriodField(): void
    {
        $artifact  = M::mock(\Tuleap\Tracker\Artifact\Artifact::class);
        $changeset = new \Tracker_Artifact_Changeset(21, $artifact, 36, 1, '');
        $tracker   = $this->buildTestTracker(89);

        $synchronized_fields = $this->buildSynchronizedFields();
        $this->fields_gatherer->shouldReceive('gather')->andReturn($synchronized_fields);

        $title_field       = $synchronized_fields->getTitleField();
        $title_field_value = new \Tracker_Artifact_ChangesetValue_String(10000, $changeset, $title_field, true, 'My awesome title', 'text');
        $changeset->setFieldValue($title_field, $title_field_value);

        $description_field       = $synchronized_fields->getDescriptionField();
        $description_field_value = new \Tracker_Artifact_ChangesetValue_Text(10001, $changeset, $description_field, true, 'My awesome description', 'text');
        $changeset->setFieldValue($description_field, $description_field_value);

        $status_field       = $synchronized_fields->getStatusField();
        $status_field_value = new \Tracker_Artifact_ChangesetValue_List(10002, $changeset, $status_field, true, ['Ongoing']);
        $changeset->setFieldValue($status_field, $status_field_value);

        $timeframe_fields       = $synchronized_fields->getTimeframeFields();
        $start_date_field       = $timeframe_fields->getStartDateField();
        $start_date_field_value = new \Tracker_Artifact_ChangesetValue_Date(10003, $changeset, $start_date_field, true, 123456789);
        $changeset->setFieldValue($start_date_field, $start_date_field_value);

        $changeset->setNoFieldValue($timeframe_fields->getEndPeriodField());

        $this->expectException(NoEndPeriodChangesetValueException::class);
        $this->gatherer->gather($changeset, $tracker);
    }

    private function buildSynchronizedFields(): SynchronizedFields
    {
        return new SynchronizedFields(
            new \Tracker_FormElement_Field_ArtifactLink(1001, 89, 1000, 'art_link', 'Links', 'Irrelevant', true, 'P', false, '', 1),
            new \Tracker_FormElement_Field_String(1002, 89, 1000, 'title', 'Title', 'Irrelevant', true, 'P', true, '', 2),
            new \Tracker_FormElement_Field_Text(1003, 89, 1000, 'description', 'Description', 'Irrelevant', true, 'P', false, '', 3),
            new \Tracker_FormElement_Field_Selectbox(1004, 89, 1000, 'status', 'Status', 'Irrelevant', true, 'P', false, '', 4),
            TimeframeFields::fromStartAndEndDates(
                $this->buildTestDateField(1005, 89),
                $this->buildTestDateField(1006, 89)
            )
        );
    }

    private function buildTestDateField(int $id, int $tracker_id): \Tracker_FormElement_Field_Date
    {
        return new \Tracker_FormElement_Field_Date($id, $tracker_id, 1000, 'date', 'Date', 'Irrelevant', true, 'P', false, '', 1);
    }

    private function buildTestTracker(int $tracker_id): \Tracker
    {
        return new \Tracker(
            $tracker_id,
            null,
            'Irrelevant',
            'Irrelevant',
            'irrelevant',
            false,
            null,
            null,
            null,
            null,
            true,
            false,
            \Tracker::NOTIFICATIONS_LEVEL_DEFAULT,
            \Tuleap\Tracker\TrackerColor::default(),
            false
        );
    }
}
