<?php
/**
 * Copyright (c) Enalean, 2014 - Present. All Rights Reserved.
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

namespace Tuleap\Tracker\Artifact;

use ColinODell\PsrTestLogger\TestLogger;
use DateTimeImmutable;
use Exception;
use PFUser;
use PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\MockObject\Stub;
use Psr\Log\NullLogger;
use SimpleXMLElement;
use TestHelper;
use Tracker_Artifact_Changeset;
use Tracker_Artifact_XMLImport;
use Tracker_Artifact_XMLImport_XMLImportZipArchive;
use Tracker_FormElement_Field_MultiSelectbox;
use Tracker_FormElement_Field_OpenList;
use Tracker_FormElement_Field_PermissionsOnArtifact;
use Tracker_FormElementFactory;
use Tracker_XML_Importer_ArtifactImportedMapping;
use TrackerXmlFieldsMapping_InSamePlatform;
use Tuleap\DB\DBConnection;
use Tuleap\GlobalLanguageMock;
use Tuleap\GlobalResponseMock;
use Tuleap\Project\XML\Import\ExternalFieldsExtractor;
use Tuleap\TemporaryTestDirectory;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\ProjectUGroupTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Artifact\Changeset\Comment\CommentFormatIdentifier;
use Tuleap\Tracker\Artifact\Changeset\Comment\PrivateComment\XMLImport\TrackerPrivateCommentUGroupExtractor;
use Tuleap\Tracker\Artifact\Changeset\NewChangeset;
use Tuleap\Tracker\Artifact\Changeset\NewChangesetCreator;
use Tuleap\Tracker\Artifact\ChangesetValue\InitialChangesetValuesContainer;
use Tuleap\Tracker\Artifact\Creation\TrackerArtifactCreator;
use Tuleap\Tracker\Artifact\XMLImport\MoveImportConfig;
use Tuleap\Tracker\Artifact\XMLImport\TrackerImportConfig;
use Tuleap\Tracker\Artifact\XMLImport\TrackerXmlImportConfig;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\Type\TypeDao;
use Tuleap\Tracker\FormElement\Field\File\CreatedFileURLMapping;
use Tuleap\Tracker\FormElement\Field\ListFields\Bind\BindStaticValueDao;
use Tuleap\Tracker\FormElement\Field\String\StringField;
use Tuleap\Tracker\Test\Builders\ArtifactTestBuilder;
use Tuleap\Tracker\Test\Builders\ChangesetTestBuilder;
use Tuleap\Tracker\Test\Builders\Fields\ArtifactLinkFieldBuilder;
use Tuleap\Tracker\Test\Builders\Fields\DateFieldBuilder;
use Tuleap\Tracker\Test\Builders\Fields\FileFieldBuilder;
use Tuleap\Tracker\Test\Builders\Fields\FloatFieldBuilder;
use Tuleap\Tracker\Test\Builders\Fields\IntegerFieldBuilder;
use Tuleap\Tracker\Test\Builders\Fields\StringFieldBuilder;
use Tuleap\Tracker\Test\Builders\Fields\TextFieldBuilder;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;
use Tuleap\Tracker\Tracker;
use Tuleap\Tracker\XML\Importer\ImportedChangesetMapping;
use User\XML\Import\IFindUserFromXMLReference;
use UserManager;
use Workflow;
use XML_RNGValidator;
use XMLImportHelper;

#[DisableReturnValueGenerationForTestDoubles]
final class Tracker_Artifact_XMLImportTest extends TestCase // phpcs:ignore Squiz.Classes.ValidClassName.NotCamelCaps
{
    use GlobalLanguageMock;
    use TemporaryTestDirectory;
    use GlobalResponseMock;

    private const TRACKER_ID = 12;

    private TrackerXmlImportConfig $tracker_xml_config;
    private Tracker $tracker;
    private Tracker_Artifact_XMLImport $importer;
    private TrackerArtifactCreator&MockObject $artifact_creator;
    private NewChangesetCreator&MockObject $new_changeset_creator;
    private Tracker_FormElementFactory&MockObject $formelement_factory;
    private UserManager&MockObject $user_manager;
    private XMLImportHelper $xml_import_helper;
    private Artifact $artifact;
    private BindStaticValueDao&MockObject $static_value_dao;
    private TestLogger $logger;
    private XML_RNGValidator&Stub $rng_validator;
    private string $extraction_path;
    private PFUser $john_doe;
    private CreatedFileURLMapping&Stub $url_mapping;
    private ExternalFieldsExtractor&Stub $external_field_extractor;
    private TrackerPrivateCommentUGroupExtractor&MockObject $private_comment_extractor;
    private int $summary_field_id;
    private DBConnection&MockObject $db_connection;

    protected function setUp(): void
    {
        $workflow = $this->createStub(Workflow::class);
        $workflow->method('disable');
        $this->tracker = TrackerTestBuilder::aTracker()->withId(self::TRACKER_ID)
            ->withProject(ProjectTestBuilder::aProject()->withId(101)->build())
            ->withWorkflow($workflow)
            ->build();

        $this->tracker_xml_config = new TrackerXmlImportConfig(
            UserTestBuilder::anActiveUser()->build(),
            new DateTimeImmutable(),
            MoveImportConfig::buildForRegularImport(),
            false
        );

        $this->artifact_creator      = $this->createMock(TrackerArtifactCreator::class);
        $this->new_changeset_creator = $this->createMock(NewChangesetCreator::class);

        $this->summary_field_id    = 50;
        $this->formelement_factory = $this->createMock(Tracker_FormElementFactory::class);

        $this->john_doe     = UserTestBuilder::anActiveUser()->withId(200)->withUserName('john_doe')->build();
        $this->user_manager = $this->createMock(UserManager::class);
        $this->user_manager->method('getUserAnonymous')->willReturn(UserTestBuilder::anAnonymousUser()->build());

        $this->xml_import_helper = new XMLImportHelper($this->user_manager);

        $this->artifact = ArtifactTestBuilder::anArtifact(101)->inTracker($this->tracker)->build();

        $this->extraction_path = $this->getTmpDir();

        $this->static_value_dao = $this->createMock(BindStaticValueDao::class);

        $this->db_connection = $this->createMock(DBConnection::class);
        $this->db_connection->method('reconnectAfterALongRunningProcess');

        $this->logger = new TestLogger();

        $this->rng_validator = $this->createStub(XML_RNGValidator::class);
        $this->rng_validator->method('validate');

        $this->url_mapping = $this->createStub(CreatedFileURLMapping::class);

        $this->external_field_extractor  = $this->createStub(ExternalFieldsExtractor::class);
        $this->private_comment_extractor = $this->createMock(TrackerPrivateCommentUGroupExtractor::class);
        $this->importer                  = new Tracker_Artifact_XMLImport(
            $this->rng_validator,
            $this->artifact_creator,
            $this->new_changeset_creator,
            $this->formelement_factory,
            $this->xml_import_helper,
            $this->static_value_dao,
            $this->logger,
            false,
            $this->createStub(TypeDao::class),
            $this->external_field_extractor,
            $this->private_comment_extractor,
            $this->db_connection,
        );
        $this->external_field_extractor->method('extractExternalFieldsFromArtifact');
    }

    public function testItCallsImportFromXMLWithContentFromArchive(): void
    {
        $archive = $this->createMock(Tracker_Artifact_XMLImport_XMLImportZipArchive::class);
        $archive->method('getXML')->willReturn('<?xml version="1.0"?><artifacts />');
        $archive->expects($this->once())->method('extractFiles');
        $archive->method('getExtractionPath')->willReturn($this->extraction_path);
        $archive->expects($this->once())->method('cleanUp');

        $importer = $this->getMockBuilder(Tracker_Artifact_XMLImport::class)
            ->setConstructorArgs([
                $this->rng_validator,
                $this->artifact_creator,
                $this->new_changeset_creator,
                $this->formelement_factory,
                $this->xml_import_helper,
                $this->static_value_dao,
                $this->logger,
                false,
                $this->createStub(TypeDao::class),
                $this->external_field_extractor,
                $this->private_comment_extractor,
                $this->db_connection,
            ])->onlyMethods(['importFromXML'])->getMock();
        $importer->expects($this->once())->method('importFromXML')
            ->with(
                $this->tracker,
                self::isInstanceOf(SimpleXMLElement::class),
                $this->extraction_path,
                self::isInstanceOf(TrackerXmlFieldsMapping_InSamePlatform::class),
                self::isInstanceOf(CreatedFileURLMapping::class),
                self::isInstanceOf(TrackerXmlImportConfig::class),
            );

        $importer->importFromArchive($this->tracker, $archive, UserTestBuilder::buildWithId(1));
    }

    public function testItCreatesArtifactOnTracker(): void
    {
        $this->user_manager->method('getUserByIdentifier')->with('john_doe')->willReturn($this->john_doe);
        $this->artifact_creator->expects($this->once())->method('createBare')
            ->with($this->tracker)->willReturn(ArtifactTestBuilder::anArtifact(41864)->inTracker($this->tracker)->build());

        $this->importer->importFromXML(
            $this->tracker,
            $this->buildValidXMLElement(),
            $this->extraction_path,
            new TrackerXmlFieldsMapping_InSamePlatform(),
            $this->url_mapping,
            $this->tracker_xml_config
        );
    }

    public function testItCreatesArtifactWithSummaryFieldData(): void
    {
        $data = [$this->summary_field_id => 'Ça marche'];

        $this->user_manager->method('getUserByIdentifier')->with('john_doe')->willReturn($this->john_doe);
        $this->artifact_creator->expects($this->once())->method('createBare')
            ->with($this->tracker)->willReturn(ArtifactTestBuilder::anArtifact(41864)->inTracker($this->tracker)->build());

        $this->artifact_creator->expects($this->once())->method('createFirstChangeset')
            ->willReturnCallback(
                fn(
                    Artifact $artifact,
                    InitialChangesetValuesContainer $changeset_values,
                    PFUser $user,
                    int $submitted_on,
                    bool $send_notification,
                    CreatedFileURLMapping $url_mapping,
                    TrackerImportConfig $tracker_import_config,
                ): ?Tracker_Artifact_Changeset => match (true) {
                    $changeset_values->getFieldsData() === $data && $send_notification === false => ChangesetTestBuilder::aChangeset(16846)->build()
                }
            );

        $summary_field = StringFieldBuilder::aStringField($this->summary_field_id)->inTracker($this->tracker)->withName('summary')
            ->withSpecificProperty('maxchars', ['name' => 'maxchars', 'type' => 'string', 'value' => '0'])->thatIsRequired()->build();
        $this->formelement_factory->method('getUsedFieldByName')->with(self::TRACKER_ID, 'summary')->willReturn($summary_field);

        $this->importer->importFromXML(
            $this->tracker,
            $this->buildValidXMLElement(),
            $this->extraction_path,
            new TrackerXmlFieldsMapping_InSamePlatform(),
            $this->url_mapping,
            $this->tracker_xml_config
        );
    }

    public function testItCreatedArtifactWithSubmitter(): void
    {
        $this->user_manager->method('getUserByIdentifier')->with('john_doe')->willReturn($this->john_doe);
        $this->artifact_creator->expects($this->once())->method('createBare')
            ->with($this->tracker)->willReturn(ArtifactTestBuilder::anArtifact(41864)->inTracker($this->tracker)->build());

        $this->importer->importFromXML(
            $this->tracker,
            $this->buildValidXMLElement(),
            $this->extraction_path,
            new TrackerXmlFieldsMapping_InSamePlatform(),
            $this->url_mapping,
            $this->tracker_xml_config
        );
    }

    public function testItCreatesArtifactAtDate(): void
    {
        $this->user_manager->method('getUserByIdentifier')->with('john_doe')->willReturn($this->john_doe);
        $this->artifact_creator->expects($this->once())->method('createBare')
            ->with($this->tracker, self::anything(), strtotime('2014-01-15T10:38:06+01:00'))
            ->willReturn(ArtifactTestBuilder::anArtifact(41864)->inTracker($this->tracker)->build());

        $this->importer->importFromXML(
            $this->tracker,
            $this->buildValidXMLElement(),
            $this->extraction_path,
            new TrackerXmlFieldsMapping_InSamePlatform(),
            $this->url_mapping,
            $this->tracker_xml_config
        );
    }

    private function buildValidXMLElement(): SimpleXMLElement
    {
        return new SimpleXMLElement(
            '<?xml version="1.0"?>
            <artifacts>
              <artifact id="4918">
                <changeset>
                  <submitted_by format="username">john_doe</submitted_by>
                  <submitted_on format="ISO8601">2014-01-15T10:38:06+01:00</submitted_on>
                  <field_change field_name="summary" type="string">
                    <value>Ça marche</value>
                  </field_change>
                </changeset>
              </artifact>
            </artifacts>'
        );
    }

    public function testItCreatesTheComments(): void
    {
        $this->user_manager->method('getUserByIdentifier')->with('john_doe')->willReturn($this->john_doe);
        $this->artifact_creator->expects($this->once())->method('createFirstChangeset')
            ->willReturnCallback(
                fn(
                    Artifact $artifact,
                    InitialChangesetValuesContainer $changeset_values,
                    PFUser $user,
                    int $submitted_on,
                    bool $send_notification,
                    CreatedFileURLMapping $url_mapping,
                    TrackerImportConfig $tracker_import_config,
                ): ?Tracker_Artifact_Changeset => match (true) {
                    $send_notification === false => ChangesetTestBuilder::aChangeset(259)->build()
                }
            );
        $this->formelement_factory->method('getUsedFieldByName')->willReturn(null);
        $this->private_comment_extractor->method('extractUGroupsFromXML')->willReturn([]);

        $this->new_changeset_creator->expects($this->exactly(2))->method('create')
            ->with(self::callback(function (NewChangeset $new_changeset) {
                $comment = $new_changeset->getComment();
                if ($comment->getBody() === 'Some text' && $comment->getFormat() === CommentFormatIdentifier::TEXT) {
                    return true;
                }
                if ($comment->getBody() === '<p>Some text</p>' && $comment->getFormat() === CommentFormatIdentifier::HTML) {
                    return true;
                }
                return false;
            }))
            ->willReturn(ChangesetTestBuilder::aChangeset(45256)->build());

        $this->artifact_creator->method('create')->willReturn($this->artifact);
        $this->artifact_creator->method('createBare')->willReturn($this->artifact);

        $xml_element = new SimpleXMLElement('<?xml version="1.0"?>
            <artifacts>
              <artifact id="4918">
                <changeset>
                  <submitted_by format="username">john_doe</submitted_by>
                  <submitted_on format="ISO8601">2014-01-15T10:38:06+01:00</submitted_on>
                  <comments/>
                  <field_change field_name="summary" type="string">
                    <value>Ça marche</value>
                  </field_change>
                </changeset>
                <changeset>
                  <submitted_by format="username">john_doe</submitted_by>
                  <submitted_on format="ISO8601">2014-01-15T11:03:50+01:00</submitted_on>
                  <comments>
                    <comment>
                      <submitted_by format="username">john_doe</submitted_by>
                      <submitted_on format="ISO8601">2014-01-15T11:03:50+01:00</submitted_on>
                      <body format="text">Some text</body>
                    </comment>
                  </comments>
                </changeset>
                <changeset>
                  <submitted_by format="username">john_doe</submitted_by>
                  <submitted_on format="ISO8601">2014-01-15T11:23:50+01:00</submitted_on>
                  <comments>
                    <comment>
                      <submitted_by format="username">john_doe</submitted_by>
                      <submitted_on format="ISO8601">2014-01-15T11:23:50+01:00</submitted_on>
                      <body format="html">&lt;p&gt;Some text&lt;/p&gt;</body>
                    </comment>
                  </comments>
                </changeset>
              </artifact>
            </artifacts>');

        $this->importer->importFromXML(
            $this->tracker,
            $xml_element,
            $this->extraction_path,
            new TrackerXmlFieldsMapping_InSamePlatform(),
            $this->url_mapping,
            $this->tracker_xml_config
        );
    }

    public function testItCreatesTheCommentsWithUpdates(): void
    {
        $this->artifact_creator->expects($this->once())->method('createFirstChangeset')
            ->willReturnCallback(
                fn(
                    Artifact $artifact,
                    InitialChangesetValuesContainer $changeset_values,
                    PFUser $user,
                    int $submitted_on,
                    bool $send_notification,
                    CreatedFileURLMapping $url_mapping,
                    TrackerImportConfig $tracker_import_config,
                ): ?Tracker_Artifact_Changeset => match (true) {
                    $send_notification === false => ChangesetTestBuilder::aChangeset(259)->build()
                }
            );

        $this->artifact_creator->method('create')->willReturn($this->artifact);
        $this->artifact_creator->method('createBare')->willReturn($this->artifact);

        $changeset = $this->createMock(Tracker_Artifact_Changeset::class);

        $this->new_changeset_creator->expects($this->once())->method('create')
            ->with(self::callback(function (NewChangeset $new_changeset) {
                $comment = $new_changeset->getComment();
                if ($comment->getBody() !== 'Some text') {
                    return false;
                }
                if ($comment->getFormat() !== CommentFormatIdentifier::TEXT) {
                    return false;
                }
                return true;
            }))
            ->willReturn($changeset);

        $changeset->expects($this->once())->method('updateCommentWithoutNotification')->with('<p>Some text</p>', $this->john_doe, CommentFormatIdentifier::HTML->value, strtotime('2014-01-15T11:23:50+01:00'), []);
        $changeset->expects($this->once())->method('getArtifact')->willReturn(ArtifactTestBuilder::anArtifact(1486)->build());

        $xml_element = new SimpleXMLElement('<?xml version="1.0"?>
            <artifacts>
              <artifact id="4918">
                <changeset>
                  <submitted_by format="username">john_doe</submitted_by>
                  <submitted_on format="ISO8601">2014-01-15T10:38:06+01:00</submitted_on>
                  <comments/>
                  <field_change field_name="summary" type="string">
                    <value>Ça marche</value>
                  </field_change>
                </changeset>
                <changeset>
                  <submitted_by format="username">john_doe</submitted_by>
                  <submitted_on format="ISO8601">2014-01-15T11:03:50+01:00</submitted_on>
                  <comments>
                    <comment>
                      <submitted_by format="username">john_doe</submitted_by>
                      <submitted_on format="ISO8601">2014-01-15T11:03:50+01:00</submitted_on>
                      <body format="text">Some text</body>
                    </comment>
                    <comment>
                      <submitted_by format="username">john_doe</submitted_by>
                      <submitted_on format="ISO8601">2014-01-15T11:23:50+01:00</submitted_on>
                      <body format="html">&lt;p&gt;Some text&lt;/p&gt;</body>
                    </comment>
                  </comments>
                </changeset>
              </artifact>
            </artifacts>');

        $this->user_manager->method('getUserByIdentifier')->with('john_doe')->willReturn($this->john_doe);
        $this->formelement_factory->method('getUsedFieldByName')->willReturn(null);
        $this->private_comment_extractor->method('extractUGroupsFromXML')->willReturn([]);

        $this->importer->importFromXML(
            $this->tracker,
            $xml_element,
            $this->extraction_path,
            new TrackerXmlFieldsMapping_InSamePlatform(),
            $this->url_mapping,
            $this->tracker_xml_config
        );
    }

    public function testItCreatesPrivateCommentWithUpdates(): void
    {
        $my_group       = ProjectUGroupTestBuilder::aCustomUserGroup(1)->build();
        $my_other_group = ProjectUGroupTestBuilder::aCustomUserGroup(2)->build();
        $my_best_group  = ProjectUGroupTestBuilder::aCustomUserGroup(3)->build();

        $this->private_comment_extractor->expects($this->exactly(2))->method('extractUGroupsFromXML')
            ->willReturnCallback(static fn(Artifact $artifact, SimpleXMLElement $comment) => match (true) {
                (string) $comment->body === 'Some text' &&
                (string) $comment->private_ugroups->ugroup[0] === 'my_group' &&
                (string) $comment->private_ugroups->ugroup[1] === 'my_other_group' => [$my_group, $my_other_group],
                (string) $comment->body === 'New comment update' &&
                (string) $comment->private_ugroups->ugroup[0] === 'my_group' &&
                (string) $comment->private_ugroups->ugroup[1] === 'the_best_group' => [$my_group, $my_best_group],
            });

        $this->artifact_creator->expects($this->once())->method('createFirstChangeset')
            ->willReturnCallback(
                fn(
                    Artifact $artifact,
                    InitialChangesetValuesContainer $changeset_values,
                    PFUser $user,
                    int $submitted_on,
                    bool $send_notification,
                    CreatedFileURLMapping $url_mapping,
                    TrackerImportConfig $tracker_import_config,
                ): ?Tracker_Artifact_Changeset => match (true) {
                    $send_notification === false => ChangesetTestBuilder::aChangeset(259)->build()
                }
            );

        $this->artifact_creator->method('create')->willReturn($this->artifact);
        $this->artifact_creator->method('createBare')->willReturn($this->artifact);

        $changeset = $this->createMock(Tracker_Artifact_Changeset::class);

        $this->new_changeset_creator->expects($this->once())->method('create')
            ->with(self::callback(function (NewChangeset $new_changeset) use ($my_other_group, $my_group) {
                $comment = $new_changeset->getComment();
                if ($comment->getBody() !== 'Some text') {
                    return false;
                }
                if ($comment->getFormat() !== CommentFormatIdentifier::TEXT) {
                    return false;
                }
                if ($comment->getUserGroupsThatAreAllowedToSee() !== [$my_group, $my_other_group]) {
                    return false;
                }
                return true;
            }))
            ->willReturn($changeset);

        $changeset->expects($this->once())->method('updateCommentWithoutNotification')
            ->with(
                'New comment update',
                $this->john_doe,
                CommentFormatIdentifier::HTML->value,
                strtotime('2014-01-15T11:23:50+01:00'),
                [$my_group, $my_best_group]
            );
        $changeset->expects($this->once())->method('getArtifact')->willReturn(ArtifactTestBuilder::anArtifact(2465)->build());

        $xml_element = new SimpleXMLElement('<?xml version="1.0"?>
            <artifacts>
              <artifact id="4918">
                <changeset>
                  <submitted_by format="username">john_doe</submitted_by>
                  <submitted_on format="ISO8601">2014-01-15T10:38:06+01:00</submitted_on>
                  <comments/>
                  <field_change field_name="summary" type="string">
                    <value>Ça marche</value>
                  </field_change>
                </changeset>
                <changeset>
                  <submitted_by format="username">john_doe</submitted_by>
                  <submitted_on format="ISO8601">2014-01-15T11:03:50+01:00</submitted_on>
                  <comments>
                    <comment>
                      <submitted_by format="username">john_doe</submitted_by>
                      <submitted_on format="ISO8601">2014-01-15T11:03:50+01:00</submitted_on>
                      <body format="text">Some text</body>
                      <private_ugroups>
                        <ugroup>my_group</ugroup>
                        <ugroup>my_other_group</ugroup>
                      </private_ugroups>
                    </comment>
                    <comment>
                      <submitted_by format="username">john_doe</submitted_by>
                      <submitted_on format="ISO8601">2014-01-15T11:23:50+01:00</submitted_on>
                      <body format="html">New comment update</body>
                      <private_ugroups>
                        <ugroup>my_group</ugroup>
                        <ugroup>the_best_group</ugroup>
                      </private_ugroups>
                    </comment>
                  </comments>
                </changeset>
              </artifact>
            </artifacts>');

        $this->user_manager->method('getUserByIdentifier')->with('john_doe')->willReturn($this->john_doe);
        $this->formelement_factory->method('getUsedFieldByName')->willReturn(null);

        $this->importer->importFromXML(
            $this->tracker,
            $xml_element,
            $this->extraction_path,
            new TrackerXmlFieldsMapping_InSamePlatform(),
            $this->url_mapping,
            $this->tracker_xml_config
        );
    }

    public function testItCreatesChangesetAsAnonymousWhenUserDoesntExists(): void
    {
        $xml_element = new SimpleXMLElement('<?xml version="1.0"?>
            <artifacts>
              <artifact id="4918">
                <changeset>
                  <submitted_by format="username">jmalko</submitted_by>
                  <submitted_on format="ISO8601">2014-01-15T10:38:06+01:00</submitted_on>
                  <field_change field_name="summary" type="string">
                    <value>Ça marche</value>
                  </field_change>
                </changeset>
              </artifact>
            </artifacts>');

        $this->artifact_creator->expects($this->once())->method('createBare')
            ->willReturnCallback(
                fn(Tracker $tracker, PFUser $user, int $submitted_on): ?Artifact => match (true) {
                    $user->isAnonymous() && $user->getEmail() === 'jmalko' => $this->artifact
                }
            );
        $this->user_manager->method('getUserByIdentifier')->with('jmalko')
            ->willReturn(UserTestBuilder::anAnonymousUser()->withEmail('jmalko')->build());
        $this->formelement_factory->method('getUsedFieldByName')->willReturn(null);
        $this->private_comment_extractor->method('extractUGroupsFromXML')->willReturn([]);

        $this->importer->importFromXML(
            $this->tracker,
            $xml_element,
            $this->extraction_path,
            new TrackerXmlFieldsMapping_InSamePlatform(),
            $this->url_mapping,
            $this->tracker_xml_config
        );
    }

    public function testItLooksForUserIdWhenFormatIsId(): void
    {
        $xml_element = new SimpleXMLElement('<?xml version="1.0"?>
            <artifacts>
              <artifact id="4918">
                <changeset>
                  <submitted_by format="id">700</submitted_by>
                  <submitted_on format="ISO8601">2014-01-15T10:38:06+01:00</submitted_on>
                  <field_change field_name="summary" type="string">
                    <value>Ça marche</value>
                  </field_change>
                </changeset>
              </artifact>
            </artifacts>');

        $this->user_manager->expects($this->atLeastOnce())->method('getUserByIdentifier')->with('id:700')->willReturn($this->john_doe);

        $this->artifact_creator->expects($this->once())->method('createBare')
            ->with($this->tracker, $this->john_doe)->willReturn($this->artifact);

        $this->importer->importFromXML(
            $this->tracker,
            $xml_element,
            $this->extraction_path,
            new TrackerXmlFieldsMapping_InSamePlatform(),
            $this->url_mapping,
            $this->tracker_xml_config
        );
    }

    public function testItLooksForLdapIdWhenFormatIsLdap(): void
    {
        $xml_element = new SimpleXMLElement('<?xml version="1.0"?>
            <artifacts>
              <artifact id="4918">
                <changeset>
                  <submitted_by format="ldap">uid=jo,ou=people,dc=example,dc=com</submitted_by>
                  <submitted_on format="ISO8601">2014-01-15T10:38:06+01:00</submitted_on>
                  <field_change field_name="summary" type="string">
                    <value>Ça marche</value>
                  </field_change>
                </changeset>
              </artifact>
            </artifacts>');

        $this->user_manager->expects($this->atLeastOnce())->method('getUserByIdentifier')
            ->with('ldapId:uid=jo,ou=people,dc=example,dc=com')
            ->willReturn($this->john_doe);

        $this->artifact_creator->expects($this->once())->method('createBare')
            ->with($this->tracker, $this->john_doe)->willReturn($this->artifact);

        $this->importer->importFromXML(
            $this->tracker,
            $xml_element,
            $this->extraction_path,
            new TrackerXmlFieldsMapping_InSamePlatform(),
            $this->url_mapping,
            $this->tracker_xml_config
        );
    }

    public function testItLooksForEmailWhenFormatIsEmail(): void
    {
        $xml_element = new SimpleXMLElement('<?xml version="1.0"?>
            <artifacts>
              <artifact id="4918">
                <changeset>
                  <submitted_by format="email" is_anonymous="1">jo@example.com</submitted_by>
                  <submitted_on format="ISO8601">2014-01-15T10:38:06+01:00</submitted_on>
                  <field_change field_name="summary" type="string">
                    <value>Ça marche</value>
                  </field_change>
                </changeset>
              </artifact>
            </artifacts>');

        $this->user_manager->expects($this->atLeastOnce())->method('getUserByIdentifier')->with('email:jo@example.com')->willReturn($this->john_doe);

        $this->artifact_creator->expects($this->once())->method('createBare')
            ->with($this->tracker, $this->john_doe)->willReturn($this->artifact);

        $this->importer->importFromXML(
            $this->tracker,
            $xml_element,
            $this->extraction_path,
            new TrackerXmlFieldsMapping_InSamePlatform(),
            $this->url_mapping,
            $this->tracker_xml_config
        );
    }

    private function buildMultipleChangesetXMLElement(): SimpleXMLElement
    {
        return new SimpleXMLElement('<?xml version="1.0"?>
            <artifacts>
              <artifact id="4918">
                <changeset>
                  <submitted_by format="username">john_doe</submitted_by>
                  <submitted_on format="ISO8601">2014-01-15T10:38:06+01:00</submitted_on>
                  <field_change field_name="summary" type="string">
                    <value>Ça marche</value>
                  </field_change>
                </changeset>
                <changeset>
                  <submitted_by format="username">john_doe</submitted_by>
                  <submitted_on format="ISO8601">2014-01-15T11:03:50+01:00</submitted_on>
                  <field_change field_name="summary" type="string">
                    <value>^Wit updates</value>
                  </field_change>
                </changeset>
              </artifact>
            </artifacts>');
    }

    public function testItCreatesTwoChangesets(): void
    {
        $this->artifact_creator->expects($this->once())->method('createBare')->willReturn($this->artifact);

        $this->artifact_creator->expects($this->once())->method('createFirstChangeset')
            ->willReturn(ChangesetTestBuilder::aChangeset(41654)->build());

        $this->artifact_creator->method('create')->willReturn($this->artifact);

        $this->new_changeset_creator->expects($this->once())->method('create');
        $this->user_manager->method('getUserByIdentifier')->with('john_doe')->willReturn($this->john_doe);
        $this->formelement_factory->method('getUsedFieldByName')->willReturn(null);
        $this->private_comment_extractor->method('extractUGroupsFromXML')->willReturn([]);

        $this->importer->importFromXML(
            $this->tracker,
            $this->buildMultipleChangesetXMLElement(),
            $this->extraction_path,
            new TrackerXmlFieldsMapping_InSamePlatform(),
            $this->url_mapping,
            $this->tracker_xml_config
        );
    }

    public function testItCreatesTheNewChangesetWithSummaryValue(): void
    {
        $data = [
            $this->summary_field_id => '^Wit updates',
        ];

        $this->artifact_creator->expects($this->once())->method('createBare')->willReturn($this->artifact);

        $this->artifact_creator->expects($this->once())->method('createFirstChangeset')
            ->willReturnCallback(
                fn(
                    Artifact $artifact,
                    InitialChangesetValuesContainer $changeset_values,
                    PFUser $user,
                    int $submitted_on,
                    bool $send_notification,
                    CreatedFileURLMapping $url_mapping,
                    TrackerImportConfig $tracker_import_config,
                ): ?Tracker_Artifact_Changeset => match (true) {
                    $send_notification === false => ChangesetTestBuilder::aChangeset(259)->build()
                }
            );

        $this->artifact_creator->method('create')->willReturn($this->artifact);

        $this->new_changeset_creator->expects($this->once())->method('create')
            ->with(self::callback(function (NewChangeset $new_changeset) use ($data) {
                if ($new_changeset->getFieldsData() !== $data) {
                    return false;
                }
                return true;
            }))
            ->willReturn(ChangesetTestBuilder::aChangeset(486248)->build());
        $summary_field = StringFieldBuilder::aStringField($this->summary_field_id)->inTracker($this->tracker)->withName('summary')
            ->withSpecificProperty('maxchars', ['name' => 'maxchars', 'type' => 'string', 'value' => '0'])->thatIsRequired()->build();
        $this->formelement_factory->method('getUsedFieldByName')->with(self::TRACKER_ID, 'summary')->willReturn($summary_field);
        $this->user_manager->method('getUserByIdentifier')->with('john_doe')->willReturn($this->john_doe);
        $this->private_comment_extractor->method('extractUGroupsFromXML')->willReturn([]);

        $this->importer->importFromXML(
            $this->tracker,
            $this->buildMultipleChangesetXMLElement(),
            $this->extraction_path,
            new TrackerXmlFieldsMapping_InSamePlatform(),
            $this->url_mapping,
            $this->tracker_xml_config
        );
    }

    public function testItCreatesTheNewChangesetWithSubmitter(): void
    {
        $this->artifact_creator->expects($this->once())->method('createBare')->willReturn($this->artifact);

        $this->artifact_creator->expects($this->once())->method('createFirstChangeset')
            ->willReturnCallback(
                fn(
                    Artifact $artifact,
                    InitialChangesetValuesContainer $changeset_values,
                    PFUser $user,
                    int $submitted_on,
                    bool $send_notification,
                    CreatedFileURLMapping $url_mapping,
                    TrackerImportConfig $tracker_import_config,
                ): ?Tracker_Artifact_Changeset => match (true) {
                    $send_notification === false => ChangesetTestBuilder::aChangeset(259)->build()
                }
            );

        $this->artifact_creator->method('create')->willReturn($this->artifact);

        $this->new_changeset_creator->expects($this->once())->method('create')
            ->with(self::callback(function (NewChangeset $new_changeset) {
                if ($new_changeset->getSubmitter() !== $this->john_doe) {
                    return false;
                }
                return true;
            }))
            ->willReturn(ChangesetTestBuilder::aChangeset(26884)->build());

        $this->user_manager->method('getUserByIdentifier')->with('john_doe')->willReturn($this->john_doe);
        $this->formelement_factory->method('getUsedFieldByName')->willReturn(null);
        $this->private_comment_extractor->method('extractUGroupsFromXML')->willReturn([]);

        $this->importer->importFromXML(
            $this->tracker,
            $this->buildMultipleChangesetXMLElement(),
            $this->extraction_path,
            new TrackerXmlFieldsMapping_InSamePlatform(),
            $this->url_mapping,
            $this->tracker_xml_config
        );
    }

    public function testItCreatesTheNewChangesetWithoutNotification(): void
    {
        $this->artifact_creator->expects($this->once())->method('createBare')->willReturn($this->artifact);

        $this->artifact_creator->expects($this->once())->method('createFirstChangeset')
            ->willReturnCallback(
                fn(
                    Artifact $artifact,
                    InitialChangesetValuesContainer $changeset_values,
                    PFUser $user,
                    int $submitted_on,
                    bool $send_notification,
                    CreatedFileURLMapping $url_mapping,
                    TrackerImportConfig $tracker_import_config,
                ): ?Tracker_Artifact_Changeset => match (true) {
                    $send_notification === false => ChangesetTestBuilder::aChangeset(259)->build()
                }
            );

        $this->artifact_creator->method('create')->willReturn($this->artifact);

        $this->new_changeset_creator->expects($this->once())->method('create')->willReturn(ChangesetTestBuilder::aChangeset(4265)->build());
        $this->user_manager->method('getUserByIdentifier')->with('john_doe')->willReturn($this->john_doe);
        $this->formelement_factory->method('getUsedFieldByName')->willReturn(null);
        $this->private_comment_extractor->method('extractUGroupsFromXML')->willReturn([]);

        $this->importer->importFromXML(
            $this->tracker,
            $this->buildMultipleChangesetXMLElement(),
            $this->extraction_path,
            new TrackerXmlFieldsMapping_InSamePlatform(),
            $this->url_mapping,
            $this->tracker_xml_config
        );
    }

    public function testItCreatesTheChangesetsAccordingToDates(): void
    {
        $this->artifact_creator->expects($this->once())->method('createBare')
            ->willReturnCallback(
                fn(Tracker $tracker, PFUser $user, int $submitted_on): ?Artifact => match (true) {
                    $tracker === $this->tracker && $submitted_on === strtotime('2014-01-15T10:38:06+01:00') => $this->artifact
                }
            );

        $this->artifact_creator->expects($this->once())->method('createFirstChangeset')
            ->willReturnCallback(
                fn(
                    Artifact $artifact,
                    InitialChangesetValuesContainer $changeset_values,
                    PFUser $user,
                    int $submitted_on,
                    bool $send_notification,
                    CreatedFileURLMapping $url_mapping,
                    TrackerImportConfig $tracker_import_config,
                ): ?Tracker_Artifact_Changeset => match (true) {
                    $submitted_on === strtotime('2014-01-15T10:38:06+01:00') && $send_notification === false => ChangesetTestBuilder::aChangeset(259)->build()
                }
            );

        $this->artifact_creator->method('create')->willReturn($this->artifact);

        $this->new_changeset_creator->expects($this->once())->method('create')
            ->with(self::callback(function (NewChangeset $new_changeset) {
                if ($new_changeset->getSubmissionTimestamp() !== strtotime('2014-01-15T11:03:50+01:00')) {
                    return false;
                }
                return true;
            }))
            ->willReturn(ChangesetTestBuilder::aChangeset(456)->build());
        $this->user_manager->method('getUserByIdentifier')->with('john_doe')->willReturn($this->john_doe);
        $this->formelement_factory->method('getUsedFieldByName')->willReturn(null);
        $this->private_comment_extractor->method('extractUGroupsFromXML')->willReturn([]);

        $this->importer->importFromXML(
            $this->tracker,
            $this->buildMultipleChangesetXMLElement(),
            $this->extraction_path,
            new TrackerXmlFieldsMapping_InSamePlatform(),
            $this->url_mapping,
            $this->tracker_xml_config
        );
    }

    public function testItCreatesTheChangesetsInAscendingDatesEvenWhenChangesetsAreMixedInXML(): void
    {
        $xml_element = new SimpleXMLElement('<?xml version="1.0"?>
            <artifacts>
              <artifact id="4918">
                <changeset>
                  <submitted_by format="username">john_doe</submitted_by>
                  <submitted_on format="ISO8601">2014-01-15T11:03:50+01:00</submitted_on>
                  <field_change field_name="summary" type="string">
                    <value>^Wit updates</value>
                  </field_change>
                </changeset>
                <changeset>
                  <submitted_by format="username">john_doe</submitted_by>
                  <submitted_on format="ISO8601">2014-01-15T10:38:06+01:00</submitted_on>
                  <field_change field_name="summary" type="string">
                    <value>Ça marche</value>
                  </field_change>
                </changeset>
              </artifact>
            </artifacts>');

        $this->artifact_creator->expects($this->once())->method('createBare')
            ->with($this->tracker, self::anything(), strtotime('2014-01-15T10:38:06+01:00'))
            ->willReturn($this->artifact);

        $this->artifact_creator->expects($this->once())->method('createFirstChangeset')
            ->willReturnCallback(
                fn(
                    Artifact $artifact,
                    InitialChangesetValuesContainer $changeset_values,
                    PFUser $user,
                    int $submitted_on,
                    bool $send_notification,
                    CreatedFileURLMapping $url_mapping,
                    TrackerImportConfig $tracker_import_config,
                ): ?Tracker_Artifact_Changeset => match (true) {
                    $submitted_on === strtotime('2014-01-15T10:38:06+01:00') && $send_notification === false => ChangesetTestBuilder::aChangeset(259)->build()
                }
            );

        $this->artifact_creator->method('create')->willReturn($this->artifact);

        $this->new_changeset_creator->expects($this->once())->method('create')
            ->with(self::callback(function (NewChangeset $new_changeset) {
                if ($new_changeset->getSubmissionTimestamp() !== strtotime('2014-01-15T11:03:50+01:00')) {
                    return false;
                }
                return true;
            }))
            ->willReturn(ChangesetTestBuilder::aChangeset(47258)->build());
        $this->user_manager->method('getUserByIdentifier')->with('john_doe')->willReturn($this->john_doe);
        $this->formelement_factory->method('getUsedFieldByName')->willReturn(null);
        $this->private_comment_extractor->method('extractUGroupsFromXML')->willReturn([]);

        $this->importer->importFromXML(
            $this->tracker,
            $xml_element,
            $this->extraction_path,
            new TrackerXmlFieldsMapping_InSamePlatform(),
            $this->url_mapping,
            $this->tracker_xml_config
        );
    }

    public function testItKeepsTheOriginalOrderWhenTwoDatesAreEqual(): void
    {
        $xml_element = new SimpleXMLElement('<?xml version="1.0"?>
            <artifacts>
              <artifact id="4918">
              <changeset>
                  <submitted_by format="username">john_doe</submitted_by>
                  <submitted_on format="ISO8601">2014-01-15T11:51:50+01:00</submitted_on>
                  <field_change field_name="summary" type="string">
                    <value>Fourth</value>
                  </field_change>
                </changeset>
               <changeset>
                  <submitted_by format="username">john_doe</submitted_by>
                  <submitted_on format="ISO8601">2014-01-15T11:03:50+01:00</submitted_on>
                  <field_change field_name="summary" type="string">
                    <value>Second</value>
                  </field_change>
                </changeset>
                <changeset>
                  <submitted_by format="username">john_doe</submitted_by>
                  <submitted_on format="ISO8601">2014-01-15T11:03:50+01:00</submitted_on>
                  <field_change field_name="summary" type="string">
                    <value>Third</value>
                  </field_change>
                </changeset>
                <changeset>
                  <submitted_by format="username">john_doe</submitted_by>
                  <submitted_on format="ISO8601">2014-01-15T10:38:06+01:00</submitted_on>
                  <field_change field_name="summary" type="string">
                    <value>First</value>
                  </field_change>
                </changeset>
              </artifact>
            </artifacts>');

        $this->artifact_creator->expects($this->once())->method('createBare')
            ->with($this->tracker, self::anything(), strtotime('2014-01-15T10:38:06+01:00'))
            ->willReturn($this->artifact);

        $this->artifact_creator->expects($this->once())->method('createFirstChangeset')
            ->willReturnCallback(
                fn(
                    Artifact $artifact,
                    InitialChangesetValuesContainer $changeset_values,
                    PFUser $user,
                    int $submitted_on,
                    bool $send_notification,
                    CreatedFileURLMapping $url_mapping,
                    TrackerImportConfig $tracker_import_config,
                ): ?Tracker_Artifact_Changeset => match (true) {
                    $changeset_values->getFieldsData()[$this->summary_field_id] === 'First' && $submitted_on === strtotime('2014-01-15T10:38:06+01:00') => ChangesetTestBuilder::aChangeset(46456)->build()
                }
            );

        $this->artifact_creator->method('create')->willReturn($this->artifact);

        $this->new_changeset_creator->expects($this->exactly(3))->method('create')
            ->with(self::callback(function (NewChangeset $new_changeset) {
                return in_array($new_changeset->getFieldsData()[$this->summary_field_id], ['Second', 'Third', 'Fourth'])
                       && $new_changeset->getSubmissionTimestamp() === strtotime('2014-01-15T11:51:50+01:00');
            }))
            ->willReturn(ChangesetTestBuilder::aChangeset(68765)->build());
        $summary_field = StringFieldBuilder::aStringField($this->summary_field_id)->inTracker($this->tracker)->withName('summary')
            ->withSpecificProperty('maxchars', ['name' => 'maxchars', 'type' => 'string', 'value' => '0'])->thatIsRequired()->build();
        $this->formelement_factory->method('getUsedFieldByName')->with(self::TRACKER_ID, 'summary')->willReturn($summary_field);
        $this->user_manager->method('getUserByIdentifier')->with('john_doe')->willReturn($this->john_doe);
        $this->private_comment_extractor->method('extractUGroupsFromXML')->willReturn([]);

        $this->importer->importFromXML(
            $this->tracker,
            $xml_element,
            $this->extraction_path,
            new TrackerXmlFieldsMapping_InSamePlatform(),
            $this->url_mapping,
            $this->tracker_xml_config
        );
    }

    public function testItCreatesTwoArtifactsOnTracker(): void
    {
        $xml_element = new SimpleXMLElement('<?xml version="1.0"?>
            <artifacts>
              <artifact id="4918">
                <changeset>
                  <submitted_by format="username">john_doe</submitted_by>
                  <submitted_on format="ISO8601">2014-01-15T10:38:06+01:00</submitted_on>
                  <field_change field_name="summary" type="string">
                    <value>Ça marche</value>
                  </field_change>
                </changeset>
              </artifact>
              <artifact id="4913">
                <changeset>
                  <submitted_by format="username">john_doe</submitted_by>
                  <submitted_on format="ISO8601">2014-01-16T11:38:06+01:00</submitted_on>
                  <field_change field_name="summary" type="string">
                    <value>Ça marche</value>
                  </field_change>
                </changeset>
              </artifact>
            </artifacts>');

        $this->artifact_creator->expects($this->exactly(2))->method('createBare')
            ->with(
                $this->tracker,
                self::anything(),
                self::callback(static fn(int $time) => in_array($time, [strtotime('2014-01-15T10:38:06+01:00'), strtotime('2014-01-16T11:38:06+01:00')])),
            )
            ->willReturn($this->artifact);
        $this->user_manager->method('getUserByIdentifier')->with('john_doe')->willReturn($this->john_doe);
        $this->formelement_factory->method('getUsedFieldByName')->willReturn(null);
        $this->private_comment_extractor->method('extractUGroupsFromXML')->willReturn([]);

        $this->importer->importFromXML(
            $this->tracker,
            $xml_element,
            $this->extraction_path,
            new TrackerXmlFieldsMapping_InSamePlatform(),
            $this->url_mapping,
            $this->tracker_xml_config
        );
    }

    public function testItForcesReconnectionToTheDbForEachImportedArtifactSoThatHugeImportDoesNotLoseConnection(): void
    {
        $xml_element = new SimpleXMLElement('<?xml version="1.0"?>
            <artifacts>
              <artifact id="4918">
                <changeset>
                  <submitted_by format="username">john_doe</submitted_by>
                  <submitted_on format="ISO8601">2014-01-15T10:38:06+01:00</submitted_on>
                  <field_change field_name="summary" type="string">
                    <value>Ça marche</value>
                  </field_change>
                </changeset>
              </artifact>
              <artifact id="4913">
                <changeset>
                  <submitted_by format="username">john_doe</submitted_by>
                  <submitted_on format="ISO8601">2014-01-16T11:38:06+01:00</submitted_on>
                  <field_change field_name="summary" type="string">
                    <value>Ça marche</value>
                  </field_change>
                </changeset>
              </artifact>
            </artifacts>');

        $this->artifact_creator->expects($this->exactly(2))->method('createBare')
            ->with($this->tracker)
            ->willReturnCallback(
                fn(Tracker $tracker, PFUser $user, int $submitted_on): ?Artifact => match ($submitted_on) {
                    strtotime('2014-01-15T10:38:06+01:00') => ArtifactTestBuilder::anArtifact(101)->inTracker($this->tracker)->build(),
                    strtotime('2014-01-16T11:38:06+01:00') => ArtifactTestBuilder::anArtifact(102)->inTracker($this->tracker)->build(),
                }
            );

        $this->db_connection->expects($this->exactly(2))->method('reconnectAfterALongRunningProcess');
        $this->user_manager->method('getUserByIdentifier')->with('john_doe')->willReturn($this->john_doe);
        $this->formelement_factory->method('getUsedFieldByName')->willReturn(null);
        $this->private_comment_extractor->method('extractUGroupsFromXML')->willReturn([]);

        $this->importer->importFromXML(
            $this->tracker,
            $xml_element,
            $this->extraction_path,
            new TrackerXmlFieldsMapping_InSamePlatform(),
            $this->url_mapping,
            $this->tracker_xml_config
        );
    }

    private function buildXMLElementWithAttachment(): SimpleXMLElement
    {
        return new SimpleXMLElement('<?xml version="1.0"?>
            <artifacts>
                <artifact id="41646">
                    <changeset>
                        <submitted_by format="username">manuel</submitted_by>
                        <submitted_on format="ISO8601">2014-01-29T10:39:44+01:00</submitted_on>
                        <field_change field_name="attachment" type="file">
                          <value ref="File33"/>
                        </field_change>
                        <field_change field_name="summary" type="string">
                          <value>Newly submitted</value>
                        </field_change>
                    </changeset>
                    <file id="File33">
                        <filename>A.png</filename>
                        <path>34_File33.png</path>
                        <filesize>87947</filesize>
                        <filetype>image/png</filetype>
                        <description>None</description>
                    </file>
                </artifact>
            </artifacts>
        ');
    }

    public function testItCreatesAChangesetWithSummaryWhenFileFormElementDoesNotExist(): void
    {
        $data = [
            $this->summary_field_id => 'Newly submitted',
        ];

        $this->artifact_creator->expects($this->once())->method('createBare')
            ->willReturn($this->artifact);

        $this->artifact_creator->expects($this->once())->method('createFirstChangeset')
            ->willReturnCallback(
                fn(
                    Artifact $artifact,
                    InitialChangesetValuesContainer $changeset_values,
                    PFUser $user,
                    int $submitted_on,
                    bool $send_notification,
                    CreatedFileURLMapping $url_mapping,
                    TrackerImportConfig $tracker_import_config,
                ): ?Tracker_Artifact_Changeset => match (true) {
                    $changeset_values->getFieldsData() === $data && $send_notification === false => ChangesetTestBuilder::aChangeset(259)->build()
                }
            );

        $summary_field = StringFieldBuilder::aStringField($this->summary_field_id)->inTracker($this->tracker)->withName('summary')
            ->withSpecificProperty('maxchars', ['name' => 'maxchars', 'type' => 'string', 'value' => '0'])->thatIsRequired()->build();
        $this->formelement_factory->method('getUsedFieldByName')->with(self::TRACKER_ID, self::isString())
            ->willReturnCallback(static fn(int $tracker_id, string $name) => match ($name) {
                'attachment' => null,
                'summary'    => $summary_field
            });
        $this->new_changeset_creator->method('create')->willReturn(ChangesetTestBuilder::aChangeset(452)->build());
        $this->user_manager->method('getUserByIdentifier')->with('manuel')->willReturn($this->john_doe);
        $this->private_comment_extractor->method('extractUGroupsFromXML')->willReturn([]);

        $this->importer->importFromXML(
            $this->tracker,
            $this->buildXMLElementWithAttachment(),
            $this->extraction_path,
            new TrackerXmlFieldsMapping_InSamePlatform(),
            $this->url_mapping,
            $this->tracker_xml_config
        );
    }

    public function testItCreatesAChangesetWithOneFileElement(): void
    {
        $this->artifact_creator->method('create')->willReturn($this->artifact);
        $this->formelement_factory->method('getUsedFieldByName')->with(self::TRACKER_ID, 'attachment')
            ->willReturn(FileFieldBuilder::aFileField(51)->build());

        touch($this->extraction_path . '/34_File33.png');

        $this->artifact_creator->expects($this->once())->method('createBare')
            ->willReturn($this->artifact);

        $this->new_changeset_creator->method('create')->willReturn(ChangesetTestBuilder::aChangeset(48)->build());
        $this->user_manager->method('getUserByIdentifier')->with('manuel')->willReturn($this->john_doe);
        $this->formelement_factory->method('getUsedFieldByName')->willReturn(null);
        $this->private_comment_extractor->method('extractUGroupsFromXML')->willReturn([]);

        $this->importer->importFromXML(
            $this->tracker,
            $this->buildXMLElementWithAttachment(),
            $this->extraction_path,
            new TrackerXmlFieldsMapping_InSamePlatform(),
            $this->url_mapping,
            $this->tracker_xml_config
        );
    }

    public function testItSkipsFieldWithoutValidFile(): void
    {
        $xml_element = new SimpleXMLElement('<?xml version="1.0"?>
            <artifacts>
                <artifact id="41646">
                    <changeset>
                        <submitted_by format="username">manuel</submitted_by>
                        <submitted_on format="ISO8601">2014-01-29T10:39:44+01:00</submitted_on>
                        <field_change field_name="attachment" type="file">
                          <value ref="File33"/>
                        </field_change>
                        <field_change field_name="summary" type="string">
                          <value>Newly submitted</value>
                        </field_change>
                    </changeset>
                    <file id="File33">
                        <filename>A.png</filename>
                        <path>34_File33.png</path>
                        <filesize>87947</filesize>
                        <filetype>image/png</filetype>
                        <description>None</description>
                    </file>
                </artifact>
            </artifacts>
        ');

        $data = [
            $this->summary_field_id => 'Newly submitted',
        ];

        $summary_field = StringFieldBuilder::aStringField($this->summary_field_id)->inTracker($this->tracker)->withName('summary')
            ->withSpecificProperty('maxchars', ['name' => 'maxchars', 'type' => 'string', 'value' => '0'])->thatIsRequired()->build();
        $this->formelement_factory->method('getUsedFieldByName')->with(self::TRACKER_ID, self::isString())
            ->willReturnCallback(static fn(int $tracker_id, string $name) => match ($name) {
                'attachment' => null,
                'summary'    => $summary_field
            });
        $this->user_manager->method('getUserByIdentifier')->with('manuel')->willReturn($this->john_doe);
        $this->private_comment_extractor->method('extractUGroupsFromXML')->willReturn([]);

        $this->artifact_creator->expects($this->once())->method('createBare')
            ->willReturn($this->artifact);

        $this->artifact_creator->expects($this->once())->method('createFirstChangeset')
            ->willReturnCallback(
                fn(
                    Artifact $artifact,
                    InitialChangesetValuesContainer $changeset_values,
                    PFUser $user,
                    int $submitted_on,
                    bool $send_notification,
                    CreatedFileURLMapping $url_mapping,
                    TrackerImportConfig $tracker_import_config,
                ): ?Tracker_Artifact_Changeset => match (true) {
                    $changeset_values->getFieldsData() === $data && $send_notification === false => ChangesetTestBuilder::aChangeset(259)->build()
                }
            );

        $this->importer->importFromXML(
            $this->tracker,
            $xml_element,
            $this->extraction_path,
            new TrackerXmlFieldsMapping_InSamePlatform(),
            $this->url_mapping,
            $this->tracker_xml_config
        );
    }

    public function testItCreatesAChangesetWithTwoFileElements(): void
    {
        $xml_element = new SimpleXMLElement('<?xml version="1.0"?>
            <artifacts>
                <artifact id="41646">
                    <changeset>
                        <submitted_by format="username">manuel</submitted_by>
                        <submitted_on format="ISO8601">2014-01-29T10:39:44+01:00</submitted_on>
                        <field_change field_name="attachment" type="file">
                          <value ref="File33"/>
                          <value ref="File34"/>
                        </field_change>
                        <field_change field_name="summary" type="string">
                          <value>Newly submitted</value>
                        </field_change>
                    </changeset>
                    <file id="File33">
                        <filename>A.png</filename>
                        <path>34_File33.png</path>
                        <filesize>87947</filesize>
                        <filetype>image/png</filetype>
                        <description>None</description>
                    </file>
                    <file id="File34">
                        <filename>B.pdf</filename>
                        <path>34_File34.pdf</path>
                        <filesize>84895</filesize>
                        <filetype>application/x-download</filetype>
                        <description>A Zuper File</description>
                    </file>
                </artifact>
            </artifacts>
        ');
        touch($this->extraction_path . '/34_File33.png');
        touch($this->extraction_path . '/34_File34.pdf');

        $this->formelement_factory->method('getUsedFieldByName')->with(self::TRACKER_ID, 'attachment')
            ->willReturn(FileFieldBuilder::aFileField(51)->build());
        $this->user_manager->method('getUserByIdentifier')->with('manuel')->willReturn($this->john_doe);
        $this->private_comment_extractor->method('extractUGroupsFromXML')->willReturn([]);

        $this->artifact_creator->expects($this->once())->method('createBare')->willReturn($this->artifact);

        $this->importer->importFromXML(
            $this->tracker,
            $xml_element,
            $this->extraction_path,
            new TrackerXmlFieldsMapping_InSamePlatform(),
            $this->url_mapping,
            $this->tracker_xml_config
        );
    }

    public function testItCreatesChangesetsThatOnlyReferenceConcernedFiles(): void
    {
        $xml_element = new SimpleXMLElement('<?xml version="1.0"?>
            <artifacts>
                <artifact id="41646">
                    <changeset>
                        <submitted_by format="username">manuel</submitted_by>
                        <submitted_on format="ISO8601">2014-01-29T10:39:44+01:00</submitted_on>
                        <field_change field_name="attachment" type="file">
                          <value ref="File33"/>
                        </field_change>
                        <field_change field_name="summary" type="string">
                          <value>Newly submitted</value>
                        </field_change>
                    </changeset>
                    <changeset>
                        <submitted_by format="username">manuel</submitted_by>
                        <submitted_on format="ISO8601">2014-01-30T10:39:44+01:00</submitted_on>
                        <field_change field_name="attachment" type="file">
                          <value ref="File33"/>
                          <value ref="File34"/>
                        </field_change>
                    </changeset>
                    <file id="File33">
                        <filename>A.png</filename>
                        <path>34_File33.png</path>
                        <filesize>87947</filesize>
                        <filetype>image/png</filetype>
                        <description>None</description>
                    </file>
                    <file id="File34">
                        <filename>B.pdf</filename>
                        <path>34_File34.pdf</path>
                        <filesize>84895</filesize>
                        <filetype>application/x-download</filetype>
                        <description>A Zuper File</description>
                    </file>
                </artifact>
            </artifacts>
        ');
        touch($this->extraction_path . '/34_File33.png');
        touch($this->extraction_path . '/34_File34.pdf');

        $this->formelement_factory->method('getUsedFieldByName')->with(self::TRACKER_ID, 'attachment')
            ->willReturn(FileFieldBuilder::aFileField(51)->build());
        $this->user_manager->method('getUserByIdentifier')->with('manuel')->willReturn($this->john_doe);
        $this->private_comment_extractor->method('extractUGroupsFromXML')->willReturn([]);

        $this->artifact_creator->expects($this->once())->method('createBare')->willReturn($this->artifact);

        $this->new_changeset_creator->method('create')
            ->with(
                $this->artifact,
                self::callback(function ($data) {
                    return $data[51][0]['name'] === 'B.pdf' &&
                           $data[51][0]['submitted_by']->getEmail() === 'manuel';
                }),
                self::anything(),
                self::anything(),
                self::anything(),
                self::anything(),
                self::anything(),
                self::anything(),
                []
            )
            ->willReturn(ChangesetTestBuilder::aChangeset(489)->build());

        $this->importer->importFromXML(
            $this->tracker,
            $xml_element,
            $this->extraction_path,
            new TrackerXmlFieldsMapping_InSamePlatform(),
            $this->url_mapping,
            $this->tracker_xml_config
        );
    }

    private function buildCCListXMLElement(): SimpleXMLElement
    {
        return new SimpleXMLElement('<?xml version="1.0"?>
            <artifacts>
              <artifact id="4918">
                <changeset>
                  <submitted_by format="username">john_doe</submitted_by>
                  <submitted_on format="ISO8601">2014-01-15T10:38:06+01:00</submitted_on>
                  <field_change type="open_list" field_name="cc" bind="user">
                    <value>homer</value>
                    <value>jeanjean</value>
                  </field_change>
                </changeset>
              </artifact>
            </artifacts>');
    }

    public function testItDelegatesOpenListComputationToField(): void
    {
        $this->artifact_creator->method('createBare')->willReturn($this->artifact);
        $this->artifact_creator->method('createFirstChangeset')->willReturn(ChangesetTestBuilder::aChangeset(259)->build());

        $open_list_field = $this->createMock(Tracker_FormElement_Field_OpenList::class);
        $open_list_field->method('setTracker');
        $open_list_field->method('getId')->willReturn(369);
        $open_list_field->method('validateField')->willReturn(true);
        $open_list_field->expects($this->exactly(2))->method('getFieldData')
            ->with(self::callback(static fn(string $value) => in_array($value, ['homer', 'jeanjean'])));

        $this->formelement_factory->method('getUsedFieldByName')->with(self::TRACKER_ID, 'cc')->willReturn($open_list_field);
        $this->user_manager->method('getUserByIdentifier')->with('john_doe')->willReturn($this->john_doe);
        $this->private_comment_extractor->method('extractUGroupsFromXML')->willReturn([]);

        $this->importer->importFromXML(
            $this->tracker,
            $this->buildCCListXMLElement(),
            $this->extraction_path,
            new TrackerXmlFieldsMapping_InSamePlatform(),
            $this->url_mapping,
            $this->tracker_xml_config
        );
    }

    public function testItCreatesArtifactWithCCFieldData(): void
    {
        $open_list_field = $this->createMock(Tracker_FormElement_Field_OpenList::class);
        $open_list_field->method('setTracker');
        $open_list_field->method('getId')->willReturn(369);
        $open_list_field->method('validateField')->willReturn(true);
        $open_list_field->expects($this->exactly(2))->method('getFieldData')
            ->willReturnCallback(static fn(string $value) => match ($value) {
                'homer'    => '!112',
                'jeanjean' => '!113',
            });

        $this->formelement_factory->method('getUsedFieldByName')->with(self::TRACKER_ID, 'cc')->willReturn($open_list_field);
        $this->user_manager->method('getUserByIdentifier')->with('john_doe')->willReturn($this->john_doe);
        $this->private_comment_extractor->method('extractUGroupsFromXML')->willReturn([]);

        $this->artifact_creator->expects($this->once())->method('createBare')->willReturn($this->artifact);

        $this->artifact_creator->expects($this->once())->method('createFirstChangeset')
            ->willReturnCallback(
                fn(
                    Artifact $artifact,
                    InitialChangesetValuesContainer $changeset_values,
                    PFUser $user,
                    int $submitted_on,
                    bool $send_notification,
                    CreatedFileURLMapping $url_mapping,
                    TrackerImportConfig $tracker_import_config,
                ): ?Tracker_Artifact_Changeset => match (true) {
                    $changeset_values->getFieldsData()[369] === '!112,!113' && $send_notification === false => ChangesetTestBuilder::aChangeset(259)->build()
                }
            );

        $this->importer->importFromXML(
            $this->tracker,
            $this->buildCCListXMLElement(),
            $this->extraction_path,
            new TrackerXmlFieldsMapping_InSamePlatform(),
            $this->url_mapping,
            $this->tracker_xml_config
        );
    }

    public function testItCreatesArtifactWithPermsFieldData(): void
    {
        $perms_field = $this->createMock(Tracker_FormElement_Field_PermissionsOnArtifact::class);
        $perms_field->method('getId')->willReturn(369);
        $perms_field->method('validateField')->willReturn(true);
        $perms_field->method('setTracker');

        $this->formelement_factory->method('getUsedFieldByName')->with(self::TRACKER_ID, 'permissions_on_artifact')->willReturn($perms_field);
        $this->user_manager->method('getUserByIdentifier')->with('john_doe')->willReturn($this->john_doe);
        $this->private_comment_extractor->method('extractUGroupsFromXML')->willReturn([]);

        $xml_element = new SimpleXMLElement('<?xml version="1.0"?>
            <artifacts>
              <artifact id="4918">
                <changeset>
                  <submitted_by format="username">john_doe</submitted_by>
                  <submitted_on format="ISO8601">2014-01-15T10:38:06+01:00</submitted_on>
                  <field_change type="permissions_on_artifact" field_name="permissions_on_artifact" use_perm="1">
                    <ugroup ugroup_id="15" />
                    <ugroup ugroup_id="101" />
                  </field_change>
                </changeset>
              </artifact>
            </artifacts>');

        $this->artifact_creator->expects($this->once())->method('createBare')->willReturn($this->artifact);

        $this->artifact_creator->expects($this->once())->method('createFirstChangeset')
            ->willReturnCallback(
                fn(
                    Artifact $artifact,
                    InitialChangesetValuesContainer $changeset_values,
                    PFUser $user,
                    int $submitted_on,
                    bool $send_notification,
                    CreatedFileURLMapping $url_mapping,
                    TrackerImportConfig $tracker_import_config,
                ): ?Tracker_Artifact_Changeset => match (true) {
                    $changeset_values->getFieldsData()[369]['use_artifact_permissions'] === 1
                    && $changeset_values->getFieldsData()[369]['u_groups'] === [15, 101]
                    && $send_notification === false
                    => ChangesetTestBuilder::aChangeset(259)->build()
                }
            );

        $this->importer->importFromXML(
            $this->tracker,
            $xml_element,
            $this->extraction_path,
            new TrackerXmlFieldsMapping_InSamePlatform(),
            $this->url_mapping,
            $this->tracker_xml_config
        );
    }

    public function testItCreatesArtifactWithTextData(): void
    {
        $text_field = TextFieldBuilder::aTextField(369)->build();

        $this->formelement_factory->method('getUsedFieldByName')->with(self::TRACKER_ID, 'textarea')->willReturn($text_field);
        $this->user_manager->method('getUserByIdentifier')->with('john_doe')->willReturn($this->john_doe);
        $this->private_comment_extractor->method('extractUGroupsFromXML')->willReturn([]);

        $xml_element = new SimpleXMLElement('<?xml version="1.0"?>
            <artifacts>
              <artifact id="4918">
                <changeset>
                  <submitted_by format="username">john_doe</submitted_by>
                  <submitted_on format="ISO8601">2014-01-15T10:38:06+01:00</submitted_on>
                  <field_change type="text" field_name="textarea">
                    <value format="html">test</value>
                  </field_change>
                </changeset>
              </artifact>
            </artifacts>');

        $this->artifact_creator->expects($this->once())->method('createBare')->willReturn($this->artifact);

        $this->artifact_creator->expects($this->once())->method('createFirstChangeset')
            ->willReturnCallback(
                fn(
                    Artifact $artifact,
                    InitialChangesetValuesContainer $changeset_values,
                    PFUser $user,
                    int $submitted_on,
                    bool $send_notification,
                    CreatedFileURLMapping $url_mapping,
                    TrackerImportConfig $tracker_import_config,
                ): ?Tracker_Artifact_Changeset => match (true) {
                    $changeset_values->getFieldsData()[369]['format'] === 'html'
                    && $changeset_values->getFieldsData()[369]['content'] === 'test'
                    && $send_notification === false
                    => ChangesetTestBuilder::aChangeset(259)->build()
                }
            );

        $this->importer->importFromXML(
            $this->tracker,
            $xml_element,
            $this->extraction_path,
            new TrackerXmlFieldsMapping_InSamePlatform(),
            $this->url_mapping,
            $this->tracker_xml_config
        );
    }

    private function buildAlnumFieldXMLElement(): SimpleXMLElement
    {
        return new SimpleXMLElement('<?xml version="1.0"?>
            <artifacts>
              <artifact id="4918">
                <changeset>
                  <submitted_by format="username">john_doe</submitted_by>
                  <submitted_on format="ISO8601">2014-01-15T10:38:06+01:00</submitted_on>
                  <field_change type="string" field_name="i_want_to">
                    <value>Import artifact in tracker v5</value>
                  </field_change>
                  <field_change type="text" field_name="so_that">
                    <value>My base of support tickets is migrated from Bugzilla to Tuleap</value>
                  </field_change>
                  <field_change type="int" field_name="initial_effort">
                    <value>5</value>
                  </field_change>
                  <field_change type="float" field_name="remaining_effort">
                    <value>4.5</value>
                  </field_change>
                  <field_change type="date" field_name="start_date">
                    <value format="ISO8601">2014-03-20T10:13:07+01:00</value>
                  </field_change>
                </changeset>
              </artifact>
            </artifacts>');
    }

    public function testItCreatesArtifactWithAlphanumFieldData(): void
    {
        $string_field = $this->createMock(StringField::class);
        $string_field->method('getId')->willReturn(369);
        $string_field->method('validateField')->willReturn(true);
        $string_field->method('setTracker');
        $int_field     = IntegerFieldBuilder::anIntField(234)->build();
        $float_field   = FloatFieldBuilder::aFloatField(347)->build();
        $date_field    = DateFieldBuilder::aDateField(978)->build();
        $summary_field = StringFieldBuilder::aStringField($this->summary_field_id)->inTracker($this->tracker)->withName('summary')
            ->withSpecificProperty('maxchars', ['name' => 'maxchars', 'type' => 'string', 'value' => '0'])->thatIsRequired()->build();
        $this->formelement_factory->method('getUsedFieldByName')->with(self::TRACKER_ID, self::isString())
            ->willReturnCallback(static fn(int $tracker_id, string $name) => match ($name) {
                'i_want_to'        => $string_field,
                'initial_effort'   => $int_field,
                'remaining_effort' => $float_field,
                'start_date'       => $date_field,
                'summary'          => $summary_field,
                'so_that'          => null,
            });
        $this->user_manager->method('getUserByIdentifier')->with('john_doe')->willReturn($this->john_doe);
        $this->private_comment_extractor->method('extractUGroupsFromXML')->willReturn([]);

        $this->artifact_creator->expects($this->once())->method('createBare')->willReturn($this->artifact);

        $this->artifact_creator->expects($this->once())->method('createFirstChangeset')
            ->willReturnCallback(
                fn(
                    Artifact $artifact,
                    InitialChangesetValuesContainer $changeset_values,
                    PFUser $user,
                    int $submitted_on,
                    bool $send_notification,
                    CreatedFileURLMapping $url_mapping,
                    TrackerImportConfig $tracker_import_config,
                ): ?Tracker_Artifact_Changeset => match (true) {
                    $changeset_values->getFieldsData()[369] === 'Import artifact in tracker v5'
                    && $changeset_values->getFieldsData()[234] === '5'
                    && $changeset_values->getFieldsData()[347] === '4.5'
                    && $changeset_values->getFieldsData()[978] === '2014-03-20'
                    && $send_notification === false
                    => ChangesetTestBuilder::aChangeset(259)->build()
                }
            );

        $this->importer->importFromXML(
            $this->tracker,
            $this->buildAlnumFieldXMLElement(),
            $this->extraction_path,
            new TrackerXmlFieldsMapping_InSamePlatform(),
            $this->url_mapping,
            $this->tracker_xml_config
        );
    }

    public function testItCreatesArtifactWithAlphanumFieldDataAndTimeDisplayedDate(): void
    {
        $string_field = $this->createMock(StringField::class);
        $string_field->method('getId')->willReturn(369);
        $string_field->method('validateField')->willReturn(true);
        $string_field->method('setTracker');
        $int_field     = IntegerFieldBuilder::anIntField(234)->build();
        $float_field   = FloatFieldBuilder::aFloatField(347)->build();
        $date_field    = DateFieldBuilder::aDateField(978)->withTime()->build();
        $summary_field = StringFieldBuilder::aStringField($this->summary_field_id)->inTracker($this->tracker)->withName('summary')
            ->withSpecificProperty('maxchars', ['name' => 'maxchars', 'type' => 'string', 'value' => '0'])->thatIsRequired()->build();
        $this->formelement_factory->method('getUsedFieldByName')->with(self::TRACKER_ID, self::isString())
            ->willReturnCallback(static fn(int $tracker_id, string $name) => match ($name) {
                'i_want_to'        => $string_field,
                'initial_effort'   => $int_field,
                'remaining_effort' => $float_field,
                'start_date'       => $date_field,
                'summary'          => $summary_field,
                'so_that'          => null,
            });
        $this->user_manager->method('getUserByIdentifier')->with('john_doe')->willReturn($this->john_doe);
        $this->private_comment_extractor->method('extractUGroupsFromXML')->willReturn([]);

        $this->artifact_creator->expects($this->once())->method('createBare')->willReturn($this->artifact);

        $this->artifact_creator->expects($this->once())->method('createFirstChangeset')
            ->willReturnCallback(
                fn(
                    Artifact $artifact,
                    InitialChangesetValuesContainer $changeset_values,
                    PFUser $user,
                    int $submitted_on,
                    bool $send_notification,
                    CreatedFileURLMapping $url_mapping,
                    TrackerImportConfig $tracker_import_config,
                ): ?Tracker_Artifact_Changeset => match (true) {
                    $changeset_values->getFieldsData()[369] === 'Import artifact in tracker v5'
                    && $changeset_values->getFieldsData()[234] === '5'
                    && $changeset_values->getFieldsData()[347] === '4.5'
                    && $changeset_values->getFieldsData()[978] === '2014-03-20 10:13'
                    && $send_notification === false
                    => ChangesetTestBuilder::aChangeset(259)->build()
                }
            );

        $this->importer->importFromXML(
            $this->tracker,
            $this->buildAlnumFieldXMLElement(),
            $this->extraction_path,
            new TrackerXmlFieldsMapping_InSamePlatform(),
            $this->url_mapping,
            $this->tracker_xml_config
        );
    }

    public function testItDoesntConvertEmptyDateInto70sdate(): void
    {
        $xml_element = new SimpleXMLElement('<?xml version="1.0"?>
            <artifacts>
              <artifact id="4918">
                <changeset>
                  <submitted_by format="username">john_doe</submitted_by>
                  <submitted_on format="ISO8601">2014-01-15T10:38:06+01:00</submitted_on>
                  <field_change type="date" field_name="start_date">
                    <value format="ISO8601"></value>
                  </field_change>
                </changeset>
              </artifact>
            </artifacts>');
        $this->formelement_factory->method('getUsedFieldByName')->with(self::TRACKER_ID, self::isString())
            ->willReturnCallback(static fn(int $tracker_id, string $name) => match ($name) {
                'i_want_to'        => StringFieldBuilder::aStringField(369)->build(),
                'initial_effort'   => IntegerFieldBuilder::anIntField(234)->build(),
                'remaining_effort' => FloatFieldBuilder::aFloatField(347)->build(),
                'start_date'       => DateFieldBuilder::aDateField(978)->build(),
            });
        $this->user_manager->method('getUserByIdentifier')->with('john_doe')->willReturn($this->john_doe);
        $this->private_comment_extractor->method('extractUGroupsFromXML')->willReturn([]);

        $this->artifact_creator->expects($this->once())->method('createBare')->willReturn($this->artifact);

        $this->artifact_creator->expects($this->once())->method('createFirstChangeset')
            ->willReturnCallback(
                fn(
                    Artifact $artifact,
                    InitialChangesetValuesContainer $changeset_values,
                    PFUser $user,
                    int $submitted_on,
                    bool $send_notification,
                    CreatedFileURLMapping $url_mapping,
                    TrackerImportConfig $tracker_import_config,
                ): ?Tracker_Artifact_Changeset => match (true) {
                    $changeset_values->getFieldsData()[978] === ''
                    && $send_notification === false
                    => ChangesetTestBuilder::aChangeset(259)->build()
                }
            );

        $this->importer->importFromXML(
            $this->tracker,
            $xml_element,
            $this->extraction_path,
            new TrackerXmlFieldsMapping_InSamePlatform(),
            $this->url_mapping,
            $this->tracker_xml_config
        );
    }

    public function testItCreatesArtifactWithSelectboxValue(): void
    {
        $status_field = $this->createMock(StringField::class);
        $status_field->method('getId')->willReturn(234);
        $status_field->method('validateField')->willReturn(true);
        $status_field->method('setTracker');
        $assto_field = $this->createMock(StringField::class);
        $assto_field->method('getId')->willReturn(456);
        $assto_field->method('validateField')->willReturn(true);
        $assto_field->method('setTracker');

        $this->formelement_factory->method('getUsedFieldByName')->with(self::TRACKER_ID, self::isString())
            ->willReturnCallback(static fn(int $tracker_id, string $name) => match ($name) {
                'status_id'   => $status_field,
                'assigned_to' => $assto_field,
            });
        $this->user_manager->method('getUserByIdentifier')->with('john_doe')->willReturn($this->john_doe);
        $this->private_comment_extractor->method('extractUGroupsFromXML')->willReturn([]);

        $this->static_value_dao->method('searchValueByLabel')->with(234, 'Open')->willReturn(TestHelper::arrayToDar([
            'id'    => 104,
            'label' => 'Open',
            // ...
        ]));
        $xml_element = new SimpleXMLElement('<?xml version="1.0"?>
            <artifacts>
              <artifact id="4918">
                <changeset>
                  <submitted_by format="username">john_doe</submitted_by>
                  <submitted_on format="ISO8601">2014-01-15T10:38:06+01:00</submitted_on>
                  <field_change type="list" field_name="status_id" bind="static">
                    <value>Open</value>
                  </field_change>
                  <field_change type="list" field_name="assigned_to" bind="user">
                    <value format="username">john_doe</value>
                  </field_change>
                </changeset>
              </artifact>
            </artifacts>');

        $this->artifact_creator->expects($this->once())->method('createBare')->willReturn($this->artifact);

        $this->artifact_creator->expects($this->once())->method('createFirstChangeset')
            ->willReturnCallback(
                fn(
                    Artifact $artifact,
                    InitialChangesetValuesContainer $changeset_values,
                    PFUser $user,
                    int $submitted_on,
                    bool $send_notification,
                    CreatedFileURLMapping $url_mapping,
                    TrackerImportConfig $tracker_import_config,
                ): ?Tracker_Artifact_Changeset => match (true) {
                    $changeset_values->getFieldsData()[234] === [104] &&
                    $changeset_values->getFieldsData()[456] === [$this->john_doe->getId()] &&
                    $send_notification === false
                    => ChangesetTestBuilder::aChangeset(259)->build()
                }
            );

        $this->user_manager->method('getUserByIdentifier')->with('john_doe')->willReturn($this->john_doe);

        $this->importer->importFromXML(
            $this->tracker,
            $xml_element,
            $this->extraction_path,
            new TrackerXmlFieldsMapping_InSamePlatform(),
            $this->url_mapping,
            $this->tracker_xml_config
        );
    }

    public function testItCreatesArtifactWithAllMultiSelectboxValue(): void
    {
        $static_multi_selectbox_field = $this->createMock(Tracker_FormElement_Field_MultiSelectbox::class);
        $static_multi_selectbox_field->method('getId')->willReturn(456);
        $static_multi_selectbox_field->method('validateField')->willReturn(true);
        $static_multi_selectbox_field->method('setTracker');

        $this->formelement_factory->method('getUsedFieldByName')->with(self::TRACKER_ID, 'multi_select_box')->willReturn($static_multi_selectbox_field);
        $this->user_manager->method('getUserByIdentifier')->with('john_doe')->willReturn($this->john_doe);
        $this->private_comment_extractor->method('extractUGroupsFromXML')->willReturn([]);
        $this->static_value_dao->method('searchValueByLabel')->with(456, self::isString())
            ->willReturnCallback(static fn(int $id, string $label) => match ($label) {
                'UI'       => TestHelper::arrayToDar([
                    'id'    => 101,
                    'label' => 'UI',
                ]),
                'Database' => TestHelper::arrayToDar([
                    'id'    => 102,
                    'label' => 'Database',
                ]),
            });

        $xml_element = new SimpleXMLElement('<?xml version="1.0"?>
            <artifacts>
              <artifact id="4918">
                <changeset>
                  <submitted_by format="username">john_doe</submitted_by>
                  <submitted_on format="ISO8601">2014-01-15T10:38:06+01:00</submitted_on>
                  <field_change type="list" field_name="multi_select_box" bind="static">
                    <value>UI</value>
                    <value>Database</value>
                  </field_change>
                </changeset>
              </artifact>
            </artifacts>');

        $this->artifact_creator->expects($this->once())->method('createBare')->willReturn($this->artifact);

        $this->artifact_creator->expects($this->once())->method('createFirstChangeset')
            ->willReturnCallback(
                fn(
                    Artifact $artifact,
                    InitialChangesetValuesContainer $changeset_values,
                    PFUser $user,
                    int $submitted_on,
                    bool $send_notification,
                    CreatedFileURLMapping $url_mapping,
                    TrackerImportConfig $tracker_import_config,
                ): ?Tracker_Artifact_Changeset => match (true) {
                    $changeset_values->getFieldsData()[456] === [101, 102] &&
                    $send_notification === false
                    => ChangesetTestBuilder::aChangeset(259)->build()
                }
            );


        $this->importer->importFromXML(
            $this->tracker,
            $xml_element,
            $this->extraction_path,
            new TrackerXmlFieldsMapping_InSamePlatform(),
            $this->url_mapping,
            $this->tracker_xml_config
        );
    }

    public function testItCreatesArtifactWithAllUserMultiSelectboxValue(): void
    {
        $user_multi_selectbox_field = $this->createMock(Tracker_FormElement_Field_MultiSelectbox::class);
        $user_multi_selectbox_field->method('getId')->willReturn(456);
        $user_multi_selectbox_field->method('validateField')->willReturn(true);
        $user_multi_selectbox_field->method('setTracker');

        $this->formelement_factory->method('getUsedFieldByName')->with(self::TRACKER_ID, 'multi_select_box_user')->willReturn($user_multi_selectbox_field);
        $this->private_comment_extractor->method('extractUGroupsFromXML')->willReturn([]);

        $xml_element = new SimpleXMLElement('<?xml version="1.0"?>
            <artifacts>
              <artifact id="4918">
                <changeset>
                  <submitted_by format="username">john_doe</submitted_by>
                  <submitted_on format="ISO8601">2014-01-15T10:38:06+01:00</submitted_on>
                  <field_change type="list" field_name="multi_select_box_user" bind="user">
                    <value format="username">jeanne</value>
                    <value format="username">serge</value>
                  </field_change>
                </changeset>
              </artifact>
            </artifacts>');

        $jeanne = new PFUser([
            'user_id'     => 101,
            'language_id' => 'en',
            'user_name'   => 'jeanne',
        ]);

        $serge = new PFUser([
            'user_id'     => 102,
            'language_id' => 'en',
            'user_name'   => 'serge',
        ]);

        $this->user_manager->method('getUserByIdentifier')->willReturnCallback(fn(string $name) => match ($name) {
            'jeanne'   => $jeanne,
            'serge'    => $serge,
            'john_doe' => $this->john_doe,
        });

        $this->artifact_creator->expects($this->once())->method('createBare')->willReturn($this->artifact);

        $this->artifact_creator->expects($this->once())->method('createFirstChangeset')
            ->willReturnCallback(
                fn(
                    Artifact $artifact,
                    InitialChangesetValuesContainer $changeset_values,
                    PFUser $user,
                    int $submitted_on,
                    bool $send_notification,
                    CreatedFileURLMapping $url_mapping,
                    TrackerImportConfig $tracker_import_config,
                ): ?Tracker_Artifact_Changeset => match (true) {
                    $changeset_values->getFieldsData()[456] === [101, 102] &&
                    $send_notification === false
                    => ChangesetTestBuilder::aChangeset(259)->build()
                }
            );

        $this->importer->importFromXML(
            $this->tracker,
            $xml_element,
            $this->extraction_path,
            new TrackerXmlFieldsMapping_InSamePlatform(),
            $this->url_mapping,
            $this->tracker_xml_config
        );
    }

    private function buildXMLElementChangesetsCreationFailure(): SimpleXMLElement
    {
        return new SimpleXMLElement('<?xml version="1.0"?>
            <artifacts>
              <artifact id="4918">
                <changeset>
                  <submitted_by format="username">john_doe</submitted_by>
                  <submitted_on format="ISO8601">2014-01-15T10:38:06+01:00</submitted_on>
                  <field_change field_name="summary" type="string">
                    <value>Ça marche</value>
                  </field_change>
                </changeset>
                <changeset>
                  <submitted_by format="username">john_doe</submitted_by>
                  <submitted_on format="ISO8601">2014-01-15T11:03:50+01:00</submitted_on>
                  <field_change field_name="summary" type="string">
                    <value>^Wit updates</value>
                  </field_change>
                </changeset>
                <changeset>
                  <submitted_by format="username">john_doe</submitted_by>
                  <submitted_on format="ISO8601">2014-01-15T11:25:50+01:00</submitted_on>
                  <field_change field_name="summary" type="string">
                    <value>Last part</value>
                  </field_change>
                </changeset>
              </artifact>
            </artifacts>');
    }

    public function testItCreatesTheLastChangesetEvenWhenTheIntermediateFails(): void
    {
        $this->artifact_creator->expects($this->once())->method('createBare')->willReturn($this->artifact);
        $this->artifact_creator->expects($this->once())->method('createFirstChangeset')->willReturn(ChangesetTestBuilder::aChangeset(14561)->build());

        $changeset = ChangesetTestBuilder::aChangeset(464265)->build();
        $this->new_changeset_creator->method('create')->willReturnOnConsecutiveCalls(null, $changeset, $changeset);
        $this->user_manager->method('getUserByIdentifier')->with('john_doe')->willReturn($this->john_doe);
        $this->formelement_factory->method('getUsedFieldByName')->willReturn(null);
        $this->private_comment_extractor->method('extractUGroupsFromXML')->willReturn([]);

        $this->importer->importFromXML(
            $this->tracker,
            $this->buildXMLElementChangesetsCreationFailure(),
            $this->extraction_path,
            new TrackerXmlFieldsMapping_InSamePlatform(),
            $this->url_mapping,
            $this->tracker_xml_config
        );
    }

    public function testItCreatesTheLastChangesetEvenWhenTheIntermediateThrowsException(): void
    {
        $this->artifact_creator->expects($this->once())->method('createBare')->willReturn($this->artifact);
        $this->artifact_creator->expects($this->once())->method('createFirstChangeset')->willReturn(ChangesetTestBuilder::aChangeset(18641)->build());

        $changeset = ChangesetTestBuilder::aChangeset(4526456)->build();
        $this->new_changeset_creator->method('create')->willReturnOnConsecutiveCalls(self::throwException(new Exception('Bad luck')), $changeset, $changeset);
        $this->user_manager->method('getUserByIdentifier')->with('john_doe')->willReturn($this->john_doe);
        $this->formelement_factory->method('getUsedFieldByName')->willReturn(null);
        $this->private_comment_extractor->method('extractUGroupsFromXML')->willReturn([]);

        $this->importer->importFromXML(
            $this->tracker,
            $this->buildXMLElementChangesetsCreationFailure(),
            $this->extraction_path,
            new TrackerXmlFieldsMapping_InSamePlatform(),
            $this->url_mapping,
            $this->tracker_xml_config
        );
    }

    public function testArtLinkItShouldMapTheOldIdToTheNewOne(): void
    {
        $xml_element = new SimpleXMLElement('<?xml version="1.0"?>
            <artifacts>
              <artifact id="100">
                <changeset>
                  <submitted_by format="username">john_doe</submitted_by>
                  <submitted_on format="ISO8601">2014-01-15T10:37:06+01:00</submitted_on>
                  <field_change field_name="artlink" type="art_link">
                    <value>101</value>
                  </field_change>
                </changeset>
              </artifact>
              <artifact id="101">
                <changeset>
                  <submitted_by format="username">john_doe</submitted_by>
                  <submitted_on format="ISO8601">2014-01-15T10:38:06+01:00</submitted_on>
                </changeset>
              </artifact>
            </artifacts>');

        $field = ArtifactLinkFieldBuilder::anArtifactLinkField(369)->build();

        $this->formelement_factory->method('getUsedFieldByName')->with(self::TRACKER_ID, 'artlink')->willReturn($field);
        $this->user_manager->method('getUserByIdentifier')->with('john_doe')->willReturn($this->john_doe);
        $this->private_comment_extractor->method('extractUGroupsFromXML')->willReturn([]);

        $art1 = ArtifactTestBuilder::anArtifact(1)->inTracker($this->tracker)->build();
        $art2 = ArtifactTestBuilder::anArtifact(2)->inTracker($this->tracker)->build();

        $this->artifact_creator
            ->expects($this->exactly(2))
            ->method('createBare')
            ->willReturnOnConsecutiveCalls($art1, $art2);

        $this->importer->importFromXML(
            $this->tracker,
            $xml_element,
            $this->extraction_path,
            new TrackerXmlFieldsMapping_InSamePlatform(),
            $this->url_mapping,
            $this->tracker_xml_config
        );
    }

    public function testArtLinkItNotifiesUnexistingArtifacts(): void
    {
        $xml_element = new SimpleXMLElement('<?xml version="1.0"?>
            <artifacts>
              <artifact id="100">
                <changeset>
                  <submitted_by format="username">john_doe</submitted_by>
                  <submitted_on format="ISO8601">2014-01-15T10:37:06+01:00</submitted_on>
                  <field_change field_name="summary" type="string">
                    <value>Last part</value>
                  </field_change>
                </changeset>
              </artifact>
              <artifact id="101">
                <changeset>
                  <submitted_by format="username">john_doe</submitted_by>
                  <submitted_on format="ISO8601">2014-01-15T10:38:06+01:00</submitted_on>
                  <field_change field_name="artlink" type="art_link">
                    <value>100</value>
                    <value>123</value>
                  </field_change>
                </changeset>
              </artifact>
            </artifacts>');

        $field = ArtifactLinkFieldBuilder::anArtifactLinkField(369)->build();

        $this->formelement_factory->method('getUsedFieldByName')->with(self::TRACKER_ID, 'artlink')->willReturn($field);
        $this->user_manager->method('getUserByIdentifier')->with('john_doe')->willReturn($this->john_doe);
        $this->private_comment_extractor->method('extractUGroupsFromXML')->willReturn([]);

        $art1 = ArtifactTestBuilder::anArtifact(1)->inTracker($this->tracker)->build();
        $art2 = ArtifactTestBuilder::anArtifact(2)->inTracker($this->tracker)->build();

        $this->artifact_creator
            ->expects($this->exactly(2))
            ->method('createBare')
            ->willReturnOnConsecutiveCalls($art1, $art2);

        $this->importer->importFromXML(
            $this->tracker,
            $xml_element,
            $this->extraction_path,
            new TrackerXmlFieldsMapping_InSamePlatform(),
            $this->url_mapping,
            $this->tracker_xml_config
        );
        self::assertTrue($this->logger->hasErrorRecords());
    }

    public function testBadDateItCreatesArtifactAtDate(): void
    {
        $xml_element = new SimpleXMLElement('<?xml version="1.0"?>
            <artifacts>
              <artifact id="4918">
                <changeset>
                  <submitted_by format="username">john_doe</submitted_by>
                  <submitted_on format="ISO8601">2011-11-24T15:51:48TCET</submitted_on>
                  <field_change field_name="summary" type="string">
                    <value>Ça marche</value>
                  </field_change>
                </changeset>
              </artifact>
            </artifacts>');

        $this->artifact_creator->expects($this->never())->method('create');
        $this->artifact_creator->expects($this->never())->method('createBare');

        $this->artifact_creator->method('create')->willReturn($this->artifact);

        $this->expectOutputRegex('/Invalid date format not ISO8601: 2011-11-24T15:51:48TCET/');
        $this->importer->importFromXML(
            $this->tracker,
            $xml_element,
            $this->extraction_path,
            new TrackerXmlFieldsMapping_InSamePlatform(),
            $this->url_mapping,
            $this->tracker_xml_config
        );
        self::assertTrue($this->logger->hasErrorRecords());
    }

    public function testItCollectsChangesetIdsMapping(): void
    {
        $user_finder               = $this->createMock(IFindUserFromXMLReference::class);
        $new_changeset_creator     = $this->createMock(NewChangesetCreator::class);
        $artifact_creator          = $this->createMock(TrackerArtifactCreator::class);
        $private_comment_extractor = $this->createMock(TrackerPrivateCommentUGroupExtractor::class);

        $importer = new Tracker_Artifact_XMLImport(
            new XML_RNGValidator(),
            $artifact_creator,
            $new_changeset_creator,
            $this->createStub(Tracker_FormElementFactory::class),
            $user_finder,
            $this->createStub(BindStaticValueDao::class),
            new NullLogger(),
            false,
            $this->createStub(TypeDao::class),
            $this->createStub(ExternalFieldsExtractor::class),
            $private_comment_extractor,
            $this->db_connection,
        );

        $xml_element = new SimpleXMLElement(
            '<?xml version="1.0"?>
            <artifacts>
              <artifact id="4918">
                <changeset id="CHANGESET_10001">
                  <submitted_by format="username">john_doe</submitted_by>
                  <submitted_on format="ISO8601">2018-08-02T17:36:24+02:00</submitted_on>
                </changeset>
                <changeset id="CHANGESET_10002">
                  <submitted_by format="username">john_doe</submitted_by>
                  <submitted_on format="ISO8601">2018-08-03T17:36:24+02:00</submitted_on>
                </changeset>
              </artifact>
            </artifacts>'
        );

        $user_finder->method('getUser')->willReturn(UserTestBuilder::buildWithDefaults());

        $workflow = $this->createStub(Workflow::class);
        $workflow->method('disable');
        $tracker  = TrackerTestBuilder::aTracker()->withWorkflow($workflow)->build();
        $artifact = ArtifactTestBuilder::anArtifact(123)->build();

        $changeset_id_mapping = new ImportedChangesetMapping();

        $changeset1 = ChangesetTestBuilder::aChangeset(11001)->build();
        $changeset2 = ChangesetTestBuilder::aChangeset(11002)->build();

        $artifact_creator->expects($this->once())->method('createFirstChangeset')->willReturn($changeset1);
        $new_changeset_creator->expects($this->once())->method('create')->willReturn($changeset2);

        $importer->importArtifactChangesFromXML(
            $tracker,
            $xml_element,
            '/extraction/path',
            new TrackerXmlFieldsMapping_InSamePlatform(),
            new Tracker_XML_Importer_ArtifactImportedMapping(),
            new CreatedFileURLMapping(),
            [$artifact],
            $changeset_id_mapping,
            $this->tracker_xml_config
        );

        self::assertEquals(11001, $changeset_id_mapping->get('CHANGESET_10001'));
        self::assertEquals(11002, $changeset_id_mapping->get('CHANGESET_10002'));
    }

    public function testItCreatesArtifactsWithProvidedIds(): void
    {
        $this->artifact_creator->expects($this->never())->method('createBare');
        $this->artifact_creator->expects($this->once())->method('createBareWithAllData')
            ->with($this->tracker, 4918)
            ->willReturn(ArtifactTestBuilder::anArtifact(4918)->inTracker($this->tracker)->build());
        $this->user_manager->method('getUserByIdentifier')->with('john_doe')->willReturn($this->john_doe);
        $this->formelement_factory->method('getUsedFieldByName')->willReturn(null);
        $this->private_comment_extractor->method('extractUGroupsFromXML')->willReturn([]);

        $tracker_xml_config = new TrackerXmlImportConfig(
            UserTestBuilder::anActiveUser()->build(),
            new DateTimeImmutable(),
            MoveImportConfig::buildForRegularImport(),
            true
        );


        $this->importer->importFromXML(
            $this->tracker,
            $this->buildValidXMLElement(),
            $this->extraction_path,
            new TrackerXmlFieldsMapping_InSamePlatform(),
            $this->url_mapping,
            $tracker_xml_config
        );
    }
}
