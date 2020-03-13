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
require_once __DIR__ . '/../../../../bootstrap.php';

class Tracker_XML_Exporter_ChangesetValue_ChangesetValueOpenListXMLExporter_UsersTest extends TuleapTestCase
{

    /** @var Tracker_XML_Exporter_ChangesetValue_ChangesetValueOpenListXMLExporter */
    private $exporter;

    /** @var SimpleXMLElement */
    private $changeset_xml;

    /** @var SimpleXMLElement */
    private $artifact_xml;

    /** @var Tracker_Artifact_ChangesetValue_OpenList */
    private $changeset_value;

    /** @var Tracker_FormElement_Field */
    private $field;

    public function setUp()
    {
        parent::setUp();
        $this->artifact_xml  = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><artifact />');
        $this->changeset_xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><changeset />');

        $bind       = stub('Tracker_FormElement_Field_List_Bind_Users')->getType()->returns('users');
        $open_value = stub('Tracker_FormElement_Field_List_OpenValue')->getLabel()->returns('email@tuleap.org');

        $user = new PFUser([
            'user_id' => 112,
            'language_id' => 'en',
            'ldap_id' => 'ldap_01'
        ]);
        $user_manager      = stub('UserManager')->getUserById(112)->returns($user);
        $user_xml_exporter = new UserXMLExporter($user_manager, mock('UserXMLExportedCollection'));

        $this->field = stub('Tracker_FormElement_Field_OpenList')->getBind()->returns($bind);
        stub($this->field)->getName()->returns('CC');
        stub($this->field)->getOpenValueById()->returns($open_value);

        $this->changeset_value = mock('Tracker_Artifact_ChangesetValue_OpenList');
        stub($this->changeset_value)->getField()->returns($this->field);

        $this->exporter = new Tracker_XML_Exporter_ChangesetValue_ChangesetValueOpenListXMLExporter($user_xml_exporter);
    }

    public function itCreatesFieldChangeNodeWithMultipleValuesInChangesetNode()
    {
        stub($this->changeset_value)->getValue()->returns(array(
            'o14',
            'b112'
        ));

        $this->exporter->export(
            $this->artifact_xml,
            $this->changeset_xml,
            mock('Tracker_Artifact'),
            $this->changeset_value
        );

        $field_change = $this->changeset_xml->field_change;
        $this->assertEqual((string) $field_change['type'], 'open_list');
        $this->assertEqual((string) $field_change['bind'], 'users');
        $this->assertEqual((string) $field_change->value[0], 'email@tuleap.org');
        $this->assertEqual((string) $field_change->value[0]['format'], 'label');
        $this->assertEqual((string) $field_change->value[1], 'ldap_01');
        $this->assertEqual((string) $field_change->value[1]['format'], 'ldap');
    }
}

class Tracker_XML_Exporter_ChangesetValue_ChangesetValueOpenListXMLExporter_UgroupsTest extends TuleapTestCase
{

    /** @var Tracker_XML_Exporter_ChangesetValue_ChangesetValueOpenListXMLExporter */
    private $exporter;

    /** @var SimpleXMLElement */
    private $changeset_xml;

    /** @var SimpleXMLElement */
    private $artifact_xml;

    /** @var Tracker_Artifact_ChangesetValue_OpenList */
    private $changeset_value;

    /** @var Tracker_FormElement_Field */
    private $field;

