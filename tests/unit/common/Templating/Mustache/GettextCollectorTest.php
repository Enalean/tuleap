<?php
/**
 * Copyright (c) Enalean, 2017-Present. All Rights Reserved.
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

namespace Tuleap\Templating\Mustache;

use Tuleap\Language\Gettext\POTEntry;

final class GettextCollectorTest extends \Tuleap\Test\PHPUnit\TestCase
{
    public function testItCollectsGettext(): void
    {
        $collector = new GettextCollector(new GettextSectionContentTransformer());
        $entries   = $this->createMock(\Tuleap\Language\Gettext\POTEntryCollection::class);

        $entries->expects(self::once())->method('add')->with(
            'tuleap-core',
            self::callback(
                function (POTEntry $entry): bool {
                    return $entry->getMsgid() === 'whatever';
                }
            ),
        );

        $collector->collectEntry('gettext', 'whatever', $entries);
    }

    public function testItCollectsNgettext(): void
    {
        $collector = new GettextCollector(new GettextSectionContentTransformer());
        $entries   = $this->createMock(\Tuleap\Language\Gettext\POTEntryCollection::class);

        $entries->expects(self::once())->method('add')->with(
            'tuleap-core',
            self::callback(
                function (POTEntry $entry): bool {
                    return $entry->getMsgid() === 'singular' &&
                        $entry->getMsgidPlural() === 'plural';
                }
            ),
        );

        $collector->collectEntry('ngettext', 'singular | plural', $entries);
    }

    public function testItCollectsDgettext(): void
    {
        $collector = new GettextCollector(new GettextSectionContentTransformer());
        $entries   = $this->createMock(\Tuleap\Language\Gettext\POTEntryCollection::class);

        $entries->expects(self::once())->method('add')->with(
            'mydomain',
            self::callback(
                function (POTEntry $entry): bool {
                    return $entry->getMsgid() === 'whatever';
                }
            ),
        );

        $collector->collectEntry('dgettext', 'mydomain | whatever', $entries);
    }

    public function testItCollectsDngettext(): void
    {
        $collector = new GettextCollector(new GettextSectionContentTransformer());
        $entries   = $this->createMock(\Tuleap\Language\Gettext\POTEntryCollection::class);

        $entries->expects(self::once())->method('add')->with(
            'mydomain',
            self::callback(
                function (POTEntry $entry): bool {
                    return $entry->getMsgid() === 'singular' &&
                        $entry->getMsgidPlural() === 'plural';
                }
            ),
        );

        $collector->collectEntry('dngettext', 'mydomain | singular | plural', $entries);
    }

    public function testItRaisesAnExceptionIfSectionNameIsUnknown(): void
    {
        $collector = new GettextCollector(new GettextSectionContentTransformer());
        $entries   = $this->createMock(\Tuleap\Language\Gettext\POTEntryCollection::class);

        $this->expectException(\RuntimeException::class);

        $collector->collectEntry('not-gettext', 'whatever', $entries);
    }
}
