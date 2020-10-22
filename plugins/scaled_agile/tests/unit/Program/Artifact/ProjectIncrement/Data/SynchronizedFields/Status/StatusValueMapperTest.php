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

namespace Tuleap\ScaledAgile\Program\Backlog\ProjectIncrement\Data\SynchronizedFields\Status;

use Mockery as M;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Tuleap\ScaledAgile\Program\Backlog\ProjectIncrement\Data\SynchronizedFields\CopiedValues;
use Tuleap\ScaledAgile\Program\Backlog\ProjectIncrement\Data\SynchronizedFields\SynchronizedFields;
use Tuleap\ScaledAgile\Program\Backlog\ProjectIncrement\Data\SynchronizedFields\TimeframeFields;
use Tuleap\Tracker\FormElement\Field\ListFields\FieldValueMatcher;

final class StatusValueMapperTest extends \PHPUnit\Framework\TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var StatusValueMapper
     */
    private $mapper;
    /**
     * @var M\LegacyMockInterface|M\MockInterface|FieldValueMatcher
     */
    private $matcher;

    protected function setUp(): void
    {
        $this->matcher = M::mock(FieldValueMatcher::class);
        $this->mapper = new StatusValueMapper($this->matcher);
    }

    public function testItMapsValuesByDuckTyping(): void
    {
        $first_list_value  = new \Tracker_FormElement_Field_List_Bind_StaticValue(2000, 'Not found', 'Irrelevant', 1, 0);
        $second_list_value = new \Tracker_FormElement_Field_List_Bind_StaticValue(2001, 'Planned', 'Irrelevant', 2, 0);
        $copied_values     = $this->buildCopiedValues([$first_list_value, $second_list_value]);
        $target_fields     = $this->buildSynchronizedFields();

        $first_mapped_value  = new \Tracker_FormElement_Field_List_Bind_StaticValue(3000, 'Not found', 'Irrelevant', 1, 0);
        $second_mapped_value = new \Tracker_FormElement_Field_List_Bind_StaticValue(3001, 'Planned', 'Irrelevant', 2, 0);
        $this->matcher->shouldReceive('getMatchingBindValueByDuckTyping')
            ->once()
            ->with($first_list_value, M::type(\Tracker_FormElement_Field_List::class))
            ->andReturn($first_mapped_value);
        $this->matcher->shouldReceive('getMatchingBindValueByDuckTyping')
            ->once()
            ->with($second_list_value, M::type(\Tracker_FormElement_Field_List::class))
            ->andReturn($second_mapped_value);

        $result = $this->mapper->mapStatusValueByDuckTyping($copied_values, $target_fields);
        $mapped_values = $result->getValues();
        self::assertContains(3000, $mapped_values);
        self::assertContains(3001, $mapped_values);
    }

    public function testItThrowsWhenOneValueCannotBeMapped(): void
    {
        $first_list_value  = new \Tracker_FormElement_Field_List_Bind_StaticValue(2000, 'Not found', 'Irrelevant', 1, 0);
        $second_list_value = new \Tracker_FormElement_Field_List_Bind_StaticValue(2001, 'Planned', 'Irrelevant', 1, 0);
        $copied_values     = $this->buildCopiedValues([$first_list_value, $second_list_value]);
        $target_fields     = $this->buildSynchronizedFields();
        $this->matcher->shouldReceive('getMatchingBindValueByDuckTyping')
            ->andReturnNull();

        $this->expectException(NoDuckTypedMatchingValueException::class);
        $this->mapper->mapStatusValueByDuckTyping($copied_values, $target_fields);
    }

    private function buildCopiedValues(array $status_value): CopiedValues
    {
        $title_field = M::mock(\Tracker_FormElement_Field::class);
        $title_changeset_value = new \Tracker_Artifact_ChangesetValue_String(10000, M::mock(\Tracker_Artifact_Changeset::class), $title_field, true, 'Irrelevant', 'text');

        $description_field = M::mock(\Tracker_FormElement_Field::class);
        $description_changeset_value = new \Tracker_Artifact_ChangesetValue_Text(10001, M::mock(\Tracker_Artifact_Changeset::class), $description_field, true, 'Irrelevant', 'text');

        $status_changeset_value = new \Tracker_Artifact_ChangesetValue_List(
            10002,
            M::mock(\Tracker_Artifact_Changeset::class),
            M::mock(\Tracker_FormElement_Field::class),
            true,
            $status_value
        );

        $start_date_changeset_value = new \Tracker_Artifact_ChangesetValue_Date(
            100003,
            M::mock(\Tracker_Artifact_Changeset::class),
            M::mock(\Tracker_FormElement_Field::class),
            true,
            1285891200
        );

        $end_period_changeset_value = new \Tracker_Artifact_ChangesetValue_Date(
            100004,
            M::mock(\Tracker_Artifact_Changeset::class),
            M::mock(\Tracker_FormElement_Field_Date::class),
            true,
            1602288000
        );

        return new CopiedValues(
            $title_changeset_value,
            $description_changeset_value,
            $status_changeset_value,
            123456789,
            123,
            $start_date_changeset_value,
            $end_period_changeset_value
        );
    }

    private function buildSynchronizedFields(): SynchronizedFields
    {
        return new SynchronizedFields(
            new \Tracker_FormElement_Field_ArtifactLink(1001, 89, 1000, 'art_link', 'Links', 'Irrelevant', true, 'P', false, '', 1),
            new \Tracker_FormElement_Field_String(1002, 89, 1000, 'title', 'Title', 'Irrelevant', true, 'P', true, '', 2),
            new \Tracker_FormElement_Field_Text(1003, 89, 1000, 'description', 'Description', 'Irrelevant', true, 'P', false, '', 3),
            new \Tracker_FormElement_Field_Selectbox(1004, 89, 1000, 'status', 'Status', 'Irrelevant', true, 'P', false, '', 4),
            TimeframeFields::fromStartAndEndDates(
                $this->buildTestDateField(1005),
                $this->buildTestDateField(1006)
            )
        );
    }

    private function buildTestDateField(int $id): \Tracker_FormElement_Field_Date
    {
        return new \Tracker_FormElement_Field_Date($id, 89, 1000, 'date', 'Date', 'Irrelevant', true, 'P', false, '', 1);
    }
}
