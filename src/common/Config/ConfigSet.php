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
 *
 */

declare(strict_types=1);

namespace Tuleap\Config;

use Tuleap\Cryptography\ConcealedString;

final class ConfigSet implements ConfigUpdater
{
    public function __construct(
        private readonly KeyMetadataProvider & KeysThatCanBeModifiedProvider $config_keys,
        private readonly ConfigDao $config_dao,
    ) {
    }

    /**
     * @throws InvalidConfigKeyException
     * @throws InvalidConfigKeyValueException
     * @throws UnknownConfigKeyException
     * @throws \Tuleap\Cryptography\Exception\CannotPerformIOOperationException
     */
    public function set(string $key, string|ConcealedString $value): void
    {
        $key_metadata = $this->config_keys->getKeyMetadata($key);
        if (! $key_metadata->can_be_modified) {
            throw new InvalidConfigKeyException($this->config_keys);
        }

        if ($key_metadata->is_secret) {
            $secret = new ConcealedString((string) $value);
            if ($key_metadata->secret_validator) {
                $key_metadata->secret_validator->checkIsValid($secret);
            }
            $value = \ForgeConfig::encryptValue($secret);
        } elseif ($key_metadata->value_validator) {
            $key_metadata->value_validator->checkIsValid((string) $value);
        }

        $this->config_dao->save($key, (string) $value);
    }
}
