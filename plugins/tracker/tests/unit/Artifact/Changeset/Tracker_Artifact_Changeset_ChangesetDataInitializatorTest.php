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

use Tuleap\Tracker\Artifact\Artifact;

final class Tracker_Artifact_Changeset_ChangesetDataInitializatorTest extends \Tuleap\Test\PHPUnit\TestCase //phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace, Squiz.Classes.ValidClassName.NotCamelCaps
{
    use \Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|Tracker
     */
    private $tracker;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|Tracker_FormElementFactory
     */
    private $formelement_factory;

    /**
     * @var Tracker_Artifact_Changeset_ChangesetDataInitializator
     */
    private $initializator;

    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|Artifact
     */
    private $artifact;

    protected function setUp(): void
    {
        $this->tracker  = Mockery::mock(Tracker::class);
        $this->artifact = Mockery::mock(Artifact::class);
        $this->artifact->shouldReceive('getTracker')->andReturn($this->tracker);

        $this->formelement_factory = \Mockery::mock(Tracker_FormElementFactory::class);
        $this->initializator       = new Tracker_Artifact_Changeset_ChangesetDataInitializator($this->formelement_factory);
    }

    public function testItPreloadsDateFieldsFromPreviousChangeset(): void
    {
        $this->formelement_factory->shouldReceive('getAllFormElementsForTracker')
            ->with($this->tracker)
            ->andReturns([]);

        $changeset = Mockery::mock(Tracker_Artifact_Changeset::class);
        $value     = Mockery::mock(Tracker_Artifact_ChangesetValue_Date::class);
        $value->shouldReceive('getTimestamp')->andReturn('1655381802');
        $changeset->shouldReceive('getValues')->andReturn([14 => $value]);

        $this->artifact->shouldReceive('getLastChangeset')->andReturn($changeset);

        $fields_data = [];

        $this->assertEquals(
            [14 => '1655381802'],
            $this->initializator->process($this->artifact, $fields_data)
        );
    }

    public function testItPreloadsListFieldsFromPreviousChangeset(): void
    {
        $this->formelement_factory->shouldReceive('getAllFormElementsForTracker')
            ->with($this->tracker)
            ->andReturns([]);

        $changeset = Mockery::mock(Tracker_Artifact_Changeset::class);
        $value     = Mockery::mock(Tracker_Artifact_ChangesetValue_List::class);
        $value->shouldReceive('getValue')->andReturn('101');
        $changeset->shouldReceive('getValues')->andReturn([22 => $value]);

        $this->artifact->shouldReceive('getLastChangeset')->andReturn($changeset);

        $fields_data = [];

        $this->assertEquals(
            [22 => '101'],
            $this->initializator->process($this->artifact, $fields_data)
        );
    }

    public function testAnEmptyValueForListFieldAtUpdateShouldUseNoneValueToWorkWellWithFieldDependenciesCheckingAfterward(): void
    {
        $this->formelement_factory->shouldReceive('getAllFormElementsForTracker')
            ->with($this->tracker)
            ->andReturns([]);

        $changeset = Mockery::mock(Tracker_Artifact_Changeset::class);
        $value     = Mockery::mock(Tracker_Artifact_ChangesetValue_List::class);
        $value->shouldReceive('getValue')->andReturn([]);
        $changeset->shouldReceive('getValues')->andReturn([22 => $value]);

        $this->artifact->shouldReceive('getLastChangeset')->andReturn($changeset);

        $fields_data = [];

        $this->assertEquals(
            [22 => [100]],
            $this->initializator->process($this->artifact, $fields_data)
        );
    }

    public function testAnEmptyValueForListFieldAtCreationShouldUseDefaultValueToWorkWellWithFieldDependenciesCheckingAfterward(): void
    {
        $list_field = $this->createMock(Tracker_FormElement_Field_List::class);
        $list_field->method('getId')->willReturn(22);
        $list_field->method('getDefaultValue')->willReturn([598]);

        $this->formelement_factory->shouldReceive('getAllFormElementsForTracker')
            ->with($this->tracker)
            ->andReturns([$list_field]);

        $this->artifact->shouldReceive('getLastChangeset')->andReturn(null);

        $fields_data = [];

        $this->assertEquals(
            [22 => [598]],
            $this->initializator->process($this->artifact, $fields_data)
        );
    }

    public function testAnEmptyValueForListFieldAtCreationShouldUseNoneValueToWorkWellWithFieldDependenciesCheckingAfterwardIfThereIsNoDefaultValue(): void
    {
        $list_field = $this->createMock(Tracker_FormElement_Field_List::class);
        $list_field->method('getId')->willReturn(22);
        $list_field->method('getDefaultValue')->willReturn([100]);

        $this->formelement_factory->shouldReceive('getAllFormElementsForTracker')
            ->with($this->tracker)
            ->andReturns([$list_field]);

        $this->artifact->shouldReceive('getLastChangeset')->andReturn(null);

        $fields_data = [];

        $this->assertEquals(
            [22 => [100]],
            $this->initializator->process($this->artifact, $fields_data)
        );
    }

