<?php
/**
 * Copyright (c) Enalean, 2011 - Present. All Rights Reserved.
 * Copyright (c) STMicroelectronics, 2010. All Rights Reserved.
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

namespace Tuleap\WebDAV;

use BaseLanguage;
use Docman_EmbeddedFile;
use Docman_File;
use Docman_ItemFactory;
use EventManager;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PFUser;
use PHPUnit\Framework\TestCase;
use Project;
use Sabre_DAV_Exception_FileNotFound;
use Sabre_DAV_Exception_Forbidden;
use Sabre_DAV_Exception_MethodNotAllowed;
use Sabre_DAV_Exception_RequestedRangeNotSatisfiable;
use Tuleap\WebDAV\Docman\DocumentDownloader;

require_once __DIR__ . '/bootstrap.php';

/**
 * This is the unit test of WebDAVDocmanFile
 */
class WebDAVDocmanFileTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|DocumentDownloader
     */
    private $document_download;

    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|PFUser
     */
    private $user;

    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|Project
     */
    private $project;

    /**
     * @var Docman_ItemFactory|Mockery\LegacyMockInterface|Mockery\MockInterface
     */
    private $docman_item_factory;

    /**
     * @var array
     */
    private $globals;

    protected function setUp(): void
    {
        parent::setUp();

        $this->globals = $GLOBALS;
        $GLOBALS = [];
        $GLOBALS['Language'] = Mockery::spy(BaseLanguage::class);

        $this->document_download = Mockery::mock(DocumentDownloader::class);
        $this->user              = Mockery::mock(PFUser::class);
        $this->project           = Mockery::mock(Project::class);

        $this->project->shouldReceive('getID')->andReturn(102);

        $this->docman_item_factory = Mockery::mock(Docman_ItemFactory::class);
        Docman_ItemFactory::setInstance(102, $this->docman_item_factory);

        $event_manager = Mockery::mock(EventManager::class);
        EventManager::setInstance($event_manager);
    }

    protected function tearDown(): void
    {
        EventManager::clearInstance();
        Docman_ItemFactory::clearInstance(102);

        $GLOBALS = $this->globals;

        parent::tearDown();
    }

    /**
     * Test when the file doesn't exist on the filesystem
     */
    public function testGetNotFound(): void
    {
        $version = \Mockery::spy(\Docman_Version::class);
        $version->shouldReceive('getPath')->andReturns(dirname(__FILE__) . '/_fixtures/nonExistant');
        $item = \Mockery::spy(\Docman_File::class);
        $item->shouldReceive('getCurrentVersion')->andReturns($version);

        $this->docman_item_factory->shouldReceive('getItemFromDb')->andReturn($item);

        $webDAVDocmanFile = \Mockery::mock(
            \WebDAVDocmanFile::class,
            [$this->user, $this->project, $item, $this->document_download]
        )
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();

        $this->expectException(Sabre_DAV_Exception_FileNotFound::class);

        $webDAVDocmanFile->get();
    }

    /**
     * Test when the file is too big
     */
    public function testGetBigFile(): void
    {
        $version = \Mockery::spy(\Docman_Version::class);
        $version->shouldReceive('getPath')->andReturns(dirname(__FILE__) . '/_fixtures/test.txt');
        $item = \Mockery::spy(\Docman_File::class);
        $item->shouldReceive('getCurrentVersion')->andReturns($version);

        $this->docman_item_factory->shouldReceive('getItemFromDb')->andReturn($item);

        $webDAVDocmanFile = \Mockery::mock(
            \WebDAVDocmanFile::class,
            [$this->user, $this->project, $item, $this->document_download]
        )
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();

        $webDAVDocmanFile->shouldReceive('getSize')->andReturns(2);
        $webDAVDocmanFile->shouldReceive('getMaxFileSize')->andReturns(1);

        $this->expectException(Sabre_DAV_Exception_RequestedRangeNotSatisfiable::class);
        $webDAVDocmanFile->get();
    }

    /**
     * Test when the file download succeede
     */
    public function testGetSucceede(): void
    {
        $version = \Mockery::spy(\Docman_Version::class);
        $version->shouldReceive('getPath')->andReturns(dirname(__FILE__) . '/_fixtures/test.txt');
        $version->shouldReceive('getFiletype')->andReturns('type1');
        $item = \Mockery::spy(\Docman_File::class);
        $item->shouldReceive('getCurrentVersion')->andReturns($version);

        $this->docman_item_factory->shouldReceive('getItemFromDb')->andReturn($item);

        $webDAVDocmanFile = \Mockery::mock(
            \WebDAVDocmanFile::class,
            [$this->user, $this->project, $item, $this->document_download]
        )
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();

        $webDAVDocmanFile->shouldReceive('getSize')->andReturns(1);
        $webDAVDocmanFile->shouldReceive('getMaxFileSize')->andReturns(1);
        $webDAVDocmanFile->shouldReceive('getName')->andReturns('document1');

        $this->document_download->shouldReceive('downloadDocument')->once();

        $webDAVDocmanFile->get();
    }

    public function testPutNoWriteEnabled(): void
    {
        $item = \Mockery::spy(\Docman_File::class);

        $this->docman_item_factory->shouldReceive('getItemFromDb')->andReturn($item);

        $webDAVDocmanFile = \Mockery::mock(
            \WebDAVDocmanFile::class,
            [$this->user, $this->project, $item, $this->document_download]
        )
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();

        $utils = \Mockery::spy(\WebDAVUtils::class);
        $utils->shouldReceive('isWriteEnabled')->andReturns(false);
        $webDAVDocmanFile->shouldReceive('getUtils')->andReturns($utils);

        $this->expectException(Sabre_DAV_Exception_Forbidden::class);
        $data = fopen(dirname(__FILE__) . '/_fixtures/test.txt', 'r');

        $webDAVDocmanFile->put($data);
    }

    public function testPutBigFile(): void
    {
        $item = \Mockery::spy(\Docman_Item::class);

        $this->docman_item_factory->shouldReceive('getItemFromDb')->andReturn($item);

        $webDAVDocmanFile = \Mockery::mock(
            \WebDAVDocmanFile::class,
            [$this->user, $this->project, $item, $this->document_download]
        )
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();

        $utils = \Mockery::spy(\WebDAVUtils::class);
        $utils->shouldReceive('isWriteEnabled')->andReturns(true);
        $utils->shouldReceive('processDocmanRequest')->never();
        $webDAVDocmanFile->shouldReceive('getUtils')->andReturns($utils);

        $webDAVDocmanFile->shouldReceive('getMaxFileSize')->andReturns(20);

        $this->expectException(Sabre_DAV_Exception_RequestedRangeNotSatisfiable::class);
        $data = fopen(dirname(__FILE__) . '/_fixtures/test.txt', 'r');
        $webDAVDocmanFile->put($data);
    }

    public function testPutSucceed(): void
    {
        $item = \Mockery::spy(\Docman_Item::class);

        $this->docman_item_factory->shouldReceive('getItemFromDb')->andReturn($item);

        $webDAVDocmanFile = \Mockery::mock(
            \WebDAVDocmanFile::class,
            [$this->user, $this->project, $item, $this->document_download]
        )
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();

        $utils = \Mockery::spy(\WebDAVUtils::class);
        $utils->shouldReceive('isWriteEnabled')->andReturns(true);
        $utils->shouldReceive('processDocmanRequest')->once();
        $webDAVDocmanFile->shouldReceive('getUtils')->andReturns($utils);

        $webDAVDocmanFile->shouldReceive('getMaxFileSize')->andReturns(4096);

        $data = fopen(dirname(__FILE__) . '/_fixtures/test.txt', 'r');
        $webDAVDocmanFile->put($data);
    }

    public function testSetNameFile(): void
    {
        $item = new Docman_File();

        $this->docman_item_factory->shouldReceive('getItemFromDb')->andReturn($item);

        $webDAVDocmanFile = \Mockery::mock(
            \WebDAVDocmanFile::class,
            [$this->user, $this->project, $item, $this->document_download]
        )
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();

        $this->expectException(Sabre_DAV_Exception_MethodNotAllowed::class);
        $webDAVDocmanFile->setName('newName');
    }

    public function testSetNameEmbeddedFile(): void
    {
        $item = new Docman_EmbeddedFile();

        $this->docman_item_factory->shouldReceive('getItemFromDb')->andReturn($item);

        $webDAVDocmanFile = \Mockery::mock(
            \WebDAVDocmanFile::class,
            [$this->user, $this->project, $item, $this->document_download]
        )
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();

        $utils = \Mockery::spy(\WebDAVUtils::class);
        $utils->shouldReceive('isWriteEnabled')->andReturns(true);
        $utils->shouldReceive('processDocmanRequest')->once();
        $webDAVDocmanFile->shouldReceive('getUtils')->andReturns($utils);

        $webDAVDocmanFile->setName('newName');
    }
}
