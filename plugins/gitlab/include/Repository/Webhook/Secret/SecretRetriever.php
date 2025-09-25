<?php
/**
 * Copyright (c) Enalean, 2020-Present. All Rights Reserved.
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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

declare(strict_types=1);

namespace Tuleap\Gitlab\Repository\Webhook\Secret;

use Tuleap\Cryptography\ConcealedString;
use Tuleap\Cryptography\KeyFactory;
use Tuleap\Cryptography\SymmetricLegacy2025\SymmetricCrypto;
use Tuleap\Gitlab\Repository\GitlabRepositoryIntegration;
use Tuleap\Gitlab\Repository\Webhook\WebhookDao;

class SecretRetriever
{
    /**
     * @var WebhookDao
     */
    private $dao;

    /**
     * @var KeyFactory
     */
    private $key_factory;

    public function __construct(WebhookDao $dao, KeyFactory $key_factory)
    {
        $this->dao         = $dao;
        $this->key_factory = $key_factory;
    }

    /**
     * @throws SecretNotDefinedException
     */
    public function getWebhookSecretForRepository(GitlabRepositoryIntegration $gitlab_repository_integration): ConcealedString
    {
        $row = $this->dao->getGitlabRepositoryWebhook($gitlab_repository_integration->getId());
        if ($row === null) {
            throw new SecretNotDefinedException($gitlab_repository_integration->getId());
        }

        return SymmetricCrypto::decrypt(
            $row['webhook_secret'],
            $this->key_factory->getLegacy2025EncryptionKey()
        );
    }
}
