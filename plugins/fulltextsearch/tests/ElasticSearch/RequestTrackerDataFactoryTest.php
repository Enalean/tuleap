<?php
/**
 * Copyright (c) Enalean, 2014 - 2018. All Rights Reserved.
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

require_once __DIR__ .'/../../include/autoload.php';
require_once __DIR__ . '/../../../tracker/tests/bootstrap.php';

class RequestTrackerDataFactory_TrackerMappingTest extends TuleapTestCase {

    private $tracker;
    private $data_factory;

    public function setUp() {
        parent::setUp();
        $this->setUpGlobalsMockery();

        $text_field = \Mockery::spy(\Tracker_FormElement_Field_Text::class);
        stub($text_field)->getName()->returns('title');
        stub($text_field)->getId()->returns(1001);

        $date_field = \Mockery::spy(\Tracker_FormElement_Field_Date::class);
        stub($date_field)->getName()->returns('my date');

        $lud_field = \Mockery::spy(\Tracker_FormElement_Field_LastUpdateDate::class);
        stub($lud_field)->getName()->returns('last updated');

        $sub_on_field = \Mockery::spy(\Tracker_FormElement_Field_SubmittedOn::class);
        stub($sub_on_field)->getName()->returns('sub on');

        $form_element_factory = \Mockery::spy(\Tracker_FormElementFactory::class);
        stub($form_element_factory)->getUsedTextFields()->returns(array($text_field));
        stub($form_element_factory)->getUsedCustomDateFields()->returns(array($date_field));
        stub($form_element_factory)->getCoreDateFields()->returns(array($lud_field, $sub_on_field));

        $this->tracker      = aTracker()->withId(455)->build();
        $this->data_factory = new ElasticSearch_1_2_RequestTrackerDataFactory(
            new ElasticSearch_1_2_ArtifactPropertiesExtractor($form_element_factory, \Mockery::spy(\Tracker_Permission_PermissionsSerializer::class))
        );
    }

    public function itHasBaseMappingWithId() {
        $mapping = $this->data_factory->getTrackerMapping($this->tracker);
        $this->assertEqual(
            $mapping['455']['properties']['id'],
            array('type' => 'integer')
        );
    }

    public function itHasBaseMappingWithTrackerId() {
        $mapping = $this->data_factory->getTrackerMapping($this->tracker);
        $this->assertEqual(
            $mapping['455']['properties']['tracker_id'],
            array('type' => 'integer')
        );
    }

    public function itHasBaseMappingWithLastChangesetId() {
        $mapping = $this->data_factory->getTrackerMapping($this->tracker);
        $this->assertEqual(
            $mapping['455']['properties']['last_changeset_id'],
            array('type' => 'integer')
        );
    }

    public function itHasBaseMappingWithFollowupComments() {
        $mapping = $this->data_factory->getTrackerMapping($this->tracker);
        $this->assertEqual(
            $mapping['455']['properties']['followup_comments'],
            array(
                'properties' => array(
                    'user_id' => array(
                        'type' => 'integer',
                    ),
                    'date_added' => array(
                        'type' => 'date',
                        'format' => 'date_time_no_millis',
                    ),
                    'comment' => array(
                        'type' => 'string',
                    ),
                )
            )
        );
    }

    public function itHasBaseMappingWithTrackerPermissions() {
        $mapping = $this->data_factory->getTrackerMapping($this->tracker);
        $this->assertEqual(
            $mapping['455']['properties']['tracker_ugroups'],
            array(
                'type'  => 'string',
                'index' => 'not_analyzed'
            )
        );
    }

    public function itHasBaseMappingWithArtifactPermissions() {
        $mapping = $this->data_factory->getTrackerMapping($this->tracker);
        $this->assertEqual(
            $mapping['455']['properties']['artifact_ugroups'],
            array(
                'type'  => 'string',
                'index' => 'not_analyzed'
            )
        );
    }

    public function itHasAdditionalFields() {
        $mapping = $this->data_factory->getTrackerMapping($this->tracker);
        $this->assertEqual(
            $mapping['455']['properties']['title'],
            array(
                'type'  => 'string',
            )
        );
        $this->assertEqual(
            $mapping['455']['properties']['my date'],
            array(
                'type'  => 'date',
                'format' => 'date_time_no_millis',
            )
        );
        $this->assertEqual(
            $mapping['455']['properties']['last updated'],
            array(
                'type'  => 'date',
                'format' => 'date_time_no_millis',
            )
        );
        $this->assertEqual(
            $mapping['455']['properties']['sub on'],
            array(
                'type'  => 'date',
                'format' => 'date_time_no_millis',
            )
        );
    }

}

abstract class RequestTrackerDataFactory_ArtifactBaseFormatting extends TuleapTestCase {

    protected $artifact;
    protected $data_factory;

    public function setUp() {
        parent::setUp();
        $this->setUpGlobalsMockery();

        $text_field = \Mockery::spy(\Tracker_FormElement_Field_Text::class);
        stub($text_field)->getName()->returns('title');
        stub($text_field)->getId()->returns(1001);

        $date_field = \Mockery::spy(\Tracker_FormElement_Field_Date::class);
        stub($date_field)->getName()->returns('my date');

        $lud_field = \Mockery::spy(\Tracker_FormElement_Field_LastUpdateDate::class);
        stub($lud_field)->getName()->returns('last updated');

        $sub_on_field = \Mockery::spy(\Tracker_FormElement_Field_SubmittedOn::class);
        stub($sub_on_field)->getName()->returns('sub on');

        $last_changeset = \Mockery::spy(\Tracker_Artifact_Changeset::class);
        stub($last_changeset)->getId()->returns(12561);
        stub($last_changeset)->getSubmittedOn()->returns(4523558);

        stub($last_changeset)->getValue($text_field)->returns(
            mockery_stub(\Tracker_Artifact_ChangesetValue_Text::class)->getValue()->returns('abc')
        );

        stub($last_changeset)->getValue($date_field)->returns(
            mockery_stub(\Tracker_Artifact_ChangesetValue_Date::class)->getTimestamp()->returns(1024586545)
        );

        $this->tracker  = aTracker()->withId(455)->withProjectId(112)->build();
        $this->artifact = anArtifact()
            ->withId(44)
            ->withTracker($this->tracker)
            ->withChangesets(array($last_changeset))
            ->withSubmittedOn(1256684)
            ->build();

        $permissions_serializer = \Mockery::spy(\Tracker_Permission_PermissionsSerializer::class);

        stub($permissions_serializer)->getLiteralizedUserGroupsThatCanViewTracker($this->artifact)->returns('@site_active, @project_members');
        stub($permissions_serializer)->getLiteralizedUserGroupsThatCanViewArtifact($this->artifact)->returns('@ug_114, @project_members');

        $form_element_factory = \Mockery::spy(\Tracker_FormElementFactory::class);
        stub($form_element_factory)->getUsedTextFields()->returns(array($text_field));
        stub($form_element_factory)->getUsedCustomDateFields()->returns(array($date_field));
        stub($form_element_factory)->getCoreDateFields()->returns(array($lud_field, $sub_on_field));

        $artifact_properties_extractor = new ElasticSearch_1_2_ArtifactPropertiesExtractor($form_element_factory, $permissions_serializer);

        $this->data_factory = new ElasticSearch_1_2_RequestTrackerDataFactory(
            $artifact_properties_extractor
        );
    }
}

class RequestTrackerDataFactory_ArtifactBaseFormattingTest extends RequestTrackerDataFactory_ArtifactBaseFormatting {

    public function itPushArtifactId() {
        $document = $this->data_factory->getFormattedArtifact($this->artifact);
        $this->assertEqual(
            $document['id'],
            44
        );
    }

    public function itPushGroupId() {
        $document = $this->data_factory->getFormattedArtifact($this->artifact);
        $this->assertEqual(
            $document['group_id'],
            112
        );
    }

    public function itPushTrackerId() {
        $document = $this->data_factory->getFormattedArtifact($this->artifact);
        $this->assertEqual(
            $document['tracker_id'],
            455
        );
    }

    public function itPushLastChangesetId() {
        $document = $this->data_factory->getFormattedArtifact($this->artifact);
        $this->assertEqual(
            $document['last_changeset_id'],
            12561
        );
    }

    public function itPushesTrackerPermissions() {
        $document = $this->data_factory->getFormattedArtifact($this->artifact);
        $this->assertEqual(
            $document['tracker_ugroups'],
            '@site_active, @project_members'
        );
    }

    public function itPushesArtifactPermissions() {
        $document = $this->data_factory->getFormattedArtifact($this->artifact);
        $this->assertEqual(
            $document['artifact_ugroups'],
            '@ug_114, @project_members'
        );
    }

    public function itPushesTextFields() {
        $document = $this->data_factory->getFormattedArtifact($this->artifact);
        $this->assertEqual(
            $document['title'],
            'abc'
        );
    }

    public function itPushesDateFields() {
        $document = $this->data_factory->getFormattedArtifact($this->artifact);
        $this->assertEqual(
            $document['my date'],
            '2002-06-20T17:22:25+02:00'
        );
    }

    public function itPushesSubmittedOnField() {
        $document = $this->data_factory->getFormattedArtifact($this->artifact);
        $this->assertEqual(
            $document['sub on'],
            '1970-01-15T14:04:44+01:00'
        );
    }

    public function itPushesLastUpdatedField() {
        $document = $this->data_factory->getFormattedArtifact($this->artifact);
        $this->assertEqual(
            $document[ElasticSearch_1_2_ArtifactPropertiesExtractor::LAST_UPDATE_PROPERTY],
            '1970-02-22T09:32:38+01:00'
        );
    }
}

class RequestTrackerDataFactory_ArtifactFollowupCommentsFormattingTest extends RequestTrackerDataFactory_ArtifactBaseFormatting {

    public function setUp() {
        parent::setUp();
        $this->setUpGlobalsMockery();
        $this->artifact_builder = anArtifact()
            ->withId(44)
            ->withTracker($this->tracker);
    }

    public function itPushNoComment() {
        $last_changeset = \Mockery::spy(\Tracker_Artifact_Changeset::class);
        stub($last_changeset)->getId()->returns(12561);
        stub($last_changeset)->getSubmittedOn()->returns(1410265950);
        stub($last_changeset)->getSubmittedBy()->returns(667);
        stub($last_changeset)->getComment()->returns(false);

        $artifact = $this->artifact_builder->withChangesets(
            array(
                $last_changeset
            )
        )->build();

        $document = $this->data_factory->getFormattedArtifact($artifact);
        $this->assertEqual(
            $document['followup_comments'],
            array()
        );
    }

    public function itPushedOneComment() {
        $last_changeset = \Mockery::spy(\Tracker_Artifact_Changeset::class);
        stub($last_changeset)->getId()->returns(12561);
        stub($last_changeset)->getSubmittedOn()->returns(1410265950);
        stub($last_changeset)->getSubmittedBy()->returns(667);
        stub($last_changeset)->getComment()->returns(aChangesetComment()->withText('Bla bla bla')->build());

        $artifact = $this->artifact_builder->withChangesets(
            array(
               $last_changeset
            )
        )->build();

        $document = $this->data_factory->getFormattedArtifact($artifact);
        $this->assertEqual(
            $document['followup_comments'],
            array(
                array(
                    'user_id'    => 667,
                    'date_added' => '2014-09-09T14:32:30+02:00',
                    'comment'    => 'Bla bla bla'
                )
            )
        );
    }
}