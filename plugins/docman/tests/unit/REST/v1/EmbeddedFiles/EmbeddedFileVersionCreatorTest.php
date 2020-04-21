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

namespace Tuleap\Docman\REST\v1\Files;

use DateTimeZone;
use Docman_EmbeddedFile;
use Docman_FileStorage;
use Docman_Version;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Tuleap\DB\DBTransactionExecutor;
use Tuleap\Docman\REST\v1\DocmanItemUpdator;
use Tuleap\Docman\REST\v1\EmbeddedFiles\DocmanEmbeddedFilesPATCHRepresentation;
use Tuleap\Docman\REST\v1\EmbeddedFiles\EmbeddedFilePropertiesFullRepresentation;
use Tuleap\Docman\REST\v1\EmbeddedFiles\EmbeddedFileVersionCreator;
use Tuleap\Docman\REST\v1\EmbeddedFiles\EmbeddedPropertiesPOSTPATCHRepresentation;
use Tuleap\Docman\REST\v1\PostUpdateEventAdder;

class EmbeddedFileVersionCreatorTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var EmbeddedFileVersionCreator
     */
    private $embedded_updator;
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
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|PostUpdateEventAdder
     */
    private $post_update_event_adder;

    protected function setUp(): void
    {
        parent::setUp();

        $this->file_storage            = Mockery::mock(Docman_FileStorage::class);
        $this->version_factory         = Mockery::mock(\Docman_VersionFactory::class);
        $this->item_factory            = Mockery::mock(\Docman_ItemFactory::class);
        $this->updator                 = Mockery::mock(DocmanItemUpdator::class);
        $this->transaction_executor    = Mockery::mock(DBTransactionExecutor::class);
        $this->post_update_event_adder = Mockery::mock(PostUpdateEventAdder::class);

        $this->embedded_updator = new EmbeddedFileVersionCreator(
            $this->file_storage,
            $this->version_factory,
            $this->item_factory,
            $this->updator,
            $this->transaction_executor,
            $this->post_update_event_adder
        );
    }

    public function testItShouldStoreTheNewVersionWhenEmbeddedFileRepresentationIsCorrect(): void
    {
        $item = Mockery::mock(Docman_EmbeddedFile::class);
        $item->shouldReceive('getId')->andReturn(1);
        $user = Mockery::mock(\PFUser::class);
        $user->shouldReceive('getId')->andReturn(101);

        $date                        = new \DateTimeImmutable();
        $date                        = $date->setTimezone(new DateTimeZone('GMT+1'));
        $date                        = $date->setTime(0, 0, 0);
        $obsolescence_date           = $date->modify('+1 day');
        $obsolescence_date_formatted = $obsolescence_date->format('Y-m-d');

        $file_version = Mockery::mock(Docman_Version::class);
        $file_version->shouldReceive('getFiletype')->andReturn('file');

        $representation                        = new DocmanEmbeddedFilesPATCHRepresentation();
        $representation->change_log            = 'changelog';
        $representation->version_title         = 'version title';
        $representation->should_lock_file      = false;
        $representation->embedded_properties   = EmbeddedFilePropertiesFullRepresentation::build(
            $file_version,
            'My custom content'
        );
        $representation->approval_table_action = 'copy';
        $representation->status                = 'rejected';
        $representation->obsolescence_date     = $obsolescence_date_formatted;

        $this->transaction_executor->shouldReceive('execute')->once();

        $this->embedded_updator->createEmbeddedFileVersion(
            $item,
            $user,
            $representation,
            $date,
            103,
            $obsolescence_date->getTimestamp(),
            '',
            ''
        );
    }

    public function testItShouldCreateAVersionOfEmbeddedFileFromAnEmptyDocument(): void
    {
        $item = Mockery::mock(\Docman_Empty::class);
        $item->shouldReceive('getId')->andReturn(1);
        $user = Mockery::mock(\PFUser::class);
        $user->shouldReceive('getId')->andReturn(101);

        $representation          = new EmbeddedPropertiesPOSTPATCHRepresentation();
        $representation->content = 'We will send Mowgli takes the medal';

        $current_time = new \DateTimeImmutable();
        $this->transaction_executor->shouldReceive('execute')->once();

        $this->embedded_updator->createEmbeddedFileVersionFromEmpty(
            $item,
            $user,
            $representation,
            $current_time
        );
    }
}
