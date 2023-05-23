<?php
/**
 * Copyright (c) Enalean, 2012 - Present. All Rights Reserved.
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
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

use User\XML\Import\IFindUserFromXMLReference;

final class Tracker_FormElement_Field_List_BindFactoryTest extends \Tuleap\Test\PHPUnit\TestCase //phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace, Squiz.Classes.ValidClassName.NotCamelCaps
{
    use \Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    public function testImportStatik(): void
    {
        $xml = new SimpleXMLElement(
            '<?xml version="1.0" standalone="yes"?>
            <bind type="static" is_rank_alpha="1">
                <items>
                    <item ID="F6-V0" label="Open"/>
                    <item ID="F6-V1" label="Closed"/>
                    <item ID="F6-V2" label="On going"/>
                </items>
                <decorators>
                    <decorator REF="F6-V0" r="255" g="0" b="0"/>
                    <decorator REF="F6-V1" r="0" g="255" b="0"/>
                    <decorator REF="F6-V2" r="0" g="0" b="0" tlp_color_name="graffiti-yellow"/>
                </decorators>
                <default_values>
                    <value REF="F6-V0" />
                </default_values>
            </bind>'
        );

        $mapping = [];

        $v1 = Mockery::mock(Tracker_FormElement_Field_List_BindValue::class);
        $v2 = Mockery::mock(Tracker_FormElement_Field_List_BindValue::class);
        $v3 = Mockery::mock(Tracker_FormElement_Field_List_BindValue::class);
        $d1 = Mockery::mock(Tracker_FormElement_Field_List_BindDecorator::class);
        $d2 = Mockery::mock(Tracker_FormElement_Field_List_BindDecorator::class);
        $d3 = Mockery::mock(Tracker_FormElement_Field_List_BindDecorator::class);

        $field = Mockery::mock(Tracker_FormElement_Field_List::class);
        $bind  = Mockery::mock(Tracker_FormElement_Field_List_BindFactory::class)
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();
        $bind->shouldReceive('getStaticValueInstance')->withArgs(['F6-V0', 'Open', '', 0, 0])->andReturn($v1);
        $bind->shouldReceive('getStaticValueInstance')->withArgs(['F6-V1', 'Closed', '', 1, 0])->andReturn($v2);
        $bind->shouldReceive('getStaticValueInstance')->withArgs(['F6-V2', 'On going', '', 2, 0])->andReturn($v3);

        $bind->shouldReceive('getDecoratorInstance')->withArgs([$field, 'F6-V0', 255, 0, 0, ''])->andReturn($d1);
        $bind->shouldReceive('getDecoratorInstance')->withArgs([$field, 'F6-V1', 0, 255, 0, ''])->andReturn($d2);
        $bind->shouldReceive('getDecoratorInstance')->withArgs(
            [$field, 'F6-V2', 0, 0, 0, 'graffiti-yellow']
        )->andReturn($d3);
        $bind->shouldReceive('getInstanceFromRow')->andReturn(
            [
                [
                    'type'           => 'static',
                    'field'          => $field,
                    'default_values' => [
                        'F6-V0' => $v1,
                    ],
                    'decorators'     => [
                        'F6-V0' => $d1,
                        'F6-V1' => $d2,
                        'F6-V2' => $d3,
                    ],
                    'is_rank_alpha'  => 1,
                    'values'         => [
                        'F6-V0' => $v1,
                        'F6-V1' => $v2,
                        'F6-V2' => $v3,
                    ],
                ],
            ]
        );
        $bind->getInstanceFromXML($xml, $field, $mapping, Mockery::mock(IFindUserFromXMLReference::class));
        $this->assertSame($v1, $mapping['F6-V0']);
        $this->assertSame($v2, $mapping['F6-V1']);
        $this->assertSame($v3, $mapping['F6-V2']);
    }

    public function testImportUsers(): void
    {
        $xml = new SimpleXMLElement(
            '<?xml version="1.0" standalone="yes"?>
           <bind type="users">
               <items>
                   <item label="ugroup1"/>
                   <item label="ugroup2"/>
               </items>
           </bind>'
        );

        $mapping = [];

        $field = Mockery::mock(Tracker_FormElement_Field_List::class);
        $bind  = Mockery::mock(Tracker_FormElement_Field_List_BindFactory::class)
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();
        $bind->shouldReceive('getInstanceFromRow')->andReturn(
            [
                [
                    'type'           => 'users',
                    'field'          => $field,
                    'default_values' => null,
                    'decorators'     => null,
                    'value_function' => 'ugroup1,ugroup2',
                ],
            ]
        );
        $bind->getInstanceFromXML($xml, $field, $mapping, Mockery::mock(IFindUserFromXMLReference::class));
        $this->assertEquals([], $mapping);
    }

    public function testImportUnknownType(): void
    {
        $mapping = [];

        $field = Mockery::mock(Tracker_FormElement_Field_List::class);
        $bind  = Mockery::mock(Tracker_FormElement_Field_List_BindFactory::class)
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();
        $bind->shouldReceive('getInstanceFromRow')->andReturn(
            [
                [
                    'type'           => 'unknown',
                    'field'          => $field,
                    'default_values' => [],
                    'decorators'     => null,
                ],
            ]
        );
        $this->assertEquals([], $mapping);
    }

    public function testItRaisesAnErrorIfUnkownType(): void
    {
        $logger  = new \ColinODell\PsrTestLogger\TestLogger();
        $factory = new Tracker_FormElement_Field_List_BindFactory($this->createStub(UGroupManager::class), $logger);

        self::assertInstanceOf(
            Tracker_FormElement_Field_List_Bind_Null::class,
            $factory->getInstanceFromRow(['type' => 'unknown', 'field' => 'a_field_object'])
        );
        self::assertTrue($logger->hasWarningRecords());
    }

    public function testItImportsStaticUgroups(): void
    {
        $ugroup_manager = Mockery::mock(UGroupManager::class);
        $project        = Mockery::mock(Project::class);

        $xml = new SimpleXMLElement(
            '<?xml version="1.0" standalone="yes"?>
                  <bind type="ugroups">
                      <items>
                          <item ID="F1-V0" label="Integrators" is_hidden="0" />
                          <item ID="F1-V1" label="Customers" is_hidden="0" />
                      </items>
                  </bind>'
        );

        $ugroup_manager->shouldReceive('getUGroupByName')
            ->withArgs([$project, 'Integrators'])
            ->andReturn(new ProjectUGroup(['name' => 'Integrators']));
        $ugroup_manager->shouldReceive('getUGroupByName')
            ->withArgs([$project, 'Customers'])
            ->andReturn(new ProjectUGroup(['name' => 'Customers']));

        $bind_factory = Mockery::mock(Tracker_FormElement_Field_List_BindFactory::class)
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();

        $ugroup_manager = Mockery::mock(UGroupManager::class);
        $intregrator    = Mockery::mock(ProjectUGroup::class);
        $intregrator->shouldReceive('getId')->andReturn(10);
        $intregrator->shouldReceive('getTranslatedName')->andReturn('Integrators');
        $ugroup_manager->shouldReceive('getUGroupByName')
            ->withArgs([Mockery::any(), 'Integrators'])
            ->andReturn($intregrator)
            ->once();
        $customer = Mockery::mock(ProjectUGroup::class);
        $customer->shouldReceive('getId')->andReturn(20);
        $customer->shouldReceive('getTranslatedName')->andReturn('Customers');
        $ugroup_manager->shouldReceive('getUGroupByName')
            ->withArgs([Mockery::any(), 'Customers'])
            ->andReturn($customer)
            ->once();
        $bind_factory->shouldReceive('getUgroupManager')->andReturn($ugroup_manager);

        $mapping = [];

        $field   = Mockery::mock(Tracker_FormElement_Field_List::class);
        $tracker = Mockery::mock(Tracker::class);
        $field->shouldReceive('getTracker')->andReturn($tracker);
        $tracker->shouldReceive('getProject')->andReturn(Mockery::mock(Project::class));

        $bind = $bind_factory->getInstanceFromXML(
            $xml,
            $field,
            $mapping,
            Mockery::mock(IFindUserFromXMLReference::class)
        );

        $values = $bind->getAllValues();
        $this->assertEquals($values["F1-V0"]->getLabel(), 'Integrators');
        $this->assertEquals($values["F1-V1"]->getLabel(), 'Customers');
    }

    public function testItImportsIgnoresStaticUgroupThatDoesntBelongToProject(): void
    {
        $ugroup_manager = Mockery::mock(UGroupManager::class);
        $project        = Mockery::mock(Project::class);

        $xml = new SimpleXMLElement(
            '<?xml version="1.0" standalone="yes"?>
            <bind type="ugroups">
                <items>
                    <item ID="F1-V0" label="NotInProject" is_hidden="0" />
                </items>
            </bind>'
        );

        $ugroup_manager->shouldReceive('getUGroupByName')->andReturn(null);

        $bind = $this->getListBindFactory($ugroup_manager, $project, $xml);

        $this->assertCount(0, $bind->getAllValues());
    }

    public function testItImportsDynamicUgroups(): void
    {
        $ugroup_manager = Mockery::mock(UGroupManager::class);
        $project        = Mockery::mock(Project::class);
        $xml            = new SimpleXMLElement(
            '<?xml version="1.0" standalone="yes"?>
            <bind type="ugroups">
                <items>
                    <item ID="F1-V0" label="ugroup_registered_users_name_key" is_hidden="0" />
                </items>
            </bind>'
        );

        $ugroup_manager->shouldReceive('getUGroupByName')->withArgs(
            [$project, 'ugroup_registered_users_name_key']
        )->andReturn(new ProjectUGroup(['name' => 'ugroup_registered_users_name_key']));

        $bind = $this->getListBindFactory($ugroup_manager, $project, $xml);

        $values = $bind->getAllValues();
        $this->assertEquals('Registered users', $values["F1-V0"]->getLabel());
    }

    public function testItImportsHiddenValues(): void
    {
        $ugroup_manager = Mockery::mock(UGroupManager::class);
        $project        = Mockery::mock(Project::class);
        $xml            = new SimpleXMLElement('<?xml version="1.0" standalone="yes"?>
            <bind type="ugroups">
                <items>
                    <item ID="F1-V0" label="ugroup_registered_users_name_key" is_hidden="1" />
                </items>
            </bind>');

        $ugroup_manager->shouldReceive('getUGroupByName')
            ->withArgs([$project, 'ugroup_registered_users_name_key'])
            ->andReturn(new ProjectUGroup(['name' => 'ugroup_registered_users_name_key']));

        $bind = $this->getListBindFactory($ugroup_manager, $project, $xml);

        $values = $bind->getAllValues();
        $this->assertTrue((bool) $values["F1-V0"]->isHidden());
    }

    protected function getListBindFactory($ugroup_manager, $project, SimpleXMLElement $xml): Tracker_FormElement_Field_List_Bind
    {
        $bind_factory = new Tracker_FormElement_Field_List_BindFactory($ugroup_manager);

        $tracker = Mockery::mock(Tracker::class);
        $tracker->shouldReceive('getProject')->andReturn($project);
        $field = Mockery::mock(Tracker_FormElement_Field_List::class);
        $field->shouldReceive('getTracker')->andReturn($tracker);
        $mapping = [];

        return $bind_factory->getInstanceFromXML(
            $xml,
            $field,
            $mapping,
            Mockery::mock(IFindUserFromXMLReference::class)
        );
    }
}
