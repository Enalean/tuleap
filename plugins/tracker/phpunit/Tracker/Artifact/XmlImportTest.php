<?php
/**
 * Copyright (c) Enalean, 2018-Present. All Rights Reserved.
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
 *
 */

namespace Tuleap\Tracker\Artifact;

use Logger;
use Mockery;
use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;
use org\bovigo\vfs\vfsStreamWrapper;
use PFUser;
use PHPUnit\Framework\TestCase;
use Psr\Log\LogLevel;
use Response;
use Tracker;
use Tracker\Artifact\XMLArtifactSourcePlatformExtractor;
use Tracker_Artifact;
use Tracker_Artifact_Changeset;
use Tracker_Artifact_Changeset_Comment;
use Tracker_Artifact_Changeset_NewChangesetCreatorBase;
use Tracker_Artifact_ChangesetValue;
use Tracker_Artifact_XMLImport;
use Tracker_ArtifactCreator;
use Tracker_ArtifactFactory;
use Tracker_FormElement_Field;
use Tracker_FormElement_Field_List_Bind_Static_ValueDao;
use Tracker_FormElement_Field_String;
use Tracker_FormElementFactory;
use Tracker_XML_Importer_ArtifactImportedMapping;
use TrackerXmlFieldsMapping_FromAnotherPlatform;
use Tuleap\Project\XML\Import\ExternalFieldsExtractor;
use Tuleap\Project\XML\Import\ImportConfig;
use Tuleap\Tracker\DAO\TrackerArtifactSourceIdDao;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\Nature\NatureDao;
use Tuleap\Tracker\FormElement\Field\File\CreatedFileURLMapping;
use UserManager;
use Workflow;
use XML_RNGValidator;
use XMLImportHelper;

require_once __DIR__ . '/../../bootstrap.php';

class XmlImportTest extends TestCase
{
    use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    private $summary_field_id = 50;

    private $tracker_id = 100;

    private $extraction_path;

    /**
     * @var PFUser
     */
    private $john_doe;

    /**
     * @var Tracker
     */
    private $tracker;

    /**
     * @var Tracker_ArtifactCreator
     */
    private $artifact_creator;

    /**
     * @var Tracker_Artifact_Changeset_NewChangesetCreatorBase
     */
    private $new_changeset_creator;

    /**
     * @var Tracker_FormElementFactory
     */
    private $formelement_factory;

    /**
     * @var UserManager
     */
    private $user_manager;

    /**
     * @var XMLImportHelper
     */
    private $xml_import_helper;

    /**
     * @var Tracker_FormElement_Field_List_Bind_Static_ValueDao
     */
    private $static_value_dao;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var XML_RNGValidator
     */
    private $rng_validator;

    /**
     * @var Tracker_Artifact_XMLImport
     */
    private $importer;

    /**
     * @var TrackerXmlFieldsMapping_FromAnotherPlatform
     */
    private $xml_mapping;

    /**
     * @var ImportConfig
     */
    private $config;

    /**
     * @var ExistingArtifactSourceIdFromTrackerExtractor
     */
    private $existing_artifact_source_id_extractor;

    /**
     * @var Tracker_ArtifactFactory
     */
    private $tracker_artifact_factory;

    /**
     * @var Tracker_FormElement_Field_String
     */
    private $tracker_formelement_field_string;

    /**
     * @var XMLArtifactSourcePlatformExtractor
     */
    private $xml_artifact_source_platform_extractor;

    /**
     * @var TrackerArtifactSourceIdDao
     */
    private $artifact_source_id_dao;
    /**
     * @var Mockery\MockInterface|CreatedFileURLMapping
     */
    private $url_mapping;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|ExternalFieldsExtractor
     */
    private $external_field_extractor;

