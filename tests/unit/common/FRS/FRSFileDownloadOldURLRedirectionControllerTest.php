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

use Laminas\HttpHandlerRunner\Emitter\EmitterInterface;
use Tuleap\Http\HTTPFactoryBuilder;
use Tuleap\Http\Server\NullServerRequest;
use Tuleap\Test\PHPUnit\TestCase;

final class FRSFileDownloadOldURLRedirectionControllerTest extends TestCase
{
    public function testOldURLsAreRedirectedToTheNewURL(): void
    {
        $controller = new FRSFileDownloadOldURLRedirectionController(
            HTTPFactoryBuilder::responseFactory(),
            $this->createMock(EmitterInterface::class)
        );

        $server_request = (new NullServerRequest())->withAttribute('file_id', '12');

        $response = $controller->handle($server_request);

        self::assertEquals(301, $response->getStatusCode());
        self::assertEquals('/file/download/12', $response->getHeaderLine('Location'));
    }
}
