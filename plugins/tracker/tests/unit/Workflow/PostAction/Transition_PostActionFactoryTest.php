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

declare(strict_types=1);

use PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles;
use PHPUnit\Framework\MockObject\MockObject;
use Tuleap\Tracker\FormElement\Field\List\SelectboxField;
use Tuleap\Tracker\Workflow\PostAction\FrozenFields\FrozenFieldsFactory;
use Tuleap\Tracker\Workflow\PostAction\HiddenFieldsets\HiddenFieldsetsFactory;

#[DisableReturnValueGenerationForTestDoubles]
final class Transition_PostActionFactoryTest extends \Tuleap\Test\PHPUnit\TestCase // phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace, Squiz.Classes.ValidClassName.NotPascalCase
{
    private Transition_PostActionFactory $factory;
    private Transition_PostAction_FieldFactory&MockObject $field_factory;
    private Transition_PostAction_CIBuildFactory&MockObject $cibuild_factory;
    private FrozenFieldsFactory&MockObject $frozen_fields_factory;
    private Transition&MockObject $transition;
    private HiddenFieldsetsFactory&MockObject $hidden_fieldset_factory;

    private EventManager&MockObject $event_manager;
    private Transition_PostAction&MockObject $post_action_1;
    private Transition_PostAction&MockObject $post_action_2;
    private Transition_PostAction&MockObject $post_action_3;

    #[\Override]
    protected function setUp(): void
    {
        $transition_id    = 123;
        $this->transition = $this->createMock(Transition::class);
        $this->transition->method('getTransitionId')->willReturn($transition_id);
        $this->transition->method('setPostActions');

        $this->event_manager = $this->createMock(EventManager::class);
        $this->factory       = new Transition_PostActionFactory($this->event_manager);

        $this->field_factory           = $this->createMock(Transition_PostAction_FieldFactory::class);
        $this->cibuild_factory         = $this->createMock(Transition_PostAction_CIBuildFactory::class);
        $this->frozen_fields_factory   = $this->createMock(FrozenFieldsFactory::class);
        $this->hidden_fieldset_factory = $this->createMock(HiddenFieldsetsFactory::class);

        $this->factory->setFieldFactory($this->field_factory);
        $this->factory->setCIBuildFactory($this->cibuild_factory);
        $this->factory->setFrozenFieldsFactory($this->frozen_fields_factory);
        $this->factory->setHiddenFieldsetsFactory($this->hidden_fieldset_factory);

        $this->post_action_1 = $this->createMock(Transition_PostAction::class);
        $this->post_action_2 = $this->createMock(Transition_PostAction::class);
        $this->post_action_3 = $this->createMock(Transition_PostAction::class);
    }

    public function testItDelegatesDuplicationToTheOtherPostActionFactories(): void
    {
        $field_mapping = [
            1 => ['from' => 2066, 'to' => 3066],
            2 => ['from' => 2067, 'to' => 3067],
        ];

        $this->field_factory->expects($this->once())->method('duplicate')->with($this->transition, 2, $field_mapping);
        $this->cibuild_factory->expects($this->once())->method('duplicate')->with($this->transition, 2, $field_mapping);
        $this->frozen_fields_factory->expects($this->once())->method('duplicate')->with($this->transition, 2, $field_mapping);
        $this->hidden_fieldset_factory->method('duplicate');

        $this->event_manager->expects($this->once())->method('processEvent');

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

        $mapping = ['F1' => 62334];

        $this->field_factory->method('getInstanceFromXML')->willReturnCallback(fn (SimpleXMLElement $xml) => match (true) {
            (string) $xml->field_id['REF'] === 'F1' &&
            (string) $xml['valuetype'] === '1' => $this->createMock(Transition_PostAction_Field_Date::class)
        });

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

        $mapping = ['F1' => 62334];

        $this->field_factory->method('getInstanceFromXML')->willReturnCallback(fn (SimpleXMLElement $xml) => match (true) {
            (string) $xml->field_id['REF'] === 'F1' &&
            (string) $xml['valuetype'] === '1' => $this->createMock(Transition_PostAction_Field_Int::class)
        });

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

        $mapping = ['F1' => 62334];

        $this->field_factory->method('getInstanceFromXML')->willReturnCallback(fn (SimpleXMLElement $xml) => match (true) {
            (string) $xml->field_id['REF'] === 'F1' &&
            (string) $xml['valuetype'] === '3.14' => $this->createMock(Transition_PostAction_Field_Float::class)
        });

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

        $mapping = ['F1' => 62334];

        $this->cibuild_factory->method('getInstanceFromXML')->willReturnCallback(fn (SimpleXMLElement $xml) => match (true) {
            (string) $xml['job_url'] === 'http://www' => $this->createMock(Transition_PostAction_CIBuild::class)
        });

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

        $mapping = ['F1' => 62334];

        $this->field_factory->method('getInstanceFromXML')->willReturn($this->createMock(Transition_PostAction_Field_Date::class));

        $this->cibuild_factory->method('getInstanceFromXML')->willReturn($this->createMock(Transition_PostAction_CIBuild::class));

        $post_actions = $this->factory->getInstanceFromXML($xml, $mapping, $this->transition);
        $this->assertInstanceOf(Transition_PostAction_Field_Date::class, $post_actions[0]);
        $this->assertInstanceOf(Transition_PostAction_CIBuild::class, $post_actions[1]);
    }

