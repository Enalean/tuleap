<?php
/**
 * Copyright (c) Enalean, 2022 - Present. All Rights Reserved.
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

namespace Tuleap\Git\Hook\PreReceive;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Formatter\OutputFormatter;

final class PreReceiveAnalyzeCommand extends Command
{
    public const NAME = 'git:pre-receive-analyze';

    public function __construct(private PreReceiveAnalyzeAction $action)
    {
        parent::__construct(self::NAME);
    }

    protected function configure(): void
    {
        $this->setDescription('Does nothing as of yet')
        ->addArgument(
            'repository_id',
            InputArgument::REQUIRED,
            'A git repository ID'
        )
        ->addArgument(
            'git_reference',
            InputArgument::REQUIRED,
            'A reference to a git object'
        )
        ->setHidden(true);
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            return $this->action->preReceiveAnalyse($input->getArgument('repository_id'), $input->getArgument('git_reference'));
        } catch (PreReceiveRepositoryNotFoundException $e) {
            $output->writeln(sprintf('<error>The ID "%s" does not correspond to any repository.</error>', OutputFormatter::escape($input->getArgument('repository_id'))));
            return self::FAILURE;
        } catch (PreReceiveCannotRetrieveReferenceException $e) {
            $output->writeln('<error>Git command execution failure, check your git reference.</error>');
            return self::FAILURE;
        }
    }
}
