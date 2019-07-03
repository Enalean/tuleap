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

declare(strict_types = 1);

namespace Tuleap\Docman\REST\v1;

use Docman_EmbeddedFile;
use Docman_Empty;
use Docman_File;
use Docman_Folder;
use Docman_Item;
use Docman_Link;
use Docman_PermissionsManager;
use Docman_Wiki;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PFUser;
use PHPUnit\Framework\TestCase;
use Tuleap\Docman\ApprovalTable\ApprovalTableException;
use Tuleap\Docman\ApprovalTable\ApprovalTableUpdateActionChecker;
use Tuleap\Docman\ApprovalTable\ApprovalTableUpdater;

class DocumentBeforeVersionCreationValidatorVisitorTest extends TestCase
{
    use MockeryPHPUnitIntegration;
    /**
     * @var \Mockery\MockInterface|ApprovalTableUpdateActionChecker
     */
    private $approval_checker;
    /**
     * @var Docman_Item|\Mockery\MockInterface
     */
    private $item;
    /**
     * @var \Mockery\MockInterface|PFUser
     */
    private $current_user;
    /**
     * @var Docman_PermissionsManager|\Mockery\MockInterface
     */
    private $permission_manager;
    /**
     * @var DocumentBeforeModificationValidatorVisitor
     */
    private $validator_visitor;

    public function setUp(): void
    {
        parent::setUp();

        $this->approval_checker   = Mockery::mock(ApprovalTableUpdateActionChecker::class);
        $this->permission_manager = Mockery::mock(Docman_PermissionsManager::class);
        $this->current_user       = Mockery::mock(PFUser::class);
        $this->item               = Mockery::mock(Docman_Item::class);
        $this->item->shouldReceive('getId')->andReturn(1);
        $this->validator_visitor = new  DocumentBeforeVersionCreationValidatorVisitor(
            $this->permission_manager,
            $this->current_user,
            $this->item,
            Docman_File::class,
            $this->approval_checker
        );
    }

    public function testItThrowsErrorWhenExpectingAFileAndGivenItemIsLink()
    {
        $link_item = new Docman_Link();

        $this->expectException(\Tuleap\REST\I18NRestException::class);

        $params = [
            'approval_table_action' => ApprovalTableUpdater::APPROVAL_TABLE_UPDATE_COPY,
            'user'                  => Mockery::mock(PFUser::class)
        ];
        $link_item->accept($this->validator_visitor, $params);
    }

    public function testItThrowsErrorWhenExpectingAFileAndGivenItemIsEmbeddedFile()
    {
        $embedded_file_item = new Docman_EmbeddedFile();

        $this->expectException(\Tuleap\REST\I18NRestException::class);

        $params = [
            'approval_table_action' => ApprovalTableUpdater::APPROVAL_TABLE_UPDATE_COPY,
            'user'                  => Mockery::mock(PFUser::class)
        ];
        $embedded_file_item->accept($this->validator_visitor, $params);
    }

    public function testItThrowsErrorWhenExpectingAFileAndGivenItemIsEmptyDocument()
    {
        $empty_item = new Docman_Empty();

        $this->expectException(\Tuleap\REST\I18NRestException::class);

        $params = [
            'approval_table_action' => ApprovalTableUpdater::APPROVAL_TABLE_UPDATE_COPY,
            'user'                  => Mockery::mock(PFUser::class)
        ];
        $empty_item->accept($this->validator_visitor, $params);
    }

    public function testItThrowsErrorWhenExpectingAFileAndGivenItemIsWiki()
    {
        $wiki_item = new Docman_Wiki();

        $this->expectException(\Tuleap\REST\I18NRestException::class);

        $params = [
            'approval_table_action' => ApprovalTableUpdater::APPROVAL_TABLE_UPDATE_COPY,
            'user'                  => Mockery::mock(PFUser::class)
        ];
        $wiki_item->accept($this->validator_visitor, $params);
    }

    public function testItThrowsErrorWhenExpectingAFileAndGivenItemIsFolder()
    {
        $folder_item = new Docman_Folder();

        $this->expectException(\Tuleap\REST\I18NRestException::class);

        $params = [
            'approval_table_action' => ApprovalTableUpdater::APPROVAL_TABLE_UPDATE_COPY,
            'user'                  => Mockery::mock(PFUser::class)
        ];
        $folder_item->accept($this->validator_visitor, $params);
    }

    public function testItThrowsErrorWhenExpectingAFileAndGivenItemIsGeneric()
    {
        $item = new Docman_Item();

        $this->expectException(\Tuleap\REST\I18NRestException::class);

        $params = [
            'approval_table_action' => ApprovalTableUpdater::APPROVAL_TABLE_UPDATE_COPY,
            'user'                  => Mockery::mock(PFUser::class)
        ];
        $item->accept($this->validator_visitor, $params);
    }

    public function testItThrowExceptionWhenUserCantWriteFile(): void
    {
        $file_item = new Docman_File();

        $this->permission_manager->shouldReceive('userCanWrite')->andReturn(false);
        $this->expectException(\Tuleap\REST\I18NRestException::class);

        $params = [
            'approval_table_action' => ApprovalTableUpdater::APPROVAL_TABLE_UPDATE_COPY,
            'user'                  => Mockery::mock(PFUser::class)
        ];
        $file_item->accept($this->validator_visitor, $params);
    }

    public function testItThrowExceptionWhenApprovalTableOptionsAreIncorrect(): void
    {
        $file_item = new Docman_File();

        $this->permission_manager->shouldReceive('userCanWrite')->andReturn(true);
        $this->approval_checker->shouldReceive('checkApprovalTableForItem')->andThrow(
            ApprovalTableException::approvalTableActionIsMandatory("item title")
        );
        $this->permission_manager->shouldReceive('_itemIsLockedForUser')->andReturn(false);

        $this->expectException(ApprovalTableException::class);

        $params = [
            'approval_table_action' => ApprovalTableUpdater::APPROVAL_TABLE_UPDATE_COPY,
            'user'                  => Mockery::mock(PFUser::class)
        ];
        $file_item->accept($this->validator_visitor, $params);
    }

    public function testItThrowExceptionWhenItemIsAlreadyLocked(): void
    {
        $file_item = new Docman_File();

        $this->permission_manager->shouldReceive('userCanWrite')->andReturn(true);
        $this->approval_checker->shouldReceive('checkApprovalTableForItem')->andReturn(true);
        $this->permission_manager->shouldReceive('_itemIsLockedForUser')->andReturn(true);

        $this->expectException(ExceptionItemIsLockedByAnotherUser::class);

        $params = [
            'approval_table_action' => ApprovalTableUpdater::APPROVAL_TABLE_UPDATE_COPY,
            'user'                  => Mockery::mock(PFUser::class)
        ];
        $file_item->accept($this->validator_visitor, $params);
    }

    public function testVisitorDoesNotReturnExceptionIsEverythingIsAlright(): void
    {
        $file_item = new Docman_File();

        $this->permission_manager->shouldReceive('userCanWrite')->andReturn(true);
        $this->approval_checker->shouldReceive('checkApprovalTableForItem')->andReturn(true);
        $this->permission_manager->shouldReceive('_itemIsLockedForUser')->andReturn(false);

        $params = [
            'approval_table_action' => ApprovalTableUpdater::APPROVAL_TABLE_UPDATE_COPY,
            'user'                  => Mockery::mock(PFUser::class)
        ];
        $file_item->accept($this->validator_visitor, $params);
    }
}
