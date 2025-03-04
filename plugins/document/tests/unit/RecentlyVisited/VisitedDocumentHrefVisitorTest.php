<?php
/**
 * Copyright (c) Enalean, 2024 - Present. All Rights Reserved.
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

namespace Tuleap\Document\Tests\RecentlyVisited;

use Docman_Version;
use Tuleap\Docman\Item\OpenItemHref;
use Tuleap\Docman\Item\OtherDocument;
use Tuleap\Document\RecentlyVisited\VisitedDocumentHrefVisitor;
use Tuleap\Document\RecentlyVisited\VisitedOtherDocumentHref;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Test\Stubs\EventDispatcherStub;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class VisitedDocumentHrefVisitorTest extends TestCase
{
    public function testFolderHrefIsThePreview(): void
    {
        $project = ProjectTestBuilder::aProject()->build();

        $item = new \Docman_Folder(['item_id' => 123]);

        $visitor = new VisitedDocumentHrefVisitor(
            new \Docman_VersionFactory(),
            EventDispatcherStub::withIdentityCallback()
        );

        self::assertEquals(
            '/plugins/document/TestProject/preview/123',
            $item->accept($visitor, ['project' => $project]),
        );
    }

    public function testEmptyHrefIsThePreview(): void
    {
        $project = ProjectTestBuilder::aProject()->build();

        $item = new \Docman_Empty(['item_id' => 123]);

        $visitor = new VisitedDocumentHrefVisitor(
            new \Docman_VersionFactory(),
            EventDispatcherStub::withIdentityCallback()
        );

        self::assertEquals(
            '/plugins/document/TestProject/preview/123',
            $item->accept($visitor, ['project' => $project]),
        );
    }

    public function testLinkHrefIsTheShowActionOfTheItem(): void
    {
        $project = ProjectTestBuilder::aProject()->build();

        $item = new \Docman_Link(['item_id' => 123, 'link_url' => 'http://example.com']);

        $visitor = new VisitedDocumentHrefVisitor(
            new \Docman_VersionFactory(),
            EventDispatcherStub::withIdentityCallback()
        );

        self::assertEquals(
            '/plugins/docman/?action=show&id=123',
            $item->accept($visitor, ['project' => $project]),
        );
    }

    public function testEmbeddedHrefIsThePreview(): void
    {
        $project = ProjectTestBuilder::aProject()->build();

        $item = new \Docman_EmbeddedFile(['item_id' => 123]);

        $visitor = new VisitedDocumentHrefVisitor(
            new \Docman_VersionFactory(),
            EventDispatcherStub::withIdentityCallback()
        );

        self::assertEquals(
            '/plugins/document/TestProject/preview/123',
            $item->accept($visitor, ['project' => $project]),
        );
    }

    public function testWikiHrefIsThePreview(): void
    {
        $project = ProjectTestBuilder::aProject()->build();

        $item = new \Docman_Wiki(['item_id' => 123, 'wiki_page' => 'MyWikiPage']);

        $visitor = new VisitedDocumentHrefVisitor(
            new \Docman_VersionFactory(),
            EventDispatcherStub::withIdentityCallback()
        );

        self::assertEquals(
            '/plugins/document/TestProject/preview/123',
            $item->accept($visitor, ['project' => $project]),
        );
    }

    public function testFileHrefIsTheDownloadHrefWhenNoCurrentVersion(): void
    {
        $project = ProjectTestBuilder::aProject()->build();

        $item = new \Docman_File(['item_id' => 123]);

        $docman_version_factory = $this->createMock(\Docman_VersionFactory::class);
        $docman_version_factory->method('getCurrentVersionForItem')->willReturn(null);

        $visitor = new VisitedDocumentHrefVisitor(
            $docman_version_factory,
            EventDispatcherStub::withIdentityCallback()
        );

        self::assertEquals(
            '/plugins/docman/download/123',
            $item->accept($visitor, ['project' => $project]),
        );
    }

    public function testFileHrefIsTheDownloadHrefWhenCurrentVersionButHookIsNotListenedTo(): void
    {
        $project = ProjectTestBuilder::aProject()->build();

        $item = new \Docman_File(['item_id' => 123]);

        $docman_version_factory = $this->createMock(\Docman_VersionFactory::class);
        $docman_version_factory->method('getCurrentVersionForItem')->willReturn(new Docman_Version());

        $visitor = new VisitedDocumentHrefVisitor(
            $docman_version_factory,
            EventDispatcherStub::withIdentityCallback()
        );

        self::assertEquals(
            '/plugins/docman/download/123',
            $item->accept($visitor, ['project' => $project]),
        );
    }

    public function testFileHrefIsTheOneProvidedByTheHook(): void
    {
        $project = ProjectTestBuilder::aProject()->build();

        $item = new \Docman_File(['item_id' => 123]);

        $docman_version_factory = $this->createMock(\Docman_VersionFactory::class);
        $docman_version_factory->method('getCurrentVersionForItem')->willReturn(new Docman_Version());

        $visitor = new VisitedDocumentHrefVisitor(
            $docman_version_factory,
            EventDispatcherStub::withCallback(static function (object $event): object {
                if ($event instanceof OpenItemHref) {
                    $event->setHref('/custom/href/123');
                }
                return $event;
            })
        );

        self::assertEquals(
            '/custom/href/123',
            $item->accept($visitor, ['project' => $project]),
        );
    }

    public function testOtherDocumentHrefIsThePreviewWhenHookIsNotListenedTo(): void
    {
        $project = ProjectTestBuilder::aProject()->build();

        $item = new class (['item_id' => 123]) extends OtherDocument {
        };

        $visitor = new VisitedDocumentHrefVisitor(
            new \Docman_VersionFactory(),
            EventDispatcherStub::withIdentityCallback()
        );

        self::assertEquals(
            '/plugins/document/TestProject/preview/123',
            $item->accept($visitor, ['project' => $project]),
        );
    }

    public function testOtherDocumentHrefIsTheOneProvidedByTheHook(): void
    {
        $project = ProjectTestBuilder::aProject()->build();

        $item = new class (['item_id' => 123]) extends OtherDocument {
        };

        $visitor = new VisitedDocumentHrefVisitor(
            new \Docman_VersionFactory(),
            EventDispatcherStub::withCallback(static function (object $event): object {
                if ($event instanceof VisitedOtherDocumentHref) {
                    $event->setHref('/custom/href/123');
                }
                return $event;
            })
        );

        self::assertEquals(
            '/custom/href/123',
            $item->accept($visitor, ['project' => $project]),
        );
    }
}
