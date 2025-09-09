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

namespace Tuleap\Artidoc\Document;

use PHPUnit\Framework\MockObject\MockObject;
use Tuleap\Artidoc\Adapter\Document\ArtidocDocument;
use Tuleap\Artidoc\Domain\Document\ArtidocWithContext;
use Tuleap\Docman\ServiceDocman;
use Tuleap\Layout\BreadCrumbDropdown\BreadCrumb;
use Tuleap\Layout\BreadCrumbDropdown\EllipsisBreadCrumb;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class ArtidocBreadcrumbsProviderTest extends TestCase
{
    private const int PROJECT_ID = 101;

    private \PFUser $user;
    private \Project $project;
    private \Docman_PermissionsManager&MockObject $permissions_manager;
    private \Docman_ItemFactory&MockObject $item_factory;

    #[\Override]
    protected function setUp(): void
    {
        $this->user    = UserTestBuilder::buildWithDefaults();
        $this->project = ProjectTestBuilder::aProject()->build();

        $this->item_factory = $this->createMock(\Docman_ItemFactory::class);

        $this->permissions_manager = $this->createMock(\Docman_PermissionsManager::class);
        \Docman_PermissionsManager::setInstance(self::PROJECT_ID, $this->permissions_manager);
    }

    #[\Override]
    protected function tearDown(): void
    {
        \Docman_PermissionsManager::clearInstances();
    }

    public function testWhenDocumentIsUnderRoot(): void
    {
        $item = new ArtidocDocument(['item_id' => 2, 'parent_id' => 1, 'title' => 'Requirements']);

        $this->item_factory
            ->method('getItemFromDb')
            ->willReturnCallback(static fn (int $id) => match ($id) {
                1 => new \Docman_Folder(['item_id' => $id, 'parent_id' => 0, 'title' => 'Documents']),
            });

        $service = new ServiceDocman($this->project, []);

        $this->permissions_manager->method('userCanAdmin')->willReturn(false);

        $provider    = new ArtidocBreadcrumbsProvider($this->item_factory);
        $breadcrumbs = $provider
            ->getBreadcrumbs((new ArtidocWithContext($item))->withContext(ServiceDocman::class, $service), $this->user)
            ->getBreadcrumbs();

        self::assertCount(2, $breadcrumbs);
        self::assertInstanceOf(BreadCrumb::class, $breadcrumbs[0]);
        self::assertSame('Documents', $breadcrumbs[0]->getBreadCrumbPresenter()->item->label);
        self::assertSame('/plugins/document/testproject/', $breadcrumbs[0]->getBreadCrumbPresenter()->item->url);
        self::assertCount(0, $breadcrumbs[0]->getBreadCrumbPresenter()->sections);

        self::assertInstanceOf(BreadCrumb::class, $breadcrumbs[1]);
        self::assertSame('Requirements', $breadcrumbs[1]->getBreadCrumbPresenter()->item->label);
        self::assertSame('/artidoc/2/', $breadcrumbs[1]->getBreadCrumbPresenter()->item->url);
    }

    public function testDisplayLinkToAdministrationWhenUserCanAdmin(): void
    {
        $item = new ArtidocDocument(['item_id' => 2, 'parent_id' => 1, 'title' => 'Requirements']);

        $this->item_factory
            ->method('getItemFromDb')
            ->willReturnCallback(static fn (int $id) => match ($id) {
                1 => new \Docman_Folder(['item_id' => $id, 'parent_id' => 0, 'title' => 'Documents']),
            });

        $service = new ServiceDocman($this->project, []);

        $this->permissions_manager->method('userCanAdmin')->willReturn(true);

        $provider    = new ArtidocBreadcrumbsProvider($this->item_factory);
        $breadcrumbs = $provider
            ->getBreadcrumbs((new ArtidocWithContext($item))->withContext(ServiceDocman::class, $service), $this->user)
            ->getBreadcrumbs();

        self::assertCount(2, $breadcrumbs);
        self::assertInstanceOf(BreadCrumb::class, $breadcrumbs[0]);
        self::assertSame('Documents', $breadcrumbs[0]->getBreadCrumbPresenter()->item->label);
        self::assertSame('/plugins/document/testproject/', $breadcrumbs[0]->getBreadCrumbPresenter()->item->url);
        self::assertCount(1, $breadcrumbs[0]->getBreadCrumbPresenter()->sections);
        self::assertSame('Administration', $breadcrumbs[0]->getBreadCrumbPresenter()->sections[0]->links[0]->label);
        self::assertSame('/plugins/docman/?group_id=101&action=admin', $breadcrumbs[0]->getBreadCrumbPresenter()->sections[0]->links[0]->url);
    }

    public function testWhenDocumentIsUnderASubFolder(): void
    {
        $item = new ArtidocDocument(['item_id' => 3, 'parent_id' => 2, 'title' => 'Requirements']);

        $this->item_factory
            ->method('getItemFromDb')
            ->willReturnCallback(static fn (int $id) => match ($id) {
                1 => new \Docman_Folder(['item_id' => $id, 'parent_id' => 0, 'title' => 'Documents']),
                2 => new \Docman_Folder(['item_id' => $id, 'parent_id' => 1, 'title' => 'Folder A']),
            });

        $service = new ServiceDocman($this->project, []);

        $this->permissions_manager->method('userCanAdmin')->willReturn(false);

        $provider    = new ArtidocBreadcrumbsProvider($this->item_factory);
        $breadcrumbs = $provider
            ->getBreadcrumbs((new ArtidocWithContext($item))->withContext(ServiceDocman::class, $service), $this->user)
            ->getBreadcrumbs();

        self::assertCount(3, $breadcrumbs);

        self::assertInstanceOf(BreadCrumb::class, $breadcrumbs[0]);
        self::assertSame('Documents', $breadcrumbs[0]->getBreadCrumbPresenter()->item->label);
        self::assertSame('/plugins/document/testproject/', $breadcrumbs[0]->getBreadCrumbPresenter()->item->url);
        self::assertCount(0, $breadcrumbs[0]->getBreadCrumbPresenter()->sections);

        self::assertInstanceOf(BreadCrumb::class, $breadcrumbs[1]);
        self::assertSame('Folder A', $breadcrumbs[1]->getBreadCrumbPresenter()->item->label);
        self::assertSame('/plugins/document/testproject/folder/2', $breadcrumbs[1]->getBreadCrumbPresenter()->item->url);

        self::assertInstanceOf(BreadCrumb::class, $breadcrumbs[2]);
        self::assertSame('Requirements', $breadcrumbs[2]->getBreadCrumbPresenter()->item->label);
        self::assertSame('/artidoc/3/', $breadcrumbs[2]->getBreadCrumbPresenter()->item->url);
    }

    public function testWhenDocumentIsUnderTooManySubFolders(): void
    {
        $item = new ArtidocDocument(['item_id' => 8, 'parent_id' => 7, 'title' => 'Requirements']);

        $this->item_factory
            ->method('getItemFromDb')
            ->willReturnCallback(static fn (int $id) => match ($id) {
                1 => new \Docman_Folder(['item_id' => $id, 'parent_id' => 0, 'title' => 'Documents']),
                2 => new \Docman_Folder(['item_id' => $id, 'parent_id' => 1, 'title' => 'Folder A']),
                3 => new \Docman_Folder(['item_id' => $id, 'parent_id' => 2, 'title' => 'Folder B']),
                4 => new \Docman_Folder(['item_id' => $id, 'parent_id' => 3, 'title' => 'Folder C']),
                5 => new \Docman_Folder(['item_id' => $id, 'parent_id' => 4, 'title' => 'Folder D']),
                6 => new \Docman_Folder(['item_id' => $id, 'parent_id' => 5, 'title' => 'Folder E']),
                7 => new \Docman_Folder(['item_id' => $id, 'parent_id' => 6, 'title' => 'Folder F']),
            });

        $service = new ServiceDocman($this->project, []);

        $this->permissions_manager->method('userCanAdmin')->willReturn(false);

        $provider    = new ArtidocBreadcrumbsProvider($this->item_factory);
        $breadcrumbs = $provider
            ->getBreadcrumbs((new ArtidocWithContext($item))->withContext(ServiceDocman::class, $service), $this->user)
            ->getBreadcrumbs();

        self::assertCount(7, $breadcrumbs);

        self::assertInstanceOf(BreadCrumb::class, $breadcrumbs[0]);
        self::assertSame('Documents', $breadcrumbs[0]->getBreadCrumbPresenter()->item->label);
        self::assertSame('/plugins/document/testproject/', $breadcrumbs[0]->getBreadCrumbPresenter()->item->url);
        self::assertCount(0, $breadcrumbs[0]->getBreadCrumbPresenter()->sections);

        self::assertInstanceOf(EllipsisBreadCrumb::class, $breadcrumbs[1]);

        self::assertInstanceOf(BreadCrumb::class, $breadcrumbs[2]);
        self::assertSame('Folder C', $breadcrumbs[2]->getBreadCrumbPresenter()->item->label);
        self::assertSame('/plugins/document/testproject/folder/4', $breadcrumbs[2]->getBreadCrumbPresenter()->item->url);

        self::assertInstanceOf(BreadCrumb::class, $breadcrumbs[3]);
        self::assertSame('Folder D', $breadcrumbs[3]->getBreadCrumbPresenter()->item->label);
        self::assertSame('/plugins/document/testproject/folder/5', $breadcrumbs[3]->getBreadCrumbPresenter()->item->url);

        self::assertInstanceOf(BreadCrumb::class, $breadcrumbs[4]);
        self::assertSame('Folder E', $breadcrumbs[4]->getBreadCrumbPresenter()->item->label);
        self::assertSame('/plugins/document/testproject/folder/6', $breadcrumbs[4]->getBreadCrumbPresenter()->item->url);

        self::assertInstanceOf(BreadCrumb::class, $breadcrumbs[5]);
        self::assertSame('Folder F', $breadcrumbs[5]->getBreadCrumbPresenter()->item->label);
        self::assertSame('/plugins/document/testproject/folder/7', $breadcrumbs[5]->getBreadCrumbPresenter()->item->url);

        self::assertInstanceOf(BreadCrumb::class, $breadcrumbs[6]);
        self::assertSame('Requirements', $breadcrumbs[6]->getBreadCrumbPresenter()->item->label);
        self::assertSame('/artidoc/8/', $breadcrumbs[6]->getBreadCrumbPresenter()->item->url);
    }
}