    public function testItSavesDateFieldPostActions(): void
    {
        $post_action = $this->createMock(Transition_PostAction_Field_Date::class);
        $this->cibuild_factory->expects($this->never())->method('saveObject');
        $this->field_factory->expects($this->once())->method('saveObject')->with($post_action);

        $this->factory->saveObject($post_action);
    }

    public function testItSavesIntFieldPostActions(): void
    {
        $post_action = $this->createMock(Transition_PostAction_Field_Int::class);
        $this->cibuild_factory->expects($this->never())->method('saveObject');
        $this->field_factory->expects($this->once())->method('saveObject')->with($post_action);

        $this->factory->saveObject($post_action);
    }

    public function testItSavesFloatFieldPostActions(): void
    {
        $post_action = $this->createMock(Transition_PostAction_Field_Float::class);
        $this->cibuild_factory->expects($this->never())->method('saveObject');
        $this->field_factory->expects($this->once())->method('saveObject')->with($post_action);

        $this->factory->saveObject($post_action);
    }

    public function testItSavesCIBuildPostActions(): void
    {
        $post_action = $this->createMock(Transition_PostAction_CIBuild::class);
        $this->field_factory->expects($this->never())->method('saveObject');
        $this->cibuild_factory->expects($this->once())->method('saveObject')->with($post_action);

        $this->factory->saveObject($post_action);
    }

    public function testItChecksFieldIsUsedInEachTypeOfPostAction(): void
    {
        $field = $this->createMock(SelectboxField::class);
        $this->cibuild_factory->expects($this->once())->method('isFieldUsedInPostActions')->with($field)->willReturn(false);
        $this->field_factory->expects($this->once())->method('isFieldUsedInPostActions')->with($field)->willReturn(false);
        $this->frozen_fields_factory->method('isFieldUsedInPostActions');
        $this->hidden_fieldset_factory->method('isFieldUsedInPostActions');

        $this->event_manager->expects($this->once())->method('processEvent');

        $this->assertNull($this->factory->isFieldUsedInPostActions($field));
    }

    public function testItReturnsTrueIfAtLeastOneOfTheSubFactoryReturnsTrue(): void
    {
        $field = $this->createMock(SelectboxField::class);

        $this->field_factory->method('isFieldUsedInPostActions')->with($field)->willReturn(true);

        $this->event_manager->expects($this->once())->method('processEvent');

        $this->assertTrue($this->factory->isFieldUsedInPostActions($field));
    }

    public function testItLoadsPostActionFromAllSubFactories(): void
    {
        $this->cibuild_factory->expects($this->once())->method('loadPostActions')->with($this->transition)->willReturn([$this->post_action_1]);
        $this->field_factory->expects($this->once())->method('loadPostActions')->with($this->transition)->willReturn([$this->post_action_2]);
        $this->hidden_fieldset_factory->expects($this->once())->method('loadPostActions')->with($this->transition)->willReturn([$this->post_action_3]);
        $this->frozen_fields_factory->method('loadPostActions');

        $this->event_manager->expects($this->once())->method('processEvent');

        $this->factory->loadPostActions($this->transition);
    }

    public function testItInjectsPostActionsIntoTheTransition(): void
    {
        $this->cibuild_factory->method('loadPostActions')->with($this->transition)->willReturn([$this->post_action_1]);
        $this->field_factory->method('loadPostActions')->with($this->transition)->willReturn([$this->post_action_2]);
        $this->hidden_fieldset_factory->expects($this->once())->method('loadPostActions')->with($this->transition)->willReturn([$this->post_action_3]);
        $this->frozen_fields_factory->method('loadPostActions');

        $expected = [$this->post_action_1, $this->post_action_2, $this->post_action_3];
        $this->transition->expects($this->once())->method('setPostActions')->with($expected);

        $this->event_manager->expects($this->once())->method('processEvent');

        $this->factory->loadPostActions($this->transition);
    }
}
