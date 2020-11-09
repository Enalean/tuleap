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
use Tuleap\Cryptography\Symmetric\SymmetricCrypto;

class SecretGenerator
{
    /**
     * @var KeyFactory
     */
    private $key_factory;

    /**
     * @var SecretDao
     */
    private $dao;

    public function __construct(KeyFactory $key_factory, SecretDao $dao)
    {
        $this->key_factory = $key_factory;
        $this->dao         = $dao;
    }

    public function generateSecretForGitlabRepository(int $generated_gitlab_repository_id): ConcealedString
    {
        $secret = new ConcealedString(\sodium_bin2hex(\random_bytes(32)));

        $encrypted_secret = SymmetricCrypto::encrypt(
            $secret,
            $this->key_factory->getEncryptionKey()
        );

        $this->dao->storeGitlabRepositoryWebhookSecret(
            $generated_gitlab_repository_id,
            $encrypted_secret
        );

        return $secret;
    }
}
