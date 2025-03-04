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

namespace Tuleap\Docman\Log;

use Tuleap\Test\PHPUnit\TestCase;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class LogEntryPageTest extends TestCase
{
    public function testNoLog(): void
    {
        $page = LogEntryPage::noLog();

        self::assertEquals(0, $page->total);
        self::assertCount(0, $page->entries);
    }

    public function testPage(): void
    {
        $page = LogEntryPage::page(
            123,
            [LogEntryTestBuilder::buildWithDefaults()]
        );

        self::assertEquals(123, $page->total);
        self::assertCount(1, $page->entries);
    }
}
