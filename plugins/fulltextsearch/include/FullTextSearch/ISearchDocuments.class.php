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
 * Interface which define the base contract for search library clients
 */
interface FullTextSearch_ISearchDocuments {

    /**
     * Search for data in the index, filter them with permissions
     * 
     * @param String $term   terms
     * @param array  $facets submitted by user for faceted navigation
     * @param int    $offset The offset of the search
     * @param User   $user   The user which do the request
     * 
     * @return FullTextSearch_SearchResultCollection
     */
    public function searchDocuments($terms, array $facets, $offset, User $user);

    /**
     * Search for data in the index
     *
     * @param String $term   terms
     * @param array  $facets submitted by user for faceted navigation
     * @param int    $offset The offset of the search
     *
     * @return FullTextSearch_SearchResultCollection
     */
    public function searchDocumentsIgnoringPermissions($terms, array $facets, $offset);

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
