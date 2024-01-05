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

use Mockery;
use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;
use org\bovigo\vfs\vfsStreamWrapper;
use PFUser;
use Psr\Log\NullLogger;
use Response;
use Tracker;
use Tracker_Artifact_Changeset;
use Tracker_Artifact_Changeset_Comment;
use Tracker_Artifact_ChangesetValue;
use Tracker_Artifact_XMLImport;
use Tracker_FormElement_Field;
use Tracker_FormElement_Field_String;
use Tracker_FormElementFactory;
use Tracker_XML_Importer_ArtifactImportedMapping;
use TrackerXmlFieldsMapping_FromAnotherPlatform;
use Tuleap\Project\XML\Import\ExternalFieldsExtractor;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Tracker\Artifact\Changeset\Comment\PrivateComment\XMLImport\TrackerPrivateCommentUGroupExtractor;
use Tuleap\Tracker\Artifact\Changeset\NewChangeset;
use Tuleap\Tracker\Artifact\Changeset\NewChangesetCreator;
use Tuleap\Tracker\Artifact\Changeset\PostCreation\PostCreationContext;
use Tuleap\Tracker\Artifact\Creation\TrackerArtifactCreator;
use Tuleap\Tracker\Artifact\XMLImport\MoveImportConfig;
use Tuleap\Tracker\Artifact\XMLImport\TrackerXmlImportConfig;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\Type\TypeDao;
use Tuleap\Tracker\FormElement\Field\File\CreatedFileURLMapping;
use Tuleap\Tracker\FormElement\Field\ListFields\Bind\BindStaticValueDao;
use UserManager;
use Workflow;
use XML_RNGValidator;
use XMLImportHelper;

