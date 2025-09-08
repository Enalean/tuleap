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
 *
 */

declare(strict_types=1);

namespace TuleapCfg\Command\SiteDeploy\Gitolite3;

use Psr\Log\LogLevel;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Logger\ConsoleLogger;
use Symfony\Component\Console\Output\OutputInterface;
use TuleapCfg\Command\ProcessFactory;

final class SiteDeployGitolite3HooksCommand extends Command
{
    public const NAME = 'site-deploy:gitolite3-hooks';

    public function __construct()
    {
        parent::__construct(self::NAME);
    }

    #[\Override]
    protected function configure(): void
    {
        $this->setDescription('Deploy Gitolite3 git hooks');
    }

    #[\Override]
    public function execute(InputInterface $input, OutputInterface $output): int
    {
        (new SiteDeployGitolite3Hooks(new ProcessFactory()))->deploy(
            new ConsoleLogger($output, [LogLevel::INFO => OutputInterface::VERBOSITY_NORMAL])
        );

        return Command::SUCCESS;
    }
}
