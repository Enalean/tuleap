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

use org\bovigo\vfs\vfsStream;
use Tuleap\Artidoc\Adapter\Document\ArtidocDocument;
use Tuleap\DB\DatabaseUUIDV7Factory;
use Tuleap\ForgeConfigSandbox;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tus\Identifier\UUIDFileIdentifierFactory;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class EmptyFileToUploadFinisherTest extends TestCase
{
    use ForgeConfigSandbox;

    public function testCreateEmptyFile(): void
    {
        $tmp = vfsStream::setup()->url();
        \ForgeConfig::set('sys_data_dir', $tmp);

        $identifier     = (new UUIDFileIdentifierFactory(new DatabaseUUIDV7Factory()))->buildIdentifier();
        $file_to_upload = new FileToUpload($identifier, 'filename.png');

        $upload_path_allocator = ArtidocUploadPathAllocator::fromArtidoc(new ArtidocDocument(['item_id' => 123]));

        (new EmptyFileToUploadFinisher())->createEmptyFile($file_to_upload, $upload_path_allocator);

        self::assertTrue(is_file($tmp . '/artidoc/sections-file-upload/123/' . $identifier->toString()));
    }
}
