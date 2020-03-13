<?php
/**
 * Copyright (c) Enalean, 2013. All Rights Reserved.
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

require_once __DIR__ . '/../../../bootstrap.php';

abstract class Tracker_Workflow_Trigger_RulesProcessor_AllOfStrategy_BaseTest extends TuleapTestCase
{

    protected $parent;
    protected $artifact;
    protected $target_field_id;
    protected $target_value_id;
    protected $rule;
    protected $target_field;
    protected $target_value;
    protected $trigger_field;
    protected $trigger_value;
    protected $task_tracker;
    protected $story_tracker;

    public function setUp()
    {
        parent::setUp();
        $this->setUpGlobalsMockery();

        $this->story_tracker = aTracker()->withId(888)->build();
        $this->task_tracker = aTracker()->withId(899)->build();

        $this->parent = \Mockery::spy(\Tracker_Artifact::class);
        stub($this->parent)->getTracker()->returns($this->story_tracker);

        $this->artifact = anArtifact()->withChangesets(array(\Mockery::spy(\Tracker_Artifact_Changeset::class)))->withParentWithoutPermissionChecking($this->parent)->withTracker($this->task_tracker)->build();

        $this->target_field_id = 569;
        $this->target_field    = aSelectBoxField()->withId($this->target_field_id)->withTracker($this->story_tracker)->build();
        $this->target_value_id = 7;
        $this->target_value    = aBindStaticValue()->withId($this->target_value_id)->build();

        $this->trigger_field = aSelectBoxField()->withId(965)->withTracker($this->task_tracker)->build();
        $this->trigger_value = aBindStaticValue()->withId(14)->build();

        $this->rule = new Tracker_Workflow_Trigger_TriggerRule(
            1,
            new Tracker_Workflow_Trigger_FieldValue(
                $this->target_field,
                $this->target_value
            ),
            Tracker_Workflow_Trigger_RulesBuilderData::CONDITION_ALL_OFF,
            [
                new Tracker_Workflow_Trigger_FieldValue(
                    $this->trigger_field,
                    $this->trigger_value
                )
            ]
        );
    }
}

class Tracker_Workflow_Trigger_RulesProcessor_AllOfStrategy_OneRuleTest extends Tracker_Workflow_Trigger_RulesProcessor_AllOfStrategy_BaseTest
{
    public function setUp()
    {
        parent::setUp();
        $this->setUpGlobalsMockery();

        $this->strategy = new Tracker_Workflow_Trigger_RulesProcessor_AllOfStrategy($this->artifact, $this->rule);
    }

    public function itSetTheValueIfArtifactHasNoSiblings()
    {
        $this->artifact->setSiblingsWithoutPermissionChecking(new ArrayIterator());

        $this->assertTrue($this->strategy->allPrecondtionsAreMet());
    }

    public function itDoesntSetTheValueIfOneSiblingHasNoValue()
    {
        $sibling = aMockArtifact()->withTracker($this->task_tracker)->build();
        $this->artifact->setSiblingsWithoutPermissionChecking(new ArrayIterator(array($sibling)));

        $this->assertFalse($this->strategy->allPrecondtionsAreMet());
    }

    public function itSetTheValueIfOneSameTypeSiblingHasCorrectValue()
    {
        $sibling = \Mockery::spy(\Tracker_Artifact::class);
        stub($sibling)->getId()->returns(112);
        stub($sibling)->getTracker()->returns($this->task_tracker);
        stub($sibling)->getValue($this->trigger_field)->returns(aChangesetValueList()->withValues(array($this->trigger_value))->build());
        $this->artifact->setSiblingsWithoutPermissionChecking(new ArrayIterator(array($sibling)));

        $this->assertTrue($this->strategy->allPrecondtionsAreMet());
    }

    public function itDoesntSetTheValueIfOneSameTypeSiblingHasIncorrectValue()
    {
        $sibling_1 = \Mockery::spy(\Tracker_Artifact::class);
        stub($sibling_1)->getId()->returns(112);
        stub($sibling_1)->getTracker()->returns($this->task_tracker);
        stub($sibling_1)->getValue($this->trigger_field)->returns(aChangesetValueList()->withValues(array($this->trigger_value))->build());

        $sibling_2 = \Mockery::spy(\Tracker_Artifact::class);
        stub($sibling_2)->getId()->returns(113);
        stub($sibling_2)->getTracker()->returns($this->task_tracker);
        stub($sibling_2)->getValue($this->trigger_field)->returns(aChangesetValueList()->withValues(array(aBindStaticValue()->withId('whatever')->build()))->build());

        $this->artifact->setSiblingsWithoutPermissionChecking(new ArrayIterator(array($sibling_1, $sibling_2)));

        $this->assertFalse($this->strategy->allPrecondtionsAreMet());
    }
}

class Tracker_Workflow_Trigger_RulesProcessor_AllOfStrategy_SeveralRulesTest extends Tracker_Workflow_Trigger_RulesProcessor_AllOfStrategy_BaseTest
{

    private $bug_tracker;

    public function setUp()
    {
        parent::setUp();
        $this->setUpGlobalsMockery();

        $this->bug_tracker  = aTracker()->withId(901)->build();

        $this->trigger_field_2 = aSelectBoxField()->withId(236)->withTracker($this->bug_tracker)->build();
        $this->trigger_value_2 = aBindStaticValue()->withId(28)->build();

        $this->complex_rule = new Tracker_Workflow_Trigger_TriggerRule(
            1,
            new Tracker_Workflow_Trigger_FieldValue(
                $this->target_field,
                $this->target_value
            ),
            Tracker_Workflow_Trigger_RulesBuilderData::CONDITION_ALL_OFF,
            [
                new Tracker_Workflow_Trigger_FieldValue(
                    $this->trigger_field,
                    $this->trigger_value
                ),
                new Tracker_Workflow_Trigger_FieldValue(
                    $this->trigger_field_2,
                    $this->trigger_value_2
                )
            ]
        );

        $this->strategy = new Tracker_Workflow_Trigger_RulesProcessor_AllOfStrategy($this->artifact, $this->complex_rule);
    }

    public function itSetTheValueIfDifferentTypeSiblingHaveLegitValue()
    {
        $sibling_1 = \Mockery::spy(\Tracker_Artifact::class);
        stub($sibling_1)->getId()->returns(112);
        stub($sibling_1)->getTracker()->returns($this->task_tracker);
        stub($sibling_1)->getValue($this->trigger_field)->returns(aChangesetValueList()->withValues(array($this->trigger_value))->build());

        $sibling_2 = \Mockery::spy(\Tracker_Artifact::class);
        stub($sibling_2)->getId()->returns(113);
        stub($sibling_1)->getTracker()->returns($this->bug_tracker);
        stub($sibling_2)->getValue($this->trigger_field)->returns(aChangesetValueList()->withValues(array($this->trigger_value_2))->build());

        $this->artifact->setSiblingsWithoutPermissionChecking(new ArrayIterator(array($sibling_1, $sibling_2)));

        $this->assertTrue($this->strategy->allPrecondtionsAreMet());
    }

    public function itDoesntSetTheValueIfOneOfTheChildDoesntApply()
    {
        $sibling_1 = \Mockery::spy(\Tracker_Artifact::class);
        stub($sibling_1)->getId()->returns(112);
        stub($sibling_1)->getTracker()->returns($this->task_tracker);
        stub($sibling_1)->getValue($this->trigger_field)->returns(aChangesetValueList()->withValues(array($this->trigger_value))->build());

        $sibling_2 = \Mockery::spy(\Tracker_Artifact::class);
        stub($sibling_2)->getId()->returns(113);
        stub($sibling_2)->getTracker()->returns($this->bug_tracker);
        stub($sibling_2)->getValue($this->trigger_field)->returns(aChangesetValueList()->withValues(array(aBindStaticValue()->withId('whatever')->build()))->build());

        $this->artifact->setSiblingsWithoutPermissionChecking(new ArrayIterator(array($sibling_1, $sibling_2)));

        $this->assertFalse($this->strategy->allPrecondtionsAreMet());
    }
}
