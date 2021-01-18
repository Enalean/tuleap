<?php
/**
 * Copyright (c) Enalean, 2013-Present. All Rights Reserved.
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

namespace Tuleap\Mediawiki;

use MediaWikiPlugin;
use PHPUnit\Framework\TestCase;
use Tuleap\Layout\ServiceUrlCollector;
use Tuleap\Test\Builders\ProjectTestBuilder;

final class mediawikiPluginTest extends TestCase //phpcs:ignore
{
    public function testSetsItsServiceURL(): void
    {
        $mediawiki_plugin = new MediaWikiPlugin();

        $collector = new ServiceUrlCollector(ProjectTestBuilder::aProject()->withUnixName('foo')->build(), 'plugin_mediawiki');
        $mediawiki_plugin->serviceUrlCollector($collector);

        self::assertEquals('/plugins/mediawiki/wiki/foo', $collector->getUrl());
    }

    public function testDoesNotTouchURLOfOthersServices(): void
    {
        $mediawiki_plugin = new MediaWikiPlugin();

        $collector = new ServiceUrlCollector(ProjectTestBuilder::aProject()->withUnixName('bar')->build(), 'plugin_doingsomething');
        $mediawiki_plugin->serviceUrlCollector($collector);

        self::assertFalse($collector->hasUrl());
    }
}
