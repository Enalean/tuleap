<?php
/**
 * Copyright (c) Enalean, 2017 - Present. All Rights Reserved.
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

namespace Tuleap\Bugzilla\Reference;

use Tuleap\Cryptography\ConcealedString;
use Tuleap\Cryptography\SymmetricLegacy2025\EncryptionKey;
use Tuleap\Cryptography\SymmetricLegacy2025\SymmetricCrypto;

class ReferenceRetriever
{
    /**
     * @var Dao
     */
    private $dao;
    /**
     * @var EncryptionKey
     */
    private $encryption_key;

    public function __construct(Dao $dao, EncryptionKey $encryption_key)
    {
        $this->dao            = $dao;
        $this->encryption_key = $encryption_key;
    }

    /**
     * @return Reference[]
     */
    public function getAllReferences()
    {
        $references = [];

        foreach ($this->dao->searchAllReferences() as $row) {
            $references[] = $this->instantiateFromRow($row);
        }

        return $references;
    }

    private function instantiateFromRow(array $references)
    {
        $api_key = new ConcealedString($references['api_key']);
        if ($references['encrypted_api_key'] !== '') {
            $api_key = SymmetricCrypto::decrypt($references['encrypted_api_key'], $this->encryption_key);
        }

        return new Reference(
            $references['id']->toString(),
            $references['keyword'],
            $references['server'],
            $references['username'],
            $api_key,
            $references['are_followup_private'],
            $references['rest_url'],
            (bool) $references['has_api_key_always_been_encrypted']
        );
    }

    public function getReferenceByKeyword($keyword)
    {
        $row = $this->dao->searchReferenceByKeyword($keyword);
        if ($row === null) {
            return null;
        }

        return $this->instantiateFromRow($row);
    }
}
