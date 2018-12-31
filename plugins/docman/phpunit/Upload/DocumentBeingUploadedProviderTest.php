<?php
/**
 * Copyright (c) Enalean, 2018. All Rights Reserved.
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

namespace Tuleap\Docman\Upload;

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;

class DocumentBeingUploadedProviderTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var vfsStreamDirectory
     */
    private $tmp_dir;

    protected function setUp()
    {
        \ForgeConfig::store();
        $this->tmp_dir = vfsStream::setup();
        \ForgeConfig::set('tmp_dir', $this->tmp_dir->url());
    }

    protected function tearDown()
    {
        \ForgeConfig::restore();
    }

    public function testFileIsCreatedIfItDoesNotExist()
    {
        $dao           = \Mockery::mock(DocumentOngoingUploadDAO::class);
        $item_factory  = \Mockery::mock(\Docman_ItemFactory::class);
        $file_provider = new DocumentBeingUploadedProvider(new DocumentUploadPathAllocator(), $dao, $item_factory);

        $dao->shouldReceive('searchDocumentOngoingUploadByItemIDUserIDAndExpirationDate')->andReturns([
            'filesize' => 123456
        ]);
        $item_factory->shouldReceive('getItemFromDb')->andReturns(null);

        $request = \Mockery::mock(ServerRequestInterface::class);
        $request->shouldReceive('getAttribute')->with('item_id')->andReturns('12');
        $request->shouldReceive('getAttribute')->with('user_id')->andReturns('102');


        $file = $file_provider->getFile($request);
        $this->assertCount(1, $this->tmp_dir->getChildren());
        $this->assertSame(123456, $file->getLength());
        $this->assertSame(0, $file->getOffset());
    }

    public function testFileIsNotCreatedAgainWhenIfItHasAlreadyBeenUploaded()
    {
        $dao           = \Mockery::mock(DocumentOngoingUploadDAO::class);
        $item_factory  = \Mockery::mock(\Docman_ItemFactory::class);
        $file_provider = new DocumentBeingUploadedProvider(new DocumentUploadPathAllocator(), $dao, $item_factory);

        $dao->shouldReceive('searchDocumentOngoingUploadByItemIDUserIDAndExpirationDate')->andReturns([
            'filesize' => 123456
        ]);
        $item_factory->shouldReceive('getItemFromDb')->andReturns(\Mockery::mock(\Docman_File::class));

        $request = \Mockery::mock(ServerRequestInterface::class);
        $request->shouldReceive('getAttribute')->with('item_id')->andReturns('12');
        $request->shouldReceive('getAttribute')->with('user_id')->andReturns('102');

        $file = $file_provider->getFile($request);
        $this->assertCount(0, $this->tmp_dir->getChildren());
        $this->assertSame(123456, $file->getLength());
        $this->assertSame(123456, $file->getOffset());
    }

    public function testDocumentCannotBeFoundIfRequestAttributesAreMissing()
    {
        $file_provider = new DocumentBeingUploadedProvider(
            new DocumentUploadPathAllocator(),
            \Mockery::mock(DocumentOngoingUploadDAO::class),
            \Mockery::mock(\Docman_ItemFactory::class)
        );

        $request = \Mockery::mock(ServerRequestInterface::class);
        $request->shouldReceive('getAttribute')->andReturns(null);

        $this->assertNull($file_provider->getFile($request));
    }

    public function testDocumentCannotBeFoundIfThereIsNotAValidEntryInTheDatabase()
    {
        $dao           = \Mockery::mock(DocumentOngoingUploadDAO::class);
        $item_factory  = \Mockery::mock(\Docman_ItemFactory::class);
        $file_provider = new DocumentBeingUploadedProvider(
            new DocumentUploadPathAllocator(),
            $dao,
            $item_factory
        );

        $dao->shouldReceive('searchDocumentOngoingUploadByItemIDUserIDAndExpirationDate')->andReturns([]);
        $item_factory->shouldReceive('getItemFromDb')->andReturns(null);

        $request = \Mockery::mock(ServerRequestInterface::class);
        $request->shouldReceive('getAttribute')->with('item_id')->andReturns('12');
        $request->shouldReceive('getAttribute')->with('user_id')->andReturns('102');


        $this->assertNull($file_provider->getFile($request));
    }
}
