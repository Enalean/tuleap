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

use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use SimpleXMLElement;
use Tracker;
use Tracker_FormElement_Container_Fieldset;
use Tracker_FormElement_Field_Date;
use Tracker_FormElement_Field_Float;
use Tracker_FormElement_Field_String;
use Tracker_FormElement_Field_Text;
use Tracker_FormElementFactory;
use Tuleap\Tracker\XML\TrackerXmlImportFeedbackCollector;
use Tuleap\Tracker\Workflow\PostAction\HiddenFieldsets\HiddenFieldsetsDao;
use User\XML\Import\IFindUserFromXMLReference;

class Tracker_FormElement_Container_FieldsetTest extends TestCase //phpcs:ignore
{
    use MockeryPHPUnitIntegration;

    /**
     * @var Mockery\MockInterface|TrackerXmlImportFeedbackCollector
     */
    private $feedback_collector;

    public function setUp(): void
    {
        $this->feedback_collector = \Mockery::spy(TrackerXmlImportFeedbackCollector::class);
    }

    //testing field import
    public function testImportFormElement()
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

        $mapping = array();

        $a_formelement = Mockery::mock(Tracker_FormElement_Field_Date::class);

        $factory = Mockery::mock(Tracker_FormElementFactory::class);
        $factory->shouldReceive('getInstanceFromXML')->andReturn($a_formelement)->once();
        $factory->shouldReceive('getInstanceFromXML')->andReturn(null)->once();

        $container_fieldset = Mockery::mock(Tracker_FormElement_Container_Fieldset::class)
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();

        $tracker = Mockery::mock(Tracker::class);
        $tracker->shouldReceive('getId')->andReturn(101);

        $container_fieldset->shouldReceive('getFormElementFactory')->andReturn($factory);
        $container_fieldset->setTracker($tracker);

        $container_fieldset->continueGetInstanceFromXML(
            $xml,
            $mapping,
            Mockery::mock(IFindUserFromXMLReference::class),
            $this->feedback_collector
        );

        $container_should_load_child = array($a_formelement);
        $this->assertEquals($container_should_load_child, $container_fieldset->getFormElements());
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

        $form_element_factory = Mockery::mock(Tracker_FormElementFactory::class);
        $form_element_factory->shouldReceive('getInstanceFromXML')->andReturn(null);
        $form_element_factory->shouldReceive('getUsedFormElementsByParentId')->andReturn(null);

        $container_fieldset = Mockery::mock(Tracker_FormElement_Container_Fieldset::class)
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();

        $tracker = Mockery::mock(Tracker::class);
        $tracker->shouldReceive('getId')->andReturn(101);

        $container_fieldset->shouldReceive('getFormElementFactory')->andReturn($form_element_factory);
        $container_fieldset->setTracker($tracker);

        $container_fieldset->continueGetInstanceFromXML(
            $xml,
            $mapping,
            Mockery::mock(IFindUserFromXMLReference::class),
            $this->feedback_collector
        );

        $this->assertNull($container_fieldset->getFormElements());
    }

    public function testAfterSaveObject()
    {
        $a_formelement = Mockery::mock(Tracker_FormElement_Field_Date::class);
        $factory       = Mockery::mock(Tracker_FormElementFactory::class);
        $tracker       = Mockery::mock(Tracker::class);

        $container_fieldset = Mockery::mock(Tracker_FormElement_Container_Fieldset::class)
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();

        $container_fieldset->shouldReceive('getFormElementFactory')->andReturn($factory);
        $container_fieldset->shouldReceive('getFormElements')->andReturn([$a_formelement]);
        $container_fieldset->shouldReceive('getId')->andReturn(66);

        $factory->shouldReceive('saveObject')
            ->with($tracker, $a_formelement, 66, false, false)
            ->once();

        $container_fieldset->afterSaveObject($tracker, false, false);
    }

    public function testIsNotDeletableWithFields()
    {
        $container_fieldset = Mockery::mock(Tracker_FormElement_Container_Fieldset::class)
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();

        $a_formelement = Mockery::mock(Tracker_FormElement_Field_Date::class);

        $container_fieldset->shouldReceive('getFormElements')->andReturn([$a_formelement]);

        $this->assertFalse($container_fieldset->canBeRemovedFromUsage());
    }

    public function testIsDeletableWithoutFields()
    {
        $hidden_dao = Mockery::mock(HiddenFieldsetsDao::class);
        $hidden_dao->shouldReceive('isFieldsetUsedInPostAction')->andReturn(false);

        $container_fieldset = Mockery::mock(Tracker_FormElement_Container_Fieldset::class)
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();

        $container_fieldset->shouldReceive('getFormElements')->andReturn(null);
        $container_fieldset->shouldReceive('getHiddenFieldsetsDao')->andReturn($hidden_dao);

        $this->assertTrue($container_fieldset->canBeRemovedFromUsage());
    }

    public function itCallsExportPermissionsToXMLForEachSubfield()
    {
        $container_fieldset = Mockery::mock(Tracker_FormElement_Container_Fieldset::class)
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();

        $field_01 = Mockery::mock(Tracker_FormElement_Field_String::class);
        $field_02 = Mockery::mock(Tracker_FormElement_Field_Float::class);
        $field_03 = Mockery::mock(Tracker_FormElement_Field_Text::class);

        $container_fieldset->shouldReceive('getAllFormElements')->andReturn([
            $field_01,
            $field_02,
            $field_03
        ]);

        $data    = '<?xml version="1.0" encoding="UTF-8"?>
                    <permissions/>';
        $xml     = new SimpleXMLElement($data);
        $mapping = [];
        $ugroups = [];

        $field_01->shouldReceive('exportPermissionsToXML')->once();
        $field_02->shouldReceive('exportPermissionsToXML')->once();
        $field_03->shouldReceive('exportPermissionsToXML')->once();

        $container_fieldset->exportPermissionsToXML($xml, $ugroups, $mapping);
    }
}
