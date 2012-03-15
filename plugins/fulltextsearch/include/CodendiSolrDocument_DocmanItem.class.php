<?php
/**
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
 *
 * This file is a part of Codendi.
 *
 * Codendi is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Codendi is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Codendi. If not, see <http://www.gnu.org/licenses/>.
 */

require_once('ICodendiSolrDocument.class.php');
require_once('common/user/UserManager.class.php');
require_once('common/user/UserHelper.class.php');
require_once(dirname(__FILE__) . '/../etc/solr/SolrPhpClient/Apache/Solr/Document.php' );

class CodendiSolrDocument_DocmanItem extends Apache_Solr_Document implements ICodendiSolrDocument {
    
    /**
     * Returns the Solr query for this SolrDocument
     *
     * @param string searched text entered by user
     *
     * @return string the Solr query for this SolrDocument
     */
    public function getSolrQuery($searched_text) {
        return '';
    }

}

?>
