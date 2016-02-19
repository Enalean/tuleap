<?php
/**
 * Copyright (c) Enalean, 2015. All Rights Reserved.
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

class Tracker_XML_Importer_ChildrenXMLImporterTest extends TuleapTestCase {

    /** @var Tracker_Artifact_XMLImport */
    private $xml_importer;

    /** @var Tracker_XML_Importer_ChildrenXMLImporter */
    private $importer;

    /** @var Tracker_XML_Importer_ArtifactImportedMapping */
    private $artifacts_imported_mapping;

    /** @var Tracker_Artifact */
    private $created_artifact;

    /** @var Tracker_Artifact */
    private $root_artifact;

    /** @var Tracker_Artifact */
    private $another_child_artifact;

    /** @var Tracker_XML_ChildrenCollector */
    private $children_collector;

    /** @var Tracker_ArtifactFactory */
    private $artifact_factory;

    /** @var PFUser */
    private $user;

    /** @var Tracker */
    private $tracker;

    /** @var Tracker */
    private $tracker_2;

    private $field_id   = 97;
    private $field_id_2 = 159;
/* TODO move those tests into copyartifacttest
    public function setUp() {
        parent::setUp();
        $tracker_factory          = mock('TrackerFactory');
        $this->xml_importer       = mock('Tracker_Artifact_XMLImport');
        $this->artifact_factory   = mock('Tracker_ArtifactFactory');
        $this->children_collector = new Tracker_XML_ChildrenCollector();
        $this->importer           = new Tracker_XML_Importer_ChildrenXMLImporter(
            $this->xml_importer,
            $tracker_factory,
            $this->artifact_factory,
            $this->children_collector
        );

        $this->artifacts_imported_mapping = mock('Tracker_XML_Importer_ArtifactImportedMapping');

        $this->tracker = aTracker()->withId(23)->build();
        stub($tracker_factory)->getTrackerById(23)->returns($this->tracker);

        $this->tracker_2 = aTracker()->withId(24)->build();
        stub($tracker_factory)->getTrackerById(24)->returns($this->tracker_2);

        $this->user = aUser()->build();

        $artlink_field   = anArtifactLinkField()->withId($this->field_id)->build();
        $artlink_field_2 = anArtifactLinkField()->withId($this->field_id_2)->build();

        $root_artifact_id = 456;
        $this->root_artifact = mock('Tracker_Artifact');
        stub($this->root_artifact)->getId()->returns($root_artifact_id);
        stub($this->root_artifact)->getAnArtifactLinkField($this->user)->returns($artlink_field);
        stub($this->artifact_factory)->getArtifactById($root_artifact_id)->returns($this->root_artifact);

        $this->created_artifact = mock('Tracker_Artifact');
        stub($this->created_artifact)->getId()->returns(1023);
        stub($this->created_artifact)->getAnArtifactLinkField($this->user)->returns($artlink_field_2);
        stub($this->artifact_factory)->getArtifactById(1023)->returns($this->created_artifact);

        $this->another_child_artifact = anArtifact()->withId(1024)->build();
        stub($this->artifact_factory)->getArtifactById(1024)->returns($this->another_child_artifact);

        $this->xml_mapping = new TrackerXmlFieldsMapping_InSamePlatform();
    }

    public function itImportsAllArtifactsExceptTheFirstOne() {
        $xml = simplexml_load_string('<?xml version="1.0" encoding="UTF-8"?>
            <artifacts>
                <artifact id="123" tracker_id="22"></artifact>
                <artifact id="456" tracker_id="23"></artifact>
            </artifacts>');

        expect($this->xml_importer)->importOneArtifactFromXML(
            $this->tracker,
            $xml->artifact[1],
            '/extraction/path',
            $this->xml_mapping
        )->once();

        $this->importer->importChildren($this->artifacts_imported_mapping, $xml, '/extraction/path', $this->user);
    }

    public function itRaisesExceptionIfNoTrackerId() {
        $xml = simplexml_load_string('<?xml version="1.0" encoding="UTF-8"?>
            <artifacts>
                <artifact id="123"></artifact>
                <artifact id="456"></artifact>
            </artifacts>');

        $this->expectException('Tracker_XML_Importer_TrackerIdNotDefinedException');

        $this->importer->importChildren($this->artifacts_imported_mapping, $xml, 'whatever', $this->user);
    }

    public function itStacksMappingBetweenOriginalAndNewArtifact() {
        $xml = simplexml_load_string('<?xml version="1.0" encoding="UTF-8"?>
            <artifacts>
                <artifact id="100" tracker_id="22"></artifact>
                <artifact id="123" tracker_id="23"></artifact>
            </artifacts>');
        stub($this->xml_importer)->importOneArtifactFromXML()->returns($this->created_artifact);

        expect($this->artifacts_imported_mapping)->add(123, 1023)->once();

        $this->importer->importChildren($this->artifacts_imported_mapping, $xml, 'whatever', $this->user);
    }

    public function itDoesNotStackMappingIfNoArtifact() {
        $xml = simplexml_load_string('<?xml version="1.0" encoding="UTF-8"?>
            <artifacts>
                <artifact id="100" tracker_id="22"></artifact>
                <artifact id="123" tracker_id="23"></artifact>
            </artifacts>');
        stub($this->xml_importer)->importOneArtifactFromXML()->returns(null);

        expect($this->artifacts_imported_mapping)->add()->never();

        $this->importer->importChildren($this->artifacts_imported_mapping, $xml, 'whatever', $this->user);
    }

    public function itDoesNotCreateAnyArtifactLinkIfThereAreNotAnyChildren() {
        $xml = simplexml_load_string('<?xml version="1.0" encoding="UTF-8"?>
            <artifacts>
                <artifact id="100" tracker_id="22"></artifact>
            </artifacts>');

        $artifact = mock('Tracker_Artifact');
        stub($this->artifact_factory)->getArtifactById(100)->returns($artifact);

        expect($artifact)->createNewChangeset()->never();
        $this->importer->importChildren($this->artifacts_imported_mapping, $xml, 'whatever', $this->user);
    }

    public function itCreatesAnArtifactLinkIfThereIsOneChild() {
        $this->children_collector->addChild(123, 100);
        $xml = simplexml_load_string('<?xml version="1.0" encoding="UTF-8"?>
            <artifacts>
                <artifact id="100" tracker_id="22">
                    <changeset>
                        <field_change field_name="content" type="art_link">
                            <value>123</value>
                        </field_change>
                    </changeset>
                </artifact>
                <artifact id="123" tracker_id="23">
                    <changeset>
                        <field_change field_name="content" type="art_link">
                            <value/>
                        </field_change>
                    </changeset>
                </artifact>
            </artifacts>'
        );

        stub($this->artifacts_imported_mapping)->get(100)->returns($this->root_artifact->getId());
        stub($this->artifacts_imported_mapping)->get(123)->returns($this->created_artifact->getId());
        stub($this->artifacts_imported_mapping)->getOriginal($this->root_artifact->getId())->returns(100);
        stub($this->artifacts_imported_mapping)->getOriginal($this->created_artifact->getId())->returns(123);

        $fields_data = array(
            $this->field_id => array(
                Tracker_FormElement_Field_ArtifactLink::NEW_VALUES_KEY => "1023"
            )
        );

        expect($this->root_artifact)->createNewChangeset($fields_data, '', $this->user, false, Tracker_Artifact_Changeset_Comment::TEXT_COMMENT)->once();
        $this->importer->importChildren($this->artifacts_imported_mapping, $xml, 'whatever', $this->user);
    }

    public function itCreatesCorrectArtifactLinksWithAChildOfAChild() {
        $xml = simplexml_load_string('<?xml version="1.0" encoding="UTF-8"?>
            <artifacts>
                <artifact id="100" tracker_id="22">
                    <changeset>
                        <field_change field_name="content" type="art_link">
                            <value>123</value>
                        </field_change>
                    </changeset>
                </artifact>
                <artifact id="123" tracker_id="23">
                    <changeset>
                        <field_change field_name="content" type="art_link">
                            <value>124</value>
                        </field_change>
                    </changeset>
                </artifact>
                <artifact id="124" tracker_id="24">
                    <changeset>
                        <field_change field_name="content" type="art_link">
                            <value/>
                        </field_change>
                    </changeset>
                </artifact>
            </artifacts>'
        );

        stub($this->xml_importer)->importOneArtifactFromXML('*', $xml->artifact[1], '*', '*', '*')->returns($this->created_artifact);
        stub($this->xml_importer)->importOneArtifactFromXML('*', $xml->artifact[2], '*', '*', '*')->returns($this->another_child_artifact);

        stub($this->artifacts_imported_mapping)->get(100)->returns($this->root_artifact->getId());
        stub($this->artifacts_imported_mapping)->get(123)->returns($this->created_artifact->getId());
        stub($this->artifacts_imported_mapping)->get(124)->returns($this->another_child_artifact->getId());
        stub($this->artifacts_imported_mapping)->getOriginal($this->root_artifact->getId())->returns(100);
        stub($this->artifacts_imported_mapping)->getOriginal($this->created_artifact->getId())->returns(123);
        stub($this->artifacts_imported_mapping)->getOriginal($this->another_child_artifact->getId())->returns(124);

        $fields_data_1 = array(
            $this->field_id => array(
                Tracker_FormElement_Field_ArtifactLink::NEW_VALUES_KEY => "1023"
            )
        );

        $fields_data_2 = array(
            $this->field_id_2 => array(
                Tracker_FormElement_Field_ArtifactLink::NEW_VALUES_KEY => "1024"
            )
        );

        expect($this->root_artifact)->createNewChangeset($fields_data_1, '', $this->user, false, Tracker_Artifact_Changeset_Comment::TEXT_COMMENT)->once();
        expect($this->created_artifact)->createNewChangeset($fields_data_2, '', $this->user, false, Tracker_Artifact_Changeset_Comment::TEXT_COMMENT)->once();

        $this->importer->importChildren($this->artifacts_imported_mapping, $xml, 'whatever', $this->user);
    }

    public function itStoresTheChildrenOfTheFirstArtifact() {
        $xml = simplexml_load_string('<?xml version="1.0" encoding="UTF-8"?>
            <artifacts>
                <artifact id="100" tracker_id="22">
                    <changeset>
                        <field_change field_name="content" type="art_link">
                            <value>123</value>
                        </field_change>
                    </changeset>
                </artifact>
                <artifact id="123" tracker_id="23">
                    <changeset>
                        <field_change field_name="content" type="art_link">
                            <value>124</value>
                        </field_change>
                    </changeset>
                </artifact>
                <artifact id="124" tracker_id="24">
                    <changeset>
                        <field_change field_name="content" type="art_link">
                            <value/>
                        </field_change>
                    </changeset>
                </artifact>
            </artifacts>'
        );

        stub($this->xml_importer)->importOneArtifactFromXML('*', $xml->artifact[1], '*', '*', "*")->returns($this->created_artifact);
        stub($this->xml_importer)->importOneArtifactFromXML('*', $xml->artifact[2], '*', '*', "*")->returns($this->another_child_artifact);

        stub($this->artifacts_imported_mapping)->get(100)->returns($this->root_artifact->getId());
        stub($this->artifacts_imported_mapping)->get(123)->returns($this->created_artifact->getId());
        stub($this->artifacts_imported_mapping)->get(124)->returns($this->another_child_artifact->getId());
        stub($this->artifacts_imported_mapping)->getOriginal($this->root_artifact->getId())->returns(100);
        stub($this->artifacts_imported_mapping)->getOriginal($this->created_artifact->getId())->returns(123);
        stub($this->artifacts_imported_mapping)->getOriginal($this->another_child_artifact->getId())->returns(124);

        $this->importer->importChildren($this->artifacts_imported_mapping, $xml, 'whatever', $this->user);
        $expected_parents = array(100, 123);
        $this->assertEqual($expected_parents, $this->children_collector->getAllParents());
    }*/
}
