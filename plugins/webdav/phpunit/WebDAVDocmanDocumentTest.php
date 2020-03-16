<?php
/**
 * Copyright (c) Enalean, 2019 - Present. All Rights Reserved.
 * Copyright (c) STMicroelectronics 2011. All rights reserved
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
use Docman_Document;
use Docman_ItemFactory;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PFUser;
use PHPUnit\Framework\TestCase;
use Project;
use ProjectManager;
use Sabre_DAV_Exception_Forbidden;
use Sabre_DAV_Exception_MethodNotAllowed;
use Tuleap\WebDAV\Docman\DocumentDownloader;
use WebDAVDocmanDocument;
use WebDAVUtils;

require_once __DIR__ . '/bootstrap.php';

class WebDAVDocmanDocumentTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|WebDAVUtils
     */
    private $utils;

    /**
     * @var Docman_ItemFactory|Mockery\LegacyMockInterface|Mockery\MockInterface
     */
    private $docman_item_factory;

    /**
     * @var Docman_Document|Mockery\LegacyMockInterface|Mockery\MockInterface
     */
    private $document;

    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|PFUser
     */
    private $user;

    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|Project
     */
    private $project;

    /**
     * @var array
     */
    private $globals;

    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|DocumentDownloader
     */
    private $document_downloader;

    protected function setUp(): void
    {
        parent::setUp();

        $this->globals = $GLOBALS;
        $GLOBALS = [];
        $GLOBALS['Language'] = Mockery::spy(BaseLanguage::class);

        $this->utils = Mockery::mock(WebDAVUtils::class);
        WebDAVUtils::setInstance($this->utils);

        $this->docman_item_factory = Mockery::mock(Docman_ItemFactory::class);
        Docman_ItemFactory::setInstance(102, $this->docman_item_factory);

        $project_manager = Mockery::mock(ProjectManager::class);
        ProjectManager::setInstance($project_manager);

        $this->document = Mockery::mock(Docman_Document::class);
        $this->document->shouldReceive('getId')->andReturn(4);

        $this->docman_item_factory->shouldReceive('getItemFromDb')->andReturn($this->document);

        $this->user     = Mockery::mock(PFUser::class);
        $this->project  = Mockery::mock(Project::class);

        $this->project->shouldReceive('getID')->andReturn(102);

        $this->document_downloader = Mockery::mock(DocumentDownloader::class);
    }

    protected function tearDown(): void
    {
        WebDAVUtils::clearInstance();
        Docman_ItemFactory::clearInstance(102);
        ProjectManager::clearInstance();

        $GLOBALS = $this->globals;

        parent::tearDown();
    }

    public function testDeleteNoWriteEnabled(): void
    {
        $webDAVDocmanDocument = new WebDAVDocmanDocument($this->user, $this->project, $this->document, $this->document_downloader);

        $this->utils->shouldReceive('isWriteEnabled')->andReturnFalse();

        $this->expectException(Sabre_DAV_Exception_Forbidden::class);

        $webDAVDocmanDocument->delete();
    }

    public function testDeleteSuccess(): void
    {
        $webDAVDocmanDocument = new WebDAVDocmanDocument($this->user, $this->project, $this->document, $this->document_downloader);

        $this->utils->shouldReceive('isWriteEnabled')->andReturnTrue();
        $this->utils->shouldReceive('processDocmanRequest')->once();

        $webDAVDocmanDocument->delete();
    }

    public function testSetNameNoWriteEnabled(): void
    {
        $webDAVDocmanDocument = new WebDAVDocmanDocument($this->user, $this->project, $this->document, $this->document_downloader);

        $this->utils->shouldReceive('isWriteEnabled')->andReturnFalse();

        $this->expectException(Sabre_DAV_Exception_MethodNotAllowed::class);

        $webDAVDocmanDocument->setName('newName');
    }

    public function testSetNameSuccess(): void
    {
        $webDAVDocmanDocument = new WebDAVDocmanDocument($this->user, $this->project, $this->document, $this->document_downloader);

        $this->utils->shouldReceive('isWriteEnabled')->andReturnTrue();
        $this->utils->shouldReceive('processDocmanRequest')->once();

        $webDAVDocmanDocument->setName('newName');
    }
}
