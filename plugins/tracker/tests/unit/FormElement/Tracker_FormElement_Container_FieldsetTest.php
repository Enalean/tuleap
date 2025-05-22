<?php
/**
 * Copyright (c) Enalean SAS. 2011 - Present. All rights reserved
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

use PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles;
use SimpleXMLElement;
use Tracker_FormElement_Container_Fieldset;
use Tracker_FormElement_Field_Float;
use Tracker_FormElement_Field_String;
use Tracker_FormElement_Field_Text;
use Tracker_FormElementFactory;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Test\Stubs\User\XML\Import\IFindUserFromXMLReferenceStub;
use Tuleap\Tracker\Test\Builders\Fields\DateFieldBuilder;
use Tuleap\Tracker\Test\Builders\Fields\FieldsetContainerBuilder;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;
use Tuleap\Tracker\Workflow\PostAction\HiddenFieldsets\HiddenFieldsetsDao;
use Tuleap\Tracker\XML\TrackerXmlImportFeedbackCollector;
use User\XML\Import\IFindUserFromXMLReference;

#[DisableReturnValueGenerationForTestDoubles]
final class Tracker_FormElement_Container_FieldsetTest extends TestCase // phpcs:ignore Squiz.Classes.ValidClassName.NotCamelCaps
{
    //testing field import
    public function testImportFormElement(): void
    {
        $xml = new SimpleXMLElement(
            '<?xml version="1.0" standalone="yes"?>
            <formElement type="mon_type" ID="F0" rank="20" required="1">
                <name>field_name</name>
                <label>field_label</label>
                <description>field_description</description>
                <formElements>
                    <formElement type="date" ID="F1" rank="1" required="1">
                        <name>date</name>
                        <label>date</label>
                        <description>date</description>
                    </formElement>
                    <externalField type="ttmstepdef" ID="F1602" rank="2">
                        <name>steps</name>
                        <label><![CDATA[Steps definition]]></label>
                        <description><![CDATA[Definition of the test\'s steps]]></description>
                    <permissions>
                        <permission scope="field" REF="F1602" ugroup="UGROUP_ANONYMOUS" type="PLUGIN_TRACKER_FIELD_READ"/>
                        <permission scope="field" REF="F1602" ugroup="UGROUP_REGISTERED" type="PLUGIN_TRACKER_FIELD_SUBMIT"/>
                        <permission scope="field" REF="F1602" ugroup="UGROUP_PROJECT_MEMBERS" type="PLUGIN_TRACKER_FIELD_UPDATE"/>
                    </permissions>
                    </externalField>
                </formElements>
            </formElement>'
        );

        $mapping = [];

        $a_formelement = DateFieldBuilder::aDateField(650)->build();

        $factory         = $this->createMock(Tracker_FormElementFactory::class);
        $invocation_rule = $this->exactly(2);
        $factory->expects($invocation_rule)->method('getInstanceFromXML')->willReturnCallback(static fn() => match ($invocation_rule->numberOfInvocations()) {
            1 => $a_formelement,
            2 => null,
        });

        $tracker            = TrackerTestBuilder::aTracker()->withId(101)->build();
        $container_fieldset = FieldsetContainerBuilder::aFieldset(651)->inTracker($tracker)->build();
        $container_fieldset->setFormElementFactory($factory);

        $container_fieldset->continueGetInstanceFromXML(
            $xml,
            $mapping,
            IFindUserFromXMLReferenceStub::buildWithUser(UserTestBuilder::buildWithDefaults()),
            new TrackerXmlImportFeedbackCollector(),
        );

        $container_should_load_child = [$a_formelement];
        self::assertEquals($container_should_load_child, $container_fieldset->getFormElements());
    }

    public function testImportFormElementReturnNullWhenNoInstance(): void
    {
        $xml = new SimpleXMLElement(
            '<?xml version="1.0" standalone="yes"?>
            <formElement type="mon_type" ID="F0" rank="20" required="1">
                <name>field_name</name>
                <label>field_label</label>
                <description>field_description</description>
                <formElements>
                    <formElement type="date" ID="F1" rank="1" required="1">
                        <name>date</name>
                        <label>date</label>
                        <description>date</description>
                    </formElement>
                </formElements>
            </formElement>'
        );

        $mapping = [];

        $form_element_factory = $this->createMock(Tracker_FormElementFactory::class);
        $form_element_factory->method('getInstanceFromXML')->willReturn(null);
        $form_element_factory->method('getUsedFormElementsByParentId')->willReturn(null);

        $tracker            = TrackerTestBuilder::aTracker()->withId(101)->build();
        $container_fieldset = FieldsetContainerBuilder::aFieldset(650)->inTracker($tracker)->build();
        $container_fieldset->setFormElementFactory($form_element_factory);

        $container_fieldset->continueGetInstanceFromXML(
            $xml,
            $mapping,
            $this->createStub(IFindUserFromXMLReference::class),
            new TrackerXmlImportFeedbackCollector(),
        );

        self::assertNull($container_fieldset->getFormElements());
    }

    public function testAfterSaveObject(): void
    {
        $a_formelement      = DateFieldBuilder::aDateField(650)->build();
        $factory            = $this->createMock(Tracker_FormElementFactory::class);
        $tracker            = TrackerTestBuilder::aTracker()->build();
        $container_fieldset = FieldsetContainerBuilder::aFieldset(66)->containsFormElements($a_formelement)->build();
        $container_fieldset->setFormElementFactory($factory);

        $factory->expects($this->once())->method('saveObject')->with($tracker, $a_formelement, 66, false, false);

        $container_fieldset->afterSaveObject($tracker, false, false);
    }

    public function testIsNotDeletableWithFields(): void
    {
        $a_formelement      = DateFieldBuilder::aDateField(650)->build();
        $container_fieldset = FieldsetContainerBuilder::aFieldset(651)->containsFormElements($a_formelement)->build();

        self::assertFalse($container_fieldset->canBeRemovedFromUsage());
    }

    public function testIsDeletableWithoutFields(): void
    {
        $hidden_dao = $this->createMock(HiddenFieldsetsDao::class);
        $hidden_dao->method('isFieldsetUsedInPostAction')->willReturn(false);

        $container_fieldset = $this->createPartialMock(Tracker_FormElement_Container_Fieldset::class, ['getHiddenFieldsetsDao', 'getFormElements']);

        $container_fieldset->method('getFormElements')->willReturn(null);
        $container_fieldset->method('getHiddenFieldsetsDao')->willReturn($hidden_dao);

        self::assertTrue($container_fieldset->canBeRemovedFromUsage());
    }

    public function testItCallsExportPermissionsToXMLForEachSubfield(): void
    {
        $container_fieldset = $this->createPartialMock(Tracker_FormElement_Container_Fieldset::class, ['getAllFormElements']);

        $field_01 = $this->createMock(Tracker_FormElement_Field_String::class);
        $field_02 = $this->createMock(Tracker_FormElement_Field_Float::class);
        $field_03 = $this->createMock(Tracker_FormElement_Field_Text::class);

        $container_fieldset->method('getAllFormElements')->willReturn([$field_01, $field_02, $field_03]);

        $data    = '<?xml version="1.0" encoding="UTF-8"?>
                    <permissions/>';
        $xml     = new SimpleXMLElement($data);
        $mapping = [];
        $ugroups = [];

        $field_01->expects($this->once())->method('exportPermissionsToXML');
        $field_02->expects($this->once())->method('exportPermissionsToXML');
        $field_03->expects($this->once())->method('exportPermissionsToXML');

        $container_fieldset->exportPermissionsToXML($xml, $ugroups, $mapping);
    }
}
