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

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class WebDAVDocmanFileTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use GlobalLanguageMock;
    use ForgeConfigSandbox;

    /**
     * @var DocumentDownloader&\PHPUnit\Framework\MockObject\MockObject
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
     * @var \WebDAVUtils&\PHPUnit\Framework\MockObject\MockObject
     */
    private $utils;
    /**
     * @var EventManager&\PHPUnit\Framework\MockObject\MockObject
     */
    private $event_manager;

    protected function setUp(): void
    {
        $this->document_download = $this->createMock(DocumentDownloader::class);
        $this->user              = UserTestBuilder::aUser()->build();
        $this->project           = ProjectTestBuilder::aProject()->build();
        $this->utils             = $this->createMock(\WebDAVUtils::class);

        $this->event_manager = $this->createMock(EventManager::class);

        EventManager::setInstance($this->event_manager);
        $GLOBALS['Language']->method('getText')->willReturn('');
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

        $this->event_manager->method('addListener');
        $this->event_manager->method('processEvent');
        $this->document_download->expects($this->once())->method('downloadDocument');

        $webDAVDocmanFile->get();
    }

    public function testPutNoWriteEnabled(): void
    {
        $item = $this->createMock(\Docman_File::class);

        $this->utils->method('isWriteEnabled')->willReturn(false);

        $webDAVDocmanFile = new \WebDAVDocmanFile($this->user, $this->project, $item, $this->document_download, $this->utils);

        $this->expectException(Forbidden::class);
        $data = fopen(dirname(__FILE__) . '/_fixtures/test.txt', 'r');

        $webDAVDocmanFile->put($data);
    }

    public function testPutBigFile(): void
    {
        $item = new \Docman_EmbeddedFile(['title' => 'foo']);

        $this->utils->method('isWriteEnabled')->willReturn(true);
        $this->utils->expects($this->never())->method('processDocmanRequest');

        $webDAVDocmanFile = new \WebDAVDocmanFile($this->user, $this->project, $item, $this->document_download, $this->utils);

        ForgeConfig::set(DocmanPlugin::PLUGIN_DOCMAN_MAX_FILE_SIZE_SETTING, 20);

        $this->expectException(RequestedRangeNotSatisfiable::class);
        $data = fopen(dirname(__FILE__) . '/_fixtures/test.txt', 'r');
        $webDAVDocmanFile->put($data);
    }

    public function testPutSucceed(): void
    {
        $item = new \Docman_EmbeddedFile(['title' => 'foo']);

        $this->utils->method('isWriteEnabled')->willReturn(true);
        $this->utils->expects($this->once())->method('processDocmanRequest');

        $webDAVDocmanFile = new \WebDAVDocmanFile($this->user, $this->project, $item, $this->document_download, $this->utils);

        ForgeConfig::set(DocmanPlugin::PLUGIN_DOCMAN_MAX_FILE_SIZE_SETTING, 4096);

        $data = fopen(dirname(__FILE__) . '/_fixtures/test.txt', 'r');
        $webDAVDocmanFile->put($data);
    }

    public function testSetNameNoWriteEnabled(): void
    {
        $this->utils->method('isWriteEnabled')->willReturn(false);

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
        $this->utils->method('isWriteEnabled')->willReturn(true);
        $this->utils->expects($this->once())->method('processDocmanRequest');

        $webDAVDocmanFile = new \WebDAVDocmanFile($this->user, $this->project, new Docman_EmbeddedFile(), $this->document_download, $this->utils);

        $webDAVDocmanFile->setName('newName');
    }

    public function testDeleteNoWriteEnabled(): void
    {
        $this->utils->method('isWriteEnabled')->willReturn(false);

        $webDAVDocmanFile = new \WebDAVDocmanFile($this->user, $this->project, new Docman_File(), $this->document_download, $this->utils);

        $this->expectException(Forbidden::class);

        $webDAVDocmanFile->delete();
    }

    public function testDeleteSuccess(): void
    {
        $this->utils->method('isWriteEnabled')->willReturn(true);
        $this->utils->expects($this->once())->method('processDocmanRequest');

        $webDAVDocmanFile = new \WebDAVDocmanFile($this->user, $this->project, new Docman_File(), $this->document_download, $this->utils);

        $webDAVDocmanFile->delete();
    }
}
