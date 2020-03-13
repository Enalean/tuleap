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

use Tracker\Artifact\XMLArtifactSourcePlatformExtractor;
use Tuleap\Project\XML\Import\ExternalFieldsExtractor;
use Tuleap\Tracker\FormElement\Field\File\CreatedFileURLMapping;

require_once __DIR__ . '/../bootstrap.php';

abstract class Tracker_Artifact_XMLImportBaseTest extends TuleapTestCase
{
    protected $tracker_id = 12;

    /** @var Tracker */
    protected $tracker;

    /** @var Tracker_Artifact_XMLImport */
    protected $importer;

    /** @var Tracker_ArtifactCreator */
    protected $artifact_creator;

    /** @var Tracker_Artifact_Changeset_NewChangesetCreatorBase */
    protected $new_changeset_creator;

    /** @var  Tracker_FormElementFactory */
    protected $formelement_factory;

    /** @var  UserManager */
    protected $user_manager;

    /** @var XMLImportHelper  */
    protected $xml_import_helper;

    /** @var Tracker_Artifact  */
    protected $artifact;

    /** @var  Tracker_FormElement_Field_List_Bind_Static_ValueDao */
    protected $static_value_dao;

    /** @var  Logger */
    protected $logger;

    /** @var  Response */
    protected $response;

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

    public function setUp()
    {
        parent::setUp();
        $this->setUpGlobalsMockery();

        $this->tracker = \Mockery::mock(\Tracker::class)->makePartial()->shouldAllowMockingProtectedMethods();
        stub($this->tracker)->getId()->returns($this->tracker_id);
        stub($this->tracker)->getWorkflow()->returns(\Mockery::spy(\Workflow::class));

        $this->artifact_creator      = \Mockery::spy(\Tracker_ArtifactCreator::class);
        $this->new_changeset_creator = \Mockery::spy(\Tracker_Artifact_Changeset_NewChangesetAtGivenDateCreator::class);

        $this->summary_field_id = 50;
        $this->formelement_factory = \Mockery::spy(\Tracker_FormElementFactory::class);
        stub($this->formelement_factory)->getUsedFieldByName($this->tracker_id, 'summary')->returns(
            aStringField()->withId(50)->withProperty('maxchars', 'string', '0')->build()
        );

        $this->john_doe = new PFUser([
            'user_id' => 200,
            'language_id' => 'en',
            'user_name' => 'john_doe'
        ]);
        $this->user_manager = \Mockery::spy(\UserManager::class);
        stub($this->user_manager)->getUserByIdentifier('john_doe')->returns($this->john_doe);
        stub($this->user_manager)->getUserAnonymous()->returns(new PFUser(array('user_id' => 0)));

        $this->xml_import_helper = new XMLImportHelper($this->user_manager);

        $this->config = new \Tuleap\Project\XML\Import\ImportConfig();

        $this->artifact = \Mockery::spy(\Tracker_Artifact::class);

        $this->extraction_path = $this->getTmpDir();

        $this->static_value_dao = \Mockery::spy(\Tracker_FormElement_Field_List_Bind_Static_ValueDao::class);

        $this->logger = \Mockery::spy(\Psr\Log\LoggerInterface::class);

        $this->response = \Mockery::spy(\Response::class);
        $GLOBALS['Response'] = $this->response;

        $this->rng_validator = \Mockery::spy(\XML_RNGValidator::class);

        $this->url_mapping = \Mockery::mock(CreatedFileURLMapping::class);

        $this->external_field_extractor = Mockery::mock(ExternalFieldsExtractor::class);
        $this->external_field_extractor->shouldReceive('extractExternalFieldsFromArtifact');

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
            \Mockery::spy(\Tuleap\Tracker\FormElement\Field\ArtifactLink\Nature\NatureDao::class),
            Mockery::spy(XMLArtifactSourcePlatformExtractor::class),
            Mockery::spy(\Tuleap\Tracker\Artifact\ExistingArtifactSourceIdFromTrackerExtractor::class),
            Mockery::spy(\Tuleap\Tracker\DAO\TrackerArtifactSourceIdDao::class),
            $this->external_field_extractor
        );
    }
}

class Tracker_Artifact_XMLImport_ZipArchiveTest extends Tracker_Artifact_XMLImportBaseTest
{

    /** @var Tracker_Artifact_XMLImport_XMLImportZipArchive */
    private $archive;

    public function setUp()
    {
        parent::setUp();
        $this->setUpGlobalsMockery();
        $this->importer = \Mockery::mock(
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
                \Mockery::spy(\Tuleap\Tracker\FormElement\Field\ArtifactLink\Nature\NatureDao::class),
                Mockery::spy(XMLArtifactSourcePlatformExtractor::class),
                Mockery::spy(\Tuleap\Tracker\Artifact\ExistingArtifactSourceIdFromTrackerExtractor::class),
                Mockery::spy(\Tuleap\Tracker\DAO\TrackerArtifactSourceIdDao::class),
                $this->external_field_extractor
            ]
        )
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();

        $this->archive  = \Mockery::spy(\Tracker_Artifact_XMLImport_XMLImportZipArchive::class);
        stub($this->archive)->getXML()->returns('<?xml version="1.0"?><artifacts />');
        stub($this->archive)->getExtractionPath()->returns($this->extraction_path);
        $this->xml_mapping = new TrackerXmlFieldsMapping_InSamePlatform();

        $this->rng_validator->shouldReceive('validate')->andReturn(true);
    }

    public function itCallsImportFromXMLWithContentFromArchive()
    {
        expect($this->importer)->importFromXML(
            $this->tracker,
            Mockery::on(function ($element) {
                return is_a($element, SimpleXMLElement::class);
            }),
            $this->extraction_path,
            Mockery::on(function ($element) {
                return is_a($element, TrackerXmlFieldsMapping_InSamePlatform::class);
            }),
            Mockery::type(CreatedFileURLMapping::class),
            Mockery::on(function ($element) {
                return is_a($element, \Tuleap\Project\XML\Import\ImportConfig::class);
            })
        )->once();

        $this->importer->importFromArchive($this->tracker, $this->archive);
    }

    public function itAskToArchiveToExtractFiles()
    {
        expect($this->archive)->extractFiles()->once();

        $this->importer->importFromArchive($this->tracker, $this->archive);
    }

    public function itCleansUp()
    {
        expect($this->archive)->cleanUp()->once();

        $this->importer->importFromArchive($this->tracker, $this->archive);
    }
}

class Tracker_Artifact_XMLImport_HappyPathTest extends Tracker_Artifact_XMLImportBaseTest
{

    private $xml_element;


