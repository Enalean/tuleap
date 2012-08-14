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

require_once 'SearchResultCollection.class.php';

/**
 * Interface which define the base contract for search library clients
 */
interface FullTextSearch_ISearchDocuments {

    /**
     * Search for data in the index
     * 
     * @param String $term terms
     * 
     * @return FullTextSearch_SearchResultCollection
     */
    public function searchDocuments($terms);
    

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
