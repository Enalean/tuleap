<?php
/**
 * Copyright (c) Enalean, 2014 - 2017. All Rights Reserved.
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

require_once __DIR__.'/../bootstrap.php';

class Tracker_Artifact_XMLImportTest_XMLImport extends Tracker_Artifact_XMLImport {
    public function importFromXMLPublic(
        Tracker $tracker,
        SimpleXMLElement $xml_element,
        $extraction_path,
        TrackerXmlFieldsMapping $xml_fields_mapping
    ) {
        return $this->importFromXML($tracker, $xml_element, $extraction_path, $xml_fields_mapping);
    }
}

abstract class Tracker_Artifact_XMLImportBaseTest extends TuleapTestCase {
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

    protected $extraction_path;
    protected $john_doe;

    public function setUp() {
        parent::setUp();

        $this->tracker = partial_mock('Tracker', array('getWorkflow', 'getId'));
        stub($this->tracker)->getId()->returns($this->tracker_id);
        stub($this->tracker)->getWorkflow()->returns(mock('Workflow'));

        $this->artifact_creator      = mock('Tracker_ArtifactCreator');
        $this->new_changeset_creator = mock('Tracker_Artifact_Changeset_NewChangesetAtGivenDateCreator');

        $this->summary_field_id = 50;
        $this->formelement_factory = mock('Tracker_FormElementFactory');
        stub($this->formelement_factory)->getUsedFieldByName($this->tracker_id, 'summary')->returns(
            aStringField()->withId(50)->withProperty('maxchars', 'string', '0')->build()
        );

        $this->john_doe = aUser()->withId(200)->withUserName('john_doe')->build();
        $this->user_manager = mock('UserManager');
        stub($this->user_manager)->getUserByIdentifier('john_doe')->returns($this->john_doe);
        stub($this->user_manager)->getUserAnonymous()->returns(new PFUser(array('user_id' => 0)));

        $this->xml_import_helper = new XMLImportHelper($this->user_manager);

        $this->artifact = mock('Tracker_Artifact');

        $this->extraction_path = $this->getTmpDir();

        $this->static_value_dao = mock('Tracker_FormElement_Field_List_Bind_Static_ValueDao');

        $this->logger = mock('Logger');

        $this->response = mock('Response');
        $GLOBALS['Response'] = $this->response;

        $this->importer = new Tracker_Artifact_XMLImportTest_XMLImport(
            mock('XML_RNGValidator'),
            $this->artifact_creator,
            $this->new_changeset_creator,
            $this->formelement_factory,
            $this->xml_import_helper,
            $this->static_value_dao,
            $this->logger,
            false,
            mock('Tracker_ArtifactFactory'),
            mock('\Tuleap\Tracker\FormElement\Field\ArtifactLink\Nature\NatureDao')
        );
    }
}

class Tracker_Artifact_XMLImport_ZipArchiveTest extends Tracker_Artifact_XMLImportBaseTest {

    /** @var Tracker_Artifact_XMLImport_XMLImportZipArchive */
    private $archive;

    public function setUp() {
        parent::setUp();
        $this->importer = partial_mock('Tracker_Artifact_XMLImportTest_XMLImport', array('importFromXML'));
        $this->archive  = mock('Tracker_Artifact_XMLImport_XMLImportZipArchive');
        stub($this->archive)->getXML()->returns('<?xml version="1.0"?><artifacts />');
        stub($this->archive)->getExtractionPath()->returns($this->extraction_path);
        $this->xml_mapping = new TrackerXmlFieldsMapping_InSamePlatform();
    }

    public function itCallsImportFromXMLWithContentFromArchive() {
        $expected_content = simplexml_load_string('<?xml version="1.0"?><artifacts />');
        expect($this->importer)->importFromXML(
            $this->tracker,
            $expected_content,
            $this->extraction_path,
            $this->xml_mapping
        )->once();

        $this->importer->importFromArchive($this->tracker, $this->archive);
    }

    public function itAskToArchiveToExtractFiles() {
        expect($this->archive)->extractFiles()->once();

        $this->importer->importFromArchive($this->tracker, $this->archive);
    }

    public function itCleansUp() {
        expect($this->archive)->cleanUp()->once();

        $this->importer->importFromArchive($this->tracker, $this->archive);
    }
}

class Tracker_Artifact_XMLImport_HappyPathTest extends Tracker_Artifact_XMLImportBaseTest {

    private $xml_element;


    public function setUp() {
        parent::setUp();

        stub($this->artifact_creator)->create()->returns(mock('Tracker_Artifact'));
        stub($this->artifact_creator)->createBare()->returns(mock('Tracker_Artifact'));

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

    public function itCreatesArtifactOnTracker() {
        expect($this->artifact_creator)->createBare($this->tracker, '*', '*')->once();

        $this->importer->importFromXMLPublic(
            $this->tracker,
            $this->xml_element,
            $this->extraction_path,
            $this->xml_mapping
        );
    }

    public function itCreatesArtifactWithSummaryFieldData() {
        $data = array(
            $this->summary_field_id => 'Ça marche'
        );
        expect($this->artifact_creator)->createBare($this->tracker, '*', '*')->once();
        expect($this->new_changeset_creator)->create('*', $data, '*', '*', '*', '*', '*')->at(0);

        $this->importer->importFromXMLPublic(
            $this->tracker,
            $this->xml_element,
            $this->extraction_path,
            $this->xml_mapping
        );
    }

    public function itCreatedArtifactWithSubmitter() {
        expect($this->artifact_creator)->createBare($this->tracker, $this->john_doe, '*')->once();

        $this->importer->importFromXMLPublic(
            $this->tracker,
            $this->xml_element,
            $this->extraction_path,
            $this->xml_mapping
        );
    }

    public function itCreatesArtifactAtDate() {
        $expected_time = strtotime('2014-01-15T10:38:06+01:00');
        expect($this->artifact_creator)->createBare($this->tracker, '*', $expected_time)->once();

        $this->importer->importFromXMLPublic(
            $this->tracker,
            $this->xml_element,
            $this->extraction_path,
            $this->xml_mapping
        );
    }
}

class Tracker_Artifact_XMLImport_CommentsTest extends Tracker_Artifact_XMLImportBaseTest {

