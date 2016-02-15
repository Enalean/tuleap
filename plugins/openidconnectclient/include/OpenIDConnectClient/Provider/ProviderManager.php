<?php
/**
 * Copyright (c) Enalean, 2016. All Rights Reserved.
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

namespace Tuleap\OpenIDConnectClient\Provider;


class ProviderManager {
    /**
     * @var ProviderDao
     */
    private $dao;

    public function __construct(ProviderDao $dao) {
        $this->dao = $dao;
    }

    /**
     * @return Provider
     * @throws ProviderNotFoundException
     */
    public function getById($id) {
        $row = $this->dao->searchById($id);
        if ($row === false) {
            throw new ProviderNotFoundException();
        }
        return $this->instantiateFromRow($row);
    }

    /**
     * @return Provider[]
     */
    public function getConfiguredProviders() {
        $providers = array();
        $rows      = $this->dao->searchConfiguredProviders();
        if ($rows === false) {
            return $providers;
        }

        foreach ($rows as $row) {
            $providers[] = $this->instantiateFromRow($row);
        }

        return $providers;
    }

    /**
     * @return Provider
     */
    private function instantiateFromRow(array $row) {
        return new Provider(
            $row['id'],
            $row['name'],
            $row['authorization_endpoint'],
            $row['token_endpoint'],
            $row['user_info_endpoint'],
            $row['client_id'],
            $row['client_secret']
        );
    }

}