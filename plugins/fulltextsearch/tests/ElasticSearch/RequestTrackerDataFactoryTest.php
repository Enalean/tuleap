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

require_once dirname(__FILE__) .'/../../include/autoload.php';
require_once TRACKER_BASE_DIR . '/../tests/bootstrap.php';

class RequestTrackerDataFactory_TrackerMappingTest extends TuleapTestCase {

    private $tracker;
    private $data_factory;

    public function setUp() {
        parent::setUp();

        $text_field = mock('Tracker_FormElement_Field_Text');
        stub($text_field)->getName()->returns('title');
        stub($text_field)->getId()->returns(1001);

        $form_element_factory = mock('Tracker_FormElementFactory');
        stub($form_element_factory)->getUsedTextFields()->returns(array($text_field));

        $this->tracker      = aTracker()->withId(455)->build();
        $this->data_factory = new ElasticSearch_1_2_RequestTrackerDataFactory(
            mock('Tracker_Permission_PermissionsSerializer'),
            $form_element_factory
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
    }

}

abstract class RequestTrackerDataFactory_ArtifactBaseFormatting extends TuleapTestCase {

    protected $artifact;
    protected $data_factory;

    public function setUp() {
        parent::setUp();
        $this->tracker  = aTracker()->withId(455)->withProjectId(112)->build();
        $this->artifact = anArtifact()
            ->withId(44)
            ->withTracker($this->tracker)
            ->withChangesets(array(aChangeset()->withId(12561)->build()))
            ->build();

        $permissions_serializer = mock('Tracker_Permission_PermissionsSerializer');

        stub($permissions_serializer)->getLiteralizedUserGroupsThatCanViewTracker($this->artifact)->returns('@site_active, @project_members');
        stub($permissions_serializer)->getLiteralizedUserGroupsThatCanViewArtifact($this->artifact)->returns('@ug_114, @project_members');

        $form_element_factory = mock('Tracker_FormElementFactory');
        stub($form_element_factory)->getUsedTextFields()->returns(array());

        $this->data_factory = new ElasticSearch_1_2_RequestTrackerDataFactory(
            $permissions_serializer,
            $form_element_factory
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
}

class RequestTrackerDataFactory_ArtifactFollowupCommentsFormattingTest extends RequestTrackerDataFactory_ArtifactBaseFormatting {

    public function setUp() {
        parent::setUp();
        $this->artifact_builder = anArtifact()
            ->withId(44)
            ->withTracker($this->tracker);
    }

    public function itPushNoComment() {
        $artifact = $this->artifact_builder->withChangesets(
            array(
                aChangeset()
                    ->withId(12561)
                    ->withSubmittedBy(667)
                    ->withSubmittedOn(1410265950)
                    ->withComment(false)
                    ->build()
            )
        )->build();

        $document = $this->data_factory->getFormattedArtifact($artifact);
        $this->assertEqual(
            $document['followup_comments'],
            array()
        );
    }

    public function itPushedOneComment() {
        $artifact = $this->artifact_builder->withChangesets(
            array(
                aChangeset()
                    ->withId(12561)
                    ->withSubmittedBy(667)
                    ->withSubmittedOn(1410265950)
                    ->withComment(aChangesetComment()->withText('Bla bla bla')->build())
                    ->build()
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