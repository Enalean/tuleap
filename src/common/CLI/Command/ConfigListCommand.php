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

namespace Tuleap\CLI\Command;

use Psr\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Tuleap\CLI\Events\GetWhitelistedKeys;

class ConfigListCommand extends Command
{
    public const NAME = 'config-list';

    /**
     * @var EventDispatcherInterface
     */
    private $event_manager;

    public function __construct(EventDispatcherInterface $event_manager)
    {
        parent::__construct(self::NAME);
        $this->event_manager = $event_manager;
    }

    protected function configure(): void
    {
        $this->setDescription('List configuration values that can be modified with `config-set`');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $white_listed_keys = $this->event_manager->dispatch(GetWhitelistedKeys::build());
        assert($white_listed_keys instanceof GetWhitelistedKeys);

        $table = new Table($output);
        $table->setHeaders(['Variable', 'Documentation']);

        foreach ($white_listed_keys->getSortedKeysWithMetadata() as $key => $summary) {
            $table->addRow([$key, $summary]);
        }

        $table->render();
        return 0;
    }
}
