<?php
/**
 * Copyright (c) Enalean, 2021 - Present. All Rights Reserved.
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
 * along with Tuleap. If not, see http://www.gnu.org/licenses/.
 */

namespace Tuleap\Gitlab\Repository\Token;

use Tuleap\Cryptography\ConcealedString;
use Tuleap\Gitlab\Repository\GitlabRepositoryIntegration;
use Tuleap\Cryptography\KeyFactory;
use Tuleap\Cryptography\SymmetricLegacy2025\SymmetricCrypto;

class IntegrationApiTokenInserter
{
    /**
     * @var IntegrationApiTokenDao
     */
    private $dao;
    /**
     * @var KeyFactory
     */
    private $key_factory;

    public function __construct(IntegrationApiTokenDao $dao, KeyFactory $key_factory)
    {
        $this->dao         = $dao;
        $this->key_factory = $key_factory;
    }

    public function insertToken(GitlabRepositoryIntegration $repository_integration, ConcealedString $token): void
    {
        $encrypted_secret = SymmetricCrypto::encrypt(
            $token,
            $this->key_factory->getLegacy2025EncryptionKey()
        );

        $this->dao->storeToken(
            $repository_integration->getId(),
            $encrypted_secret
        );
    }
}
