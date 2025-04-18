<?php
/**
 * Copyright (c) Enalean, 2019 - Present. All Rights Reserved.
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

namespace Tuleap\Tracker\FormElement\Field\File\Upload\Tus;

use PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles;
use Tuleap\Http\Server\NullServerRequest;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Test\Stubs\CurrentRequestUserProviderStub;
use Tuleap\Tracker\FormElement\Field\File\Upload\FileOngoingUploadDao;
use Tuleap\Upload\UploadPathAllocator;

#[DisableReturnValueGenerationForTestDoubles]
final class FileBeingUploadedInformationProviderTest extends TestCase
{
    public function testFileInformationCanBeProvided(): void
    {
        $path_allocator = new UploadPathAllocator('/base_path');

        $dao          = $this->createMock(FileOngoingUploadDao::class);
        $current_user = UserTestBuilder::buildWithId(102);
        $data_store   = new FileBeingUploadedInformationProvider($path_allocator, $dao, new CurrentRequestUserProviderStub($current_user));

        $dao->method('searchFileOngoingUploadByIDUserIDAndExpirationDate')->willReturn([
            'filesize' => 123456,
            'filename' => 'readme.md',
        ]);

        $item_id        = 12;
        $server_request = (new NullServerRequest())->withAttribute('id', (string) $item_id);

        $file_information = $data_store->getFileInformation($server_request);

        self::assertSame($item_id, $file_information->getID());
        self::assertSame(123456, $file_information->getLength());
        self::assertSame(0, $file_information->getOffset());
    }

    public function testFileInformationCannotBeFoundIfRequestAttributesAreMissing(): void
    {
        $data_store = new FileBeingUploadedInformationProvider(
            new UploadPathAllocator('/base_path'),
            $this->createStub(FileOngoingUploadDao::class),
            new CurrentRequestUserProviderStub(UserTestBuilder::buildWithDefaults())
        );

        self::assertNull($data_store->getFileInformation(new NullServerRequest()));
    }

    public function testFileInformationCannotBeFoundIfNoCurrentUserIsAssociatedWithTheRequest(): void
    {
        $provider = new FileBeingUploadedInformationProvider(
            new UploadPathAllocator('/base_path'),
            $this->createStub(FileOngoingUploadDao::class),
            new CurrentRequestUserProviderStub(null)
        );

        self::assertNull($provider->getFileInformation((new NullServerRequest())->withAttribute('id', '12')));
    }

    public function testFileInformationCannotBeFoundIfThereIsNotAValidEntryInTheDatabase(): void
    {
        $dao          = $this->createMock(FileOngoingUploadDao::class);
        $current_user = UserTestBuilder::buildWithId(102);
        $data_store   = new FileBeingUploadedInformationProvider(
            new UploadPathAllocator('/base_path'),
            $dao,
            new CurrentRequestUserProviderStub($current_user),
        );

        $dao->method('searchFileOngoingUploadByIDUserIDAndExpirationDate')->willReturn([]);

        $server_request = (new NullServerRequest())->withAttribute('id', '12');

        self::assertNull($data_store->getFileInformation($server_request));
    }
}
