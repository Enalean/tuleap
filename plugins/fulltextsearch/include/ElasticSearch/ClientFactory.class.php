<?php
/**
 * Copyright (c) Enalean, 2012 -2014. All Rights Reserved.
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
 * Builds ElasticSearch_ClientFacade instances
 */
class ElasticSearch_ClientFactory {

    /**
     * @var ElasticSearch_ClientConfig
     */
    private $client_config;

    /**
     * @var ProjectManager
     */
    private $project_manager;

    public function __construct(ElasticSearch_ClientConfig $client_config, ProjectManager $project_manager) {
        $this->client_config  = $client_config;
        $this->project_manager = $project_manager;
    }

    /**
     * Build instance of ClientFacade
     *
     * @param String $index     Type of the items to search
     * @param String $type      @see elasticsearch- an identifier of a set of items
     *
     * @return ElasticSearch_ClientFacade
     */
    public function buildIndexClient($index, $type) {
        $client = $this->getClient($index, $type);
        return new ElasticSearch_IndexClientFacade($client);
    }

    /**
     * Build instance of SearchClientFacade
     *
     * @param String $index     Type of the items to search
     * @param String $type      @see elasticsearch- an identifier of a set of items
     *
     * @return ElasticSearch_ClientFacade
     */
    public function buildSearchClient($index, $type) {
        $client = $this->getClient($index, $type);
        return new ElasticSearch_SearchClientFacade(
            $client,
            $index,
            $this->project_manager,
            UserManager::instance(),
            new ElasticSearch_1_2_ResultFactory(
                $this->project_manager,
                new URLVerification(),
                UserManager::instance()
            )
        );
    }

    /**
     * Build instance of SearchAdminClientFacade
     *
     * @return ElasticSearch_ClientFacade
     */
    public function buildSearchAdminClient() {
        $index  = fulltextsearchPlugin::SEARCH_DEFAULT;
        $type   = '';
        $client = $this->getClient($index, $type);
        return new ElasticSearch_SearchAdminClientFacade(
            $client,
            $index,
            $this->project_manager,
            UserManager::instance(),
            new ElasticSearch_1_2_ResultFactory(
                $this->project_manager,
                new URLVerification(),
                UserManager::instance()
            )
        );
    }

    private function getClient($index, $type) {
        //todo use installation dir defined by elasticsearch rpm
        $client_path = $this->client_config->getClientPath() .'/ElasticSearchClient.php';
        if (! file_exists($client_path)) {
            throw new ElasticSearch_ClientNotFoundException();
        }
        // magic <3 : use an external library and overload one of the files
        require_once $client_path;

        $transport = new ElasticSearch_TransportHTTPBasicAuth(
            $this->client_config->getServerHost(),
            $this->client_config->getServerPort(),
            $this->client_config->getServerUser(),
            $this->client_config->getServerPassword(),
            $this->client_config->getRequestTimeout()
        );

        return new ElasticSearchClient($transport, $index, $type);
    }
}