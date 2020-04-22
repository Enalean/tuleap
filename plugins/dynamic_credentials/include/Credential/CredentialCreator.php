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

use Tuleap\Cryptography\ConcealedString;

class CredentialCreator
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
     * @throws CredentialInvalidUsernameException
     * @throws DuplicateCredentialException
     */
    public function create($username, ConcealedString $password, \DateTimeImmutable $expiration)
    {
        $identifier          = $this->identifier_extractor->extract($username);
        $hashed_password     = $this->password_handler->computeHashPassword($password);

        try {
            $this->dao->save($identifier, $hashed_password, $expiration->getTimestamp());
        } catch (\PDOException $ex) {
            throw new DuplicateCredentialException();
        }
    }
}
