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

use Psr\Log\LoggerInterface;
use Tracker\Artifact\XMLArtifactSourcePlatformExtractor;
use Tuleap\Project\XML\Import\ExternalFieldsExtractor;
use Tuleap\Project\XML\Import\ImportConfig;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\Artifact\Changeset\Comment\PrivateComment\XMLImport\TrackerPrivateCommentUGroupExtractor;
use Tuleap\Tracker\Artifact\Changeset\NewChangeset;
use Tuleap\Tracker\Artifact\Changeset\NewChangesetCreator;
use Tuleap\Tracker\Artifact\Creation\TrackerArtifactCreator;
use Tuleap\Tracker\Artifact\ExistingArtifactSourceIdFromTrackerExtractor;
use Tuleap\Tracker\Artifact\XMLImport\MoveImportConfig;
use Tuleap\Tracker\Artifact\XMLImport\TrackerXmlImportConfig;
use Tuleap\Tracker\DAO\TrackerArtifactSourceIdDao;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\Type\TypeDao;
use Tuleap\Tracker\FormElement\Field\File\CreatedFileURLMapping;
use Tuleap\Tracker\FormElement\Field\ListFields\Bind\BindStaticValueDao;
use Tuleap\Tracker\Test\Builders\ArtifactTestBuilder;
use Tuleap\Tracker\XML\Importer\ImportedChangesetMapping;

// phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace,Squiz.Classes.ValidClassName.NotCamelCaps
final class Tracker_Artifact_XMLImportTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use \Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
    use \Tuleap\GlobalLanguageMock;
    use \Tuleap\TemporaryTestDirectory;
    use \Tuleap\GlobalResponseMock;

    private TrackerXmlImportConfig $tracker_xml_config;
    protected int $tracker_id = 12;

    /** @var Tracker */
    protected $tracker;

    protected Tracker_Artifact_XMLImport $importer;

    /** @var TrackerArtifactCreator */
    protected $artifact_creator;

    /** @var NewChangesetCreator */
    protected $new_changeset_creator;

    /** @var  Tracker_FormElementFactory */
    protected $formelement_factory;

    /** @var  UserManager */
    protected $user_manager;

    /** @var XMLImportHelper  */
    protected $xml_import_helper;

    /** @var Artifact  */
    protected $artifact;

    /** @var  BindStaticValueDao */
    protected $static_value_dao;

    /** @var  \Psr\Log\LoggerInterface */
    protected $logger;

    /** @var  XML_RNGValidator */
    protected $rng_validator;

    protected $extraction_path;
    protected $john_doe;
    protected $config;
    /**
     * @var \Mockery\MockInterface|CreatedFileURLMapping
     */
    protected $url_mapping;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|ExternalFieldsExtractor
     */
    protected $external_field_extractor;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|TrackerPrivateCommentUGroupExtractor
     */
    private $private_comment_extractor;
    private int $summary_field_id;
    private \Tuleap\DB\DBConnection&\PHPUnit\Framework\MockObject\MockObject $db_connection;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tracker = \Mockery::mock(\Tracker::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $this->tracker->shouldReceive('getId')->andReturns($this->tracker_id);
        $this->tracker->shouldReceive('getWorkflow')->andReturns(\Mockery::spy(\Workflow::class));
        $project = \Tuleap\Test\Builders\ProjectTestBuilder::aProject()->withId(101)->build();
        $this->tracker->shouldReceive('getPRoject')->andReturns($project);

        $this->tracker_xml_config = new TrackerXmlImportConfig(
            \Tuleap\Test\Builders\UserTestBuilder::anActiveUser()->build(),
            new DateTimeImmutable(),
            MoveImportConfig::buildForRegularImport(),
            false
        );

        $this->artifact_creator      = \Mockery::spy(TrackerArtifactCreator::class);
        $this->new_changeset_creator = \Mockery::spy(NewChangesetCreator::class);

        $this->summary_field_id    = 50;
        $this->formelement_factory = \Mockery::spy(\Tracker_FormElementFactory::class);
        $string_field              = new Tracker_FormElement_Field_String(50, $this->tracker_id, null, 'str', 'label', 'desc', true, 'S', true, false, 0);
        $string_field->setCacheSpecificProperties(['maxchars' => ['name' => 'maxchars', 'type' => 'string', 'value' => '0']]);
        $this->formelement_factory->shouldReceive('getUsedFieldByName')->with($this->tracker_id, 'summary')->andReturns($string_field);

        $this->john_doe     = new PFUser([
            'user_id' => 200,
            'language_id' => 'en',
            'user_name' => 'john_doe',
        ]);
        $this->user_manager = \Mockery::spy(\UserManager::class);
        $this->user_manager->shouldReceive('getUserByIdentifier')->with('john_doe')->andReturns($this->john_doe);
        $this->user_manager->shouldReceive('getUserAnonymous')->andReturns(new PFUser(['user_id' => 0]));

        $this->xml_import_helper = new XMLImportHelper($this->user_manager);

        $this->config = new \Tuleap\Project\XML\Import\ImportConfig();

        $this->artifact = \Mockery::spy(\Tuleap\Tracker\Artifact\Artifact::class);

        $this->extraction_path = $this->getTmpDir();

        $this->static_value_dao = \Mockery::spy(
            \Tuleap\Tracker\FormElement\Field\ListFields\Bind\BindStaticValueDao::class
        );

        $this->db_connection = $this->createMock(\Tuleap\DB\DBConnection::class);
        $this->db_connection->method('reconnectAfterALongRunningProcess');

        $this->logger = \Mockery::spy(\Psr\Log\LoggerInterface::class);

        $this->rng_validator = \Mockery::spy(\XML_RNGValidator::class);

        $this->url_mapping = \Mockery::mock(CreatedFileURLMapping::class);

        $this->external_field_extractor = Mockery::mock(ExternalFieldsExtractor::class);
        $this->external_field_extractor->shouldReceive('extractExternalFieldsFromArtifact');
        $this->private_comment_extractor = Mockery::mock(TrackerPrivateCommentUGroupExtractor::class);
        $this->private_comment_extractor
            ->shouldReceive('extractUGroupsFromXML')
            ->andReturn([])
            ->byDefault();
        $this->importer = new Tracker_Artifact_XMLImport(
            $this->rng_validator,
            $this->artifact_creator,
            $this->new_changeset_creator,
            $this->formelement_factory,
            $this->xml_import_helper,
            $this->static_value_dao,
            $this->logger,
            false,
            \Mockery::spy(\Tracker_ArtifactFactory::class),
            \Mockery::spy(\Tuleap\Tracker\FormElement\Field\ArtifactLink\Type\TypeDao::class),
            Mockery::spy(XMLArtifactSourcePlatformExtractor::class),
            Mockery::spy(\Tuleap\Tracker\Artifact\ExistingArtifactSourceIdFromTrackerExtractor::class),
            Mockery::spy(\Tuleap\Tracker\DAO\TrackerArtifactSourceIdDao::class),
            $this->external_field_extractor,
            $this->private_comment_extractor,
            $this->db_connection,
        );
    }

    public function testItCallsImportFromXMLWithContentFromArchive(): void
    {
        $archive = \Mockery::mock(\Tracker_Artifact_XMLImport_XMLImportZipArchive::class);
        $archive->shouldReceive('getXML')->andReturns('<?xml version="1.0"?><artifacts />');
        $archive->shouldReceive('extractFiles')->once();
        $archive->shouldReceive('getExtractionPath')->andReturns($this->extraction_path);
        $archive->shouldReceive('cleanUp')->once();

        $importer = \Mockery::mock(
            \Tracker_Artifact_XMLImport::class,
            [
                $this->rng_validator,
                $this->artifact_creator,
                $this->new_changeset_creator,
                $this->formelement_factory,
                $this->xml_import_helper,
                $this->static_value_dao,
                $this->logger,
                false,
                \Mockery::spy(\Tracker_ArtifactFactory::class),
                \Mockery::spy(\Tuleap\Tracker\FormElement\Field\ArtifactLink\Type\TypeDao::class),
                Mockery::spy(XMLArtifactSourcePlatformExtractor::class),
                Mockery::spy(\Tuleap\Tracker\Artifact\ExistingArtifactSourceIdFromTrackerExtractor::class),
                Mockery::spy(\Tuleap\Tracker\DAO\TrackerArtifactSourceIdDao::class),
                $this->external_field_extractor,
                $this->private_comment_extractor,
                $this->db_connection,
            ]
        )->makePartial()->shouldAllowMockingProtectedMethods();
        $importer->shouldReceive('importFromXML')->with($this->tracker, Mockery::on(function ($element) {
            return is_a($element, SimpleXMLElement::class);
        }), $this->extraction_path, Mockery::on(function ($element) {
            return is_a($element, TrackerXmlFieldsMapping_InSamePlatform::class);
        }), Mockery::type(CreatedFileURLMapping::class), Mockery::on(function ($element) {
            return is_a($element, \Tuleap\Project\XML\Import\ImportConfig::class);
        }), Mockery::type(TrackerXmlImportConfig::class))->once();

        $user = Mockery::mock(PFUser::class);
        $user->shouldReceive('getId')->andReturn(1)->once();
        $importer->importFromArchive($this->tracker, $archive, $user);
    }

    public function testItCreatesArtifactOnTracker(): void
    {
        $bare_artifact = Mockery::spy(Artifact::class);
        $bare_artifact->shouldReceive('getTracker')->andReturn($this->tracker);

        $this->artifact_creator->shouldReceive('createBare')
            ->with($this->tracker, Mockery::any(), Mockery::any())
            ->once()
            ->andReturn($bare_artifact);

        $this->importer->importFromXML(
            $this->tracker,
            $this->buildValidXMLElement(),
            $this->extraction_path,
            new TrackerXmlFieldsMapping_InSamePlatform(),
            $this->url_mapping,
            $this->config,
            $this->tracker_xml_config
        );
    }

    public function testItCreatesArtifactWithSummaryFieldData(): void
    {
        $data          = [
            $this->summary_field_id => 'Ça marche',
        ];
        $bare_artifact = Mockery::spy(Artifact::class);
        $bare_artifact->shouldReceive('getTracker')->andReturn($this->tracker);

        $this->artifact_creator->shouldReceive('createBare')
            ->with($this->tracker, Mockery::any(), Mockery::any())
            ->once()
            ->andReturn($bare_artifact);

        $this->artifact_creator->shouldReceive('createFirstChangeset')
            ->with(
                Mockery::any(),
                $data,
                Mockery::any(),
                Mockery::any(),
                false,
                Mockery::any(),
                Mockery::type(TrackerXmlImportConfig::class)
            )
            ->once()
            ->andReturn(Mockery::spy(\Tracker_Artifact_Changeset::class));

        $this->importer->importFromXML(
            $this->tracker,
            $this->buildValidXMLElement(),
            $this->extraction_path,
            new TrackerXmlFieldsMapping_InSamePlatform(),
            $this->url_mapping,
            $this->config,
            $this->tracker_xml_config
        );
    }

    public function testItCreatedArtifactWithSubmitter(): void
    {
        $bare_artifact = Mockery::spy(Artifact::class);
        $bare_artifact->shouldReceive('getTracker')->andReturn($this->tracker);

        $this->artifact_creator->shouldReceive('createBare')
            ->with($this->tracker, $this->john_doe, Mockery::any())
            ->once()
            ->andReturn($bare_artifact);

        $this->importer->importFromXML(
            $this->tracker,
            $this->buildValidXMLElement(),
            $this->extraction_path,
            new TrackerXmlFieldsMapping_InSamePlatform(),
            $this->url_mapping,
            $this->config,
            $this->tracker_xml_config
        );
    }

    public function testItCreatesArtifactAtDate(): void
    {
        $bare_artifact = Mockery::spy(Artifact::class);
        $bare_artifact->shouldReceive('getTracker')->andReturn($this->tracker);

        $expected_time = strtotime('2014-01-15T10:38:06+01:00');
        $this->artifact_creator->shouldReceive('createBare')
            ->with($this->tracker, Mockery::any(), $expected_time)
            ->once()
            ->andReturn($bare_artifact);

        $this->importer->importFromXML(
            $this->tracker,
            $this->buildValidXMLElement(),
            $this->extraction_path,
            new TrackerXmlFieldsMapping_InSamePlatform(),
            $this->url_mapping,
            $this->config,
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
        $this->artifact_creator->shouldReceive('createFirstChangeset')
            ->with(
                Mockery::any(),
                Mockery::any(),
                Mockery::any(),
                Mockery::any(),
                false,
                Mockery::any(),
                Mockery::type(TrackerXmlImportConfig::class)
            )
            ->once()
            ->andReturn(\Mockery::spy(\Tracker_Artifact_Changeset::class));

        $this->new_changeset_creator->shouldReceive('create')->times(2);

        $this->new_changeset_creator->shouldReceive('create')
            ->with(Mockery::any(), Mockery::any(), 'Some text', Mockery::any(), Mockery::any(), Mockery::any(), Tracker_Artifact_Changeset_Comment::TEXT_COMMENT, Mockery::any(), [])
            ->andReturn(\Mockery::spy(\Tracker_Artifact_Changeset::class));

        $this->new_changeset_creator->shouldReceive('create')
            ->with(Mockery::any(), Mockery::any(), '<p>Some text</p>', Mockery::any(), Mockery::any(), Mockery::any(), Tracker_Artifact_Changeset_Comment::HTML_COMMENT, Mockery::any(), [])
            ->andReturn(\Mockery::spy(\Tracker_Artifact_Changeset::class));

        $this->artifact->shouldReceive('getTracker')->andReturn($this->tracker);

        $this->artifact_creator->shouldReceive('create')->andReturns($this->artifact);
        $this->artifact_creator->shouldReceive('createBare')->andReturns($this->artifact);

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
            $this->config,
            $this->tracker_xml_config
        );
    }

    public function testItCreatesTheCommentsWithUpdates(): void
    {
        $this->artifact_creator->shouldReceive('createFirstChangeset')
            ->with(Mockery::any(), Mockery::any(), Mockery::any(), Mockery::any(), false, Mockery::any(), Mockery::type(TrackerXmlImportConfig::class))
            ->once()
            ->andReturn(\Mockery::spy(\Tracker_Artifact_Changeset::class));
        $this->artifact->shouldReceive('getTracker')->andReturn($this->tracker);

        $this->artifact_creator->shouldReceive('create')->andReturns($this->artifact);
        $this->artifact_creator->shouldReceive('createBare')->andReturns($this->artifact);

        $changeset = \Mockery::spy(\Tracker_Artifact_Changeset::class);

        $this->new_changeset_creator->shouldReceive('create')
            ->withArgs(function (NewChangeset $new_changeset) {
                $comment = $new_changeset->getComment();
                if ($comment->getBody() !== 'Some text') {
                    return false;
                }
                if ((string) $comment->getFormat() !== \Tracker_Artifact_Changeset_Comment::TEXT_COMMENT) {
                    return false;
                }
                return true;
            })
            ->once()
            ->andReturn($changeset);

        $changeset->shouldReceive('updateCommentWithoutNotification')->with('<p>Some text</p>', $this->john_doe, Tracker_Artifact_Changeset_Comment::HTML_COMMENT, strtotime('2014-01-15T11:23:50+01:00'), [])->once();
        $changeset->shouldReceive('getArtifact')->once()->andReturn(Mockery::mock(Artifact::class));

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

        $this->importer->importFromXML(
            $this->tracker,
            $xml_element,
            $this->extraction_path,
            new TrackerXmlFieldsMapping_InSamePlatform(),
            $this->url_mapping,
            $this->config,
            $this->tracker_xml_config
        );
    }

    public function testItCreatesPrivateCommentWithUpdates(): void
    {
        $my_group       = Mockery::mock(ProjectUGroup::class);
        $my_other_group = Mockery::mock(ProjectUGroup::class);
        $my_best_group  = Mockery::mock(ProjectUGroup::class);

        $this->private_comment_extractor
            ->shouldReceive('extractUGroupsFromXML')
            ->with(
                Mockery::any(),
                Mockery::on(
                    function (\SimpleXMLElement $comment): bool {
                        return (string) $comment->body === "Some text" &&
                               (string) $comment->private_ugroups->ugroup[0] === "my_group" &&
                               (string) $comment->private_ugroups->ugroup[1] === "my_other_group";
                    }
                )
            )
            ->andReturn([$my_group, $my_other_group])
            ->once();

        $this->private_comment_extractor
            ->shouldReceive('extractUGroupsFromXML')
            ->with(
                Mockery::any(),
                Mockery::on(
                    function (\SimpleXMLElement $comment): bool {
                        return (string) $comment->body === "New comment update" &&
                               (string) $comment->private_ugroups->ugroup[0] === "my_group" &&
                               (string) $comment->private_ugroups->ugroup[1] === "the_best_group";
                    }
                )
            )
            ->andReturn([$my_group, $my_best_group])
            ->once();

        $this->artifact_creator->shouldReceive('createFirstChangeset')
            ->with(
                Mockery::any(),
                Mockery::any(),
                Mockery::any(),
                Mockery::any(),
                false,
                Mockery::any(),
                Mockery::type(TrackerXmlImportConfig::class)
            )
            ->once()
            ->andReturn(\Mockery::spy(\Tracker_Artifact_Changeset::class));
        $this->artifact->shouldReceive('getTracker')->andReturn($this->tracker);

        $this->artifact_creator->shouldReceive('create')->andReturns($this->artifact);
        $this->artifact_creator->shouldReceive('createBare')->andReturns($this->artifact);

        $changeset = \Mockery::spy(\Tracker_Artifact_Changeset::class);

        $this->new_changeset_creator->shouldReceive('create')
            ->withArgs(
                function (NewChangeset $new_changeset) use ($my_other_group, $my_group) {
                    $comment = $new_changeset->getComment();
                    if ($comment->getBody() !== 'Some text') {
                        return false;
                    }
                    if ((string) $comment->getFormat() !== \Tracker_Artifact_Changeset_Comment::TEXT_COMMENT) {
                        return false;
                    }
                    if ($comment->getUserGroupsThatAreAllowedToSee() !== [$my_group, $my_other_group]) {
                        return false;
                    }
                    return true;
                }
            )
            ->once()
            ->andReturn($changeset);

        $changeset->shouldReceive('updateCommentWithoutNotification')
            ->with(
                'New comment update',
                $this->john_doe,
                Tracker_Artifact_Changeset_Comment::HTML_COMMENT,
                strtotime('2014-01-15T11:23:50+01:00'),
                [$my_group, $my_best_group]
            )
            ->once();
        $changeset->shouldReceive('getArtifact')->once()->andReturn(Mockery::mock(Artifact::class));

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

        $this->importer->importFromXML(
            $this->tracker,
            $xml_element,
            $this->extraction_path,
            new TrackerXmlFieldsMapping_InSamePlatform(),
            $this->url_mapping,
            $this->config,
            $this->tracker_xml_config
        );
    }

    public function testItThrowAnExceptionWhenFieldDoesntExist(): void
    {
        $artifact = \Mockery::spy(\Tuleap\Tracker\Artifact\Artifact::class);
        $artifact->shouldReceive('getId')->andReturn(101);
        $artifact->shouldReceive('getTracker')->andReturn(Mockery::spy(Tracker::class));

        $this->artifact_creator->shouldReceive('createBare')->once()->andReturn($artifact);

        $this->logger->shouldReceive('log')->with(\Psr\Log\LogLevel::WARNING, Mockery::any(), Mockery::any())->once();

        $xml_element = new SimpleXMLElement('<?xml version="1.0"?>
            <artifacts>
              <artifact id="4918">
                <changeset>
                  <submitted_by format="username">john_doe</submitted_by>
                  <submitted_on format="ISO8601">2014-01-15T10:38:06+01:00</submitted_on>
                  <field_change field_name="summaro" type="string">
                    <value>Ça marche</value>
                  </field_change>
                </changeset>
              </artifact>
            </artifacts>');

        $this->importer->importFromXML(
            $this->tracker,
            $xml_element,
            $this->extraction_path,
            new TrackerXmlFieldsMapping_InSamePlatform(),
            $this->url_mapping,
            $this->config,
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

        $artifact = \Mockery::spy(\Tuleap\Tracker\Artifact\Artifact::class);
        $artifact->shouldReceive('getId')->andReturn(101);
        $artifact->shouldReceive('getTracker')->andReturn(Mockery::spy(Tracker::class));

        $this->artifact_creator->shouldReceive('createBare')
            ->with(
                $this->tracker,
                Mockery::on(function ($user) {
                    return ($user instanceof PFUser && $user->isAnonymous() && $user->getEmail() == 'jmalko');
                }),
                Mockery::any()
            )
            ->once()
            ->andReturn($artifact);

        $this->importer->importFromXML(
            $this->tracker,
            $xml_element,
            $this->extraction_path,
            new TrackerXmlFieldsMapping_InSamePlatform(),
            $this->url_mapping,
            $this->config,
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

        $this->user_manager->shouldReceive('getUserByIdentifier')
            ->with('id:700')
            ->atLeast()
            ->once()
            ->andReturn($this->john_doe);

        $artifact = \Mockery::spy(\Tuleap\Tracker\Artifact\Artifact::class);
        $artifact->shouldReceive('getId')->andReturn(101);
        $artifact->shouldReceive('getTracker')->andReturn(Mockery::spy(Tracker::class));

        $this->artifact_creator->shouldReceive('createBare')
            ->with(
                $this->tracker,
                $this->john_doe,
                Mockery::any()
            )
            ->once()
            ->andReturn($artifact);

        $this->importer->importFromXML(
            $this->tracker,
            $xml_element,
            $this->extraction_path,
            new TrackerXmlFieldsMapping_InSamePlatform(),
            $this->url_mapping,
            $this->config,
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

        $this->user_manager->shouldReceive('getUserByIdentifier')
            ->with('ldapId:uid=jo,ou=people,dc=example,dc=com')
            ->atLeast()
            ->once()
            ->andReturn($this->john_doe);

        $artifact = \Mockery::spy(\Tuleap\Tracker\Artifact\Artifact::class);
        $artifact->shouldReceive('getId')->andReturn(101);
        $artifact->shouldReceive('getTracker')->andReturn(Mockery::spy(Tracker::class));

        $this->artifact_creator->shouldReceive('createBare')
            ->with(
                $this->tracker,
                $this->john_doe,
                Mockery::any()
            )
            ->once()
            ->andReturn($artifact);

        $this->importer->importFromXML(
            $this->tracker,
            $xml_element,
            $this->extraction_path,
            new TrackerXmlFieldsMapping_InSamePlatform(),
            $this->url_mapping,
            $this->config,
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

        $this->user_manager->shouldReceive('getUserByIdentifier')
            ->with('email:jo@example.com')
            ->atLeast()
            ->once()
            ->andReturn($this->john_doe);

        $artifact = \Mockery::spy(\Tuleap\Tracker\Artifact\Artifact::class);
        $artifact->shouldReceive('getId')->andReturn(101);
        $artifact->shouldReceive('getTracker')->andReturn(Mockery::spy(Tracker::class));

        $this->artifact_creator->shouldReceive('createBare')
            ->with(
                $this->tracker,
                $this->john_doe,
                Mockery::any()
            )
            ->once()
            ->andReturn($artifact);

        $this->importer->importFromXML(
            $this->tracker,
            $xml_element,
            $this->extraction_path,
            new TrackerXmlFieldsMapping_InSamePlatform(),
            $this->url_mapping,
            $this->config,
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
        $this->artifact_creator->shouldReceive('createBare')->once()->andReturn($this->artifact);

        $this->artifact_creator->shouldReceive('createFirstChangeset')
            ->once()
            ->andReturn(Mockery::spy(Tracker_Artifact_Changeset::class));

        $this->artifact->shouldReceive('getId')->andReturn(101);
        $this->artifact->shouldReceive('getTracker')->andReturn(Mockery::spy(Tracker::class));

        $this->artifact_creator->shouldReceive('create')->andReturns(\Mockery::spy(\Tuleap\Tracker\Artifact\Artifact::class));

        $this->new_changeset_creator->shouldReceive('create')->times(1);

        $this->importer->importFromXML(
            $this->tracker,
            $this->buildMultipleChangesetXMLElement(),
            $this->extraction_path,
            new TrackerXmlFieldsMapping_InSamePlatform(),
            $this->url_mapping,
            $this->config,
            $this->tracker_xml_config
        );
    }

    public function testItCreatesTheNewChangesetWithSummaryValue(): void
    {
        $data = [
            $this->summary_field_id => '^Wit updates',
        ];

        $this->artifact_creator->shouldReceive('createBare')->once()->andReturn($this->artifact);

        $this->artifact_creator->shouldReceive('createFirstChangeset')
            ->once()
            ->with(Mockery::any(), Mockery::any(), Mockery::any(), Mockery::any(), false, Mockery::any(), Mockery::type(TrackerXmlImportConfig::class))
            ->andReturn(Mockery::spy(Tracker_Artifact_Changeset::class));

        $this->artifact->shouldReceive('getId')->andReturn(101);
        $this->artifact->shouldReceive('getTracker')->andReturn(Mockery::spy(Tracker::class));

        $this->artifact_creator->shouldReceive('create')->andReturns(\Mockery::spy(\Tuleap\Tracker\Artifact\Artifact::class));

        $this->new_changeset_creator->shouldReceive('create')
            ->once()
            ->withArgs(function (NewChangeset $new_changeset) use ($data) {
                if ($new_changeset->getFieldsData() !== $data) {
                    return false;
                }
                return true;
            })
            ->andReturn(\Mockery::spy(\Tracker_Artifact_Changeset::class));

        $this->importer->importFromXML(
            $this->tracker,
            $this->buildMultipleChangesetXMLElement(),
            $this->extraction_path,
            new TrackerXmlFieldsMapping_InSamePlatform(),
            $this->url_mapping,
            $this->config,
            $this->tracker_xml_config
        );
    }

    public function testItCreatesTheNewChangesetWithSubmitter(): void
    {
        $this->artifact_creator->shouldReceive('createBare')->once()->andReturn($this->artifact);

        $this->artifact_creator->shouldReceive('createFirstChangeset')
            ->once()
            ->with(Mockery::any(), Mockery::any(), Mockery::any(), Mockery::any(), false, Mockery::any(), Mockery::type(TrackerXmlImportConfig::class))
            ->andReturn(Mockery::spy(Tracker_Artifact_Changeset::class));

        $this->artifact->shouldReceive('getId')->andReturn(101);
        $this->artifact->shouldReceive('getTracker')->andReturn(Mockery::spy(Tracker::class));

        $this->artifact_creator->shouldReceive('create')->andReturns(\Mockery::spy(\Tuleap\Tracker\Artifact\Artifact::class));

        $this->new_changeset_creator->shouldReceive('create')
            ->once()
            ->withArgs(function (NewChangeset $new_changeset) {
                if ($new_changeset->getSubmitter() !== $this->john_doe) {
                    return false;
                }
                return true;
            })
            ->andReturn(\Mockery::spy(\Tracker_Artifact_Changeset::class));

        $this->importer->importFromXML(
            $this->tracker,
            $this->buildMultipleChangesetXMLElement(),
            $this->extraction_path,
            new TrackerXmlFieldsMapping_InSamePlatform(),
            $this->url_mapping,
            $this->config,
            $this->tracker_xml_config
        );
    }

    public function testItCreatesTheNewChangesetWithoutNotification(): void
    {
        $this->artifact_creator->shouldReceive('createBare')->once()->andReturn($this->artifact);

        $this->artifact_creator->shouldReceive('createFirstChangeset')
            ->once()
            ->with(Mockery::any(), Mockery::any(), Mockery::any(), Mockery::any(), false, Mockery::any(), Mockery::type(TrackerXmlImportConfig::class))
            ->andReturn(Mockery::spy(Tracker_Artifact_Changeset::class));

        $this->artifact->shouldReceive('getId')->andReturn(101);
        $this->artifact->shouldReceive('getTracker')->andReturn(Mockery::spy(Tracker::class));

        $this->artifact_creator->shouldReceive('create')->andReturns(\Mockery::spy(\Tuleap\Tracker\Artifact\Artifact::class));

        $this->new_changeset_creator->shouldReceive('create')
            ->once()
            ->andReturn(\Mockery::spy(\Tracker_Artifact_Changeset::class));

        $this->importer->importFromXML(
            $this->tracker,
            $this->buildMultipleChangesetXMLElement(),
            $this->extraction_path,
            new TrackerXmlFieldsMapping_InSamePlatform(),
            $this->url_mapping,
            $this->config,
            $this->tracker_xml_config
        );
    }

    public function testItCreatesTheChangesetsAccordingToDates(): void
    {
        $this->artifact_creator->shouldReceive('createBare')
            ->with($this->tracker, Mockery::any(), strtotime('2014-01-15T10:38:06+01:00'))
            ->once()
            ->andReturn($this->artifact);

        $this->artifact_creator->shouldReceive('createFirstChangeset')
            ->once()
            ->with(Mockery::any(), Mockery::any(), Mockery::any(), strtotime('2014-01-15T10:38:06+01:00'), false, Mockery::any(), Mockery::type(TrackerXmlImportConfig::class))
            ->andReturn(Mockery::spy(Tracker_Artifact_Changeset::class));

        $this->artifact->shouldReceive('getId')->andReturn(101);
        $this->artifact->shouldReceive('getTracker')->andReturn(Mockery::spy(Tracker::class));

        $this->artifact_creator->shouldReceive('create')->andReturns(\Mockery::spy(\Tuleap\Tracker\Artifact\Artifact::class));

        $this->new_changeset_creator->shouldReceive('create')
            ->once()
            ->withArgs(function (NewChangeset $new_changeset) {
                if ($new_changeset->getSubmissionTimestamp() !== strtotime('2014-01-15T11:03:50+01:00')) {
                    return false;
                }
                return true;
            })
            ->andReturn(\Mockery::spy(\Tracker_Artifact_Changeset::class));

        $this->importer->importFromXML(
            $this->tracker,
            $this->buildMultipleChangesetXMLElement(),
            $this->extraction_path,
            new TrackerXmlFieldsMapping_InSamePlatform(),
            $this->url_mapping,
            $this->config,
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

        $this->artifact_creator->shouldReceive('createBare')
            ->with($this->tracker, Mockery::any(), strtotime('2014-01-15T10:38:06+01:00'))
            ->once()
            ->andReturn($this->artifact);

        $this->artifact_creator->shouldReceive('createFirstChangeset')
            ->once()
            ->with(Mockery::any(), Mockery::any(), Mockery::any(), strtotime('2014-01-15T10:38:06+01:00'), false, Mockery::any(), Mockery::type(TrackerXmlImportConfig::class))
            ->andReturn(Mockery::spy(Tracker_Artifact_Changeset::class));

        $this->artifact->shouldReceive('getId')->andReturn(101);
        $this->artifact->shouldReceive('getTracker')->andReturn(Mockery::spy(Tracker::class));

        $this->artifact_creator->shouldReceive('create')->andReturns(\Mockery::spy(\Tuleap\Tracker\Artifact\Artifact::class));

        $this->new_changeset_creator->shouldReceive('create')
            ->once()
            ->withArgs(function (NewChangeset $new_changeset) {
                if ($new_changeset->getSubmissionTimestamp() !== strtotime('2014-01-15T11:03:50+01:00')) {
                    return false;
                }
                return true;
            })
            ->andReturn(\Mockery::spy(\Tracker_Artifact_Changeset::class));

        $this->importer->importFromXML(
            $this->tracker,
            $xml_element,
            $this->extraction_path,
            new TrackerXmlFieldsMapping_InSamePlatform(),
            $this->url_mapping,
            $this->config,
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

        $this->artifact_creator->shouldReceive('createBare')
            ->with($this->tracker, Mockery::any(), strtotime('2014-01-15T10:38:06+01:00'))
            ->once()
            ->andReturn($this->artifact);

        $this->artifact_creator->shouldReceive('createFirstChangeset')
            ->once()
            ->with(
                Mockery::any(),
                Mockery::on(function ($data) {
                    return $data[$this->summary_field_id] === 'First';
                }),
                Mockery::any(),
                strtotime('2014-01-15T10:38:06+01:00'),
                Mockery::any(),
                Mockery::any(),
                Mockery::type(TrackerXmlImportConfig::class)
            )
            ->andReturn(Mockery::spy(Tracker_Artifact_Changeset::class));
        $this->artifact->shouldReceive('getId')->andReturn(101);
        $this->artifact->shouldReceive('getTracker')->andReturn(Mockery::spy(Tracker::class));

        $this->artifact_creator->shouldReceive('create')->andReturns(\Mockery::spy(\Tuleap\Tracker\Artifact\Artifact::class));

        $this->new_changeset_creator->shouldReceive('create')->times(3);

        $this->new_changeset_creator->shouldReceive('create')
            ->with(
                $this->artifact,
                Mockery::on(function ($data) {
                    return $data[$this->summary_field_id] === 'Second';
                }),
                Mockery::any(),
                Mockery::any(),
                strtotime('2014-01-15T11:03:50+01:00'),
                Mockery::any(),
                Mockery::any(),
                Mockery::any(),
                []
            )
            ->andReturn(\Mockery::spy(\Tracker_Artifact_Changeset::class));

        $this->new_changeset_creator->shouldReceive('create')
            ->with(
                $this->artifact,
                Mockery::on(function ($data) {
                    return $data[$this->summary_field_id] === 'Third';
                }),
                Mockery::any(),
                Mockery::any(),
                strtotime('2014-01-15T11:03:50+01:00'),
                Mockery::any(),
                Mockery::any(),
                Mockery::any(),
                []
            )
            ->andReturn(\Mockery::spy(\Tracker_Artifact_Changeset::class));

        $this->new_changeset_creator->shouldReceive('create')
            ->with(
                $this->artifact,
                Mockery::on(function ($data) {
                    return $data[$this->summary_field_id] === 'Fourth';
                }),
                Mockery::any(),
                Mockery::any(),
                strtotime('2014-01-15T11:51:50+01:00'),
                Mockery::any(),
                Mockery::any(),
                Mockery::any(),
                []
            )
            ->andReturn(\Mockery::spy(\Tracker_Artifact_Changeset::class));

        $this->importer->importFromXML(
            $this->tracker,
            $xml_element,
            $this->extraction_path,
            new TrackerXmlFieldsMapping_InSamePlatform(),
            $this->url_mapping,
            $this->config,
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

        $artifact = \Mockery::spy(\Tuleap\Tracker\Artifact\Artifact::class);
        $artifact->shouldReceive('getId')->andReturn(101);
        $artifact->shouldReceive('getTracker')->andReturn(Mockery::spy(Tracker::class));

        $this->artifact_creator->shouldReceive('createBare')
            ->with($this->tracker, Mockery::any(), strtotime('2014-01-15T10:38:06+01:00'))
            ->once()
            ->andReturn($artifact);

        $this->artifact_creator->shouldReceive('createBare')
            ->with($this->tracker, Mockery::any(), strtotime('2014-01-16T11:38:06+01:00'))
            ->once()
            ->andReturn($artifact);

        $this->importer->importFromXML(
            $this->tracker,
            $xml_element,
            $this->extraction_path,
            new TrackerXmlFieldsMapping_InSamePlatform(),
            $this->url_mapping,
            $this->config,
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

        $this->artifact_creator->shouldReceive('createBare')
            ->with($this->tracker, Mockery::any(), strtotime('2014-01-15T10:38:06+01:00'))
            ->andReturn(ArtifactTestBuilder::anArtifact(101)->inTracker($this->tracker)->build());

        $this->artifact_creator->shouldReceive('createBare')
            ->with($this->tracker, Mockery::any(), strtotime('2014-01-16T11:38:06+01:00'))
            ->andReturn(ArtifactTestBuilder::anArtifact(102)->inTracker($this->tracker)->build());

        $this->db_connection->expects(self::exactly(2))->method('reconnectAfterALongRunningProcess');

        $this->importer->importFromXML(
            $this->tracker,
            $xml_element,
            $this->extraction_path,
            new TrackerXmlFieldsMapping_InSamePlatform(),
            $this->url_mapping,
            $this->config,
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

        $this->artifact_creator->shouldReceive('createBare')
            ->with($this->tracker, Mockery::any(), Mockery::any())
            ->once()
            ->andReturn($this->artifact);

        $this->artifact_creator->shouldReceive('createFirstChangeset')
            ->once()
            ->with(Mockery::any(), $data, Mockery::any(), Mockery::any(), false, Mockery::any(), Mockery::type(TrackerXmlImportConfig::class))
            ->andReturn(Mockery::spy(Tracker_Artifact_Changeset::class));

        $this->artifact->shouldReceive('getId')->andReturn(101);
        $this->artifact->shouldReceive('getTracker')->andReturn(Mockery::spy(Tracker::class));

        $this->new_changeset_creator->shouldReceive('create')->andReturns(
            \Mockery::spy(\Tracker_Artifact_Changeset::class)
        );

        $this->importer->importFromXML(
            $this->tracker,
            $this->buildXMLElementWithAttachment(),
            $this->extraction_path,
            new TrackerXmlFieldsMapping_InSamePlatform(),
            $this->url_mapping,
            $this->config,
            $this->tracker_xml_config
        );
    }

    public function testItCreatesAChangesetWithOneFileElement(): void
    {
        $this->artifact_creator->shouldReceive('create')->andReturns(\Mockery::spy(\Tuleap\Tracker\Artifact\Artifact::class));
        $file_field = Mockery::mock(Tracker_FormElement_Field_File::class);
        $file_field->shouldReceive('getId')->andReturn(51);
        $this->formelement_factory->shouldReceive('getUsedFieldByName')->with($this->tracker_id, 'attachment')->andReturns($file_field);

        touch($this->extraction_path . '/34_File33.png');

        $this->artifact_creator->shouldReceive('createBare')
            ->with($this->tracker, Mockery::any(), Mockery::any())
            ->once()
            ->andReturn($this->artifact);

        $this->artifact->shouldReceive('getId')->andReturn(101);
        $this->artifact->shouldReceive('getTracker')->andReturn(Mockery::spy(Tracker::class));

        $this->new_changeset_creator->shouldReceive('create')->andReturns(
            \Mockery::spy(\Tracker_Artifact_Changeset::class)
        );

        $this->artifact_creator->shouldReceive('createFirstChangeset')
            ->with(
                Mockery::any(),
                Mockery::on(function ($data) {
                    return $data[$this->summary_field_id] === 'Newly submitted' &&
                        $data[51][0]['name'] === 'A.png' &&
                        $data[51][0]['submitted_by']->getEmail() === 'manuel';
                }),
                Mockery::any(),
                Mockery::any(),
                false,
                Mockery::any()
            )
            ->andReturn(Mockery::spy(Tracker_Artifact_Changeset::class));

        $this->importer->importFromXML(
            $this->tracker,
            $this->buildXMLElementWithAttachment(),
            $this->extraction_path,
            new TrackerXmlFieldsMapping_InSamePlatform(),
            $this->url_mapping,
            $this->config,
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

        $artifact = \Mockery::spy(\Tuleap\Tracker\Artifact\Artifact::class);
        $artifact->shouldReceive('getId')->andReturn(101);
        $artifact->shouldReceive('getTracker')->andReturn(Mockery::spy(Tracker::class));

        $this->artifact_creator->shouldReceive('createBare')
            ->with($this->tracker, Mockery::any(), Mockery::any())
            ->once()
            ->andReturn($artifact);

        $this->artifact_creator->shouldReceive('createFirstChangeset')
            ->once()
            ->with(Mockery::any(), $data, Mockery::any(), Mockery::any(), false, Mockery::any(), Mockery::type(TrackerXmlImportConfig::class))
            ->andReturn(Mockery::spy(Tracker_Artifact_Changeset::class));

        $this->importer->importFromXML(
            $this->tracker,
            $xml_element,
            $this->extraction_path,
            new TrackerXmlFieldsMapping_InSamePlatform(),
            $this->url_mapping,
            $this->config,
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

        $file_field = Mockery::mock(Tracker_FormElement_Field_File::class);
        $file_field->shouldReceive('getId')->andReturn(51);

        $this->formelement_factory->shouldReceive('getUsedFieldByName')->with($this->tracker_id, 'attachment')->andReturns($file_field);

        $artifact = \Mockery::spy(\Tuleap\Tracker\Artifact\Artifact::class);
        $artifact->shouldReceive('getId')->andReturn(101);
        $artifact->shouldReceive('getTracker')->andReturn(Mockery::spy(Tracker::class));

        $this->artifact_creator->shouldReceive('createBare')
            ->with($this->tracker, Mockery::any(), Mockery::any())
            ->once()
            ->andReturn($artifact);

        $this->artifact_creator->shouldReceive('createFirstChangeset')
            ->with(
                Mockery::any(),
                Mockery::on(function ($data) {
                    return $data[$this->summary_field_id] === 'Newly submitted' &&
                           $data[51][0]['name'] === 'A.png' &&
                           $data[51][0]['submitted_by']->getEmail() === 'manuel' &&
                           $data[51][1]['name'] === 'B.pdf' &&
                           $data[51][1]['submitted_by']->getEmail() === 'manuel';
                }),
                Mockery::any(),
                Mockery::any(),
                false,
                Mockery::any(),
                Mockery::type(TrackerXmlImportConfig::class)
            )
            ->andReturn(Mockery::spy(Tracker_Artifact_Changeset::class));

        $this->importer->importFromXML(
            $this->tracker,
            $xml_element,
            $this->extraction_path,
            new TrackerXmlFieldsMapping_InSamePlatform(),
            $this->url_mapping,
            $this->config,
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

        $file_field = Mockery::mock(Tracker_FormElement_Field_File::class);
        $file_field->shouldReceive('getId')->andReturn(51);

        $this->formelement_factory->shouldReceive('getUsedFieldByName')->with($this->tracker_id, 'attachment')->andReturns($file_field);

        $artifact = \Mockery::spy(\Tuleap\Tracker\Artifact\Artifact::class);
        $artifact->shouldReceive('getId')->andReturn(101);
        $artifact->shouldReceive('getTracker')->andReturn(Mockery::spy(Tracker::class));

        $this->artifact_creator->shouldReceive('createBare')
            ->with($this->tracker, Mockery::any(), Mockery::any())
            ->once()
            ->andReturn($artifact);

        $this->artifact_creator->shouldReceive('createFirstChangeset')
            ->with(
                Mockery::any(),
                Mockery::on(function ($data) {
                    return $data[$this->summary_field_id] === 'Newly submitted' &&
                           $data[51][0]['name'] === 'A.png' &&
                           $data[51][0]['submitted_by']->getEmail() === 'manuel';
                }),
                Mockery::any(),
                Mockery::any(),
                false,
                Mockery::any(),
                Mockery::type(TrackerXmlImportConfig::class)
            )
            ->andReturn(Mockery::spy(Tracker_Artifact_Changeset::class));

        $this->new_changeset_creator->shouldReceive('create')
            ->with(
                $artifact,
                Mockery::on(function ($data) {
                    return $data[51][0]['name'] === 'B.pdf' &&
                           $data[51][0]['submitted_by']->getEmail() === 'manuel';
                }),
                Mockery::any(),
                Mockery::any(),
                Mockery::any(),
                Mockery::any(),
                Mockery::any(),
                Mockery::any(),
                []
            )
            ->andReturn(\Mockery::spy(\Tracker_Artifact_Changeset::class));

        $this->importer->importFromXML(
            $this->tracker,
            $xml_element,
            $this->extraction_path,
            new TrackerXmlFieldsMapping_InSamePlatform(),
            $this->url_mapping,
            $this->config,
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
        $this->artifact_creator->shouldReceive('createBare')->andReturns($this->artifact);
        $this->artifact_creator->shouldReceive('createFirstChangeset')->andReturns(Mockery::spy(\Tracker_Artifact_Changeset::class));

        $this->artifact->shouldReceive('getId')->andReturn(101);
        $this->artifact->shouldReceive('getTracker')->andReturn(Mockery::spy(Tracker::class));

        $open_list_field = \Mockery::spy(\Tracker_FormElement_Field_OpenList::class);
        $open_list_field->shouldReceive('getId')->andReturns(369);
        $open_list_field->shouldReceive('validateField')->andReturns(true);
        $open_list_field->shouldReceive('getFieldData')->with('homer')->once();
        $open_list_field->shouldReceive('getFieldData')->with('jeanjean')->once();

        $this->formelement_factory->shouldReceive('getUsedFieldByName')->with($this->tracker_id, 'cc')->andReturns(
            $open_list_field
        );

        $this->importer->importFromXML(
            $this->tracker,
            $this->buildCCListXMLElement(),
            $this->extraction_path,
            new TrackerXmlFieldsMapping_InSamePlatform(),
            $this->url_mapping,
            $this->config,
            $this->tracker_xml_config
        );
    }

    public function testItCreatesArtifactWithCCFieldData(): void
    {
        $open_list_field = \Mockery::spy(\Tracker_FormElement_Field_OpenList::class);
        $open_list_field->shouldReceive('getId')->andReturns(369);
        $open_list_field->shouldReceive('validateField')->andReturns(true);
        $open_list_field->shouldReceive('getFieldData')->with('homer')->once()->andReturn('!112');
        $open_list_field->shouldReceive('getFieldData')->with('jeanjean')->once()->andReturn('!113');

        $this->artifact->shouldReceive('getId')->andReturn(101);
        $this->artifact->shouldReceive('getTracker')->andReturn(Mockery::spy(Tracker::class));

        $this->formelement_factory->shouldReceive('getUsedFieldByName')->with($this->tracker_id, 'cc')->andReturns($open_list_field);

        $this->artifact_creator->shouldReceive('createBare')->once()->andReturn($this->artifact);

        $this->artifact_creator->shouldReceive('createFirstChangeset')
            ->once()
            ->with(
                Mockery::any(),
                Mockery::on(function ($data) {
                    return $data[369] === '!112,!113';
                }),
                Mockery::any(),
                Mockery::any(),
                false,
                Mockery::any(),
                Mockery::type(TrackerXmlImportConfig::class)
            )
            ->andReturn(Mockery::spy(Tracker_Artifact_Changeset::class));

        $this->importer->importFromXML(
            $this->tracker,
            $this->buildCCListXMLElement(),
            $this->extraction_path,
            new TrackerXmlFieldsMapping_InSamePlatform(),
            $this->url_mapping,
            $this->config,
            $this->tracker_xml_config
        );
    }

    public function testItCreatesArtifactWithPermsFieldData(): void
    {
        $perms_field = \Mockery::spy(\Tracker_FormElement_Field_PermissionsOnArtifact::class);
        $perms_field->shouldReceive('getId')->andReturns(369);
        $perms_field->shouldReceive('validateField')->andReturns(true);

        $this->formelement_factory->shouldReceive('getUsedFieldByName')->with($this->tracker_id, 'permissions_on_artifact')->andReturns($perms_field);

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

        $this->artifact->shouldReceive('getId')->andReturn(101);
        $this->artifact->shouldReceive('getTracker')->andReturn(Mockery::spy(Tracker::class));

        $this->artifact_creator->shouldReceive('createBare')->once()->andReturn($this->artifact);

        $this->artifact_creator->shouldReceive('createFirstChangeset')
            ->once()
            ->with(
                Mockery::any(),
                Mockery::on(function ($data) {
                    return $data[369]['use_artifact_permissions'] === 1 &&
                           $data[369]['u_groups'] === [15, 101];
                }),
                Mockery::any(),
                Mockery::any(),
                false,
                Mockery::any(),
                Mockery::type(TrackerXmlImportConfig::class)
            )
            ->andReturn(Mockery::spy(Tracker_Artifact_Changeset::class));

        $this->importer->importFromXML(
            $this->tracker,
            $xml_element,
            $this->extraction_path,
            new TrackerXmlFieldsMapping_InSamePlatform(),
            $this->url_mapping,
            $this->config,
            $this->tracker_xml_config
        );
    }

    public function testItCreatesArtifactWithTextData(): void
    {
        $text_field = \Mockery::spy(\Tracker_FormElement_Field_Text::class);
        $text_field->shouldReceive('getId')->andReturns(369);
        $text_field->shouldReceive('validateField')->andReturns(true);

        $this->formelement_factory->shouldReceive('getUsedFieldByName')->with($this->tracker_id, 'textarea')->andReturns($text_field);

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

        $this->artifact->shouldReceive('getId')->andReturn(101);
        $this->artifact->shouldReceive('getTracker')->andReturn(Mockery::spy(Tracker::class));

        $this->artifact_creator->shouldReceive('createBare')->once()->andReturn($this->artifact);

        $this->artifact_creator->shouldReceive('createFirstChangeset')
            ->once()
            ->with(
                Mockery::any(),
                Mockery::on(function ($data) {
                    return $data[369]['format'] === 'html' &&
                           $data[369]['content'] === 'test';
                }),
                Mockery::any(),
                Mockery::any(),
                false,
                Mockery::any(),
                Mockery::type(TrackerXmlImportConfig::class)
            )
            ->andReturn(Mockery::spy(Tracker_Artifact_Changeset::class));

        $this->importer->importFromXML(
            $this->tracker,
            $xml_element,
            $this->extraction_path,
            new TrackerXmlFieldsMapping_InSamePlatform(),
            $this->url_mapping,
            $this->config,
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
        $string_field = \Mockery::spy(\Tracker_FormElement_Field_String::class);
        $string_field->shouldReceive('getId')->andReturns(369);
        $string_field->shouldReceive('validateField')->andReturns(true);
        $int_field = \Mockery::spy(\Tracker_FormElement_Field_Integer::class);
        $int_field->shouldReceive('getId')->andReturns(234);
        $int_field->shouldReceive('validateField')->andReturns(true);
        $float_field = \Mockery::spy(\Tracker_FormElement_Field_Float::class);
        $float_field->shouldReceive('getId')->andReturns(347);
        $float_field->shouldReceive('validateField')->andReturns(true);
        $date_field = \Mockery::mock(\Tracker_FormElement_Field_Date::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $date_field->shouldReceive('getId')->andReturns(978);
        $date_field->shouldReceive('validateField')->andReturns(true);
        $date_field->shouldReceive('isTimeDisplayed')->andReturns(false);
        $this->formelement_factory->shouldReceive('getUsedFieldByName')->with($this->tracker_id, 'i_want_to')->andReturns($string_field);
        $this->formelement_factory->shouldReceive('getUsedFieldByName')->with($this->tracker_id, 'initial_effort')->andReturns($int_field);
        $this->formelement_factory->shouldReceive('getUsedFieldByName')->with($this->tracker_id, 'remaining_effort')->andReturns($float_field);
        $this->formelement_factory->shouldReceive('getUsedFieldByName')->with($this->tracker_id, 'start_date')->andReturns($date_field);

        $this->artifact->shouldReceive('getId')->andReturn(101);
        $this->artifact->shouldReceive('getTracker')->andReturn(Mockery::spy(Tracker::class));

        $this->artifact_creator->shouldReceive('createBare')->once()->andReturn($this->artifact);

        $this->artifact_creator->shouldReceive('createFirstChangeset')
            ->once()
            ->with(
                Mockery::any(),
                Mockery::on(function ($data) {
                    return $data[369] === 'Import artifact in tracker v5' &&
                           $data[234] === '5' &&
                           $data[347] === '4.5' &&
                           $data[978] === '2014-03-20';
                }),
                Mockery::any(),
                Mockery::any(),
                false,
                Mockery::any(),
                Mockery::type(TrackerXmlImportConfig::class)
            )
            ->andReturn(Mockery::spy(Tracker_Artifact_Changeset::class));

        $this->importer->importFromXML(
            $this->tracker,
            $this->buildAlnumFieldXMLElement(),
            $this->extraction_path,
            new TrackerXmlFieldsMapping_InSamePlatform(),
            $this->url_mapping,
            $this->config,
            $this->tracker_xml_config
        );
    }

    public function testItCreatesArtifactWithAlphanumFieldDataAndTimeDisplayedDate(): void
    {
        $string_field = \Mockery::spy(\Tracker_FormElement_Field_String::class);
        $string_field->shouldReceive('getId')->andReturns(369);
        $string_field->shouldReceive('validateField')->andReturns(true);
        $int_field = \Mockery::spy(\Tracker_FormElement_Field_Integer::class);
        $int_field->shouldReceive('getId')->andReturns(234);
        $int_field->shouldReceive('validateField')->andReturns(true);
        $float_field = \Mockery::spy(\Tracker_FormElement_Field_Float::class);
        $float_field->shouldReceive('getId')->andReturns(347);
        $float_field->shouldReceive('validateField')->andReturns(true);
        $date_field = \Mockery::mock(\Tracker_FormElement_Field_Date::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $date_field->shouldReceive('getId')->andReturns(978);
        $date_field->shouldReceive('validateField')->andReturns(true);
        $date_field->shouldReceive('isTimeDisplayed')->andReturns(true);
        $this->formelement_factory->shouldReceive('getUsedFieldByName')->with($this->tracker_id, 'i_want_to')->andReturns($string_field);
        $this->formelement_factory->shouldReceive('getUsedFieldByName')->with($this->tracker_id, 'initial_effort')->andReturns($int_field);
        $this->formelement_factory->shouldReceive('getUsedFieldByName')->with($this->tracker_id, 'remaining_effort')->andReturns($float_field);
        $this->formelement_factory->shouldReceive('getUsedFieldByName')->with($this->tracker_id, 'start_date')->andReturns($date_field);

        $this->artifact->shouldReceive('getId')->andReturn(101);
        $this->artifact->shouldReceive('getTracker')->andReturn(Mockery::spy(Tracker::class));

        $this->artifact_creator->shouldReceive('createBare')->once()->andReturn($this->artifact);

        $this->artifact_creator->shouldReceive('createFirstChangeset')
            ->once()
            ->with(
                Mockery::any(),
                Mockery::on(function ($data) {
                    return $data[369] === 'Import artifact in tracker v5' &&
                           $data[234] === '5' &&
                           $data[347] === '4.5' &&
                           $data[978] === '2014-03-20 10:13';
                }),
                Mockery::any(),
                Mockery::any(),
                false,
                Mockery::any(),
                Mockery::type(TrackerXmlImportConfig::class)
            )
            ->andReturn(Mockery::spy(Tracker_Artifact_Changeset::class));

        $this->importer->importFromXML(
            $this->tracker,
            $this->buildAlnumFieldXMLElement(),
            $this->extraction_path,
            new TrackerXmlFieldsMapping_InSamePlatform(),
            $this->url_mapping,
            $this->config,
            $this->tracker_xml_config
        );
    }

    public function testItDoesntConvertEmptyDateInto70sdate(): void
    {
        $xml_element  = new SimpleXMLElement('<?xml version="1.0"?>
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
        $string_field = \Mockery::spy(\Tracker_FormElement_Field_String::class);
        $string_field->shouldReceive('getId')->andReturns(369);
        $string_field->shouldReceive('validateField')->andReturns(true);
        $int_field = \Mockery::spy(\Tracker_FormElement_Field_Integer::class);
        $int_field->shouldReceive('getId')->andReturns(234);
        $int_field->shouldReceive('validateField')->andReturns(true);
        $float_field = \Mockery::spy(\Tracker_FormElement_Field_Float::class);
        $float_field->shouldReceive('getId')->andReturns(347);
        $float_field->shouldReceive('validateField')->andReturns(true);
        $date_field = \Mockery::mock(\Tracker_FormElement_Field_Date::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $date_field->shouldReceive('getId')->andReturns(978);
        $date_field->shouldReceive('validateField')->andReturns(true);
        $this->formelement_factory->shouldReceive('getUsedFieldByName')->with($this->tracker_id, 'i_want_to')->andReturns($string_field);
        $this->formelement_factory->shouldReceive('getUsedFieldByName')->with($this->tracker_id, 'initial_effort')->andReturns($int_field);
        $this->formelement_factory->shouldReceive('getUsedFieldByName')->with($this->tracker_id, 'remaining_effort')->andReturns($float_field);
        $this->formelement_factory->shouldReceive('getUsedFieldByName')->with($this->tracker_id, 'start_date')->andReturns($date_field);

        $this->artifact->shouldReceive('getId')->andReturn(101);
        $this->artifact->shouldReceive('getTracker')->andReturn(Mockery::spy(Tracker::class));

        $this->artifact_creator->shouldReceive('createBare')->once()->andReturn($this->artifact);

        $this->artifact_creator->shouldReceive('createFirstChangeset')
            ->once()
            ->with(
                Mockery::any(),
                Mockery::on(function ($data) {
                    return $data[978] === '';
                }),
                Mockery::any(),
                Mockery::any(),
                false,
                Mockery::any(),
                Mockery::type(TrackerXmlImportConfig::class)
            )
            ->andReturn(Mockery::spy(Tracker_Artifact_Changeset::class));

        $this->importer->importFromXML(
            $this->tracker,
            $xml_element,
            $this->extraction_path,
            new TrackerXmlFieldsMapping_InSamePlatform(),
            $this->url_mapping,
            $this->config,
            $this->tracker_xml_config
        );
    }

    public function testItCreatesArtifactWithSelectboxValue(): void
    {
        $status_field = \Mockery::spy(\Tracker_FormElement_Field_String::class);
        $status_field->shouldReceive('getId')->andReturns(234);
        $status_field->shouldReceive('validateField')->andReturns(true);
        $assto_field = \Mockery::spy(\Tracker_FormElement_Field_String::class);
        $assto_field->shouldReceive('getId')->andReturns(456);
        $assto_field->shouldReceive('validateField')->andReturns(true);

        $this->formelement_factory->shouldReceive('getUsedFieldByName')->with($this->tracker_id, 'status_id')->andReturns($status_field);
        $this->formelement_factory->shouldReceive('getUsedFieldByName')->with($this->tracker_id, 'assigned_to')->andReturns($assto_field);

        $this->static_value_dao->shouldReceive('searchValueByLabel')->with(234, 'Open')->andReturns(\TestHelper::arrayToDar([
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

        $this->artifact->shouldReceive('getId')->andReturn(101);
        $this->artifact->shouldReceive('getTracker')->andReturn(Mockery::spy(Tracker::class));

        $this->artifact_creator->shouldReceive('createBare')->once()->andReturn($this->artifact);

        $this->artifact_creator->shouldReceive('createFirstChangeset')
            ->once()
            ->with(
                Mockery::any(),
                Mockery::on(function ($data) {
                    return $data[234] === [104] &&
                           $data[456] === [$this->john_doe->getId()];
                }),
                Mockery::any(),
                Mockery::any(),
                false,
                Mockery::any(),
                Mockery::type(TrackerXmlImportConfig::class)
            )
            ->andReturn(Mockery::spy(Tracker_Artifact_Changeset::class));

        $this->importer->importFromXML(
            $this->tracker,
            $xml_element,
            $this->extraction_path,
            new TrackerXmlFieldsMapping_InSamePlatform(),
            $this->url_mapping,
            $this->config,
            $this->tracker_xml_config
        );
    }

    public function testItCreatesArtifactWithAllMultiSelectboxValue(): void
    {
        $static_multi_selectbox_field = \Mockery::spy(\Tracker_FormElement_Field_MultiSelectbox::class);
        $static_multi_selectbox_field->shouldReceive('getId')->andReturns(456);
        $static_multi_selectbox_field->shouldReceive('validateField')->andReturns(true);

        $this->formelement_factory->shouldReceive('getUsedFieldByName')->with($this->tracker_id, 'multi_select_box')->andReturns($static_multi_selectbox_field);
        $this->static_value_dao->shouldReceive('searchValueByLabel')->with(456, 'UI')->andReturns(\TestHelper::arrayToDar([
            'id'    => 101,
            'label' => 'UI',
        ]));

        $this->static_value_dao->shouldReceive('searchValueByLabel')->with(456, 'Database')->andReturns(\TestHelper::arrayToDar([
            'id'    => 102,
            'label' => 'Database',
        ]));
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

        $this->artifact->shouldReceive('getId')->andReturn(101);
        $this->artifact->shouldReceive('getTracker')->andReturn(Mockery::spy(Tracker::class));

        $this->artifact_creator->shouldReceive('createBare')->once()->andReturn($this->artifact);

        $this->artifact_creator->shouldReceive('createFirstChangeset')
            ->once()
            ->with(
                Mockery::any(),
                Mockery::on(function ($data) {
                    return $data[456] === [101, 102];
                }),
                Mockery::any(),
                Mockery::any(),
                false,
                Mockery::any(),
                Mockery::type(TrackerXmlImportConfig::class)
            )
            ->andReturn(Mockery::spy(Tracker_Artifact_Changeset::class));

        $this->importer->importFromXML(
            $this->tracker,
            $xml_element,
            $this->extraction_path,
            new TrackerXmlFieldsMapping_InSamePlatform(),
            $this->url_mapping,
            $this->config,
            $this->tracker_xml_config
        );
    }

    public function testItCreatesArtifactWithAllUserMultiSelectboxValue(): void
    {
        $user_multi_selectbox_field = \Mockery::spy(\Tracker_FormElement_Field_MultiSelectbox::class);
        $user_multi_selectbox_field->shouldReceive('getId')->andReturns(456);
        $user_multi_selectbox_field->shouldReceive('validateField')->andReturns(true);

        $this->formelement_factory->shouldReceive('getUsedFieldByName')->with($this->tracker_id, 'multi_select_box_user')->andReturns($user_multi_selectbox_field);

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
            'user_id' => 101,
            'language_id' => 'en',
            'user_name' => 'jeanne',
        ]);

        $serge = new PFUser([
            'user_id' => 102,
            'language_id' => 'en',
            'user_name' => 'serge',
        ]);

        $this->user_manager->shouldReceive('getUserByIdentifier')->with('jeanne')->andReturns($jeanne);
        $this->user_manager->shouldReceive('getUserByIdentifier')->with('serge')->andReturns($serge);

        $this->artifact->shouldReceive('getId')->andReturn(101);
        $this->artifact->shouldReceive('getTracker')->andReturn(Mockery::spy(Tracker::class));

        $this->artifact_creator->shouldReceive('createBare')->once()->andReturn($this->artifact);

        $this->artifact_creator->shouldReceive('createFirstChangeset')
            ->once()
            ->with(
                Mockery::any(),
                Mockery::on(function ($data) {
                    return $data[456] === [101, 102];
                }),
                Mockery::any(),
                Mockery::any(),
                false,
                Mockery::any(),
                Mockery::type(TrackerXmlImportConfig::class)
            )
            ->andReturn(Mockery::spy(Tracker_Artifact_Changeset::class));

        $this->importer->importFromXML(
            $this->tracker,
            $xml_element,
            $this->extraction_path,
            new TrackerXmlFieldsMapping_InSamePlatform(),
            $this->url_mapping,
            $this->config,
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
        $this->artifact->shouldReceive('getId')->andReturn(101);
        $this->artifact->shouldReceive('getTracker')->andReturn(Mockery::spy(Tracker::class));
        $this->artifact_creator->shouldReceive('createBare')->once()->andReturn($this->artifact);
        $this->artifact_creator->shouldReceive('createFirstChangeset')->once()->andReturn(Mockery::spy(Tracker_Artifact_Changeset::class));

        $this->new_changeset_creator->shouldReceive('create')->andReturn(null);
        $this->new_changeset_creator->shouldReceive('create')->andReturn(\Mockery::spy(\Tracker_Artifact_Changeset::class));
        $this->new_changeset_creator->shouldReceive('create')->andReturn(\Mockery::spy(\Tracker_Artifact_Changeset::class));

        $this->importer->importFromXML(
            $this->tracker,
            $this->buildXMLElementChangesetsCreationFailure(),
            $this->extraction_path,
            new TrackerXmlFieldsMapping_InSamePlatform(),
            $this->url_mapping,
            $this->config,
            $this->tracker_xml_config
        );
    }

    public function testItCreatesTheLastChangesetEvenWhenTheIntermediateThrowsException(): void
    {
        $this->artifact->shouldReceive('getId')->andReturn(101);
        $this->artifact->shouldReceive('getTracker')->andReturn(Mockery::spy(Tracker::class));
        $this->artifact_creator->shouldReceive('createBare')->once()->andReturn($this->artifact);
        $this->artifact_creator->shouldReceive('createFirstChangeset')->once()->andReturn(Mockery::spy(Tracker_Artifact_Changeset::class));

        $this->new_changeset_creator->shouldReceive('create')->andThrow(new Exception('Bad luck'));
        $this->new_changeset_creator->shouldReceive('create')->andReturn(\Mockery::spy(\Tracker_Artifact_Changeset::class));
        $this->new_changeset_creator->shouldReceive('create')->andReturn(\Mockery::spy(\Tracker_Artifact_Changeset::class));

        $this->importer->importFromXML(
            $this->tracker,
            $this->buildXMLElementChangesetsCreationFailure(),
            $this->extraction_path,
            new TrackerXmlFieldsMapping_InSamePlatform(),
            $this->url_mapping,
            $this->config,
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

        $field = \Mockery::spy(\Tracker_FormElement_Field_ArtifactLink::class);
        $field->shouldReceive('getId')->andReturns(369);
        $field->shouldReceive('validateField')->andReturns(true);

        $this->formelement_factory->shouldReceive('getUsedFieldByName')->with($this->tracker_id, 'artlink')->andReturns($field);

        $art1 = \Mockery::spy(\Tuleap\Tracker\Artifact\Artifact::class);
        $art1->shouldReceive('getId')->andReturns(1);
        $art1->shouldReceive('getTracker')->andReturns(Mockery::spy(Tracker::class));
        $art2 = \Mockery::spy(\Tuleap\Tracker\Artifact\Artifact::class);
        $art2->shouldReceive('getId')->andReturns(2);
        $art2->shouldReceive('getTracker')->andReturns(Mockery::spy(Tracker::class));

        $this->artifact_creator->shouldReceive('createBare')->once()->andReturn($art1);
        $this->artifact_creator->shouldReceive('createBare')->once()->andReturn($art2);

        $artlink_strategy = \Mockery::mock(\Tracker_Artifact_XMLImport_XMLImportFieldStrategyArtifactLink::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $artlink_strategy->shouldReceive('getLastChangeset')->andReturns(false);

        $this->importer->importFromXML(
            $this->tracker,
            $xml_element,
            $this->extraction_path,
            new TrackerXmlFieldsMapping_InSamePlatform(),
            $this->url_mapping,
            $this->config,
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

        $field = \Mockery::spy(\Tracker_FormElement_Field_ArtifactLink::class);
        $field->shouldReceive('getId')->andReturns(369);
        $field->shouldReceive('validateField')->andReturns(true);

        $this->formelement_factory->shouldReceive('getUsedFieldByName')->with($this->tracker_id, 'artlink')->andReturns($field);

        $art1 = \Mockery::spy(\Tuleap\Tracker\Artifact\Artifact::class);
        $art1->shouldReceive('getId')->andReturns(1);
        $art1->shouldReceive('getTracker')->andReturns(Mockery::spy(Tracker::class));
        $art2 = \Mockery::spy(\Tuleap\Tracker\Artifact\Artifact::class);
        $art2->shouldReceive('getId')->andReturns(2);
        $art2->shouldReceive('getTracker')->andReturns(Mockery::spy(Tracker::class));

        $this->artifact_creator->shouldReceive('createBare')->once()->andReturn($art1);
        $this->artifact_creator->shouldReceive('createBare')->once()->andReturn($art2);

        $artlink_strategy = \Mockery::mock(\Tracker_Artifact_XMLImport_XMLImportFieldStrategyArtifactLink::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $artlink_strategy->shouldReceive('getLastChangeset')->andReturns(false);

        $this->logger->shouldReceive('log')->with(\Psr\Log\LogLevel::ERROR, Mockery::any(), Mockery::any())->once();
        $this->importer->importFromXML(
            $this->tracker,
            $xml_element,
            $this->extraction_path,
            new TrackerXmlFieldsMapping_InSamePlatform(),
            $this->url_mapping,
            $this->config,
            $this->tracker_xml_config
        );
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

        $this->artifact_creator->shouldReceive('create')->never();
        $this->artifact_creator->shouldReceive('createBare')->never();
        $this->logger->shouldReceive('log')->with(\Psr\Log\LogLevel::ERROR, Mockery::any(), Mockery::any())->once();

        $this->artifact_creator->shouldReceive('create')->andReturns(\Mockery::spy(\Tuleap\Tracker\Artifact\Artifact::class));

        $this->expectOutputRegex('/Invalid date format not ISO8601: 2011-11-24T15:51:48TCET/');
        $this->importer->importFromXML(
            $this->tracker,
            $xml_element,
            $this->extraction_path,
            new TrackerXmlFieldsMapping_InSamePlatform(),
            $this->url_mapping,
            $this->config,
            $this->tracker_xml_config
        );
    }

    public function testItCollectsChangesetIdsMapping()
    {
        $user_finder               = Mockery::mock(User\XML\Import\IFindUserFromXMLReference::class);
        $new_changeset_creator     = Mockery::mock(NewChangesetCreator::class);
        $artifact_creator          = Mockery::mock(TrackerArtifactCreator::class);
        $private_comment_extractor = Mockery::mock(TrackerPrivateCommentUGroupExtractor::class);

        $importer = new Tracker_Artifact_XMLImport(
            new XML_RNGValidator(),
            $artifact_creator,
            $new_changeset_creator,
            Mockery::mock(Tracker_FormElementFactory::class),
            $user_finder,
            Mockery::mock(BindStaticValueDao::class),
            Mockery::spy(LoggerInterface::class),
            false,
            Mockery::mock(Tracker_ArtifactFactory::class),
            Mockery::mock(TypeDao::class),
            Mockery::mock(XMLArtifactSourcePlatformExtractor::class),
            Mockery::mock(ExistingArtifactSourceIdFromTrackerExtractor::class),
            Mockery::mock(TrackerArtifactSourceIdDao::class),
            Mockery::mock(ExternalFieldsExtractor::class),
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

        $user_finder->shouldReceive(['getUser' => Mockery::mock(PFUser::class)]);

        $tracker = Mockery::mock(Tracker::class);
        $tracker->shouldReceive(['getWorkflow' => Mockery::spy(Workflow::class)]);

        $artifact = Mockery::mock(Artifact::class);
        $artifact->shouldReceive(['getId' => 123]);

        $changeset_id_mapping = new ImportedChangesetMapping();

        $changeset1 = Mockery::mock(Tracker_Artifact_Changeset::class)->shouldReceive(['getId' => 11001])->getMock();
        $changeset2 = Mockery::mock(Tracker_Artifact_Changeset::class)->shouldReceive(['getId' => 11002])->getMock();

        $artifact_creator
            ->shouldReceive('createFirstChangeset')
            ->once()
            ->andReturn($changeset1);
        $new_changeset_creator
            ->shouldReceive('create')
            ->once()
            ->andReturn($changeset2);

        $importer->importArtifactChangesFromXML(
            $tracker,
            $xml_element,
            '/extraction/path',
            new TrackerXmlFieldsMapping_InSamePlatform(),
            new Tracker_XML_Importer_ArtifactImportedMapping(),
            new CreatedFileURLMapping(),
            [$artifact],
            new ImportConfig(),
            $changeset_id_mapping,
            $this->tracker_xml_config
        );

        $this->assertEquals(11001, $changeset_id_mapping->get('CHANGESET_10001'));
        $this->assertEquals(11002, $changeset_id_mapping->get('CHANGESET_10002'));
    }

    public function testItCreatesArtifactsWithProvidedIds(): void
    {
        $bare_artifact = Mockery::spy(Artifact::class);
        $bare_artifact->shouldReceive('getTracker')->andReturn($this->tracker);

        $this->artifact_creator->shouldNotReceive('createBare');
        $this->artifact_creator->shouldReceive('createBareWithAllData')
            ->with($this->tracker, 4918, Mockery::any(), Mockery::any())
            ->once()
            ->andReturn($bare_artifact);

        $tracker_xml_config = new TrackerXmlImportConfig(
            \Tuleap\Test\Builders\UserTestBuilder::anActiveUser()->build(),
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
            $this->config,
            $tracker_xml_config
        );
    }
}
