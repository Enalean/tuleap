<?php
/**
 * Copyright (c) Enalean, 2020 - Present. All Rights Reserved.
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

namespace Tuleap\Tracker\Creation;

use EventManager;
use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles;
use Tuleap\Test\PHPUnit\TestCase;

#[DisableReturnValueGenerationForTestDoubles]
final class DefaultTemplatesCollectionBuilderTest extends TestCase
{
    public function testBuild(): void
    {
        $event_manager = $this->createMock(EventManager::class);
        $event_manager->method('processEvent')
            ->willReturnCallback(static function (DefaultTemplatesXMLFileCollection $collection) {
                $plugin_path = vfsStream::setup()->url() . '/file.xml';
                file_put_contents($plugin_path, <<<EOS
                    <tracker>
                          <name>Releases</name>
                          <item_name>release</item_name>
                          <description>Release tracker defined in a plugin</description>
                          <color>acid-green</color>
                    </tracker>
                    EOS
                );
                $collection->add($plugin_path);
            });

        $collection = (new DefaultTemplatesCollectionBuilder($event_manager))->build();

        self::assertCount(2, $collection->getSortedDefaultTemplatesRepresentations());
        self::assertTrue($collection->has('default-bug'));
        self::assertTrue($collection->has('default-release'));
    }

    public function testItIgnoresXMLFilesThatDoNotContainATrackerName(): void
    {
        $event_manager = $this->createMock(EventManager::class);
        $event_manager->method('processEvent')
            ->willReturnCallback(static function (DefaultTemplatesXMLFileCollection $collection) {
                $plugin_path = vfsStream::setup()->url() . '/file.xml';
                file_put_contents($plugin_path, <<<EOS
                    <tracker>
                          <item_name>release</item_name>
                          <description>Release tracker defined in a plugin</description>
                          <color>acid-green</color>
                    </tracker>
                    EOS
                );
                $collection->add($plugin_path);
            });

        $collection = (new DefaultTemplatesCollectionBuilder($event_manager))->build();

        self::assertCount(1, $collection->getSortedDefaultTemplatesRepresentations());
        self::assertTrue($collection->has('default-bug'));
    }

    public function testItIgnoresXMLFilesThatDoNotContainATrackerItemName(): void
    {
        $event_manager = $this->createMock(EventManager::class);
        $event_manager->method('processEvent')
            ->willReturnCallback(static function (DefaultTemplatesXMLFileCollection $collection) {
                $plugin_path = vfsStream::setup()->url() . '/file.xml';
                file_put_contents($plugin_path, <<<EOS
                    <tracker>
                          <name>Releases</name>
                          <description>Release tracker defined in a plugin</description>
                          <color>acid-green</color>
                    </tracker>
                    EOS
                );
                $collection->add($plugin_path);
            });

        $collection = (new DefaultTemplatesCollectionBuilder($event_manager))->build();

        self::assertCount(1, $collection->getSortedDefaultTemplatesRepresentations());
        self::assertTrue($collection->has('default-bug'));
    }

    public function testItIgnoresXMLFilesThatDoNotContainATrackerColor(): void
    {
        $event_manager = $this->createMock(EventManager::class);
        $event_manager->method('processEvent')
            ->willReturnCallback(static function (DefaultTemplatesXMLFileCollection $collection) {
                $plugin_path = vfsStream::setup()->url() . '/file.xml';
                file_put_contents($plugin_path, <<<EOS
                    <tracker>
                          <name>Releases</name>
                          <item_name>release</item_name>
                          <description>Release tracker defined in a plugin</description>
                    </tracker>
                    EOS
                );
                $collection->add($plugin_path);
            });

        $collection = (new DefaultTemplatesCollectionBuilder($event_manager))->build();

        self::assertCount(1, $collection->getSortedDefaultTemplatesRepresentations());
        self::assertTrue($collection->has('default-bug'));
    }
}
