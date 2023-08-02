<?php
/**
 * Copyright (c) Enalean, 2023-Present. All Rights Reserved.
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

namespace Tuleap\DynamicCredentials\Plugin;

use Tuleap\Config\ConfigKey;
use Tuleap\Config\ConfigKeyCategory;
use Tuleap\Config\ConfigKeyString;
use Tuleap\Cryptography\Asymmetric\SignaturePublicKey;
use Tuleap\Cryptography\ConcealedString;
use Tuleap\Option\Option;

#[ConfigKeyCategory('Dynamic Credentials')]
final class DynamicCredentialsSettings
{
    private const DEFAULT_REAL_NAME = 'Dynamic user';

    #[ConfigKey('Real name to use for the dynamic user')]
    #[ConfigKeyString(self::DEFAULT_REAL_NAME)]
    public const USER_REALNAME = 'dynamic_credentials_user_real_name';

    #[ConfigKey('Public key used to verify dynamic credentials requests')]
    #[ConfigKeyString]
    public const SIGNATURE_PUBLIC_KEY = 'dynamic_credentials_signature_public_key';

    private const USER_REALNAME_OLD_INC_FILE_SETTING        = 'dynamic_user_realname';
    private const SIGNATURE_PUBLIC_KEY_OLD_INC_FILE_SETTING = 'signature_public_key';

    public function __construct(
        private readonly PluginInfo $plugin_info,
    ) {
    }

    /**
     * @return Option<SignaturePublicKey>
     */
    public function getSignaturePublicKey(): Option
    {
        $signature_public_key = (string) \ForgeConfig::get(self::SIGNATURE_PUBLIC_KEY);
        if ($signature_public_key !== '') {
            return Option::fromValue(self::buildPublicKey($signature_public_key));
        }

        $signature_public_key_old_inc_file = (string) $this->plugin_info->getPropertyValueForName(self::SIGNATURE_PUBLIC_KEY_OLD_INC_FILE_SETTING);
        if ($signature_public_key_old_inc_file !== '') {
            return Option::fromValue(self::buildPublicKey($signature_public_key_old_inc_file));
        }

        return Option::nothing(SignaturePublicKey::class);
    }

    private static function buildPublicKey(string $content): SignaturePublicKey
    {
        return new SignaturePublicKey(new ConcealedString(base64_decode($content)));
    }

    public function getDynamicUserRealname(): string
    {
        $real_name = (string) \ForgeConfig::get(self::USER_REALNAME);
        if ($real_name !== self::DEFAULT_REAL_NAME && $real_name !== '') {
            return $real_name;
        }

        $real_name_old_inc = (string) $this->plugin_info->getPropertyValueForName(self::USER_REALNAME_OLD_INC_FILE_SETTING);
        if ($real_name_old_inc === '') {
            return $real_name;
        }

        return $real_name_old_inc;
    }
}
