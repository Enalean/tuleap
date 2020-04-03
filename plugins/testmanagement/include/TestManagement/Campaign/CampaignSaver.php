<?php
/**
 * Copyright (c) Enalean, 2018 - present. All Rights Reserved.
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

namespace Tuleap\TestManagement\Campaign;

use Tuleap\Cryptography\ConcealedString;
use Tuleap\Cryptography\KeyFactory;
use Tuleap\Cryptography\Symmetric\SymmetricCrypto;

class CampaignSaver
{
    /** @var CampaignDao */
    private $dao;
    /**
     * @var KeyFactory
     */
    private $key_factory;

    public function __construct(CampaignDao $dao, KeyFactory $key_factory)
    {
        $this->dao         = $dao;
        $this->key_factory = $key_factory;
    }

    public function save(Campaign $campaign): void
    {
        $job_configuration = $campaign->getJobConfiguration();

        $this->dao->update(
            $campaign->getArtifact()->getId(),
            $job_configuration->getUrl(),
            $this->getEncryptedToken($job_configuration->getToken())
        );
    }

    private function getEncryptedToken(ConcealedString $token): string
    {
        return SymmetricCrypto::encrypt($token, $this->key_factory->getEncryptionKey());
    }
}
