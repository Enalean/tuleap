<?php
/**
 * Copyright (c) Enalean, 2024-Present. All Rights Reserved.
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

namespace TuleapCfg\Command\SiteDeploy\Nginx;

use Symfony\Component\Process\Process;

final class CurrentCPUInformation implements CPUInformation
{
    #[\Override]
    public function wordSize(): int
    {
        // This is a good enough assumption given the underlying OS only support
        // x86_64 and recent ARM CPUs
        return PHP_INT_SIZE;
    }

    #[\Override]
    public function l1CacheLineSize(): int
    {
        // \posix_sysconf() is PHP 8.3+
        $getconf_process = new Process(['getconf', 'LEVEL1_DCACHE_LINESIZE']);
        $getconf_process->run();

        if (! $getconf_process->isSuccessful()) {
            // Assume a small L1 cache line if we cannot retrieve it
            return 32;
        }

        return (int) $getconf_process->getOutput();
    }
}
