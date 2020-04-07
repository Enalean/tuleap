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

namespace Tuleap\FRS;

use Mockery;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use Tuleap\Http\HTTPFactoryBuilder;
use Laminas\HttpHandlerRunner\Emitter\EmitterInterface;

final class FRSFileDownloadOldURLRedirectionControllerTest extends TestCase
{
    use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    public function testOldURLsAreRedirectedToTheNewURL(): void
    {
        $controller = new FRSFileDownloadOldURLRedirectionController(
            HTTPFactoryBuilder::responseFactory(),
            Mockery::mock(EmitterInterface::class)
        );

        $server_request = Mockery::mock(ServerRequestInterface::class);
        $server_request->shouldReceive('getAttribute')->with('file_id')->andReturn('12');

        $response = $controller->handle($server_request);

        $this->assertEquals(301, $response->getStatusCode());
        $this->assertEquals('/file/download/12', $response->getHeaderLine('Location'));
    }
}
