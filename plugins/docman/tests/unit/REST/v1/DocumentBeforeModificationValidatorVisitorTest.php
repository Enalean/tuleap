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
use Docman_Link;
use Docman_PermissionsManager;
use Docman_Wiki;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PFUser;
use PHPUnit\Framework\TestCase;
use Tuleap\Docman\ItemType\DoesItemHasExpectedTypeVisitor;

class DocumentBeforeModificationValidatorVisitorTest extends TestCase
{
    use MockeryPHPUnitIntegration;

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

        $this->permission_manager = \Mockery::mock(Docman_PermissionsManager::class);
        $this->current_user = \Mockery::mock(PFUser::class);
        $this->item = \Mockery::mock(Docman_Item::class);
        $this->item->shouldReceive('getId')->andReturn(1);
        $this->validator_visitor = new DocumentBeforeModificationValidatorVisitor(
            $this->permission_manager,
            $this->current_user,
            $this->item,
            new DoesItemHasExpectedTypeVisitor(Docman_File::class)
        );
    }

    public function testItThrowsErrorWhenExpectingAFileAndGivenItemIsLink()
    {
        $link_item = new Docman_Link();

        $this->expectException(\Tuleap\REST\I18NRestException::class);

        $link_item->accept($this->validator_visitor);
    }

    public function testItThrowsErrorWhenExpectingAFileAndGivenItemIsEmbeddedFile()
    {
        $embedded_file_item = new Docman_EmbeddedFile();

        $this->expectException(\Tuleap\REST\I18NRestException::class);

        $embedded_file_item->accept($this->validator_visitor);
    }

    public function testItThrowsErrorWhenExpectingAFileAndGivenItemIsEmptyDocument()
    {
        $empty_item = new Docman_Empty();

        $this->expectException(\Tuleap\REST\I18NRestException::class);

        $empty_item->accept($this->validator_visitor);
    }

    public function testItThrowsErrorWhenExpectingAFileAndGivenItemIsWiki()
    {
        $wiki_item = new Docman_Wiki();

        $this->expectException(\Tuleap\REST\I18NRestException::class);

        $wiki_item->accept($this->validator_visitor);
    }

    public function testItThrowsErrorWhenExpectingAFileAndGivenItemIsFolder()
    {
        $folder_item = new Docman_Folder();

        $this->expectException(\Tuleap\REST\I18NRestException::class);

        $folder_item->accept($this->validator_visitor);
    }

    public function testItThrowsErrorWhenExpectingAFileAndGivenItemIsGeneric()
    {
        $item = new Docman_Item();

        $this->expectException(\Tuleap\REST\I18NRestException::class);

        $item->accept($this->validator_visitor);
    }

    public function testItDoesNotThrowErrorWhenExpectingAFileAndGivenItemIsAFile()
    {
        $file_item = new Docman_File();

        $this->permission_manager->shouldReceive('userCanWrite')->andReturn(true);

        $file_item->accept($this->validator_visitor);
    }

    public function testItThrowExceptionWhenUserCantWriteFile(): void
    {
        $file_item = new Docman_File();

        $this->permission_manager->shouldReceive('userCanWrite')->andReturn(false);
        $this->expectException(\Tuleap\REST\I18NRestException::class);

        $file_item->accept($this->validator_visitor);
    }
}
