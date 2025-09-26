<?php
/**
 * Copyright (c) Enalean, 2014 - present. All Rights Reserved.
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

use PHPUnit\Framework\MockObject\MockObject;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\FormElement\Field\ListField;
use Tuleap\Tracker\Test\Builders\Fields\LastUpdateDateFieldBuilder;
use Tuleap\Tracker\Test\Builders\Fields\SubmittedOnFieldBuilder;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;
use Tuleap\Tracker\Tracker;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class Tracker_Artifact_Changeset_ChangesetDataInitializatorTest extends TestCase //phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace, Squiz.Classes.ValidClassName.NotCamelCaps
{
    private Tracker $tracker;
    private MockObject&Tracker_FormElementFactory $formelement_factory;

    private Tracker_Artifact_Changeset_ChangesetDataInitializator $initializator;
    private MockObject&Artifact $artifact;

    #[\Override]
    protected function setUp(): void
    {
        $this->tracker  = TrackerTestBuilder::aTracker()->build();
        $this->artifact = $this->createMock(Artifact::class);
        $this->artifact->method('getTracker')->willReturn($this->tracker);

        $this->formelement_factory = $this->createMock(Tracker_FormElementFactory::class);
        $this->initializator       = new Tracker_Artifact_Changeset_ChangesetDataInitializator($this->formelement_factory);
    }

    public function testItPreloadsDateFieldsFromPreviousChangeset(): void
    {
        $this->formelement_factory->method('getAllFormElementsForTracker')
            ->with($this->tracker)
            ->willReturn([]);

        $changeset = $this->createMock(Tracker_Artifact_Changeset::class);
        $value     = $this->createMock(Tracker_Artifact_ChangesetValue_Date::class);
        $value->method('getTimestamp')->willReturn(1655381802);
        $changeset->method('getValues')->willReturn([14 => $value]);

        $this->artifact->method('getLastChangeset')->willReturn($changeset);

        $fields_data = [];

        $this->assertEquals(
            [14 => '1655381802'],
            $this->initializator->process($this->artifact, $fields_data)
        );
    }

    public function testItPreloadsListFieldsFromPreviousChangeset(): void
    {
        $this->formelement_factory->method('getAllFormElementsForTracker')
            ->with($this->tracker)
            ->willReturn([]);

        $changeset = $this->createMock(Tracker_Artifact_Changeset::class);
        $value     = $this->createMock(Tracker_Artifact_ChangesetValue_List::class);
        $value->method('getValue')->willReturn('101');
        $changeset->method('getValues')->willReturn([22 => $value]);

        $this->artifact->method('getLastChangeset')->willReturn($changeset);

        $fields_data = [];

        $this->assertEquals(
            [22 => '101'],
            $this->initializator->process($this->artifact, $fields_data)
        );
    }

    public function testAnEmptyValueForListFieldAtUpdateShouldUseNoneValueToWorkWellWithFieldDependenciesCheckingAfterward(): void
    {
        $this->formelement_factory->method('getAllFormElementsForTracker')
            ->with($this->tracker)
            ->willReturn([]);

        $changeset = $this->createMock(Tracker_Artifact_Changeset::class);
        $value     = $this->createMock(Tracker_Artifact_ChangesetValue_List::class);
        $value->method('getValue')->willReturn([]);
        $changeset->method('getValues')->willReturn([22 => $value]);

        $this->artifact->method('getLastChangeset')->willReturn($changeset);

        $fields_data = [];

        $this->assertEquals(
            [22 => [100]],
            $this->initializator->process($this->artifact, $fields_data)
        );
    }

    public function testAnEmptyValueForListFieldAtCreationShouldUseDefaultValueToWorkWellWithFieldDependenciesCheckingAfterward(): void
    {
        $list_field = $this->createMock(ListField::class);
        $list_field->method('getId')->willReturn(22);
        $list_field->method('getDefaultValue')->willReturn([598]);

        $this->formelement_factory->method('getAllFormElementsForTracker')
            ->with($this->tracker)
            ->willReturn([$list_field]);

        $this->artifact->method('getLastChangeset')->willReturn(null);

        $fields_data = [];

        $this->assertEquals(
            [22 => [598]],
            $this->initializator->process($this->artifact, $fields_data)
        );
    }

    public function testAnEmptyValueForListFieldAtCreationShouldUseNoneValueToWorkWellWithFieldDependenciesCheckingAfterwardIfThereIsNoDefaultValue(): void
    {
        $list_field = $this->createMock(ListField::class);
        $list_field->method('getId')->willReturn(22);
        $list_field->method('getDefaultValue')->willReturn([100]);

        $this->formelement_factory->method('getAllFormElementsForTracker')
            ->with($this->tracker)
            ->willReturn([$list_field]);

        $this->artifact->method('getLastChangeset')->willReturn(null);

        $fields_data = [];

        $this->assertEquals(
            [22 => [100]],
            $this->initializator->process($this->artifact, $fields_data)
        );
    }

    public function testCreationWithListFieldValueHasTheSelectedValue(): void
    {
        $list_field = $this->createMock(ListField::class);
        $list_field->method('getId')->willReturn(22);

        $this->formelement_factory->method('getAllFormElementsForTracker')
            ->with($this->tracker)
            ->willReturn([$list_field]);

        $this->artifact->method('getLastChangeset')->willReturn(null);

        $fields_data = [22 => [234]];

        $this->assertEquals(
            [22 => [234]],
            $this->initializator->process($this->artifact, $fields_data)
        );
    }

    public function testSubmittedDateFieldsOverridesPreviousChangeset(): void
    {
        $this->formelement_factory->method('getAllFormElementsForTracker')
            ->with($this->tracker)
            ->willReturn([]);

        $changeset = $this->createMock(Tracker_Artifact_Changeset::class);
        $value     = $this->createMock(Tracker_Artifact_ChangesetValue_Date::class);
        $value->method('getTimestamp')->willReturn(1655381802);
        $changeset->method('getValues')->willReturn([14 => $value]);

        $this->artifact->method('getLastChangeset')->willReturn($changeset);

        $fields_data = [14 => '2014-07-07'];

        $this->assertEquals(
            [14 => '2014-07-07'],
            $this->initializator->process($this->artifact, $fields_data)
        );
    }

    public function testSubmittedListFieldsOverridesPreviousChangeset(): void
    {
        $this->formelement_factory->method('getAllFormElementsForTracker')
            ->with($this->tracker)
            ->willReturn([]);

        $changeset = $this->createMock(Tracker_Artifact_Changeset::class);
        $value     = $this->createMock(Tracker_Artifact_ChangesetValue_List::class);
        $value->method('getValue')->willReturn('101');
        $changeset->method('getValues')->willReturn([22 => $value]);

        $this->artifact->method('getLastChangeset')->willReturn($changeset);

        $fields_data = [22 => '108'];

        $this->assertEquals(
            [22 => '108'],
            $this->initializator->process($this->artifact, $fields_data)
        );
    }

    public function testItAppendsSubmittedBy(): void
    {
        $this->formelement_factory->method('getAllFormElementsForTracker')
            ->with($this->tracker)
            ->willReturn([SubmittedOnFieldBuilder::aSubmittedOnField(12)->build()]);

        $this->artifact->method('getLastChangeset')->willReturn($this->createMock(Tracker_Artifact_Changeset_Null::class));
        $this->artifact->method('getSubmittedOn')->willReturn(12346789);

        $this->assertEquals(
            [12 => 12346789],
            $this->initializator->process($this->artifact, [])
        );
    }

    public function testItNoReturnLastFieldChangesIfNoChangesets(): void
    {
        $this->formelement_factory->method('getAllFormElementsForTracker')
            ->with($this->tracker)
            ->willReturn([SubmittedOnFieldBuilder::aSubmittedOnField(12)->build()]);

        $this->artifact->method('getLastChangeset')->willReturn(null);
        $this->artifact->method('getSubmittedOn')->willReturn(12346789);

        $this->assertEquals(
            [12 => 12346789],
            $this->initializator->process($this->artifact, [])
        );
    }

    public function testItAppendsLastUpdateDateAtCurrentTime(): void
    {
        $this->formelement_factory->method('getAllFormElementsForTracker')
            ->with($this->tracker)
            ->willReturn([LastUpdateDateFieldBuilder::aLastUpdateDateField(55)->build()]);

        $date = $_SERVER['REQUEST_TIME'];
        $this->artifact->method('getLastChangeset')->willReturn(new Tracker_Artifact_Changeset_Null());
        $this->artifact->method('getSubmittedOn')->willReturn($date);

        $this->assertEquals(
            [55 => date('Y-m-d', $date)],
            $this->initializator->process($this->artifact, [])
        );
    }
}
