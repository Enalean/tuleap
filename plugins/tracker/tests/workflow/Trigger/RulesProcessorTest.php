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

require_once TRACKER_BASE_DIR . '/../tests/bootstrap.php';

abstract class Tracker_Workflow_Trigger_RulesProcessor_BaseTest  extends TuleapTestCase {

    protected $parent;
    protected $artifact;
    protected $rules_processor;
    protected $target_field_id;
    protected $target_value_id;
    protected $rule;
    protected $target_field;
    protected $target_value;
    protected $trigger_field;
    protected $trigger_value;
    protected $task_tracker;
    protected $story_tracker;

    public function setUp() {
        parent::setUp();

        $this->story_tracker = aTracker()->withId(888)->build();
        $this->task_tracker = aTracker()->withId(899)->build();

        $this->parent = mock('Tracker_Artifact');
        stub($this->parent)->getTracker()->returns($this->story_tracker);

        $this->artifact = anArtifact()->withChangesets(array(mock('Tracker_Artifact_Changeset')))->withParentWithoutPermissionChecking($this->parent)->withTracker($this->task_tracker)->build();

        $this->rules_processor = new Tracker_Workflow_Trigger_RulesProcessor(
            new Tracker_Workflow_WorkflowUser(),
            mock('WorkflowBackendLogger')
        );

        $this->target_field_id = 569;
        $this->target_field    = aSelectBoxField()->withId($this->target_field_id)->withTracker($this->story_tracker)->build();
        $this->target_value_id = 7;
        $this->target_value    = aBindStaticValue()->withId($this->target_value_id)->build();

        $this->trigger_field = aSelectBoxField()->withId(965)->withTracker($this->task_tracker)->build();
        $this->trigger_value = aBindStaticValue()->withId(14)->build();

        $this->rule = aTriggerRule()
            ->applyValue(
                new Tracker_Workflow_Trigger_FieldValue(
                    $this->target_field,
                    $this->target_value
                )
            )
            ->whenAllOf()
            ->childHas(
                new Tracker_Workflow_Trigger_FieldValue(
                    $this->trigger_field,
                    $this->trigger_value
                )
            )
            ->build();
    }

}

class Tracker_Workflow_Trigger_RulesProcessor_GeneralTest extends TuleapTestCase {

    private $parent;
    private $artifact;
    private $user;
    private $rules_processor;
    private $target_field_id;
    private $target_value_id;
    private $rule;
    private $target_field;
    private $target_value;

    public function setUp() {
        parent::setUp();
        $this->parent = mock('Tracker_Artifact');
        $this->task_tracker = aTracker()->withId(899)->build();
        $this->artifact = anArtifact()->withChangesets(array(mock('Tracker_Artifact_Changeset')))->withParentWithoutPermissionChecking($this->parent)->withTracker($this->task_tracker)->build();
        $this->user = aUser()->build();
        $this->rules_processor = new Tracker_Workflow_Trigger_RulesProcessor(
            new Tracker_Workflow_WorkflowUser(),
            mock('WorkflowBackendLogger')
        );

        $this->target_field_id = 569;
        $this->target_field    = aSelectBoxField()->withId($this->target_field_id)->build();
        $this->target_value_id = 7;
        $this->target_value    = aBindStaticValue()->withId($this->target_value_id)->build();
        $this->rule = mock('Tracker_Workflow_Trigger_TriggerRule');
    }

    public function itDoesNothingWhenArtifactHasNoParents() {
        $artifact = anArtifact()->withTracker($this->task_tracker)->withoutParentWithoutPermissionChecking()->build();

        $this->rules_processor->process($artifact, $this->rule);
    }
}

class Tracker_Workflow_Trigger_RulesProcessorTest extends TuleapTestCase {

    private $parent;
    private $artifact;
    private $user;
    private $rules_processor;
    private $target_field_id;
    private $target_value_id;
    private $rule;
    private $target_field;
    private $target_value;

