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
     * PUT /docman/:project_id/:document_id
     *
     * @param array       $document
     * @param Docman_Item $item
     *
     * @return array
     */
    public function index(array $document, Docman_Item $item);

    /**
     * Flush this index/type combination
     *
     * DELETE /docman/:project_id/:document_id
     *
     * @param Docman_Item $item
     * @param array       $options Parameters to pass to delete action
     * 
     * @return array
     */
    public function delete(Docman_Item $item);

    /**
     * Update document datas
     *
     * @param Docman_Item $item
     * @param array       $data
     *
     * @return array
     */
    public function update(Docman_Item $item, $data);

    /**
     * make and append prepared request to currentData
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

    /**
     * Get the project mapping
     *
     * GET /docman/:project_id/_mapping
     *
     * @param int $project_id
     *
     * @return array
     */
    public function getProjectMapping($project_id);

    /**
     * Initialize the project mapping
     *
     * PUT /docman/:project_id/_mapping
     *
     * @param int   $project_id
     * @param array $mapping_data
     *
     * @return array
     */
    public function initializeProjectMapping($project_id, array $mapping_data);
}