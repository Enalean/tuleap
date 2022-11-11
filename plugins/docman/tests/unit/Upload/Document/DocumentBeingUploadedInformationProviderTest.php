<?php
/**
 * Copyright (c) Enalean, 2018 - Present. All Rights Reserved.
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

namespace Tuleap\Docman\Upload\Document;

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Psr\Http\Message\ServerRequestInterface;
use Tuleap\Http\Server\NullServerRequest;
use Tuleap\Request\NotFoundException;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\Stubs\CurrentRequestUserProviderStub;
use Tuleap\Upload\UploadPathAllocator;

final class DocumentBeingUploadedInformationProviderTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use MockeryPHPUnitIntegration;

    public function testFileInformationCanBeProvided(): void
    {
        $path_allocator = new UploadPathAllocator('/var/tmp');
        $dao            = \Mockery::mock(DocumentOngoingUploadDAO::class);
        $item_factory   = \Mockery::mock(\Docman_ItemFactory::class);
        $current_user   = UserTestBuilder::buildWithId(102);
        $data_store     = new DocumentBeingUploadedInformationProvider($path_allocator, $dao, $item_factory, new CurrentRequestUserProviderStub($current_user));

        $dao->shouldReceive('searchDocumentOngoingUploadByItemIDUserIDAndExpirationDate')->andReturns([
            'filesize' => 123456,
            'filename' => 'readme.md',
        ]);
        $item_factory->shouldReceive('getItemFromDb')->andReturns(null);

        $item_id        = 12;
        $server_request = (new NullServerRequest())->withAttribute('id', (string) $item_id);

        $file_information = $data_store->getFileInformation($server_request);

        $this->assertSame($item_id, $file_information->getID());
        $this->assertSame(123456, $file_information->getLength());
        $this->assertSame(0, $file_information->getOffset());
    }

    public function testFileInformationCanBeProvidedWhenTheFileHasAlreadyBeenUploaded(): void
    {
        $path_allocator = new UploadPathAllocator('/var/tmp');
        $dao            = \Mockery::mock(DocumentOngoingUploadDAO::class);
        $item_factory   = \Mockery::mock(\Docman_ItemFactory::class);
        $data_store     = new DocumentBeingUploadedInformationProvider(
            $path_allocator,
            $dao,
            $item_factory,
            new CurrentRequestUserProviderStub(UserTestBuilder::buildWithId(102))
        );

        $dao->shouldReceive('searchDocumentOngoingUploadByItemIDUserIDAndExpirationDate')->andReturns([
            'filesize' => 123456,
            'filename' => 'readme.md',
        ]);
        $item_factory->shouldReceive('getItemFromDb')->andReturns(\Mockery::mock(\Docman_Item::class));

        $item_id      = 12;
        $current_user = \Mockery::mock(\PFUser::class);
        $current_user->shouldReceive('getID')->andReturn('102');
        $server_request = (new NullServerRequest())->withAttribute('id', (string) $item_id);

        $file_information = $data_store->getFileInformation($server_request);

        $this->assertSame($item_id, $file_information->getID());
        $this->assertSame(123456, $file_information->getLength());
        $this->assertSame(123456, $file_information->getOffset());
    }

    public function testFileInformationCannotBeFoundIfRequestAttributesAreMissing(): void
    {
        $data_store = new DocumentBeingUploadedInformationProvider(
            new UploadPathAllocator('/var/tmp'),
            \Mockery::mock(DocumentOngoingUploadDAO::class),
            \Mockery::mock(\Docman_ItemFactory::class),
            new CurrentRequestUserProviderStub(UserTestBuilder::buildWithDefaults()),
        );

        $request = \Mockery::mock(ServerRequestInterface::class);
        $request->shouldReceive('getAttribute')->andReturns(null);

        $this->assertNull($data_store->getFileInformation($request));
    }

    public function testFileInformationCannotBeFoundIfCurrentUserIsMissing(): void
    {
        $data_store = new DocumentBeingUploadedInformationProvider(
            new UploadPathAllocator('/var/tmp'),
            \Mockery::mock(DocumentOngoingUploadDAO::class),
            \Mockery::mock(\Docman_ItemFactory::class),
            new CurrentRequestUserProviderStub(null),
        );

        $this->expectException(NotFoundException::class);
        $data_store->getFileInformation((new NullServerRequest())->withAttribute('id', '11'));
    }

    public function testFileInformationCannotBeFoundIfThereIsNotAValidEntryInTheDatabase(): void
    {
        $dao          = \Mockery::mock(DocumentOngoingUploadDAO::class);
        $item_factory = \Mockery::mock(\Docman_ItemFactory::class);
        $data_store   = new DocumentBeingUploadedInformationProvider(
            new UploadPathAllocator('/var/tmp'),
            $dao,
            $item_factory,
            new CurrentRequestUserProviderStub(UserTestBuilder::buildWithId(102)),
        );

        $dao->shouldReceive('searchDocumentOngoingUploadByItemIDUserIDAndExpirationDate')->andReturns([]);
        $item_factory->shouldReceive('getItemFromDb')->andReturns(null);

        $server_request = (new NullServerRequest())->withAttribute('id', '12');

        $this->assertNull($data_store->getFileInformation($server_request));
    }
}