    private $xml_element;

    public function setUp() {
        parent::setUp();

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

        stub($this->artifact_creator)->create()->returns($this->artifact);
        stub($this->artifact_creator)->createBare()->returns($this->artifact);
        stub($this->new_changeset_creator)->create()->returns(mock('Tracker_Artifact_Changeset'));
        stub($this->artifact_creator)->createFirstChangeset()->returns(mock('Tracker_Artifact'));

        $this->xml_mapping = new TrackerXmlFieldsMapping_InSamePlatform();
    }

    public function itCreatesTheComments() {
        expect($this->artifact_creator)->createFirstChangeset()->count(1);
        expect($this->artifact_creator)->createFirstChangeset('*', '*', '*', '*', '*', false)->at(0);
        expect($this->new_changeset_creator)->create()->count(2);
        expect($this->new_changeset_creator)->create('*', '*', 'Some text', '*', '*', '*', Tracker_Artifact_Changeset_Comment::TEXT_COMMENT)->at(0);
        expect($this->new_changeset_creator)->create('*', '*', '<p>Some text</p>', '*', '*', '*', Tracker_Artifact_Changeset_Comment::HTML_COMMENT)->at(1);

        $this->importer->importFromXMLPublic(
            $this->tracker,
            $this->xml_element,
            $this->extraction_path,
            $this->xml_mapping
        );
    }
}

class Tracker_Artifact_XMLImport_CommentUpdatesTest extends Tracker_Artifact_XMLImportBaseTest {

    private $xml_element;
    private $changeset;

    public function setUp() {
        parent::setUp();

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

        $this->changeset = mock('Tracker_Artifact_Changeset');

        stub($this->artifact_creator)->create()->returns($this->artifact);
        stub($this->artifact_creator)->createBare()->returns($this->artifact);
        stub($this->new_changeset_creator)->create()->returns($this->changeset);
        stub($this->artifact_creator)->createFirstChangeset()->returns(mock('Tracker_Artifact'));

        $this->xml_mapping = new TrackerXmlFieldsMapping_InSamePlatform();
    }

    public function itCreatesTheCommentsWithUpdates() {
        expect($this->artifact_creator)->createFirstChangeset()->count(1);
        expect($this->artifact_creator)->createFirstChangeset('*', '*', '*', '*', '*', false)->at(0);
        expect($this->new_changeset_creator)->create()->count(1);
        expect($this->new_changeset_creator)->create('*', '*', 'Some text', '*', '*', '*', Tracker_Artifact_Changeset_Comment::TEXT_COMMENT)->at(0);

        expect($this->changeset)->updateCommentWithoutNotification(
            '<p>Some text</p>',
            $this->john_doe,
            Tracker_Artifact_Changeset_Comment::HTML_COMMENT,
            strtotime('2014-01-15T11:23:50+01:00')
        )->once();

        $this->importer->importFromXMLPublic(
            $this->tracker,
            $this->xml_element,
            $this->extraction_path,
            $this->xml_mapping
        );
    }
}

class Tracker_Artifact_XMLImport_NoFieldTest extends Tracker_Artifact_XMLImportBaseTest {

    private $xml_element;

    public function setUp() {
        parent::setUp();

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

    public function itThrowAnExceptionWhenFieldDoesntExist() {
        expect($this->artifact_creator)->createBare()->once();
        stub($this->artifact_creator)->createBare()->returns(mock('Tracker_Artifact'));

        expect($this->logger)->warn()->once();

        $this->importer->importFromXMLPublic(
            $this->tracker,
            $this->xml_element,
            $this->extraction_path,
            $this->xml_mapping
        );
    }
}

class Tracker_Artifact_XMLImport_UserTest extends Tracker_Artifact_XMLImportBaseTest {


    public function setUp() {
        parent::setUp();

        stub($this->artifact_creator)->create()->returns(mock('Tracker_Artifact'));
        stub($this->artifact_creator)->createFirstChangeset()->returns(mock('Tracker_Artifact'));

        $this->user_manager = mock('UserManager');
        stub($this->user_manager)->getUserAnonymous()->returns(new PFUser(array('user_id' => 0)));

        $this->xml_import_helper = new XMLImportHelper($this->user_manager);

        $this->importer = new Tracker_Artifact_XMLImportTest_XMLImport(
            mock('XML_RNGValidator'),
            $this->artifact_creator,
            $this->new_changeset_creator,
            $this->formelement_factory,
            $this->xml_import_helper,
            $this->static_value_dao,
            mock('Logger'),
            false,
            mock('Tracker_ArtifactFactory'),
            mock('\Tuleap\Tracker\FormElement\Field\ArtifactLink\Nature\NatureDao')
        );

        $this->xml_mapping = new TrackerXmlFieldsMapping_InSamePlatform();
    }

    public function itCreatesChangesetAsAnonymousWhenUserDoesntExists() {
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

        stub($this->artifact_creator)->createBare()->returns(mock('Tracker_Artifact'));
        expect($this->artifact_creator)->createBare($this->tracker, new isAnonymousUserWithEmailExpectation('jmalko'), '*')->once();

       $this->importer->importFromXMLPublic(
            $this->tracker,
            $xml_element,
            $this->extraction_path,
            $this->xml_mapping
        );
    }

