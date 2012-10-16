<?php
/**
 * Copyright (c) STMicroelectronics, 2012. All Rights Reserved.
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

/**
 * Class responsible to send requests to an indexation server
 */
class FullTextSearchTrackerActions {

    /**
     * @var FullTextSearch_IIndexDocuments
     */
    protected $client;

    /** Constructor
     *
     * @param FullTextSearch_IIndexDocuments $client Search client
     *
     * @return Void
     */
    public function __construct(FullTextSearch_IIndexDocuments $client) {
        $this->client = $client;
    }

    /**
     * Index a new followup comment
     *
     * @param Integer $groupId     Id of the project
     * @param Integer $artifactId  Id of the artifact
     * @param Integer $changesetId Id of the changeset
     * @param String  $text        Body of the followup comment
     */
    public function indexNewFollowup($groupId, $artifactId, $changesetId, $text) {
        $indexed_data = $this->getIndexedData($artifactId, $changesetId, $text);
        $this->client->index($indexed_data, $artifactId);
    }

    /**
     * Index an updated followup comment
     *
     * @param Integer $groupId     Id of the project
     * @param Integer $artifactId  Id of the artifact
     * @param Integer $changesetId Id of the changeset
     * @param String  $text        Body of the followup comment
     */
    public function indexUpdatedFollowup($groupId, $artifactId, $changesetId, $text) {
        $update_data = $this->client->initializeSetterData();
        $update_data = $this->client->appendSetterData($update_data, 'file', base64_encode($text));
        $this->client->update($artifactId, $update_data);
    }

    /**
     * Format data to be indexed
     *
     * @param 
     *
     * @return Array
     */
    private function getIndexedData($groupId, $artifactId, $changesetId, $text) {
        return array(
            'id'           => $changesetId,
            'type'         => 'tracker',
            'group_id'     => $groupId,
            'artifact_id'  => $artifactId,
            'changeset_id' => $changesetId,
            'file'         => base64_encode($text)
        );
    }

}

?>