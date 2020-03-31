<?php
/**
 * Copyright (c) Enalean, 2011 - Present. All Rights Reserved.
 *
 *  This file is a part of Tuleap.
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

use Tuleap\Tracker\Workflow\PostAction\FrozenFields\FrozenFieldsFactory;
use Tuleap\Tracker\Workflow\PostAction\HiddenFieldsets\HiddenFieldsetsFactory;

final class Transition_PostActionFactoryTest extends \PHPUnit\Framework\TestCase
{
    use \Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    /**
     * @var Transition_PostActionFactory
     */
    private $factory;
    /**
     * @var \Transition_PostAction_FieldFactory
     */
    private $field_factory;
    /**
     * @var \Transition_PostAction_CIBuildFactory
     */
    private $cibuild_factory;
    /**
     * @var FrozenFieldsFactory
     */
    private $frozen_fields_actory;
    /**
     * @var int
     */
    private $transition_id;
    /**
     * @var \Transition
     */
    private $transition;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|HiddenFieldsetsFactory
     */
    private $hidden_fieldset_factory;

    /**
     * @var EventManager|\Mockery\LegacyMockInterface|\Mockery\MockInterface
     */
    private $event_manager;
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

    protected function setUp(): void
    {
        $this->transition_id = 123;
        $this->transition    = Mockery::spy(\Transition::class);
        $this->transition->shouldReceive('getTransitionId')->andReturn($this->transition_id);

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

        $this->post_action_1 = \Mockery::spy(\Transition_PostAction::class);
        $this->post_action_2 = \Mockery::spy(\Transition_PostAction::class);
        $this->post_action_3 = \Mockery::spy(\Transition_PostAction::class);
    }

    public function testItDelegatesDuplicationToTheOtherPostActionFactories(): void
    {
        $field_mapping = array(
            1 => array('from' => 2066, 'to' => 3066),
            2 => array('from' => 2067, 'to' => 3067),
        );

        $this->field_factory->shouldReceive('duplicate')->with($this->transition, 2, $field_mapping)->once();
        $this->cibuild_factory->shouldReceive('duplicate')->with($this->transition, 2, $field_mapping)->once();
        $this->frozen_fields_actory->shouldReceive('duplicate')->with($this->transition, 2, $field_mapping)->once();

        $this->event_manager->shouldReceive('processEvent')->once();

        $this->factory->duplicate($this->transition, 2, $field_mapping);
    }

    public function testItReturnsAFieldDatePostActionIfXmlCorrespondsToADate(): void
    {
        $xml = new SimpleXMLElement('
            <postactions>
                <postaction_field_date valuetype="1">
                    <field_id REF="F1"/>
                </postaction_field_date>
            </postactions>
        ');

        $mapping = array('F1' => 62334);

        $this->field_factory->shouldReceive('getInstanceFromXML')->with(Mockery::on(function (SimpleXMLElement $xml_postaction) {
            return (string) $xml_postaction->field_id['REF'] === 'F1' &&
                (string) $xml_postaction['valuetype'] === '1';
        }), $mapping, $this->transition)->andReturns(\Mockery::spy(\Transition_PostAction_Field_Date::class));

        $post_actions = $this->factory->getInstanceFromXML($xml, $mapping, $this->transition);
        $this->assertInstanceOf(Transition_PostAction_Field_Date::class, $post_actions[0]);
    }

    public function testItReturnsAFieldIntPostActionIfXmlCorrespondsToAInt(): void
    {
        $xml = new SimpleXMLElement('
            <postactions>
                <postaction_field_int valuetype="1">
                    <field_id REF="F1"/>
                </postaction_field_int>
            </postactions>
        ');

        $mapping = array('F1' => 62334);

        $this->field_factory->shouldReceive('getInstanceFromXML')->with(Mockery::on(function (SimpleXMLElement $xml_postaction) {
            return (string) $xml_postaction->field_id['REF'] === 'F1' &&
                (string) $xml_postaction['valuetype'] === '1';
        }), $mapping, $this->transition)->andReturns(\Mockery::spy(\Transition_PostAction_Field_Int::class));

        $post_actions = $this->factory->getInstanceFromXML($xml, $mapping, $this->transition);
        $this->assertInstanceOf(Transition_PostAction_Field_Int::class, $post_actions[0]);
    }

    public function testItReturnsAFieldFloatPostActionIfXmlCorrespondsToAFloat(): void
    {
        $xml = new SimpleXMLElement('
            <postactions>
                <postaction_field_float valuetype="3.14">
                    <field_id REF="F1"/>
                </postaction_field_float>
            </postactions>
        ');

        $mapping = array('F1' => 62334);

        $this->field_factory->shouldReceive('getInstanceFromXML')->with(Mockery::on(function (SimpleXMLElement $xml_postaction) {
            return (string) $xml_postaction->field_id['REF'] === 'F1' &&
                (string) $xml_postaction['valuetype'] === '3.14';
        }), $mapping, $this->transition)->andReturns(\Mockery::spy(\Transition_PostAction_Field_Float::class));

        $post_actions = $this->factory->getInstanceFromXML($xml, $mapping, $this->transition);
        $this->assertInstanceOf(Transition_PostAction_Field_Float::class, $post_actions[0]);
    }

    public function testItReturnsACIBuildPostActionIfXmlCorrespondsToACIBuild(): void
    {
        $xml = new SimpleXMLElement('
            <postactions>
                <postaction_ci_build job_url="http://www">
                </postaction_ci_build>
            </postactions>
        ');

        $mapping = array('F1' => 62334);

        $this->cibuild_factory->shouldReceive('getInstanceFromXML')->with(Mockery::on(function (SimpleXMLElement $xml_postaction) {
            return (string) $xml_postaction['job_url'] === 'http://www';
        }), $mapping, $this->transition)->andReturns(\Mockery::spy(\Transition_PostAction_CIBuild::class));

        $post_actions = $this->factory->getInstanceFromXML($xml, $mapping, $this->transition);
        $this->assertInstanceOf(Transition_PostAction_CIBuild::class, $post_actions[0]);
    }

    public function testItLoadsAllPostActionsFromXML(): void
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

        $this->field_factory->shouldReceive('getInstanceFromXML')->andReturns(\Mockery::spy(\Transition_PostAction_Field_Date::class));

        $this->cibuild_factory->shouldReceive('getInstanceFromXML')->andReturns(\Mockery::spy(\Transition_PostAction_CIBuild::class));

        $post_actions = $this->factory->getInstanceFromXML($xml, $mapping, $this->transition);
        $this->assertInstanceOf(Transition_PostAction_Field_Date::class, $post_actions[0]);
        $this->assertInstanceOf(Transition_PostAction_CIBuild::class, $post_actions[1]);
    }

    public function testItSavesDateFieldPostActions(): void
    {
        $post_action = \Mockery::spy(\Transition_PostAction_Field_Date::class);
        $this->cibuild_factory->shouldReceive('saveObject')->never();
        $this->field_factory->shouldReceive('saveObject')->with($post_action)->once();

        $this->factory->saveObject($post_action);
    }

    public function testItSavesIntFieldPostActions(): void
    {
        $post_action = \Mockery::spy(\Transition_PostAction_Field_Int::class);
        $this->cibuild_factory->shouldReceive('saveObject')->never();
        $this->field_factory->shouldReceive('saveObject')->with($post_action)->once();

        $this->factory->saveObject($post_action);
    }

    public function testItSavesFloatFieldPostActions(): void
    {
        $post_action = \Mockery::spy(\Transition_PostAction_Field_Float::class);
        $this->cibuild_factory->shouldReceive('saveObject')->never();
        $this->field_factory->shouldReceive('saveObject')->with($post_action)->once();

        $this->factory->saveObject($post_action);
    }

    public function testItSavesCIBuildPostActions(): void
    {
        $post_action = \Mockery::spy(\Transition_PostAction_CIBuild::class);
        $this->field_factory->shouldReceive('saveObject')->never();
        $this->cibuild_factory->shouldReceive('saveObject')->with($post_action)->once();

        $this->factory->saveObject($post_action);
    }

    public function testItChecksFieldIsUsedInEachTypeOfPostAction(): void
    {
        $field = \Mockery::spy(\Tracker_FormElement_Field_Selectbox::class);
        $this->cibuild_factory->shouldReceive('isFieldUsedInPostActions')->with($field)->once()->andReturns(false);
        $this->field_factory->shouldReceive('isFieldUsedInPostActions')->with($field)->once()->andReturns(false);

        $this->event_manager->shouldReceive('processEvent')->once();

        $this->assertNull($this->factory->isFieldUsedInPostActions($field));
    }

    public function testItReturnsTrueIfAtLeastOneOfTheSubFactoryReturnsTrue(): void
    {
        $field = \Mockery::spy(\Tracker_FormElement_Field_Selectbox::class);

        $this->field_factory->shouldReceive('isFieldUsedInPostActions')->with($field)->andReturns(true);

        $this->event_manager->shouldReceive('processEvent')->once();

        $this->assertTrue($this->factory->isFieldUsedInPostActions($field));
    }

    public function testItLoadsPostActionFromAllSubFactories(): void
    {
        $this->cibuild_factory->shouldReceive('loadPostActions')->with($this->transition)->andReturns(array($this->post_action_1))->once();
        $this->field_factory->shouldReceive('loadPostActions')->with($this->transition)->andReturns(array($this->post_action_2))->once();
        $this->hidden_fieldset_factory->shouldReceive('loadPostActions')->with($this->transition)->once()->andReturn([$this->post_action_3]);

        $this->event_manager->shouldReceive('processEvent')->once();

        $this->factory->loadPostActions($this->transition);
    }

    public function testItInjectsPostActionsIntoTheTransition(): void
    {
        $this->cibuild_factory->shouldReceive('loadPostActions')->with($this->transition)->andReturns(array($this->post_action_1));
        $this->field_factory->shouldReceive('loadPostActions')->with($this->transition)->andReturns(array($this->post_action_2));
        $this->hidden_fieldset_factory->shouldReceive('loadPostActions')->with($this->transition)->once()->andReturn([$this->post_action_3]);

        $expected     = array($this->post_action_1, $this->post_action_2, $this->post_action_3);
        $this->transition->shouldReceive('setPostActions')->with($expected)->once();

        $this->event_manager->shouldReceive('processEvent')->once();

        $this->factory->loadPostActions($this->transition);
    }
}