    public function itLooksForUserIdWhenFormatIsId() {
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

        expect($this->user_manager)->getUserByIdentifier('id:700')->atLeastOnce();
        expect($this->user_manager)->getUserByIdentifier()->returns($this->john_doe);

        stub($this->artifact_creator)->createBare()->returns(mock('Tracker_Artifact'));
        expect($this->artifact_creator)->createBare($this->tracker, $this->john_doe, '*')->once();

        $this->importer->importFromXMLPublic(
            $this->tracker,
            $xml_element,
            $this->extraction_path,
            $this->xml_mapping
        );
    }

    public function itLooksForLdapIdWhenFormatIsLdap() {
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

        expect($this->user_manager)->getUserByIdentifier('ldapId:uid=jo,ou=people,dc=example,dc=com')->atLeastOnce();
        expect($this->user_manager)->getUserByIdentifier()->returns($this->john_doe);

        stub($this->artifact_creator)->createBare()->returns(mock('Tracker_Artifact'));
        expect($this->artifact_creator)->createBare($this->tracker, $this->john_doe, '*')->once();

        $this->importer->importFromXMLPublic(
            $this->tracker,
            $xml_element,
            $this->extraction_path,
            $this->xml_mapping
        );
    }

    public function itLooksForEmailWhenFormatIsEmail() {
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

        expect($this->user_manager)->getUserByIdentifier('email:jo@example.com')->atLeastOnce();
        expect($this->user_manager)->getUserByIdentifier()->returns($this->john_doe);

        stub($this->artifact_creator)->createBare()->returns(mock('Tracker_Artifact'));
        expect($this->artifact_creator)->createBare($this->tracker, $this->john_doe, '*')->once();

        $this->importer->importFromXMLPublic(
            $this->tracker,
            $xml_element,
            $this->extraction_path,
            $this->xml_mapping
        );
    }
}

class Tracker_Artifact_XMLImport_MultipleChangesetsTest extends Tracker_Artifact_XMLImportBaseTest {

    private $xml_element;

    public function setUp() {
        parent::setUp();

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

        stub($this->artifact_creator)->createBare()->returns($this->artifact);
        stub($this->artifact_creator)->create()->returns(mock('Tracker_Artifact_Changeset'));
        stub($this->artifact_creator)->createFirstChangeset()->returns(mock('Tracker_Artifact'));
        $this->xml_mapping = new TrackerXmlFieldsMapping_InSamePlatform();
    }

    public function itCreatesTwoChangesets() {
        expect($this->artifact_creator)->createBare()->once();

        expect($this->artifact_creator)->createFirstChangeset()->count(1);
        expect($this->new_changeset_creator)->create()->count(1);
        //expect($this->new_changeset_creator)->create()->at(1);

        $this->importer->importFromXMLPublic(
            $this->tracker,
            $this->xml_element,
            $this->extraction_path,
            $this->xml_mapping
        );
    }

    public function itCreatesTheNewChangesetWithSummaryValue() {
        $data = array(
            $this->summary_field_id => '^Wit updates'
        );

        expect($this->artifact_creator)->createFirstChangeset()->count(1);
        expect($this->artifact_creator)->createFirstChangeset('*', '*', '*', '*', '*', false)->at(0);
        expect($this->new_changeset_creator)->create()->count(1);
        expect($this->new_changeset_creator)->create($this->artifact, $data, '*', '*', '*', '*', '*')->at(0);

        $this->importer->importFromXMLPublic(
            $this->tracker,
            $this->xml_element,
            $this->extraction_path,
            $this->xml_mapping
        );
    }

    public function itCreatesTheNewChangesetWithSubmitter() {
        expect($this->artifact_creator)->createFirstChangeset()->count(1);
        expect($this->artifact_creator)->createFirstChangeset('*', '*', '*', '*', '*', false)->at(0);
        expect($this->new_changeset_creator)->create()->count(1);
        expect($this->new_changeset_creator)->create($this->artifact, '*', '*', $this->john_doe, '*', '*', '*')->at(0);

        $this->importer->importFromXMLPublic(
            $this->tracker,
            $this->xml_element,
            $this->extraction_path,
            $this->xml_mapping
        );
    }

    public function itCreatesTheNewChangesetWithoutNotification() {
        expect($this->artifact_creator)->createFirstChangeset()->count(1);
        expect($this->artifact_creator)->createFirstChangeset('*', '*', '*', '*', '*', false)->at(0);
        expect($this->new_changeset_creator)->create()->count(1);
        expect($this->new_changeset_creator)->create($this->artifact, '*', '*', '*', '*', false, '*')->at(0); // or ->once() ?

        $this->importer->importFromXMLPublic(
            $this->tracker,
            $this->xml_element,
            $this->extraction_path,
            $this->xml_mapping
        );
    }


    public function itCreatesTheChangesetsAccordingToDates() {
        expect($this->artifact_creator)->createBare($this->tracker, '*', strtotime('2014-01-15T10:38:06+01:00'))->once();

        expect($this->artifact_creator)->createFirstChangeset()->count(1);
        expect($this->artifact_creator)->createFirstChangeset('*', '*', '*', '*', strtotime('2014-01-15T10:38:06+01:00'), false)->at(0);
        expect($this->new_changeset_creator)->create()->count(1);
        expect($this->new_changeset_creator)->create($this->artifact, '*', '*', '*', strtotime('2014-01-15T11:03:50+01:00'), '*', '*')->at(0);

        $this->importer->importFromXMLPublic(
            $this->tracker,
            $this->xml_element,
            $this->extraction_path,
            $this->xml_mapping
        );
    }

