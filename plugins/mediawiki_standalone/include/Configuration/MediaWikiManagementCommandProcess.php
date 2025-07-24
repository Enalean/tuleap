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
 *
 */

declare(strict_types=1);

namespace Tuleap\MediawikiStandalone\Configuration;

use Psr\Log\LoggerInterface;
use Symfony\Component\Process\Process;
use Tuleap\Cryptography\ConcealedString;
use Tuleap\NeverThrow\Err;
use Tuleap\NeverThrow\Ok;
use Tuleap\NeverThrow\Result;

final class MediaWikiManagementCommandProcess implements MediaWikiManagementCommand
{
    private Process $process;

    /**
     * @param array<string, string|ConcealedString> $parameters
     */
    public function __construct(private LoggerInterface $logger, string $commandline, array $parameters = [])
    {
        $this->process = Process::fromShellCommandline($commandline, '/');
        $this->process->setEnv($parameters);
        $this->process->setTimeout(null);
        $this->process->start();
    }

    /**
     * @return Ok<null>|Err<MediaWikiManagementCommandFailure>
     */
    public function wait(): Ok|Err
    {
        $exit_code      = $this->process->wait();
        $process_output = $this->process->getOutput() . $this->process->getErrorOutput();
        $this->logger->debug($process_output);
        if ($exit_code <= 0) {
            return Result::ok(null);
        }

        return Result::err(new MediaWikiManagementCommandFailure($exit_code, $this->process->getCommandLine(), $process_output));
    }
}
