<?php
/**
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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

namespace Tuleap\Docman\Upload\Version;

use Docman_Item;
use Docman_ItemFactory;
use Tuleap\Http\Server\NullServerRequest;
use Tuleap\Request\NotFoundException;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Test\Stubs\CurrentRequestUserProviderStub;
use Tuleap\Upload\UploadPathAllocator;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class VersionBeingUploadedInformationProviderTest extends TestCase
{
    public function testFileInformationCanBeProvided(): void
    {
        $path_allocator = new UploadPathAllocator('/var/tmp');
        $dao            = $this->createMock(DocumentOnGoingVersionToUploadDAO::class);
        $item_factory   = $this->createMock(Docman_ItemFactory::class);
        $data_store     = new VersionBeingUploadedInformationProvider($dao, $item_factory, $path_allocator, new CurrentRequestUserProviderStub(UserTestBuilder::buildWithId(102)));

        $dao->method('searchDocumentVersionOngoingUploadByVersionIDUserIDAndExpirationDate')->willReturn([
            'filesize' => 123456,
            'item_id'  => 5,
            'filename' => 'readme.md',
        ]);
        $item = new Docman_Item(['item_id' => 5]);
        $item_factory->method('getItemFromDb')->willReturn($item);

        $id             = 12;
        $server_request = (new NullServerRequest())->withAttribute('id', (string) $id);

        $file_information = $data_store->getFileInformation($server_request);

        self::assertSame($id, $file_information->getID());
        self::assertSame(123456, $file_information->getLength());
        self::assertSame(0, $file_information->getOffset());
    }

    public function testFileInformationCannotBeFoundIfItemIsNoMoreFoundInBD(): void
    {
        $path_allocator = new UploadPathAllocator('/var/tmp');
        $dao            = $this->createMock(DocumentOnGoingVersionToUploadDAO::class);
        $item_factory   = $this->createMock(Docman_ItemFactory::class);
        $data_store     = new VersionBeingUploadedInformationProvider($dao, $item_factory, $path_allocator, new CurrentRequestUserProviderStub(UserTestBuilder::buildWithDefaults()));

        $dao->method('searchDocumentVersionOngoingUploadByVersionIDUserIDAndExpirationDate')->willReturn([
            'filesize' => 123456,
            'item_id'  => 5,
            'filename' => 'readme.md',
        ]);
        $item_factory->method('getItemFromDb')->willReturn(null);

        $request = new NullServerRequest();

        self::assertNull($data_store->getFileInformation($request));
    }

    public function testFileInformationCannotBeFoundIfRequestAttributesAreMissing(): void
    {
        $path_allocator = new UploadPathAllocator('/var/tmp');
        $dao            = $this->createMock(DocumentOnGoingVersionToUploadDAO::class);
        $item_factory   = $this->createMock(Docman_ItemFactory::class);
        $data_store     = new VersionBeingUploadedInformationProvider($dao, $item_factory, $path_allocator, new CurrentRequestUserProviderStub(UserTestBuilder::buildWithDefaults()));

        $request = new NullServerRequest();

        self::assertNull($data_store->getFileInformation($request));
    }

    public function testFileInformationCannotBeFoundIfNoCurrentUserIsAssociatedWithTheRequest(): void
    {
        $path_allocator = new UploadPathAllocator('/var/tmp');
        $dao            = $this->createStub(DocumentOnGoingVersionToUploadDAO::class);
        $item_factory   = $this->createStub(Docman_ItemFactory::class);
        $data_store     = new VersionBeingUploadedInformationProvider($dao, $item_factory, $path_allocator, new CurrentRequestUserProviderStub(null));


        $this->expectException(NotFoundException::class);
        $data_store->getFileInformation((new NullServerRequest())->withAttribute('id', '102'));
    }

    public function testFileInformationCannotBeFoundIfThereIsNotAValidEntryInTheDatabase(): void
    {
        $path_allocator = new UploadPathAllocator('/var/tmp');
        $dao            = $this->createMock(DocumentOnGoingVersionToUploadDAO::class);
        $item_factory   = $this->createMock(Docman_ItemFactory::class);
        $data_store     = new VersionBeingUploadedInformationProvider($dao, $item_factory, $path_allocator, new CurrentRequestUserProviderStub(UserTestBuilder::buildWithId(102)));

        $dao->method('searchDocumentVersionOngoingUploadByVersionIDUserIDAndExpirationDate')->willReturn([]);
        $item_factory->method('getItemFromDb')->willReturn(null);

        $server_request = (new NullServerRequest())->withAttribute('id', '12');

        self::assertNull($data_store->getFileInformation($server_request));
    }
}