    public function itCreatesTheChangesetsInAscendingDatesEvenWhenChangesetsAreMixedInXML() {
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

        expect($this->artifact_creator)->createBare($this->tracker, '*', strtotime('2014-01-15T10:38:06+01:00'))->once();

        expect($this->artifact_creator)->createFirstChangeset()->count(1);
        expect($this->artifact_creator)->createFirstChangeset('*', '*', '*', '*', strtotime('2014-01-15T10:38:06+01:00'), false)->at(0);
        expect($this->new_changeset_creator)->create()->count(1);
        //sorted by submitted_on
        expect($this->new_changeset_creator)->create($this->artifact, '*', '*', '*', strtotime('2014-01-15T11:03:50+01:00'), '*', '*')->at(0);
        $this->importer->importFromXMLPublic(
            $this->tracker,
            $this->xml_element,
            $this->extraction_path,
            $this->xml_mapping
        );
    }

    public function itKeepsTheOriginalOrderWhenTwoDatesAreEqual() {
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

        expect($this->artifact_creator)->createBare('*', '*', strtotime('2014-01-15T10:38:06+01:00'))->once();
        expect($this->artifact_creator)->createFirstChangeset('*', '*', new FieldDataExpectation(array($this->summary_field_id => 'First')), '*', strtotime('2014-01-15T10:38:06+01:00'), '*')->once();

        expect($this->new_changeset_creator)->create()->count(3);
        expect($this->new_changeset_creator)->create($this->artifact, new FieldDataExpectation(array($this->summary_field_id => 'Second')), '*', '*', strtotime('2014-01-15T11:03:50+01:00'), '*', '*')->at(0);
        expect($this->new_changeset_creator)->create($this->artifact, new FieldDataExpectation(array($this->summary_field_id => 'Third')), '*', '*', strtotime('2014-01-15T11:03:50+01:00'), '*', '*')->at(1);
        expect($this->new_changeset_creator)->create($this->artifact, new FieldDataExpectation(array($this->summary_field_id => 'Fourth')), '*', '*', strtotime('2014-01-15T11:51:50+01:00'), '*', '*')->at(2);

        $this->importer->importFromXMLPublic(
            $this->tracker,
            $this->xml_element,
            $this->extraction_path,
            $this->xml_mapping
        );
    }
}

class FieldDataExpectation extends SimpleExpectation {

    private $expected_field_data;

    public function __construct($expected_field_data) {
        parent::__construct();
        $this->expected_field_data = $expected_field_data;
    }

    public function test($compare) {
        if ($this->expected_field_data == $compare) {
            return true;
        }
        return false;
    }

    public function testMessage($compare) {
        return "Expected ".$this->flatten($this->expected_field_data).' got '.$this->flatten($compare);
    }

    private function flatten($array) {
        $str_chunks = array();
        foreach ($array as $key => $value) {
            $str_chunks[]= "$key => $value";
        }
        return 'array('.implode(', ', $str_chunks).')';
    }
}

class Tracker_Artifact_XMLImport_SeveralArtifactsTest extends Tracker_Artifact_XMLImportBaseTest {

    private $xml_element;


    public function setUp() {
        parent::setUp();

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

    public function itCreatesTwoArtifactsOnTracker() {
        stub($this->artifact_creator)->createBare()->returns(mock('Tracker_Artifact'));
        expect($this->artifact_creator)->createBare()->count(2);
        expect($this->artifact_creator)->createBare($this->tracker, '*', strtotime('2014-01-15T10:38:06+01:00'))->at(0);
        expect($this->artifact_creator)->createBare($this->tracker, '*', strtotime('2014-01-16T11:38:06+01:00'))->at(1);

        $this->importer->importFromXMLPublic(
            $this->tracker,
            $this->xml_element,
            $this->extraction_path,
            $this->xml_mapping
        );
    }
}

class isAnonymousUserWithEmailExpectation extends SimpleExpectation {

    private $email;

    public function __construct($email) {
        $this->email = $email;
    }

    public function test($user) {
        return ($user instanceof PFUser && $user->isAnonymous() && $user->getEmail() == $this->email);
    }

    public function testMessage($user) {
        return "An anonymous user with email `{$this->email}` expected, ($user) given";
    }
}

class Tracker_Artifact_XMLImport_OneArtifactWithAttachementTest extends Tracker_Artifact_XMLImportBaseTest {

    private $file_field_id = 51;

    public function setUp() {
        parent::setUp();

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
        touch($this->extraction_path.'/34_File33.png');

        stub($this->artifact_creator)->createBare()->returns(mock('Tracker_Artifact'));
        stub($this->new_changeset_creator)->create()->returns(mock('Tracker_Artifact_Changeset'));
        stub($this->artifact_creator)->createFirstChangeset()->returns(mock('Tracker_Artifact'));
        $this->xml_mapping = new TrackerXmlFieldsMapping_InSamePlatform();
    }

    public function itCreatesAChangesetWithSummaryWhenFileFormElementDoesNotExist() {
        stub($this->artifact_creator)->createBare()->returns(mock('Tracker_Artifact'));
        $data = array(
            $this->summary_field_id => 'Newly submitted'
        );
        expect($this->artifact_creator)->createBare($this->tracker, '*', '*')->once();
        expect($this->artifact_creator)->createFirstChangeset()->count(1);
        expect($this->artifact_creator)->createFirstChangeset('*', '*', $data, '*', '*', false)->at(0);

        $this->importer->importFromXMLPublic($this->tracker, $this->xml_element, $this->extraction_path, $this->xml_mapping);
    }

