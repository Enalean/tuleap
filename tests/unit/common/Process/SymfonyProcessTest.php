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

use Tuleap\NeverThrow\Err;
use Tuleap\NeverThrow\Ok;
use Tuleap\Test\PHPUnit\TestCase;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class SymfonyProcessTest extends TestCase
{
    public function testCanRunSuccessfulProcess(): void
    {
        $process = new SymfonyProcess(new \Symfony\Component\Process\Process(['sh', '-c', 'echo -n "STDOUT" && echo -n "STDERR" 1>&2']));
        $success = $process->run();

        self::assertInstanceOf(Ok::class, $success);
        self::assertEquals('STDOUT', $process->getOutput());
        self::assertEquals('STDERR', $process->getErrorOutput());
    }

    public function testCanRunFailingProcess(): void
    {
        $process = new SymfonyProcess(new \Symfony\Component\Process\Process(['sh', '-c', 'echo -n "STDOUT" && echo -n "STDERR" 1>&2 && exit 1']));
        $success = $process->run();

        self::assertInstanceOf(Err::class, $success);
        self::assertEquals('STDOUT', $process->getOutput());
        self::assertEquals('STDERR', $process->getErrorOutput());
    }

    public function testTryingToGetOutputWithoutRunningTheProcessFirstFails(): void
    {
        $process = new SymfonyProcess(new \Symfony\Component\Process\Process(['sh']));

        $this->expectException(\LogicException::class);
        $process->getOutput();
    }

    public function testTryingToGetErrorOutputWithoutRunningTheProcessFirstFails(): void
    {
        $process = new SymfonyProcess(new \Symfony\Component\Process\Process(['sh']));

        $this->expectException(\LogicException::class);
        $process->getErrorOutput();
    }
}
