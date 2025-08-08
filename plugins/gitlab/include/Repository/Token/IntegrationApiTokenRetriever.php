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

use Tuleap\Gitlab\Repository\GitlabRepositoryIntegration;
use Tuleap\Cryptography\KeyFactory;
use Tuleap\Cryptography\SymmetricLegacy2025\SymmetricCrypto;

class IntegrationApiTokenRetriever
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

    public function getIntegrationAPIToken(GitlabRepositoryIntegration $repository_integration): ?IntegrationApiToken
    {
        $row = $this->dao->searchIntegrationAPIToken($repository_integration->getId());

        if (! $row) {
            return null;
        }

        return IntegrationApiToken::buildAlreadyKnownToken(
            SymmetricCrypto::decrypt(
                $row['token'],
                $this->key_factory->getLegacy2025EncryptionKey()
            ),
            $row['is_email_already_send_for_invalid_token'],
        );
    }
}
