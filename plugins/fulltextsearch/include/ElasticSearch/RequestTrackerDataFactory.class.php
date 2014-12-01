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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

/**
 * I build data for ElasticSearch 1.2 requests
 */
class ElasticSearch_1_2_RequestTrackerDataFactory {

    const COMMENT_FIELD_NAME = 'comment';

    /** @var ElasticSearch_1_2_ArtifactPropertiesExtractor */
    private $artifact_properties_extractor;

    public function __construct(ElasticSearch_1_2_ArtifactPropertiesExtractor $artifact_properties_extractor) {
        $this->artifact_properties_extractor = $artifact_properties_extractor;
    }

    public function getFormattedArtifact(Tracker_Artifact $artifact) {
        $es_document = array();
        $this->getBaseArtifact($artifact, $es_document);
        $this->getArtifactComments($artifact, $es_document);

        return $es_document;
    }

    private function getBaseArtifact(Tracker_Artifact $artifact, array &$properties) {
        $last_changeset    = $artifact->getLastChangeset();
        $last_changeset_id = ($last_changeset) ? $last_changeset->getId() : -1;

        $properties = array(
            'id'                => $artifact->getId(),
            'group_id'          => $artifact->getTracker()->getGroupId(),
            'tracker_id'        => $artifact->getTrackerId(),
            'last_changeset_id' => $last_changeset_id,
        );

        $this->artifact_properties_extractor->extractTrackerUserGroups($artifact, $properties);
        $this->artifact_properties_extractor->extractArtifactUserGroups($artifact, $properties);

        if ($last_changeset) {
            $this->artifact_properties_extractor->extractArtifactTextFields($artifact, $last_changeset, $properties);
            $this->artifact_properties_extractor->extractArtifactDateFields($artifact, $last_changeset, $properties);
        }
    }

    private function getArtifactComments(Tracker_Artifact $artifact, array &$properties) {
        $this->artifact_properties_extractor->extractArtifactComments($artifact, $properties);
    }

    public function getTrackerMapping(Tracker $tracker) {
        $tracker_id = $tracker->getId();

        $mapping_data = array(
            $tracker_id => array(
                'properties' => array(
                    'id' => array(
                        'type' => 'integer'
                    ),
                    'group_id' => array(
                        'type' => 'integer'
                    ),
                    'tracker_id' => array(
                        'type' => 'integer'
                    ),
                    'last_changeset_id' => array(
                        'type' => 'integer'
                    ),
                    'followup_comments' => array(
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
                )
            )
        );

        $this->addStandardTrackerPermissionsMetadata($mapping_data[$tracker_id]['properties']);
        $this->addStandardArtifactPermissionsMetadata($mapping_data[$tracker_id]['properties']);

        $this->artifact_properties_extractor->extractTrackerFields($tracker, $mapping_data);

        return $mapping_data;
    }

    private function addStandardTrackerPermissionsMetadata(array &$mapping_data) {
        $mapping_data['tracker_ugroups'] = array(
            'type'  => 'string',
            'index' => 'not_analyzed'
        );
    }

    private function addStandardArtifactPermissionsMetadata(array &$mapping_data) {
        $mapping_data['artifact_ugroups'] = array(
            'type'  => 'string',
            'index' => 'not_analyzed'
        );
    }
}