    public function setUp()
    {
        parent::setUp();
        $this->setUpGlobalsMockery();

        $this->bare_artifact = Mockery::spy(Tracker_Artifact::class);
        $this->bare_artifact->shouldReceive('getTracker')->andReturn($this->tracker);

        stub($this->artifact_creator)->create()->returns(mock('Tracker_Artifact'));

        $this->xml_element = new SimpleXMLElement('<?xml version="1.0"?>
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
            </artifacts>');

        $this->xml_mapping = new TrackerXmlFieldsMapping_InSamePlatform();
    }

    public function itCreatesArtifactOnTracker()
    {
        $this->artifact_creator->shouldReceive('createBare')
            ->with($this->tracker, Mockery::any(), Mockery::any())
            ->once()
            ->andReturn($this->bare_artifact);

        $this->importer->importFromXML(
            $this->tracker,
            $this->xml_element,
            $this->extraction_path,
            $this->xml_mapping,
            $this->url_mapping,
            $this->config
        );
    }

    public function itCreatesArtifactWithSummaryFieldData()
    {
        $data = array(
            $this->summary_field_id => 'Ça marche'
        );

        $this->artifact_creator->shouldReceive('createBare')
            ->with($this->tracker, Mockery::any(), Mockery::any())
            ->once()
            ->andReturn($this->bare_artifact);

        $this->artifact_creator->shouldReceive('createFirstChangeset')
            ->with(
                Mockery::any(),
                Mockery::any(),
                $data,
                Mockery::any(),
                Mockery::any(),
                false,
                Mockery::any()
            )
            ->once()
            ->andReturn($this->bare_artifact);

        $this->importer->importFromXML(
            $this->tracker,
            $this->xml_element,
            $this->extraction_path,
            $this->xml_mapping,
            $this->url_mapping,
            $this->config
        );
    }

    public function itCreatedArtifactWithSubmitter()
    {
        $this->artifact_creator->shouldReceive('createBare')
            ->with($this->tracker, $this->john_doe, Mockery::any())
            ->once()
            ->andReturn($this->bare_artifact);

        $this->importer->importFromXML(
            $this->tracker,
            $this->xml_element,
            $this->extraction_path,
            $this->xml_mapping,
            $this->url_mapping,
            $this->config
        );
    }

    public function itCreatesArtifactAtDate()
    {
        $expected_time = strtotime('2014-01-15T10:38:06+01:00');
        $this->artifact_creator->shouldReceive('createBare')
            ->with($this->tracker, Mockery::any(), $expected_time)
            ->once()
            ->andReturn($this->bare_artifact);

        $this->importer->importFromXML(
            $this->tracker,
            $this->xml_element,
            $this->extraction_path,
            $this->xml_mapping,
            $this->url_mapping,
            $this->config
        );
    }
}

class Tracker_Artifact_XMLImport_CommentsTest extends Tracker_Artifact_XMLImportBaseTest
{

    private $xml_element;

    public function setUp()
    {
        parent::setUp();
        $this->setUpGlobalsMockery();

        $this->xml_element = new SimpleXMLElement('<?xml version="1.0"?>
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

        $this->artifact->shouldReceive('getTracker')->andReturn($this->tracker);

        stub($this->artifact_creator)->create()->returns($this->artifact);
        stub($this->artifact_creator)->createBare()->returns($this->artifact);

        $this->xml_mapping = new TrackerXmlFieldsMapping_InSamePlatform();
    }

    public function itCreatesTheComments()
    {
        $this->artifact_creator->shouldReceive('createFirstChangeset')
            ->with(Mockery::any(), Mockery::any(), Mockery::any(), Mockery::any(), Mockery::any(), false, Mockery::any())
            ->once()
            ->andReturn(\Mockery::spy(\Tracker_Artifact::class));

        $this->new_changeset_creator->shouldReceive('create')->times(2);

        $this->new_changeset_creator->shouldReceive('create')
            ->with(Mockery::any(), Mockery::any(), 'Some text', Mockery::any(), Mockery::any(), Mockery::any(), Tracker_Artifact_Changeset_Comment::TEXT_COMMENT, Mockery::any())
            ->andReturn(\Mockery::spy(\Tracker_Artifact_Changeset::class));

        $this->new_changeset_creator->shouldReceive('create')
            ->with(Mockery::any(), Mockery::any(), '<p>Some text</p>', Mockery::any(), Mockery::any(), Mockery::any(), Tracker_Artifact_Changeset_Comment::HTML_COMMENT, Mockery::any())
            ->andReturn(\Mockery::spy(\Tracker_Artifact_Changeset::class));

        $this->importer->importFromXML(
            $this->tracker,
            $this->xml_element,
            $this->extraction_path,
            $this->xml_mapping,
            $this->url_mapping,
            $this->config
        );
    }
}

class Tracker_Artifact_XMLImport_CommentUpdatesTest extends Tracker_Artifact_XMLImportBaseTest
{

    private $xml_element;
    private $changeset;

    public function setUp()
    {
        parent::setUp();
        $this->setUpGlobalsMockery();

        $this->xml_element = new SimpleXMLElement('<?xml version="1.0"?>
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

        $this->changeset = \Mockery::spy(\Tracker_Artifact_Changeset::class);

        $this->artifact->shouldReceive('getTracker')->andReturn($this->tracker);

        stub($this->artifact_creator)->create()->returns($this->artifact);
        stub($this->artifact_creator)->createBare()->returns($this->artifact);

        $this->xml_mapping = new TrackerXmlFieldsMapping_InSamePlatform();
    }

    public function itCreatesTheCommentsWithUpdates()
    {
        $this->artifact_creator->shouldReceive('createFirstChangeset')
            ->with(Mockery::any(), Mockery::any(), Mockery::any(), Mockery::any(), Mockery::any(), false, Mockery::any())
            ->once()
            ->andReturn($this->artifact);

        $this->new_changeset_creator->shouldReceive('create')
            ->with(Mockery::any(), Mockery::any(), 'Some text', Mockery::any(), Mockery::any(), Mockery::any(), Tracker_Artifact_Changeset_Comment::TEXT_COMMENT, Mockery::any())
            ->once()
            ->andReturn($this->changeset);

        expect($this->changeset)->updateCommentWithoutNotification(
            '<p>Some text</p>',
            $this->john_doe,
            Tracker_Artifact_Changeset_Comment::HTML_COMMENT,
            strtotime('2014-01-15T11:23:50+01:00')
        )->once();

        $this->importer->importFromXML(
            $this->tracker,
            $this->xml_element,
            $this->extraction_path,
            $this->xml_mapping,
            $this->url_mapping,
            $this->config
        );
    }
}

class Tracker_Artifact_XMLImport_NoFieldTest extends Tracker_Artifact_XMLImportBaseTest
{

    private $xml_element;

    public function setUp()
    {
        parent::setUp();
        $this->setUpGlobalsMockery();

        $this->xml_element = new SimpleXMLElement('<?xml version="1.0"?>
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

        $this->xml_mapping = new TrackerXmlFieldsMapping_InSamePlatform();
    }

    public function itThrowAnExceptionWhenFieldDoesntExist()
    {
        $artifact = \Mockery::spy(\Tracker_Artifact::class);
        $artifact->shouldReceive('getId')->andReturn(101);
        $artifact->shouldReceive('getTracker')->andReturn(Mockery::spy(Tracker::class));

        $this->artifact_creator->shouldReceive('createBare')->once()->andReturn($artifact);

        $this->logger->shouldReceive('log')->with(\Psr\Log\LogLevel::WARNING, Mockery::any(), Mockery::any())->once();

        $this->importer->importFromXML(
            $this->tracker,
            $this->xml_element,
            $this->extraction_path,
            $this->xml_mapping,
            $this->url_mapping,
            $this->config
        );
    }
}

class Tracker_Artifact_XMLImport_UserTest extends Tracker_Artifact_XMLImportBaseTest
{


