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
class ElasticSearch_1_2_ArtifactPropertiesExtractor {

    const LAST_UPDATE_PROPERTY = 'es_last_update_date';

    /** @var Tracker_Permission_PermissionsSerializer */
    private $permissions_serializer;

    /** @var Tracker_FormElementFactory */
    private $form_element_factory;

    public function __construct(
        Tracker_FormElementFactory $form_element_factory,
        Tracker_Permission_PermissionsSerializer $permissions_serializer
    ) {
        $this->form_element_factory   = $form_element_factory;
        $this->permissions_serializer = $permissions_serializer;
    }

    public function extractTrackerUserGroups(Tracker_Artifact $artifact, array &$properties) {
        $properties['tracker_ugroups'] = $this->permissions_serializer->getLiteralizedUserGroupsThatCanViewTracker($artifact);
    }

    public function extractArtifactUserGroups(Tracker_Artifact $artifact, array &$properties) {
        $properties['artifact_ugroups'] = $this->permissions_serializer->getLiteralizedUserGroupsThatCanViewArtifact($artifact);
    }

    public function extractArtifactTextFields(Tracker_Artifact $artifact, Tracker_Artifact_Changeset $last_changeset, array &$properties) {
        $tracker     = $artifact->getTracker();
        $text_fields = $this->form_element_factory->getUsedTextFields($tracker);

        foreach ($text_fields as $text_field) {
            $last_changeset_value = $last_changeset->getValue($text_field);

            if ($last_changeset->getValue($text_field) && $last_changeset_value) {
                $properties[$text_field->getName()] = $last_changeset_value->getValue();
            }
        }
    }

    public function extractArtifactComments(Tracker_Artifact $artifact, array &$properties) {
        $properties['followup_comments'] = array();

        foreach ($artifact->getChangesets() as $changeset) {
            $comment = $changeset->getComment();
            if ($comment) {
                $properties['followup_comments'][] = array(
                    'user_id'    => $changeset->getSubmittedBy(),
                    'date_added' => date('c', $changeset->getSubmittedOn()),
                    'comment'    => $changeset->getComment()->body,
                );
            }
        }
    }

    public function extractArtifactDateFields(Tracker_Artifact $artifact, Tracker_Artifact_Changeset $last_changeset, array &$properties) {
        $tracker            = $artifact->getTracker();
        $custom_date_fields = $this->form_element_factory->getUsedCustomDateFields($tracker);

        foreach ($custom_date_fields as $date_field) {
            $last_changeset_value = $last_changeset->getValue($date_field);

            if ($last_changeset->getValue($date_field) && $last_changeset_value) {
                $properties[$date_field->getName()] = date('c', $last_changeset_value->getTimestamp());
            }
        }

        $core_date_fields      = $this->form_element_factory->getCoreDateFields($tracker);
        $has_last_update_field = false;
        foreach ($core_date_fields as $date_field) {
            if ($date_field instanceof Tracker_FormElement_Field_SubmittedOn) {
                $properties[$date_field->getName()] = date('c', $artifact->getSubmittedOn());
            } elseif ($date_field instanceof Tracker_FormElement_Field_LastUpdateDate) {
                $has_last_update_field = true;
                $properties[self::LAST_UPDATE_PROPERTY] = date('c', $artifact->getLastUpdateDate());
            }
        }

        if (! $has_last_update_field) {
            $last_modified = $artifact->getLastUpdateDate();

            if ($last_modified === -1) {
                $last_modified = $artifact->getSubmittedOn();
            }

            $properties[self::LAST_UPDATE_PROPERTY] = date('c', $last_modified);
        }
    }

    public function extractTrackerFields(Tracker $tracker, array &$mapping_data) {
        $string_map  = array('type' => 'string');
        $text_fields = $this->form_element_factory->getUsedTextFields($tracker);
        foreach ($text_fields as $field) {
            $mapping_data[$tracker->getId()]['properties'][$field->getName()] = $string_map;
        }

        $date_map    = array(
            'type'   => 'date',
            'format' => 'date_time_no_millis',
        );

        $custom_date_fields = $this->form_element_factory->getUsedCustomDateFields($tracker);
        foreach ($custom_date_fields as $field) {
            $mapping_data[$tracker->getId()]['properties'][$field->getName()] = $date_map;
        }

        $core_date_fields = $this->form_element_factory->getCoreDateFields($tracker);
        foreach ($core_date_fields as $field) {
            $mapping_data[$tracker->getId()]['properties'][$field->getName()] = $date_map;
        }
    }
}
