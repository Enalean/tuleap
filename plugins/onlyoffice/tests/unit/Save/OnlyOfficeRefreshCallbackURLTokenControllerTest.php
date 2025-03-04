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

namespace Tuleap\OnlyOffice\Save;

use Laminas\HttpHandlerRunner\Emitter\SapiEmitter;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\NullLogger;
use Tuleap\Cryptography\ConcealedString;
use Tuleap\Http\HTTPFactoryBuilder;
use Tuleap\Http\Server\NullServerRequest;
use Tuleap\NeverThrow\Err;
use Tuleap\NeverThrow\Fault;
use Tuleap\NeverThrow\Ok;
use Tuleap\NeverThrow\Result;
use Tuleap\Test\PHPUnit\TestCase;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class OnlyOfficeRefreshCallbackURLTokenControllerTest extends TestCase
{
    public function testReturnsA200ResponseWhenAValidTokenIdentifierIsProvided(): void
    {
        $controller = self::buildController(Result::ok(null));

        $response = $controller->handle(self::buildServerRequest('https://example.com?token=valid_token_identifier'));

        self::assertEquals(204, $response->getStatusCode());
    }

    public function testReturnsA200ResponseWhenNoTokenIdentifierIsProvided(): void
    {
        $controller = self::buildController(Result::ok(null));

        $response = $controller->handle(self::buildServerRequest('https://example.com'));

        self::assertEquals(204, $response->getStatusCode());
    }

    public function testReturnsA400ResponseWhenProvidedTokenIdentifierIsInvalid(): void
    {
        $controller = self::buildController(Result::err(Fault::fromMessage('Expired token')));

        $response = $controller->handle(self::buildServerRequest('https://example.com?token=expired_token_identifier'));

        self::assertEquals(400, $response->getStatusCode());
    }

    /**
     * @param Ok<null>|Err<Fault> $token_refresher_return_value
     */
    private static function buildController(
        Ok|Err $token_refresher_return_value,
    ): OnlyOfficeRefreshCallbackURLTokenController {
        return new OnlyOfficeRefreshCallbackURLTokenController(
            new class ($token_refresher_return_value) implements OnlyOfficeSaveDocumentTokenRefresher {
                /**
                 * @param Ok<null>|Err<Fault> $token_refresher_return_value
                 */
                public function __construct(private Ok|Err $token_refresher_return_value)
                {
                }

                public function refreshToken(ConcealedString $raw_save_token, \DateTimeImmutable $now): Ok|Err
                {
                    return $this->token_refresher_return_value;
                }
            },
            new CallbackURLSaveTokenIdentifierExtractor(),
            HTTPFactoryBuilder::responseFactory(),
            new NullLogger(),
            new SapiEmitter(),
        );
    }

    private static function buildServerRequest(string $body_content): ServerRequestInterface
    {
        return (new NullServerRequest())->withBody(HTTPFactoryBuilder::streamFactory()->createStream($body_content));
    }
}
