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
use PHPUnit\Framework\MockObject\MockObject;
use Tuleap\Docman\ItemType\DoesItemHasExpectedTypeVisitor;
use Tuleap\REST\I18NRestException;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class DocumentBeforeModificationValidatorVisitorTest extends TestCase
{
    private Docman_PermissionsManager&MockObject $permission_manager;
    private DocumentBeforeModificationValidatorVisitor $validator_visitor;

    public function setUp(): void
    {
        $this->permission_manager = $this->createMock(Docman_PermissionsManager::class);
        $this->validator_visitor  = new DocumentBeforeModificationValidatorVisitor(
            $this->permission_manager,
            UserTestBuilder::buildWithDefaults(),
            new Docman_Item(['item_id' => 1]),
            new DoesItemHasExpectedTypeVisitor(Docman_File::class)
        );
    }

    public function testItThrowsErrorWhenExpectingAFileAndGivenItemIsLink(): void
    {
        $link_item = new Docman_Link();

        $this->expectException(I18NRestException::class);

        $link_item->accept($this->validator_visitor);
    }

    public function testItThrowsErrorWhenExpectingAFileAndGivenItemIsEmbeddedFile(): void
    {
        $embedded_file_item = new Docman_EmbeddedFile();

        $this->expectException(I18NRestException::class);

        $embedded_file_item->accept($this->validator_visitor);
    }

    public function testItThrowsErrorWhenExpectingAFileAndGivenItemIsEmptyDocument(): void
    {
        $empty_item = new Docman_Empty();

        $this->expectException(I18NRestException::class);

        $empty_item->accept($this->validator_visitor);
    }

    public function testItThrowsErrorWhenExpectingAFileAndGivenItemIsWiki(): void
    {
        $wiki_item = new Docman_Wiki();

        $this->expectException(I18NRestException::class);

        $wiki_item->accept($this->validator_visitor);
    }

    public function testItThrowsErrorWhenExpectingAFileAndGivenItemIsFolder(): void
    {
        $folder_item = new Docman_Folder();

        $this->expectException(I18NRestException::class);

        $folder_item->accept($this->validator_visitor);
    }

    public function testItThrowsErrorWhenExpectingAFileAndGivenItemIsGeneric(): void
    {
        $item = new Docman_Item();

        $this->expectException(I18NRestException::class);

        $item->accept($this->validator_visitor);
    }

    public function testItDoesNotThrowErrorWhenExpectingAFileAndGivenItemIsAFile(): void
    {
        self::expectNotToPerformAssertions();
        $file_item = new Docman_File();

        $this->permission_manager->method('userCanWrite')->willReturn(true);

        $file_item->accept($this->validator_visitor);
    }

    public function testItThrowExceptionWhenUserCantWriteFile(): void
    {
        $file_item = new Docman_File();

        $this->permission_manager->method('userCanWrite')->willReturn(false);
        $this->expectException(I18NRestException::class);

        $file_item->accept($this->validator_visitor);
    }
}
