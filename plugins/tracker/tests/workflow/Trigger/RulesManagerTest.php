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

abstract class Tracker_Workflow_Trigger_RulesManagerTest extends TuleapTestCase {
    protected $manager;
    protected $dao;
    protected $target_value_id;

    public function setUp() {
        parent::setUp();
        $this->target_value_id     = 789;
        $this->dao                 = mock('Tracker_Workflow_Trigger_RulesDao');
        $this->formelement_factory = mock('Tracker_FormElementFactory');
        $this->rules_processor     = mock('Tracker_Workflow_Trigger_RulesProcessor');
        $this->manager             = new Tracker_Workflow_Trigger_RulesManager(
            $this->dao,
            $this->formelement_factory,
            $this->rules_processor,
            mock('WorkflowBackendLogger')
        );
    }
}

class Tracker_Workflow_Trigger_RulesManager_duplicateTest extends Tracker_Workflow_Trigger_RulesManagerTest {

    public function setUp() {
        parent::setUp();

        $this->manager = partial_mock(
            'Tracker_Workflow_Trigger_RulesManager',
            array('add', 'getForTargetTracker', 'getTriggers'),
            array(
                $this->dao,
                $this->formelement_factory,
                $this->rules_processor,
                mock('WorkflowBackendLogger')
            )
        );
    }

    public function itDuplicatesTriggerRulesFromOldTracker() {
        $template_tracker  = stub('Tracker')->getId()->returns(101);
        $new_field_01      = aMockField()->withTracker($template_tracker)->withId(502)->build();
        $new_field_02      = aMockField()->withTracker($template_tracker)->withId(503)->build();
        $new_field_03      = aMockField()->withTracker($template_tracker)->withId(501)->build();

        stub($this->formelement_factory)->getFieldById(502)->returns($new_field_01);
        stub($this->formelement_factory)->getFieldById(503)->returns($new_field_02);
        stub($this->formelement_factory)->getFieldById(501)->returns($new_field_03);

        $trigger_01 = new Tracker_Workflow_Trigger_FieldValue(
            aSelectBoxField()->withId(102)->build(),
            aBindStaticValue()->withId(101)->build()
        );

        $trigger_02 = new Tracker_Workflow_Trigger_FieldValue(
            aSelectBoxField()->withId(103)->build(),
            aBindStaticValue()->withId(104)->build()
        );

        $rule_01 = new Tracker_Workflow_Trigger_TriggerRule(
            0,
            new Tracker_Workflow_Trigger_FieldValue(
                aSelectBoxField()->withId(101)->build(),
                aBindStaticValue()->withId(101)->build()
            ),
            Tracker_Workflow_Trigger_RulesBuilderData::CONDITION_AT_LEAST_ONE,
            array(
                $trigger_01
            )
        );

        $rule_02 = new Tracker_Workflow_Trigger_TriggerRule(
            1,
            new Tracker_Workflow_Trigger_FieldValue(
                aSelectBoxField()->withId(101)->build(),
                aBindStaticValue()->withId(102)->build()
            ),
            Tracker_Workflow_Trigger_RulesBuilderData::CONDITION_ALL_OFF,
            array(
                $trigger_02
            )
        );


        stub($this->manager)->getForTargetTracker()->returns(
            array($rule_01, $rule_02)
        );

        stub($this->manager)->getTriggers(0)->returns($trigger_01);
        stub($this->manager)->getTriggers(1)->returns($trigger_02);

        $template_trackers = array(
           $template_tracker,
        );

        $field_mapping = array(
            0 => array(
                'from'   => 102,
                'to'     => 502,
                'values' => array(
                    101 => 601,
                    102 => 602
                )
            ),
            1 => array(
                'from'   => 103,
                'to'     => 503,
                'values' => array(
                    101 => 701,
                    102 => 702,
                    103 => 703,
                    104 => 704,
                )
            ),
            2 => array(
                'from'   => 101,
                'to'     => 501,
                'values' => array(
                    101 => 801,
                    102 => 802,
                )
            )
        );

        expect($this->manager)->add()->count(2);

        $this->manager->duplicate($template_trackers, $field_mapping);
    }

}

class Tracker_Workflow_Trigger_RulesManager_addTest extends Tracker_Workflow_Trigger_RulesManagerTest {

