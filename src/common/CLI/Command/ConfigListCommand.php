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
use Symfony\Component\Console\Helper\TableCell;
use Symfony\Component\Console\Helper\TableCellStyle;
use Symfony\Component\Console\Helper\TableSeparator;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Tuleap\Config\GetConfigKeys;
use Tuleap\Config\ConfigKeyMetadata;

class ConfigListCommand extends Command
{
    public const NAME = 'config-list';

    public function __construct(private EventDispatcherInterface $event_manager)
    {
        parent::__construct(self::NAME);
    }

    #[\Override]
    protected function configure(): void
    {
        $this->setDescription('List configuration values that can be modified with `config-set`');
    }

    #[\Override]
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $config_keys = $this->event_manager->dispatch(new GetConfigKeys());

        $table = new Table($output);
        $table->setHeaders(['Variable', 'Documentation', 'How to set ?']);

        $categorized_rows   = [];
        $uncategorized_rows = [];
        foreach ($config_keys->getSortedKeysWithMetadata() as $key => $key_metadata) {
            if ($key_metadata->is_hidden) {
                continue;
            }
            if ($key_metadata->category) {
                $categorized_rows[$key_metadata->category][] = $this->getKeyRow($key, $key_metadata);
            } else {
                $uncategorized_rows[] = $this->getKeyRow($key, $key_metadata);
            }
        }
        foreach ($categorized_rows as $category => $rows) {
            $table->addRow([new TableCell(strtoupper($category), ['colspan' => 3, 'style' => new TableCellStyle(['align' => 'center'])])]);
            $table->addRows($rows);
            $table->addRow(new TableSeparator());
        }
        $table->addRows($uncategorized_rows);

        $table->render();
        return 0;
    }

    private function getKeyRow(string $key, ConfigKeyMetadata $key_metadata): array
    {
        return [
            $key,
            $key_metadata->description,
            $key_metadata->can_be_modified->getModifierLabel(),
        ];
    }
}
