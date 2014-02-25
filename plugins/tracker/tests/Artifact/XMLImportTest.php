<?php
/**
 * Copyright (c) Enalean, 2014. All Rights Reserved.
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

require_once TRACKER_BASE_DIR . '/../tests/bootstrap.php';

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

    protected $john_doe;
    protected $formelement_factory;
    protected $user_manager;
    protected $artifact;
    protected $extraction_path;


    public function setUp() {
        parent::setUp();
        $this->tracker  = aTracker()->withId($this->tracker_id)->build();
        $this->artifact_creator      = mock('Tracker_ArtifactCreator');
        $this->new_changeset_creator = mock('Tracker_Artifact_Changeset_NewChangesetAtGivenDateCreator');

        $this->summary_field_id = 50;
        $this->formelement_factory = mock('Tracker_FormElementFactory');
        stub($this->formelement_factory)->getFormElementByName($this->tracker_id, 'summary')->returns(
            aStringField()->withId(50)->build()
        );

        $this->john_doe = aUser()->withId(200)->withUserName('john_doe')->build();
        $this->user_manager = mock('UserManager');
        stub($this->user_manager)->getUserByIdentifier('john_doe')->returns($this->john_doe);
        stub($this->user_manager)->getUserAnonymous()->returns(new PFUser(array('user_id' => 0)));

        $this->artifact = mock('Tracker_Artifact');

        $this->extraction_path = '/some/random/path';

        $this->importer = new Tracker_Artifact_XMLImport(
            mock('XML_RNGValidator'),
            $this->artifact_creator,
            $this->new_changeset_creator,
            $this->formelement_factory,
            $this->user_manager
        );
    }
}

class Tracker_Artifact_XMLImport_ZipArchiveTest extends Tracker_Artifact_XMLImportBaseTest {

    /** @var Tracker_Artifact_XMLImport_XMLImportZipArchive */
    private $archive;

    public function setUp() {
        parent::setUp();
        $this->importer = partial_mock('Tracker_Artifact_XMLImport', array('importFromXML'));
        $this->archive  = mock('Tracker_Artifact_XMLImport_XMLImportZipArchive');
        stub($this->archive)->getXML()->returns('<?xml version="1.0"?><artifacts />');
        stub($this->archive)->getExtractionPath()->returns($this->extraction_path);
    }

    public function itCallsImportFromXMLWithContentFromArchive() {
        $expected_content = simplexml_load_string('<?xml version="1.0"?><artifacts />');
        expect($this->importer)->importFromXML($this->tracker, $expected_content, $this->extraction_path)->once();

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

        $this->xml_element = new SimpleXMLElement('<?xml version="1.0"?>
            <artifacts>
              <artifact id="4918">
                <changeset>
                  <submitted_by format="username">john_doe</submitted_by>
                  <submitted_on format="ISO8601">2014-01-15T10:38:06+01:00</submitted_on>
                  <field_change field_name="summary">
                    <value>Ça marche</value>
                  </field_change>
                </changeset>
              </artifact>
            </artifacts>');
    }

    public function itCreatesArtifactOnTracker() {
        expect($this->artifact_creator)->create($this->tracker, '*', '*', '*', '*', '*')->once();

        $this->importer->importFromXML($this->tracker, $this->xml_element, $this->extraction_path);
    }

    public function itCreatesArtifactWithSummaryFieldData() {
        $data = array(
            $this->summary_field_id => 'Ça marche'
        );
        expect($this->artifact_creator)->create('*', $data, '*', '*', '*', '*')->once();

        $this->importer->importFromXML($this->tracker, $this->xml_element, $this->extraction_path);
    }

    public function itCreatedArtifactWithSubmitter() {
        expect($this->artifact_creator)->create('*', '*', $this->john_doe, '*', '*', '*')->once();

        $this->importer->importFromXML($this->tracker, $this->xml_element, $this->extraction_path);
    }

    public function itDoesntHaveAnEmailWhenUserIsKnown() {
        expect($this->artifact_creator)->create('*', '*', '*', '', '*', '*')->once();

        $this->importer->importFromXML($this->tracker, $this->xml_element, $this->extraction_path);
    }

    public function itCreatesArtifactAtDate() {
        $expected_time = strtotime('2014-01-15T10:38:06+01:00');
        expect($this->artifact_creator)->create('*', '*', '*', '*', $expected_time, '*')->once();

        $this->importer->importFromXML($this->tracker, $this->xml_element, $this->extraction_path);
    }

    public function itDoesntNotify() {
        expect($this->artifact_creator)->create('*', '*', '*', '*', '*', false)->once();

        $this->importer->importFromXML($this->tracker, $this->xml_element, $this->extraction_path);
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
                  <field_change field_name="summaro">
                    <value>Ça marche</value>
                  </field_change>
                </changeset>
              </artifact>
            </artifacts>');
    }

    public function itThrowAnExceptionWhenFieldDoesntExist() {
        expect($this->artifact_creator)->create()->never();

        $this->expectException('Tracker_Artifact_Exception_EmptyChangesetException');

        $this->importer->importFromXML($this->tracker, $this->xml_element, $this->extraction_path);
    }
}

