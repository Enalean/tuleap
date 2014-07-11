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

/**
 * Class responsible to send requests to an indexation server
 */
class FullTextSearchWikiActions {

    /**
     * @var FullTextSearch_IIndexDocuments
     */
    private $client;

    /** @var ElasticSearch_1_2_RequestWikiDataFactory */
    private $request_data_factory;

    /** @var BackendLogger */
    private $logger;

    public function __construct(
        FullTextSearch_IIndexDocuments $client,
        ElasticSearch_1_2_RequestWikiDataFactory $request_data_factory,
        BackendLogger $logger
    ) {
        $this->client               = $client;
        $this->request_data_factory = $request_data_factory;
        $this->logger               = $logger;
    }

    public function checkProjectMappingExists($project_id) {
        $this->logger->debug('[Wiki] ElasticSearch: get the mapping for project #' . $project_id);

        return count($this->client->getMapping($project_id)) > 0;
    }

    public function initializeProjetMapping($project_id) {
        $this->logger->debug('[Wiki] ElasticSearch: initialize the mapping for project #' . $project_id);

        $this->client->setMapping(
            $project_id,
            $this->request_data_factory->getPUTMappingData($project_id)
        );
    }

    /**
     * Index a new wiki page
     *
     * @param WikiPage $wiki_page The wiki page
     */
    public function indexNewEmptyWikiPage(WikiPage $wiki_page) {
        $this->logger->debug('[Wiki] ElasticSearch: index new empty wiki page #' . $wiki_page->getId());

        $indexed_data = $this->request_data_factory->getIndexedWikiPageData($wiki_page);

        $this->client->index($wiki_page->getGid(), $wiki_page->getId(), $indexed_data);
    }

    /**
     * Index a new wiki page
     *
     * @param WikiPage $wiki_page The wiki page
     */
    public function indexWikiPage(WikiPage $wiki_page) {
        $this->logger->debug('[Wiki] ElasticSearch: index wiki page #' . $wiki_page->getId());

        $indexed_data = $this->request_data_factory->getIndexedWikiPageData($wiki_page);

        $this->client->index($wiki_page->getGid(), $wiki_page->getId(), $indexed_data);
    }
}
