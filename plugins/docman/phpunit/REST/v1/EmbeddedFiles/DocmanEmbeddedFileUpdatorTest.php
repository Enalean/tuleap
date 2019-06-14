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
use Docman_EmbeddedFile;
use Docman_FileStorage;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Tuleap\DB\DBTransactionExecutor;
use Tuleap\Docman\REST\v1\DocmanItemUpdator;
use Tuleap\Docman\REST\v1\EmbeddedFiles\DocmanEmbeddedFilesPATCHRepresentation;
use Tuleap\Docman\REST\v1\EmbeddedFiles\DocmanEmbeddedFileUpdator;
use Tuleap\Docman\REST\v1\EmbeddedFiles\EmbeddedFilePropertiesRepresentation;
use Tuleap\Docman\REST\v1\ExceptionItemIsLockedByAnotherUser;
use Tuleap\Docman\REST\v1\Metadata\HardcodedMetadataObsolescenceDateRetriever;
use Tuleap\Docman\REST\v1\Metadata\ItemStatusMapper;

class DocmanEmbeddedFileUpdatorTest extends TestCase
{
    use MockeryPHPUnitIntegration;
    /**
     * @var DocmanEmbeddedFileUpdator
     */
    private $embedded_updator;
    /**
     * @var \Docman_PermissionsManager|Mockery\MockInterface
     */
    private $permissions_manager;
    /**
     * @var Mockery\MockInterface|HardcodedMetadataObsolescenceDateRetriever
     */
    private $date_retriever;
    /**
     * @var Mockery\MockInterface|ItemStatusMapper
     */
    private $status_mapper;
    /**
     * @var Mockery\MockInterface|DBTransactionExecutor
     */
    private $transaction_executor;
    /**
     * @var Mockery\MockInterface|DocmanItemUpdator
     */
    private $updator;
    /**
     * @var \Docman_ItemFactory|Mockery\MockInterface
     */
    private $item_factory;
    /**
     * @var \Docman_VersionFactory|Mockery\MockInterface
     */
    private $version_factory;
    /**
     * @var Docman_FileStorage|Mockery\MockInterface
     */
    private $file_storage;

    protected function setUp(): void
    {
        parent::setUp();

        $this->file_storage         = Mockery::mock(Docman_FileStorage::class);
        $this->version_factory      = Mockery::mock(\Docman_VersionFactory::class);
        $this->item_factory         = Mockery::mock(\Docman_ItemFactory::class);
        $this->updator              = Mockery::mock(DocmanItemUpdator::class);
        $this->transaction_executor = Mockery::mock(DBTransactionExecutor::class);
        $this->status_mapper        = Mockery::mock(ItemStatusMapper::class);
        $this->date_retriever       = Mockery::mock(HardcodedMetadataObsolescenceDateRetriever::class);
        $this->permissions_manager  = Mockery::mock(\Docman_PermissionsManager::class);

        $this->embedded_updator = new DocmanEmbeddedFileUpdator(
            $this->file_storage,
            $this->version_factory,
            $this->item_factory,
            $this->updator,
            $this->transaction_executor,
            $this->status_mapper,
            $this->date_retriever,
            $this->permissions_manager
        );
    }

    public function testItShouldStoreTheNewVersionWhenEmbeddedFileRepresentationIsCorrect(): void
    {
        $item = Mockery::mock(Docman_EmbeddedFile::class);
        $item->shouldReceive('getId')->andReturn(1);
        $user = Mockery::mock(\PFUser::class);
        $user->shouldReceive('getId')->andReturn(101);

        $this->permissions_manager->shouldReceive('_itemIsLockedForUser')->andReturn(false);

        $date                        = new \DateTimeImmutable();
        $date                        = $date->setTimezone(new DateTimeZone('GMT+1'));
        $date                        = $date->setTime(0, 0, 0);
        $obsolescence_date           = $date->modify('+1 day');
        $obsolescence_date_formatted = $obsolescence_date->format('Y-m-d');

        $this->status_mapper->shouldReceive('getItemStatusIdFromItemStatusString')->with('rejected')->andReturn(103);

        $this->date_retriever->shouldReceive('getTimeStampOfDate')->withArgs(
            [$obsolescence_date_formatted, $date]
        )->andReturn($obsolescence_date->getTimestamp());

        $representation                                 = new DocmanEmbeddedFilesPATCHRepresentation();
        $representation->change_log                     = 'changelog';
        $representation->version_title                  = 'version title';
        $representation->should_lock_file               = false;
        $representation->embedded_properties            = new EmbeddedFilePropertiesRepresentation();
        $representation->embedded_properties->file_type = 'file';
        $representation->embedded_properties->content   = 'My custom content';
        $representation->approval_table_action          = 'copy';
        $representation->status                         = 'rejected';
        $representation->obsolescence_date              = $obsolescence_date_formatted;

        $this->transaction_executor->shouldReceive('execute')->once();

        $this->embedded_updator->updateEmbeddedFile($item, $user, $representation, $date);
    }

    public function testItThrowsAnExceptionWhenItemIsLocked(): void
    {
        $item = Mockery::mock(\Docman_EmbeddedFile::class);
        $item->shouldReceive('getId')->andReturn(1);
        $user = Mockery::mock(\PFUser::class);
        $user->shouldReceive('getId')->andReturn(101);

        $this->permissions_manager->shouldReceive('_itemIsLockedForUser')->andReturn(true);

        $date                        = new \DateTimeImmutable();
        $date                        = $date->setTimezone(new DateTimeZone('GMT+1'));
        $date                        = $date->setTime(0, 0, 0);
        $obsolescence_date           = $date->modify('+1 day');
        $obsolescence_date_formatted = $obsolescence_date->format('Y-m-d');

        $representation                                 = new DocmanEmbeddedFilesPATCHRepresentation();
        $representation->change_log                     = 'changelog';
        $representation->version_title                  = 'version title';
        $representation->should_lock_file               = false;
        $representation->embedded_properties            = new EmbeddedFilePropertiesRepresentation();
        $representation->embedded_properties->file_type = 'file';
        $representation->embedded_properties->content   = 'My custom content';
        $representation->approval_table_action          = 'copy';
        $representation->status                         = 'rejected';
        $representation->obsolescence_date              = $obsolescence_date_formatted;

        $this->status_mapper->shouldReceive('getItemStatusIdFromItemStatusString')->never();
        $this->transaction_executor->shouldReceive('execute')->never();

        $this->expectException(ExceptionItemIsLockedByAnotherUser::class);

        $this->embedded_updator->updateEmbeddedFile($item, $user, $representation, $date);
    }
}
