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

namespace Tuleap\Docman;

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Tuleap\Docman\DocumentDeletion\DocmanWikiDeletor;
use Tuleap\PHPWiki\WikiPage;

class DocmanWikiDeletorTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var \Docman_PermissionsManager
     */
    private $permissions_manager;

    /**
     * @var \Docman_ItemFactory
     */
    private $item_factory;

    /**
     * @var \EventManager
     */
    private $event_manager;

    /**
     * @var \Docman_ItemDao
     */
    private $item_dao;

    /**
     * @var DocmanWikiDeletor
     */
    private $wiki_deletor;
    /**
     * @var \Mockery\MockInterface|DocmanReferencedWikiPageRetriever
     */
    private $wiki_page_retriever;

    protected function setUp(): void
    {
        $this->permissions_manager = \Mockery::mock(\Docman_PermissionsManager::class);
        $this->item_factory        = \Mockery::mock(\Docman_ItemFactory::class);
        $this->event_manager       = \Mockery::mock(\EventManager::class);
        $this->item_dao            = \Mockery::mock(\Docman_ItemDao::class);
        $this->wiki_page_retriever = \Mockery::mock(DocmanReferencedWikiPageRetriever::class);

        $this->wiki_deletor = new DocmanWikiDeletor(
            $this->wiki_page_retriever,
            $this->permissions_manager,
            $this->item_factory,
            $this->item_dao,
            $this->event_manager
        );
    }

    protected function tearDown(): void
    {
        \Mockery::close();
    }

    public function testItThrowsAnExceptionIfUserCannotDeleteTheWiki(): void
    {
        $propagate_deletion_to_wiki_service = false;
        $wiki_to_delete                     = \Mockery::mock(\Docman_Wiki::class);
        $wiki_page                          = \Mockery::mock(WikiPage::class);

        $wiki_to_delete->shouldReceive("getId")->andReturns(169);
        $wiki_to_delete->shouldReceive("getTitle")->andReturns("My kinky wiki");

        $this->wiki_page_retriever
            ->shouldReceive("retrieveAssociatedWikiPage")
            ->with($wiki_to_delete)
            ->andReturns($wiki_page);

        $wiki_page->shouldReceive("isReferenced")->andReturns(true);
        $wiki_page->shouldReceive("getId")->andReturns(69);

        $this->permissions_manager->shouldReceive("userCanDelete")->andReturns(false);
        $this->item_factory->shouldReceive("delete")->never();

        $this->expectException(DeleteFailedException::class);

        $this->wiki_deletor->deleteWiki(
            $wiki_to_delete,
            \Mockery::mock(\PFUser::class),
            $propagate_deletion_to_wiki_service
        );
    }

    public function testItOnlyDeletesAWikiAndSendsWikiPageUpdatedEvent(): void
    {
        $propagate_deletion_to_wiki_service = false;
        $wiki_to_delete                     = \Mockery::mock(\Docman_Wiki::class);
        $wiki_page                          = \Mockery::mock(WikiPage::class);

        $wiki_to_delete->shouldReceive("getId")->andReturns(169);
        $wiki_to_delete->shouldReceive("getGroupId")->andReturns(104);
        $wiki_to_delete->shouldReceive("getPagename")->andReturns("My kinky wiki");

        $this->wiki_page_retriever
            ->shouldReceive("retrieveAssociatedWikiPage")
            ->with($wiki_to_delete)
            ->andReturns($wiki_page);

        $wiki_page->shouldReceive("isReferenced")->andReturns(true);
        $wiki_page->shouldReceive("getId")->andReturns(69);

        $this->permissions_manager->shouldReceive("userCanDelete")->andReturns(true);

        $this->item_factory->shouldReceive("delete")->with($wiki_to_delete);
        $this->item_factory->shouldReceive("getIdInWikiOfWikiPageItem");

        $this->item_dao->shouldReceive("isWikiPageReferenced");

        $this->event_manager->shouldReceive("processEvent")->with(
            "wiki_page_updated",
            \Mockery::any()
        );

        $this->wiki_deletor->deleteWiki(
            $wiki_to_delete,
            \Mockery::mock(\PFUser::class),
            $propagate_deletion_to_wiki_service
        );
    }

    public function testItDeletesAWikiAndItsReferencedWikiPage(): void
    {
        $propagate_deletion_to_wiki_service = true;
        $wiki_to_delete                     = \Mockery::mock(\Docman_Wiki::class);
        $wiki_page                          = \Mockery::mock(WikiPage::class);

        $wiki_to_delete->shouldReceive("getId")->andReturns(169);
        $wiki_to_delete->shouldReceive("getGroupId")->andReturns(104);
        $wiki_to_delete->shouldReceive("getPagename")->andReturns("My kinky wiki");

        $this->wiki_page_retriever
            ->shouldReceive("retrieveAssociatedWikiPage")
            ->with($wiki_to_delete)
            ->andReturns($wiki_page);

        $wiki_page->shouldReceive("isReferenced")->andReturns(true);
        $wiki_page->shouldReceive("getId")->andReturns(69);

        $this->permissions_manager->shouldReceive("userCanDelete")->andReturns(true);

        $this->item_factory->shouldReceive("delete")->with($wiki_to_delete);
        $this->item_factory->shouldReceive("deleteWikiPage")->with("My kinky wiki", 104)->andReturns(true);

        $this->wiki_deletor->deleteWiki(
            $wiki_to_delete,
            \Mockery::mock(\PFUser::class),
            $propagate_deletion_to_wiki_service
        );
    }

    public function testItThrowsExceptionWhenReferencedWikiPageHasNotBeenDeleted(): void
    {
        $propagate_deletion_to_wiki_service = true;
        $wiki_to_delete                     = \Mockery::mock(\Docman_Wiki::class);
        $wiki_page                          = \Mockery::mock(WikiPage::class);

        $wiki_to_delete->shouldReceive("getId")->andReturns(169);
        $wiki_to_delete->shouldReceive("getGroupId")->andReturns(104);
        $wiki_to_delete->shouldReceive("getPagename")->andReturns("My kinky wiki");

        $this->wiki_page_retriever
            ->shouldReceive("retrieveAssociatedWikiPage")
            ->with($wiki_to_delete)
            ->andReturns($wiki_page);

        $wiki_page->shouldReceive("isReferenced")->andReturns(true);
        $wiki_page->shouldReceive("getId")->andReturns(69);

        $this->permissions_manager->shouldReceive("userCanDelete")->andReturns(true);

        $this->item_factory->shouldReceive("delete")->with($wiki_to_delete);
        $this->item_factory->shouldReceive("deleteWikiPage")->with("My kinky wiki", 104)->andReturns(false);

        $this->expectException(DeleteFailedException::class);

        $this->wiki_deletor->deleteWiki(
            $wiki_to_delete,
            \Mockery::mock(\PFUser::class),
            $propagate_deletion_to_wiki_service
        );
    }

    public function testItDoesNotThrowsExceptionWhenTheReferencedWikiPageDoesNotExist(): void
    {
        $propagate_deletion_to_wiki_service = true;
        $wiki_to_delete                     = \Mockery::mock(\Docman_Wiki::class);

        $wiki_to_delete->shouldReceive("getId")->andReturns(169);
        $wiki_to_delete->shouldReceive("getGroupId")->andReturns(104);
        $wiki_to_delete->shouldReceive("getPagename")->andReturns("My kinky wiki");

        $this->wiki_page_retriever
            ->shouldReceive("retrieveAssociatedWikiPage")
            ->with($wiki_to_delete)
            ->andReturns(null);

        $this->permissions_manager->shouldReceive("userCanDelete")->andReturns(true);

        $this->item_factory->shouldReceive("delete")->with($wiki_to_delete);
        $this->item_factory->shouldReceive("deleteWikiPage")->never();

        $this->wiki_deletor->deleteWiki(
            $wiki_to_delete,
            \Mockery::mock(\PFUser::class),
            $propagate_deletion_to_wiki_service
        );
    }
}
