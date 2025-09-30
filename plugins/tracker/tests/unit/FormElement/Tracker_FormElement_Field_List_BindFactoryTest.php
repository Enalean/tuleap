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

declare(strict_types=1);

namespace Tuleap\Tracker\FormElement;

use ColinODell\PsrTestLogger\TestLogger;
use PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles;
use Project;
use ProjectUGroup;
use SimpleXMLElement;
use Tracker_FormElement_Field_List_Bind;
use Tracker_FormElement_Field_List_Bind_Null;
use Tracker_FormElement_Field_List_BindDecorator;
use Tracker_FormElement_Field_List_BindFactory;
use Tuleap\DB\DatabaseUUIDV7Factory;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\ProjectUGroupTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Test\Stubs\User\XML\Import\IFindUserFromXMLReferenceStub;
use Tuleap\Tracker\Test\Builders\Fields\List\ListStaticValueBuilder;
use Tuleap\Tracker\Test\Builders\Fields\SelectboxFieldBuilder;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;
use UGroupManager;

#[DisableReturnValueGenerationForTestDoubles]
final class Tracker_FormElement_Field_List_BindFactoryTest extends TestCase //phpcs:ignore Squiz.Classes.ValidClassName.NotPascalCase
{
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

        $field  = SelectboxFieldBuilder::aSelectboxField(456)->build();
        $value1 = ListStaticValueBuilder::aStaticValue('Open')->build();
        $value2 = ListStaticValueBuilder::aStaticValue('Closed')->build();
        $value3 = ListStaticValueBuilder::aStaticValue('On going')->build();
        $deco1  = new Tracker_FormElement_Field_List_BindDecorator($field, 'F6-V0', 255, 0, 0, '');
        $deco2  = new Tracker_FormElement_Field_List_BindDecorator($field, 'F6-V1', 0, 255, 0, '');
        $deco3  = new Tracker_FormElement_Field_List_BindDecorator($field, 'F6-V2', 0, 0, 0, 'graffiti-yellow');

        $bind = $this->createPartialMock(Tracker_FormElement_Field_List_BindFactory::class, [
            'getStaticValueInstance', 'getDecoratorInstance', 'getInstanceFromRow',
        ]);
        $bind->method('getStaticValueInstance')
            ->willReturnCallback(static fn($id, $label, $description, $rank, $is_hidden) => match ([$id, $label, $description, $rank, $is_hidden]) {
                ['F6-V0', 'Open', '', 0, 0]     => $value1,
                ['F6-V1', 'Closed', '', 1, 0]   => $value2,
                ['F6-V2', 'On going', '', 2, 0] => $value3,
            });
        $bind->method('getDecoratorInstance')
            ->willReturnCallback(static fn($pfield, $id, $r, $g, $b, $tlp_color_name) => match ([$pfield, $id, $r, $g, $b, $tlp_color_name]) {
                [$field, 'F6-V0', 255, 0, 0, '']              => $deco1,
                [$field, 'F6-V1', 0, 255, 0, '']              => $deco2,
                [$field, 'F6-V2', 0, 0, 0, 'graffiti-yellow'] => $deco3,
            });
        $bind->method('getInstanceFromRow')->willReturn([
            [
                'type'           => 'static',
                'field'          => $field,
                'default_values' => [
                    'F6-V0' => $value1,
                ],
                'decorators'     => [
                    'F6-V0' => $deco1,
                    'F6-V1' => $deco2,
                    'F6-V2' => $deco3,
                ],
                'is_rank_alpha'  => 1,
                'values'         => [
                    'F6-V0' => $value1,
                    'F6-V1' => $value2,
                    'F6-V2' => $value3,
                ],
            ],
        ]);
        $bind->getInstanceFromXML($xml, $field, $mapping, IFindUserFromXMLReferenceStub::buildWithUser(UserTestBuilder::buildWithDefaults()));
        self::assertSame($value1, $mapping['F6-V0']);
        self::assertSame($value2, $mapping['F6-V1']);
        self::assertSame($value3, $mapping['F6-V2']);
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