    public function setUp() {
        parent::setUp();

        $this->trigger_value_id_1 = 369;
        $this->trigger_value_id_2 = 258;
        $this->rule = new Tracker_Workflow_Trigger_TriggerRule(
            null,
            new Tracker_Workflow_Trigger_FieldValue(
                aSelectBoxField()->withId(12)->build(),
                aBindStaticValue()->withId($this->target_value_id)->build()
            ),
            Tracker_Workflow_Trigger_RulesBuilderData::CONDITION_AT_LEAST_ONE,
            array(
                new Tracker_Workflow_Trigger_FieldValue(
                    aSelectBoxField()->withId(23)->build(),
                    aBindStaticValue()->withId($this->trigger_value_id_1)->build()
                ),
                new Tracker_Workflow_Trigger_FieldValue(
                    aSelectBoxField()->withId(25)->build(),
                    aBindStaticValue()->withId($this->trigger_value_id_2)->build()
                ),
            )
        );
    }

    public function itAddsTargetFieldAndCondition() {
        expect($this->dao)->addTarget($this->target_value_id, Tracker_Workflow_Trigger_RulesBuilderData::CONDITION_AT_LEAST_ONE)->once();

        $this->manager->add($this->rule);
    }

    public function itAddsTriggeringFields() {
        $rule_id = 4587;
        stub($this->dao)->addTarget()->returns($rule_id);

        expect($this->dao)->addTriggeringField()->count(2);
        expect($this->dao)->addTriggeringField($rule_id, $this->trigger_value_id_1)->at(0);
        expect($this->dao)->addTriggeringField($rule_id, $this->trigger_value_id_2)->at(1);

        $this->manager->add($this->rule);
    }

    public function itUpdateRuleWithNewId() {
        $rule_id = 4587;
        stub($this->dao)->addTarget()->returns($rule_id);

        $this->manager->add($this->rule);

        $this->assertEqual($rule_id, $this->rule->getId());
    }

    public function itUsesTransactionToKeepConsistency() {
        expect($this->dao)->enableExceptionsOnError()->once();
        expect($this->dao)->startTransaction()->once();
        expect($this->dao)->commit()->once();
        $this->manager->add($this->rule);
    }
}

class Tracker_Workflow_Trigger_RulesManager_getFromTrackerTest extends Tracker_Workflow_Trigger_RulesManagerTest {

    public function setUp() {
        parent::setUp();

        $this->rule_id = 6347;

        $this->tracker_id = 4656;
        $this->tracker = aTracker()->withId($this->tracker_id)->build();

        $this->target_field_id = 12;
        $this->target_field_value = aBindStaticValue()->withId($this->target_value_id)->build();
        $this->target_field = aMockField()->withTracker($this->tracker)->build();
        stub($this->target_field)->getAllValues()->returns(
            array(
                aBindStaticValue()->withId(9998)->build(),
                $this->target_field_value,
                aBindStaticValue()->withId(9999)->build(),
            )
        );
        stub($this->formelement_factory)->getUsedFormElementFieldById($this->target_field_id)->returns($this->target_field);

        $this->trigger_field_id_1 = 369;
        $this->trigger_value_id_1 = 852;
        $this->trigger_field_value_1 = aBindStaticValue()->withId($this->trigger_value_id_1)->build();
        $this->trigger_field_1 = aMockField()->withId($this->trigger_field_id_1)->build();
        stub($this->trigger_field_1)->getAllValues()->returns(
            array(
                $this->trigger_field_value_1,
            )
        );
        stub($this->formelement_factory)->getUsedFormElementFieldById($this->trigger_field_id_1)->returns($this->trigger_field_1);

    }

    public function itFetchesDataFromDb() {
        expect($this->dao)->searchForTargetTracker($this->tracker_id)->once();
        stub($this->dao)->searchForTargetTracker()->returnsEmptyDar();

        $this->manager->getForTargetTracker($this->tracker);
    }

    public function itHasNoRules() {
        stub($this->dao)->searchForTargetTracker()->returnsEmptyDar();

        $rule_collection = $this->manager->getForTargetTracker($this->tracker);
        $this->assertIsA($rule_collection, 'Tracker_Workflow_Trigger_TriggerRuleCollection');
        $this->assertCount($rule_collection, 0);
    }

    public function setUpOneRule() {
        stub($this->dao)->searchForTargetTracker()->returnsDar(array(
            'id'             => $this->rule_id,
            'field_id'       => $this->target_field_id,
            'value_id'       => $this->target_value_id,
            'rule_condition' => Tracker_Workflow_Trigger_RulesBuilderData::CONDITION_AT_LEAST_ONE));
    }

    public function itHasOneElementInCollection() {
        $this->setUpOneRule();
        stub($this->dao)->searchForTriggeringFieldByRuleId()->returnsEmptyDar();

        $rule_collection = $this->manager->getForTargetTracker($this->tracker);
        $this->assertCount($rule_collection, 1);
    }

    public function itBuildsTheRuleWithId() {
        $this->setUpOneRule();
        stub($this->dao)->searchForTriggeringFieldByRuleId()->returnsEmptyDar();

        $rule = $this->manager->getForTargetTracker($this->tracker)->current();
        $this->assertEqual($rule->getId(), $this->rule_id);
    }

