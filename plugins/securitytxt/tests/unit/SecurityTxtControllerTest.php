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

namespace Tuleap\SecurityTxt;

use Laminas\HttpHandlerRunner\Emitter\SapiEmitter;
use Tuleap\ForgeConfigSandbox;
use Tuleap\Http\HTTPFactoryBuilder;
use Tuleap\Http\Server\NullServerRequest;
use Tuleap\ServerHostname;
use Tuleap\Test\PHPUnit\TestCase;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class SecurityTxtControllerTest extends TestCase
{
    use ForgeConfigSandbox;

    private SecurityTxtController $controller;

    protected function setUp(): void
    {
        $this->controller = new SecurityTxtController(
            HTTPFactoryBuilder::responseFactory(),
            HTTPFactoryBuilder::streamFactory(),
            new SapiEmitter()
        );
    }

    public function testDisplaysSecurityTxtFile(): void
    {
        \ForgeConfig::set(SecurityTxtOptions::CONTACT, 'mailto:security@example.com');
        \ForgeConfig::set(ServerHostname::DEFAULT_DOMAIN, 'tuleap.example.com');

        $response = $this->controller->handle(new NullServerRequest());

        self::assertEquals(200, $response->getStatusCode());

        $content = $response->getBody()->getContents();

        self::assertStringContainsString('Contact: mailto:security@example.com', $content);
        self::assertStringContainsString('Canonical: https://tuleap.example.com/.well-known/security.txt', $content);
        self::assertStringContainsString('Expires: ', $content);
    }

    public function testNotFoundWhenNoPrimaryContactIsDefined(): void
    {
        $response = $this->controller->handle(new NullServerRequest());

        self::assertEquals(404, $response->getStatusCode());
    }
}
