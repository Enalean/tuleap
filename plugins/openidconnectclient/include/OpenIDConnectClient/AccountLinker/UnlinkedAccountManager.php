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

namespace Tuleap\OpenIDConnectClient\AccountLinker;

use RandomNumberGenerator;

class UnlinkedAccountManager
{

    /**
     * @var UnlinkedAccountDao
     */
    private $dao;
    /**
     * @var RandomNumberGenerator
     */
    private $random_number_generator;

    public function __construct(UnlinkedAccountDao $dao, RandomNumberGenerator $random_number_generator)
    {
        $this->dao                     = $dao;
        $this->random_number_generator = $random_number_generator;
    }

    /**
     * @return UnlinkedAccount
     * @throws UnlinkedAccountNotFoundException
     */
    public function getbyId($id)
    {
        $row = $this->dao->searchById($id);
        if ($row === false) {
            throw new UnlinkedAccountNotFoundException();
        }
        return $this->instantiateFromRow($row);
    }

    /**
     * @return UnlinkedAccount
     * @throws UnlinkedAccountDataAccessException
     */
    public function create($provider_id, $user_identifier)
    {
        $id       = $this->random_number_generator->getNumber();
        $is_saved = $this->dao->save($id, $provider_id, $user_identifier);
        if (! $is_saved) {
            throw new UnlinkedAccountDataAccessException();
        }
        return new UnlinkedAccount($id, $provider_id, $user_identifier);
    }

    /**
     * @throws UnlinkedAccountDataAccessException
     */
    public function removeById($id)
    {
        $is_deleted = $this->dao->deleteById($id);
        if (! $is_deleted) {
            throw new UnlinkedAccountDataAccessException();
        }
    }

    /**
     * @return UnlinkedAccount
     */
    private function instantiateFromRow(array $row)
    {
        return new UnlinkedAccount(
            $row['id'],
            $row['provider_id'],
            $row['openidconnect_identifier']
        );
    }
}
