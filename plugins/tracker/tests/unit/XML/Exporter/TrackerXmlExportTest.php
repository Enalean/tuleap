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

use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Project;
use SimpleXMLElement;
use Tracker;
use Tracker_Artifact_XMLExport;
use TrackerFactory;
use TrackerXmlExport;
use Tuleap\Project\XML\Export\ArchiveInterface;
use Tuleap\Project\XML\Export\NoArchive;
use Tuleap\Project\XML\Import\ExternalFieldsExtractor;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Tracker\Admin\ArtifactLinksUsageDao;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\Type\TypePresenter;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\Type\TypePresenterFactory;

final class TrackerXmlExportTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use MockeryPHPUnitIntegration;

    private $tracker1;
    private $tracker2;
    private $xml_export;

    /**
     * @var  Mockery\LegacyMockInterface|Mockery\MockInterface|TypePresenterFactory
     */
    private $type_presenter_factory;
    /**
     * @var Tracker_Artifact_XMLExport
     */
    private $tracker_artifact_XMLexport;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|ArtifactLinksUsageDao
     */
    private $artifact_link_dao;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|ExternalFieldsExtractor
     */
    private $external_field_extractor;

    private \EventManager&\PHPUnit\Framework\MockObject\MockObject $event_manager;

    public function setUp(): void
    {
        $this->tracker1 = Mockery::mock(Tracker::class);
        $this->tracker1->shouldReceive('isProjectAllowedToUseType');

        $this->tracker2 = Mockery::mock(Tracker::class);
        $this->tracker2->shouldReceive('isProjectAllowedToUseType');

        $tracker_factory = Mockery::mock(TrackerFactory::class);
        $tracker_factory->shouldReceive('getTrackersByGroupId')->andReturn([$this->tracker1, $this->tracker2]);
        $tracker_factory->shouldReceive('getTrackerById')->withArgs([456])->andReturn($this->tracker1);

        $this->type_presenter_factory = Mockery::mock(TypePresenterFactory::class);

        $this->artifact_link_dao = Mockery::mock(ArtifactLinksUsageDao::class);
        $this->artifact_link_dao->shouldReceive('isTypeDisabledInProject');

        $this->tracker_artifact_XMLexport = Mockery::mock(Tracker_Artifact_XMLExport::class);

        $trigger_rules_manager = Mockery::mock(\Tracker_Workflow_Trigger_RulesManager::class);
        $trigger_rules_manager->shouldReceive('exportToXml');

        $rng_validator = Mockery::mock(\XML_RNGValidator::class);
        $rng_validator->shouldReceive('validate');

        $this->external_field_extractor = Mockery::mock(ExternalFieldsExtractor::class);

        $this->event_manager = $this->createMock(\EventManager::class);

        $this->xml_export = new TrackerXmlExport(
            $tracker_factory,
            $trigger_rules_manager,
            $rng_validator,
            $this->tracker_artifact_XMLexport,
            Mockery::mock(\UserXMLExporter::class),
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
        $project     = Mockery::mock(Project::class);
        $project->shouldReceive('getId')->andReturn(123);

        $this->tracker1->shouldReceive('isActive')->andReturn(true);
        $this->tracker2->shouldReceive('isActive')->andReturn(true);

        $this->tracker1->shouldReceive('exportToXML')->once()->andReturn('<tracker>');
        $this->tracker2->shouldReceive('exportToXML')->once()->andReturn('<tracker>');

        $this->tracker1->shouldReceive('getXMLId');
        $this->tracker2->shouldReceive('getXMLId');

        $this->external_field_extractor->shouldReceive('extractExternalFieldsFromTracker')->twice();

        $type = new TypePresenter('fixed_in', '', '', true);

        $this->type_presenter_factory->shouldReceive('getAllTypesEditableInProject')->andReturn([$type]);

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
        $project     = Mockery::mock(Project::class);
        $project->shouldReceive('getId')->andReturn(123);

        $this->tracker1->shouldReceive('isActive')->andReturn(true);
        $this->tracker2->shouldReceive('isActive')->andReturn(false);

        $this->tracker1->shouldReceive('exportToXML')->once()->andReturn('<tracker>');
        $this->tracker2->shouldReceive('exportToXML')->never();

        $this->tracker1->shouldReceive('getXMLId');
        $this->tracker2->shouldNotReceive('getXMLId');

        $this->external_field_extractor->shouldReceive('extractExternalFieldsFromTracker')->once();

        $type = new TypePresenter('fixed_in', '', '', true);

        $this->type_presenter_factory->shouldReceive('getAllTypesEditableInProject')->andReturn([$type]);

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

        $this->tracker1->shouldReceive('isActive')->andReturn(true);

        $this->tracker1->shouldReceive('exportToXML')->never();
        $this->tracker1->shouldReceive('exportToXMLInProjectExportContext')->once();
        $this->tracker_artifact_XMLexport->shouldReceive('export')->once();

        $this->external_field_extractor->shouldReceive('extractExternalFieldsFromTracker')->once();

        $archive = Mockery::mock(ArchiveInterface::class);

        $this->xml_export->exportSingleTrackerToXml($xml_content, $tracker_id, $user, $archive);
    }

    public function testFullExportTracker(): void
    {
        $xml_content = new SimpleXMLElement('<project/>');
        $project     = Mockery::mock(Project::class);
        $project->shouldReceive('getId')->andReturn(123);

        $this->tracker1->shouldReceive('isActive')->andReturn(true);
        $this->tracker2->shouldReceive('isActive')->andReturn(true);

        $this->tracker1->shouldReceive('exportToXML')->once()->andReturn('<tracker>');
        $this->tracker2->shouldReceive('exportToXML')->once()->andReturn('<tracker>');

        $this->tracker1->shouldReceive('getXMLId');
        $this->tracker2->shouldReceive('getXMLId');

        $this->external_field_extractor->shouldReceive('extractExternalFieldsFromTracker')->twice();
        $this->tracker_artifact_XMLexport->shouldReceive('export')->times(2);

        $type = new TypePresenter('fixed_in', '', '', true);

        $this->type_presenter_factory->shouldReceive('getAllTypesEditableInProject')->andReturn([$type]);

        $this->event_manager->method('processEvent');

        $this->xml_export->exportToXmlFull(
            $project,
            $xml_content,
            UserTestBuilder::aUser()->build(),
            new NoArchive(),
        );
    }
}