    public function setUp(): void
    {
        $this->tracker = Mockery::mock(Tracker::class);
        $this->tracker->shouldReceive('getId')->andReturn($this->tracker_id);
        $this->tracker->shouldReceive('getWorkflow')->andReturn(Mockery::spy(Workflow::class));

        $this->artifact_creator                       = Mockery::mock(Tracker_ArtifactCreator::class);
        $this->new_changeset_creator                  = Mockery::mock(Tracker_Artifact_Changeset_NewChangesetCreatorBase::class);
        $this->formelement_factory                    = Mockery::mock(Tracker_FormElementFactory::class);
        $this->tracker_artifact_factory               = Mockery::mock(Tracker_ArtifactFactory::class);
        $this->existing_artifact_source_id_extractor  = Mockery::mock(ExistingArtifactSourceIdFromTrackerExtractor::class);
        $this->static_value_dao                       = Mockery::mock(Tracker_FormElement_Field_List_Bind_Static_ValueDao::class);
        $this->xml_artifact_source_platform_extractor = Mockery::mock(XMLArtifactSourcePlatformExtractor::class);
        $this->response                               = Mockery::mock(Response::class);
        $this->config                                 = Mockery::mock(ImportConfig::class);
        $this->artifacts_id_mapping                   = new Tracker_XML_Importer_ArtifactImportedMapping();
        $this->xml_mapping                            = new TrackerXmlFieldsMapping_FromAnotherPlatform([]);
        $this->url_mapping                            = Mockery::mock(CreatedFileURLMapping::class);
        $this->artifact_source_id_dao                 = Mockery::mock(TrackerArtifactSourceIdDao::class);

        $this->tracker_formelement_field_string = Mockery::mock(Tracker_FormElement_Field_String::class);
        $this->tracker_formelement_field_string->shouldReceive('setTracker');
        $this->tracker_formelement_field_string->shouldReceive('getName')->andReturns('summary');
        $this->tracker_formelement_field_string->shouldReceive('getId')->andReturns($this->summary_field_id);
        $this->tracker_formelement_field_string->shouldReceive('getTrackerId')->andReturns($this->tracker_id);
        $this->tracker_formelement_field_string->shouldReceive('getLabel')->andReturns('summary');
        $this->tracker_formelement_field_string->shouldReceive('validateField')->andReturns(true);

        $this->john_doe = Mockery::mock(PFUser::class);
        $this->john_doe->shouldReceive('getId')->andReturn(200);

        $this->user_manager = Mockery::mock(UserManager::class);
        $this->user_manager->shouldReceive('getUserByIdentifier')->withArgs(['john_doe'])->andReturn($this->john_doe);
        $this->user_manager->shouldReceive('getUserAnonymous')->andReturn(new PFUser(array('language_id' => 'en_US', 'user_id' => 0)));

        $this->xml_import_helper = Mockery::mock(XMLImportHelper::class);
        $this->xml_import_helper->shouldReceive('getUser')->andReturn($this->john_doe);

        $this->extraction_path = $this->getTmpDir();

        $this->logger = Mockery::mock(\Psr\Log\LoggerInterface::class);
        $this->logger->shouldReceive('log')->with(LogLevel::INFO, Mockery::any(), Mockery::any());
        $this->logger->shouldReceive('log')->with(LogLevel::DEBUG, Mockery::any(), Mockery::any());

        $this->rng_validator =  Mockery::mock(XML_RNGValidator::class);
        $this->rng_validator->shouldReceive('validate');

        $this->external_field_extractor = Mockery::mock(ExternalFieldsExtractor::class);

        $this->importer = new Tracker_Artifact_XMLImport(
            $this->rng_validator,
            $this->artifact_creator,
            $this->new_changeset_creator,
            $this->formelement_factory,
            $this->xml_import_helper,
            $this->static_value_dao,
            $this->logger,
            false,
            $this->tracker_artifact_factory,
            Mockery::mock(NatureDao::class),
            $this->xml_artifact_source_platform_extractor,
            $this->existing_artifact_source_id_extractor,
            $this->artifact_source_id_dao,
            $this->external_field_extractor
        );
    }