    public function itBuildsTheRuleTargetField() {
        $this->setUpOneRule();
        stub($this->dao)->searchForTriggeringFieldByRuleId()->returnsEmptyDar();

        $rule = $this->manager->getForTargetTracker($this->tracker)->current();
        $this->assertEqual($rule->getTarget()->getField(), $this->target_field);
        $this->assertEqual($rule->getTarget()->getValue(), $this->target_field_value);
    }

    public function itBuildsTheRuleCondition() {
        $this->setUpOneRule();
        stub($this->dao)->searchForTriggeringFieldByRuleId()->returnsEmptyDar();

        $rule = $this->manager->getForTargetTracker($this->tracker)->current();
        $this->assertEqual($rule->getCondition(), Tracker_Workflow_Trigger_RulesBuilderData::CONDITION_AT_LEAST_ONE);
    }

    public function itBuildsTheRuleWithOneTriggeringField() {
        $this->setUpOneRule();

        stub($this->dao)->searchForTriggeringFieldByRuleId($this->rule_id)->returnsDar(array(
            'field_id' => $this->trigger_field_id_1,
            'value_id' => $this->trigger_value_id_1,
        ));

        $rule = $this->manager->getForTargetTracker($this->tracker)->current();
        $this->assertCount($rule->getTriggers(), 1);
    }

    public function itBuildsTheRuleWithTheRightTriggeringField() {
        $this->setUpOneRule();

        stub($this->dao)->searchForTriggeringFieldByRuleId($this->rule_id)->returnsDar(array(
            'field_id' => $this->trigger_field_id_1,
            'value_id' => $this->trigger_value_id_1,
        ));

        $rule = $this->manager->getForTargetTracker($this->tracker)->current();
        $trigger = current($rule->getTriggers());
        $this->assertEqual($trigger->getField(), $this->trigger_field_1);
        $this->assertEqual($trigger->getValue(), $this->trigger_field_value_1);
    }
}

class Tracker_Workflow_Trigger_RulesManager_deleteByRuleIdTest extends Tracker_Workflow_Trigger_RulesManagerTest {

    private $tracker;
    private $rule;
    private $rule_id;

    public function setUp() {
        parent::setUp();
        $this->rule_id = 777;
        $this->tracker = aTracker()->build();
        $this->rule    = mock('Tracker_Workflow_Trigger_TriggerRule');
        stub($this->rule)->getId()->returns($this->rule_id);
        stub($this->rule)->getTargetTracker()->returns($this->tracker);
    }

    public function itDeletesTheTriggeringFields() {
        expect($this->dao)->deleteTriggeringFieldsByRuleId($this->rule_id)->once();

        $this->manager->delete($this->tracker, $this->rule);
    }

    public function itDeletesTheTarget() {
        expect($this->dao)->deleteTargetByRuleId($this->rule_id)->once();

        $this->manager->delete($this->tracker, $this->rule);
    }

    public function itUsesTransactionToKeepConsistency() {
        expect($this->dao)->enableExceptionsOnError()->once();
        expect($this->dao)->startTransaction()->once();
        expect($this->dao)->commit()->once();

        $this->manager->delete($this->tracker, $this->rule);
    }

    public function itRaisesAnExceptionWhenRuleTrackerDiffersFromGivenTracker() {
        $this->expectException('Tracker_Exception');

        $this->manager->delete(aTracker()->build(), $this->rule);
    }
}


class Tracker_Workflow_Trigger_RulesManager_processTriggersTest extends Tracker_Workflow_Trigger_RulesManagerTest {

    public function itProcessTheInvolvedTriggerRules() {
        $manager = partial_mock(
            'Tracker_Workflow_Trigger_RulesManager',
            array('getRuleById'),
            array($this->dao, $this->formelement_factory, $this->rules_processor, mock('WorkflowBackendLogger'))
        );

        $artifact  = mock('Tracker_Artifact');
        $trigger_1 = aTriggerRule()->withId(1)->build();
        $trigger_2 = aTriggerRule()->withId(2)->build();
        $changeset = stub('Tracker_Artifact_Changeset')->getId()->returns(3);
        stub($changeset)->getArtifact()->returns($artifact);

        stub($this->dao)->searchForInvolvedRulesIdsByChangesetId(3)->returnsDar(
            array('rule_id' => 1),
            array('rule_id' => 2)
        );
        stub($manager)->getRuleById(1)->returns($trigger_1);
        stub($manager)->getRuleById(2)->returns($trigger_2);

        expect($this->rules_processor)->process($artifact, $trigger_1)->at(1);
        expect($this->rules_processor)->process($artifact, $trigger_2)->at(2);
        $manager->processTriggers($changeset);
    }
}
?>
