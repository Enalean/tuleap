<?php
/**
 * Copyright (c) Enalean, 2022-Present. All Rights Reserved.
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

namespace Tuleap\Platform;

use Laminas\HttpHandlerRunner\Emitter\EmitterInterface;
use Tuleap\GlobalLanguageMock;
use Tuleap\Http\HTTPFactoryBuilder;
use Tuleap\Http\Server\NullServerRequest;
use Tuleap\Test\PHPUnit\TestCase;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class RobotsTxtControllerTest extends TestCase
{
    use GlobalLanguageMock;

    public function testSuccessfullyAnswersToRobotsTxtRequest(): void
    {
        $controller = new RobotsTxtController(
            HTTPFactoryBuilder::responseFactory(),
            HTTPFactoryBuilder::streamFactory(),
            $this->createStub(EmitterInterface::class)
        );

        $GLOBALS['Language']->method('getContent')->willReturn(__FILE__);

        $response = $controller->handle(new NullServerRequest());

        self::assertEquals(200, $response->getStatusCode());
        self::assertNotEmpty($response->getBody()->getContents());
    }
}