    public function testImportChangesetInNewArtifactWithNoChangeSet()
    {
        $this->xml_artifact_source_platform_extractor->shouldReceive("getSourcePlatform")->andReturn("https://web/");

        $changeset_1 = $this->mockAChangeset($this->john_doe->getId(), strtotime("2014-01-15T10:38:06+01:00"), null, null, null, $this->tracker_id, "summary", 'OK', 0);
        $changeset_2 = $this->mockAChangeset($this->john_doe->getId(), strtotime("2014-01-15T10:38:06+01:00"), null, null, null, $this->tracker_id, "summary", 'Again', 1);
        $changeset_3 = $this->mockAChangeset($this->john_doe->getId(), strtotime("2014-01-15T10:38:06+01:00"), null, null, null, $this->tracker_id, "summary", 'Value', 2);

        $this->config->shouldReceive('isUpdate')->andReturn(false);

        $artifact = $this->mockAnArtifact(101, $this->tracker, $this->tracker_id, []);

        $xml_field_mapping = file_get_contents(__DIR__ . '/_fixtures/testImportChangesetInNewArtifact.xml');
        $xml_input = simplexml_load_string($xml_field_mapping);

        $data = array(
            $this->summary_field_id => 'OK'
        );

        $this->artifact_creator
            ->shouldReceive('createFirstChangeset')
            ->with(
                $artifact,
                $data,
                $this->john_doe,
                Mockery::any(),
                false,
                Mockery::any()
            )
            ->andReturn($changeset_1)
            ->once();

        $data = array(
            $this->summary_field_id => 'Again'
        );

        $this->new_changeset_creator
            ->shouldReceive('create')
            ->with(
                $artifact,
                $data,
                Mockery::any(),
                $this->john_doe,
                Mockery::any(),
                false,
                "text",
                $this->url_mapping
            )
            ->andReturn($changeset_2)
            ->once();

        $data = array(
            $this->summary_field_id => 'Value'
        );

        $this->new_changeset_creator
            ->shouldReceive('create')
            ->with(
                $artifact,
                $data,
                Mockery::any(),
                $this->john_doe,
                Mockery::any(),
                false,
                "text",
                $this->url_mapping
            )
            ->andReturn($changeset_3)
            ->once();

        $this->artifact_creator->shouldReceive('createBare')->once()->andReturn($artifact);

        $this->formelement_factory->shouldReceive('getUsedFieldByName')->withArgs([$this->tracker_id, 'summary'])->andReturn($this->tracker_formelement_field_string);

        $this->formelement_factory->shouldReceive('getFormElementByName')->andReturn([]);

        $this->existing_artifact_source_id_extractor->shouldReceive('getSourceArtifactIds')->andReturn();

        $this->artifact_source_id_dao->shouldReceive('save')->withArgs([101, 4918, "https://web/"])->once();

        $this->external_field_extractor->shouldReceive('extractExternalFieldsFromArtifact')->once();

        $this->importer->importFromXML(
            $this->tracker,
            $xml_input,
            $this->extraction_path,
            $this->xml_mapping,
            $this->url_mapping,
            $this->config
        );
    }

    public function testUpdateModeItCreateArtifactAndChangesetInExistingTracker()
    {
        $this->xml_artifact_source_platform_extractor->shouldReceive("getSourcePlatform")->andReturn();

        $changeset_1 = $this->mockAChangeset($this->john_doe->getId(), strtotime("2014-01-15T10:38:06+01:00"), null, null, null, $this->tracker_id, "summary", 'OK', 0);
        $changeset_2 = $this->mockAChangeset($this->john_doe->getId(), strtotime("2014-01-15T10:38:06+01:00"), null, null, null, $this->tracker_id, "summary", 'Again', 1);
        $changeset_3 = $this->mockAChangeset($this->john_doe->getId(), strtotime("2014-01-15T10:38:06+01:00"), null, null, null, $this->tracker_id, "summary", 'Value', 2);

        $this->existing_artifact_source_id_extractor->shouldReceive('getSourceArtifactIds')->andReturn();

        $this->artifact_source_id_dao->shouldReceive('getSourceArtifactId')->andReturn(101);

        $this->config->shouldReceive('isUpdate')->andReturn(true);

        $artifact = $this->mockAnArtifact(101, $this->tracker, $this->tracker_id, []);

        $this->artifact_creator->shouldReceive('createBare')->once()->andReturn($artifact);

        $xml_field_mapping = file_get_contents(__DIR__ . '/_fixtures/testImportChangesetInNewArtifact.xml');
        $xml_input = simplexml_load_string($xml_field_mapping);

        $data = array(
            $this->summary_field_id => 'OK'
        );

        $this->artifact_creator
            ->shouldReceive('createFirstChangeset')
            ->with(
                $artifact,
                $data,
                $this->john_doe,
                Mockery::any(),
                false,
                $this->url_mapping
            )
            ->andReturn($changeset_1)
            ->once();

        $data = array(
            $this->summary_field_id => 'Again'
        );

        $this->new_changeset_creator
            ->shouldReceive('create')
            ->with(
                $artifact,
                $data,
                Mockery::any(),
                $this->john_doe,
                Mockery::any(),
                false,
                "text",
                $this->url_mapping
            )
            ->andReturn($changeset_2)
            ->once();

        $data = array(
            $this->summary_field_id => 'Value'
        );

        $this->new_changeset_creator
            ->shouldReceive('create')
            ->with(
                $artifact,
                $data,
                Mockery::any(),
                $this->john_doe,
                Mockery::any(),
                false,
                "text",
                $this->url_mapping
            )
            ->andReturn($changeset_3)
            ->once();

        $this->formelement_factory->shouldReceive('getUsedFieldByName')->withArgs([$this->tracker_id, 'summary'])->andReturn($this->tracker_formelement_field_string);

        $this->external_field_extractor->shouldReceive('extractExternalFieldsFromArtifact')->once();


        $this->importer->importFromXML(
            $this->tracker,
            $xml_input,
            $this->extraction_path,
            $this->xml_mapping,
            $this->url_mapping,
            $this->config
        );
    }

