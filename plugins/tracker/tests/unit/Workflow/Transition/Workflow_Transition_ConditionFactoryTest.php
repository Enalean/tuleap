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

// phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace,Squiz.Classes.ValidClassName.NotCamelCaps
final class Workflow_Transition_ConditionFactoryTest extends \PHPUnit\Framework\TestCase
{
    use \Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    private $xml_mapping = [];

    /** @var Workflow_Transition_ConditionFactory */
    private $condition_factory;

    /** @var Workflow_Transition_Condition_CommentNotEmpty_Factory */
    private $commentnotempty_factory;

    /** @var Workflow_Transition_Condition_FieldNotEmpty_Factory */
    private $fieldnotempty_factory;

    /** @var Workflow_Transition_Condition_Permissions_Factory */
    private $permissions_factory;

    private $project;
    private $transition;
    private $legacy_permissions_xml;
    private $from_5_7_permissions_xml;

    protected function setUp(): void
    {
        $this->permissions_factory     = \Mockery::spy(\Workflow_Transition_Condition_Permissions_Factory::class);
        $this->fieldnotempty_factory   = \Mockery::spy(\Workflow_Transition_Condition_FieldNotEmpty_Factory::class);
        $this->commentnotempty_factory = \Mockery::spy(\Workflow_Transition_Condition_CommentNotEmpty_Factory::class);

        $this->condition_factory = new Workflow_Transition_ConditionFactory(
            $this->permissions_factory,
            $this->fieldnotempty_factory,
            $this->commentnotempty_factory
        );

        $this->project = \Mockery::spy(\Project::class);

        PermissionsManager::setInstance(\Mockery::spy(\PermissionsManager::class));

        $this->transition = \Mockery::spy(\Transition::class);

        $this->legacy_permissions_xml = new SimpleXMLElement('
            <transition>
                <permissions>
                    <permission ugroup="UGROUP_PROJECT_MEMBERS"/>
                    <permission ugroup="UGROUP_PROJECT_ADMIN"/>
                </permissions>
            </transition>
        ');
        $this->permissions_factory->shouldReceive('getInstanceFromXML')->with(
            Mockery::on(static function (SimpleXMLElement $xml): bool {
                return isset($xml->permission);
            }),
            $this->xml_mapping,
            $this->transition,
            $this->project
        )->andReturns(\Mockery::spy(\Workflow_Transition_Condition_Permissions::class));

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
        $this->permissions_factory->shouldReceive('getInstanceFromXML')->with(
            Mockery::on(static function (SimpleXMLElement $xml): bool {
                return isset($xml->permissions);
            }),
            $this->xml_mapping,
            $this->transition,
            $this->project
        )->andReturns(\Mockery::spy(\Workflow_Transition_Condition_Permissions::class));
    }

    protected function tearDown(): void
    {
        PermissionsManager::clearInstance();
    }

    public function testItDelegatesToFieldNotEmptyFactory(): void
    {
        $field = \Mockery::spy(\Tracker_FormElement_Field_Date::class);
        $this->fieldnotempty_factory->shouldReceive('isFieldUsedInConditions')->with($field)->once()->andReturns(true);
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

        $condition = \Mockery::spy(\Workflow_Transition_Condition_FieldNotEmpty::class);
        $this->fieldnotempty_factory->shouldReceive('getInstanceFromXML')->with(
            Mockery::on(
                static function (SimpleXMLElement $xml): bool {
                    return (string) $xml['type'] === 'notempty';
                }
            ),
            \Mockery::any(),
            \Mockery::any()
        )->andReturns($condition);

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

        $this->fieldnotempty_factory->shouldReceive('getInstanceFromXML')->andReturns(null);

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
        $field_mapping     = array('some fields mapping');
        $ugroup_mapping    = array('some ugroups mapping');
        $duplicate_type    = PermissionsDao::DUPLICATE_NEW_PROJECT;

        $this->permissions_factory->shouldReceive('duplicate')->with($this->transition, $new_transition_id, $field_mapping, $ugroup_mapping, $duplicate_type)->once();
        $this->fieldnotempty_factory->shouldReceive('duplicate')->with($this->transition, $new_transition_id, $field_mapping, $ugroup_mapping, $duplicate_type)->once();
        $this->condition_factory->duplicate($this->transition, $new_transition_id, $field_mapping, $ugroup_mapping, $duplicate_type);
    }
}
