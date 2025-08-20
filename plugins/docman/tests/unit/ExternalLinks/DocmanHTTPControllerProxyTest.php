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
use HTTPRequest;
use Project;
use Tuleap\Request\NotFoundException;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class DocmanHTTPControllerProxyTest extends TestCase
{
    public function testThrowsNotFoundWhenProjectCannotBeIdentifierFromTheRequest(): void
    {
        $controller_proxy = new DocmanHTTPControllerProxy(
            $this->createMock(ExternalLinkParametersExtractor::class),
            $this->createMock(Docman_HTTPController::class),
            $this->createMock(Docman_ItemDao::class)
        );

        $request = new HTTPRequest();
        $project = $this->createMock(Project::class);
        $project->method('getID')->willReturn(null);
        $request->setProject($project);

        $this->expectException(NotFoundException::class);
        $controller_proxy->process($request, UserTestBuilder::aUser()->build());
    }
}
