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

declare(strict_types=1);

namespace Tuleap\CLI;

use Tuleap\ForgeConfigSandbox;
use Tuleap\Test\PHPUnit\TestCase;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class AssertRunnerTest extends TestCase
{
    use ForgeConfigSandbox;

    public function testDoNotThrowsIfCurrentUseIsTheExpectedOne(): void
    {
        \ForgeConfig::set('sys_http_user', 'donotexist');

        $this->expectException(\RuntimeException::class);
        AssertRunner::asHTTPUser()->assertProcessIsExecutedByExpectedUser();
    }

    public function testDoesNotThrowWhenExecutedByTheExpectedUse(): void
    {
        \ForgeConfig::set('sys_http_user', posix_getpwuid(posix_geteuid())['name']);

        AssertRunner::asHTTPUser()->assertProcessIsExecutedByExpectedUser();

        $this->expectNotToPerformAssertions();
    }
}
