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
    /** @var Tracker */
    protected $tracker;

    /** @var Tracker_Artifact_XMLImport */
    protected $importer;

    /** @var Tracker_ArtifactFactory */
    protected $artifact_factory;

    protected $john_doe;
    protected $formelement_factory;
    protected $user_manager;
    protected $artifact;


    public function setUp() {
        parent::setUp();
        $tracker_id = 12;
        $this->tracker  = aTracker()->withId($tracker_id)->build();
        $this->artifact_factory = mock('Tracker_ArtifactFactory');

        $this->summary_field_id = 50;
        $this->formelement_factory = mock('Tracker_FormElementFactory');
        stub($this->formelement_factory)->getFormElementByName($tracker_id, 'summary')->returns(
            aStringField()->withId(50)->build()
        );

        $this->john_doe = aUser()->withId(200)->withUserName('john_doe')->build();
        $this->user_manager = mock('UserManager');
        stub($this->user_manager)->getUserByIdentifier('john_doe')->returns($this->john_doe);
        stub($this->user_manager)->getUserAnonymous()->returns(new PFUser(array('user_id' => 0)));

        $this->artifact = mock('Tracker_Artifact');

        $this->importer = new Tracker_Artifact_XMLImport(
            mock('XML_RNGValidator'),
            $this->artifact_factory,
            $this->formelement_factory,
            $this->user_manager
        );
    }
}

class Tracker_Artifact_XMLImport_HappyPathTest extends Tracker_Artifact_XMLImportBaseTest {

    private $xml_element;


    public function setUp() {
        parent::setUp();

        stub($this->artifact_factory)->createArtifactAt()->returns(mock('Tracker_Artifact'));

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
        expect($this->artifact_factory)->createArtifactAt($this->tracker, '*', '*', '*', '*', '*')->once();

        $this->importer->importFromXML($this->tracker, $this->xml_element);
    }

    public function itCreatesArtifactWithSummaryFieldData() {
        $data = array(
            $this->summary_field_id => 'Ça marche'
        );
        expect($this->artifact_factory)->createArtifactAt('*', $data, '*', '*', '*', '*')->once();

        $this->importer->importFromXML($this->tracker, $this->xml_element);
    }

    public function itCreatedArtifactWithSubmitter() {
        expect($this->artifact_factory)->createArtifactAt('*', '*', $this->john_doe, '*', '*', '*')->once();

        $this->importer->importFromXML($this->tracker, $this->xml_element);
    }

    public function itDoesntHaveAnEmailWhenUserIsKnown() {
        expect($this->artifact_factory)->createArtifactAt('*', '*', '*', '', '*', '*')->once();

        $this->importer->importFromXML($this->tracker, $this->xml_element);
    }

    public function itCreatesArtifactAtDate() {
        $expected_time = strtotime('2014-01-15T10:38:06+01:00');
        expect($this->artifact_factory)->createArtifactAt('*', '*', '*', '*', $expected_time, '*')->once();

        $this->importer->importFromXML($this->tracker, $this->xml_element);
    }

    public function itDoesntNotify() {
        expect($this->artifact_factory)->createArtifactAt('*', '*', '*', '*', '*', false)->once();

        $this->importer->importFromXML($this->tracker, $this->xml_element);
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
        expect($this->artifact_factory)->createArtifactAt()->never();

        $this->expectException('Tracker_Artifact_Exception_EmptyChangesetException');

        $this->importer->importFromXML($this->tracker, $this->xml_element);
    }
}

class Tracker_Artifact_XMLImport_UserTest extends Tracker_Artifact_XMLImportBaseTest {


    public function setUp() {
        parent::setUp();

        stub($this->artifact_factory)->createArtifactAt()->returns(mock('Tracker_Artifact'));

        $this->user_manager = mock('UserManager');
        stub($this->user_manager)->getUserAnonymous()->returns(new PFUser(array('user_id' => 0)));

        $this->importer = new Tracker_Artifact_XMLImport(
            mock('XML_RNGValidator'),
            $this->artifact_factory,
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

        expect($this->artifact_factory)->createArtifactAt('*', '*', new isAnonymousUserWithEmailExpectation('jmalko'), '*', '*', '*')->once();

        $this->importer->importFromXML($this->tracker, $xml_element);
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

        expect($this->artifact_factory)->createArtifactAt('*', '*', $this->john_doe, '*', '*', '*')->once();

        $this->importer->importFromXML($this->tracker, $xml_element);
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

        expect($this->artifact_factory)->createArtifactAt('*', '*', $this->john_doe, '*', '*', '*')->once();

        $this->importer->importFromXML($this->tracker, $xml_element);
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

        expect($this->artifact_factory)->createArtifactAt('*', '*', $this->john_doe, '*', '*', '*')->once();

        $this->importer->importFromXML($this->tracker, $xml_element);
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

        stub($this->artifact_factory)->createArtifactAt()->returns($this->artifact);
        stub($this->artifact)->createNewChangesetAt()->returns(true);
    }

    public function itCreatesTwoChangesets() {
        expect($this->artifact_factory)->createArtifactAt()->once();
        expect($this->artifact)->createNewChangesetAt()->once();

        $this->importer->importFromXML($this->tracker, $this->xml_element);
    }

    public function itCreatesTheNewChangesetWithSummaryValue() {
        $data = array(
            $this->summary_field_id => '^Wit updates'
        );
        expect($this->artifact)->createNewChangesetAt($data, '*', '*', '*', '*')->once();

        $this->importer->importFromXML($this->tracker, $this->xml_element);
    }

    public function itCreatesTheNewChangesetWithSubmitter() {
        expect($this->artifact)->createNewChangesetAt('*', '*', $this->john_doe, '*', '*')->once();

        $this->importer->importFromXML($this->tracker, $this->xml_element);
    }

    public function itCreatesTheNewChangesetWithoutNotification() {
        expect($this->artifact)->createNewChangesetAt('*', '*', '*', '*', false)->once();

        $this->importer->importFromXML($this->tracker, $this->xml_element);
    }


    public function itCreatesTheChangesetsAccordingToDates() {
        expect($this->artifact_factory)->createArtifactAt('*', '*', '*', '*', strtotime('2014-01-15T10:38:06+01:00'), '*')->once();
        
        expect($this->artifact)->createNewChangesetAt('*', '*', '*', strtotime('2014-01-15T11:03:50+01:00'), '*')->once();

        $this->importer->importFromXML($this->tracker, $this->xml_element);
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

        expect($this->artifact_factory)->createArtifactAt('*', '*', '*', '*', strtotime('2014-01-15T10:38:06+01:00'), '*')->once();

        expect($this->artifact)->createNewChangesetAt('*', '*', '*', strtotime('2014-01-15T11:03:50+01:00'), '*')->once();

        $this->importer->importFromXML($this->tracker, $this->xml_element);
    }
}

class Tracker_Artifact_XMLImport_SeveralArtifactsTest extends Tracker_Artifact_XMLImportBaseTest {

    private $xml_element;


    public function setUp() {
        parent::setUp();

        stub($this->artifact_factory)->createArtifactAt()->returns($this->artifact);

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
        expect($this->artifact_factory)->createArtifactAt()->count(2);
        expect($this->artifact_factory)->createArtifactAt('*', '*', '*', '*', strtotime('2014-01-15T10:38:06+01:00'), '*')->at(0);
        expect($this->artifact_factory)->createArtifactAt('*', '*', '*', '*', strtotime('2014-01-16T11:38:06+01:00'), '*')->at(1);

        $this->importer->importFromXML($this->tracker, $this->xml_element);
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
