<?php
/**
 * Copyright (c) Enalean, 2020-Present. All Rights Reserved.
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

namespace Tuleap\Docman\ExternalLinks;

use Docman_HTTPController;
use Docman_ItemDao;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Tuleap\Request\NotFoundException;
use Tuleap\Test\Builders\UserTestBuilder;

final class DocmanHTTPControllerProxyTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function testThrowsNotFoundWhenProjectCannotBeIdentifierFromTheRequest(): void
    {
        $controller_proxy = new DocmanHTTPControllerProxy(
            \Mockery::mock(\EventManager::instance()),
            \Mockery::mock(ExternalLinkParametersExtractor::class),
            \Mockery::mock(Docman_HTTPController::class),
            \Mockery::mock(Docman_ItemDao::class)
        );

        $request = \Mockery::mock(\HTTPRequest::class);
        $project = \Mockery::mock(\Project::class);
        $project->shouldReceive('getID')->andReturn(null);
        $request->shouldReceive('getProject')->andReturn($project);

        $this->expectException(NotFoundException::class);
        $controller_proxy->process($request, UserTestBuilder::aUser()->build());
    }
}
