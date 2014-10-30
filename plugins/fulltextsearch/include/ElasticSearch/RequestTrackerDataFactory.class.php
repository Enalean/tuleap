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

    /** @var Tracker_Permission_PermissionsSerializer */
    private $permissions_serializer;

    /** @var Tracker_FormElementFactory */
    private $form_element_factory;

    public function __construct(
        Tracker_Permission_PermissionsSerializer $permissions_serializer,
        Tracker_FormElementFactory $form_element_factory
    ) {
        $this->permissions_serializer = $permissions_serializer;
        $this->form_element_factory   = $form_element_factory;
    }

    public function getFormattedArtifact(Tracker_Artifact $artifact) {
        $es_document = $this->getBaseArtifact($artifact);
        foreach ($artifact->getChangesets() as $changeset) {
            $comment = $changeset->getComment();
            if ($comment) {
                $es_document['followup_comments'][] = array(
                    'user_id'    => $changeset->getSubmittedBy(),
                    'date_added' => date('c', $changeset->getSubmittedOn()),
                    'comment'    => $changeset->getComment()->body,
                );
            }
        }
        return $es_document;
    }

    private function getBaseArtifact(Tracker_Artifact $artifact) {
        $last_changeset = $artifact->getLastChangeset();

        $properties = array(
            'id'                => $artifact->getId(),
            'group_id'          => $artifact->getTracker()->getGroupId(),
            'tracker_id'        => $artifact->getTrackerId(),
            'last_changeset_id' => $last_changeset->getId(),
            'tracker_ugroups'   => $this->permissions_serializer->getLiteralizedUserGroupsThatCanViewTracker($artifact),
            'artifact_ugroups'  => $this->permissions_serializer->getLiteralizedUserGroupsThatCanViewArtifact($artifact),
            'followup_comments' => array(),
        );

        $text_fields = $this->form_element_factory->getUsedTextFields($artifact->getTracker());
        foreach ($text_fields as $text_field) {
            $properties[$text_field->getName()] = $last_changeset->getValue($text_field)->getValue();
        }

        return $properties;
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

        $this->addTrackerFieldsToMapping($mapping_data, $tracker);

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

    private function addTrackerFieldsToMapping(array &$mapping_data, Tracker $tracker) {
        $text_fields = $this->form_element_factory->getUsedTextFields($tracker);

        $string_map = array('type' => 'string');
        foreach ($text_fields as $field) {
            $mapping_data[$tracker->getId()]['properties'][$field->getName()] = $string_map;
        }
    }
}
