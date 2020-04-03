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
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

final class ClearPrometheusMetricsCommand extends Command
{
    public const NAME = 'prometheus_metrics:clear';
    /**
     * @var FlushableStorage
     */
    private $flushable_storage;

    public function __construct(FlushableStorage $flushable_storage)
    {
        parent::__construct(self::NAME);
        $this->flushable_storage = $flushable_storage;
    }

    protected function configure(): void
    {
        $this->setDescription('Clear Prometheus metrics');
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->flushable_storage->flush();
        $output->writeln('<info>Prometheus metrics have been cleared</info>');
        return 0;
    }
}
