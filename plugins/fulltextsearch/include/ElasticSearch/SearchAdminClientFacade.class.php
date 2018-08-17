<?php
/**
 * Copyright (c) Enalean, 2012-2018. All Rights Reserved.
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

require_once 'common/project/UGroupLiteralizer.class.php';
require_once 'common/project/ProjectManager.class.php';

/**
 * Allow to perform search on ElasticSearch Index
 */
class ElasticSearch_SearchAdminClientFacade extends ElasticSearch_SearchClientFacade implements FullTextSearch_ISearchDocumentsForAdmin {

    /**
     * @see ISearchDocuments::getStatus
     *
     * @return array
     */
    public function getStatus() {
        $this->client->setType('');
        $result = $this->client->request(array('_status'), 'GET', $payload = false, $verbose = true);
        $this->client->setType($this->index);

        $status = array(
            'size'    => isset($result['indices']['tuleap']['index']['size']) ? $result['indices']['tuleap']['index']['size'] : '0',
            'nb_docs' => isset($result['indices']['tuleap']['docs']['num_docs']) ? $result['indices']['tuleap']['docs']['num_docs'] : 0,
        );

        return $status;
    }

    protected function filterQueryWithPermissions(array &$query, PFUser $user, $terms, $facets)
    {
        /* do not filter since site admin has full power */
    }

    /**
     * @return array to be used for querying ES
     */
    protected function getSearchDocumentsQuery($terms, array $facets, $offset, PFUser $user, $size) {
        $query = parent::getSearchDocumentsQuery($terms, $facets, $offset, $user, $size);
        $query['fields'][] = 'permissions';
        return $query;
    }
}

?>
