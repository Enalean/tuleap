<?php
/**
 * Copyright (c) Enalean, 2015 - present. All Rights Reserved.
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

namespace Tuleap\Tracker\XML\Exporter;

use PHPUnit\Framework\MockObject\MockObject;
use SimpleXMLElement;
use Tracker;
use Tracker_Artifact_XMLExport;
use TrackerFactory;
use TrackerXmlExport;
use Tuleap\Project\XML\Export\ArchiveInterface;
use Tuleap\Project\XML\Export\NoArchive;
use Tuleap\Project\XML\Import\ExternalFieldsExtractor;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Tracker\Admin\ArtifactLinksUsageDao;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\Type\TypePresenter;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\Type\TypePresenterFactory;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class TrackerXmlExportTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private Tracker&MockObject $tracker1;
    private Tracker&MockObject $tracker2;
    private TrackerXmlExport $xml_export;

    private TypePresenterFactory&MockObject $type_presenter_factory;
    private Tracker_Artifact_XMLExport&MockObject $tracker_artifact_XMLexport;
    private ArtifactLinksUsageDao&MockObject $artifact_link_dao;
    private ExternalFieldsExtractor&MockObject $external_field_extractor;

    private \EventManager&MockObject $event_manager;

    public function setUp(): void
    {
        $this->tracker1 = $this->createMock(Tracker::class);
        $this->tracker1->method('isProjectAllowedToUseType');

        $this->tracker2 = $this->createMock(Tracker::class);
        $this->tracker2->method('isProjectAllowedToUseType');

        $tracker_factory = $this->createMock(TrackerFactory::class);
        $tracker_factory->method('getTrackersByGroupId')->willReturn([$this->tracker1, $this->tracker2]);
        $tracker_factory->method('getTrackerById')->with(456)->willReturn($this->tracker1);

        $this->type_presenter_factory = $this->createMock(TypePresenterFactory::class);

        $this->artifact_link_dao = $this->createMock(ArtifactLinksUsageDao::class);
        $this->artifact_link_dao->method('isTypeDisabledInProject');

        $this->tracker_artifact_XMLexport = $this->createMock(Tracker_Artifact_XMLExport::class);

        $trigger_rules_manager = $this->createMock(\Tracker_Workflow_Trigger_RulesManager::class);
        $trigger_rules_manager->method('exportToXml');

        $rng_validator = $this->createMock(\XML_RNGValidator::class);
        $rng_validator->method('validate');

        $this->external_field_extractor = $this->createMock(ExternalFieldsExtractor::class);

        $this->event_manager = $this->createMock(\EventManager::class);

        $this->xml_export = new TrackerXmlExport(
            $tracker_factory,
            $trigger_rules_manager,
            $rng_validator,
            $this->tracker_artifact_XMLexport,
            $this->createMock(\UserXMLExporter::class),
            $this->event_manager,
            $this->type_presenter_factory,
            $this->artifact_link_dao,
            $this->external_field_extractor,
            new \Psr\Log\NullLogger()
        );
    }

    public function testExportToXml(): void
    {
        $xml_content = new SimpleXMLElement('<project/>');
        $project     = ProjectTestBuilder::aProject()->withId(123)->build();

        $this->tracker1->method('isActive')->willReturn(true);
        $this->tracker2->method('isActive')->willReturn(true);

        $this->tracker1->expects($this->once())->method('exportToXML')->willReturn('<tracker>');
        $this->tracker2->expects($this->once())->method('exportToXML')->willReturn('<tracker>');

        $this->tracker1->method('getXMLId');
        $this->tracker2->method('getXMLId');

        $this->external_field_extractor->expects($this->exactly(2))->method('extractExternalFieldsFromTracker');

        $type = new TypePresenter('fixed_in', '', '', true);

        $this->type_presenter_factory->method('getAllTypesEditableInProject')->willReturn([$type]);

        $this->event_manager->method('dispatch');

        $this->xml_export->exportToXMl(
            $project,
            $xml_content,
            UserTestBuilder::aUser()->build(),
        );
    }

    public function testExportToXmlDoNotIncludeDeletedTrackers(): void
    {
        $xml_content = new SimpleXMLElement('<project/>');
        $project     = ProjectTestBuilder::aProject()->withId(123)->build();

        $this->tracker1->method('isActive')->willReturn(true);
        $this->tracker2->method('isActive')->willReturn(false);

        $this->tracker1->expects($this->once())->method('exportToXML')->willReturn('<tracker>');
        $this->tracker2->expects($this->never())->method('exportToXML');

        $this->tracker1->method('getXMLId');
        $this->tracker2->expects($this->never())->method('getXMLId');

        $this->external_field_extractor->expects($this->once())->method('extractExternalFieldsFromTracker');

        $type = new TypePresenter('fixed_in', '', '', true);

        $this->type_presenter_factory->method('getAllTypesEditableInProject')->willReturn([$type]);

        $this->event_manager->method('dispatch');

        $this->xml_export->exportToXMl(
            $project,
            $xml_content,
            UserTestBuilder::aUser()->build(),
        );
    }

    public function testExportSingleTracker(): void
    {
        $xml_content = new SimpleXMLElement('<project/>');
        $tracker_id  = 456;
        $user        = UserTestBuilder::aUser()->build();

        $this->tracker1->method('isActive')->willReturn(true);

        $this->tracker1->expects($this->never())->method('exportToXML');
        $this->tracker1->expects($this->once())->method('exportToXMLInProjectExportContext');
        $this->tracker_artifact_XMLexport->expects($this->once())->method('export');

        $this->external_field_extractor->expects($this->once())->method('extractExternalFieldsFromTracker');

        $archive = $this->createMock(ArchiveInterface::class);

        $this->xml_export->exportSingleTrackerToXml($xml_content, $tracker_id, $user, $archive);
    }

    public function testFullExportTracker(): void
    {
        $xml_content = new SimpleXMLElement('<project/>');
        $project     = ProjectTestBuilder::aProject()->withId(123)->build();

        $this->tracker1->method('isActive')->willReturn(true);
        $this->tracker2->method('isActive')->willReturn(true);

        $this->tracker1->expects($this->once())->method('exportToXML')->willReturn('<tracker>');
        $this->tracker2->expects($this->once())->method('exportToXML')->willReturn('<tracker>');

        $this->tracker1->method('getXMLId');
        $this->tracker2->method('getXMLId');

        $this->external_field_extractor->expects($this->exactly(2))->method('extractExternalFieldsFromTracker');
        $this->tracker_artifact_XMLexport->expects($this->exactly(2))->method('export');

        $type = new TypePresenter('fixed_in', '', '', true);

        $this->type_presenter_factory->method('getAllTypesEditableInProject')->willReturn([$type]);

        $this->event_manager->method('processEvent');

        $this->xml_export->exportToXmlFull(
            $project,
            $xml_content,
            UserTestBuilder::aUser()->build(),
            new NoArchive(),
        );
    }
}
