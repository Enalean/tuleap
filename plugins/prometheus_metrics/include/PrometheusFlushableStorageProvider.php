<?php
/**
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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

namespace Tuleap\PrometheusMetrics;

use Enalean\Prometheus\Storage\FlushableStorage;
use Enalean\Prometheus\Storage\NullStore;
use Enalean\Prometheus\Storage\RedisStore;
use ForgeConfig;
use Tuleap\Instrument\Prometheus\Prometheus;
use Tuleap\Redis\ClientFactory;

final class PrometheusFlushableStorageProvider
{
    public function getFlushableStorage(): FlushableStorage
    {
        if (
            ClientFactory::canClientBeBuiltFromForgeConfig() &&
            ForgeConfig::exists(Prometheus::CONFIG_PROMETHEUS_PLATFORM)
        ) {
            return new RedisStore(ClientFactory::fromForgeConfig());
        }
        return new NullStore();
    }
}
