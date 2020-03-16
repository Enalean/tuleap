<?php
/**
 * Copyright (c) Enalean, 2011 - Present. All Rights Reserved.
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

use Tuleap\Tracker\Workflow\PostAction\FrozenFields\FrozenFieldsFactory;
use Tuleap\Tracker\Workflow\PostAction\HiddenFieldsets\HiddenFieldsetsFactory;

require_once __DIR__ . '/../../bootstrap.php';

class Transition_PostActionFactory_BaseTest extends TuleapTestCase
{

    /**
     * @var Transition_PostActionFactory
     */
    protected $factory;
    /**
     * @var \Transition_PostAction_FieldFactory
     */
    protected $field_factory;
    /**
     * @var \Transition_PostAction_CIBuildFactory
     */
    protected $cibuild_factory;
    /**
     * @var FrozenFieldsFactory
     */
    protected $frozen_fields_actory;
    /**
     * @var int
     */
    protected $transition_id;
    /**
     * @var \Transition
     */
    protected $transition;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|HiddenFieldsetsFactory
     */
    protected $hidden_fieldset_factory;

    /**
     * @var EventManager|\Mockery\LegacyMockInterface|\Mockery\MockInterface
     */
    protected $event_manager;

    public function setUp()
    {
        parent::setUp();
        $this->setUpGlobalsMockery();

        $this->transition_id = 123;
        $this->transition    = mockery_stub(\Transition::class)->getTransitionId()->returns($this->transition_id);

        $this->event_manager = Mockery::mock(EventManager::class);
        $this->factory = new Transition_PostActionFactory($this->event_manager);

        $this->field_factory        = \Mockery::spy(\Transition_PostAction_FieldFactory::class);
        $this->cibuild_factory      = \Mockery::spy(\Transition_PostAction_CIBuildFactory::class);
        $this->frozen_fields_actory = \Mockery::spy(FrozenFieldsFactory::class);
        $this->hidden_fieldset_factory = \Mockery::spy(HiddenFieldsetsFactory::class);

        $this->factory->setFieldFactory($this->field_factory);
        $this->factory->setCIBuildFactory($this->cibuild_factory);
        $this->factory->setFrozenFieldsFactory($this->frozen_fields_actory);
        $this->factory->setHiddenFieldsetsFactory($this->hidden_fieldset_factory);
    }
}

class Transition_PostActionFactory_DuplicateTest extends Transition_PostActionFactory_BaseTest
{

    public function itDelegatesDuplicationToTheOtherPostActionFactories()
    {
        $field_mapping = array(
            1 => array('from' => 2066, 'to' => 3066),
            2 => array('from' => 2067, 'to' => 3067),
        );

        stub($this->field_factory)->duplicate($this->transition, 2, $field_mapping)->once();
        stub($this->cibuild_factory)->duplicate($this->transition, 2, $field_mapping)->once();
        stub($this->frozen_fields_actory)->duplicate($this->transition, 2, $field_mapping)->once();

        $this->event_manager->shouldReceive('processEvent')->once();

        $this->factory->duplicate($this->transition, 2, $field_mapping);
    }
}

class Transition_PostActionFactory_GetInstanceFromXmlTest extends Transition_PostActionFactory_BaseTest
{

