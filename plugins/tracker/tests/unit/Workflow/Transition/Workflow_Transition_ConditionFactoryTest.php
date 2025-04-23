<?php
/**
 * Copyright (c) Enalean, 2012-Present. All Rights Reserved.
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

use PHPUnit\Framework\MockObject\MockObject;
use Tuleap\Project\Duplication\DuplicationUserGroupMapping;
use Tuleap\Test\Builders\ProjectTestBuilder;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class Workflow_Transition_ConditionFactoryTest extends \Tuleap\Test\PHPUnit\TestCase // phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace,Squiz.Classes.ValidClassName.NotCamelCaps
{
    private array $xml_mapping = [];

    private Workflow_Transition_ConditionFactory $condition_factory;

    private Workflow_Transition_Condition_CommentNotEmpty_Factory&MockObject $commentnotempty_factory;

    private Workflow_Transition_Condition_FieldNotEmpty_Factory&MockObject $fieldnotempty_factory;

    private Workflow_Transition_Condition_Permissions_Factory&MockObject $permissions_factory;

    private Project $project;
    private Transition&MockObject $transition;
    private SimpleXMLElement $legacy_permissions_xml;
    private SimpleXMLElement $from_5_7_permissions_xml;

    protected function setUp(): void
    {
        $this->permissions_factory     = $this->createMock(\Workflow_Transition_Condition_Permissions_Factory::class);
        $this->fieldnotempty_factory   = $this->createMock(\Workflow_Transition_Condition_FieldNotEmpty_Factory::class);
        $this->commentnotempty_factory = $this->createMock(\Workflow_Transition_Condition_CommentNotEmpty_Factory::class);

        $this->commentnotempty_factory->method('duplicate');

        $this->condition_factory = new Workflow_Transition_ConditionFactory(
            $this->permissions_factory,
            $this->fieldnotempty_factory,
            $this->commentnotempty_factory
        );

        $this->project = ProjectTestBuilder::aProject()->build();

        PermissionsManager::setInstance($this->createMock(\PermissionsManager::class));

        $this->transition = $this->createMock(\Transition::class);

        $this->legacy_permissions_xml = new SimpleXMLElement('
            <transition>
                <permissions>
                    <permission ugroup="UGROUP_PROJECT_MEMBERS"/>
                    <permission ugroup="UGROUP_PROJECT_ADMIN"/>
                </permissions>
            </transition>
        ');

        $this->from_5_7_permissions_xml = new SimpleXMLElement('
            <transition>
                <conditions>
                    <condition type="perms">
                        <permissions>
                            <permission ugroup="UGROUP_PROJECT_MEMBERS"/>
                            <permission ugroup="UGROUP_PROJECT_ADMIN"/>
                        </permissions>
                    </condition>
                </conditions>
            </transition>
        ');

        $this->permissions_factory->method('getInstanceFromXML')
            ->willReturnCallback(
                fn (SimpleXMLElement $xml) => match (true) {
                    isset($xml->permission),
                    isset($xml->permissions) => $this->createMock(\Workflow_Transition_Condition_Permissions::class),
                }
            );
    }

    protected function tearDown(): void
    {
        PermissionsManager::clearInstance();
    }

    public function testItDelegatesToFieldNotEmptyFactory(): void
    {
        $field = $this->createMock(\Tracker_FormElement_Field_Date::class);
        $this->fieldnotempty_factory->expects($this->once())->method('isFieldUsedInConditions')->with($field)->willReturn(true);
        $this->assertTrue($this->condition_factory->isFieldUsedInConditions($field));
    }

    public function testItReconstitutesLegacyPermissions(): void
    {
        $conditions = $this->condition_factory->getAllInstancesFromXML(
            $this->legacy_permissions_xml,
            $this->xml_mapping,
            $this->transition,
            $this->project
        );

        $this->assertInstanceOf(\Workflow_Transition_Condition_Permissions::class, $conditions[0]);
    }

    public function testItReconstitutesPermissions(): void
    {
        $conditions = $this->condition_factory->getAllInstancesFromXML(
            $this->from_5_7_permissions_xml,
            $this->xml_mapping,
            $this->transition,
            $this->project
        );

        $this->assertInstanceOf(\Workflow_Transition_Condition_Permissions::class, $conditions[0]);
    }

    public function testItReconstituesFieldNotEmpty(): void
    {
        $xml = new SimpleXMLElement('
            <transition>
                <conditions>
                    <condition type="notempty">
                        <field REF="F14"/>
                    </condition>
                </conditions>
            </transition>
        ');

        $condition = $this->createMock(\Workflow_Transition_Condition_FieldNotEmpty::class);
        $this->fieldnotempty_factory->method('getInstanceFromXML')->willReturnCallback(
            static fn (SimpleXMLElement $xml) => match ((string) $xml['type']) {
                'notempty' => $condition
            }
        );

        $conditions = $this->condition_factory->getAllInstancesFromXML(
            $xml,
            $this->xml_mapping,
            $this->transition,
            $this->project
        );

        $this->assertEquals($condition, $conditions[0]);
    }

    public function testItIgnoresNullConditions(): void
    {
        $xml = new SimpleXMLElement('
            <transition>
                <conditions>
                    <condition type="notempty" />
                </conditions>
            </transition>
        ');

        $this->fieldnotempty_factory->method('getInstanceFromXML')->willReturn(null);

        $conditions = $this->condition_factory->getAllInstancesFromXML(
            $xml,
            $this->xml_mapping,
            $this->transition,
            $this->project
        );

        $this->assertEquals(new Workflow_Transition_ConditionsCollection(), $conditions);
    }

    public function testItDelegatesTheDuplicateToSubFactories(): void
    {
        $new_transition_id = 2;
        $field_mapping     = ['some fields mapping'];

        $mapping = DuplicationUserGroupMapping::fromNewProjectWithMapping([103 => 122]);

        $this->permissions_factory->expects($this->once())->method('duplicate')->with($this->transition, $new_transition_id, $mapping);
        $this->fieldnotempty_factory->expects($this->once())->method('duplicate')->with($this->transition, $new_transition_id, $field_mapping);
        $this->condition_factory->duplicate($this->transition, $new_transition_id, $field_mapping, $mapping);
    }
}