final class XmlImportTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    private TrackerXmlImportConfig $import_config;
    private $summary_field_id = 50;

    private $tracker_id = 100;

    private $extraction_path;
    private PFUser $john_doe;

    /**
     * @var Tracker
     */
    private $tracker;

    /**
     * @var TrackerArtifactCreator
     */
    private $artifact_creator;

    /**
     * @var NewChangesetCreator
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
     * @var BindStaticValueDao
     */
    private $static_value_dao;

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
     * @var Tracker_FormElement_Field_String
     */
    private $tracker_formelement_field_string;

    /**
     * @var Mockery\MockInterface|CreatedFileURLMapping
     */
    private $url_mapping;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|ExternalFieldsExtractor
     */
    private $external_field_extractor;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|TrackerPrivateCommentUGroupExtractor
     */
    private $private_comment_extractor;
    private $response;
    private Tracker_XML_Importer_ArtifactImportedMapping $artifacts_id_mapping;
    private \Tuleap\DB\DBConnection&\PHPUnit\Framework\MockObject\MockObject $db_connection;

    public function setUp(): void
    {
        $this->tracker = Mockery::mock(Tracker::class);
        $this->tracker->shouldReceive('getId')->andReturn($this->tracker_id);
        $this->tracker->shouldReceive('getWorkflow')->andReturn(Mockery::spy(Workflow::class));

        $this->artifact_creator      = Mockery::mock(TrackerArtifactCreator::class);
        $this->new_changeset_creator = Mockery::mock(NewChangesetCreator::class);
        $this->formelement_factory   = Mockery::mock(Tracker_FormElementFactory::class);
        $this->static_value_dao      = Mockery::mock(BindStaticValueDao::class);
        $this->response              = Mockery::mock(Response::class);
        $this->artifacts_id_mapping  = new Tracker_XML_Importer_ArtifactImportedMapping();
        $this->xml_mapping           = new TrackerXmlFieldsMapping_FromAnotherPlatform([]);
        $this->url_mapping           = Mockery::mock(CreatedFileURLMapping::class);

        $this->tracker_formelement_field_string = Mockery::mock(Tracker_FormElement_Field_String::class);
        $this->tracker_formelement_field_string->shouldReceive('setTracker');
        $this->tracker_formelement_field_string->shouldReceive('getName')->andReturns('summary');
        $this->tracker_formelement_field_string->shouldReceive('getId')->andReturns($this->summary_field_id);
        $this->tracker_formelement_field_string->shouldReceive('getTrackerId')->andReturns($this->tracker_id);
        $this->tracker_formelement_field_string->shouldReceive('getLabel')->andReturns('summary');
        $this->tracker_formelement_field_string->shouldReceive('validateField')->andReturns(true);

        $this->john_doe = UserTestBuilder::aUser()->withId(200)->build();

        $this->user_manager = Mockery::mock(UserManager::class);
        $this->user_manager->shouldReceive('getUserByIdentifier')->withArgs(['john_doe'])->andReturn($this->john_doe);
        $this->user_manager->shouldReceive('getUserAnonymous')->andReturn(new PFUser(['language_id' => 'en_US', 'user_id' => 0]));

        $this->xml_import_helper = Mockery::mock(XMLImportHelper::class);
        $this->xml_import_helper->shouldReceive('getUser')->andReturn($this->john_doe);

        $this->extraction_path = $this->getTmpDir();

        $this->rng_validator =  Mockery::mock(XML_RNGValidator::class);
        $this->rng_validator->shouldReceive('validate');

        $this->import_config = new TrackerXmlImportConfig($this->john_doe, new \DateTimeImmutable(), MoveImportConfig::buildForRegularImport(), false);

        $this->external_field_extractor = Mockery::mock(ExternalFieldsExtractor::class);

        $this->db_connection = $this->createMock(\Tuleap\DB\DBConnection::class);
        $this->db_connection->method('reconnectAfterALongRunningProcess');

        $this->private_comment_extractor = Mockery::mock(TrackerPrivateCommentUGroupExtractor::class);

        $this->importer = new Tracker_Artifact_XMLImport(
            $this->rng_validator,
            $this->artifact_creator,
            $this->new_changeset_creator,
            $this->formelement_factory,
            $this->xml_import_helper,
            $this->static_value_dao,
            new NullLogger(),
            false,
            Mockery::mock(TypeDao::class),
            $this->external_field_extractor,
            $this->private_comment_extractor,
            $this->db_connection,
        );
    }

    public function testImportChangesetInNewArtifactWithNoChangeSet(): void
    {
        $changeset_1 = $this->mockAChangeset($this->john_doe->getId(), strtotime("2014-01-15T10:38:06+01:00"), null, null, null, $this->tracker_id, "summary", 'OK', 0);
        $changeset_2 = $this->mockAChangeset($this->john_doe->getId(), strtotime("2014-01-15T10:38:06+01:00"), null, null, null, $this->tracker_id, "summary", 'Again', 1);
        $changeset_3 = $this->mockAChangeset($this->john_doe->getId(), strtotime("2014-01-15T10:38:06+01:00"), null, null, null, $this->tracker_id, "summary", 'Value', 2);

        $artifact = $this->mockAnArtifact(101, $this->tracker, $this->tracker_id, []);

        $xml_field_mapping = file_get_contents(__DIR__ . '/_fixtures/testImportChangesetInNewArtifact.xml');
        $xml_input         = simplexml_load_string($xml_field_mapping);

        $data = [
            $this->summary_field_id => 'OK',
        ];

        $this->artifact_creator
            ->shouldReceive('createFirstChangeset')
            ->with(
                $artifact,
                $data,
                $this->john_doe,
                Mockery::any(),
                false,
                Mockery::any(),
                $this->import_config
            )
            ->andReturn($changeset_1)
            ->once();

        $this->new_changeset_creator
            ->shouldReceive('create')
            ->withArgs(function (NewChangeset $new_changeset, PostCreationContext $context) use ($artifact) {
                if ($new_changeset->getArtifact() !== $artifact) {
                    return false;
                }
                $first  = [$this->summary_field_id => 'Again'];
                $second = [$this->summary_field_id => 'Value'];
                if ($new_changeset->getFieldsData() !== $first && $new_changeset->getFieldsData() !== $second) {
                    return false;
                }
                if ($new_changeset->getSubmitter() !== $this->john_doe) {
                    return false;
                }
                if ($new_changeset->getUrlMapping() !== $this->url_mapping) {
                    return false;
                }
                if ($context->getImportConfig() !== $this->import_config) {
                    return false;
                }
                if ($context->shouldSendNotifications() !== false) {
                    return false;
                }
                return true;
            })
            ->twice()
            ->andReturn($changeset_2, $changeset_3);

        $this->artifact_creator->shouldReceive('createBare')->once()->andReturn($artifact);

        $this->formelement_factory->shouldReceive('getUsedFieldByName')->withArgs([$this->tracker_id, 'summary'])->andReturn($this->tracker_formelement_field_string);

        $this->formelement_factory->shouldReceive('getFormElementByName')->andReturn([]);


        $this->external_field_extractor->shouldReceive('extractExternalFieldsFromArtifact')->once();

        $this->importer->importFromXML(
            $this->tracker,
            $xml_input,
            $this->extraction_path,
            $this->xml_mapping,
            $this->url_mapping,
            $this->import_config
        );
    }

    public function testImportChangesetWithPrivateCommentAndUpdateCommentInNewArtifact(): void
    {
        $changeset_1 = $this->mockAChangeset($this->john_doe->getId(), strtotime("2014-01-15T10:38:06+01:00"), null, null, null, $this->tracker_id, "summary", 'OK', 0);
        $changeset_2 = $this->mockAChangeset($this->john_doe->getId(), strtotime("2014-01-15T10:38:06+01:00"), null, null, null, $this->tracker_id, "summary", 'Again', 1);

        $artifact = $this->mockAnArtifact(101, $this->tracker, $this->tracker_id, []);

        $xml_field_mapping = file_get_contents(__DIR__ . '/_fixtures/testImportChangesetWithPrivateCommentInNewArtifact.xml');
        $xml_input         = simplexml_load_string($xml_field_mapping);

        $this->artifact_creator
            ->shouldReceive('createFirstChangeset')
            ->with(
                $artifact,
                [$this->summary_field_id => 'OK'],
                $this->john_doe,
                Mockery::any(),
                false,
                Mockery::any(),
                $this->import_config
            )
            ->andReturn($changeset_1)
            ->once();

        $ugroup_2 = Mockery::mock(\ProjectUGroup::class);

        $this->private_comment_extractor
            ->shouldReceive("extractUGroupsFromXML")
            ->with(
                $artifact,
                Mockery::on(
                    function (\SimpleXMLElement $comment): bool {
                        return (string) $comment->body === "My First Comment" &&
                            (string) $comment->private_ugroups->ugroup[0] === "my_group";
                    }
                )
            )
            ->once()
            ->andReturn([$ugroup_2]);

        $this->new_changeset_creator
            ->shouldReceive('create')
            ->withArgs(function (NewChangeset $new_changeset, PostCreationContext $context) use ($artifact, $ugroup_2) {
                if ($new_changeset->getArtifact() !== $artifact) {
                    return false;
                }
                $first = [$this->summary_field_id => 'Again'];
                if ($new_changeset->getFieldsData() !== $first) {
                    return false;
                }
                if ($new_changeset->getSubmitter() !== $this->john_doe) {
                    return false;
                }
                if ($new_changeset->getUrlMapping() !== $this->url_mapping) {
                    return false;
                }
                if ($context->getImportConfig() !== $this->import_config) {
                    return false;
                }
                if ($context->shouldSendNotifications() !== false) {
                    return false;
                }
                $comment = $new_changeset->getComment();
                if ($comment->getUserGroupsThatAreAllowedToSee() !== [$ugroup_2]) {
                    return false;
                }
                return true;
            })
            ->once()
            ->andReturn($changeset_2);

        $ugroup_3 = Mockery::mock(\ProjectUGroup::class);

        $this->private_comment_extractor
            ->shouldReceive("extractUGroupsFromXML")
            ->with($artifact, Mockery::on(
                function (\SimpleXMLElement $comment): bool {
                    return (string) $comment->body === "My Second Comment" &&
                           (string) $comment->private_ugroups->ugroup[0] === "my_other_group";
                }
            ))
            ->once()
            ->andReturn([$ugroup_3]);

        $changeset_2
            ->shouldReceive('getArtifact')
            ->once()
            ->andReturn($artifact);

        $changeset_2
            ->shouldReceive('updateCommentWithoutNotification')
            ->once()
            ->with(
                'My Second Comment',
                Mockery::any(),
                'text',
                1389778686,
                [$ugroup_3]
            );

        $this->artifact_creator->shouldReceive('createBare')->once()->andReturn($artifact);

        $this->formelement_factory->shouldReceive('getUsedFieldByName')->withArgs([$this->tracker_id, 'summary'])->andReturn($this->tracker_formelement_field_string);

        $this->formelement_factory->shouldReceive('getFormElementByName')->andReturn([]);

        $this->external_field_extractor->shouldReceive('extractExternalFieldsFromArtifact')->once();

        $this->importer->importFromXML(
            $this->tracker,
            $xml_input,
            $this->extraction_path,
            $this->xml_mapping,
            $this->url_mapping,
            $this->import_config
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
     *
     * @return Artifact
     */
    private function mockAnArtifact($id, $tracker, $tracker_id, $changeset = [])
    {
        $artifact = Mockery::mock(Artifact::class);
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
