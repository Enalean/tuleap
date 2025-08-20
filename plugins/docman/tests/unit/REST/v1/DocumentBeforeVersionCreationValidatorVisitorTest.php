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

namespace Tuleap\Docman\REST\v1;

use Docman_EmbeddedFile;
use Docman_Empty;
use Docman_File;
use Docman_Folder;
use Docman_Item;
use Docman_ItemFactory;
use Docman_Link;
use Docman_PermissionsManager;
use Docman_Wiki;
use Luracast\Restler\RestException;
use PHPUnit\Framework\MockObject\MockObject;
use Service;
use Tuleap\Docman\ApprovalTable\ApprovalTableException;
use Tuleap\Docman\ApprovalTable\ApprovalTableRetriever;
use Tuleap\Docman\ApprovalTable\ApprovalTableUpdateActionChecker;
use Tuleap\Docman\ApprovalTable\ApprovalTableUpdater;
use Tuleap\REST\I18NRestException;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class DocumentBeforeVersionCreationValidatorVisitorTest extends TestCase
{
    private ApprovalTableRetriever&MockObject $approval_retriever;
    private Docman_ItemFactory&MockObject $docamn_factory;
    private ApprovalTableUpdateActionChecker&MockObject $approval_checker;
    private Docman_PermissionsManager&MockObject $permission_manager;
    private DocumentBeforeVersionCreationValidatorVisitor $validator_visitor;

    protected function setUp(): void
    {
        $this->approval_checker   = $this->createMock(ApprovalTableUpdateActionChecker::class);
        $this->approval_retriever = $this->createMock(ApprovalTableRetriever::class);
        $this->permission_manager = $this->createMock(Docman_PermissionsManager::class);
        $this->docamn_factory     = $this->createMock(Docman_ItemFactory::class);
        $this->validator_visitor  = new DocumentBeforeVersionCreationValidatorVisitor(
            $this->permission_manager,
            $this->approval_checker,
            $this->docamn_factory,
            $this->approval_retriever
        );
    }

    public function testItThrowsErrorWhenExpectingAFileAndGivenItemIsLink(): void
    {
        $link_item = new Docman_Link();

        $this->expectException(I18NRestException::class);

        $params = [
            'approval_table_action' => ApprovalTableUpdater::APPROVAL_TABLE_UPDATE_COPY,
            'user'                  => UserTestBuilder::buildWithDefaults(),
            'document_type'         => Docman_File::class,
            'item'                  => new Docman_File(),
            'title'                 => 'my document title',
        ];
        $link_item->accept($this->validator_visitor, $params);
    }

    public function testItThrowsErrorWhenExpectingAFileAndGivenItemIsEmbeddedFile(): void
    {
        $embedded_file_item = new Docman_EmbeddedFile();

        $this->expectException(I18NRestException::class);

        $params = [
            'approval_table_action' => ApprovalTableUpdater::APPROVAL_TABLE_UPDATE_COPY,
            'user'                  => UserTestBuilder::buildWithDefaults(),
            'document_type'         => Docman_File::class,
            'item'                  => new Docman_File(),
            'title'                 => 'my document title',
        ];
        $embedded_file_item->accept($this->validator_visitor, $params);
    }

    public function testItThrowsErrorWhenExpectingAFileAndGivenItemIsEmptyDocument(): void
    {
        $empty_item = new Docman_Empty();

        $this->expectException(I18NRestException::class);

        $params = [
            'approval_table_action' => ApprovalTableUpdater::APPROVAL_TABLE_UPDATE_COPY,
            'user'                  => UserTestBuilder::buildWithDefaults(),
            'document_type'         => Docman_File::class,
            'item'                  => new Docman_File(),
            'title'                 => 'my document title',
        ];
        $empty_item->accept($this->validator_visitor, $params);
    }

    public function testItThrowsErrorWhenExpectingAFileAndGivenItemIsWiki(): void
    {
        $wiki_item = new Docman_Wiki();

        $this->expectException(I18NRestException::class);

        $params = [
            'approval_table_action' => ApprovalTableUpdater::APPROVAL_TABLE_UPDATE_COPY,
            'user'                  => UserTestBuilder::buildWithDefaults(),
            'document_type'         => Docman_File::class,
            'item'                  => new Docman_File(),
            'title'                 => 'my document title',
        ];
        $wiki_item->accept($this->validator_visitor, $params);
    }

    public function testItThrowsErrorWhenExpectingAFileAndGivenItemIsFolder(): void
    {
        $folder_item = new Docman_Folder();

        $this->expectException(I18NRestException::class);

        $params = [
            'approval_table_action' => ApprovalTableUpdater::APPROVAL_TABLE_UPDATE_COPY,
            'user'                  => UserTestBuilder::buildWithDefaults(),
            'document_type'         => Docman_File::class,
            'item'                  => new Docman_File(),
            'title'                 => 'my document title',
        ];
        $folder_item->accept($this->validator_visitor, $params);
    }

    public function testItThrowsErrorWhenExpectingAFileAndGivenItemIsGeneric(): void
    {
        $item = new Docman_Item();

        $this->expectException(I18NRestException::class);

        $params = [
            'approval_table_action' => ApprovalTableUpdater::APPROVAL_TABLE_UPDATE_COPY,
            'user'                  => UserTestBuilder::buildWithDefaults(),
            'document_type'         => Docman_File::class,
            'item'                  => new Docman_File(),
            'title'                 => 'my document title',
        ];
        $item->accept($this->validator_visitor, $params);
    }

    public function testItThrowExceptionWhenUserCantWriteFile(): void
    {
        $file_item = new Docman_File();

        $this->permission_manager->method('userCanWrite')->willReturn(false);
        $this->docamn_factory->method('doesTitleCorrespondToExistingDocument')->willReturn(false);
        $this->expectException(I18NRestException::class);

        $params = [
            'approval_table_action' => ApprovalTableUpdater::APPROVAL_TABLE_UPDATE_COPY,
            'user'                  => UserTestBuilder::buildWithDefaults(),
            'document_type'         => Docman_File::class,
            'item'                  => new Docman_File(),
            'title'                 => 'my document title',
        ];
        $file_item->accept($this->validator_visitor, $params);
    }

    public function testItThrowExceptionWhenApprovalTableOptionsAreIncorrect(): void
    {
        $file_item = new Docman_File();

        $this->permission_manager->method('userCanWrite')->willReturn(true);
        $this->docamn_factory->method('doesTitleCorrespondToExistingDocument')->willReturn(false);
        $this->approval_checker->method('checkApprovalTableForItem')->willThrowException(
            ApprovalTableException::approvalTableActionIsMandatory('item title')
        );
        $this->permission_manager->method('_itemIsLockedForUser')->willReturn(false);

        $this->expectException(ApprovalTableException::class);

        $params = [
            'approval_table_action' => ApprovalTableUpdater::APPROVAL_TABLE_UPDATE_COPY,
            'user'                  => UserTestBuilder::buildWithDefaults(),
            'document_type'         => Docman_File::class,
            'item'                  => new Docman_File(),
            'title'                 => 'my document title',
        ];
        $file_item->accept($this->validator_visitor, $params);
    }

    public function testItThrowExceptionWhenItemNameAlreadyExistsInParent(): void
    {
        $file_item = new Docman_File();

        $this->permission_manager->method('userCanWrite')->willReturn(true);
        $this->docamn_factory->method('doesTitleCorrespondToExistingDocument')->willReturn(true);
        $this->approval_checker->method('checkApprovalTableForItem')->willThrowException(
            ApprovalTableException::approvalTableActionIsMandatory('item title')
        );
        $this->permission_manager->method('_itemIsLockedForUser')->willReturn(false);

        $this->expectException(RestException::class);

        $params = [
            'approval_table_action' => ApprovalTableUpdater::APPROVAL_TABLE_UPDATE_COPY,
            'user'                  => UserTestBuilder::buildWithDefaults(),
            'document_type'         => Docman_File::class,
            'item'                  => new Docman_File(),
            'title'                 => 'my document title',
        ];
        $file_item->accept($this->validator_visitor, $params);
    }

    public function testItThrowExceptionWhenFolderNameAlreadyExistsInParent(): void
    {
        $file_item = new Docman_Folder();

        $this->permission_manager->method('userCanWrite')->willReturn(true);
        $this->docamn_factory->method('doesTitleCorrespondToExistingFolder')->willReturn(true);
        $this->approval_checker->method('checkApprovalTableForItem')->willThrowException(
            ApprovalTableException::approvalTableActionIsMandatory('item title')
        );
        $this->permission_manager->method('_itemIsLockedForUser')->willReturn(false);

        $this->expectException(RestException::class);

        $params = [
            'approval_table_action' => ApprovalTableUpdater::APPROVAL_TABLE_UPDATE_COPY,
            'user'                  => UserTestBuilder::buildWithDefaults(),
            'document_type'         => Docman_Folder::class,
            'item'                  => new Docman_Folder(),
            'title'                 => 'my document title',
        ];
        $file_item->accept($this->validator_visitor, $params);
    }

    public function testItThrowExceptionWhenItemIsAlreadyLocked(): void
    {
        $file_item = new Docman_File();

        $this->permission_manager->method('userCanWrite')->willReturn(true);
        $this->docamn_factory->method('doesTitleCorrespondToExistingDocument')->willReturn(false);
        $this->approval_checker->method('checkApprovalTableForItem');
        $this->permission_manager->method('_itemIsLockedForUser')->willReturn(true);

        $this->expectException(I18NRestException::class);

        $params = [
            'approval_table_action' => ApprovalTableUpdater::APPROVAL_TABLE_UPDATE_COPY,
            'user'                  => UserTestBuilder::buildWithDefaults(),
            'document_type'         => Docman_File::class,
            'item'                  => new Docman_File(),
            'title'                 => 'my document title',
        ];
        $file_item->accept($this->validator_visitor, $params);
    }

    public function testVisitorDoesNotReturnExceptionIsEverythingIsAlright(): void
    {
        self::expectNotToPerformAssertions();

        $file_item = new Docman_File();

        $this->permission_manager->method('userCanWrite')->willReturn(true);
        $this->docamn_factory->method('doesTitleCorrespondToExistingDocument')->willReturn(false);
        $this->approval_checker->method('checkApprovalTableForItem');
        $this->permission_manager->method('_itemIsLockedForUser')->willReturn(false);

        $params = [
            'approval_table_action' => ApprovalTableUpdater::APPROVAL_TABLE_UPDATE_COPY,
            'user'                  => UserTestBuilder::buildWithDefaults(),
            'document_type'         => Docman_File::class,
            'item'                  => new Docman_File(),
            'title'                 => 'my document title',
        ];
        $file_item->accept($this->validator_visitor, $params);
    }

    public function testItThrowExceptionWhenProjectDoesNotUseWiki(): void
    {
        $file_item = new Docman_Wiki();

        $this->permission_manager->method('userCanWrite')->willReturn(true);
        $this->docamn_factory->method('doesTitleCorrespondToExistingDocument')->willReturn(false);
        $this->approval_checker->method('checkApprovalTableForItem');
        $this->permission_manager->method('_itemIsLockedForUser')->willReturn(false);

        $project = ProjectTestBuilder::aProject()->withUnixName('my project')->withoutServices()->build();
        $params  = [
            'user'          => UserTestBuilder::buildWithDefaults(),
            'document_type' => Docman_Wiki::class,
            'item'          => new Docman_Wiki(),
            'title'         => 'my document title',
            'project'       => $project,
        ];

        $this->expectException(RestException::class);
        $file_item->accept($this->validator_visitor, $params);
    }

    public function testItThrowExceptionWhenWikiHasAnApprovalTable(): void
    {
        $file_item = new Docman_Wiki();

        $this->permission_manager->method('userCanWrite')->willReturn(true);
        $this->docamn_factory->method('doesTitleCorrespondToExistingDocument')->willReturn(false);
        $this->approval_checker->method('checkApprovalTableForItem');
        $this->permission_manager->method('_itemIsLockedForUser')->willReturn(false);
        $this->approval_retriever->method('hasApprovalTable')->willReturn(true);

        $project = ProjectTestBuilder::aProject()->withUsedService(Service::WIKI)->build();
        $params  = [
            'approval_table_action' => ApprovalTableUpdater::APPROVAL_TABLE_UPDATE_COPY,
            'user'                  => UserTestBuilder::buildWithDefaults(),
            'document_type'         => Docman_Wiki::class,
            'item'                  => new Docman_Wiki(),
            'title'                 => 'my document title',
            'project'               => $project,
        ];

        $this->expectException(I18NRestException::class);
        $file_item->accept($this->validator_visitor, $params);
    }
}
