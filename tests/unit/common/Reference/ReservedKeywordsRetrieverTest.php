<?php
/**
 * Copyright (c) Enalean, 2025 - Present. All Rights Reserved.
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

namespace Tuleap\Reference;

use PHPUnit\Framework\Attributes\TestWith;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Test\Stubs\EventDispatcherStub;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class ReservedKeywordsRetrieverTest extends TestCase
{
    #[TestWith([[
        'doc',
        'file',
        'wiki',
        'cvs',
        'svn',
        'news',
        'forum',
        'msg',
        'cc',
        'release',
        'tag',
        'thread',
        'im',
        'project',
        'folder',
        'plugin',
        'img',
        'commit',
        'rev',
        'revision',
        'patch',
        'proj',
        'dossier',
    ],
    ])]
    public function testItReturnsLegacyReservedKeywords(array $keywords): void
    {
        $event_manager = EventDispatcherStub::withIdentityCallback();

        $reserved_keywords = new ReservedKeywordsRetriever($event_manager)->loadReservedKeywords();

        self::assertEqualsCanonicalizing($keywords, $reserved_keywords);
    }

    public function testItIncludesKeywordsFromPlugins(): void
    {
        $event_manager = EventDispatcherStub::withCallback(
            static function (object $event): object {
                if ($event instanceof GetReservedKeywordsEvent) {
                    $event->addKeyword('foobar');
                }
                return $event;
            }
        );

        $reserved_keywords = new ReservedKeywordsRetriever($event_manager)->loadReservedKeywords();

        self::assertContains('foobar', $reserved_keywords);
    }
}
