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
 * along with Tuleap. If not, see http://www.gnu.org/licenses/.
 *
 */

declare(strict_types=1);

namespace Tuleap\Docman\REST\v1\Files;

use DateTimeImmutable;
use Docman_Empty;
use Docman_Item;
use Docman_LockFactory;
use Docman_SettingsBo;
use PHPUnit\Framework\MockObject\MockObject;
use Tuleap\Docman\FilenamePattern\FilenameBuilder;
use Tuleap\Docman\REST\v1\Metadata\ItemStatusMapper;
use Tuleap\Docman\Tests\Stub\FilenamePatternRetrieverStub;
use Tuleap\Docman\Upload\Version\VersionToUpload;
use Tuleap\Docman\Upload\Version\VersionToUploadCreator;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class DocmanFileVersionCreatorTest extends TestCase
{
    private VersionToUploadCreator&MockObject $creator;
    private DocmanFileVersionCreator $version_creator;
    private Docman_LockFactory&MockObject $lock_factory;

    protected function setUp(): void
    {
        $this->creator         = $this->createMock(VersionToUploadCreator::class);
        $this->lock_factory    = $this->createMock(Docman_LockFactory::class);
        $this->version_creator = new DocmanFileVersionCreator(
            $this->creator,
            $this->lock_factory,
            new FilenameBuilder(
                FilenamePatternRetrieverStub::buildWithNoPattern(),
                new ItemStatusMapper($this->createMock(Docman_SettingsBo::class))
            )
        );
    }

    public function testItShouldStoreTheVersionWhenFileRepresentationIsCorrect(): void
    {
        $item = new Docman_Item(['item_id' => 4, 'status' => 100, 'obsolescence_date' => 0, 'title' => 'file', 'description' => '', 'group_id' => 102]);
        $user = UserTestBuilder::buildWithId(101);

        $date = new DateTimeImmutable();

        $representation                             = new DocmanFileVersionPOSTRepresentation();
        $representation->change_log                 = 'changelog';
        $representation->version_title              = 'version title';
        $representation->file_properties            = new FilePropertiesPOSTPATCHRepresentation();
        $representation->file_properties->file_name = 'file';
        $representation->file_properties->file_size = 0;
        $representation->approval_table_action      = 'copy';
        $representation->should_lock_file           = false;

        $version_id        = 1;
        $version_to_upload = new VersionToUpload($version_id);
        $this->creator->expects($this->once())->method('create')->willReturn($version_to_upload);

        $this->lock_factory->expects(self::never())->method('itemIsLocked');

        $created_version_representation = $this->version_creator->createFileVersion(
            $item,
            $user,
            $representation,
            $date,
            (int) $item->getStatus(),
            (int) $item->getObsolescenceDate()
        );

        self::assertEquals('/uploads/docman/version/1', $created_version_representation->upload_href);
    }

    public function testItShouldStoreANewFileVersionWhenAnEmptyItemBecomesAFile(): void
    {
        $item = new Docman_Empty(['item_id' => 4, 'status' => 100, 'obsolescence_date' => 0, 'title' => 'file', 'description' => '']);
        $user = UserTestBuilder::buildWithId(101);

        $representation            = new FilePropertiesPOSTPATCHRepresentation();
        $representation->file_name = 'Coco';
        $representation->file_size = 5;
        $date                      = new DateTimeImmutable();

        $this->lock_factory->expects($this->once())->method('itemIsLocked')->with($item)->willReturn(true);

        $version_id        = 1;
        $version_to_upload = new VersionToUpload($version_id);
        $this->creator->expects($this->once())->method('create')->willReturn($version_to_upload);

        $created_version_representation = $this->version_creator->createVersionFromEmpty(
            $item,
            $user,
            $representation,
            $date,
            (int) $item->getStatus(),
            (int) $item->getObsolescenceDate()
        );

        self::assertEquals('/uploads/docman/version/1', $created_version_representation->upload_href);
    }
}