    public function itreturnsAFieldDatePostActionIfXmlCorrespondsToADate()
    {
        $xml = new SimpleXMLElement('
            <postactions>
                <postaction_field_date valuetype="1">
                    <field_id REF="F1"/>
                </postaction_field_date>
            </postactions>
        ');

        $mapping = array('F1' => 62334);

        stub($this->field_factory)
            ->getInstanceFromXML(
                Mockery::on(function (SimpleXMLElement $xml_postaction) {
                    return (string) $xml_postaction->field_id['REF'] === 'F1' &&
                        (string) $xml_postaction['valuetype'] === '1';
                }),
                $mapping,
                $this->transition
            )
            ->returns(\Mockery::spy(\Transition_PostAction_Field_Date::class));

        $post_actions = $this->factory->getInstanceFromXML($xml, $mapping, $this->transition);
        $this->assertIsA($post_actions[0], 'Transition_PostAction_Field_Date');
    }

    public function itreturnsAFieldIntPostActionIfXmlCorrespondsToAInt()
    {
        $xml = new SimpleXMLElement('
            <postactions>
                <postaction_field_int valuetype="1">
                    <field_id REF="F1"/>
                </postaction_field_int>
            </postactions>
        ');

        $mapping = array('F1' => 62334);

        stub($this->field_factory)
            ->getInstanceFromXML(
                Mockery::on(function (SimpleXMLElement $xml_postaction) {
                    return (string) $xml_postaction->field_id['REF'] === 'F1' &&
                        (string) $xml_postaction['valuetype'] === '1';
                }),
                $mapping,
                $this->transition
            )
            ->returns(\Mockery::spy(\Transition_PostAction_Field_Int::class));

        $post_actions = $this->factory->getInstanceFromXML($xml, $mapping, $this->transition);
        $this->assertIsA($post_actions[0], 'Transition_PostAction_Field_Int');
    }

    public function itreturnsAFieldFloatPostActionIfXmlCorrespondsToAFloat()
    {
        $xml = new SimpleXMLElement('
            <postactions>
                <postaction_field_float valuetype="3.14">
                    <field_id REF="F1"/>
                </postaction_field_float>
            </postactions>
        ');

        $mapping = array('F1' => 62334);

        stub($this->field_factory)
            ->getInstanceFromXML(
                Mockery::on(function (SimpleXMLElement $xml_postaction) {
                    return (string) $xml_postaction->field_id['REF'] === 'F1' &&
                        (string) $xml_postaction['valuetype'] === '3.14';
                }),
                $mapping,
                $this->transition
            )
            ->returns(\Mockery::spy(\Transition_PostAction_Field_Float::class));

        $post_actions = $this->factory->getInstanceFromXML($xml, $mapping, $this->transition);
        $this->assertIsA($post_actions[0], 'Transition_PostAction_Field_Float');
    }

    public function itreturnsACIBuildPostActionIfXmlCorrespondsToACIBuild()
    {
        $xml = new SimpleXMLElement('
            <postactions>
                <postaction_ci_build job_url="http://www">
                </postaction_ci_build>
            </postactions>
        ');

        $mapping = array('F1' => 62334);

        stub($this->cibuild_factory)
            ->getInstanceFromXML(
                Mockery::on(function (SimpleXMLElement $xml_postaction) {
                    return (string) $xml_postaction['job_url'] === 'http://www';
                }),
                $mapping,
                $this->transition
            )
            ->returns(\Mockery::spy(\Transition_PostAction_CIBuild::class));

        $post_actions = $this->factory->getInstanceFromXML($xml, $mapping, $this->transition);
        $this->assertIsA($post_actions[0], 'Transition_PostAction_CIBuild');
    }

    public function itLoadsAllPostActionsFromXML()
    {
        $xml = new SimpleXMLElement('
            <postactions>
                <postaction_field_date valuetype="1">
                    <field_id REF="F1"/>
                </postaction_field_date>
                <postaction_ci_build job_url="http://www">
                </postaction_ci_build>
            </postactions>
        ');

        $mapping = array('F1' => 62334);

        stub($this->field_factory)
            ->getInstanceFromXML()
            ->returns(\Mockery::spy(\Transition_PostAction_Field_Date::class));

        stub($this->cibuild_factory)
            ->getInstanceFromXML()
            ->returns(\Mockery::spy(\Transition_PostAction_CIBuild::class));

        $post_actions = $this->factory->getInstanceFromXML($xml, $mapping, $this->transition);
        $this->assertIsA($post_actions[0], 'Transition_PostAction_Field_Date');
        $this->assertIsA($post_actions[1], 'Transition_PostAction_CIBuild');
    }
}
class Transition_PostActionFactory_SaveObjectTest extends Transition_PostActionFactory_BaseTest
{

    public function itSavesDateFieldPostActions()
    {
        $post_action = \Mockery::spy(\Transition_PostAction_Field_Date::class);
        stub($this->cibuild_factory)->saveObject()->never();
        stub($this->field_factory)->saveObject($post_action)->once();

        $this->factory->saveObject($post_action);
    }

    public function itSavesIntFieldPostActions()
    {
        $post_action = \Mockery::spy(\Transition_PostAction_Field_Int::class);
        stub($this->cibuild_factory)->saveObject()->never();
        stub($this->field_factory)->saveObject($post_action)->once();

        $this->factory->saveObject($post_action);
    }

    public function itSavesFloatFieldPostActions()
    {
        $post_action = \Mockery::spy(\Transition_PostAction_Field_Float::class);
        stub($this->cibuild_factory)->saveObject()->never();
        stub($this->field_factory)->saveObject($post_action)->once();

        $this->factory->saveObject($post_action);
    }

    public function itSavesCIBuildPostActions()
    {
        $post_action = \Mockery::spy(\Transition_PostAction_CIBuild::class);
        stub($this->field_factory)->saveObject()->never();
        stub($this->cibuild_factory)->saveObject($post_action)->once();

        $this->factory->saveObject($post_action);
    }
}

class Transition_PostActionFactory_IsFieldUsedInPostActionsTest extends Transition_PostActionFactory_BaseTest
{

    public function itChecksFieldIsUsedInEachTypeOfPostAction()
    {
        $field = \Mockery::spy(\Tracker_FormElement_Field_Selectbox::class);
        stub($this->cibuild_factory)->isFieldUsedInPostActions($field)->once()->returns(false);
        stub($this->field_factory)->isFieldUsedInPostActions($field)->once()->returns(false);

        $this->event_manager->shouldReceive('processEvent')->once();

        $this->assertFalse($this->factory->isFieldUsedInPostActions($field));
    }

    public function itReturnsTrueIfAtLeastOneOfTheSubFactoryReturnsTrue()
    {
        $field = \Mockery::spy(\Tracker_FormElement_Field_Selectbox::class);

        stub($this->field_factory)->isFieldUsedInPostActions($field)->returns(true);

        $this->event_manager->shouldReceive('processEvent')->once();

        $this->assertTrue($this->factory->isFieldUsedInPostActions($field));
    }
}

class Transition_PostActionFactory_loadPostActionsTest extends Transition_PostActionFactory_BaseTest
{

    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|Transition_PostAction
     */
    private $post_action_1;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|Transition_PostAction
     */
    private $post_action_2;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|Transition_PostAction
     */
    private $post_action_3;

    public function setUp()
    {
        parent::setUp();
        $this->setUpGlobalsMockery();
        $this->post_action_1 = \Mockery::spy(\Transition_PostAction::class);
        $this->post_action_2 = \Mockery::spy(\Transition_PostAction::class);
        $this->post_action_3 = \Mockery::spy(\Transition_PostAction::class);
    }

    public function itLoadsPostActionFromAllSubFactories()
    {
        stub($this->cibuild_factory)->loadPostActions($this->transition)->returns(array($this->post_action_1))->once();
        stub($this->field_factory)->loadPostActions($this->transition)->returns(array($this->post_action_2))->once();
        $this->hidden_fieldset_factory->shouldReceive('loadPostActions')->with($this->transition)->once()->andReturn([$this->post_action_3]);

        $this->event_manager->shouldReceive('processEvent')->once();

        $this->factory->loadPostActions($this->transition);
    }

    public function itInjectsPostActionsIntoTheTransition()
    {
        stub($this->cibuild_factory)->loadPostActions($this->transition)->returns(array($this->post_action_1));
        stub($this->field_factory)->loadPostActions($this->transition)->returns(array($this->post_action_2));
        $this->hidden_fieldset_factory->shouldReceive('loadPostActions')->with($this->transition)->once()->andReturn([$this->post_action_3]);

        $expected     = array($this->post_action_1, $this->post_action_2, $this->post_action_3);
        expect($this->transition)->setPostActions($expected)->once();

        $this->event_manager->shouldReceive('processEvent')->once();

        $this->factory->loadPostActions($this->transition);
    }
}
