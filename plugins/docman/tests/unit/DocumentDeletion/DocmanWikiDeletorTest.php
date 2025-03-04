<?php
/**
 * Copyright (c) Enalean, 2019 - present. All Rights Reserved.
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

namespace Tuleap\Docman\DocumentDeletion;

use Docman_ItemDao;
use Docman_ItemFactory;
use Docman_PermissionsManager;
use Docman_Wiki;
use EventManager;
use PHPUnit\Framework\MockObject\MockObject;
use Tuleap\Docman\DeleteFailedException;
use Tuleap\Docman\DocmanReferencedWikiPageRetriever;
use Tuleap\PHPWiki\WikiPage;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class DocmanWikiDeletorTest extends TestCase
{
    private Docman_PermissionsManager&MockObject $permissions_manager;
    private Docman_ItemFactory&MockObject $item_factory;
    private EventManager&MockObject $event_manager;
    private Docman_ItemDao&MockObject $item_dao;
    private DocmanWikiDeletor $wiki_deletor;
    private DocmanReferencedWikiPageRetriever&MockObject $wiki_page_retriever;

    protected function setUp(): void
    {
        $this->permissions_manager = $this->createMock(Docman_PermissionsManager::class);
        $this->item_factory        = $this->createMock(Docman_ItemFactory::class);
        $this->event_manager       = $this->createMock(EventManager::class);
        $this->item_dao            = $this->createMock(Docman_ItemDao::class);
        $this->wiki_page_retriever = $this->createMock(DocmanReferencedWikiPageRetriever::class);

        $this->wiki_deletor = new DocmanWikiDeletor(
            $this->wiki_page_retriever,
            $this->permissions_manager,
            $this->item_factory,
            $this->item_dao,
            $this->event_manager
        );
    }

    public function testItThrowsAnExceptionIfUserCannotDeleteTheWiki(): void
    {
        $wiki_to_delete = $this->createMock(Docman_Wiki::class);
        $wiki_page      = $this->createMock(WikiPage::class);

        $wiki_to_delete->method('getId')->willReturn(169);
        $wiki_to_delete->method('getTitle')->willReturn('My kinky wiki');

        $this->wiki_page_retriever->method('retrieveAssociatedWikiPage')->with($wiki_to_delete)->willReturn($wiki_page);

        $wiki_page->method('isReferenced')->willReturn(true);
        $wiki_page->method('getId')->willReturn(69);

        $this->permissions_manager->method('userCanDelete')->willReturn(false);
        $this->item_factory->expects(self::never())->method('delete');

        self::expectException(DeleteFailedException::class);

        $this->wiki_deletor->deleteWiki(
            $wiki_to_delete,
            UserTestBuilder::buildWithDefaults(),
            false
        );
    }

    public function testItOnlyDeletesAWikiAndSendsWikiPageUpdatedEvent(): void
    {
        $wiki_to_delete = $this->createMock(Docman_Wiki::class);
        $wiki_page      = $this->createMock(WikiPage::class);

        $wiki_to_delete->method('getId')->willReturn(169);
        $wiki_to_delete->method('getGroupId')->willReturn(104);
        $wiki_to_delete->method('getPagename')->willReturn('My kinky wiki');

        $this->wiki_page_retriever->method('retrieveAssociatedWikiPage')->with($wiki_to_delete)->willReturn($wiki_page);

        $wiki_page->method('isReferenced')->willReturn(true);
        $wiki_page->method('getId')->willReturn(69);

        $this->permissions_manager->method('userCanDelete')->willReturn(true);

        $this->item_factory->expects(self::atLeastOnce())->method('delete')->with($wiki_to_delete);
        $this->item_factory->method('getIdInWikiOfWikiPageItem');

        $this->item_dao->method('isWikiPageReferenced');

        $this->event_manager->expects(self::atLeastOnce())->method('processEvent')->with('wiki_page_updated', self::anything());

        $this->wiki_deletor->deleteWiki(
            $wiki_to_delete,
            UserTestBuilder::buildWithDefaults(),
            false
        );
    }

    public function testItDeletesAWikiAndItsReferencedWikiPage(): void
    {
        $wiki_to_delete = $this->createMock(Docman_Wiki::class);
        $wiki_page      = $this->createMock(WikiPage::class);

        $wiki_to_delete->method('getId')->willReturn(169);
        $wiki_to_delete->method('getGroupId')->willReturn(104);
        $wiki_to_delete->method('getPagename')->willReturn('My kinky wiki');

        $this->wiki_page_retriever->method('retrieveAssociatedWikiPage')->with($wiki_to_delete)->willReturn($wiki_page);

        $wiki_page->method('isReferenced')->willReturn(true);
        $wiki_page->method('getId')->willReturn(69);

        $this->permissions_manager->method('userCanDelete')->willReturn(true);

        $this->item_factory->expects(self::atLeastOnce())->method('delete')->with($wiki_to_delete);
        $this->item_factory->expects(self::atLeastOnce())->method('deleteWikiPage')->with('My kinky wiki', 104)->willReturn(true);

        $this->wiki_deletor->deleteWiki(
            $wiki_to_delete,
            UserTestBuilder::buildWithDefaults(),
            true
        );
    }

    public function testItThrowsExceptionWhenReferencedWikiPageHasNotBeenDeleted(): void
    {
        $wiki_to_delete = $this->createMock(Docman_Wiki::class);
        $wiki_page      = $this->createMock(WikiPage::class);

        $wiki_to_delete->method('getId')->willReturn(169);
        $wiki_to_delete->method('getGroupId')->willReturn(104);
        $wiki_to_delete->method('getPagename')->willReturn('My kinky wiki');

        $this->wiki_page_retriever->method('retrieveAssociatedWikiPage')->with($wiki_to_delete)->willReturn($wiki_page);

        $wiki_page->method('isReferenced')->willReturn(true);
        $wiki_page->method('getId')->willReturn(69);

        $this->permissions_manager->method('userCanDelete')->willReturn(true);

        $this->item_factory->method('delete')->with($wiki_to_delete);
        $this->item_factory->method('deleteWikiPage')->with('My kinky wiki', 104)->willReturn(false);

        self::expectException(DeleteFailedException::class);

        $this->wiki_deletor->deleteWiki(
            $wiki_to_delete,
            UserTestBuilder::buildWithDefaults(),
            true
        );
    }

    public function testItDoesNotThrowsExceptionWhenTheReferencedWikiPageDoesNotExist(): void
    {
        $wiki_to_delete = $this->createMock(Docman_Wiki::class);

        $wiki_to_delete->method('getId')->willReturn(169);
        $wiki_to_delete->method('getGroupId')->willReturn(104);
        $wiki_to_delete->method('getPagename')->willReturn('My kinky wiki');

        $this->wiki_page_retriever->method('retrieveAssociatedWikiPage')->with($wiki_to_delete)->willReturn(null);

        $this->permissions_manager->method('userCanDelete')->willReturn(true);

        $this->item_factory->method('delete')->with($wiki_to_delete);
        $this->item_factory->expects(self::never())->method('deleteWikiPage');

        $this->wiki_deletor->deleteWiki(
            $wiki_to_delete,
            UserTestBuilder::buildWithDefaults(),
            true
        );
    }
}
