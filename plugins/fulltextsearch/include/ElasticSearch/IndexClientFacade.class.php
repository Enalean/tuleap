<?php
/**
 * Copyright (c) Enalean, 2012 - 2014. All Rights Reserved.
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
 * This facade is used for all types of items such as wiki, docman or trackers.
 *
 * If you want to figure-out what $this->client is doing then
 * @see https://github.com/nervetattoo/elasticsearch
 *
 * ElasticSearch URL formatting => url:port/{index}/{type}/{id}
 *
 * Here's a few examples of the curl that is going on behind

 * e.g. get an artifact   : curl -u superuser:Adm1n "localhost:9200/tracker/[tracker_id]/[artifact_id]/?pretty"
 * e.g. update an artifact: curl -u superuser:Adm1n -XPOST 'http://localhost:9200/tracker/[tracker_id]/[artifact_id]' -d '{big fat JSON that corresponds to the item}'
 */
class ElasticSearch_IndexClientFacade extends ElasticSearch_ClientFacade implements FullTextSearch_IIndexDocuments {

    /**
     * @see FullTextSearch_IIndexDocuments::index
     */
    public function index($type, $document_id, array $document) {
        $this->client->setType($type);

        $this->client->index($document, $document_id);
    }

    /**
     * @see FullTextSearch_IIndexDocuments::delete
     */
    public function delete($type, $document_id) {
        $this->client->setType($type);
        $this->client->delete($document_id);
    }

    public function deleteType($type) {
        $this->client->setType($type);
        $this->client->request('/', 'DELETE', false, true);
    }

    /**
     * @see FullTextSearch_IIndexDocuments::update
     */
    public function update($type, $document_id, array $document) {
        $this->client->setType($type);

        $formatted_data = $this->initializeUpdateData();
        foreach ($document as $name => $value) {
            $formatted_data = $this->appendUpdateData($formatted_data, $name, $value);
        }

        $this->client->request($document_id.'/_update', 'POST', $formatted_data, true);
    }

    public function getIndexedType($type) {
        $this->client->setType($type);

        try {
            $result = $this->client->request('/_search', 'GET', false, true);

            if ($this->requestHasNoResult($result)) {
                throw new ElasticSearch_TypeNotIndexed();
            }

        } catch (ElasticSearchTransportHTTPException $exception) {
            throw new ElasticSearch_TypeNotIndexed();
        }

    }

    private function requestHasNoResult($result) {
        return (! isset($result['hits']) || ! isset($result['hits']['total']) || $result['hits']['total'] === 0);
    }

    public function getIndexedElement($type, $element_id) {
        $this->client->setType($type);

        try {
            $this->client->request($element_id, 'GET', array(), true);
        } catch (ElasticSearchTransportHTTPException $exception) {
            throw new ElasticSearch_ElementNotIndexed();
        }

    }

    public function getMapping($type) {
        $this->client->setType($type);

        return $this->client->request('/_mapping', 'GET', array(), true);
    }

    public function setMapping($type, array $mapping_data) {
        $this->client->setType($type);

        $this->client->request('/_mapping', 'PUT', $mapping_data, true);
    }

    /**
     * make a parameter with name $name and value $value
     * then append it to current_data as var
     */
    public function appendUpdateData(array $current_data, $name, $value) {
        $current_data['doc'][$name] = $value;
        return $current_data;
    }

    /**
     * make a parameter with name $name and value $value
     * then append it to current_data as script and var
     */
    public function appendSetterData(array $current_data, $name, $value) {
        $current_data['script']       .= "ctx._source.$name = $name;";
        $current_data['params'][$name] = $value;
        return $current_data;
    }

    /**
     * Return the base to build a setter data
     *
     * @return array
     */
    public function initializeSetterData() {
        return array(
            'script' => '',
            'params' => array()
        );
    }

    /**
     * Return the base to build update data
     *
     * @return array
     */
    public function initializeUpdateData() {
        return array(
            'doc' => array()
        );
    }
}