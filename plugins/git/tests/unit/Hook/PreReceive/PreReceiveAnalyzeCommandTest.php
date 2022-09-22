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

use Symfony\Component\Console\Tester\CommandTester;

final class PreReceiveAnalyzeCommandTest extends \Tuleap\Test\PHPUnit\TestCase
{
    public function testDefaultResult(): void
    {
        $command        = new PreReceiveAnalyzeCommand();
        $command_tester = new CommandTester($command);

        $command_tester->execute([]);
        $text_table = $command_tester->getDisplay();
        $this->assertEquals('rejection_message: 0', $text_table);

        $command_tester->execute(['--format' => 'json'], ['capture_stderr_separately' => true]);
        $json_output = json_decode($command_tester->getDisplay(), true);
        $this->assertEqualsCanonicalizing(
            [
                'rejection_message'       => 0,
            ],
            $json_output
        );
    }

    public function testUnknownFormatIsRejected(): void
    {
        $command        = new PreReceiveAnalyzeCommand();
        $command_tester = new CommandTester($command);

        $command_tester->execute(['--format' => 'aaaaaaa']);
        $text_table = $command_tester->getDisplay();
        $this->assertStringContainsString('Unsupported format', $text_table);
    }
}
