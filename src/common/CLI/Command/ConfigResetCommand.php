<?php
/**
 * Copyright (c) Enalean, 2023-Present. All Rights Reserved.
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

namespace Tuleap\CLI\Command;

use ForgeConfig;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\InvalidArgumentException;
use Symfony\Component\Console\Formatter\OutputFormatter;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Tuleap\Config\ConfigDao;
use Tuleap\Config\KeyMetadataProvider;

final class ConfigResetCommand extends Command
{
    public const NAME = 'config-reset';

    public function __construct(
        private readonly KeyMetadataProvider $config_keys,
        private readonly ConfigDao $config_dao,
    ) {
        parent::__construct(self::NAME);
    }

    protected function configure(): void
    {
        $this->setDescription('Reset a configuration setting to its default value')
            ->addArgument('key', InputArgument::REQUIRED, 'Variable key');
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $key = $input->getArgument('key');
        if (! ForgeConfig::exists($key)) {
            throw new InvalidArgumentException("Invalid key $key");
        }

        $key_metadata = $this->config_keys->getKeyMetadata($key);

        if (! $key_metadata->can_be_modified) {
            throw new InvalidArgumentException("$key cannot be modified");
        }

        if (! $key_metadata->has_default_value) {
            throw new InvalidArgumentException("$key does not have a default value");
        }

        $this->config_dao->delete($key);

        $output->writeln(sprintf("%s has been successfully reset to its default value", OutputFormatter::escape($key)));

        return self::SUCCESS;
    }
}