    public function itCreatesAChangesetWithOneFileElement() {
        stub($this->artifact_creator)->create()->returns(mock('Tracker_Artifact'));
        stub($this->formelement_factory)->getUsedFieldByName($this->tracker_id, 'attachment')->returns(
            aFileField()->withId($this->file_field_id)->build()
        );

        $data = array(
            $this->file_field_id    => array(
                array(
                    'is_migrated'  => true,
                    'submitted_by' => $this->john_doe,
                    'name'         => 'A.png',
                    'type'         => 'image/png',
                    'description'  => 'None',
                    'size'         => 87947,
                    'tmp_name'     => $this->extraction_path.'/34_File33.png',
                    'error'        => UPLOAD_ERR_OK,
               )
            ),
            $this->summary_field_id => 'Newly submitted'
        );

        expect($this->artifact_creator)->createBare($this->tracker, '*', '*')->once();
        expect($this->artifact_creator)->createFirstChangeset()->count(1);
        expect($this->artifact_creator)->createFirstChangeset('*', '*', $data, '*', '*', false)->at(0);
        $this->importer->importFromXMLPublic($this->tracker, $this->xml_element, $this->extraction_path, $this->xml_mapping);
    }
}

class Tracker_Artifact_XMLImport_AttachmentNoLongerExistsTest extends Tracker_Artifact_XMLImportBaseTest {

    private $file_field_id = 51;

    public function setUp() {
        parent::setUp();

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

        stub($this->artifact_creator)->createBare()->returns(mock('Tracker_Artifact'));
        stub($this->artifact_creator)->createFirstChangeset()->returns(mock('Tracker_Artifact'));
        stub($this->formelement_factory)->getUsedFieldByName($this->tracker_id, 'attachment')->returns(
            aFileField()->withId($this->file_field_id)->build()
        );

        $this->xml_mapping = new TrackerXmlFieldsMapping_InSamePlatform();
    }

    public function itSkipsFieldWithoutValidFile() {
        $data = array(
            $this->summary_field_id => 'Newly submitted'
        );
        expect($this->artifact_creator)->createBare($this->tracker, '*', '*')->once();
        expect($this->artifact_creator)->createFirstChangeset()->count(1);
        expect($this->artifact_creator)->createFirstChangeset('*', '*', $data, '*', '*', false)->at(0);

        $this->importer->importFromXMLPublic($this->tracker, $this->xml_element, $this->extraction_path, $this->xml_mapping);
    }
}

class Tracker_Artifact_XMLImport_OneArtifactWithMultipleAttachementsTest extends Tracker_Artifact_XMLImportBaseTest {

    private $file_field_id = 51;

    public function setUp() {
        parent::setUp();

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
        touch($this->extraction_path.'/34_File33.png');
        touch($this->extraction_path.'/34_File34.pdf');

        $this->xml_mapping = new TrackerXmlFieldsMapping_InSamePlatform();
    }

    public function itCreatesAChangesetWithTwoFileElements() {
        stub($this->artifact_creator)->createBare()->returns(mock('Tracker_Artifact'));
        stub($this->artifact_creator)->createFirstChangeset()->returns(mock('Tracker_Artifact'));
        stub($this->formelement_factory)->getUsedFieldByName($this->tracker_id, 'attachment')->returns(
            aFileField()->withId($this->file_field_id)->build()
        );

        $data = array(
            $this->file_field_id    => array(
                array(
                    'is_migrated'  => true,
                    'submitted_by' => $this->john_doe,
                    'name'         => 'A.png',
                    'type'         => 'image/png',
                    'description'  => 'None',
                    'size'         => 87947,
                    'tmp_name'     => $this->extraction_path.'/34_File33.png',
                    'error'        => UPLOAD_ERR_OK,
                ),
                array(
                    'is_migrated'  => true,
                    'submitted_by' => $this->john_doe,
                    'name'         => 'B.pdf',
                    'type'         => 'application/x-download',
                    'description'  => 'A Zuper File',
                    'size'         => 84895,
                    'tmp_name'     => $this->extraction_path.'/34_File34.pdf',
                    'error'        => UPLOAD_ERR_OK,
                )
            ),
            $this->summary_field_id => 'Newly submitted'
        );
        expect($this->artifact_creator)->createBare($this->tracker, '*', '*')->once();
        expect($this->artifact_creator)->createFirstChangeset('*', '*', $data, '*', '*', false)->once();

        $this->importer->importFromXMLPublic($this->tracker, $this->xml_element, $this->extraction_path, $this->xml_mapping);
    }
}

class Tracker_Artifact_XMLImport_OneArtifactWithMultipleAttachementsAndChangesetsTest extends Tracker_Artifact_XMLImportBaseTest {

    private $file_field_id = 51;

    public function setUp() {
        parent::setUp();

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
        touch($this->extraction_path.'/34_File33.png');
        touch($this->extraction_path.'/34_File34.pdf');

        $this->xml_mapping = new TrackerXmlFieldsMapping_InSamePlatform();
    }