    public function setUp()
    {
        parent::setUp();
        $this->setUpGlobalsMockery();

        stub($this->artifact_creator)->create()->returns(\Mockery::spy(\Tracker_Artifact::class));
        stub($this->artifact_creator)->createFirstChangeset()->returns(\Mockery::spy(\Tracker_Artifact::class));

        $this->user_manager = \Mockery::spy(\UserManager::class);
        stub($this->user_manager)->getUserAnonymous()->returns(new PFUser(array('user_id' => 0)));

        $this->xml_import_helper = new XMLImportHelper($this->user_manager);

        $this->importer = new Tracker_Artifact_XMLImport(
            \Mockery::spy(\XML_RNGValidator::class),
            $this->artifact_creator,
            $this->new_changeset_creator,
            $this->formelement_factory,
            $this->xml_import_helper,
            $this->static_value_dao,
            \Mockery::spy(\Psr\Log\LoggerInterface::class),
            false,
            \Mockery::spy(\Tracker_ArtifactFactory::class),
            \Mockery::spy(\Tuleap\Tracker\FormElement\Field\ArtifactLink\Nature\NatureDao::class),
            Mockery::spy(XMLArtifactSourcePlatformExtractor::class),
            Mockery::spy(\Tuleap\Tracker\Artifact\ExistingArtifactSourceIdFromTrackerExtractor::class),
            Mockery::spy(\Tuleap\Tracker\DAO\TrackerArtifactSourceIdDao::class),
            $this->external_field_extractor
        );

        $this->xml_mapping = new TrackerXmlFieldsMapping_InSamePlatform();
    }

    public function itCreatesChangesetAsAnonymousWhenUserDoesntExists()
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

        $artifact = \Mockery::spy(\Tracker_Artifact::class);
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
            $this->xml_mapping,
            $this->url_mapping,
            $this->config
        );
    }

    public function itLooksForUserIdWhenFormatIsId()
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

        $artifact = \Mockery::spy(\Tracker_Artifact::class);
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
            $this->xml_mapping,
            $this->url_mapping,
            $this->config
        );
    }

    public function itLooksForLdapIdWhenFormatIsLdap()
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

        $artifact = \Mockery::spy(\Tracker_Artifact::class);
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
            $this->xml_mapping,
            $this->url_mapping,
            $this->config
        );
    }

    public function itLooksForEmailWhenFormatIsEmail()
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

        $artifact = \Mockery::spy(\Tracker_Artifact::class);
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
            $this->xml_mapping,
            $this->url_mapping,
            $this->config
        );
    }
}

class Tracker_Artifact_XMLImport_MultipleChangesetsTest extends Tracker_Artifact_XMLImportBaseTest
{

    private $xml_element;

    public function setUp()
    {
        parent::setUp();
        $this->setUpGlobalsMockery();

        $this->xml_element = new SimpleXMLElement('<?xml version="1.0"?>
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

        $this->artifact->shouldReceive('getId')->andReturn(101);
        $this->artifact->shouldReceive('getTracker')->andReturn(Mockery::spy(Tracker::class));

        stub($this->artifact_creator)->create()->returns(\Mockery::spy(\Tracker_Artifact::class));
        $this->xml_mapping = new TrackerXmlFieldsMapping_InSamePlatform();
    }

    public function itCreatesTwoChangesets()
    {
        $this->artifact_creator->shouldReceive('createBare')->once()->andReturn($this->artifact);

        $this->artifact_creator->shouldReceive('createFirstChangeset')
            ->once()
            ->andReturn($this->artifact);

        expect($this->new_changeset_creator)->create()->count(1);

        $this->importer->importFromXML(
            $this->tracker,
            $this->xml_element,
            $this->extraction_path,
            $this->xml_mapping,
            $this->url_mapping,
            $this->config
        );
    }

    public function itCreatesTheNewChangesetWithSummaryValue()
    {
        $data = array(
            $this->summary_field_id => '^Wit updates'
        );

        $this->artifact_creator->shouldReceive('createBare')->once()->andReturn($this->artifact);

        $this->artifact_creator->shouldReceive('createFirstChangeset')
            ->once()
            ->with(Mockery::any(), Mockery::any(), Mockery::any(), Mockery::any(), Mockery::any(), false, Mockery::any())
            ->andReturn($this->artifact);

        $this->new_changeset_creator->shouldReceive('create')
            ->once()
            ->with($this->artifact, $data, Mockery::any(), Mockery::any(), Mockery::any(), Mockery::any(), Mockery::any(), Mockery::any())
            ->andReturn(\Mockery::spy(\Tracker_Artifact_Changeset::class));

        $this->importer->importFromXML(
            $this->tracker,
            $this->xml_element,
            $this->extraction_path,
            $this->xml_mapping,
            $this->url_mapping,
            $this->config
        );
    }

    public function itCreatesTheNewChangesetWithSubmitter()
    {
        $this->artifact_creator->shouldReceive('createBare')->once()->andReturn($this->artifact);

        $this->artifact_creator->shouldReceive('createFirstChangeset')
            ->once()
            ->with(Mockery::any(), Mockery::any(), Mockery::any(), Mockery::any(), Mockery::any(), false, Mockery::any())
            ->andReturn($this->artifact);

        $this->new_changeset_creator->shouldReceive('create')
            ->once()
            ->with($this->artifact, Mockery::any(), Mockery::any(), $this->john_doe, Mockery::any(), Mockery::any(), Mockery::any(), Mockery::any())
            ->andReturn(\Mockery::spy(\Tracker_Artifact_Changeset::class));

        $this->importer->importFromXML(
            $this->tracker,
            $this->xml_element,
            $this->extraction_path,
            $this->xml_mapping,
            $this->url_mapping,
            $this->config
        );
    }

    public function itCreatesTheNewChangesetWithoutNotification()
    {
        $this->artifact_creator->shouldReceive('createBare')->once()->andReturn($this->artifact);

        $this->artifact_creator->shouldReceive('createFirstChangeset')
            ->once()
            ->with(Mockery::any(), Mockery::any(), Mockery::any(), Mockery::any(), Mockery::any(), false, Mockery::any())
            ->andReturn($this->artifact);

        $this->new_changeset_creator->shouldReceive('create')
            ->once()
            ->with($this->artifact, Mockery::any(), Mockery::any(), Mockery::any(), Mockery::any(), Mockery::any(), Mockery::any(), Mockery::any())
            ->andReturn(\Mockery::spy(\Tracker_Artifact_Changeset::class));

        $this->importer->importFromXML(
            $this->tracker,
            $this->xml_element,
            $this->extraction_path,
            $this->xml_mapping,
            $this->url_mapping,
            $this->config
        );
    }

    public function itCreatesTheChangesetsAccordingToDates()
    {
        $this->artifact_creator->shouldReceive('createBare')
            ->with($this->tracker, Mockery::any(), strtotime('2014-01-15T10:38:06+01:00'))
            ->once()
            ->andReturn($this->artifact);

        $this->artifact_creator->shouldReceive('createFirstChangeset')
            ->once()
            ->with(Mockery::any(), Mockery::any(), Mockery::any(), Mockery::any(), strtotime('2014-01-15T10:38:06+01:00'), false, Mockery::any())
            ->andReturn($this->artifact);

        $this->new_changeset_creator->shouldReceive('create')
            ->once()
            ->with($this->artifact, Mockery::any(), Mockery::any(), Mockery::any(), strtotime('2014-01-15T11:03:50+01:00'), Mockery::any(), Mockery::any(), Mockery::any())
            ->andReturn(\Mockery::spy(\Tracker_Artifact_Changeset::class));

        $this->importer->importFromXML(
            $this->tracker,
            $this->xml_element,
            $this->extraction_path,
            $this->xml_mapping,
            $this->url_mapping,
            $this->config
        );
    }

    public function itCreatesTheChangesetsInAscendingDatesEvenWhenChangesetsAreMixedInXML()
    {
        $this->xml_element = new SimpleXMLElement('<?xml version="1.0"?>
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
            ->with(Mockery::any(), Mockery::any(), Mockery::any(), Mockery::any(), strtotime('2014-01-15T10:38:06+01:00'), false, Mockery::any())
            ->andReturn($this->artifact);

        $this->new_changeset_creator->shouldReceive('create')
            ->once()
            ->with($this->artifact, Mockery::any(), Mockery::any(), Mockery::any(), strtotime('2014-01-15T11:03:50+01:00'), Mockery::any(), Mockery::any(), Mockery::any())
            ->andReturn(\Mockery::spy(\Tracker_Artifact_Changeset::class));

        $this->importer->importFromXML(
            $this->tracker,
            $this->xml_element,
            $this->extraction_path,
            $this->xml_mapping,
            $this->url_mapping,
            $this->config
        );
    }

    public function itKeepsTheOriginalOrderWhenTwoDatesAreEqual()
    {
        $this->xml_element = new SimpleXMLElement('<?xml version="1.0"?>
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
                Mockery::any(),
                Mockery::on(function ($data) {
                    return $data[$this->summary_field_id] === 'First';
                }),
                Mockery::any(),
                strtotime('2014-01-15T10:38:06+01:00'),
                Mockery::any(),
                Mockery::any()
            )
            ->andReturn($this->artifact);

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
                Mockery::any()
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
                Mockery::any()
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
                Mockery::any()
            )
            ->andReturn(\Mockery::spy(\Tracker_Artifact_Changeset::class));

        $this->importer->importFromXML(
            $this->tracker,
            $this->xml_element,
            $this->extraction_path,
            $this->xml_mapping,
            $this->url_mapping,
            $this->config
        );
    }
}

class Tracker_Artifact_XMLImport_SeveralArtifactsTest extends Tracker_Artifact_XMLImportBaseTest
{

