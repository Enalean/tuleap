<?php
/**
 * Copyright (c) Enalean, 2021-Present. All Rights Reserved.
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

namespace Tuleap\Queue;

use ForgeConfig;
use Tuleap\Config\ConfigKey;
use Tuleap\Config\ConfigKeyInt;
use Tuleap\Config\ConfigKeyValueValidator;

class WorkerAvailability implements IsAsyncTaskProcessingAvailable
{
    #[ConfigKey(<<<'EOS'
    Number of backend workers to process background jobs.
    To be adapted based on your server workload. 2 is a good starting value, you can increase it if needed.
    EOS)]
    #[ConfigKeyInt(2)]
    #[ConfigKeyValueValidator(NbBackendWorkersConfigValidator::class)]
    public const NB_BACKEND_WORKERS_CONFIG_KEY = 'sys_nb_backend_workers';

    public function canProcessAsyncTasks(): bool
    {
        return $this->getWorkerCount() > 0;
    }

    public function getWorkerCount(): int
    {
        if (! \Tuleap\Redis\ClientFactory::canClientBeBuiltFromForgeConfig()) {
            return 0;
        }

        if (ForgeConfig::exists(self::NB_BACKEND_WORKERS_CONFIG_KEY)) {
            return abs(ForgeConfig::getInt(self::NB_BACKEND_WORKERS_CONFIG_KEY));
        }

        return 2;
    }
}
