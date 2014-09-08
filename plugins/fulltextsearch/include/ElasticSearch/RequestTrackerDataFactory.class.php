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
        return array(
            'id'                => $artifact->getId(),
            'group_id'          => $artifact->getTracker()->getGroupId(),
            'tracker_id'        => $artifact->getTrackerId(),
            'last_changeset_id' => $artifact->getLastChangeset()->getId(),
            'followup_comments' => array(),
        );
    }

    public function getTrackerMapping(Tracker $tracker) {
        return array(
            $tracker->getId() => array(
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
    }
}
