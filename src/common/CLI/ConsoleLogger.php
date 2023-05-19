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

namespace Tuleap\CLI;

use Psr\Log\LoggerInterface;
use Psr\Log\AbstractLogger;
use Symfony\Component\Console\Formatter\OutputFormatter;
use Symfony\Component\Console\Output\OutputInterface;

class ConsoleLogger extends AbstractLogger implements LoggerInterface
{
    /**
     * @var OutputInterface
     */
    private $output;

    public function __construct(OutputInterface $output)
    {
        $this->output = $output;
    }

    public function log($level, string|\Stringable $message, array $context = []): void
    {
        $this->output->writeln($this->colorize($level, $message));
    }

    private function colorize($level, $message)
    {
        $color = null;
        switch ($level) {
            case \Psr\Log\LogLevel::INFO:
                return '<info>' . OutputFormatter::escape($message) . '</info>';
            case \Psr\Log\LogLevel::WARNING:
                return '<fg=yellow>' . OutputFormatter::escape($message) . '</>';
            case \Psr\Log\LogLevel::ERROR:
                return '<error>' . OutputFormatter::escape($message) . '</error>';
            default:
                return OutputFormatter::escape($message);
        }
    }
}