class Tracker_Artifact_XMLImport_UserTest extends Tracker_Artifact_XMLImportBaseTest {


    public function setUp() {
        parent::setUp();

        stub($this->artifact_creator)->create()->returns(mock('Tracker_Artifact'));

        $this->user_manager = mock('UserManager');
        stub($this->user_manager)->getUserAnonymous()->returns(new PFUser(array('user_id' => 0)));

        $this->importer = new Tracker_Artifact_XMLImport(
            mock('XML_RNGValidator'),
            $this->artifact_creator,
            $this->new_changeset_creator,
            $this->formelement_factory,
            $this->user_manager
        );
    }

    public function itCreatesChangesetAsAnonymousWhenUserDoesntExists() {
        $xml_element = new SimpleXMLElement('<?xml version="1.0"?>
            <artifacts>
              <artifact id="4918">
                <changeset>
                  <submitted_by format="username">jmalko</submitted_by>
                  <submitted_on format="ISO8601">2014-01-15T10:38:06+01:00</submitted_on>
                  <field_change field_name="summary">
                    <value>Ça marche</value>
                  </field_change>
                </changeset>
              </artifact>
            </artifacts>');

        expect($this->artifact_creator)->create('*', '*', new isAnonymousUserWithEmailExpectation('jmalko'), '*', '*', '*')->once();

        $this->importer->importFromXML($this->tracker, $xml_element, $this->extraction_path);
    }

    public function itLooksForUserIdWhenFormatIsId() {
        $xml_element = new SimpleXMLElement('<?xml version="1.0"?>
            <artifacts>
              <artifact id="4918">
                <changeset>
                  <submitted_by format="id">700</submitted_by>
                  <submitted_on format="ISO8601">2014-01-15T10:38:06+01:00</submitted_on>
                  <field_change field_name="summary">
                    <value>Ça marche</value>
                  </field_change>
                </changeset>
              </artifact>
            </artifacts>');

        expect($this->user_manager)->getUserByIdentifier('id:700')->once();
        expect($this->user_manager)->getUserByIdentifier()->returns($this->john_doe);

        expect($this->artifact_creator)->create('*', '*', $this->john_doe, '*', '*', '*')->once();

        $this->importer->importFromXML($this->tracker, $xml_element, $this->extraction_path);
    }

    public function itLooksForLdapIdWhenFormatIsLdap() {
        $xml_element = new SimpleXMLElement('<?xml version="1.0"?>
            <artifacts>
              <artifact id="4918">
                <changeset>
                  <submitted_by format="ldap">uid=jo,ou=people,dc=example,dc=com</submitted_by>
                  <submitted_on format="ISO8601">2014-01-15T10:38:06+01:00</submitted_on>
                  <field_change field_name="summary">
                    <value>Ça marche</value>
                  </field_change>
                </changeset>
              </artifact>
            </artifacts>');

        expect($this->user_manager)->getUserByIdentifier('ldapId:uid=jo,ou=people,dc=example,dc=com')->once();
        expect($this->user_manager)->getUserByIdentifier()->returns($this->john_doe);

        expect($this->artifact_creator)->create('*', '*', $this->john_doe, '*', '*', '*')->once();

        $this->importer->importFromXML($this->tracker, $xml_element, $this->extraction_path);
    }

    public function itLooksForEmailWhenFormatIsEmail() {
        $xml_element = new SimpleXMLElement('<?xml version="1.0"?>
            <artifacts>
              <artifact id="4918">
                <changeset>
                  <submitted_by format="email" is_anonymous="1">jo@example.com</submitted_by>
                  <submitted_on format="ISO8601">2014-01-15T10:38:06+01:00</submitted_on>
                  <field_change field_name="summary">
                    <value>Ça marche</value>
                  </field_change>
                </changeset>
              </artifact>
            </artifacts>');

        expect($this->user_manager)->getUserByIdentifier('email:jo@example.com')->once();
        expect($this->user_manager)->getUserByIdentifier()->returns($this->john_doe);

        expect($this->artifact_creator)->create('*', '*', $this->john_doe, '*', '*', '*')->once();

        $this->importer->importFromXML($this->tracker, $xml_element, $this->extraction_path);
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
                  <field_change field_name="summary">
                    <value>Ça marche</value>
                  </field_change>
                </changeset>
                <changeset>
                  <submitted_by format="username">john_doe</submitted_by>
                  <submitted_on format="ISO8601">2014-01-15T11:03:50+01:00</submitted_on>
                  <field_change field_name="summary">
                    <value>^Wit updates</value>
                  </field_change>
                </changeset>
              </artifact>
            </artifacts>');

        stub($this->artifact_creator)->create()->returns($this->artifact);
        stub($this->new_changeset_creator)->create()->returns(true);
    }

    public function itCreatesTwoChangesets() {
        expect($this->artifact_creator)->create()->once();
        expect($this->new_changeset_creator)->create()->once();

        $this->importer->importFromXML($this->tracker, $this->xml_element, $this->extraction_path);
    }

    public function itCreatesTheNewChangesetWithSummaryValue() {
        $data = array(
            $this->summary_field_id => '^Wit updates'
        );
        expect($this->new_changeset_creator)->create($this->artifact, $data, '*', '*', '*', '*', '*')->once();

        $this->importer->importFromXML($this->tracker, $this->xml_element, $this->extraction_path);
    }

    public function itCreatesTheNewChangesetWithSubmitter() {
        expect($this->new_changeset_creator)->create($this->artifact, '*', '*', $this->john_doe, '*', '*', '*')->once();

        $this->importer->importFromXML($this->tracker, $this->xml_element, $this->extraction_path);
    }

    public function itCreatesTheNewChangesetWithoutNotification() {
        expect($this->new_changeset_creator)->create($this->artifact, '*', '*', '*', '*', false, '*')->once();

        $this->importer->importFromXML($this->tracker, $this->xml_element, $this->extraction_path);
    }


    public function itCreatesTheChangesetsAccordingToDates() {
        expect($this->artifact_creator)->create('*', '*', '*', '*', strtotime('2014-01-15T10:38:06+01:00'), '*')->once();
        
        expect($this->new_changeset_creator)->create($this->artifact, '*', '*', '*', strtotime('2014-01-15T11:03:50+01:00'), '*', '*')->once();

        $this->importer->importFromXML($this->tracker, $this->xml_element, $this->extraction_path);
    }

    public function itCreatesTheChangesetsInAscendingDatesEvenWhenChangesetsAreMixedInXML() {
        $this->xml_element = new SimpleXMLElement('<?xml version="1.0"?>
            <artifacts>
              <artifact id="4918">
                <changeset>
                  <submitted_by format="username">john_doe</submitted_by>
                  <submitted_on format="ISO8601">2014-01-15T11:03:50+01:00</submitted_on>
                  <field_change field_name="summary">
                    <value>^Wit updates</value>
                  </field_change>
                </changeset>
                <changeset>
                  <submitted_by format="username">john_doe</submitted_by>
                  <submitted_on format="ISO8601">2014-01-15T10:38:06+01:00</submitted_on>
                  <field_change field_name="summary">
                    <value>Ça marche</value>
                  </field_change>
                </changeset>
              </artifact>
            </artifacts>');

        expect($this->artifact_creator)->create('*', '*', '*', '*', strtotime('2014-01-15T10:38:06+01:00'), '*')->once();

        expect($this->new_changeset_creator)->create($this->artifact, '*', '*', '*', strtotime('2014-01-15T11:03:50+01:00'), '*', '*')->once();

        $this->importer->importFromXML($this->tracker, $this->xml_element, $this->extraction_path);
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
                  <field_change field_name="summary">
                    <value>Ça marche</value>
                  </field_change>
                </changeset>
              </artifact>
              <artifact id="4913">
                <changeset>
                  <submitted_by format="username">john_doe</submitted_by>
                  <submitted_on format="ISO8601">2014-01-16T11:38:06+01:00</submitted_on>
                  <field_change field_name="summary">
                    <value>Ça marche</value>
                  </field_change>
                </changeset>
              </artifact>
            </artifacts>');
    }

    public function itCreatesTwoArtifactsOnTracker() {
        expect($this->artifact_creator)->create()->count(2);
        expect($this->artifact_creator)->create('*', '*', '*', '*', strtotime('2014-01-15T10:38:06+01:00'), '*')->at(0);
        expect($this->artifact_creator)->create('*', '*', '*', '*', strtotime('2014-01-16T11:38:06+01:00'), '*')->at(1);

        $this->importer->importFromXML($this->tracker, $this->xml_element, $this->extraction_path);
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
                        <field_change field_name="attachment">
                          <value ref="File33"/>
                        </field_change>
                        <field_change field_name="summary">
                          <value>Newly submitted</value>
                        </field_change>
                    </changeset>
                    <file id="File33">
                        <filename>A.png</filename>
                        <path>data/34_File33.png</path>
                        <filesize>87947</filesize>
                        <filetype>image/png</filetype>
                        <description>None</description>
                    </file>
                </artifact>
            </artifacts>
        ');

    }

    public function itCreatesAChangesetWithSummaryWhenFileFormElementDoesNotExist() {
        stub($this->artifact_creator)->create()->returns(mock('Tracker_Artifact'));
        $data = array(
            $this->summary_field_id => 'Newly submitted'
        );
        expect($this->artifact_creator)->create('*', $data, '*', '*', '*', '*')->once();

        $this->importer->importFromXML($this->tracker, $this->xml_element, $this->extraction_path);
    }

    public function itCreatesAChangesetWithOneFileElement() {
        stub($this->artifact_creator)->create()->returns(mock('Tracker_Artifact'));
        stub($this->formelement_factory)->getFormElementByName($this->tracker_id, 'attachment')->returns(
            aFileField()->withId($this->file_field_id)->build()
        );

        $data = array(
            $this->file_field_id    => array(
                array(
                    'is_migrated' => true,
                    'name'        => 'A.png',
                    'type'        => 'image/png',
                    'description' => 'None',
                    'size'        => 87947,
                    'tmp_name'    => $this->extraction_path.'/data/34_File33.png',
                    'error'       => UPLOAD_ERR_OK,
               )
            ),
            $this->summary_field_id => 'Newly submitted'
        );
        expect($this->artifact_creator)->create('*', $data, '*', '*', '*', '*')->once();

        $this->importer->importFromXML($this->tracker, $this->xml_element, $this->extraction_path);
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
                        <field_change field_name="attachment">
                          <value ref="File33"/>
                          <value ref="File34"/>
                        </field_change>
                        <field_change field_name="summary">
                          <value>Newly submitted</value>
                        </field_change>
                    </changeset>
                    <file id="File33">
                        <filename>A.png</filename>
                        <path>data/34_File33.png</path>
                        <filesize>87947</filesize>
                        <filetype>image/png</filetype>
                        <description>None</description>
                    </file>
                    <file id="File34">
                        <filename>B.pdf</filename>
                        <path>data/34_File34.pdf</path>
                        <filesize>84895</filesize>
                        <filetype>application/x-download</filetype>
                        <description>A Zuper File</description>
                    </file>
                </artifact>
            </artifacts>
        ');

    }

    public function itCreatesAChangesetWithTwoFileElements() {
        stub($this->artifact_creator)->create()->returns(mock('Tracker_Artifact'));
        stub($this->formelement_factory)->getFormElementByName($this->tracker_id, 'attachment')->returns(
            aFileField()->withId($this->file_field_id)->build()
        );

        $data = array(
            $this->file_field_id    => array(
                array(
                    'is_migrated' => true,
                    'name'        => 'A.png',
                    'type'        => 'image/png',
                    'description' => 'None',
                    'size'        => 87947,
                    'tmp_name'    => $this->extraction_path.'/data/34_File33.png',
                    'error'       => UPLOAD_ERR_OK,
                ),
                array(
                    'is_migrated' => true,
                    'name'        => 'B.pdf',
                    'type'        => 'application/x-download',
                    'description' => 'A Zuper File',
                    'size'        => 84895,
                    'tmp_name'    => $this->extraction_path.'/data/34_File34.pdf',
                    'error'       => UPLOAD_ERR_OK,
                )
            ),
            $this->summary_field_id => 'Newly submitted'
        );
        expect($this->artifact_creator)->create('*', $data, '*', '*', '*', '*')->once();

        $this->importer->importFromXML($this->tracker, $this->xml_element, $this->extraction_path);
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
                        <field_change field_name="attachment">
                          <value ref="File33"/>
                        </field_change>
                        <field_change field_name="summary">
                          <value>Newly submitted</value>
                        </field_change>
                    </changeset>
                    <changeset>
                        <submitted_by format="username">manuel</submitted_by>
                        <submitted_on format="ISO8601">2014-01-30T10:39:44+01:00</submitted_on>
                        <field_change field_name="attachment">
                          <value ref="File33"/>
                          <value ref="File34"/>
                        </field_change>
                    </changeset>
                    <file id="File33">
                        <filename>A.png</filename>
                        <path>data/34_File33.png</path>
                        <filesize>87947</filesize>
                        <filetype>image/png</filetype>
                        <description>None</description>
                    </file>
                    <file id="File34">
                        <filename>B.pdf</filename>
                        <path>data/34_File34.pdf</path>
                        <filesize>84895</filesize>
                        <filetype>application/x-download</filetype>
                        <description>A Zuper File</description>
                    </file>
                </artifact>
            </artifacts>
        ');

    }

    public function itCreatesChangesetsThatOnlyReferenceConcernedFiles() {
        $artifact = mock('Tracker_Artifact');
        stub($this->artifact_creator)->create()->returns($artifact);
        stub($this->new_changeset_creator)->create()->returns(true);
        stub($this->formelement_factory)->getFormElementByName($this->tracker_id, 'attachment')->returns(
            aFileField()->withId($this->file_field_id)->build()
        );

        $initial_changeset_data = array(
            $this->file_field_id    => array(
                array(
                    'is_migrated' => true,
                    'name'        => 'A.png',
                    'type'        => 'image/png',
                    'description' => 'None',
                    'size'        => 87947,
                    'tmp_name'    => $this->extraction_path.'/data/34_File33.png',
                    'error'       => UPLOAD_ERR_OK,
                )
            ),
            $this->summary_field_id => 'Newly submitted'
        );

        $second_changeset_data = array(
            $this->file_field_id    => array(
                array(
                    'is_migrated' => true,
                    'name'        => 'B.pdf',
                    'type'        => 'application/x-download',
                    'description' => 'A Zuper File',
                    'size'        => 84895,
                    'tmp_name'    => $this->extraction_path.'/data/34_File34.pdf',
                    'error'       => UPLOAD_ERR_OK,
                )
            )
        );
        expect($this->artifact_creator)->create('*', $initial_changeset_data, '*', '*', '*', '*')->once();
        expect($this->new_changeset_creator)->create($artifact, $second_changeset_data, '*', '*', '*', '*', '*')->once();

        $this->importer->importFromXML($this->tracker, $this->xml_element, $this->extraction_path);
    }
}

class Tracker_Artifact_XMLImport_CCListTest extends Tracker_Artifact_XMLImportBaseTest {

    private $xml_element;

    private $cc_field_id = 369;
    private $open_list_field;

    public function setUp() {
        parent::setUp();

        stub($this->artifact_creator)->create()->returns(mock('Tracker_Artifact'));

        $this->open_list_field = stub('Tracker_FormElement_Field_OpenList')->getId()->returns($this->cc_field_id);

        stub($this->formelement_factory)->getFormElementByName($this->tracker_id, 'cc')->returns(
            $this->open_list_field
        );

        $this->xml_element = new SimpleXMLElement('<?xml version="1.0"?>
            <artifacts>
              <artifact id="4918">
                <changeset>
                  <submitted_by format="username">john_doe</submitted_by>
                  <submitted_on format="ISO8601">2014-01-15T10:38:06+01:00</submitted_on>
                  <field_change type="open_list" field_name="cc" bind="user">
                    <value format="username">homer</value>
                    <value format="username">jeanjean</value>
                  </field_change>
                </changeset>
              </artifact>
            </artifacts>');
    }

    public function itDelegatesOpenListComputationToField() {
        expect($this->open_list_field)->getFieldData('homer,jeanjean')->once();

        $this->importer->importFromXML($this->tracker, $this->xml_element, $this->extraction_path);
    }

    public function itCreatesArtifactWithCCFieldData() {
        stub($this->open_list_field)->getFieldData()->returns('!homer,!jeanjean');

        $data = array(
            $this->cc_field_id => '!homer,!jeanjean'
        );
        expect($this->artifact_creator)->create('*', $data, '*', '*', '*', '*')->once();

        $this->importer->importFromXML($this->tracker, $this->xml_element, $this->extraction_path);
    }
}
