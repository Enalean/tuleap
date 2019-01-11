<?php
/**
 * Copyright (c) Enalean, 2018-2019. All Rights Reserved.
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

namespace Tuleap\Docman\Upload;

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;

class DocumentBeingUploadedInformationProviderTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function testFileInformationCanBeProvided() : void
    {
        $path_allocator = new DocumentUploadPathAllocator();
        $dao            = \Mockery::mock(DocumentOngoingUploadDAO::class);
        $item_factory   = \Mockery::mock(\Docman_ItemFactory::class);
        $data_store     = new DocumentBeingUploadedInformationProvider($path_allocator, $dao, $item_factory);

        $dao->shouldReceive('searchDocumentOngoingUploadByItemIDUserIDAndExpirationDate')->andReturns([
            'filesize' => 123456
        ]);
        $item_factory->shouldReceive('getItemFromDb')->andReturns(null);

        $request = \Mockery::mock(ServerRequestInterface::class);
        $item_id = 12;
        $request->shouldReceive('getAttribute')->with('item_id')->andReturns((string) $item_id);
        $request->shouldReceive('getAttribute')->with('user_id')->andReturns('102');

        $file_information = $data_store->getFileInformation($request);

        $this->assertSame($item_id, $file_information->getID());
        $this->assertSame(123456, $file_information->getLength());
        $this->assertSame(0, $file_information->getOffset());
    }

    public function testFileInformationCanBeProvidedWhenTheFileHasAlreadyBeenUploaded() : void
    {
        $path_allocator = new DocumentUploadPathAllocator();
        $dao            = \Mockery::mock(DocumentOngoingUploadDAO::class);
        $item_factory   = \Mockery::mock(\Docman_ItemFactory::class);
        $data_store     = new DocumentBeingUploadedInformationProvider($path_allocator, $dao, $item_factory);

        $dao->shouldReceive('searchDocumentOngoingUploadByItemIDUserIDAndExpirationDate')->andReturns([
            'filesize' => 123456
        ]);
        $item_factory->shouldReceive('getItemFromDb')->andReturns(\Mockery::mock(\Docman_Item::class));

        $request = \Mockery::mock(ServerRequestInterface::class);
        $item_id = 12;
        $request->shouldReceive('getAttribute')->with('item_id')->andReturns((string) $item_id);
        $request->shouldReceive('getAttribute')->with('user_id')->andReturns('102');

        $file_information = $data_store->getFileInformation($request);

        $this->assertSame($item_id, $file_information->getID());
        $this->assertSame(123456, $file_information->getLength());
        $this->assertSame(123456, $file_information->getOffset());
    }

    public function testFileInformationCannotBeFoundIfRequestAttributesAreMissing() : void
    {
        $data_store = new DocumentBeingUploadedInformationProvider(
            new DocumentUploadPathAllocator(),
            \Mockery::mock(DocumentOngoingUploadDAO::class),
            \Mockery::mock(\Docman_ItemFactory::class)
        );

        $request = \Mockery::mock(ServerRequestInterface::class);
        $request->shouldReceive('getAttribute')->andReturns(null);

        $this->assertNull($data_store->getFileInformation($request));
    }

    public function testFileInformationCannotBeFoundIfThereIsNotAValidEntryInTheDatabase() : void
    {
        $dao           = \Mockery::mock(DocumentOngoingUploadDAO::class);
        $item_factory  = \Mockery::mock(\Docman_ItemFactory::class);
        $data_store = new DocumentBeingUploadedInformationProvider(
            new DocumentUploadPathAllocator(),
            $dao,
            $item_factory
        );

        $dao->shouldReceive('searchDocumentOngoingUploadByItemIDUserIDAndExpirationDate')->andReturns([]);
        $item_factory->shouldReceive('getItemFromDb')->andReturns(null);

        $request = \Mockery::mock(ServerRequestInterface::class);
        $request->shouldReceive('getAttribute')->with('item_id')->andReturns('12');
        $request->shouldReceive('getAttribute')->with('user_id')->andReturns('102');


        $this->assertNull($data_store->getFileInformation($request));
    }
}
