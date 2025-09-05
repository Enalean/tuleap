<?php
/**
 * Copyright (c) Enalean, 2022-Present. All Rights Reserved.
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

namespace Tuleap\PluginsAdministration\LifecycleHookCommand;

use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Formatter\OutputFormatter;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Tuleap\CLI\AssertRunner;
use Tuleap\CLI\ConsoleLogger;
use Tuleap\Plugin\LifecycleHookCommand\PluginExecuteUpdateHookEvent;

final class PluginUpdateHookCommand extends Command
{
    public const NAME = 'plugins_administration:update_hook';

    public function __construct(
        private EventDispatcherInterface $event_dispatcher,
        private AssertRunner $assert_runner,
        private LoggerInterface $logger,
    ) {
        parent::__construct(self::NAME);
    }

    #[\Override]
    protected function configure(): void
    {
        $this->setDescription('Execute plugins update hook')->setHidden(true);
    }

    #[\Override]
    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->assert_runner->assertProcessIsExecutedByExpectedUser();

        $output->writeln('<info>Execute plugin update hooks</info>');
        try {
            $this->event_dispatcher->dispatch(new PluginExecuteUpdateHookEvent(new ConsoleLogger($output)));
        } catch (\Exception $exception) {
            $message = 'An error has been encountered while running plugin update hooks';
            $output->writeln('<error>' . OutputFormatter::escape($message) . '</error>');
            $this->logger->error($message, ['exception' => $exception]);
            throw $exception;
        }
        $output->writeln('<info>Plugin update hooks have been executed</info>');
        return 0;
    }
}
