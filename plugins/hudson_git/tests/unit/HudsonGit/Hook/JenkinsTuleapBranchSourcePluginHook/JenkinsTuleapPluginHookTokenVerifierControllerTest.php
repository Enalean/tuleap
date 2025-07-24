<?php
/**
 * Copyright (c) Enalean, 2022 - Present. All Rights Reserved.
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

namespace Tuleap\HudsonGit\Hook\JenkinsTuleapBranchSourcePluginHook;

use DateTimeImmutable;
use Laminas\HttpHandlerRunner\Emitter\EmitterInterface;
use Tuleap\Cryptography\ConcealedString;
use Tuleap\Http\HTTPFactoryBuilder;
use Tuleap\Http\Server\NullServerRequest;
use Tuleap\Test\PHPUnit\TestCase;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class JenkinsTuleapPluginHookTokenVerifierControllerTest extends TestCase
{
    public function testSuccessfulRequest(): void
    {
        $controller = $this->buildController(true);

        $response = $controller->handle((new NullServerRequest())->withBody(HTTPFactoryBuilder::streamFactory()->createStream('valid_token')));

        self::assertEquals(204, $response->getStatusCode());
    }

    public function testFailedRequest(): void
    {
        $controller = $this->buildController(false);

        $response = $controller->handle((new NullServerRequest())->withBody(HTTPFactoryBuilder::streamFactory()->createStream('invalid_token')));

        self::assertEquals(403, $response->getStatusCode());
    }

    private function buildController(bool $is_token_valid): JenkinsTuleapPluginHookTokenVerifierController
    {
        return new JenkinsTuleapPluginHookTokenVerifierController(
            HTTPFactoryBuilder::responseFactory(),
            new class ($is_token_valid) implements JenkinsTuleapPluginHookTokenVerifier
            {
                public function __construct(private bool $is_valid)
                {
                }

                #[\Override]
                public function isTriggerTokenValid(ConcealedString $trigger_token, DateTimeImmutable $now): bool
                {
                    return $this->is_valid;
                }
            },
            $this->createStub(EmitterInterface::class)
        );
    }
}