    public function testUpdateModeWithoutSourcePlatformAttributeItCreateArtifactAndChangesetInExistingTracker()
    {
        $this->xml_artifact_source_platform_extractor->shouldReceive("getSourcePlatform")->andReturn(null);

        $this->existing_artifact_source_id_extractor->shouldReceive('getSourceArtifactIds')->with($this->tracker, null)->andReturn(null);

        $this->artifact_source_id_dao->shouldReceive('getSourceArtifactId')->andReturn();

        $changeset_1 = $this->mockAChangeset($this->john_doe->getId(), strtotime("2014-01-15T10:38:06+01:00"), null, null, null, $this->tracker_id, "summary", 'OK', 0);
        $changeset_2 = $this->mockAChangeset($this->john_doe->getId(), strtotime("2014-01-15T10:38:06+01:00"), null, null, null, $this->tracker_id, "summary", 'Again', 1);
        $changeset_3 = $this->mockAChangeset($this->john_doe->getId(), strtotime("2014-01-15T10:38:06+01:00"), null, null, null, $this->tracker_id, "summary", 'Value', 2);

        $this->config->shouldReceive('isUpdate')->andReturn(true);

        $artifact = $this->mockAnArtifact(101, $this->tracker, $this->tracker_id, []);

        $this->artifact_creator->shouldReceive('createBare')->once()->andReturn($artifact);

        $xml_field_mapping = file_get_contents(__DIR__ . '/_fixtures/testImportChangesetInArtifactWithoutSourcePlatformAttribute.xml');
        $xml_input = simplexml_load_string($xml_field_mapping);

        $data = array(
            $this->summary_field_id => 'OK'
        );

        $this->artifact_creator
            ->shouldReceive('createFirstChangeset')
            ->with(
                $artifact,
                $data,
                $this->john_doe,
                Mockery::any(),
                false,
                $this->url_mapping
            )
            ->andReturn($changeset_1)
            ->once();

        $data = array(
            $this->summary_field_id => 'Again'
        );

        $this->new_changeset_creator
            ->shouldReceive('create')
            ->with(
                $artifact,
                $data,
                Mockery::any(),
                $this->john_doe,
                Mockery::any(),
                false,
                "text",
                $this->url_mapping
            )
            ->andReturn($changeset_2)
            ->once();

        $data = array(
            $this->summary_field_id => 'Value'
        );

        $this->new_changeset_creator
            ->shouldReceive('create')
            ->with(
                $artifact,
                $data,
                Mockery::any(),
                $this->john_doe,
                Mockery::any(),
                false,
                "text",
                $this->url_mapping
            )
            ->andReturn($changeset_3)
            ->once();

        $this->formelement_factory->shouldReceive('getUsedFieldByName')->withArgs([$this->tracker_id, 'summary'])->andReturn($this->tracker_formelement_field_string);

        $this->external_field_extractor->shouldReceive('extractExternalFieldsFromArtifact')->once();

        $this->importer->importFromXML(
            $this->tracker,
            $xml_input,
            $this->extraction_path,
            $this->xml_mapping,
            $this->url_mapping,
            $this->config
        );
    }

