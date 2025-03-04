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
use Tuleap\Http\HTTPFactoryBuilder;
use Tuleap\Http\Response\JSONResponseBuilder;
use Tuleap\Http\Server\NullServerRequest;
use Tuleap\NeverThrow\Err;
use Tuleap\NeverThrow\Fault;
use Tuleap\NeverThrow\Ok;
use Tuleap\NeverThrow\Result;
use Tuleap\Option\Option;
use Tuleap\Test\DB\UUIDTestContext;
use Tuleap\Test\PHPUnit\TestCase;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class OnlyOfficeSaveControllerTest extends TestCase
{
    public function testReturnsSuccessResponseNoSaveTokenIsProvided(): void
    {
        $controller = self::buildController(
            Result::ok(
                Option::nothing(OnlyOfficeCallbackSaveResponseData::class)
            ),
            true,
        );

        $response = $controller->handle(new NullServerRequest());

        self::assertEquals(200, $response->getStatusCode());
        self::assertJsonStringEqualsJsonString('{"error":0}', $response->getBody()->getContents());
    }

    public function testReturnsSuccessResponseWhenNothingGoesWrong(): void
    {
        $controller = self::buildController(
            Result::ok(
                Option::fromValue(
                    new OnlyOfficeCallbackSaveResponseData('https://example.com/download', '7.2.0', [102])
                )
            ),
            true,
        );

        $response = $controller->handle(self::buildCallbackRequestWithSaveToken());

        self::assertEquals(200, $response->getStatusCode());
        self::assertJsonStringEqualsJsonString('{"error":0}', $response->getBody()->getContents());
    }

    public function testReturnsErrorResponseWhenCannotParseCallbackContent(): void
    {
        $controller = self::buildController(
            Result::err(Fault::fromMessage('Something bad happened')),
            true,
        );

        $response = $controller->handle(self::buildCallbackRequestWithSaveToken());

        self::assertEquals(200, $response->getStatusCode());
        self::assertJsonStringEqualsJsonString('{"error":1}', $response->getBody()->getContents());
    }

    public function testReturnsErrorResponseWhenCannotSaveTheDocument(): void
    {
        $controller = self::buildController(
            Result::ok(
                Option::fromValue(
                    new OnlyOfficeCallbackSaveResponseData('https://example.com/download', '7.2.0', [102])
                )
            ),
            false,
        );

        $response = $controller->handle(self::buildCallbackRequestWithSaveToken());

        self::assertEquals(200, $response->getStatusCode());
        self::assertJsonStringEqualsJsonString('{"error":1}', $response->getBody()->getContents());
    }

    /**
     * @psalm-param Ok<Option<OnlyOfficeCallbackSaveResponseData>>|Err<Fault> $parser_response
     */
    private static function buildController(
        Ok|Err $parser_response,
        bool $save_document_success,
    ): OnlyOfficeSaveController {
        return new OnlyOfficeSaveController(
            new class ($parser_response) implements OnlyOfficeCallbackResponseParser {
                /**
                 * @param Ok<Option<OnlyOfficeCallbackSaveResponseData>>|Err<Fault> $result
                 */
                public function __construct(
                    private Ok|Err $result,
                ) {
                }

                public function parseCallbackResponseContent(
                    string $response_content,
                    SaveDocumentTokenData $save_token_information,
                ): Ok|Err {
                    return $this->result;
                }
            },
            new class ($save_document_success) implements SaveOnlyOfficeCallbackDocument {
                public function __construct(private bool $is_success)
                {
                }

                public function saveDocument(
                    SaveDocumentTokenData $save_token_information,
                    Option $optional_response_data,
                ): Ok|Err {
                    if ($this->is_success) {
                        return Result::ok(null);
                    }

                    return Result::err(Fault::fromMessage('Could not save document'));
                }
            },
            new JSONResponseBuilder(HTTPFactoryBuilder::responseFactory(), HTTPFactoryBuilder::streamFactory()),
            new NullLogger(),
            new SapiEmitter(),
        );
    }

    private static function buildCallbackRequestWithSaveToken(): ServerRequestInterface
    {
        return (new NullServerRequest())->withAttribute(
            SaveDocumentTokenData::class,
            new SaveDocumentTokenData(1, 1, 1, new UUIDTestContext())
        );
    }
}
