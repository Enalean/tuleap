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
use Symfony\Component\Console\Formatter\OutputFormatter;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

// ARG: Repo ID + reference
// Use Git_Exec.class.php to 'git cat-file'

final class PreReceiveAnalyzeCommand extends Command
{
    public const NAME = 'git:pre-receive-analyze';

    public function __construct()
    {
        parent::__construct(self::NAME);
    }

    protected function configure(): void
    {
        $this->setDescription('Does nothing as of yet')
            ->addOption('format', null, InputOption::VALUE_REQUIRED, 'The output format (txt or json)', 'txt')->setHidden(true);
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        switch ($format = $input->getOption('format')) {
            case 'txt':
                $this->displayAsText($output);
                break;
            case 'json':
                $this->displayAsJSON($output);
                break;
            default:
                $output->writeln(sprintf('<error>Unsupported format "%s". See help for supported formats.</error>', OutputFormatter::escape($format)));
                return self::FAILURE;
        }

        return self::SUCCESS;
    }

    private function preReceiveAnalyse(OutputInterface $output): int
    {
        return 0;
    }

    private function displayAsText(OutputInterface $output): void
    {
        $message = "rejection_message: " . $this->preReceiveAnalyse($output);
        $output->write($message);
    }

    private function displayAsJSON(OutputInterface $output): void
    {
        $rows = [
            'rejection_message' => $this->preReceiveAnalyse($output),
        ];
        $output->write(json_encode($rows));
    }
}