    public function testUpdateModeWithWrongSourcePlatformAttributeItCreateArtifactAndChangesetInExistingTracker()
    {
        $this->xml_artifact_source_platform_extractor->shouldReceive("getSourcePlatform")->andReturn(null);

        $this->artifact_source_id_dao->shouldReceive('getSourceArtifactId')->andReturn();

        $changeset_1 = $this->mockAChangeset($this->john_doe->getId(), strtotime("2014-01-15T10:38:06+01:00"), null, null, null, $this->tracker_id, "summary", 'OK', 0);
        $changeset_2 = $this->mockAChangeset($this->john_doe->getId(), strtotime("2014-01-15T10:38:06+01:00"), null, null, null, $this->tracker_id, "summary", 'Again', 1);
        $changeset_3 = $this->mockAChangeset($this->john_doe->getId(), strtotime("2014-01-15T10:38:06+01:00"), null, null, null, $this->tracker_id, "summary", 'Value', 2);

        $this->config->shouldReceive('isUpdate')->andReturn(true);

        $artifact = $this->mockAnArtifact(101, $this->tracker, $this->tracker_id, []);

        $this->artifact_creator->shouldReceive('createBare')->once()->andReturn($artifact);

        $xml_field_mapping = file_get_contents(dirname(__FILE__) . '/_fixtures/testImportChangesetInArtifactWithWrongSourcePlatformAttribute.xml');
        $xml_input = simplexml_load_string($xml_field_mapping);

        $data = array(
            $this->summary_field_id => 'OK'
        );

        $this->artifact_creator
            ->shouldReceive('createFirstChangeset')
            ->with(
                $artifact,
                $data,
                $this->john_doe,
                Mockery::any(),
                false,
                $this->url_mapping
            )
            ->andReturn($changeset_1)
            ->once();

        $data = array(
            $this->summary_field_id => 'Again'
        );

        $this->new_changeset_creator
            ->shouldReceive('create')
            ->with(
                $artifact,
                $data,
                Mockery::any(),
                $this->john_doe,
                Mockery::any(),
                false,
                "text",
                $this->url_mapping
            )
            ->andReturn($changeset_2)
            ->once();

        $data = array(
            $this->summary_field_id => 'Value'
        );

        $this->new_changeset_creator
            ->shouldReceive('create')
            ->with(
                $artifact,
                $data,
                Mockery::any(),
                $this->john_doe,
                Mockery::any(),
                false,
                "text",
                $this->url_mapping
            )
            ->andReturn($changeset_3)
            ->once();

        $this->formelement_factory->shouldReceive('getUsedFieldByName')->withArgs([$this->tracker_id, 'summary'])->andReturn($this->tracker_formelement_field_string);

        $this->existing_artifact_source_id_extractor->shouldReceive('getSourceArtifactIds')->andReturn();

        $this->logger->shouldReceive('warn')->with("[XML import] No correspondence between artifact_id and source_artifact_id. New artifact created.", null);

        $this->external_field_extractor->shouldReceive('extractExternalFieldsFromArtifact')->once();

        $this->importer->importFromXML(
            $this->tracker,
            $xml_input,
            $this->extraction_path,
            $this->xml_mapping,
            $this->url_mapping,
            $this->config
        );
    }

