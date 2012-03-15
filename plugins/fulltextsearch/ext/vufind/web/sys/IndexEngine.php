<?php
/**
 *
 * Copyright (C) Andrew Nagy 2008.
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License version 2,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 *
 */

/**
 * Index Engine Interface
 *
 * @version     $Revision$
 * @author      Andrew S. Nagy <asnagy@gmail.com>
 * @access      public
 */
Interface IndexEngine {
    /**
     * Retrieves a document specified by the ID.
     *
     * @param   string  $id         The document to retrieve from Solr
     * @access  public
     * @throws  object              PEAR Error
     * @return  string              The requested resource
     */
    function getRecord($id); 
    
    /**
     * Get records similiar to one record
     *
     * @access  public
     * @param   id          The record id
     * @throws  object      PEAR Error
     * @return  array       An array of query results
     */
    function getMoreLikeThis($id);
    
    /**
     * Get record data based on the provided field and phrase.
     * Used for AJAX suggestions.
     *
     * @access  public
     * @param   string  $phrase     The input phrase
     * @param   string  $field      The field to search on
     * @param   int     $limit      The number of results to return
     * @return  array   An array of query results
     */
    function getSuggestion($phrase, $field, $limit);
    
    /**
     * Get spelling suggestions based on input phrase.
     *
     * @access  public
     * @param   string  $phrase     The input phrase
     * @return  array   An array of spelling suggestions
     */
    function checkSpelling($phrase);

    /**
     * Build Query string from search parameters
     *
     * @access  public
     * @param   array   $search     An array of search parameters
     * @param   string  $sortBy     The value to be used by for sorting
     * @throws  object              PEAR Error
     * @static
     * @return  string              The query
     */
    function buildQuery($search);

    /**
     * Execute a search.
     *
     * @param   string  $query      The XQuery script in binary encoding.
     * @param   string  $handler    The Query Handler/Index to search on
     * @param   array   $filter     The fields and values to filter results on
     * @param   string  $start      The record to start with
     * @param   string  $limit      The amount of records to return
     * @param   array   $facet      An array of faceting options
     * @param   string  $spell      Phrase to spell check
     * @param   string  $fields     A list of fields to be returned
     * @access  public
     * @throws  object              PEAR Error
     * @return  array               An array of query results
     * @todo    Change solr to lookup an explicit list of fields to optimize
     *          memory load
     */
	function search($query, $handler = null, $filter = null, $start = 0,
	                $limit = null, $facet = null, $spell = null, $sort = null, 
                    $fields = null, $method = HTTP_REQUEST_METHOD_POST);


}
?>