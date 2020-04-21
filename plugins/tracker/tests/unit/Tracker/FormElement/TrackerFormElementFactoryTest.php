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
use Mockery;
use PHPUnit\Framework\TestCase;
use Project;
use SimpleXMLElement;
use Tracker;
use Tracker_FormElement_Container_Fieldset;
use Tracker_FormElementFactory;
use Tuleap\GlobalLanguageMock;
use Tuleap\GlobalResponseMock;
use Tuleap\Tracker\XML\TrackerXmlImportFeedbackCollector;
use User\XML\Import\IFindUserFromXMLReference;

class TrackerFormElementFactoryTest extends TestCase
{
    use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
    use GlobalLanguageMock;
    use GlobalResponseMock;

    /**
     * @var Mockery\MockInterface|Tracker_FormElementFactory
     */
    private $form_element_factory;

    /**
     * @var Mockery\MockInterface|Tracker_FormElement_Container_Fieldset
     */
    private $form_element;

    /**
     * @var Mockery\MockInterface|IFindUserFromXMLReference
     */
    private $user_finder;

    /**
     * @var array
     */
    private $row;
    /**
     * @var SimpleXMLElement
     */
    private $xml_element;

    /**
     * @var Mockery\MockInterface|Tracker
     */
    private $tracker;

    /**
     * @var Mockery\MockInterface|TrackerXmlImportFeedbackCollector
     */
    private $feedback_collector;

    public function setUp(): void
    {
        $this->form_element_factory = \Mockery::mock(Tracker_FormElementFactory::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $this->form_element         = \Mockery::mock(Tracker_FormElement_Container_Fieldset::class);
        $this->user_finder          = \Mockery::mock(IFindUserFromXMLReference::class);
        $this->xml_element          = $this->getXmlElement();
        $this->feedback_collector   = \Mockery::mock(TrackerXmlImportFeedbackCollector::class);
        $this->tracker              = \Mockery::mock(Tracker::class);
        $this->row                  = [
            'formElement_type' => 'mon_type',
            'name' => 'field_name',
            'label' => 'field_label',
            'rank' => 20,
            'use_it' => 1,
            'scope' => 'P',
            'required' => 1,
            'notifications' => 1,
            'description' => 'field_description',
            'id' => 0,
            'tracker_id' => 0,
            'parent_id' => 0,
            'original_field_id' => null,
        ];
    }

    public function tearDown(): void
    {
        EventManager::clearInstance();

        parent::tearDown();
    }

    public function testImportFormElement(): void
    {
        $mapping = [];

        $this->form_element->shouldReceive('continueGetInstanceFromXML')->withArgs([
            $this->xml_element,
            Mockery::any(),
            $this->user_finder,
            $this->feedback_collector])->once();

        $this->form_element_factory->shouldReceive('getInstanceFromRow')->withArgs([$this->row])->andReturns($this->form_element);

        $this->form_element->shouldReceive('setTracker')->withArgs([$this->tracker])->once();

        $element_from_instance = $this->form_element_factory->getInstanceFromXML($this->tracker, $this->xml_element, $mapping, $this->user_finder, $this->feedback_collector);
        $this->assertSame($element_from_instance, $this->form_element);
        $this->assertSame($mapping['F0'], $this->form_element);
    }

    public function testImportFormElementReturnWarningFeedbackWhenNoFormelementCorresponding(): void
    {
        $mapping = [];

        $this->form_element->shouldNotReceive('continueGetInstanceFromXML');

        $this->form_element_factory->shouldReceive('getInstanceFromRow')->withArgs([$this->row])->andReturns([]);

        $this->form_element->shouldNotReceive('setTracker');

        $this->feedback_collector
            ->shouldReceive('addWarnings')
            ->withArgs(['Type \'mon_type\' does not exist. This field is ignored. (Name : \'field_name\', ID: \'F0\').']);

        $GLOBALS['Response']
            ->shouldReceive('addFeedback')
            ->withArgs(['warning', 'Type \'mon_type\' does not exist. This field is ignored. (Name : \'field_name\', ID: \'F0\').']);

        $this->assertNull($this->form_element_factory->getInstanceFromXML($this->tracker, $this->xml_element, $mapping, $this->user_finder, $this->feedback_collector));
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
        $this->tracker->shouldReceive('getProject')->andReturn(Mockery::mock(Project::class));

        $event_manager = Mockery::mock(EventManager::class);
        EventManager::setInstance($event_manager);

        $event_manager->shouldReceive('processEvent')->once();


        $this->feedback_collector
            ->shouldReceive('addWarnings')
            ->withArgs(['Type \'external\' does not exist. This field is ignored. (Name : \'external\', ID: \'F1602\').']);

        $GLOBALS['Response']
            ->shouldReceive('addFeedback')
            ->withArgs(['warning', 'Type \'external\' does not exist. This field is ignored. (Name : \'external\', ID: \'F1602\').']);

        $element_from_instance = $this->form_element_factory->getInstanceFromXML(
            $this->tracker,
            $xml,
            $mapping,
            $this->user_finder,
            $this->feedback_collector
        );

        $this->assertNull($element_from_instance);
    }

    private function getXmlElement(): SimpleXMLElement
    {
        return new SimpleXMLElement(
            '<?xml version="1.0" standalone="yes"?>
            <formElement type="mon_type" ID="F0" rank="20" required="1" notifications="1">
                <name>field_name</name>
                <label>field_label</label>
                <description>field_description</description>
            </formElement>'
        );
    }
}
