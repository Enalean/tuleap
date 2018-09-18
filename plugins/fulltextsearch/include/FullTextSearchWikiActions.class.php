<?php
/**
 * Copyright (c) Enalean, 2014-2018. All Rights Reserved.
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

use Tuleap\PHPWiki\WikiPage;

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

    /** @var TruncateLevelLogger */
    private $logger;

    public function __construct(
        FullTextSearch_IIndexDocuments $client,
        ElasticSearch_1_2_RequestWikiDataFactory $request_data_factory,
        TruncateLevelLogger $logger
    ) {
        $this->client               = $client;
        $this->request_data_factory = $request_data_factory;
        $this->logger               = $logger;
    }

    private function indexOrUpdate($project_id, $wiki_page_id, $data) {
        try {
            $this->client->getIndexedElement($project_id, $wiki_page_id);
            $this->client->update($project_id, $wiki_page_id, $data);

        } catch (ElasticSearch_ElementNotIndexed $exception) {
            $this->client->index($project_id, $wiki_page_id, $data);
            return;
        }
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
        $this->logger->debug('[Wiki] ElasticSearch: index new empty wiki page ' . $wiki_page->getPagename() . ' #' . $wiki_page->getId());

        $indexed_data = $this->request_data_factory->getIndexedWikiPageData($wiki_page);

        $this->client->index($wiki_page->getGid(), $wiki_page->getId(), $indexed_data);
    }

    /**
     * Index a new wiki page
     *
     * @param WikiPage $wiki_page The wiki page
     */
    public function indexWikiPage(WikiPage $wiki_page) {
        $this->logger->debug('[Wiki] ElasticSearch: index wiki page ' . $wiki_page->getPagename() . ' #' . $wiki_page->getId());

        $indexed_data = $this->request_data_factory->getIndexedWikiPageData($wiki_page);

        $this->client->index($wiki_page->getGid(), $wiki_page->getId(), $indexed_data);
    }

    /**
     * Remove an indexed wiki page
     *
     * @param WikiPage $wiki_page The item to delete
     */
    public function delete(WikiPage $wiki_page) {
        $this->logger->debug('[Wiki] ElasticSearch: delete wiki page ' . $wiki_page->getPagename() . ' #' . $wiki_page->getId());

        try{
            $this->client->getIndexedElement($wiki_page->getGid(), $wiki_page->getId());
            $this->client->delete($wiki_page->getGid(), $wiki_page->getId());
        } catch (ElasticSearch_ElementNotIndexed $exception) {
            $this->logger->debug('[Wiki] ElasticSearch: wiki page ' . $wiki_page->getPagename() . ' #' . $wiki_page->getId() . ' not indexed, nothing to delete');
            return;
        }
    }

    private function deleteForProject($project_id) {
        $this->logger->debug('[Wiki] ElasticSearch: deleting all project wiki pages #' . $project_id);

        try{
            $this->client->getIndexedType($project_id);
            $this->client->deleteType($project_id);

        } catch (ElasticSearch_TypeNotIndexed $exception) {
            $this->logger->debug('[Wiki] ElasticSearch: project #' . $project_id . ' not indexed, nothing to delete');
            return;
        }
    }

    /**
     *
     * @param WikiPage $wiki_page
     */
    public function updatePermissions(WikiPage $wiki_page) {
        $this->logger->debug('[Wiki] ElasticSearch: update permissions of wiki page ' . $wiki_page->getPagename() . ' #' . $wiki_page->getId());

        $update_data = array();
        $this->request_data_factory->setUpdatedData(
            $update_data,
            'permissions',
            $this->request_data_factory->getCurrentPermissions($wiki_page)
        );

        $this->indexOrUpdate($wiki_page->getGid(), $wiki_page->getId(), $update_data);
    }

    private function indexAllProjectWikiPages($project_id) {
        $indexable_pages = $this->getAllIndexablePagesForProject($project_id);

        foreach($indexable_pages as $indexable_page) {
            $this->indexWikiPage($indexable_page);
        }
    }

    protected function getAllIndexablePagesForProject($project_id) {
        $wiki_page = new WikiPage();
        return $wiki_page->getAllIndexablePages($project_id);
    }

    /**
     *
     * @param int $project_id
     */
    public function reIndexProjectWikiPages($project_id) {
        $this->deleteForProject($project_id);
        $this->initializeProjetMapping($project_id);
        $this->indexAllProjectWikiPages($project_id);
    }
}
