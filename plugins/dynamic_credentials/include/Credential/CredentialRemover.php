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
 */

namespace Tuleap\DynamicCredentials\Credential;

class CredentialRemover
{
    /**
     * @var CredentialDAO
     */
    private $dao;
    /**
     * @var CredentialIdentifierExtractor
     */
    private $identifier_extractor;

    public function __construct(CredentialDAO $dao, CredentialIdentifierExtractor $identifier_extractor)
    {
        $this->dao                  = $dao;
        $this->identifier_extractor = $identifier_extractor;
    }

    /**
     * @throws CredentialInvalidUsernameException
     * @return bool
     */
    public function revokeByUsername($username)
    {
        $identifier = $this->identifier_extractor->extract($username);

        return $this->dao->revokeByIdentifier($identifier) > 0;
    }

    public function deleteExpired()
    {
        $this->dao->deleteByExpirationDate(time());
    }
}
