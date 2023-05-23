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

namespace Tuleap\OAuth2ServerCore\Grant;

use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use ColinODell\PsrTestLogger\TestLogger;
use Tuleap\Authentication\SplitToken\SplitToken;
use Tuleap\Authentication\SplitToken\SplitTokenException;
use Tuleap\Authentication\SplitToken\SplitTokenIdentifierTranslator;
use Tuleap\Authentication\SplitToken\SplitTokenVerificationString;
use Tuleap\Http\HTTPFactoryBuilder;
use Tuleap\Http\Server\Authentication\BasicAuthLoginExtractor;
use Tuleap\Http\Server\NullServerRequest;
use Tuleap\OAuth2ServerCore\App\OAuth2App;
use Tuleap\OAuth2ServerCore\App\OAuth2AppCredentialVerifier;
use Tuleap\OAuth2ServerCore\OAuth2ServerException;
use Tuleap\Test\Builders\ProjectTestBuilder;

final class OAuth2ClientAuthenticationMiddlewareTest extends \Tuleap\Test\PHPUnit\TestCase
{
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&SplitTokenIdentifierTranslator
     */
    private $client_secret_unserializer;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&OAuth2AppCredentialVerifier
     */
    private $verifier;
    /**
     * @var TestLogger
     */
    private $logger;
    /**
     * @var OAuth2ClientAuthenticationMiddleware
     */
    private $middleware;

    protected function setUp(): void
    {
        $this->client_secret_unserializer = $this->createMock(SplitTokenIdentifierTranslator::class);
        $this->verifier                   = $this->createMock(OAuth2AppCredentialVerifier::class);
        $this->logger                     = new TestLogger();
        $this->middleware                 = new OAuth2ClientAuthenticationMiddleware(
            $this->client_secret_unserializer,
            $this->verifier,
            new BasicAuthLoginExtractor(),
            $this->logger
        );
    }

    public function testAuthenticatedAppIsSetAsAttributeOfTheRequest(): void
    {
        $incoming_server_request = (new NullServerRequest())
            ->withHeader('Authorization', 'Basic ' . base64_encode('tlp-client-id-1:client_secret'));

        $this->client_secret_unserializer->method('getSplitToken')->willReturn(
            new SplitToken(1, SplitTokenVerificationString::generateNewSplitTokenVerificationString())
        );

        $expected_app = new OAuth2App(1, 'Name', 'https://example.com', true, ProjectTestBuilder::aProject()->build());
        $this->verifier->method('getApp')->willReturn($expected_app);

        $handler = $this->buildHandler($expected_app);

        $this->middleware->process($incoming_server_request, $handler);
    }

    public function testRequestIsGivenToTheHandlerWithoutAnAppWhenNoBasicAuthCredentialsAreSet(): void
    {
        $incoming_server_request = new NullServerRequest();

        $handler = $this->buildHandler(null);

        $this->middleware->process($incoming_server_request, $handler);
        $this->assertTrue($this->logger->hasDebugRecords());
    }

    public function testRequestIsGivenToTheHandlerWithoutAnAppWhenClientIdentifierIsNotValid(): void
    {
        $incoming_server_request = (new NullServerRequest())
            ->withHeader('Authorization', 'Basic ' . base64_encode('not_valid_client_id:client_secret'));

        $handler = $this->buildHandler(null);

        $this->middleware->process($incoming_server_request, $handler);
        $this->assertTrue($this->logger->hasDebugRecords());
    }

    public function testRequestIsGivenToTheHandlerWithoutAnAppWhenClientSecretCanNotBeUnserialized(): void
    {
        $incoming_server_request = (new NullServerRequest())
            ->withHeader('Authorization', 'Basic ' . base64_encode('tlp-client-id-1:wrong_format_client_secret'));

        $this->client_secret_unserializer->method('getSplitToken')->willThrowException(
            new class extends SplitTokenException {
            }
        );

        $handler = $this->buildHandler(null);

        $this->middleware->process($incoming_server_request, $handler);
        $this->assertTrue($this->logger->hasDebugRecords());
    }

    public function testRequestIsGivenToTheHandlerWithoutAnAppWhenTheAppCannotBeVerifiedFromTheCredentials(): void
    {
        $incoming_server_request = (new NullServerRequest())
            ->withHeader('Authorization', 'Basic ' . base64_encode('tlp-client-id-3:client_secret'));

        $this->client_secret_unserializer->method('getSplitToken')->willReturn(
            new SplitToken(3, SplitTokenVerificationString::generateNewSplitTokenVerificationString())
        );

        $this->verifier->method('getApp')->willThrowException(
            new class extends \RuntimeException implements OAuth2ServerException
            {
            }
        );

        $handler = $this->buildHandler(null);

        $this->middleware->process($incoming_server_request, $handler);
    }

    public function testDoesNotTryToHandleCodeStateIssueWhenVerifyingTheApp(): void
    {
        $incoming_server_request = (new NullServerRequest())
            ->withHeader('Authorization', 'Basic ' . base64_encode('tlp-client-id-4:client_secret'));

        $this->client_secret_unserializer->method('getSplitToken')->willReturn(
            new SplitToken(4, SplitTokenVerificationString::generateNewSplitTokenVerificationString())
        );

        $exception = new \LogicException('State is not consistent with the code');
        $this->verifier->method('getApp')->willThrowException($exception);

        $handler = $this->buildHandler(null);

        $this->expectExceptionObject($exception);
        $this->middleware->process($incoming_server_request, $handler);
    }

    private function buildHandler(?OAuth2App $expected_app_attribute): RequestHandlerInterface
    {
        return new class (HTTPFactoryBuilder::responseFactory(), $expected_app_attribute) implements RequestHandlerInterface
        {
            /**
             * @var ResponseFactoryInterface
             */
            private $response_factory;
            /**
             * @var OAuth2App|null
             */
            private $expected_app_attribute;

            public function __construct(
                ResponseFactoryInterface $response_factory,
                ?OAuth2App $expected_app_attribute,
            ) {
                $this->response_factory       = $response_factory;
                $this->expected_app_attribute = $expected_app_attribute;
            }

            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                TestCase::assertSame($this->expected_app_attribute, $request->getAttribute(OAuth2ClientAuthenticationMiddleware::class));
                return $this->response_factory->createResponse();
            }
        };
    }
}