    public function itCreatesChangesetsThatOnlyReferenceConcernedFiles() {
        $artifact = mock('Tracker_Artifact');
        stub($this->artifact_creator)->createBare()->returns($artifact);
        stub($this->new_changeset_creator)->create()->returns(mock('Tracker_Artifact_Changeset'));
        stub($this->artifact_creator)->createFirstChangeset()->returns(mock('Tracker_Artifact'));
        stub($this->formelement_factory)->getUsedFieldByName($this->tracker_id, 'attachment')->returns(
            aFileField()->withId($this->file_field_id)->build()
        );

        $initial_changeset_data = array(
            $this->file_field_id    => array(
                array(
                    'is_migrated'  => true,
                    'submitted_by' => $this->john_doe,
                    'name'         => 'A.png',
                    'type'         => 'image/png',
                    'description'  => 'None',
                    'size'         => 87947,
                    'tmp_name'     => $this->extraction_path.'/34_File33.png',
                    'error'        => UPLOAD_ERR_OK,
                )
            ),
            $this->summary_field_id => 'Newly submitted'
        );

        $second_changeset_data = array(
            $this->file_field_id    => array(
                array(
                    'is_migrated'  => true,
                    'submitted_by' => $this->john_doe,
                    'name'         => 'B.pdf',
                    'type'         => 'application/x-download',
                    'description'  => 'A Zuper File',
                    'size'         => 84895,
                    'tmp_name'     => $this->extraction_path.'/34_File34.pdf',
                    'error'        => UPLOAD_ERR_OK,
                )
            )
        );
        expect($this->artifact_creator)->createBare($this->tracker, '*', '*')->once();
        expect($this->artifact_creator)->createFirstChangeset('*', '*', $initial_changeset_data, '*', '*', false)->once();
        expect($this->new_changeset_creator)->create()->count(1);
        expect($this->new_changeset_creator)->create($artifact, $second_changeset_data, '*', '*', '*', '*', '*')->at(0);

        $this->importer->importFromXMLPublic($this->tracker, $this->xml_element, $this->extraction_path, $this->xml_mapping);
    }
}

class Tracker_Artifact_XMLImport_CCListTest extends Tracker_Artifact_XMLImportBaseTest {

    private $xml_element;

    private $cc_field_id = 369;
    private $open_list_field;

    public function setUp() {
        parent::setUp();

        stub($this->artifact_creator)->createBare()->returns(mock('Tracker_Artifact'));
        stub($this->artifact_creator)->createFirstChangeset()->returns(mock('Tracker_Artifact'));

        $this->open_list_field = mock('Tracker_FormElement_Field_OpenList');
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

    public function itDelegatesOpenListComputationToField() {
        expect($this->open_list_field)->getFieldData()->count(2);
        expect($this->open_list_field)->getFieldData('homer')->at(0);
        expect($this->open_list_field)->getFieldData('jeanjean')->at(1);

        $this->importer->importFromXMLPublic($this->tracker, $this->xml_element, $this->extraction_path, $this->xml_mapping);
    }

    public function itCreatesArtifactWithCCFieldData() {
        stub($this->open_list_field)->getFieldData('homer')->returns('!112');
        stub($this->open_list_field)->getFieldData('jeanjean')->returns('!113');

        $data = array(
            $this->cc_field_id => '!112,!113'
        );
        expect($this->artifact_creator)->createBare()->once();
        expect($this->artifact_creator)->createFirstChangeset('*', '*', $data, '*', '*', false)->once();

        $this->importer->importFromXMLPublic($this->tracker, $this->xml_element, $this->extraction_path, $this->xml_mapping);
    }
}

class Tracker_Artifact_XMLImport_PermsOnArtifactTest extends Tracker_Artifact_XMLImportBaseTest {

    private $xml_element;

    private $perms_field_id = 369;
    private $perms_field;

    public function setUp() {
        parent::setUp();

        stub($this->artifact_creator)->createBare()->returns(mock('Tracker_Artifact'));
        stub($this->artifact_creator)->createFirstChangeset()->returns(mock('Tracker_Artifact'));
        $this->perms_field = mock('Tracker_FormElement_Field_PermissionsOnArtifact');
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

    public function itCreatesArtifactWithPermsFieldData() {
        $data = array(
            $this->perms_field_id => array(
                'use_artifact_permissions' => 1,
                'u_groups' => array(15, 101)
            )
        );
        expect($this->artifact_creator)->createBare()->once();
        expect($this->artifact_creator)->createFirstChangeset('*', '*', $data, '*', '*', false)->once();

        $this->importer->importFromXMLPublic($this->tracker, $this->xml_element, $this->extraction_path, $this->xml_mapping);
    }
}

class Tracker_Artifact_XMLImport_TextTest extends Tracker_Artifact_XMLImportBaseTest {

    private $xml_element;

    private $text_field_id = 369;
    private $text_field;

    public function setUp() {
        parent::setUp();

        stub($this->artifact_creator)->createBare()->returns(mock('Tracker_Artifact'));
        stub($this->artifact_creator)->createFirstChangeset()->returns(mock('Tracker_Artifact'));

        $this->text_field = mock('Tracker_FormElement_Field_Text');
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

    public function itCreatesArtifactWithTextData() {
        $data = array(
            $this->text_field_id => array(
                'format'  => 'html',
                'content' => 'test'
            )
        );
        expect($this->artifact_creator)->createBare()->once();
        expect($this->artifact_creator)->createFirstChangeset('*', '*', $data, '*', '*', false)->once();

        $this->importer->importFromXMLPublic($this->tracker, $this->xml_element, $this->extraction_path, $this->xml_mapping);
    }
}

class Tracker_Artifact_XMLImport_AlphanumericTest extends Tracker_Artifact_XMLImportBaseTest {

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

    public function setUp() {
        parent::setUp();

        stub($this->artifact_creator)->createBare()->returns(mock('Tracker_Artifact'));
        stub($this->artifact_creator)->createFirstChangeset()->returns(mock('Tracker_Artifact'));
        $this->string_field = mock('Tracker_FormElement_Field_String');
        stub($this->string_field)->getId()->returns($this->string_field_id);
        stub($this->string_field)->validateField()->returns(true);
        $this->int_field    = mock('Tracker_FormElement_Field_Integer');
        stub($this->int_field)->getId()->returns($this->int_field_id);
        stub($this->int_field)->validateField()->returns(true);
        $this->float_field  = mock('Tracker_FormElement_Field_Float');
        stub($this->float_field)->getId()->returns($this->float_field_id);
        stub($this->float_field)->validateField()->returns(true);
        $this->date_field   = partial_mock(
            'Tracker_FormElement_Field_Date',
            array('getId', 'validateField', 'isTimeDisplayed')
        );
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
    }