    private $xml_element;


    public function setUp()
    {
        parent::setUp();
        $this->setUpGlobalsMockery();

        stub($this->artifact_creator)->create()->returns($this->artifact);

        $this->xml_element = new SimpleXMLElement('<?xml version="1.0"?>
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

        $this->xml_mapping = new TrackerXmlFieldsMapping_InSamePlatform();
    }

    public function itCreatesTwoArtifactsOnTracker()
    {
        $artifact = \Mockery::spy(\Tracker_Artifact::class);
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
            $this->xml_element,
            $this->extraction_path,
            $this->xml_mapping,
            $this->url_mapping,
            $this->config
        );
    }
}

class Tracker_Artifact_XMLImport_OneArtifactWithAttachementTest extends Tracker_Artifact_XMLImportBaseTest
{

    private $file_field_id = 51;

    public function setUp()
    {
        parent::setUp();
        $this->setUpGlobalsMockery();

        $this->xml_element = new SimpleXMLElement('<?xml version="1.0"?>
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
        touch($this->extraction_path . '/34_File33.png');

        $this->artifact->shouldReceive('getId')->andReturn(101);
        $this->artifact->shouldReceive('getTracker')->andReturn(Mockery::spy(Tracker::class));

        stub($this->new_changeset_creator)->create()->returns(\Mockery::spy(\Tracker_Artifact_Changeset::class));
        $this->xml_mapping = new TrackerXmlFieldsMapping_InSamePlatform();
    }

    public function itCreatesAChangesetWithSummaryWhenFileFormElementDoesNotExist()
    {
        $data = array(
            $this->summary_field_id => 'Newly submitted'
        );

        $this->artifact_creator->shouldReceive('createBare')
            ->with($this->tracker, Mockery::any(), Mockery::any())
            ->once()
            ->andReturn($this->artifact);

        $this->artifact_creator->shouldReceive('createFirstChangeset')
            ->once()
            ->with(Mockery::any(), Mockery::any(), $data, Mockery::any(), Mockery::any(), false, Mockery::any())
            ->andReturn($this->artifact);

        $this->importer->importFromXML($this->tracker, $this->xml_element, $this->extraction_path, $this->xml_mapping, $this->url_mapping, $this->config);
    }

    public function itCreatesAChangesetWithOneFileElement()
    {
        stub($this->artifact_creator)->create()->returns(\Mockery::spy(\Tracker_Artifact::class));
        stub($this->formelement_factory)->getUsedFieldByName($this->tracker_id, 'attachment')->returns(
            aFileField()->withId($this->file_field_id)->build()
        );

        $this->artifact_creator->shouldReceive('createBare')
            ->with($this->tracker, Mockery::any(), Mockery::any())
            ->once()
            ->andReturn($this->artifact);

        $this->artifact_creator->shouldReceive('createFirstChangeset')
            ->once()
            ->with(
                Mockery::any(),
                Mockery::any(),
                Mockery::on(function ($data) {
                    return $data[$this->summary_field_id] === 'Newly submitted' &&
                        $data[$this->file_field_id][0]['name'] === 'A.png' &&
                        $data[$this->file_field_id][0]['submitted_by']->getEmail() === 'manuel';
                }),
                Mockery::any(),
                Mockery::any(),
                false,
                Mockery::any()
            )
            ->andReturn($this->artifact);

        $this->importer->importFromXML($this->tracker, $this->xml_element, $this->extraction_path, $this->xml_mapping, $this->url_mapping, $this->config);
    }
}

class Tracker_Artifact_XMLImport_AttachmentNoLongerExistsTest extends Tracker_Artifact_XMLImportBaseTest
{

    private $file_field_id = 51;

    public function setUp()
    {
        parent::setUp();
        $this->setUpGlobalsMockery();

        $this->xml_element = new SimpleXMLElement('<?xml version="1.0"?>
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

        stub($this->formelement_factory)->getUsedFieldByName($this->tracker_id, 'attachment')->returns(
            aFileField()->withId($this->file_field_id)->build()
        );

        $this->xml_mapping = new TrackerXmlFieldsMapping_InSamePlatform();
    }

    public function itSkipsFieldWithoutValidFile()
    {
        $data = array(
            $this->summary_field_id => 'Newly submitted'
        );

        $artifact = \Mockery::spy(\Tracker_Artifact::class);
        $artifact->shouldReceive('getId')->andReturn(101);
        $artifact->shouldReceive('getTracker')->andReturn(Mockery::spy(Tracker::class));

        $this->artifact_creator->shouldReceive('createBare')
            ->with($this->tracker, Mockery::any(), Mockery::any())
            ->once()
            ->andReturn($artifact);

        $this->artifact_creator->shouldReceive('createFirstChangeset')
            ->once()
            ->with(Mockery::any(), Mockery::any(), $data, Mockery::any(), Mockery::any(), false, Mockery::any())
            ->andReturn($artifact);

        $this->importer->importFromXML($this->tracker, $this->xml_element, $this->extraction_path, $this->xml_mapping, $this->url_mapping, $this->config);
    }
}

class Tracker_Artifact_XMLImport_OneArtifactWithMultipleAttachementsTest extends Tracker_Artifact_XMLImportBaseTest
{

