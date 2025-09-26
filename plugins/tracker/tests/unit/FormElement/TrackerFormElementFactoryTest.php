<?php
/**
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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

use EventManager;
use PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles;
use SimpleXMLElement;
use Tracker_FormElementFactory;
use Tuleap\GlobalResponseMock;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Test\Stubs\User\XML\Import\IFindUserFromXMLReferenceStub;
use Tuleap\Tracker\FormElement\Container\Fieldset\FieldsetContainer;
use Tuleap\Tracker\Test\Builders\Fields\FieldsetContainerBuilder;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;
use Tuleap\Tracker\Tracker;
use Tuleap\Tracker\XML\TrackerXmlImportFeedbackCollector;
use User\XML\Import\IFindUserFromXMLReference;

#[DisableReturnValueGenerationForTestDoubles]
final class TrackerFormElementFactoryTest extends TestCase
{
    use GlobalResponseMock;

    private Tracker_FormElementFactory $form_element_factory;
    private FieldsetContainer $form_element;
    private IFindUserFromXMLReference $user_finder;
    private Tracker $tracker;
    private TrackerXmlImportFeedbackCollector $feedback_collector;

    #[\Override]
    public function setUp(): void
    {
        $this->form_element_factory = Tracker_FormElementFactory::instance();
        $this->form_element         = FieldsetContainerBuilder::aFieldset(0)
            ->withName('field_name')
            ->withLabel('field_label')
            ->withDescription('field_description')
            ->required()
            ->build();
        $this->user_finder          = IFindUserFromXMLReferenceStub::buildWithUser(UserTestBuilder::buildWithDefaults());
        $this->feedback_collector   = new TrackerXmlImportFeedbackCollector();
        $this->tracker              = TrackerTestBuilder::aTracker()->withProject(ProjectTestBuilder::aProject()->build())->build();
        $this->form_element->setTracker($this->tracker);
    }

    #[\Override]
    public function tearDown(): void
    {
        EventManager::clearInstance();
    }

    public function testImportFormElement(): void
    {
        $mapping = [];

        $element_from_instance = $this->form_element_factory->getInstanceFromXML($this->tracker, new SimpleXMLElement(
            '<?xml version="1.0" standalone="yes"?>
            <formElement type="fieldset" ID="F0" rank="20" required="1" notifications="1">
                <name>field_name</name>
                <label>field_label</label>
                <description>field_description</description>
            </formElement>'
        ), $mapping, $this->user_finder, $this->feedback_collector);
        self::assertEquals($this->form_element, $element_from_instance);
        self::assertEquals($this->form_element, $mapping['F0']);
    }

    public function testImportFormElementReturnWarningFeedbackWhenNoFormelementCorresponding(): void
    {
        $mapping = [];

        $GLOBALS['Response']->method('addFeedback')
            ->with('warning', 'Type \'mon_type\' does not exist. This field is ignored. (Name : \'field_name\', ID: \'F0\').');

        self::assertNull($this->form_element_factory->getInstanceFromXML($this->tracker, new SimpleXMLElement(
            '<?xml version="1.0" standalone="yes"?>
            <formElement type="mon_type" ID="F0" rank="20" required="1" notifications="1">
                <name>field_name</name>
                <label>field_label</label>
                <description>field_description</description>
            </formElement>'
        ), $mapping, $this->user_finder, $this->feedback_collector));
        self::assertSame(['Type \'mon_type\' does not exist. This field is ignored. (Name : \'field_name\', ID: \'F0\').'], $this->feedback_collector->getWarnings());
    }

    public function testImportCallExternalElementEventAndReturnNull(): void
    {
        $mapping = [];
        $xml     = new SimpleXMLElement(
            '<?xml version="1.0" standalone="yes"?>
            <externalField type="external" ID="F1602" rank="2">
                 <name>external</name>
                 <label><![CDATA[Steps definition]]></label>
                 <description><![CDATA[Definition of the test\'s steps]]></description>
                <permissions>
                 <permission scope="field" REF="F1602" ugroup="UGROUP_ANONYMOUS" type="PLUGIN_TRACKER_FIELD_READ"/>
                 <permission scope="field" REF="F1602" ugroup="UGROUP_REGISTERED" type="PLUGIN_TRACKER_FIELD_SUBMIT"/>
                 <permission scope="field" REF="F1602" ugroup="UGROUP_PROJECT_MEMBERS" type="PLUGIN_TRACKER_FIELD_UPDATE"/>
                </permissions>
            </externalField>'
        );

        $event_manager = $this->createMock(EventManager::class);
        EventManager::setInstance($event_manager);
        $event_manager->expects($this->once())->method('processEvent');


        $GLOBALS['Response']->method('addFeedback')
            ->with('warning', 'Type \'external\' does not exist. This field is ignored. (Name : \'external\', ID: \'F1602\').');

        $element_from_instance = $this->form_element_factory->getInstanceFromXML(
            $this->tracker,
            $xml,
            $mapping,
            $this->user_finder,
            $this->feedback_collector
        );

        self::assertNull($element_from_instance);
        self::assertSame(['Type \'external\' does not exist. This field is ignored. (Name : \'external\', ID: \'F1602\').'], $this->feedback_collector->getWarnings());
    }
}
