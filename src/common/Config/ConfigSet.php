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

use Psr\EventDispatcher\EventDispatcherInterface;
use Tuleap\CLI\Events\GetWhitelistedKeys;
use Tuleap\Cryptography\ConcealedString;

final class ConfigSet
{
    public function __construct(
        private EventDispatcherInterface $event_dispatcher,
        private ConfigDao $config_dao,
    ) {
    }

    /**
     * @throws InvalidConfigKeyException
     * @throws UnknownConfigKeyException
     * @throws \Tuleap\Cryptography\Exception\CannotPerformIOOperationException
     */
    public function set(string $key, string $value): void
    {
        $white_listed_keys = $this->event_dispatcher->dispatch(new GetWhitelistedKeys());

        $key_metadata = $white_listed_keys->getKeyMetadata($key);
        if (! $key_metadata->can_be_modified) {
            throw new InvalidConfigKeyException($white_listed_keys);
        }

        if ($key_metadata->is_secret) {
            $value = \ForgeConfig::encryptValue(new ConcealedString($value));
        }

        $this->config_dao->save($key, $value);
    }
}
