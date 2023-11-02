<?php
/**
 * Copyright (c) Enalean, 2023 - Present. All Rights Reserved.
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

namespace Tuleap\Document\Reference;

use Tuleap\Docman\Item\OpenItemHref;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Test\Stubs\EventDispatcherStub;
use Tuleap\Test\Stubs\ProjectByIDFactoryStub;

final class ReferenceURLBuilderTest extends TestCase
{
    public function testItReturnsBaseURLIfNotLinkedToDocman(): void
    {
        $base_url    = '/plugins/document/whatever';
        $docman_item = new \Docman_Item(null);

        $reference_url_builder = $this->buildDefaultURLBuilder();

        self::assertSame(
            $base_url,
            $reference_url_builder->buildURLForReference($docman_item, $base_url),
        );
    }

    public function testItReturnsBaseURLIfDocumentIsLink(): void
    {
        $base_url    = '/plugins/docman/?group_id=$group_id&action=show&id=$1';
        $docman_link = new \Docman_Link(null);

        $reference_url_builder = $this->buildDefaultURLBuilder();

        self::assertSame(
            $base_url,
            $reference_url_builder->buildURLForReference($docman_link, $base_url),
        );
    }

    public function testItDocumentPreviewURLIfDocumentIsEmpty(): void
    {
        $base_url     = '/plugins/docman/?group_id=$group_id&action=show&id=$1';
        $docman_empty = new \Docman_Empty(null);
        $docman_empty->setGroupId(101);

        $reference_url_builder = $this->buildDefaultURLBuilder();

        self::assertSame(
            '/plugins/document/project-test/preview/$1',
            $reference_url_builder->buildURLForReference($docman_empty, $base_url),
        );
    }

    public function testItReturnsBaseURLIfDocumentIsWiki(): void
    {
        $base_url    = '/plugins/docman/?group_id=$group_id&action=show&id=$1';
        $docman_wiki = new \Docman_Wiki(null);

        $reference_url_builder = $this->buildDefaultURLBuilder();

        self::assertSame(
            $base_url,
            $reference_url_builder->buildURLForReference($docman_wiki, $base_url),
        );
    }

    public function testItReturnsExternalURLIfItemIsFileAndExternalPluginReturnsAnHrefULR(): void
    {
        $base_url = '/plugins/docman/?group_id=$group_id&action=show&id=$1';

        $docman_file = new \Docman_File(null);
        $docman_file->setCurrentVersion(new \Docman_Version(null));

        $reference_url_builder = new ReferenceURLBuilder(
            EventDispatcherStub::withCallback(
                function (OpenItemHref $event): object {
                    $event->setHref('external_plugin_href');
                    return $event;
                }
            ),
            ProjectByIDFactoryStub::buildWith(ProjectTestBuilder::aProject()->withId(101)->withUnixName('project-test')->build()),
        );

        self::assertSame(
            'external_plugin_href',
            $reference_url_builder->buildURLForReference($docman_file, $base_url),
        );
    }

    public function testItReturnsBaseURLIfItemIsFileAndExternalPluginDoesNotReturnAnHrefURL(): void
    {
        $base_url = '/plugins/docman/?group_id=$group_id&action=show&id=$1';

        $docman_file = new \Docman_File(null);
        $docman_file->setCurrentVersion(new \Docman_Version(null));

        $reference_url_builder = $this->buildDefaultURLBuilder();

        self::assertSame(
            $base_url,
            $reference_url_builder->buildURLForReference($docman_file, $base_url),
        );
    }

    public function testItReturnsNewURLifItemIsAFolder(): void
    {
        $base_url      = '/plugins/docman/?group_id=$group_id&action=show&id=$1';
        $docman_folder = new \Docman_Folder(null);
        $docman_folder->setGroupId(101);

        $reference_url_builder = $this->buildDefaultURLBuilder();

        self::assertSame(
            '/plugins/document/project-test/folder/$1',
            $reference_url_builder->buildURLForReference($docman_folder, $base_url),
        );
    }

    public function testItReturnsNewURLifItemIsAnEmbedded(): void
    {
        $base_url        = '/plugins/docman/?group_id=$group_id&action=show&id=$1';
        $docman_embedded = new \Docman_EmbeddedFile(null);
        $docman_embedded->setParentId(2);
        $docman_embedded->setGroupId(101);

        $reference_url_builder = $this->buildDefaultURLBuilder();

        self::assertSame(
            '/plugins/document/project-test/folder/2/$1',
            $reference_url_builder->buildURLForReference($docman_embedded, $base_url),
        );
    }

    private function buildDefaultURLBuilder(): ReferenceURLBuilder
    {
        return new ReferenceURLBuilder(
            EventDispatcherStub::withCallback(
                function (object $event): object {
                    return $event;
                }
            ),
            ProjectByIDFactoryStub::buildWith(
                ProjectTestBuilder::aProject()->withId(101)->withUnixName('project-test')->build(),
            ),
        );
    }
}