    public function itCreatesArtifactWithAlphanumFieldData() {
        $data = array(
            $this->string_field_id => 'Import artifact in tracker v5',
            $this->int_field_id    => '5',
            $this->float_field_id  => '4.5',
            $this->date_field_id   => '2014-03-20',
        );

        stub($this->date_field)->isTimeDisplayed()->returns(false);

        expect($this->artifact_creator)->createBare()->once();
        expect($this->artifact_creator)->createFirstChangeset('*', '*', $data, '*', '*', false)->once();

        $this->importer->importFromXMLPublic($this->tracker, $this->xml_element, $this->extraction_path, $this->xml_mapping);
    }

     public function itCreatesArtifactWithAlphanumFieldDataAndTimeDisplayedDate() {
        $data = array(
            $this->string_field_id => 'Import artifact in tracker v5',
            $this->int_field_id    => '5',
            $this->float_field_id  => '4.5',
            $this->date_field_id   => '2014-03-20 10:13',
        );

        stub($this->date_field)->isTimeDisplayed()->returns(true);

        expect($this->artifact_creator)->createBare()->once();
        expect($this->artifact_creator)->createFirstChangeset('*', '*', $data, '*', '*', false)->once();

        $this->importer->importFromXMLPublic($this->tracker, $this->xml_element, $this->extraction_path, $this->xml_mapping);
    }

    public function itDoesntConvertEmptyDateInto70sdate() {
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

        $data = array(
            $this->date_field_id   => '',
        );
        expect($this->artifact_creator)->createBare()->once();
        expect($this->artifact_creator)->createFirstChangeset('*', '*', $data, '*', '*', false)->once();

        $this->importer->importFromXMLPublic($this->tracker, $this->xml_element, $this->extraction_path, $this->xml_mapping);
    }
}

class Tracker_Artifact_XMLImport_SelectboxTest extends Tracker_Artifact_XMLImportBaseTest {

    private $status_field;
    private $status_field_id = 234;
    private $assto_field;
    private $assto_field_id = 456;
    private $open_value_id = 104;

    public function setUp() {
        parent::setUp();

        stub($this->artifact_creator)->createBare()->returns(mock('Tracker_Artifact'));
        stub($this->artifact_creator)->createFirstChangeset()->returns(mock('Tracker_Artifact'));

        $this->status_field = mock('Tracker_FormElement_Field_String');
        stub($this->status_field)->getId()->returns($this->status_field_id);
        stub($this->status_field)->validateField()->returns(true);
        $this->assto_field  = mock('Tracker_FormElement_Field_String');
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

    public function itCreatesArtifactWithSelectboxValue() {
        $data = array(
            $this->status_field_id => array($this->open_value_id),
            $this->assto_field_id  => array($this->john_doe->getId()),
        );
        expect($this->artifact_creator)->createBare()->once();
        expect($this->artifact_creator)->createFirstChangeset('*', '*', $data, '*', '*', false)->once();

        $this->importer->importFromXMLPublic($this->tracker, $this->xml_element, $this->extraction_path, $this->xml_mapping);
    }
}

class Tracker_Artifact_XMLImport_StaticMultiSelectboxTest extends Tracker_Artifact_XMLImportBaseTest {

    private $static_multi_selectbox_field;
    private $static_multi_selectbox_field_id = 456;

    private $ui_value_id          = 101;
    private $ui_value_label       = "UI";
    private $database_value_id    = 102;
    private $database_value_label = "Database";

    public function setUp() {
        parent::setUp();

        stub($this->artifact_creator)->createBare()->returns(mock('Tracker_Artifact'));
        stub($this->artifact_creator)->createFirstChangeset()->returns(mock('Tracker_Artifact'));

        $this->static_multi_selectbox_field = mock('Tracker_FormElement_Field_MultiSelectbox');
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

    public function itCreatesArtifactWithAllMultiSelectboxValue() {
        $data = array(
            $this->static_multi_selectbox_field_id => array($this->ui_value_id, $this->database_value_id),
        );
        expect($this->artifact_creator)->createBare()->once();
        expect($this->artifact_creator)->createFirstChangeset('*', '*', $data, '*', '*', false)->once();

        $this->importer->importFromXMLPublic($this->tracker, $this->xml_element, $this->extraction_path, $this->xml_mapping);
    }
}

class Tracker_Artifact_XMLImport_UserMultiSelectboxTest extends Tracker_Artifact_XMLImportBaseTest {

    private $user_multi_selectbox_field;
    private $user_multi_selectbox_field_id = 456;

    private $user_01_id   = 101;
    private $user_02_id   = 102;

    public function setUp() {
        parent::setUp();

        stub($this->artifact_creator)->createBare()->returns(mock('Tracker_Artifact'));
        stub($this->artifact_creator)->createFirstChangeset()->returns(mock('Tracker_Artifact'));

        $this->user_multi_selectbox_field = mock('Tracker_FormElement_Field_MultiSelectbox');
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

        $this->jeanne = aUser()->withId(101)->withUserName('jeanne')->build();
        $this->serge  = aUser()->withId(102)->withUserName('serge')->build();

        stub($this->user_manager)->getUserByIdentifier('jeanne')->returns($this->jeanne);
        stub($this->user_manager)->getUserByIdentifier('serge')->returns($this->serge);

        $this->xml_mapping = new TrackerXmlFieldsMapping_InSamePlatform();
    }