    private $file_field_id = 51;

    public function setUp()
    {
        parent::setUp();
        $this->setUpGlobalsMockery();

        $this->xml_element = new SimpleXMLElement('<?xml version="1.0"?>
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

        $this->xml_mapping = new TrackerXmlFieldsMapping_InSamePlatform();
    }

    public function itCreatesAChangesetWithTwoFileElements()
    {
        stub($this->formelement_factory)->getUsedFieldByName($this->tracker_id, 'attachment')->returns(
            aFileField()->withId($this->file_field_id)->build()
        );

        $artifact = \Mockery::spy(\Tracker_Artifact::class);
        $artifact->shouldReceive('getId')->andReturn(101);
        $artifact->shouldReceive('getTracker')->andReturn(Mockery::spy(Tracker::class));

        $this->artifact_creator->shouldReceive('createBare')
            ->with($this->tracker, Mockery::any(), Mockery::any())
            ->once()
            ->andReturn($artifact);

        $this->artifact_creator->shouldReceive('createFirstChangeset')
            ->once()
            ->with(
                Mockery::any(),
                Mockery::any(),
                Mockery::on(function ($data) {
                    return $data[$this->summary_field_id] === 'Newly submitted' &&
                        $data[$this->file_field_id][0]['name'] === 'A.png' &&
                        $data[$this->file_field_id][0]['submitted_by']->getEmail() === 'manuel' &&
                        $data[$this->file_field_id][1]['name'] === 'B.pdf' &&
                        $data[$this->file_field_id][1]['submitted_by']->getEmail() === 'manuel';
                }),
                Mockery::any(),
                Mockery::any(),
                false,
                Mockery::any()
            )
            ->andReturn($artifact);

        $this->importer->importFromXML($this->tracker, $this->xml_element, $this->extraction_path, $this->xml_mapping, $this->url_mapping, $this->config);
    }
}

class Tracker_Artifact_XMLImport_OneArtifactWithMultipleAttachementsAndChangesetsTest extends Tracker_Artifact_XMLImportBaseTest
{

    private $file_field_id = 51;

    public function setUp()
    {
        parent::setUp();
        $this->setUpGlobalsMockery();

        $this->xml_element = new SimpleXMLElement('<?xml version="1.0"?>
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

        $this->xml_mapping = new TrackerXmlFieldsMapping_InSamePlatform();
    }

    public function itCreatesChangesetsThatOnlyReferenceConcernedFiles()
    {
        stub($this->formelement_factory)->getUsedFieldByName($this->tracker_id, 'attachment')->returns(
            aFileField()->withId($this->file_field_id)->build()
        );

        $artifact = \Mockery::spy(\Tracker_Artifact::class);
        $artifact->shouldReceive('getId')->andReturn(101);
        $artifact->shouldReceive('getTracker')->andReturn(Mockery::spy(Tracker::class));

        $this->artifact_creator->shouldReceive('createBare')
            ->with($this->tracker, Mockery::any(), Mockery::any())
            ->once()
            ->andReturn($artifact);

        $this->artifact_creator->shouldReceive('createFirstChangeset')
            ->once()
            ->with(
                Mockery::any(),
                Mockery::any(),
                Mockery::on(function ($data) {
                    return $data[$this->summary_field_id] === 'Newly submitted' &&
                        $data[$this->file_field_id][0]['name'] === 'A.png' &&
                        $data[$this->file_field_id][0]['submitted_by']->getEmail() === 'manuel';
                }),
                Mockery::any(),
                Mockery::any(),
                false,
                Mockery::any()
            )
            ->andReturn($artifact);

        $this->new_changeset_creator->shouldReceive('create')
            ->once()
            ->with(
                $artifact,
                Mockery::on(function ($data) {
                    return $data[$this->file_field_id][0]['name'] === 'B.pdf' &&
                        $data[$this->file_field_id][0]['submitted_by']->getEmail() === 'manuel';
                }),
                Mockery::any(),
                Mockery::any(),
                Mockery::any(),
                Mockery::any(),
                Mockery::any(),
                Mockery::any()
            )
            ->andReturn(\Mockery::spy(\Tracker_Artifact_Changeset::class));

        $this->importer->importFromXML($this->tracker, $this->xml_element, $this->extraction_path, $this->xml_mapping, $this->url_mapping, $this->config);
    }
}

class Tracker_Artifact_XMLImport_CCListTest extends Tracker_Artifact_XMLImportBaseTest
{

    private $xml_element;

    private $cc_field_id = 369;
    private $open_list_field;

    public function setUp()
    {
        parent::setUp();
        $this->setUpGlobalsMockery();

        $this->artifact->shouldReceive('getId')->andReturn(101);
        $this->artifact->shouldReceive('getTracker')->andReturn(Mockery::spy(Tracker::class));

        $this->open_list_field = \Mockery::spy(\Tracker_FormElement_Field_OpenList::class);
        stub($this->open_list_field)->getId()->returns($this->cc_field_id);
        stub($this->open_list_field)->validateField()->returns(true);

        stub($this->formelement_factory)->getUsedFieldByName($this->tracker_id, 'cc')->returns(
            $this->open_list_field
        );

        $this->xml_element = new SimpleXMLElement('<?xml version="1.0"?>
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

        $this->xml_mapping = new TrackerXmlFieldsMapping_InSamePlatform();
    }

    public function itDelegatesOpenListComputationToField()
    {
        stub($this->artifact_creator)->createBare()->returns($this->artifact);
        stub($this->artifact_creator)->createFirstChangeset()->returns($this->artifact);

        $this->open_list_field->shouldReceive('getFieldData')->with('homer')->once();
        $this->open_list_field->shouldReceive('getFieldData')->with('jeanjean')->once();

        $this->importer->importFromXML($this->tracker, $this->xml_element, $this->extraction_path, $this->xml_mapping, $this->url_mapping, $this->config);
    }

    public function itCreatesArtifactWithCCFieldData()
    {
        $this->open_list_field->shouldReceive('getFieldData')->with('homer')->once()->andReturn('!112');
        $this->open_list_field->shouldReceive('getFieldData')->with('jeanjean')->once()->andReturn('!113');

        $this->artifact_creator->shouldReceive('createBare')->once()->andReturn($this->artifact);

        $this->artifact_creator->shouldReceive('createFirstChangeset')
            ->once()
            ->with(
                Mockery::any(),
                Mockery::any(),
                Mockery::on(function ($data) {
                    return $data[$this->cc_field_id] === '!112,!113';
                }),
                Mockery::any(),
                Mockery::any(),
                false,
                Mockery::any()
            )
            ->andReturn($this->artifact);

        $this->importer->importFromXML($this->tracker, $this->xml_element, $this->extraction_path, $this->xml_mapping, $this->url_mapping, $this->config);
    }
}

class Tracker_Artifact_XMLImport_PermsOnArtifactTest extends Tracker_Artifact_XMLImportBaseTest
{

    private $xml_element;

    private $perms_field_id = 369;
    private $perms_field;

