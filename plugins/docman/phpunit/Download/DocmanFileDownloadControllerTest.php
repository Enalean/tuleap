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

namespace Tuleap\Docman\Download;

use Docman_File;
use Docman_Item;
use Docman_ItemFactory;
use HTTPRequest;
use Log_NoopLogger;
use LogicException;
use Mockery;
use PFUser;
use PHPUnit\Framework\TestCase;
use Project;
use ProjectManager;
use Tuleap\Layout\BaseLayout;
use Tuleap\Request\NotFoundException;

final class DocmanFileDownloadControllerTest extends TestCase
{
    use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    /**
     * @var ProjectManager|Mockery\MockInterface
     */
    private $project_manager;
    /**
     * @var Docman_ItemFactory|Mockery\MockInterface
     */
    private $item_factory;
    /**
     * @var DocmanFileDownloadResponseGenerator|Mockery\MockInterface
     */
    private $response_generator;

    protected function setUp() : void
    {
        $this->project_manager    = Mockery::mock(ProjectManager::class);
        $this->item_factory       = Mockery::mock(Docman_ItemFactory::class);
        $this->response_generator = Mockery::mock(DocmanFileDownloadResponseGenerator::class);
    }

    public function testControllerIsNotReusable() : void
    {
        $controller = new DocmanFileDownloadController(
            $this->project_manager,
            $this->item_factory,
            $this->response_generator,
            new Log_NoopLogger()
        );

        $docman_file = Mockery::mock(Docman_File::class);
        $docman_file->shouldReceive('getGroupId')->andReturn('101');
        $this->item_factory->shouldReceive('getItemFromDb')->andReturn($docman_file);
        $this->project_manager->shouldReceive('getProject')->andReturn(Mockery::mock(Project::class));

        $http_request = Mockery::mock(HTTPRequest::class);
        $controller->getProject($http_request, ['file_id' => '1']);
        $this->expectException(LogicException::class);
        $controller->getProject($http_request, ['file_id' => '2']);
    }

    public function testProjectCanNotBeFoundWhenItemDoesNotExist() : void
    {
        $controller = new DocmanFileDownloadController(
            $this->project_manager,
            $this->item_factory,
            $this->response_generator,
            new Log_NoopLogger()
        );

        $this->item_factory->shouldReceive('getItemFromDb')->andReturn(null);

        $this->expectException(NotFoundException::class);
        $controller->getProject(Mockery::mock(HTTPRequest::class), ['file_id' => '1']);
    }

    public function testProjectCanNotBeFoundWhenItemIsLinkedToANonExistingProject() : void
    {
        $controller = new DocmanFileDownloadController(
            $this->project_manager,
            $this->item_factory,
            $this->response_generator,
            new Log_NoopLogger()
        );

        $docman_file = Mockery::mock(Docman_File::class);
        $docman_file->shouldReceive('getGroupId')->andReturn('101');
        $this->item_factory->shouldReceive('getItemFromDb')->andReturn($docman_file);
        $this->project_manager->shouldReceive('getProject')->andReturn(null);

        $this->expectException(NotFoundException::class);
        $controller->getProject(Mockery::mock(HTTPRequest::class), ['file_id' => '1']);
    }

    public function testItemMustHaveBeenIdentifiedBeforeProcessingTheRequest() : void
    {
        $controller = new DocmanFileDownloadController(
            $this->project_manager,
            $this->item_factory,
            $this->response_generator,
            new Log_NoopLogger()
        );

        $this->expectException(LogicException::class);
        $controller->process(Mockery::mock(HTTPRequest::class), Mockery::mock(BaseLayout::class), []);
    }

    public function testOnlyAFileCanBeDownloaded() : void
    {
        $controller = new DocmanFileDownloadController(
            $this->project_manager,
            $this->item_factory,
            $this->response_generator,
            new Log_NoopLogger()
        );

        $docman_item = Mockery::mock(Docman_Item::class);
        $docman_item->shouldReceive('getGroupId')->andReturn('101');
        $this->item_factory->shouldReceive('getItemFromDb')->andReturn($docman_item);
        $this->project_manager->shouldReceive('getProject')->andReturn(Mockery::mock(Project::class));

        $uri_variables =  ['file_id' => '1'];
        $http_request  = Mockery::mock(HTTPRequest::class);
        $controller->getProject($http_request, $uri_variables);
        $this->expectException(NotFoundException::class);
        $controller->process($http_request, Mockery::mock(BaseLayout::class), $uri_variables);
    }

    public function testDownloadFailsWhenRequestedVersionCannotBeFound() : void
    {
        $controller         = new DocmanFileDownloadController(
            $this->project_manager,
            $this->item_factory,
            $this->response_generator,
            new Log_NoopLogger()
        );

        $docman_file = Mockery::mock(Docman_File::class);
        $docman_file->shouldReceive('getGroupId')->andReturn('101');
        $docman_file->shouldReceive('getId')->andReturn('1');
        $this->item_factory->shouldReceive('getItemFromDb')->andReturn($docman_file);
        $this->project_manager->shouldReceive('getProject')->andReturn(Mockery::mock(Project::class));

        $http_request  = Mockery::mock(HTTPRequest::class);
        $http_request->shouldReceive('getCurrentUser')->andReturn(Mockery::mock(PFUser::class));
        $uri_variables =  ['file_id' => '1', 'version_id' => '1'];
        $controller->getProject($http_request, $uri_variables);

        $this->response_generator->shouldReceive('generateResponse')->andThrow(new VersionNotFoundException($docman_file, 1));

        $this->expectException(NotFoundException::class);
        $this->expectExceptionMessageRegExp('/version/');
        $controller->process($http_request, Mockery::mock(BaseLayout::class), $uri_variables);
    }

    public function testDownloadFailsWhenResponseCannotBeGenerated() : void
    {
        $controller         = new DocmanFileDownloadController(
            $this->project_manager,
            $this->item_factory,
            $this->response_generator,
            new Log_NoopLogger()
        );

        $docman_file = Mockery::mock(Docman_File::class);
        $docman_file->shouldReceive('getGroupId')->andReturn('101');
        $this->item_factory->shouldReceive('getItemFromDb')->andReturn($docman_file);
        $this->project_manager->shouldReceive('getProject')->andReturn(Mockery::mock(Project::class));

        $http_request  = Mockery::mock(HTTPRequest::class);
        $http_request->shouldReceive('getCurrentUser')->andReturn(Mockery::mock(PFUser::class));
        $uri_variables =  ['file_id' => '1'];
        $controller->getProject($http_request, $uri_variables);

        $this->response_generator->shouldReceive('generateResponse')->andThrow(
            Mockery::mock(FileDownloadException::class)
        );

        $this->expectException(NotFoundException::class);
        $controller->process($http_request, Mockery::mock(BaseLayout::class), $uri_variables);
    }
}
