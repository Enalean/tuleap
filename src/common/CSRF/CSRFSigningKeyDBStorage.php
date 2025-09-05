<?php
/**
 * Copyright (c) Enalean, 2025-Present. All Rights Reserved.
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

namespace Tuleap\CSRF;

use Tuleap\Config\ConfigCannotBeModified;
use Tuleap\Config\ConfigDao;
use Tuleap\Config\ConfigKey;
use Tuleap\Config\ConfigKeyHidden;
use Tuleap\Config\ConfigKeySecret;
use Tuleap\Cryptography\ConcealedString;

final class CSRFSigningKeyDBStorage implements CSRFSigningKeyStorage
{
    #[ConfigKey('Key used to sign CSRF tokens')]
    #[ConfigKeySecret]
    #[ConfigCannotBeModified]
    #[ConfigKeyHidden]
    private const NAME = 'csrf_token_signing_key';

    private ?ConcealedString $signing_key = null;

    public function __construct(private readonly ConfigDao $config_dao)
    {
    }

    #[\Override]
    public function getSigningKey(): ConcealedString
    {
        if ($this->signing_key !== null) {
            return $this->signing_key;
        }
        if (! \ForgeConfig::exists(self::NAME)) {
            $signing_key = new ConcealedString(sodium_bin2base64(random_bytes(32), SODIUM_BASE64_VARIANT_URLSAFE_NO_PADDING));
            $this->config_dao->save(
                self::NAME,
                \ForgeConfig::encryptValue($signing_key)
            );
        } else {
            $signing_key = \ForgeConfig::getSecretAsClearText(self::NAME);
        }
        $this->signing_key = $signing_key;
        return $signing_key;
    }
}
