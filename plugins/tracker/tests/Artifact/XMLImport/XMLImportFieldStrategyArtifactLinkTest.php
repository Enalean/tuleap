<?php
/**
 * Copyright (c) Enalean, 2016. All Rights Reserved.
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

require_once __DIR__.'/../../bootstrap.php';

class XMLImportFieldStrategyArtifactLinkTest extends TuleapTestCase
{
    /** @var  Tracker_FormElement_Field_ArtifactLink */
    private $field;

    /** @var  PFUser */
    private $submitted_by;

    /** @var  Logger */
    private $logger;

    /** @var  Tracker_ArtifactFactory */
    private $artifact_factory;

    /** @var Tracker_Artifact_XMLImport_XMLImportFieldStrategyArtifactLink */
    private $artlink_strategy;

    /** @var  Tuleap\Tracker\FormElement\Field\ArtifactLink\Nature\NatureCreator */
    private $nature_creator;

    /** @var  Tuleap\Tracker\FormElement\Field\ArtifactLink\Nature\NatureDao */
    private $nature_dao;

    /** @var  Tracker_Artifact */
    private $artifact;

    public function setUp()
    {
        $this->field            = mock('Tracker_FormElement_Field_ArtifactLink');
        $this->submitted_by     = mock('PFUser');
        $this->logger           = mock('Logger');
        $this->artifact_factory = mock('Tracker_ArtifactFactory');
        $this->nature_dao       = mock('Tuleap\Tracker\FormElement\Field\ArtifactLink\Nature\NatureDao');
        $this->nature_creator   = mock('Tuleap\Tracker\FormElement\Field\ArtifactLink\Nature\NatureCreator');
        $this->artifact         = mock('Tracker_Artifact');

        $this->artlink_strategy = partial_mock(
            'Tracker_Artifact_XMLImport_XMLImportFieldStrategyArtifactLink',
            array('getLastChangeset')
        );
    }

    public function itShouldWorkWithCompleteMapping()
    {
        $mapping = new Tracker_XML_Importer_ArtifactImportedMapping();
        $mapping->add(100, 1);
        $mapping->add(101, 2);
        $strategy = new Tracker_Artifact_XMLImport_XMLImportFieldStrategyArtifactLink(
            $mapping,
            $this->logger,
            $this->artifact_factory,
            $this->nature_dao,
            $this->nature_creator
        );

        $xml_change = new SimpleXMLElement('<?xml version="1.0"?>
                  <field_change field_name="artlink" type="art_link">
                    <value>101</value>
                    <value>100</value>
                  </field_change>');

        stub($this->nature_dao)->getNatureByShortname()->returnsDar(array());
        stub($this->artlink_strategy)->getLastChangeset($xml_change)->returns(null);
        stub($this->artifact_factory)->getArtifactById()->returns($this->artifact);

        $res = $strategy->getFieldData($this->field, $xml_change, $this->submitted_by, $this->artifact);
        $expected_res =  array("new_values" => '2,1', 'removed_values' => array(), 'natures' => array('1' => '', '2' => ''));
        $this->assertEqual($expected_res, $res);
    }

    public function itShouldImportSystemNatures()
    {
        $mapping = new Tracker_XML_Importer_ArtifactImportedMapping();
        $mapping->add(100, 1);
        $mapping->add(101, 2);
        $strategy = new Tracker_Artifact_XMLImport_XMLImportFieldStrategyArtifactLink(
            $mapping,
            $this->logger,
            $this->artifact_factory,
            $this->nature_dao,
            $this->nature_creator
        );

        $xml_change = new SimpleXMLElement('<?xml version="1.0"?>
                  <field_change field_name="artlink" type="art_link">
                    <value nature="_is_child">101</value>
                    <value nature="_in_folder">100</value>
                  </field_change>');

        stub($this->nature_dao)->getNatureByShortname()->returnsDar(array());
        stub($this->artlink_strategy)->getLastChangeset($xml_change)->returns(null);
        stub($this->artifact_factory)->getArtifactById()->returns($this->artifact);

        $res = $strategy->getFieldData($this->field, $xml_change, $this->submitted_by, $this->artifact);
        $expected_res =  array("new_values" => '2,1', 'removed_values' => array(), 'natures' => array('1' => '_in_folder', '2' => '_is_child'));

        $this->assertEqual($expected_res, $res);
    }

    public function itShouldWorkWithCompleteMappingAndNature()
    {
        $mapping = new Tracker_XML_Importer_ArtifactImportedMapping();
        $mapping->add(100, 1);
        $mapping->add(101, 2);
        $mapping->add(102, 3);

        $strategy = new Tracker_Artifact_XMLImport_XMLImportFieldStrategyArtifactLink(
            $mapping,
            $this->logger,
            $this->artifact_factory,
            $this->nature_dao,
            $this->nature_creator
        );
        $xml_change = new SimpleXMLElement('<?xml version="1.0"?>
                  <field_change field_name="artlink" type="art_link">
                    <value nature="toto">101</value>
                    <value nature="titi">100</value>
                    <value>102</value>
                  </field_change>');

        stub($this->artlink_strategy)->getLastChangeset($xml_change)->returns(null);
        stub($this->nature_dao)->getNatureByShortname()->returnsDar(array('titi'));
        stub($this->artifact_factory)->getArtifactById()->returns($this->artifact);

        $res = $strategy->getFieldData($this->field, $xml_change, $this->submitted_by, $this->artifact);
        $expected_res =  array("new_values" => '2,1,3', 'removed_values' => array(), 'natures' => array('1' => 'titi', '2' => 'toto', '3' => ''));
        $this->assertEqual($expected_res, $res);
    }

    public function itShouldLogWhenArtifactLinkReferenceIsBroken()
    {
        $mapping          = new Tracker_XML_Importer_ArtifactImportedMapping();
        $strategy         = new Tracker_Artifact_XMLImport_XMLImportFieldStrategyArtifactLink(
            $mapping,
            $this->logger,
            $this->artifact_factory,
            $this->nature_dao,
            $this->nature_creator
        );
        $xml_change = new SimpleXMLElement('<?xml version="1.0"?>
                  <field_change field_name="artlink" type="art_link">
                    <value>101</value>
                  </field_change>');

        stub($this->nature_dao)->getNatureByShortname()->returnsDar(array());
        stub($this->artlink_strategy)->getLastChangeset($xml_change)->returns(null);
        stub($this->artifact_factory)->getArtifactById()->returns($this->artifact);

        $strategy->getFieldData($this->field, $xml_change, $this->submitted_by, $this->artifact);
        expect($this->logger)->error()->count(1);
    }

    public function itShouldRemoveValuesWhenArtifactChildrenAreRemoved()
    {
        $mapping = new Tracker_XML_Importer_ArtifactImportedMapping();
        $mapping->add(200, 1);
        $mapping->add(101, 2);
        $mapping->add(102, 3);

        $strategy = new Tracker_Artifact_XMLImport_XMLImportFieldStrategyArtifactLink(
            $mapping,
            $this->logger,
            $this->artifact_factory,
            $this->nature_dao,
            $this->nature_creator
        );
        $xml_change = new SimpleXMLElement('<?xml version="1.0"?>
                  <field_change field_name="artlink" type="art_link">
                    <value nature="toto">200</value>
                  </field_change>');

        $changeset_value = mock('Tracker_Artifact_ChangesetValue_ArtifactLink');
        stub($changeset_value)->getArtifactIds()->returns(array(1, 2, 3));
        $changeset = mock('Tracker_Artifact_Changeset');
        stub($changeset)->getValues()->returns(array($changeset_value));
        stub($this->artifact)->getLastChangeset()->returns($changeset);
        stub($this->artifact_factory)->getArtifactById()->returns($this->artifact);

        stub($this->nature_dao)->getNatureByShortname()->returnsDar(array('toto'));
        $res = $strategy->getFieldData($this->field, $xml_change, $this->submitted_by, $this->artifact);
        $expected_res =  array("new_values" => '1', 'removed_values' => array(2 => 2, 3 => 3), 'natures' => array('1' => 'toto'));

        $this->assertEqual($expected_res, $res);
    }
}