    public function setUp()
    {
        parent::setUp();
        $this->setUpGlobalsMockery();

        $this->perms_field = \Mockery::spy(\Tracker_FormElement_Field_PermissionsOnArtifact::class);
        stub($this->perms_field)->getId()->returns($this->perms_field_id);
        stub($this->perms_field)->validateField()->returns(true);

        stub($this->formelement_factory)->getUsedFieldByName($this->tracker_id, 'permissions_on_artifact')->returns(
            $this->perms_field
        );

        $this->xml_element = new SimpleXMLElement('<?xml version="1.0"?>
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

        $this->xml_mapping = new TrackerXmlFieldsMapping_InSamePlatform();
    }

    public function itCreatesArtifactWithPermsFieldData()
    {
        $this->artifact->shouldReceive('getId')->andReturn(101);
        $this->artifact->shouldReceive('getTracker')->andReturn(Mockery::spy(Tracker::class));

        $this->artifact_creator->shouldReceive('createBare')->once()->andReturn($this->artifact);

        $this->artifact_creator->shouldReceive('createFirstChangeset')
            ->once()
            ->with(
                Mockery::any(),
                Mockery::any(),
                Mockery::on(function ($data) {
                    return $data[$this->perms_field_id]['use_artifact_permissions'] === 1 &&
                        $data[$this->perms_field_id]['u_groups'] === array(15, 101);
                }),
                Mockery::any(),
                Mockery::any(),
                false,
                Mockery::any()
            )
            ->andReturn($this->artifact);

        $this->importer->importFromXML($this->tracker, $this->xml_element, $this->extraction_path, $this->xml_mapping, $this->url_mapping, $this->config);
    }
}

class Tracker_Artifact_XMLImport_TextTest extends Tracker_Artifact_XMLImportBaseTest
{

    private $xml_element;

    private $text_field_id = 369;
    private $text_field;

    public function setUp()
    {
        parent::setUp();
        $this->setUpGlobalsMockery();

        $this->text_field = \Mockery::spy(\Tracker_FormElement_Field_Text::class);
        stub($this->text_field)->getId()->returns($this->text_field_id);
        stub($this->text_field)->validateField()->returns(true);

        stub($this->formelement_factory)->getUsedFieldByName($this->tracker_id, 'textarea')->returns(
            $this->text_field
        );

        $this->xml_element = new SimpleXMLElement('<?xml version="1.0"?>
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

        $this->xml_mapping = new TrackerXmlFieldsMapping_InSamePlatform();
    }

    public function itCreatesArtifactWithTextData()
    {
        $this->artifact->shouldReceive('getId')->andReturn(101);
        $this->artifact->shouldReceive('getTracker')->andReturn(Mockery::spy(Tracker::class));

        $this->artifact_creator->shouldReceive('createBare')->once()->andReturn($this->artifact);

        $this->artifact_creator->shouldReceive('createFirstChangeset')
            ->once()
            ->with(
                Mockery::any(),
                Mockery::any(),
                Mockery::on(function ($data) {
                    return $data[$this->text_field_id]['format'] === 'html' &&
                        $data[$this->text_field_id]['content'] === 'test';
                }),
                Mockery::any(),
                Mockery::any(),
                false,
                Mockery::any()
            )
            ->andReturn($this->artifact);

        $this->importer->importFromXML($this->tracker, $this->xml_element, $this->extraction_path, $this->xml_mapping, $this->url_mapping, $this->config);
    }
}

class Tracker_Artifact_XMLImport_AlphanumericTest extends Tracker_Artifact_XMLImportBaseTest
{

    private $xml_element;

    private $string_field_id = 369;
    private $string_field;
    private $text_field;
    private $int_field_id = 234;
    private $int_field;
    private $float_field_id = 347;
    private $float_field;
    private $date_field_id = 978;
    private $date_field;