    public function testUpdateItCreateChangesetsInExistingArtifactWithChangeset()
    {
        $this->xml_artifact_source_platform_extractor->shouldReceive("getSourcePlatform")->andReturn("https://web/");

        $this->config->shouldReceive('isUpdate')->andReturn(true);

        $changeset = $this->mockAChangeset($this->john_doe->getId(), strtotime("102030"), "un com", $this->john_doe->getId(), strtotime("102030"), $this->tracker_id, "stuff", 'value', 0);

        $artifact = $this->mockAnArtifact(101, $this->tracker, $this->tracker_id, [$changeset]);

        $xml_field_mapping = file_get_contents(dirname(__FILE__) . '/_fixtures/testImportChangesetInNewArtifact.xml');
        $xml_input = simplexml_load_string($xml_field_mapping);

        $this->tracker_artifact_factory->shouldReceive('getArtifactById')->andReturn($artifact)->times(2);

        $this->formelement_factory->shouldReceive('getUsedFieldByName')->withArgs([$this->tracker_id, 'summary'])->andReturn($this->tracker_formelement_field_string);

        $this->existing_artifact_source_id_extractor->shouldReceive('getSourceArtifactIds')->andReturn([4918 => 101]);

        $this->artifact_source_id_dao->shouldReceive('getSourceArtifactId')->with(101, "https://web/")->andReturn(4918);

        $changeset_1 = $this->mockAChangeset($this->john_doe->getId(), strtotime("102030"), "un com", $this->john_doe->getId(), strtotime("102030"), $this->tracker_id, "stuff", 'OK', 0);
        $changeset_2 = $this->mockAChangeset($this->john_doe->getId(), strtotime("102030"), "un com", $this->john_doe->getId(), strtotime("102030"), $this->tracker_id, "stuff", 'Again', 1);
        $changeset_3 = $this->mockAChangeset($this->john_doe->getId(), strtotime("102030"), "un com", $this->john_doe->getId(), strtotime("102030"), $this->tracker_id, "stuff", 'Value', 2);

        $data = array(
            $this->summary_field_id => 'OK'
        );

        $this->new_changeset_creator
            ->shouldReceive('create')
            ->with(
                $artifact,
                $data,
                Mockery::any(),
                $this->john_doe,
                Mockery::any(),
                false,
                "text",
                $this->url_mapping
            )
            ->once()
            ->andReturn($changeset_1);

        $data = array(
            $this->summary_field_id => 'Again'
        );

        $this->new_changeset_creator
            ->shouldReceive('create')
            ->with(
                $artifact,
                $data,
                Mockery::any(),
                $this->john_doe,
                Mockery::any(),
                false,
                "text",
                $this->url_mapping
            )
            ->andReturn($changeset_2)
            ->once();

        $data = array(
            $this->summary_field_id => 'Value'
        );

        $this->new_changeset_creator
            ->shouldReceive('create')
            ->with(
                $artifact,
                $data,
                Mockery::any(),
                $this->john_doe,
                Mockery::any(),
                false,
                "text",
                $this->url_mapping
            )
            ->andReturn($changeset_3)
            ->once();

        $this->external_field_extractor->shouldReceive('extractExternalFieldsFromArtifact')->once();

        $this->importer->importFromXML(
            $this->tracker,
            $xml_input,
            $this->extraction_path,
            $this->xml_mapping,
            $this->url_mapping,
            $this->config
        );
    }

    private function getTmpDir()
    {
        vfsStreamWrapper::register();
        vfsStreamWrapper::setRoot(new vfsStreamDirectory("tuleap_tests"));

        return vfsStream::url("tuleap_tests");
    }

    /**
     * @param $id
     * @param $tracker
     * @param $tracker_id
     * @param array $changeset
     * @return Tracker_Artifact
     */
    private function mockAnArtifact($id, $tracker, $tracker_id, $changeset = [])
    {
        $artifact = Mockery::mock(Tracker_Artifact::class);
        $artifact->shouldReceive('getId')->andReturn($id);
        $artifact->shouldReceive('getTracker')->andReturn($tracker);
        $artifact->shouldReceive('getTrackerId')->andReturn($tracker_id);
        $artifact->shouldReceive('getChangesets')->andReturn($changeset);
        return $artifact;
    }

    private function mockAChangeset($subby, $subon, $txt_com, $subby_com, $subon_com, $id_tracker, $name_field, $value_change, $id)
    {
        $formelement_field = Mockery::mock(Tracker_FormElement_Field::class);
        $formelement_field->shouldReceive('getName')->andReturn($name_field);

        $changesetValue = Mockery::mock(Tracker_Artifact_ChangesetValue::class);
        $changesetValue->shouldReceive('getField')->andReturn($formelement_field);
        $changesetValue->shouldReceive('getValue')->andReturn($value_change);

        $tracker = Mockery::mock(Tracker::class);
        $tracker->shouldReceive('getId')->andReturn($id_tracker);

        $comment = Mockery::mock(Tracker_Artifact_Changeset_Comment::class);
        $comment->shouldReceive('getSubmittedOn')->andReturn($subon_com);
        $comment->shouldReceive('getSubmittedBy')->andReturn($subby_com);
        $comment->shouldReceive('getPurifiedBodyForText')->andReturn($txt_com);

        $changeset = Mockery::mock(Tracker_Artifact_Changeset::class);
        $changeset->shouldReceive('getComment')->andReturn($comment);
        $changeset->shouldReceive('getSubmittedOn')->andReturn($subon);
        $changeset->shouldReceive('getSubmittedBy')->andReturn($subby);
        $changeset->shouldReceive('getTracker')->andReturn($tracker);
        $changeset->shouldReceive('getValue')->andReturn($changesetValue);
        $changeset->shouldReceive('getId')->andReturn($id);

        return $changeset;
    }
}
