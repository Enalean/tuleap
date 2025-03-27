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

namespace Tuleap\Docman\REST\v1\EmbeddedFiles;

use DateTimeImmutable;
use DateTimeZone;
use Docman_EmbeddedFile;
use Docman_Empty;
use Docman_FileStorage;
use Docman_ItemFactory;
use Docman_Version;
use Docman_VersionFactory;
use Tuleap\DB\DBTransactionExecutor;
use Tuleap\Docman\REST\v1\DocmanItemUpdator;
use Tuleap\Docman\REST\v1\PostUpdateEventAdder;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class EmbeddedFileVersionCreatorTest extends TestCase
{
    private EmbeddedFileVersionCreator $embedded_updator;
    private DBTransactionExecutor $transaction_executor;

    protected function setUp(): void
    {
        $this->transaction_executor = $this->createMock(DBTransactionExecutor::class);

        $this->embedded_updator = new EmbeddedFileVersionCreator(
            $this->createMock(Docman_FileStorage::class),
            $this->createMock(Docman_VersionFactory::class),
            $this->createMock(Docman_ItemFactory::class),
            $this->createMock(DocmanItemUpdator::class),
            $this->transaction_executor,
            $this->createMock(PostUpdateEventAdder::class)
        );
    }

    public function testItShouldStoreTheNewVersionWhenEmbeddedFileRepresentationIsCorrect(): void
    {
        $item = new Docman_EmbeddedFile(['item_id' => 1]);
        $user = UserTestBuilder::buildWithId(101);

        $date                        = new DateTimeImmutable();
        $date                        = $date->setTimezone(new DateTimeZone('GMT+1'));
        $date                        = $date->setTime(0, 0, 0);
        $obsolescence_date           = $date->modify('+1 day');
        $obsolescence_date_formatted = $obsolescence_date->format('Y-m-d');

        $file_version = new Docman_Version(['filetype' => 'file', 'number' => 123]);

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

        $this->transaction_executor->expects($this->once())->method('execute');

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
        $item = new Docman_Empty(['item_id' => 1]);
        $user = UserTestBuilder::buildWithId(101);

        $representation          = new EmbeddedPropertiesPOSTPATCHRepresentation();
        $representation->content = 'We will send Mowgli takes the medal';

        $current_time = new DateTimeImmutable();
        $this->transaction_executor->expects($this->once())->method('execute');

        $this->embedded_updator->createEmbeddedFileVersionFromEmpty(
            $item,
            $user,
            $representation,
            $current_time
        );
    }
}
