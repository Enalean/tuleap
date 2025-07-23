<?php
/**
 * Copyright (c) Enalean, 2025 - Present. All Rights Reserved.
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

namespace Tuleap\Tracker\Tracker\XML\Importer;

use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\NullLogger;
use SimpleXMLElement;
use Tracker_FormElementFactory;
use TrackerFactory;
use TrackerFromXmlImportCannotBeCreatedException;
use Tuleap\Project\XML\Import\ImportConfig;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;
use Tuleap\Tracker\TrackerIsInvalidException;
use Tuleap\Tracker\TrackerXMLFieldMappingFromExistingTracker;
use Tuleap\Tracker\XML\Importer\TrackerExtraConfiguration;
use Tuleap\Tracker\XML\TrackerXmlImportFeedbackCollector;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class TrackerFromXmlInstantiatorTest extends TestCase
{
    private TrackerFactory&MockObject $tracker_factory;
    private Tracker_FormElementFactory&MockObject $tracker_form_element_factory;
    private TrackerXMLFieldMappingFromExistingTracker&MockObject $mapping_from_existing_tracker;

    #[\Override]
    protected function setUp(): void
    {
        $this->tracker_factory               = $this->createMock(TrackerFactory::class);
        $this->tracker_form_element_factory  = $this->createMock(Tracker_FormElementFactory::class);
        $this->mapping_from_existing_tracker = $this->createMock(TrackerXMLFieldMappingFromExistingTracker::class);
    }

    public function testInstantiateTrackerFromXmlReturnsAlreadyExistingTracker(): void
    {
        $project = ProjectTestBuilder::aProject()->build();

        $xml           = new SimpleXMLElement('<tracker><item_name>existing_tracker</item_name></tracker>');
        $import_config = new ImportConfig();
        $import_config->addExtraConfiguration(new TrackerExtraConfiguration(['existing_tracker']));

        $this->tracker_form_element_factory->method('getFields')->willReturn([]);
        $this->mapping_from_existing_tracker->method('getXmlFieldsMapping');

        $tracker = TrackerTestBuilder::aTracker()->build();
        $this->tracker_factory->method('getTrackerByShortnameAndProjectId')->willReturn($tracker);


        $instantiator = new TrackerFromXmlInstantiator(
            $this->tracker_factory,
            $this->tracker_form_element_factory,
            $this->createMock(CreateFromXml::class),
            $this->createMock(TrackerXmlImportFeedbackCollector::class),
            new NullLogger(),
        );

        $xml_fields_mapping    = [];
        $reports_xml_mapping   = [];
        $renderers_xml_mapping = [];

        $instantiated_tracker = $instantiator->instantiateTrackerFromXml(
            $project,
            $xml,
            $import_config,
            [],
            $this->mapping_from_existing_tracker,
            $xml_fields_mapping,
            $reports_xml_mapping,
            $renderers_xml_mapping,
        );
        $this->assertEquals($tracker, $instantiated_tracker);
    }

    public function testInstantiateTracker(): void
    {
        $project       = ProjectTestBuilder::aProject()->build();
        $xml           = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><tracker/>');
        $import_config = new ImportConfig();
        $tracker       = TrackerTestBuilder::aTracker()->build();

        $from_xml_creator = $this->createMock(CreateFromXml::class);
        $from_xml_creator->expects($this->once())->method('createFromXML')->willReturn($tracker);

        $instantiator = new TrackerFromXmlInstantiator(
            $this->tracker_factory,
            $this->tracker_form_element_factory,
            $from_xml_creator,
            $this->createMock(TrackerXmlImportFeedbackCollector::class),
            new NullLogger(),
        );

        $xml_fields_mapping    = [];
        $reports_xml_mapping   = [];
        $renderers_xml_mapping = [];

        $instantiated_tracker = $instantiator->instantiateTrackerFromXml(
            $project,
            $xml,
            $import_config,
            [],
            $this->mapping_from_existing_tracker,
            $xml_fields_mapping,
            $reports_xml_mapping,
            $renderers_xml_mapping,
        );

        $this->assertEquals($tracker, $instantiated_tracker);
    }

    public function testInstantiateTrackerFromXmlDisplayErrorsBeforeThrowingAGlobalException(): void
    {
        $project       = ProjectTestBuilder::aProject()->build();
        $xml           = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><tracker/>');
        $import_config = new ImportConfig();


        $from_xml_creator = $this->createMock(CreateFromXml::class);
        $from_xml_creator->expects($this->once())->method('createFromXML')->willThrowException(
            TrackerIsInvalidException::invalidTrackerTemplate()
        );

        $feedback_collector = $this->createMock(TrackerXmlImportFeedbackCollector::class);
        $feedback_collector->expects($this->once())->method('addErrors');
        $feedback_collector->expects($this->once())->method('displayErrors');

        $this->expectException(TrackerFromXmlImportCannotBeCreatedException::class);

        $instantiator = new TrackerFromXmlInstantiator(
            $this->tracker_factory,
            $this->tracker_form_element_factory,
            $from_xml_creator,
            $feedback_collector,
            new NullLogger(),
        );

        $xml_fields_mapping    = [];
        $reports_xml_mapping   = [];
        $renderers_xml_mapping = [];

        $instantiator->instantiateTrackerFromXml(
            $project,
            $xml,
            $import_config,
            [],
            $this->mapping_from_existing_tracker,
            $xml_fields_mapping,
            $reports_xml_mapping,
            $renderers_xml_mapping,
        );
    }
}
