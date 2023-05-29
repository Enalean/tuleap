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

namespace Tuleap\FRS\Upload\Tus;

use Psr\Http\Message\ServerRequestInterface;
use Tuleap\FRS\Upload\FileOngoingUploadDao;
use Tuleap\FRS\Upload\UploadPathAllocator;
use Tuleap\Http\Server\NullServerRequest;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\Stubs\CurrentRequestUserProviderStub;

final class FileBeingUploadedInformationProviderTest extends \Tuleap\Test\PHPUnit\TestCase
{
    public function testFileInformationCanBeProvided(): void
    {
        $path_allocator = new UploadPathAllocator();
        $dao            = $this->createMock(FileOngoingUploadDao::class);
        $current_user   = UserTestBuilder::aUser()->withId(102)->build();
        $data_store     = new FileBeingUploadedInformationProvider($path_allocator, $dao, new CurrentRequestUserProviderStub($current_user));

        $dao->method('searchFileOngoingUploadByIDUserIDAndExpirationDate')->willReturn(
            [
                'file_size' => 123456,
                'name'      => 'readme.md',
            ]
        );

        $item_id        = 12;
        $server_request = (new NullServerRequest())->withAttribute('id', (string) $item_id);

        $file_information = $data_store->getFileInformation($server_request);

        self::assertNotNull($file_information);
        self::assertSame($item_id, $file_information->getID());
        self::assertSame(123456, $file_information->getLength());
        self::assertSame(0, $file_information->getOffset());
    }

    public function testFileInformationCannotBeFoundIfRequestAttributesAreMissing(): void
    {
        $data_store = new FileBeingUploadedInformationProvider(
            new UploadPathAllocator(),
            $this->createMock(FileOngoingUploadDao::class),
            new CurrentRequestUserProviderStub(UserTestBuilder::buildWithDefaults())
        );

        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('getAttribute')->willReturn(null);

        self::assertNull($data_store->getFileInformation($request));
    }

    public function testFileInformationCannotBeFoundIfNoCurrentUserIsFoundOnTheRequest(): void
    {
        $data_store = new FileBeingUploadedInformationProvider(
            new UploadPathAllocator(),
            $this->createStub(FileOngoingUploadDao::class),
            new CurrentRequestUserProviderStub(null)
        );

        self::assertNull($data_store->getFileInformation((new NullServerRequest())->withAttribute('id', '11')));
    }

    public function testFileInformationCannotBeFoundIfThereIsNotAValidEntryInTheDatabase(): void
    {
        $dao          = $this->createMock(FileOngoingUploadDao::class);
        $current_user = UserTestBuilder::aUser()->withId(102)->build();
        $data_store   = new FileBeingUploadedInformationProvider(new UploadPathAllocator(), $dao, new CurrentRequestUserProviderStub($current_user));

        $dao->method('searchFileOngoingUploadByIDUserIDAndExpirationDate')->willReturn([]);

        $server_request = (new NullServerRequest())->withAttribute('id', '12');

        self::assertNull($data_store->getFileInformation($server_request));
    }
}
