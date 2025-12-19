<?php
/*
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

namespace Tuleap\Document\Tree;

use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Stubs\ProjectByIDFactoryStub;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class DocumentItemUrlBuilderTest extends \Tuleap\Test\PHPUnit\TestCase
{
    public function testGetUrlForRootFolder(): void
    {
        $project = ProjectTestBuilder::aProject()->withUnixName('myproject')->withId(123)->build();
        $item    = new \Docman_File(['item_id' => 2, 'parent_id' => 0, 'group_id' => $project->getID()]);

        $url_builder = new DocumentItemUrlBuilder(ProjectByIDFactoryStub::buildWith($project));

        self::assertSame('/plugins/document/myproject', $url_builder->getUrl($item));
    }

    public function testGetUrlRedirectsOnPreview(): void
    {
        $project = ProjectTestBuilder::aProject()->withUnixName('other-project')->withId(456)->build();
        $item    = new \Docman_File(['item_id' => 20, 'parent_id' => 10, 'group_id' => $project->getID()]);

        $url_builder = new DocumentItemUrlBuilder(ProjectByIDFactoryStub::buildWith($project));

        self::assertSame('/plugins/document/other-project/preview/20', $url_builder->getUrl($item));
    }

    public function testGetRedirectionForEmbeddedFile(): void
    {
        $project = ProjectTestBuilder::aProject()->withUnixName('my_embedded_project')->build();
        $item    = new \Docman_EmbeddedFile(['item_id' => 200, 'parent_id' => 100, 'group_id' => $project->getID()]);

        $url_builder = new DocumentItemUrlBuilder(ProjectByIDFactoryStub::buildWith($project));

        self::assertSame(
            '/plugins/document/my_embedded_project/folder/100/200',
            $url_builder->getRedirectionForEmbeddedFile($item)
        );
    }

    public function testGetRedirectionForFolder(): void
    {
        $project = ProjectTestBuilder::aProject()->withUnixName('my_folder_project')->build();
        $item    = new \Docman_Folder(['item_id' => 222, 'group_id' => $project->getID()]);

        $url_builder = new DocumentItemUrlBuilder(ProjectByIDFactoryStub::buildWith($project));

        self::assertSame(
            '/plugins/document/my_folder_project/folder/222',
            $url_builder->getRedirectionForFolder($item)
        );
    }
}
