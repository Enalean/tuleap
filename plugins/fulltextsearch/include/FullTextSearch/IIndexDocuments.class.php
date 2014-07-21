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
 * Interface which define the base contract for index/search library clients
 */
interface FullTextSearch_IIndexDocuments {

    /**
     * Index a new document or update it if existing
     *
     * PUT /:index/:type/:document_id
     *
     * @param string $type
     * @param string $document_id
     * @param array  $document
     *
     * @return array
     */
    public function index($type, $document_id, array $document);

    /**
     * Flush this index/type combination
     *
     * DELETE /:index/:type/:document_id
     *
     * @param string $type
     * @param string $document_id
     * @param array  $options Parameters to pass to delete action
     * 
     * @return array
     */
    public function delete($type, $document_id);

    /**
     * Update document data
     *
     * @param string $type
     * @param string $document_id
     * @param array  $document
     *
     * @return array
     */
    public function update($type, $document_id, array $document);

    /**
     * Get the indexed element
     *
     * GET /:index/:type/:element_id
     *
     * @param string $type
     * @param string $element_id
     *
     * @return array
     */
    public function getIndexedElement($type, $element_id);

    /**
     * Get the mapping
     *
     * GET /:index/:type/_mapping
     *
     * @param string $type
     *
     * @return array
     */
    public function getMapping($type);

    /**
     * Define the project mapping (create and update)
     *
     * PUT /:index/:type/_mapping
     *
     * @param string $type
     * @param array  $mapping_data
     *
     * @return array
     */
    public function setMapping($type, array $mapping_data);

    /**
     * Make a parameter with name $name and value $value
     * then append it to current_data as script and var*
     *
     * @param array  $current_data
     * @param string $name
     * @param string $value
     *
     * @return array
     */
    public function appendSetterData(array $current_data, $name, $value);

    /**
     * Return the base to build a setter data
     *
     * @return array
     */
    public function initializeSetterData();
}