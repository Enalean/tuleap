<?php
/**
 * Copyright (c) Enalean, 2020-Present. All Rights Reserved.
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

declare(strict_types=1);

namespace Tuleap\OAuth2Server\App;

use Tuleap\Authentication\SplitToken\SplitTokenVerificationString;
use Tuleap\Authentication\SplitToken\SplitTokenVerificationStringHasher;

class ClientSecretUpdater
{

    /**
     * @var SplitTokenVerificationStringHasher
     */
    private $hasher;
    /**
     * @var AppDao
     */
    private $app_dao;
    /**
     * @var LastGeneratedClientSecretStore
     */
    private $client_secret_store;

    public function __construct(
        SplitTokenVerificationStringHasher $hasher,
        AppDao $app_dao,
        LastGeneratedClientSecretStore $client_secret_store
    ) {
        $this->hasher              = $hasher;
        $this->app_dao             = $app_dao;
        $this->client_secret_store = $client_secret_store;
    }

    public function updateClientSecret(int $app_id): void
    {
        $secret = SplitTokenVerificationString::generateNewSplitTokenVerificationString();
        $hashed_secret = $this->hasher->computeHash($secret);
        $this->app_dao->updateSecret($app_id, $hashed_secret);
        $this->client_secret_store->storeLastGeneratedClientSecret($app_id, $secret);
    }
}
