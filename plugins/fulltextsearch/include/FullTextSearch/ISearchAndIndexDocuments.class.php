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
interface FullTextSearch_ISearchAndIndexDocuments {

    /**
     * Index a new document or update it if existing
     *
     * @return array
     * @param array $document
     * @param mixed $id Optional
     */
    public function index(array $document, $id = false);

    /**
     * Flush this index/type combination
     *
     * 
     * @param mixed $id If id is supplied, delete that id for this index
     *                  if not wipe the entire index
     * @param array $options Parameters to pass to delete action
     * 
     * @return array
     */
    public function delete($id = false);

    /**
     * Update document datas
     *
     * @return array
     */
    public function update($item_id, $data);

    /**
     * make and append prepared request to currentData
     *
     * @return array
     */
    public function buildSetterData(array $current_data, $name, $value);
    
    /**
     * Search for data in the index
     * 
     * @param String $term terms
     * 
     * @return array
     */
    public function searchDocuments($query);
    

    /**
     * Return status of the index
     * 
     * The returned array is:
     * array('size'   => string with human readable size
     *       'nb_docs => integer, number of documents in index)
     * 
     * @return array 
     */
    public function getStatus();
}
?>
