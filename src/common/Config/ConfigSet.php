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

use ConfigDao;
use Psr\EventDispatcher\EventDispatcherInterface;
use Tuleap\CLI\Events\GetWhitelistedKeys;

final class ConfigSet
{
    /**
     * @var ConfigDao
     */
    private $config_dao;
    /**
     * @var EventDispatcherInterface
     */
    private $event_dispatcher;

    public function __construct(EventDispatcherInterface $event_dispatcher, ConfigDao $config_dao)
    {
        $this->event_dispatcher = $event_dispatcher;
        $this->config_dao       = $config_dao;
    }

    /**
     * @throws InvalidConfigKeyException
     */
    public function set(string $key, string $value): void
    {
        $white_listed_keys = $this->event_dispatcher->dispatch(GetWhitelistedKeys::build());
        assert($white_listed_keys instanceof GetWhitelistedKeys);

        if (! $white_listed_keys->isKeyWhiteListed($key)) {
            throw new InvalidConfigKeyException($white_listed_keys);
        }

        $this->config_dao->save($key, $value);
    }
}
