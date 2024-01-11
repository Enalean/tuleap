<?php
/**
 * Copyright (c) Enalean, 2023 - Present. All Rights Reserved.
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
use Tuleap\Config\ConfigKeyCategory;
use Tuleap\Config\ConfigKeyString;
use Tuleap\Config\FeatureFlagConfigKey;
use Tuleap\NeverThrow\Fault;

#[ConfigKeyCategory('Git')]
final class PreReceiveCommand extends Command
{
    #[FeatureFlagConfigKey("Feature flag to ignore specific git repositories by the git:pre-receive command")]
    #[ConfigKeyString("")]
    public const FEATURE_FLAG_KEY = 'pre_receive_ignored_repos_ids';

    public const NAME = 'git:pre-receive';

    public function __construct(
        private PreReceiveAction $action,
    ) {
        parent::__construct(self::NAME);
    }

    protected function configure(): void
    {
        $this->setDescription('This command calls an external module to decide whether or not the revisions passed on STDIN should be accepted.')
            ->addArgument(
                'repository_path',
                InputArgument::REQUIRED,
                'A git repository path'
            )
            ->setHidden(true);
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $repository_path = (string) $input->getArgument('repository_path');
        if ($repository_path === '') {
            $output->writeln('The repository_path cannot be empty');
            return self::FAILURE;
        }
        return $this->action->preReceiveExecute(
            $repository_path,
            stream_get_contents(STDIN),
        )->match(
            fn(): int => self::SUCCESS,
            function (Fault $fault) use ($output): int {
                $output->writeln(OutputFormatter::escape((string) $fault));
                return self::FAILURE;
            },
        );
    }
}
