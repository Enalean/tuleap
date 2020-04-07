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

class CredentialRetriever
{
    /**
     * @var CredentialDAO
     */
    private $dao;
    /**
     * @var \PasswordHandler
     */
    private $password_handler;
    /**
     * @var CredentialIdentifierExtractor
     */
    private $identifier_extractor;

    public function __construct(CredentialDAO $dao, \PasswordHandler $password_handler, CredentialIdentifierExtractor $identifier_extractor)
    {
        $this->dao                  = $dao;
        $this->password_handler     = $password_handler;
        $this->identifier_extractor = $identifier_extractor;
    }

    /**
     * @return Credential
     * @throws CredentialAuthenticationException
     * @throws CredentialExpiredException
     * @throws CredentialInvalidUsernameException
     * @throws CredentialNotFoundException
     */
    public function authenticate($username, $password)
    {
        $identifier = $this->identifier_extractor->extract($username);
        $row        = $this->dao->getUnrevokedCredentialByIdentifier($identifier);
        if (empty($row)) {
            throw new CredentialNotFoundException();
        }

        if (! $this->password_handler->verifyHashPassword($password, $row['password'])) {
            throw new CredentialAuthenticationException();
        }

        $account = $this->instantiateCredential($row);
        if ($account->hasExpired()) {
            throw new CredentialExpiredException();
        }

        return $account;
    }

    /**
     * @return Credential
     * @throws CredentialNotFoundException
     */
    public function getByIdentifier($identifier)
    {
        $row = $this->dao->getUnrevokedCredentialByIdentifier($identifier);
        if (empty($row)) {
            throw new CredentialNotFoundException();
        }

        return $this->instantiateCredential($row);
    }

    /**
     * @return Credential
     */
    private function instantiateCredential(array $row)
    {
        $expiration_date = new \DateTimeImmutable('@' . $row['expiration']);
        return new Credential($row['identifier'], $expiration_date);
    }
}
