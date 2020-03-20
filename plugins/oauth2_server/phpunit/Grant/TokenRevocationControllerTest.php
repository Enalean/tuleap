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

namespace Tuleap\OAuth2Server\Grant;

use Laminas\HttpHandlerRunner\Emitter\EmitterInterface;
use Mockery as M;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use Tuleap\Authentication\SplitToken\SplitToken;
use Tuleap\Authentication\SplitToken\SplitTokenException;
use Tuleap\Authentication\SplitToken\SplitTokenIdentifierTranslator;
use Tuleap\Authentication\SplitToken\SplitTokenVerificationString;
use Tuleap\Http\HTTPFactoryBuilder;
use Tuleap\Http\Server\NullServerRequest;
use Tuleap\OAuth2Server\AccessToken\OAuth2AccessTokenRevocationVerifier;
use Tuleap\OAuth2Server\App\OAuth2App;
use Tuleap\OAuth2Server\Grant\AuthorizationCode\OAuth2AuthorizationCodeRevoker;
use Tuleap\User\OAuth2\OAuth2Exception;

final class TokenRevocationControllerTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var TokenRevocationController
     */
    private $controller;
    /**
     * @var M\LegacyMockInterface|M\MockInterface|SplitTokenIdentifierTranslator
     */
    private $access_token_unserializer;
    /**
     * @var M\LegacyMockInterface|M\MockInterface|OAuth2AccessTokenRevocationVerifier
     */
    private $access_token_verifier;
    /**
     * @var M\LegacyMockInterface|M\MockInterface|OAuth2AuthorizationCodeRevoker
     */
    private $authorization_code_revoker;

    protected function setUp(): void
    {
        $this->authorization_code_revoker = M::mock(OAuth2AuthorizationCodeRevoker::class);
        $this->access_token_unserializer  = M::mock(SplitTokenIdentifierTranslator::class);
        $this->access_token_verifier      = M::mock(OAuth2AccessTokenRevocationVerifier::class);
        $this->controller                 = new TokenRevocationController(
            HTTPFactoryBuilder::responseFactory(),
            HTTPFactoryBuilder::streamFactory(),
            $this->access_token_unserializer,
            $this->access_token_verifier,
            $this->authorization_code_revoker,
            M::mock(EmitterInterface::class)
        );
    }

    public function testRejectsRequestWhereTheClientHasNotBeenAuthenticated(): void
    {
        $response = $this->controller->handle(new NullServerRequest());

        $this->assertSame(401, $response->getStatusCode());
        $this->authorization_code_revoker->shouldNotHaveReceived('revokeForApp');
        $this->assertTrue($response->hasHeader('WWW-Authenticate'));
        $this->assertEquals('application/json;charset=UTF-8', $response->getHeaderLine('Content-Type'));
        $this->assertJsonStringEqualsJsonString('{"error":"invalid_client"}', $response->getBody()->getContents());
    }

    /**
     * @dataProvider dataProviderInvalidBody
     * @param array|null $parsed_body
     */
    public function testHandleReturnsErrorWhenDataIsInvalid($parsed_body): void
    {
        $request = $this->buildRequest()->withParsedBody($parsed_body);
        $response = $this->controller->handle($request);

        $this->assertSame(400, $response->getStatusCode());
        $this->authorization_code_revoker->shouldNotHaveReceived('revokeForApp');
        $this->assertEquals('application/json;charset=UTF-8', $response->getHeaderLine('Content-Type'));
        $this->assertJsonStringEqualsJsonString('{"error":"invalid_request"}', $response->getBody()->getContents());
    }

    public function dataProviderInvalidBody(): array
    {
        return [
            'No body'  => [null],
            'No token' => [['not_token' => 'invalid']]
        ];
    }

    public function testHandleSilentlyIgnoresBadlyFormattedToken(): void
    {
        $request = $this->buildRequest()->withParsedBody(['token' => 'valid_access_token']);
        $this->access_token_unserializer->shouldReceive('getSplitToken')
            ->once()
            ->andThrow(
                new class extends SplitTokenException {
                }
            );

        $response = $this->controller->handle($request);
        $this->assertSame(200, $response->getStatusCode());
        $this->authorization_code_revoker->shouldNotHaveReceived('revokeForApp');
    }

    public function testHandleSilentlyIgnoresTokenNotAssociatedToThisClientOrInvalidToken(): void
    {
        $request = $this->buildRequest()->withParsedBody(['token' => 'valid_access_token']);
        $this->access_token_unserializer->shouldReceive('getSplitToken')
            ->once()
            ->andReturn(new SplitToken(12, SplitTokenVerificationString::generateNewSplitTokenVerificationString()));
        $this->access_token_verifier->shouldReceive('getAssociatedAuthorizationCodeID')
            ->once()
            ->andThrow(
                new class extends \RuntimeException implements OAuth2Exception {
                }
            );

        $response = $this->controller->handle($request);
        $this->assertSame(200, $response->getStatusCode());
        $this->authorization_code_revoker->shouldNotHaveReceived('revokeForApp');
    }

    public function testHandleRevokesAccessTokenAndAssociatedAuthorizationCode(): void
    {
        $request = $this->buildRequest()->withParsedBody(['token' => 'valid_access_token']);
        $this->access_token_unserializer->shouldReceive('getSplitToken')
            ->once()
            ->andReturn(new SplitToken(12, SplitTokenVerificationString::generateNewSplitTokenVerificationString()));
        $this->access_token_verifier->shouldReceive('getAssociatedAuthorizationCodeID')
            ->once()
            ->andReturn(38);
        $this->authorization_code_revoker->shouldReceive('revokeByAuthCodeId')
            ->once()
            ->with(38);

        $response = $this->controller->handle($request);
        $this->assertSame(200, $response->getStatusCode());
    }

    private function buildRequest(): ServerRequestInterface
    {
        return (new NullServerRequest())->withAttribute(
            OAuth2ClientAuthenticationMiddleware::class,
            new OAuth2App(12, 'Client', 'https://example.com', false, new \Project(['group_id' => 102]))
        );
    }
}
