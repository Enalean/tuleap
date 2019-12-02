<?php
/**
 * Copyright (c) Enalean, 2018-Present. All Rights Reserved.
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

namespace Tuleap\CLI\Command;

use SiteCache;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Tuleap\CLI\ConsoleLogger;

class ClearCachesCommand extends Command
{
    public const NAME = '-c, --clear-caches';

    protected function configure()
    {
        $this->setName(self::NAME)->setDescription('Clear caches');
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $site_cache = new SiteCache(new ConsoleLogger($output));
        $site_cache->invalidatePluginBasedCaches();
        return 0;
    }
}
