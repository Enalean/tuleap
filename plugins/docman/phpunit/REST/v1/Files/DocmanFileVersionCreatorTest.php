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

use Docman_Item;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Tuleap\Docman\Upload\Version\VersionToUpload;
use Tuleap\Docman\Upload\Version\VersionToUploadCreator;

class DocmanFileVersionCreatorTest extends TestCase
{

    use MockeryPHPUnitIntegration;

    /**
     * @var Mockery\MockInterface|VersionToUploadCreator
     */
    private $creator;
    /**
     * @var DocmanFileVersionCreator
     */
    private $version_creator;
    /**
     * @var \Docman_LockFactory|Mockery\LegacyMockInterface|Mockery\MockInterface
     */
    private $lock_factory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->creator         = Mockery::mock(VersionToUploadCreator::class);
        $this->lock_factory    = Mockery::mock(\Docman_LockFactory::class);
        $this->version_creator = new DocmanFileVersionCreator(
            $this->creator,
            $this->lock_factory
        );
    }

    public function testItShouldStoreTheVersionWhenFileRepresentationIsCorrect(): void
    {
        $item = Mockery::mock(Docman_Item::class);
        $item->shouldReceive('getId')->andReturn(4);
        $item->shouldReceive('getStatus')->andReturn(100);
        $item->shouldReceive('getObsolescenceDate')->andReturn(0);
        $item->shouldReceive('getTitle')->andReturn('file');
        $item->shouldReceive('getDescription')->andReturn('');

        $user = Mockery::mock(\PFUser::class);
        $user->shouldReceive('getId')->andReturn(101);

        $date = new \DateTimeImmutable();

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
        $this->creator->shouldReceive('create')->once()->andReturn($version_to_upload);

        $this->lock_factory->shouldReceive('itemIsLocked')->never();

        $created_version_representation = $this->version_creator->createFileVersion(
            $item,
            $user,
            $representation,
            $date,
            (int) $item->getStatus(),
            (int) $item->getObsolescenceDate(),
            $item->getTitle(),
            $item->getDescription()
        );

        $this->assertEquals("/uploads/docman/version/1", $created_version_representation->upload_href);
    }

    public function testItShouldStoreANewFileVersionWhenAnEmptyItemBecomesAFile(): void
    {
        $item = Mockery::mock(\Docman_Empty::class);
        $item->shouldReceive('getId')->andReturn(4);
        $item->shouldReceive('getStatus')->andReturn(100);
        $item->shouldReceive('getObsolescenceDate')->andReturn(0);
        $item->shouldReceive('getTitle')->andReturn('file');
        $item->shouldReceive('getDescription')->andReturn('');

        $user = Mockery::mock(\PFUser::class);
        $user->shouldReceive('getId')->andReturn(101);

        $representation            = new FilePropertiesPOSTPATCHRepresentation();
        $representation->file_name = "Coco";
        $representation->file_size = 5;
        $date                      = new \DateTimeImmutable();

        $this->lock_factory->shouldReceive('itemIsLocked')->once()->with($item)->andReturn(true);

        $version_id        = 1;
        $version_to_upload = new VersionToUpload($version_id);
        $this->creator->shouldReceive('create')->once()->andReturn($version_to_upload);

        $created_version_representation = $this->version_creator->createVersionFromEmpty(
            $item,
            $user,
            $representation,
            $date,
            (int) $item->getStatus(),
            (int) $item->getObsolescenceDate()
        );

        $this->assertEquals("/uploads/docman/version/1", $created_version_representation->upload_href);
    }
}
