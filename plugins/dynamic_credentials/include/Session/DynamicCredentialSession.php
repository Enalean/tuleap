<?php
/**
 * Copyright (c) Enalean, 2018 - Present. All Rights Reserved.
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

namespace Tuleap\DynamicCredentials\Session;

use Tuleap\Cryptography\ConcealedString;
use Tuleap\DynamicCredentials\Credential\Credential;
use Tuleap\DynamicCredentials\Credential\CredentialRetriever;

class DynamicCredentialSession
{
    public function __construct(private DynamicCredentialIdentifierStorage $storage, private CredentialRetriever $credential_retriever)
    {
    }

    /**
     * @throws \Tuleap\DynamicCredentials\Credential\CredentialInvalidUsernameException
     * @throws \Tuleap\DynamicCredentials\Credential\CredentialExpiredException
     * @throws \Tuleap\DynamicCredentials\Credential\CredentialAuthenticationException
     * @throws \Tuleap\DynamicCredentials\Credential\CredentialNotFoundException
     */
    public function initialize(string $username, ConcealedString $password)
    {
        $credential = $this->credential_retriever->authenticate($username, $password);
        $this->storage->setIdentifier($credential->getIdentifier());
    }

    /**
     * @return Credential
     * @throws DynamicCredentialSessionNotInitializedException
     * @throws \Tuleap\DynamicCredentials\Credential\CredentialNotFoundException
     */
    public function getAssociatedCredential()
    {
        $identifier = $this->storage->getIdentifier();
        if ($identifier === null) {
            throw new DynamicCredentialSessionNotInitializedException();
        }

        return $this->credential_retriever->getByIdentifier($identifier);
    }
}
