<?php
/**
 * Copyright (c) Enalean, 2025 - Present. All Rights Reserved.
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

namespace Tuleap\Artidoc\Upload\Section\File;

use Tuleap\Artidoc\Adapter\Document\ArtidocDocument;
use Tuleap\DB\DatabaseUUIDV7Factory;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tus\Identifier\UUIDFileIdentifierFactory;
use Tuleap\Upload\NextGen\FileBeingUploadedInformation;

final class ArtidocUploadPathAllocatorTest extends TestCase
{
    public function testTheSamePathIsAlwaysAllocatedForAGivenItemID(): void
    {
        $allocator = ArtidocUploadPathAllocator::fromArtidoc(new ArtidocDocument(['item_id' => 123]));

        $file_id = (new UUIDFileIdentifierFactory(new DatabaseUUIDV7Factory()))->buildIdentifier();

        self::assertSame(
            $allocator->getPathForItemBeingUploaded(new FileBeingUploadedInformation($file_id, 'Filename', 123, 0)),
            $allocator->getPathForItemBeingUploaded(new FileBeingUploadedInformation($file_id, 'Filename', 123, 0))
        );
    }
}
