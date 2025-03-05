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

namespace Tuleap\OnlyOffice\Download;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Tuleap\Authentication\SplitToken\PrefixedSplitTokenSerializer;
use Tuleap\Authentication\SplitToken\SplitToken;
use Tuleap\Authentication\SplitToken\SplitTokenFormatter;
use Tuleap\Authentication\SplitToken\SplitTokenIdentifierTranslator;
use Tuleap\Authentication\SplitToken\SplitTokenVerificationString;
use Tuleap\Http\HTTPFactoryBuilder;
use Tuleap\Http\Server\AlwaysSuccessfulRequestHandler;
use Tuleap\Http\Server\NullServerRequest;
use Tuleap\Request\NotFoundException;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Test\Stubs\RetrieveUserByIdStub;
use Tuleap\User\RetrieveUserById;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class DownloadDocumentWithTokenMiddlewareTest extends TestCase
{
    public function testRequestWithAValidTokenSetsUserAndFileID(): void
    {
        $document_token_verifier = $this->createStub(OnlyOfficeDownloadDocumentTokenVerifier::class);
        $expected_user           = UserTestBuilder::buildWithDefaults();
        $middleware              = self::buildMiddleware(
            $document_token_verifier,
            RetrieveUserByIdStub::withUser($expected_user)
        );

        $token_identifier = self::buildTokenSerializer()->getIdentifier(new SplitToken(1, SplitTokenVerificationString::generateNewSplitTokenVerificationString()));
        $server_request   = (new NullServerRequest())->withQueryParams(['token' => $token_identifier->getString()]);

        $document_token_verifier->method('getDocumentDownloadTokenData')->willReturn(new DownloadDocumentTokenData((int) $expected_user->getId(), 11));

        $test_request_handler_expectations = function (ServerRequestInterface $request) use ($expected_user, $middleware): void {
            self::assertSame($expected_user, $middleware->getCurrentRequestUser($request));
            self::assertSame(11, $request->getAttribute('file_id'));
        };

        $request_handler = new class ($test_request_handler_expectations) implements RequestHandlerInterface {
            /**
             * @psalm-param callable(ServerRequestInterface):void $test_expectations
             */
            public function __construct(private $test_expectations)
            {
            }

            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                ($this->test_expectations)($request);
                return HTTPFactoryBuilder::responseFactory()->createResponse();
            }
        };

        $middleware->process($server_request, $request_handler);
    }

    public function testRequestWithAnIncorrectlyFormattedTokenIsRejected(): void
    {
        $middleware = self::buildMiddleware(
            $this->createStub(OnlyOfficeDownloadDocumentTokenVerifier::class),
            RetrieveUserByIdStub::withNoUser()
        );

        $server_request = (new NullServerRequest())->withQueryParams(['token' => 'wrong']);

        $this->expectException(NotFoundException::class);
        $middleware->process($server_request, new AlwaysSuccessfulRequestHandler(HTTPFactoryBuilder::responseFactory()));
    }

    public function testRequestWithAnUnknownTokenIsRejected(): void
    {
        $middleware = self::buildMiddleware(
            $this->createStub(OnlyOfficeDownloadDocumentTokenVerifier::class),
            RetrieveUserByIdStub::withNoUser()
        );

        $server_request = (new NullServerRequest())->withQueryParams(['token' => 'wrong']);

        $this->expectException(NotFoundException::class);
        $middleware->process($server_request, new AlwaysSuccessfulRequestHandler(HTTPFactoryBuilder::responseFactory()));
    }

    private static function buildMiddleware(
        OnlyOfficeDownloadDocumentTokenVerifier $document_token_verifier,
        RetrieveUserById $user_retriever,
    ): DownloadDocumentWithTokenMiddleware {
        return new DownloadDocumentWithTokenMiddleware(
            self::buildTokenSerializer(),
            $document_token_verifier,
            $user_retriever
        );
    }

    /**
     * @return SplitTokenFormatter&SplitTokenIdentifierTranslator
     */
    private static function buildTokenSerializer()
    {
        return new PrefixedSplitTokenSerializer(new PrefixOnlyOfficeDocumentDownload());
    }
}
