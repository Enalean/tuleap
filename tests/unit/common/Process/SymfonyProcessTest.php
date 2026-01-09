<?php
/**
 * Copyright (c) Enalean, 2025-Present. All Rights Reserved.
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

namespace Tuleap\Process;

use Tuleap\NeverThrow\Result;
use Tuleap\Test\PHPUnit\TestCase;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class SymfonyProcessTest extends TestCase
{
    public function testCanRunSuccessfulProcess(): void
    {
        $process = new SymfonyProcess(new \Symfony\Component\Process\Process(['sh', '-c', 'echo -n "STDOUT" && echo -n "STDERR" 1>&2']));
        $result  = $process->run();

        self::assertTrue(Result::isOk($result));
        $result->map(
            function (ProcessOutput $output): void {
                self::assertEquals('STDOUT', $output->getOutput());
                self::assertEquals('STDERR', $output->getErrorOutput());
            }
        );
    }

    public function testCanRunFailingProcess(): void
    {
        $process = new SymfonyProcess(new \Symfony\Component\Process\Process(['sh', '-c', 'echo -n "STDOUT" && echo -n "STDERR" 1>&2 && exit 1']));
        $result  = $process->run();

        self::assertTrue(Result::isErr($result));
        $result->mapErr(
            function (ProcessExecutionFailure $execution_failure): void {
                self::assertStringContainsString('exit code 1', (string) $execution_failure->fault);
                self::assertEquals('STDOUT', $execution_failure->process_output->getOutput());
                self::assertEquals('STDERR', $execution_failure->process_output->getErrorOutput());
            }
        );
    }
}
