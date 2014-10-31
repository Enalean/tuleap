<?php
/**
 * Copyright (c) STMicroelectronics, 2012. All Rights Reserved.
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

/**
 * Class responsible to send indexation requests for tracker changesets to an indexation server
 */
class FullTextSearchTrackerActions {

    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var ElasticSearch_1_2_RequestTrackerDataFactory
     */
    private $tracker_data_factory;

    /**
     * @var FullTextSearch_IIndexDocuments
     */
    private $client;

    /** Constructor
     *
     * @param FullTextSearch_IIndexDocuments $client Search client
     *
     * @return Void
     */
    public function __construct(FullTextSearch_IIndexDocuments $client, ElasticSearch_1_2_RequestTrackerDataFactory $tracker_data_factory, Logger $logger) {
        $this->client               = $client;
        $this->tracker_data_factory = $tracker_data_factory;
        $this->logger               = $logger;
    }

    /**
     * Index an artifact
     *
     * @param Tracker_Artifact $artifact The artifact to index
     */
    public function indexArtifactUpdate(Tracker_Artifact $artifact) {
        $this->initializeMapping($artifact->getTracker());
        $this->logger->debug('[Tracker] Elasticsearch index artifact #' . $artifact->getId() . ' in tracker #' . $artifact->getTrackerId());
        $this->client->index(
            $artifact->getTrackerId(),
            $artifact->getId(),
            $this->tracker_data_factory->getFormattedArtifact($artifact)
        );
    }

    public function reIndexProjectArtifacts(array $trackers) {
        foreach ($trackers as $tracker) {
            $this->reIndexTracker($tracker);
        }
    }

    public function reIndexTracker(Tracker $tracker) {
        $this->deleteTracker($tracker);
        $this->indexAllArtifacts($tracker);
    }

    private function deleteTracker($tracker) {
        $tracker_id = $tracker->getId();

        $this->logger->debug('[Tracker] ElasticSearch: deleting all artifacts of tracker #' . $tracker_id);

        try {
            $this->client->getIndexedType($tracker_id);
            $this->client->deleteType($tracker_id);

        } catch (ElasticSearch_TypeNotIndexed $e) {
            $this->logger->debug('[Tracker] ElasticSearch: tracker #' . $tracker_id . ' has not previously been indexed, nothing to delete');
            return;
        }
    }

    private function indexAllArtifacts(Tracker $tracker) {
        $tracker_id                = $tracker->getId();
        $tracker_artifact_factory  = Tracker_ArtifactFactory::instance();
        $tracker_artifact_iterator = new Tracker_Artifact_BatchIterator($tracker_artifact_factory, $tracker_id);

        $this->logger->debug('[Tracker] ElasticSearch: indexing all artifacts of tracker #' . $tracker_id);

        $tracker_artifact_iterator->rewind();
        while ($batch = $tracker_artifact_iterator->next()) {
            foreach ($batch as $artifact) {
                $this->indexArtifactUpdate($artifact);
            }
        }
    }

    private function initializeMapping(Tracker $tracker) {
        if (! $this->mappingExists($tracker)) {
            $this->logger->debug('[Tracker] Elasticsearch set mapping for tracker #'.$tracker->getId());
            $this->client->setMapping((string) $tracker->getId(), $this->tracker_data_factory->getTrackerMapping($tracker));
        }
    }

    private function mappingExists(Tracker $tracker) {
        return count($this->client->getMapping((string) $tracker->getId())) > 0;
    }

    public function deleteArtifactIndex($artifact_id, $tracker_id) {
        $this->client->delete(
            $tracker_id,
            $artifact_id
        );
    }

    public function deleteTrackerIndex($tracker_id) {
        $this->client->deleteType(
            $tracker_id
        );
    }
}