    public function setUp()
    {
        parent::setUp();
        $this->artifact_xml  = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><artifact />');
        $this->changeset_xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><changeset />');

        $bind       = stub('Tracker_FormElement_Field_List_Bind_Ugroups')->getType()->returns('ugroups');
        $open_value = stub('Tracker_FormElement_Field_List_OpenValue')->getLabel()->returns('new_ugroup');

        $user_xml_exporter = mock('UserXMLExporter');

        $ugroup = stub('ProjectUGroup')->getId()->returns(112);
        stub($ugroup)->getNormalizedName()->returns('ugroup_01');

        $this->field = stub('Tracker_FormElement_Field_OpenList')->getBind()->returns($bind);
        stub($this->field)->getName()->returns('ugroup_binded');
        stub($this->field)->getOpenValueById()->returns($open_value);

        $this->changeset_value = mock('Tracker_Artifact_ChangesetValue_OpenList');
        stub($this->changeset_value)->getField()->returns($this->field);

        $this->exporter = new Tracker_XML_Exporter_ChangesetValue_ChangesetValueOpenListXMLExporter($user_xml_exporter);
    }

    public function itCreatesFieldChangeNodeWithMultipleValuesInChangesetNode()
    {
        stub($this->changeset_value)->getValue()->returns(array(
            'o14',
            'b112'
        ));

        $this->exporter->export(
            $this->artifact_xml,
            $this->changeset_xml,
            mock('Tracker_Artifact'),
            $this->changeset_value
        );

        $field_change = $this->changeset_xml->field_change;
        $this->assertEqual((string) $field_change['type'], 'open_list');
        $this->assertEqual((string) $field_change['bind'], 'ugroups');
        $this->assertEqual((string) $field_change->value[0], 'new_ugroup');
        $this->assertEqual((string) $field_change->value[0]['format'], 'label');
        $this->assertEqual((string) $field_change->value[1], 'b112');
        $this->assertEqual((string) $field_change->value[1]['format'], 'id');
    }
}

class Tracker_XML_Exporter_ChangesetValue_ChangesetValueOpenListXMLExporter_StaticTest extends TuleapTestCase
{

    /** @var Tracker_XML_Exporter_ChangesetValue_ChangesetValueOpenListXMLExporter */
    private $exporter;

    /** @var SimpleXMLElement */
    private $changeset_xml;

    /** @var SimpleXMLElement */
    private $artifact_xml;

    /** @var Tracker_Artifact_ChangesetValue_OpenList */
    private $changeset_value;

    /** @var Tracker_FormElement_Field */
    private $field;

    public function setUp()
    {
        parent::setUp();

        $user_xml_exporter = new UserXMLExporter(mock('UserManager'), mock('UserXMLExportedCollection'));
        $this->exporter    = new Tracker_XML_Exporter_ChangesetValue_ChangesetValueOpenListXMLExporter(
            $user_xml_exporter
        );

        $this->artifact_xml  = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><artifact />');
        $this->changeset_xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><changeset />');

        $bind       = stub('Tracker_FormElement_Field_List_Bind_Static')->getType()->returns('static');
        $open_value = stub('Tracker_FormElement_Field_List_OpenValue')->getLabel()->returns('keyword01');

        $this->field = stub('Tracker_FormElement_Field_OpenList')->getBind()->returns($bind);
        stub($this->field)->getName()->returns('keywords');
        stub($this->field)->getOpenValueById()->returns($open_value);

        $this->changeset_value = mock('Tracker_Artifact_ChangesetValue_OpenList');
        stub($this->changeset_value)->getField()->returns($this->field);
    }

    public function itCreatesFieldChangeNodeWithMultipleValuesInChangesetNode()
    {
        stub($this->changeset_value)->getValue()->returns(array(
            'o14',
            'b112'
        ));

        $this->exporter->export(
            $this->artifact_xml,
            $this->changeset_xml,
            mock('Tracker_Artifact'),
            $this->changeset_value
        );

        $field_change = $this->changeset_xml->field_change;
        $this->assertEqual((string) $field_change['type'], 'open_list');
        $this->assertEqual((string) $field_change['bind'], 'static');
        $this->assertEqual((string) $field_change->value[0], 'keyword01');
        $this->assertEqual((string) $field_change->value[0]['format'], 'label');
        $this->assertEqual((string) $field_change->value[1], 'b112');
        $this->assertEqual((string) $field_change->value[1]['format'], 'id');
    }
}
