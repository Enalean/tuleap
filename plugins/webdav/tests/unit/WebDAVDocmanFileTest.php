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

use Docman_EmbeddedFile;
use Docman_File;
use DocmanPlugin;
use EventManager;
use ForgeConfig;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PFUser;
use Project;
use Sabre\DAV\Exception\Forbidden;
use Sabre\DAV\Exception\MethodNotAllowed;
use Sabre\DAV\Exception\NotFound;
use Sabre\DAV\Exception\RequestedRangeNotSatisfiable;
use Tuleap\ForgeConfigSandbox;
use Tuleap\GlobalLanguageMock;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\WebDAV\Docman\DocumentDownloader;

class WebDAVDocmanFileTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use MockeryPHPUnitIntegration;
    use GlobalLanguageMock;
    use ForgeConfigSandbox;

    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|DocumentDownloader
     */
    private $document_download;

    /**
     * @var PFUser
     */
    private $user;

    /**
     * @var Project
     */
    private $project;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|\WebDAVUtils
     */
    private $utils;

    protected function setUp(): void
    {
        $this->document_download = Mockery::mock(DocumentDownloader::class);
        $this->user              = UserTestBuilder::aUser()->build();
        $this->project           = ProjectTestBuilder::aProject()->build();
        $this->utils             = Mockery::mock(\WebDAVUtils::class);

        EventManager::setInstance(Mockery::spy(EventManager::class));
    }

    protected function tearDown(): void
    {
        EventManager::clearInstance();
    }

    /**
     * Test when the file doesn't exist on the filesystem
     */
    public function testGetNotFound(): void
    {
        $version = new \Docman_Version(['filesize' => 2, 'path' => __DIR__ . '/_fixtures/nonExistant']);

        $item = new Docman_File();
        $item->setCurrentVersion($version);

        $this->expectException(NotFound::class);

        $webDAVDocmanFile = new \WebDAVDocmanFile($this->user, $this->project, $item, $this->document_download, $this->utils);
        $webDAVDocmanFile->get();
    }

    /**
     * Test when the file is too big
     */
    public function testGetBigFile(): void
    {
        $version = new \Docman_Version(['filesize' => 2, 'path' => __DIR__ . '/_fixtures/test.txt']);

        $item = new Docman_File();
        $item->setCurrentVersion($version);

        $webDAVDocmanFile = new \WebDAVDocmanFile($this->user, $this->project, $item, $this->document_download, $this->utils);

        ForgeConfig::set(DocmanPlugin::PLUGIN_DOCMAN_MAX_FILE_SIZE_SETTING, 1);

        $this->expectException(RequestedRangeNotSatisfiable::class);
        $webDAVDocmanFile->get();
    }

    public function testGetSucceed(): void
    {
        $version = new \Docman_Version(['filesize' => 1, 'path' => __DIR__ . '/_fixtures/test.txt', 'filetype' => 'type1']);

        $item = new Docman_File();
        $item->setCurrentVersion($version);

        $webDAVDocmanFile = new \WebDAVDocmanFile($this->user, $this->project, $item, $this->document_download, $this->utils);

        ForgeConfig::set(DocmanPlugin::PLUGIN_DOCMAN_MAX_FILE_SIZE_SETTING, 1);

        $this->document_download->shouldReceive('downloadDocument')->once();

        $webDAVDocmanFile->get();
    }

    public function testPutNoWriteEnabled(): void
    {
        $item = \Mockery::spy(\Docman_File::class);

        $this->utils->shouldReceive('isWriteEnabled')->andReturns(false);

        $webDAVDocmanFile = new \WebDAVDocmanFile($this->user, $this->project, $item, $this->document_download, $this->utils);

        $this->expectException(Forbidden::class);
        $data = fopen(dirname(__FILE__) . '/_fixtures/test.txt', 'r');

        $webDAVDocmanFile->put($data);
    }

    public function testPutBigFile(): void
    {
        $item = new \Docman_EmbeddedFile(['title' => 'foo']);

        $this->utils->shouldReceive('isWriteEnabled')->andReturns(true);
        $this->utils->shouldReceive('processDocmanRequest')->never();

        $webDAVDocmanFile = new \WebDAVDocmanFile($this->user, $this->project, $item, $this->document_download, $this->utils);

        ForgeConfig::set(DocmanPlugin::PLUGIN_DOCMAN_MAX_FILE_SIZE_SETTING, 20);

        $this->expectException(RequestedRangeNotSatisfiable::class);
        $data = fopen(dirname(__FILE__) . '/_fixtures/test.txt', 'r');
        $webDAVDocmanFile->put($data);
    }

    public function testPutSucceed(): void
    {
        $item = new \Docman_EmbeddedFile(['title' => 'foo']);

        $this->utils->shouldReceive('isWriteEnabled')->andReturns(true);
        $this->utils->shouldReceive('processDocmanRequest')->once();

        $webDAVDocmanFile = new \WebDAVDocmanFile($this->user, $this->project, $item, $this->document_download, $this->utils);

        ForgeConfig::set(DocmanPlugin::PLUGIN_DOCMAN_MAX_FILE_SIZE_SETTING, 4096);

        $data = fopen(dirname(__FILE__) . '/_fixtures/test.txt', 'r');
        $webDAVDocmanFile->put($data);
    }

    public function testSetNameNoWriteEnabled(): void
    {
        $this->utils->shouldReceive('isWriteEnabled')->andReturnFalse();

        $webDAVDocmanFile = new \WebDAVDocmanFile($this->user, $this->project, new Docman_File(), $this->document_download, $this->utils);

        $this->expectException(MethodNotAllowed::class);

        $webDAVDocmanFile->setName('newName');
    }

    public function testSetNameFile(): void
    {
        $webDAVDocmanFile = new \WebDAVDocmanFile($this->user, $this->project, new Docman_File(), $this->document_download, $this->utils);

        $this->expectException(MethodNotAllowed::class);

        $webDAVDocmanFile->setName('newName');
    }

    public function testSetNameEmbeddedFile(): void
    {
        $this->utils->shouldReceive('isWriteEnabled')->andReturns(true);
        $this->utils->shouldReceive('processDocmanRequest')->once();

        $webDAVDocmanFile = new \WebDAVDocmanFile($this->user, $this->project, new Docman_EmbeddedFile(), $this->document_download, $this->utils);

        $webDAVDocmanFile->setName('newName');
    }

    public function testDeleteNoWriteEnabled(): void
    {
        $this->utils->shouldReceive('isWriteEnabled')->andReturnFalse();

        $webDAVDocmanFile = new \WebDAVDocmanFile($this->user, $this->project, new Docman_File(), $this->document_download, $this->utils);

        $this->expectException(Forbidden::class);

        $webDAVDocmanFile->delete();
    }

    public function testDeleteSuccess(): void
    {
        $this->utils->shouldReceive('isWriteEnabled')->andReturnTrue();
        $this->utils->shouldReceive('processDocmanRequest')->once();

        $webDAVDocmanFile = new \WebDAVDocmanFile($this->user, $this->project, new Docman_File(), $this->document_download, $this->utils);

        $webDAVDocmanFile->delete();
    }
}