    public function testCreationWithListFieldValueHasTheSelectedValue(): void
    {
        $list_field = $this->createMock(Tracker_FormElement_Field_List::class);
        $list_field->method('getId')->willReturn(22);

        $this->formelement_factory->shouldReceive('getAllFormElementsForTracker')
            ->with($this->tracker)
            ->andReturns([$list_field]);

        $this->artifact->shouldReceive('getLastChangeset')->andReturn(null);

        $fields_data = [22 => [234]];

        $this->assertEquals(
            [22 => [234]],
            $this->initializator->process($this->artifact, $fields_data)
        );
    }

    public function testSubmittedDateFieldsOverridesPreviousChangeset(): void
    {
        $this->formelement_factory->shouldReceive('getAllFormElementsForTracker')
            ->with($this->tracker)
            ->andReturns([]);

        $changeset = Mockery::mock(Tracker_Artifact_Changeset::class);
        $value     = Mockery::mock(Tracker_Artifact_ChangesetValue_Date::class);
        $value->shouldReceive('getTimestamp')->andReturn('1655381802');
        $changeset->shouldReceive('getValues')->andReturn([14 => $value]);

        $this->artifact->shouldReceive('getLastChangeset')->andReturn($changeset);

        $fields_data = [14 => '2014-07-07'];

        $this->assertEquals(
            [14 => '2014-07-07'],
            $this->initializator->process($this->artifact, $fields_data)
        );
    }

    public function testSubmittedListFieldsOverridesPreviousChangeset(): void
    {
        $this->formelement_factory->shouldReceive('getAllFormElementsForTracker')
            ->with($this->tracker)
            ->andReturns([]);

        $changeset = Mockery::mock(Tracker_Artifact_Changeset::class);
        $value     = Mockery::mock(Tracker_Artifact_ChangesetValue_List::class);
        $value->shouldReceive('getValue')->andReturn('101');
        $changeset->shouldReceive('getValues')->andReturn([22 => $value]);

        $this->artifact->shouldReceive('getLastChangeset')->andReturn($changeset);

        $fields_data = [22 => '108'];

        $this->assertEquals(
            [22 => '108'],
            $this->initializator->process($this->artifact, $fields_data)
        );
    }

    public function testItAppendsSubmittedBy(): void
    {
        $field = Mockery::mock(Tracker_FormElement_Field_SubmittedOn::class);
        $field->shouldReceive('getId')->andReturn(12);
        $this->formelement_factory->shouldReceive('getAllFormElementsForTracker')
            ->with($this->tracker)
            ->andReturns([$field]);

        $this->artifact->shouldReceive('getLastChangeset')->andReturn(Mockery::mock(Tracker_Artifact_Changeset_Null::class));
        $this->artifact->shouldReceive('getSubmittedOn')->andReturn(12346789);

        $this->assertEquals(
            [12 => 12346789],
            $this->initializator->process($this->artifact, [])
        );
    }

    public function testItNoReturnLastFieldChangesIfNoChangesets(): void
    {
        $field = Mockery::mock(Tracker_FormElement_Field_SubmittedOn::class);
        $field->shouldReceive('getId')->andReturn(12);
        $this->formelement_factory->shouldReceive('getAllFormElementsForTracker')
            ->with($this->tracker)
            ->andReturns([$field]);

        $this->artifact->shouldReceive('getLastChangeset')->andReturn(null);
        $this->artifact->shouldReceive('getSubmittedOn')->andReturn(12346789);

        $this->assertEquals(
            [12 => 12346789],
            $this->initializator->process($this->artifact, [])
        );
    }

    public function testItAppendsLastUpdateDateAtCurrentTime(): void
    {
        $field = Mockery::mock(Tracker_FormElement_Field_LastUpdateDate::class);
        $field->shouldReceive('getId')->andReturn(55);
        $this->formelement_factory->shouldReceive('getAllFormElementsForTracker')
            ->with($this->tracker)
            ->andReturns([$field]);

        $date = $_SERVER['REQUEST_TIME'];
        $this->artifact->shouldReceive('getLastChangeset')->andReturn(Mockery::mock(Tracker_Artifact_Changeset_Null::class));
        $this->artifact->shouldReceive('getSubmittedOn')->andReturn($date);

        $this->assertEquals(
            [55 => date('Y-m-d', $date)],
            $this->initializator->process($this->artifact, [])
        );
    }
}