        $field = SelectboxFieldBuilder::aSelectboxField(456)->build();
        $bind  = $this->createPartialMock(Tracker_FormElement_Field_List_BindFactory::class, ['getInstanceFromRow']);
        $bind->method('getInstanceFromRow')->willReturn([
            [
                'type'           => 'users',
                'field'          => $field,
                'default_values' => null,
                'decorators'     => null,
                'value_function' => 'ugroup1,ugroup2',
            ],
        ]);
        $bind->getInstanceFromXML($xml, $field, $mapping, IFindUserFromXMLReferenceStub::buildWithUser(UserTestBuilder::buildWithDefaults()));
        self::assertEquals([], $mapping);
    }

    public function testItRaisesAnErrorIfUnkownType(): void
    {
        $logger  = new TestLogger();
        $factory = new Tracker_FormElement_Field_List_BindFactory(new DatabaseUUIDV7Factory(), $this->createStub(UGroupManager::class), $logger);

        self::assertInstanceOf(
            Tracker_FormElement_Field_List_Bind_Null::class,
            $factory->getInstanceFromRow(['type' => 'unknown', 'field' => 'a_field_object'])
        );
        self::assertTrue($logger->hasWarningRecords());
    }

    public function testItImportsStaticUgroups(): void
    {
        $ugroup_manager = $this->createMock(UGroupManager::class);
        $project        = ProjectTestBuilder::aProject()->build();

        $xml = new SimpleXMLElement(
            '<?xml version="1.0" standalone="yes"?>
                  <bind type="ugroups">
                      <items>
                          <item ID="F1-V0" label="Integrators" is_hidden="0" />
                          <item ID="F1-V1" label="Customers" is_hidden="0" />
                      </items>
                  </bind>'
        );

        $intregrator = ProjectUGroupTestBuilder::aCustomUserGroup(10)->withName('Integrators')->build();
        $customer    = ProjectUGroupTestBuilder::aCustomUserGroup(20)->withName('Customers')->build();
        $ugroup_manager->expects($this->exactly(2))->method('getUGroupByName')->with($project, self::anything())
            ->willReturnCallback(static fn(Project $project, string $name) => match ($name) {
                'Integrators' => $intregrator,
                'Customers'   => $customer,
            });

        $bind_factory = $this->getMockBuilder(Tracker_FormElement_Field_List_BindFactory::class)
            ->setConstructorArgs([new DatabaseUUIDV7Factory()])
            ->onlyMethods(['getUgroupManager'])
            ->getMock();
        $bind_factory->method('getUgroupManager')->willReturn($ugroup_manager);

        $mapping = [];

        $tracker = TrackerTestBuilder::aTracker()->withProject($project)->build();
        $field   = SelectboxFieldBuilder::aSelectboxField(456)->inTracker($tracker)->build();

        $bind = $bind_factory->getInstanceFromXML(
            $xml,
            $field,
            $mapping,
            IFindUserFromXMLReferenceStub::buildWithUser(UserTestBuilder::buildWithDefaults())
        );

        $values = $bind->getAllValues();
        self::assertEquals('Integrators', $values['F1-V0']->getLabel());
        self::assertEquals('Customers', $values['F1-V1']->getLabel());
    }

    public function testItImportsIgnoresStaticUgroupThatDoesntBelongToProject(): void
    {
        $ugroup_manager = $this->createMock(UGroupManager::class);
        $project        = ProjectTestBuilder::aProject()->build();

        $xml = new SimpleXMLElement(
            '<?xml version="1.0" standalone="yes"?>
            <bind type="ugroups">
                <items>
                    <item ID="F1-V0" label="NotInProject" is_hidden="0" />
                </items>
            </bind>'
        );

        $ugroup_manager->method('getUGroupByName')->willReturn(null);

        $bind = $this->getListBindFactory($ugroup_manager, $project, $xml);

        self::assertCount(0, $bind->getAllValues());
    }

    public function testItImportsDynamicUgroups(): void
    {
        $ugroup_manager = $this->createMock(UGroupManager::class);
        $project        = ProjectTestBuilder::aProject()->build();
        $xml            = new SimpleXMLElement(
            '<?xml version="1.0" standalone="yes"?>
            <bind type="ugroups">
                <items>
                    <item ID="F1-V0" label="ugroup_registered_users_name_key" is_hidden="0" />
                </items>
            </bind>'
        );

        $ugroup_manager->method('getUGroupByName')->with($project, 'ugroup_registered_users_name_key')
            ->willReturn(new ProjectUGroup(['name' => 'ugroup_registered_users_name_key']));

        $bind = $this->getListBindFactory($ugroup_manager, $project, $xml);

        $values = $bind->getAllValues();
        self::assertEquals('Registered users', $values['F1-V0']->getLabel());
    }

    public function testItImportsHiddenValues(): void
    {
        $ugroup_manager = $this->createMock(UGroupManager::class);
        $project        = ProjectTestBuilder::aProject()->build();
        $xml            = new SimpleXMLElement('<?xml version="1.0" standalone="yes"?>
            <bind type="ugroups">
                <items>
                    <item ID="F1-V0" label="ugroup_registered_users_name_key" is_hidden="1" />
                </items>
            </bind>');

        $ugroup_manager->method('getUGroupByName')->with($project, 'ugroup_registered_users_name_key')
            ->willReturn(new ProjectUGroup(['name' => 'ugroup_registered_users_name_key']));

        $bind = $this->getListBindFactory($ugroup_manager, $project, $xml);

        $values = $bind->getAllValues();
        self::assertTrue((bool) $values['F1-V0']->isHidden());
    }

    protected function getListBindFactory(UGroupManager $ugroup_manager, Project $project, SimpleXMLElement $xml): Tracker_FormElement_Field_List_Bind
    {
        $bind_factory = new Tracker_FormElement_Field_List_BindFactory(new DatabaseUUIDV7Factory(), $ugroup_manager);

        $tracker = TrackerTestBuilder::aTracker()->withProject($project)->build();
        $field   = SelectboxFieldBuilder::aSelectboxField(456)->inTracker($tracker)->build();
        $mapping = [];

        return $bind_factory->getInstanceFromXML(
            $xml,
            $field,
            $mapping,
            IFindUserFromXMLReferenceStub::buildWithUser(UserTestBuilder::buildWithDefaults())
        );
    }
}