    public function setUp() {
        parent::setUp();
        $this->parent = mock('Tracker_Artifact');
        $this->task_tracker = aTracker()->withId(899)->build();
        $this->artifact = anArtifact()->withChangesets(array(mock('Tracker_Artifact_Changeset')))->withParentWithoutPermissionChecking($this->parent)->withTracker($this->task_tracker)->build();
        $this->user = aUser()->build();
        $this->rules_processor = new Tracker_Workflow_Trigger_RulesProcessor(
            new Tracker_Workflow_WorkflowUser(),
            mock('WorkflowBackendLogger')
        );

        $this->target_field_id = 569;
        $this->target_field    = aSelectBoxField()->withId($this->target_field_id)->build();
        $this->target_value_id = 7;
        $this->target_value    = aBindStaticValue()->withId($this->target_value_id)->build();
        $this->rule = aTriggerRule()
            ->applyValue(
                new Tracker_Workflow_Trigger_FieldValue(
                    $this->target_field,
                    $this->target_value
                )
            )
            ->whenAtLeastOne()
            ->childHas(mock('Tracker_Workflow_Trigger_FieldValue'))
            ->build();
    }

    public function itAlwaysApplyRuleWhenAtLeastOneValueIsSet() {
        $fields_data = array(
            $this->target_field_id => $this->target_value_id
        );
        $email = '';
        $send_notification = true;

        expect($this->parent)->createNewChangeset($fields_data, '*', new IsAWorkflowUserExpectation(), $email, $send_notification, Tracker_Artifact_Changeset_Comment::HTML_COMMENT)->once();

        $this->rules_processor->process($this->artifact, $this->rule);
    }

    public function itDoesntSetTargetValueIfAlreadySet() {
        stub($this->parent)->getValue($this->target_field)->returns(aChangesetValueList()->withValues(array($this->target_value))->build());
        expect($this->parent)->createNewChangeset()->never();
        $this->rules_processor->process($this->artifact, $this->rule);
    }
}

class IsAWorkflowUserExpectation extends SimpleExpectation {
    public function test($compare) {
        return $compare instanceof Tracker_Workflow_WorkflowUser;
    }

    public function testMessage($compare) {
        return 'Tracker_Workflow_WorkflowUser expected, '.get_class($compare).' given';
    }
}

class Tracker_Workflow_Trigger_RulesProcessor_AllOfTest extends Tracker_Workflow_Trigger_RulesProcessor_BaseTest {

    private $bug_tracker;

    public function setUp() {
        parent::setUp();

        $this->bug_tracker  = aTracker()->withId(901)->build();

    }

    public function itDoesntSetTargetValueIfAlreadySet() {
        stub($this->parent)->getValue($this->target_field)->returns(aChangesetValueList()->withValues(array($this->target_value))->build());
        expect($this->parent)->createNewChangeset()->never();
        $this->rules_processor->process($this->artifact, $this->rule);
    }

    public function itSetTheValueIfArtifactHasNoSiblings() {
        $this->artifact->setSiblingsWithoutPermissionChecking(new ArrayIterator());
        expect($this->parent)->createNewChangeset()->once();

        $this->rules_processor->process($this->artifact, $this->rule);
    }

    public function itDoesntSetTheValueIfOneSiblingHasNoValue() {
        $sibling = aMockArtifact()->withTracker($this->task_tracker)->build();
        $this->artifact->setSiblingsWithoutPermissionChecking(new ArrayIterator(array($sibling)));

        expect($this->parent)->createNewChangeset()->never();

        $this->rules_processor->process($this->artifact, $this->rule);
    }

    public function itSetTheValueIfOneSameTypeSiblingHasCorrectValue() {
        $sibling = mock('Tracker_Artifact');
        stub($sibling)->getId()->returns(112);
        stub($sibling)->getTracker()->returns($this->task_tracker);
        stub($sibling)->getValue($this->trigger_field)->returns(aChangesetValueList()->withValues(array($this->trigger_value))->build());
        $this->artifact->setSiblingsWithoutPermissionChecking(new ArrayIterator(array($sibling)));

        expect($this->parent)->createNewChangeset()->once();

        $this->rules_processor->process($this->artifact, $this->rule);
    }

