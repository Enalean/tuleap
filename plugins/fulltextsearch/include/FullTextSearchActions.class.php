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
 * Class responsible to send requests to an indexation server
 */
class FullTextSearchActions {

    /**
     * @var FullTextSearch_IIndexDocuments
     */
    protected $client;
    protected $permissions;

    public function __construct(FullTextSearch_IIndexDocuments $client) {
        $this->client              = $client;
    }

    /**
     * Index a new document with permissions
     *
     */
    public function indexNewDocument() {
    }

    /**
     * Index a new document with permissions
     *
     */
    public function indexNewVersion() {
    }

    /**
     * Index the new permissions of a document
     *
     * @param Integer the object Id
     */
    public function updatePermissions($id) {
        $update_data = $this->client->initializeSetterData();
        $update_data = $this->client->appendSetterData($update_data, 'permissions', $this->permissions);
        $this->client->update($id, $update_data);
    }

    /**
     * Update title and description of a document
     *
     */
    public function updateDocument() {
    }

    /**
     * Remove an indexed document
     *
     */
    public function delete() {
    }

    /**
     * Return Indexed data
     * 
     */
    private function getIndexedData() {
    }

    /**
     * Get file contents and encode them with base64
     *
     * @param string $file_name
     * @return string
     */
    protected function fileContentEncode($file_name) {
        if (is_file($file_name)) {
            return base64_encode(file_get_contents($file_name));
        }
        return '';
    }
}
?>