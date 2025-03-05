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

use Tuleap\Test\PHPUnit\TestCase;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class MediaWikiInstallAndUpdateHandlerExceptionTest extends TestCase
{
    public function testBuildsMessageFromCommandFailures(): void
    {
        $failures = [
            new MediaWikiManagementCommandFailure(1, 'command 1', 'Fail 1'),
            new MediaWikiManagementCommandFailure(2, 'command 2', 'Fail 2'),
        ];

        $exception = MediaWikiInstallAndUpdateHandlerException::fromCommandFailures($failures);

        $expected_message = <<<EOF
        Could not execute MW install and update scripts:
        Exit code: 1
        Process command line: command 1
        Process output: Fail 1
        ------
        Exit code: 2
        Process command line: command 2
        Process output: Fail 2
        EOF;

        self::assertEquals($expected_message, $exception->getMessage());
    }
}
