<?php
/**
 * Copyright (c) Enalean, 2018. All Rights Reserved.
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
 *
 */

namespace Tuleap\TuleapSynchro\ListEndpoints;

use CSRFSynchronizerToken;

class ListEndpointsPresenterBuilder
{
    /**
     * @param array $list_endpoints
     * @param CSRFSynchronizerToken $csrf_token_add
     * @param CSRFSynchronizerToken $csrf_token_delete
     * @return ListEndpointsPresenter[]
     */
    public function build(array $list_endpoints)
    {
        $csrf_token_delete = new \CSRFSynchronizerToken(TULEAP_SYNCHRO_URL . '/delete_endpoint');
        $csrf_token_add = new \CSRFSynchronizerToken(TULEAP_SYNCHRO_URL . '/add_endpoint');

        $endpoints['csrf_token_add'] = $csrf_token_add;

        foreach ($list_endpoints as $key => $endpoint) {
            $endpoints["endpoints"][] = new ListEndpointsPresenter($endpoint, $csrf_token_add, $csrf_token_delete);
        }
        return $endpoints;
    }
}
