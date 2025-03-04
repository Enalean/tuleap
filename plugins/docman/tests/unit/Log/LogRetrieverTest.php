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

use Tuleap\Docman\Tests\Stub\StoredLogStub;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Test\Stubs\RetrieveUserByIdStub;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class LogRetrieverTest extends TestCase
{
    public function testPaginatedLog(): void
    {
        $item      = new \Docman_File(['item_id' => 101]);
        $retriever = new LogRetriever(
            StoredLogStub::buildForItem(
                $item,
                LogEntryTestBuilder::anEntry()->withType(LogEntry::EVENT_EDIT)->build(),
                LogEntryTestBuilder::anEntry()->withType(LogEntry::EVENT_ACCESS)->build(),
                LogEntryTestBuilder::anEntry()->withType(LogEntry::EVENT_ADD)->build(),
            ),
            RetrieveUserByIdStub::withUser(UserTestBuilder::buildSiteAdministrator()),
            new \Docman_MetadataListOfValuesElementFactory(),
        );

        $page = $retriever->getPaginatedLogForItem($item, 2, 0, true);

        self::assertEquals(3, $page->total);
        self::assertEquals(
            [LogEntry::EVENT_EDIT, LogEntry::EVENT_ACCESS],
            array_map(
                static fn (LogEntry $entry): int => $entry->type,
                $page->entries,
            ),
        );
    }

    public function testPaginatedLogWhenWeDontWantAccessLogs(): void
    {
        $item      = new \Docman_File(['item_id' => 101]);
        $retriever = new LogRetriever(
            StoredLogStub::buildForItem(
                $item,
                LogEntryTestBuilder::anEntry()->withType(LogEntry::EVENT_EDIT)->build(),
                LogEntryTestBuilder::anEntry()->withType(LogEntry::EVENT_ACCESS)->build(),
                LogEntryTestBuilder::anEntry()->withType(LogEntry::EVENT_ADD)->build(),
            ),
            RetrieveUserByIdStub::withUser(UserTestBuilder::buildSiteAdministrator()),
            new \Docman_MetadataListOfValuesElementFactory(),
        );

        $page = $retriever->getPaginatedLogForItem($item, 2, 0, false);

        self::assertEquals(3, $page->total);
        self::assertEquals(
            [LogEntry::EVENT_EDIT],
            array_map(
                static fn (LogEntry $entry): int => $entry->type,
                $page->entries,
            ),
        );
    }
}
