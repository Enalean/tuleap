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


class ElasticSearch_IndexClientFacade extends ElasticSearch_ClientFacade implements FullTextSearch_IIndexDocuments {
    
    /**
     * @see FullTextSearch_IIndexDocuments::index
     */
    public function index(array $document, $id = false) {
        $this->client->index($document, $id);
    }

    /**
     * @see FullTextSearch_IIndexDocuments::delete
     */
    public function delete($id = false) {
        $this->client->delete($id);
    }

    /**
     * @see FullTextSearch_IIndexDocuments::delete
     */
    public function update($item_id, $data) {
        $this->client->request($item_id.'/_update', 'POST', $data);
    }

    /**
     * make a parameter with name $nname and value $value
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
}
?>