    public function setUp()
    {
        parent::setUp();
        $this->setUpGlobalsMockery();

        $this->string_field = \Mockery::spy(\Tracker_FormElement_Field_String::class);
        stub($this->string_field)->getId()->returns($this->string_field_id);
        stub($this->string_field)->validateField()->returns(true);
        $this->int_field    = \Mockery::spy(\Tracker_FormElement_Field_Integer::class);
        stub($this->int_field)->getId()->returns($this->int_field_id);
        stub($this->int_field)->validateField()->returns(true);
        $this->float_field  = \Mockery::spy(\Tracker_FormElement_Field_Float::class);
        stub($this->float_field)->getId()->returns($this->float_field_id);
        stub($this->float_field)->validateField()->returns(true);
        $this->date_field   = \Mockery::mock(\Tracker_FormElement_Field_Date::class)->makePartial()->shouldAllowMockingProtectedMethods();
        stub($this->date_field)->getId()->returns($this->date_field_id);
        stub($this->date_field)->validateField()->returns(true);

        stub($this->formelement_factory)->getUsedFieldByName($this->tracker_id, 'i_want_to')->returns(
            $this->string_field
        );
        stub($this->formelement_factory)->getUsedFieldByName($this->tracker_id, 'so_that')->returns(
            $this->text_field
        );
        stub($this->formelement_factory)->getUsedFieldByName($this->tracker_id, 'initial_effort')->returns(
            $this->int_field
        );
        stub($this->formelement_factory)->getUsedFieldByName($this->tracker_id, 'remaining_effort')->returns(
            $this->float_field
        );
        stub($this->formelement_factory)->getUsedFieldByName($this->tracker_id, 'start_date')->returns(
            $this->date_field
        );

        $this->xml_element = new SimpleXMLElement('<?xml version="1.0"?>
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

        $this->xml_mapping = new TrackerXmlFieldsMapping_InSamePlatform();
        $this->url_mapping = \Mockery::mock(CreatedFileURLMapping::class);

        $this->artifact->shouldReceive('getId')->andReturn(101);
        $this->artifact->shouldReceive('getTracker')->andReturn(Mockery::spy(Tracker::class));
    }

    public function itCreatesArtifactWithAlphanumFieldData()
    {
        stub($this->date_field)->isTimeDisplayed()->returns(false);

        $this->artifact_creator->shouldReceive('createBare')->once()->andReturn($this->artifact);

        $this->artifact_creator->shouldReceive('createFirstChangeset')
            ->once()
            ->with(
                Mockery::any(),
                Mockery::any(),
                Mockery::on(function ($data) {
                    return $data[$this->string_field_id] === 'Import artifact in tracker v5' &&
                        $data[$this->int_field_id] === '5' &&
                        $data[$this->float_field_id] === '4.5' &&
                        $data[$this->date_field_id] === '2014-03-20';
                }),
                Mockery::any(),
                Mockery::any(),
                false,
                Mockery::any()
            )
            ->andReturn($this->artifact);

        $this->importer->importFromXML($this->tracker, $this->xml_element, $this->extraction_path, $this->xml_mapping, $this->url_mapping, $this->config);
    }

    public function itCreatesArtifactWithAlphanumFieldDataAndTimeDisplayedDate()
    {
        stub($this->date_field)->isTimeDisplayed()->returns(true);

        $this->artifact_creator->shouldReceive('createBare')->once()->andReturn($this->artifact);

        $this->artifact_creator->shouldReceive('createFirstChangeset')
           ->once()
           ->with(
               Mockery::any(),
               Mockery::any(),
               Mockery::on(function ($data) {
                    return $data[$this->string_field_id] === 'Import artifact in tracker v5' &&
                        $data[$this->int_field_id] === '5' &&
                        $data[$this->float_field_id] === '4.5' &&
                        $data[$this->date_field_id] === '2014-03-20 10:13';
               }),
               Mockery::any(),
               Mockery::any(),
               false,
               Mockery::any()
           )
           ->andReturn($this->artifact);

        $this->importer->importFromXML($this->tracker, $this->xml_element, $this->extraction_path, $this->xml_mapping, $this->url_mapping, $this->config);
    }

    public function itDoesntConvertEmptyDateInto70sdate()
    {
        $this->xml_element = new SimpleXMLElement('<?xml version="1.0"?>
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

        $this->artifact_creator->shouldReceive('createBare')->once()->andReturn($this->artifact);

        $this->artifact_creator->shouldReceive('createFirstChangeset')
            ->once()
            ->with(
                Mockery::any(),
                Mockery::any(),
                Mockery::on(function ($data) {
                        return $data[$this->date_field_id] === '';
                }),
                Mockery::any(),
                Mockery::any(),
                false,
                Mockery::any()
            )
            ->andReturn($this->artifact);

        $this->importer->importFromXML($this->tracker, $this->xml_element, $this->extraction_path, $this->xml_mapping, $this->url_mapping, $this->config);
    }
}

class Tracker_Artifact_XMLImport_SelectboxTest extends Tracker_Artifact_XMLImportBaseTest
{

    private $status_field;
    private $status_field_id = 234;
    private $assto_field;
    private $assto_field_id = 456;
    private $open_value_id = 104;

    public function setUp()
    {
        parent::setUp();
        $this->setUpGlobalsMockery();

        $this->status_field = \Mockery::spy(\Tracker_FormElement_Field_String::class);
        stub($this->status_field)->getId()->returns($this->status_field_id);
        stub($this->status_field)->validateField()->returns(true);
        $this->assto_field  = \Mockery::spy(\Tracker_FormElement_Field_String::class);
        stub($this->assto_field)->getId()->returns($this->assto_field_id);
        stub($this->assto_field)->validateField()->returns(true);

        stub($this->formelement_factory)->getUsedFieldByName($this->tracker_id, 'status_id')->returns(
            $this->status_field
        );
        stub($this->formelement_factory)->getUsedFieldByName($this->tracker_id, 'assigned_to')->returns(
            $this->assto_field
        );

        stub($this->static_value_dao)->searchValueByLabel($this->status_field_id, 'Open')->returnsDar(array(
            'id'    => $this->open_value_id,
            'label' => 'Open',
            // ...
        ));

        $this->xml_element = new SimpleXMLElement('<?xml version="1.0"?>
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

        $this->xml_mapping = new TrackerXmlFieldsMapping_InSamePlatform();
    }

    public function itCreatesArtifactWithSelectboxValue()
    {
        $this->artifact->shouldReceive('getId')->andReturn(101);
        $this->artifact->shouldReceive('getTracker')->andReturn(Mockery::spy(Tracker::class));

        $this->artifact_creator->shouldReceive('createBare')->once()->andReturn($this->artifact);

        $this->artifact_creator->shouldReceive('createFirstChangeset')
            ->once()
            ->with(
                Mockery::any(),
                Mockery::any(),
                Mockery::on(function ($data) {
                    return $data[$this->status_field_id] === array($this->open_value_id) &&
                        $data[$this->assto_field_id] === array($this->john_doe->getId());
                }),
                Mockery::any(),
                Mockery::any(),
                false,
                Mockery::any()
            )
            ->andReturn($this->artifact);

        $this->importer->importFromXML($this->tracker, $this->xml_element, $this->extraction_path, $this->xml_mapping, $this->url_mapping, $this->config);
    }
}

class Tracker_Artifact_XMLImport_StaticMultiSelectboxTest extends Tracker_Artifact_XMLImportBaseTest
{

    private $static_multi_selectbox_field;
    private $static_multi_selectbox_field_id = 456;

    private $ui_value_id          = 101;
    private $ui_value_label       = "UI";
    private $database_value_id    = 102;
    private $database_value_label = "Database";

    public function setUp()
    {
        parent::setUp();
        $this->setUpGlobalsMockery();

        $this->static_multi_selectbox_field = \Mockery::spy(\Tracker_FormElement_Field_MultiSelectbox::class);
        stub($this->static_multi_selectbox_field)->getId()->returns($this->static_multi_selectbox_field_id);
        stub($this->static_multi_selectbox_field)->validateField()->returns(true);

        stub($this->formelement_factory)->getUsedFieldByName($this->tracker_id, 'multi_select_box')->returns(
            $this->static_multi_selectbox_field
        );

        stub($this->static_value_dao)->searchValueByLabel($this->static_multi_selectbox_field_id, $this->ui_value_label)->returnsDar(array(
            'id'    => $this->ui_value_id,
            'label' => $this->ui_value_label,
        ));

        stub($this->static_value_dao)->searchValueByLabel($this->static_multi_selectbox_field_id, $this->database_value_label)->returnsDar(array(
            'id'    => $this->database_value_id,
            'label' => $this->database_value_label,
        ));

        $this->xml_element = new SimpleXMLElement('<?xml version="1.0"?>
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

        $this->xml_mapping = new TrackerXmlFieldsMapping_InSamePlatform();
    }

    public function itCreatesArtifactWithAllMultiSelectboxValue()
    {
        $this->artifact->shouldReceive('getId')->andReturn(101);
        $this->artifact->shouldReceive('getTracker')->andReturn(Mockery::spy(Tracker::class));

        $this->artifact_creator->shouldReceive('createBare')->once()->andReturn($this->artifact);

        $this->artifact_creator->shouldReceive('createFirstChangeset')
            ->once()
            ->with(
                Mockery::any(),
                Mockery::any(),
                Mockery::on(function ($data) {
                    return $data[$this->static_multi_selectbox_field_id] === array($this->ui_value_id, $this->database_value_id);
                }),
                Mockery::any(),
                Mockery::any(),
                false,
                Mockery::any()
            )
            ->andReturn($this->artifact);

        $this->importer->importFromXML($this->tracker, $this->xml_element, $this->extraction_path, $this->xml_mapping, $this->url_mapping, $this->config);
    }
}

class Tracker_Artifact_XMLImport_UserMultiSelectboxTest extends Tracker_Artifact_XMLImportBaseTest
{

    private $user_multi_selectbox_field;
    private $user_multi_selectbox_field_id = 456;

    private $user_01_id   = 101;
    private $user_02_id   = 102;

    public function setUp()
    {
        parent::setUp();
        $this->setUpGlobalsMockery();

        $this->user_multi_selectbox_field = \Mockery::spy(\Tracker_FormElement_Field_MultiSelectbox::class);
        stub($this->user_multi_selectbox_field)->getId()->returns($this->user_multi_selectbox_field_id);
        stub($this->user_multi_selectbox_field)->validateField()->returns(true);

        stub($this->formelement_factory)->getUsedFieldByName($this->tracker_id, 'multi_select_box_user')->returns(
            $this->user_multi_selectbox_field
        );

        $this->xml_element = new SimpleXMLElement('<?xml version="1.0"?>
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

        $this->jeanne = new PFUser([
            'user_id' => 101,
            'language_id' => 'en',
            'user_name' => 'jeanne'
        ]);

        $this->serge = new PFUser([
            'user_id' => 102,
            'language_id' => 'en',
            'user_name' => 'serge'
        ]);

        stub($this->user_manager)->getUserByIdentifier('jeanne')->returns($this->jeanne);
        stub($this->user_manager)->getUserByIdentifier('serge')->returns($this->serge);

        $this->xml_mapping = new TrackerXmlFieldsMapping_InSamePlatform();
    }

    public function itCreatesArtifactWithAllMultiSelectboxValue()
    {
        $this->artifact->shouldReceive('getId')->andReturn(101);
        $this->artifact->shouldReceive('getTracker')->andReturn(Mockery::spy(Tracker::class));

        $this->artifact_creator->shouldReceive('createBare')->once()->andReturn($this->artifact);

        $this->artifact_creator->shouldReceive('createFirstChangeset')
            ->once()
            ->with(
                Mockery::any(),
                Mockery::any(),
                Mockery::on(function ($data) {
                    return $data[$this->user_multi_selectbox_field_id] === array($this->user_01_id, $this->user_02_id);
                }),
                Mockery::any(),
                Mockery::any(),
                false,
                Mockery::any()
            )
            ->andReturn($this->artifact);

        $this->importer->importFromXML($this->tracker, $this->xml_element, $this->extraction_path, $this->xml_mapping, $this->url_mapping, $this->config);
    }
}

class Tracker_Artifact_XMLImport_ChangesetsCreationFailureTest extends Tracker_Artifact_XMLImportBaseTest
{

    private $xml_element;

    public function setUp()
    {
        parent::setUp();
        $this->setUpGlobalsMockery();

        $this->xml_element = new SimpleXMLElement('<?xml version="1.0"?>
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

        $this->artifact->shouldReceive('getId')->andReturn(101);
        $this->artifact->shouldReceive('getTracker')->andReturn(Mockery::spy(Tracker::class));

        $this->xml_mapping = new TrackerXmlFieldsMapping_InSamePlatform();
    }

    public function itCreatesTheLastChangesetEvenWhenTheIntermediateFails()
    {
        $this->artifact_creator->shouldReceive('createBare')->once()->andReturn($this->artifact);
        $this->artifact_creator->shouldReceive('createFirstChangeset')->once()->andReturn($this->artifact);

        $this->new_changeset_creator->shouldReceive('create')->andReturn(null);
        $this->new_changeset_creator->shouldReceive('create')->andReturn(\Mockery::spy(\Tracker_Artifact_Changeset::class));
        $this->new_changeset_creator->shouldReceive('create')->andReturn(\Mockery::spy(\Tracker_Artifact_Changeset::class));

        $this->importer->importFromXML($this->tracker, $this->xml_element, $this->extraction_path, $this->xml_mapping, $this->url_mapping, $this->config);
    }

    public function itCreatesTheLastChangesetEvenWhenTheIntermediateThrowsException()
    {
        $this->artifact_creator->shouldReceive('createBare')->once()->andReturn($this->artifact);
        $this->artifact_creator->shouldReceive('createFirstChangeset')->once()->andReturn($this->artifact);

        $this->new_changeset_creator->shouldReceive('create')->andThrow(new Exception('Bad luck'));
        $this->new_changeset_creator->shouldReceive('create')->andReturn(\Mockery::spy(\Tracker_Artifact_Changeset::class));
        $this->new_changeset_creator->shouldReceive('create')->andReturn(\Mockery::spy(\Tracker_Artifact_Changeset::class));

        $this->importer->importFromXML($this->tracker, $this->xml_element, $this->extraction_path, $this->xml_mapping, $this->url_mapping, $this->config);
    }
}

class Tracker_Artifact_XMLImport_ArtifactLinkTest extends Tracker_Artifact_XMLImportBaseTest
{
    private $field_id = 369;
    private $field;

    public function setUp()
    {
        parent::setUp();
        $this->setUpGlobalsMockery();
        $this->field = \Mockery::spy(\Tracker_FormElement_Field_ArtifactLink::class);
        stub($this->field)->getId()->returns($this->field_id);
        stub($this->field)->validateField()->returns(true);

        stub($this->formelement_factory)->getUsedFieldByName($this->tracker_id, 'artlink')->returns($this->field);
        $this->xml_mapping = new TrackerXmlFieldsMapping_InSamePlatform();
    }


    public function itShouldMapTheOldIdToTheNewOne()
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

            $art1 = \Mockery::spy(\Tracker_Artifact::class);
            stub($art1)->getId()->returns(1);
            stub($art1)->getTracker()->returns(Mockery::spy(Tracker::class));
            $art2 = \Mockery::spy(\Tracker_Artifact::class);
            stub($art2)->getId()->returns(2);
            stub($art2)->getTracker()->returns(Mockery::spy(Tracker::class));

            $this->artifact_creator->shouldReceive('createBare')->once()->andReturn($art1);
            $this->artifact_creator->shouldReceive('createBare')->once()->andReturn($art2);

            $artlink_strategy = \Mockery::mock(\Tracker_Artifact_XMLImport_XMLImportFieldStrategyArtifactLink::class)->makePartial()->shouldAllowMockingProtectedMethods();
            stub($artlink_strategy)->getLastChangeset()->returns(false);

            $this->importer->importFromXML($this->tracker, $xml_element, $this->extraction_path, $this->xml_mapping, $this->url_mapping, $this->config);
    }

    public function itNotifiesUnexistingArtifacts()
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

        $art1 = \Mockery::spy(\Tracker_Artifact::class);
        stub($art1)->getId()->returns(1);
        stub($art1)->getTracker()->returns(Mockery::spy(Tracker::class));
        $art2 = \Mockery::spy(\Tracker_Artifact::class);
        stub($art2)->getId()->returns(2);
        stub($art2)->getTracker()->returns(Mockery::spy(Tracker::class));

        $this->artifact_creator->shouldReceive('createBare')->once()->andReturn($art1);
        $this->artifact_creator->shouldReceive('createBare')->once()->andReturn($art2);

        $artlink_strategy = \Mockery::mock(\Tracker_Artifact_XMLImport_XMLImportFieldStrategyArtifactLink::class)->makePartial()->shouldAllowMockingProtectedMethods();
        stub($artlink_strategy)->getLastChangeset()->returns(false);

        $this->logger->shouldReceive('log')->with(\Psr\Log\LogLevel::ERROR, Mockery::any(), Mockery::any())->once();
        $this->importer->importFromXML($this->tracker, $xml_element, $this->extraction_path, $this->xml_mapping, $this->url_mapping, $this->config);
    }
}

class Tracker_Artifact_XMLImport_BadDateTest extends Tracker_Artifact_XMLImportBaseTest
{

    private $xml_element;


    public function setUp()
    {
        parent::setUp();
        $this->setUpGlobalsMockery();

        stub($this->artifact_creator)->create()->returns(\Mockery::spy(\Tracker_Artifact::class));

        $this->xml_element = new SimpleXMLElement('<?xml version="1.0"?>
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

        $this->xml_mapping = new TrackerXmlFieldsMapping_InSamePlatform();
    }

    public function itCreatesArtifactAtDate()
    {
        expect($this->artifact_creator)->create()->never();
        expect($this->artifact_creator)->createBare()->never();
        $this->logger->shouldReceive('log')->with(\Psr\Log\LogLevel::ERROR, Mockery::any(), Mockery::any())->once();

        $this->importer->importFromXML(
            $this->tracker,
            $this->xml_element,
            $this->extraction_path,
            $this->xml_mapping,
            $this->url_mapping,
            $this->config
        );
    }
}
