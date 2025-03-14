<?php
/**
 * Copyright (c) Enalean, 2013-Present. All Rights Reserved.
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

use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\Test\Builders\Fields\List\ListStaticValueBuilder;
use Tuleap\Tracker\TrackerColor;
use Tuleap\Tracker\Workflow\Trigger\Siblings\SiblingsRetriever;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class Tracker_Workflow_Trigger_RulesProcessor_AllOfStrategyTest extends \Tuleap\Test\PHPUnit\TestCase // phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace,Squiz.Classes.ValidClassName.NotCamelCaps
{
    use \Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    private $artifact;
    private $trigger_field;
    private $trigger_value;
    private $task_tracker;
    private $bug_tracker;
    private $strategy;
    private $strategy_complex_rule;
    private $trigger_value_2;

    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|SiblingsRetriever
     */
    private $siblings_retriever;

    protected function setUp(): void
    {
        $story_tracker      = $this->buildTracker(888);
        $this->task_tracker = $this->buildTracker(899);

        $parent = \Mockery::spy(\Tuleap\Tracker\Artifact\Artifact::class);
        $parent->shouldReceive('getTracker')->andReturns($story_tracker);

        $this->artifact = new Artifact(
            2,
            $this->task_tracker->getId(),
            null,
            10,
            null
        );
        $this->artifact->setTracker($this->task_tracker);
        $this->artifact->setParentWithoutPermissionChecking($parent);
        $this->artifact->setChangesets([\Mockery::spy(\Tracker_Artifact_Changeset::class)]);

        $target_field_id = 569;
        $target_field    = $this->buildSelectBoxField($target_field_id, $story_tracker);
        $target_value_id = 7;
        $target_value    = ListStaticValueBuilder::aStaticValue('label')->withId($target_value_id)->build();

        $this->trigger_field = $this->buildSelectBoxField(965, $this->task_tracker);
        $this->trigger_value = ListStaticValueBuilder::aStaticValue('label')->withId(14)->build();

        $rule = new Tracker_Workflow_Trigger_TriggerRule(
            1,
            new Tracker_Workflow_Trigger_FieldValue(
                $target_field,
                $target_value
            ),
            Tracker_Workflow_Trigger_RulesBuilderData::CONDITION_ALL_OFF,
            [
                new Tracker_Workflow_Trigger_FieldValue(
                    $this->trigger_field,
                    $this->trigger_value
                ),
            ]
        );

        $this->siblings_retriever = Mockery::mock(SiblingsRetriever::class);
        $this->strategy           = new Tracker_Workflow_Trigger_RulesProcessor_AllOfStrategy(
            $this->artifact,
            $rule,
            $this->siblings_retriever
        );

        $this->bug_tracker = $this->buildTracker(901);

        $trigger_field_2       = $this->buildSelectBoxField(236, $this->bug_tracker);
        $this->trigger_value_2 = ListStaticValueBuilder::aStaticValue('label')->withId(28)->build();

        $complex_rule = new Tracker_Workflow_Trigger_TriggerRule(
            1,
            new Tracker_Workflow_Trigger_FieldValue(
                $target_field,
                $target_value
            ),
            Tracker_Workflow_Trigger_RulesBuilderData::CONDITION_ALL_OFF,
            [
                new Tracker_Workflow_Trigger_FieldValue(
                    $this->trigger_field,
                    $this->trigger_value
                ),
                new Tracker_Workflow_Trigger_FieldValue(
                    $trigger_field_2,
                    $this->trigger_value_2
                ),
            ]
        );

        $this->strategy_complex_rule = new Tracker_Workflow_Trigger_RulesProcessor_AllOfStrategy(
            $this->artifact,
            $complex_rule,
            $this->siblings_retriever
        );
    }

    private function buildTracker(int $id): Tracker
    {
        return new Tracker(
            $id,
            102,
            'Test ' . $id,
            null,
            'test_' . $id,
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            TrackerColor::default(),
            null
        );
    }

    private function buildSelectBoxField(int $id, Tracker $tracker): Tracker_FormElement_Field_Selectbox
    {
        $field = new Tracker_FormElement_Field_Selectbox(
            $id,
            $tracker->getId(),
            0,
            'name',
            'label',
            'description',
            true,
            'S',
            false,
            false,
            0
        );
        $field->setTracker($tracker);
        return $field;
    }

    public function testItSetTheValueIfArtifactHasNoSiblings(): void
    {
        $this->siblings_retriever->shouldReceive('getSiblingsWithoutPermissionChecking')->andReturn([]);

        $this->assertTrue($this->strategy->allPrecondtionsAreMet());
    }

    public function testItDoesntSetTheValueIfOneSiblingHasNoValue(): void
    {
        $sibling = new Artifact(
            3,
            $this->task_tracker->getId(),
            null,
            10,
            null
        );
        $sibling->setTracker($this->task_tracker);
        $sibling->setChangesets([]);
        $this->siblings_retriever->shouldReceive('getSiblingsWithoutPermissionChecking')->andReturn([$sibling]);

        $this->assertEquals(0, $this->strategy->allPrecondtionsAreMet());
    }

    public function testItSetTheValueIfOneSameTypeSiblingHasCorrectValue(): void
    {
        $sibling = \Mockery::spy(\Tuleap\Tracker\Artifact\Artifact::class);
        $sibling->shouldReceive('getId')->andReturns(112);
        $sibling->shouldReceive('getTracker')->andReturns($this->task_tracker);
        $changeset_value_list = new Tracker_Artifact_ChangesetValue_List(41, Mockery::mock(Tracker_Artifact_Changeset::class), null, null, [$this->trigger_value]);
        $sibling->shouldReceive('getValue')->with($this->trigger_field)->andReturns($changeset_value_list);
        $this->siblings_retriever->shouldReceive('getSiblingsWithoutPermissionChecking')->andReturn([$sibling]);

        $this->assertEquals(1, $this->strategy->allPrecondtionsAreMet());
    }

    public function testItDoesntSetTheValueIfOneSameTypeSiblingHasIncorrectValue(): void
    {
        $sibling_1 = \Mockery::spy(\Tuleap\Tracker\Artifact\Artifact::class);
        $sibling_1->shouldReceive('getId')->andReturns(112);
        $sibling_1->shouldReceive('getTracker')->andReturns($this->task_tracker);
        $sibling_1->shouldReceive('getValue')->with($this->trigger_field)->andReturns(
            new Tracker_Artifact_ChangesetValue_List(43, Mockery::mock(Tracker_Artifact_Changeset::class), null, null, [$this->trigger_value])
        );

        $sibling_2 = \Mockery::spy(\Tuleap\Tracker\Artifact\Artifact::class);
        $sibling_2->shouldReceive('getId')->andReturns(113);
        $sibling_2->shouldReceive('getTracker')->andReturns($this->task_tracker);
        $bind_static_value = ListStaticValueBuilder::aStaticValue('label')->withId(74)->build();
        $sibling_2->shouldReceive('getValue')->with($this->trigger_field)->andReturns(
            new Tracker_Artifact_ChangesetValue_List(43, Mockery::mock(Tracker_Artifact_Changeset::class), null, null, [$bind_static_value])
        );

        $this->siblings_retriever->shouldReceive('getSiblingsWithoutPermissionChecking')->andReturn([$sibling_1, $sibling_2]);

        $this->assertEquals(0, $this->strategy->allPrecondtionsAreMet());
    }

    public function testItSetTheValueIfDifferentTypeSiblingHaveLegitValue(): void
    {
        $sibling_1 = \Mockery::spy(\Tuleap\Tracker\Artifact\Artifact::class);
        $sibling_1->shouldReceive('getId')->andReturns(112);
        $sibling_1->shouldReceive('getTracker')->andReturns($this->task_tracker);
        $sibling_1->shouldReceive('getValue')->with($this->trigger_field)->andReturns(
            new Tracker_Artifact_ChangesetValue_List(41, Mockery::mock(Tracker_Artifact_Changeset::class), null, null, [$this->trigger_value])
        );

        $sibling_2 = \Mockery::spy(\Tuleap\Tracker\Artifact\Artifact::class);
        $sibling_2->shouldReceive('getId')->andReturns(113);
        $sibling_1->shouldReceive('getTracker')->andReturns($this->bug_tracker);
        $sibling_2->shouldReceive('getValue')->with($this->trigger_field)->andReturns(
            new Tracker_Artifact_ChangesetValue_List(43, Mockery::mock(Tracker_Artifact_Changeset::class), null, null, [$this->trigger_value_2])
        );

        $this->siblings_retriever->shouldReceive('getSiblingsWithoutPermissionChecking')->andReturn([$sibling_1, $sibling_2]);

        $this->assertEquals(1, $this->strategy_complex_rule->allPrecondtionsAreMet());
    }

    public function testItDoesntSetTheValueIfOneOfTheChildDoesntApply(): void
    {
        $sibling_1 = \Mockery::spy(\Tuleap\Tracker\Artifact\Artifact::class);
        $sibling_1->shouldReceive('getId')->andReturns(112);
        $sibling_1->shouldReceive('getTracker')->andReturns($this->task_tracker);
        $sibling_1->shouldReceive('getValue')->with($this->trigger_field)->andReturns(
            new Tracker_Artifact_ChangesetValue_List(41, Mockery::mock(Tracker_Artifact_Changeset::class), null, null, [$this->trigger_value])
        );

        $sibling_2 = \Mockery::spy(\Tuleap\Tracker\Artifact\Artifact::class);
        $sibling_2->shouldReceive('getId')->andReturns(113);
        $sibling_2->shouldReceive('getTracker')->andReturns($this->bug_tracker);
        $bind_static_value = ListStaticValueBuilder::aStaticValue('label')->withId(74)->build();
        $sibling_2->shouldReceive('getValue')->with($this->trigger_field)->andReturns(
            new Tracker_Artifact_ChangesetValue_List(43, Mockery::mock(Tracker_Artifact_Changeset::class), null, null, [$bind_static_value])
        );

        $this->siblings_retriever->shouldReceive('getSiblingsWithoutPermissionChecking')->andReturn([$sibling_1, $sibling_2]);

        $this->assertEquals(0, $this->strategy_complex_rule->allPrecondtionsAreMet());
    }
}
