<?php
/**
 * Copyright (c) Enalean, 2012. All Rights Reserved.
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
     * Build instance of ClientFacade
     *
     * @param string $path_to_elasticsearch_client /usr/share/elasticsearch
     * @param string the host of the search server
     * @param string the port of the search server
     *
     * @return ElasticSearch_ClientFacade
     */
    public function build($path_to_elasticsearch_client, $server_host, $server_port, ProjectManager $project_manager) {
        //todo use installation dir defined by elasticsearch rpm
        $client_path = $path_to_elasticsearch_client .'/ElasticSearchClient.php';
        if (! file_exists($client_path)) {
            $error_message = $GLOBALS['Language']->getText('plugin_fulltextsearch', 'client_library_not_found');
            $GLOBALS['Response']->addFeedback('error', $error_message);
            $GLOBALS['HTML']->redirect('/docman/?group_id=' . $this->getId());
            die();
        }

        require_once $client_path;
        require_once 'ClientFacade.class.php'; //can't be moved to the top of the file for now

        $transport  = new ElasticSearchTransportHTTP($server_host, $server_port);

        $type = 'docman';
        
        $client = new ElasticSearchClient($transport, 'tuleap', $type);
        return new ElasticSearch_ClientFacade($client, $type, $project_manager);
    }
}
?>