    public function itCreatesArtifactWithAllMultiSelectboxValue() {
        $data = array(
            $this->user_multi_selectbox_field_id => array(
                $this->user_01_id,
                $this->user_02_id
            ),
        );
        expect($this->artifact_creator)->createBare()->once();
        expect($this->artifact_creator)->createFirstChangeset('*', '*', $data, '*', '*', false)->once();

        $this->importer->importFromXMLPublic($this->tracker, $this->xml_element, $this->extraction_path, $this->xml_mapping);
    }
}

class Tracker_Artifact_XMLImport_ChangesetsCreationFailureTest extends Tracker_Artifact_XMLImportBaseTest {

    private $xml_element;

    public function setUp() {
        parent::setUp();

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

        stub($this->artifact_creator)->createBare()->returns($this->artifact);
        stub($this->artifact_creator)->createFirstChangeset()->returns(mock('Tracker_Artifact'));

        $this->xml_mapping = new TrackerXmlFieldsMapping_InSamePlatform();
    }

    public function itCreatesTheLastChangesetEvenWhenTheIntermediateFails() {
        stub($this->new_changeset_creator)->create()->returnsAt(0, null);
        stub($this->new_changeset_creator)->create()->returnsAt(1, mock('Tracker_Artifact_Changeset'));
        stub($this->new_changeset_creator)->create()->returnsAt(2, mock('Tracker_Artifact_Changeset'));

        expect($this->artifact_creator)->createBare()->once();
        expect($this->artifact_creator)->createFirstChangeset()->count(1);
        expect($this->new_changeset_creator)->create()->count(2); // or 2

        $this->importer->importFromXMLPublic($this->tracker, $this->xml_element, $this->extraction_path, $this->xml_mapping);
    }

    public function itCreatesTheLastChangesetEvenWhenTheIntermediateThrowsException() {
        stub($this->new_changeset_creator)->create()->throwsAt(0, new Exception('Bad luck'));
        stub($this->new_changeset_creator)->create()->returnsAt(1, mock('Tracker_Artifact_Changeset'));
        stub($this->new_changeset_creator)->create()->returnsAt(2, mock('Tracker_Artifact_Changeset'));

        expect($this->artifact_creator)->createBare()->once();
        expect($this->artifact_creator)->createFirstChangeset()->once();
        expect($this->new_changeset_creator)->create()->count(2);

        $this->importer->importFromXMLPublic($this->tracker, $this->xml_element, $this->extraction_path, $this->xml_mapping);
    }
}

class Tracker_Artifact_XMLImport_ArtifactLinkTest extends Tracker_Artifact_XMLImportBaseTest {
    private $field_id = 369;
    private $field;

    public function setUp() {
        parent::setUp();
        $this->field = mock('Tracker_FormElement_Field_ArtifactLink');
        stub($this->field)->getId()->returns($this->field_id);
        stub($this->field)->validateField()->returns(true);

        stub($this->formelement_factory)->getUsedFieldByName($this->tracker_id, 'artlink')->returns($this->field);
        $this->xml_mapping = new TrackerXmlFieldsMapping_InSamePlatform();
    }


    public function itShouldMapTheOldIdToTheNewOne() {
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

            $art1 = mock('Tracker_Artifact');
            stub($art1)->getId()->returns(1);
            $art2 = mock('Tracker_Artifact');
            stub($art2)->getId()->returns(2);
            stub($this->artifact_creator)->createBare()->returnsAt(0, $art1);
            stub($this->artifact_creator)->createBare()->returnsAt(1, $art2);

            $artlink_strategy = partial_mock(
                'Tracker_Artifact_XMLImport_XMLImportFieldStrategyArtifactLink',
                array('getLastChangeset', 'createNatureIfNotExists')
            );
            stub($artlink_strategy)->getLastChangeset()->returns(false);

            $this->importer->importFromXMLPublic($this->tracker, $xml_element, $this->extraction_path, $this->xml_mapping);
    }

    public function itNotifiesUnexistingArtifacts() {
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
        $art1 = mock('Tracker_Artifact');
        stub($art1)->getId()->returns(1);
        $art2 = mock('Tracker_Artifact');
        stub($art2)->getId()->returns(2);
        stub($this->artifact_creator)->createBare()->returnsAt(0, $art1);
        stub($this->artifact_creator)->createBare()->returnsAt(1, $art2);

        $artlink_strategy = partial_mock(
            'Tracker_Artifact_XMLImport_XMLImportFieldStrategyArtifactLink',
            array('getLastChangeset', 'createNatureIfNotExists')
        );
        stub($artlink_strategy)->getLastChangeset()->returns(false);

        expect($this->logger)->error()->count(1);
        $this->importer->importFromXMLPublic($this->tracker, $xml_element, $this->extraction_path, $this->xml_mapping);
    }
}

class Tracker_Artifact_XMLImport_BadDateTest extends Tracker_Artifact_XMLImportBaseTest {

    private $xml_element;


    public function setUp() {
        parent::setUp();

        stub($this->artifact_creator)->create()->returns(mock('Tracker_Artifact'));

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

    public function itCreatesArtifactAtDate() {
        expect($this->artifact_creator)->create()->never();
        expect($this->artifact_creator)->createBare()->never();
        expect($this->logger)->error()->once();

        $this->importer->importFromXMLPublic(
            $this->tracker,
            $this->xml_element,
            $this->extraction_path,
            $this->xml_mapping
        );
    }

}