    public function itDoesntSetTheValueIfOneSameTypeSiblingHasIncorrectValue() {
        $sibling_1 = mock('Tracker_Artifact');
        stub($sibling_1)->getId()->returns(112);
        stub($sibling_1)->getTracker()->returns($this->task_tracker);
        stub($sibling_1)->getValue($this->trigger_field)->returns(aChangesetValueList()->withValues(array($this->trigger_value))->build());

        $sibling_2 = mock('Tracker_Artifact');
        stub($sibling_2)->getId()->returns(113);
        stub($sibling_2)->getTracker()->returns($this->task_tracker);
        stub($sibling_2)->getValue($this->trigger_field)->returns(aChangesetValueList()->withValues(array(aBindStaticValue()->withId('whatever')->build()))->build());

        $this->artifact->setSiblingsWithoutPermissionChecking(new ArrayIterator(array($sibling_1, $sibling_2)));

        expect($this->parent)->createNewChangeset()->never();

        $this->rules_processor->process($this->artifact, $this->rule);
    }
}

class Tracker_Workflow_Trigger_RulesProcessor_AllOfWithSeveralRulesTest extends Tracker_Workflow_Trigger_RulesProcessor_BaseTest {

    private $bug_tracker;

    public function setUp() {
        parent::setUp();

        $this->bug_tracker  = aTracker()->withId(901)->build();

        $this->trigger_field_2 = aSelectBoxField()->withId(236)->withTracker($this->bug_tracker)->build();
        $this->trigger_value_2 = aBindStaticValue()->withId(28)->build();

        $this->complex_rule = aTriggerRule()
            ->applyValue(
                new Tracker_Workflow_Trigger_FieldValue(
                    $this->target_field,
                    $this->target_value
                )
            )
            ->whenAllOf()
            ->childHas(
                new Tracker_Workflow_Trigger_FieldValue(
                    $this->trigger_field,
                    $this->trigger_value
                )
            )
            ->childHas(
                new Tracker_Workflow_Trigger_FieldValue(
                    $this->trigger_field_2,
                    $this->trigger_value_2
                )
            )
            ->build();
    }

    public function itSetTheValueIfDifferentTypeSiblingHaveLegitValue() {
        $sibling_1 = mock('Tracker_Artifact');
        stub($sibling_1)->getId()->returns(112);
        stub($sibling_1)->getTracker()->returns($this->task_tracker);
        stub($sibling_1)->getValue($this->trigger_field)->returns(aChangesetValueList()->withValues(array($this->trigger_value))->build());

        $sibling_2 = mock('Tracker_Artifact');
        stub($sibling_2)->getId()->returns(113);
        stub($sibling_1)->getTracker()->returns($this->bug_tracker);
        stub($sibling_2)->getValue($this->trigger_field)->returns(aChangesetValueList()->withValues(array($this->trigger_value_2))->build());

        $this->artifact->setSiblingsWithoutPermissionChecking(new ArrayIterator(array($sibling_1, $sibling_2)));

        expect($this->parent)->createNewChangeset()->once();

        $this->rules_processor->process($this->artifact, $this->complex_rule);
    }

    public function itDoesntSetTheValueIfOneOfTheChildDoesntApply() {
        $sibling_1 = mock('Tracker_Artifact');
        stub($sibling_1)->getId()->returns(112);
        stub($sibling_1)->getTracker()->returns($this->task_tracker);
        stub($sibling_1)->getValue($this->trigger_field)->returns(aChangesetValueList()->withValues(array($this->trigger_value))->build());

        $sibling_2 = mock('Tracker_Artifact');
        stub($sibling_2)->getId()->returns(113);
        stub($sibling_2)->getTracker()->returns($this->bug_tracker);
        stub($sibling_2)->getValue($this->trigger_field)->returns(aChangesetValueList()->withValues(array(aBindStaticValue()->withId('whatever')->build()))->build());

        $this->artifact->setSiblingsWithoutPermissionChecking(new ArrayIterator(array($sibling_1, $sibling_2)));

        expect($this->parent)->createNewChangeset()->never();

        $this->rules_processor->process($this->artifact, $this->complex_rule);
    }
}

?>
