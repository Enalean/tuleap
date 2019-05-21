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

namespace Tuleap\Docman\REST\v1\Files;

use DateTimeZone;
use Docman_Item;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Tuleap\Docman\ApprovalTable\ApprovalTableRetriever;
use Tuleap\Docman\Lock\LockChecker;
use Tuleap\Docman\REST\v1\Metadata\HardcodedMetadataObsolescenceDateRetriever;
use Tuleap\Docman\REST\v1\Metadata\ItemStatusMapper;
use Tuleap\Docman\Upload\Version\VersionToUpload;
use Tuleap\Docman\Upload\Version\VersionToUploadCreator;

class DocmanItemFileUpdatorTest extends TestCase
{
    use MockeryPHPUnitIntegration;
    /**
     * @var Mockery\MockInterface|LockChecker
     */
    private $lock_checker;
    /**
     * @var Mockery\MockInterface|VersionToUploadCreator
     */
    private $creator;
    /**
     * @var DocmanItemFileUpdator
     */
    private $updator;

    /**
     * @var Mockery\MockInterface|ApprovalTableRetriever
     */
    private $approval_table_retriever;
    /**
     * @var Mockery\MockInterface|ItemStatusMapper
     */
    private $status_mapper;
    /**
     * @var Mockery\MockInterface|HardcodedMetadataObsolescenceDateRetriever
     */
    private $date_retriever;

    protected function setUp() : void
    {
        parent::setUp();

        $this->approval_table_retriever  = Mockery::mock(ApprovalTableRetriever::class);
        $this->creator                   = Mockery::mock(VersionToUploadCreator::class);
        $this->lock_checker              = Mockery::mock(LockChecker::class);
        $this->status_mapper             = Mockery::mock(ItemStatusMapper::class);
        $this->date_retriever            = Mockery::mock(HardcodedMetadataObsolescenceDateRetriever::class);

        $this->updator = new DocmanItemFileUpdator(
            $this->approval_table_retriever,
            $this->creator,
            $this->lock_checker,
            $this->status_mapper,
            $this->date_retriever
        );
    }

    public function testItShouldStoreTheNewVersionWhenFileRepresentationIsCorrect()
    {
        $item = Mockery::mock(Docman_Item::class);
        $user = Mockery::mock(\PFUser::class);
        $user->shouldReceive('getId')->andReturn(101);
        $this->approval_table_retriever->shouldReceive('retrieveByItem')
                                       ->with($item)
                                       ->andReturn(null);

        $date                        = new \DateTimeImmutable();
        $date                        = $date->setTimezone(new DateTimeZone('GMT+1'));
        $date                        = $date->setTime(0, 0, 0);
        $obsolescence_date           = $date->modify('+1 day');
        $obsolescence_date_formatted = $obsolescence_date->format('Y-m-d');
        $this->lock_checker->shouldReceive('checkItemIsLocked');

        $this->status_mapper->shouldReceive('getItemStatusIdFromItemStatusString')->with('rejected')->andReturn(103);

        $this->date_retriever->shouldReceive('getTimeStampOfDate')->withArgs(
            [$obsolescence_date_formatted, $date]
        )->andReturn($obsolescence_date->getTimestamp());

        $representation                             = new DocmanFilesPATCHRepresentation();
        $representation->change_log                 = 'changelog';
        $representation->version_title              = 'version title';
        $representation->should_lock_file           = false;
        $representation->file_properties            = new FilePropertiesPOSTPATCHRepresentation();
        $representation->file_properties->file_name = 'file';
        $representation->file_properties->file_size = 0;
        $representation->approval_table_action      = 'copy';
        $representation->status                     = 'rejected';
        $representation->obsolescence_date          = $obsolescence_date_formatted;

        $version_id        = 1;
        $version_to_upload = new VersionToUpload($version_id);
        $this->creator->shouldReceive('create')->once()->andReturn($version_to_upload);

        $created_version_representation = $this->updator->updateFile($item, $user, $representation, $date);

        $this->assertEquals("/uploads/docman/version/1", $created_version_representation->upload_href);
    }
}
