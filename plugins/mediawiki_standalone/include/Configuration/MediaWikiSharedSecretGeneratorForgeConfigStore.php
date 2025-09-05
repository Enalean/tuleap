<?php
/**
 * Copyright (c) Enalean, 2022-Present. All Rights Reserved.
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

namespace Tuleap\MediawikiStandalone\Configuration;

use Tuleap\Config\ConfigDao;
use Tuleap\Cryptography\ConcealedString;
use Tuleap\MediawikiStandalone\Instance\MediawikiHTTPClientFactory;

final class MediaWikiSharedSecretGeneratorForgeConfigStore implements MediaWikiSharedSecretGenerator
{
    public function __construct(private ConfigDao $config_dao)
    {
    }

    #[\Override]
    public function generateSharedSecret(): ConcealedString
    {
        $secret = new ConcealedString(sodium_bin2hex(random_bytes(32)));

        $this->config_dao->save(
            MediawikiHTTPClientFactory::SHARED_SECRET,
            \ForgeConfig::encryptValue($secret)
        );

        return $secret;
    }
}
