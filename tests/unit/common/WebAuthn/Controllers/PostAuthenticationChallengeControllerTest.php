<?php
/**
 * Copyright (c) Enalean, 2023-Present. All Rights Reserved.
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

namespace Tuleap\WebAuthn\Controllers;

use ParagonIE\ConstantTime\Base64UrlSafe;
use Psr\Http\Message\ResponseInterface;
use Tuleap\Http\HTTPFactoryBuilder;
use Tuleap\Http\Response\JSONResponseBuilder;
use Tuleap\Http\Response\RestlerErrorResponseBuilder;
use Tuleap\Http\Server\NullServerRequest;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\Helpers\NoopSapiEmitter;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Test\Stubs\ProvideAndRetrieveUserStub;
use Tuleap\Test\Stubs\WebAuthn\WebAuthnChallengeDaoStub;
use Tuleap\Test\Stubs\WebAuthn\WebAuthnCredentialSourceDaoStub;
use Tuleap\User\ProvideCurrentUser;
use Tuleap\User\RetrieveUserByUserName;
use Tuleap\WebAuthn\Challenge\SaveWebAuthnChallenge;
use Tuleap\WebAuthn\Source\GetAllCredentialSourceByUserId;
use function Psl\Json\decode as psl_json_decode;
use function Psl\Json\encode as psl_json_encode;

final class PostAuthenticationChallengeControllerTest extends TestCase
{
    /**
     * @dataProvider getTest400Data
     */
    public function testItReturnsError400ForAnonymous(
        string|array $body,
    ): void {
        $response = $this->handle(
            ProvideAndRetrieveUserStub::build(UserTestBuilder::anAnonymousUser()->build()),
            WebAuthnCredentialSourceDaoStub::withoutCredentialSources(),
            new WebAuthnChallengeDaoStub(),
            $body
        );

        self::assertSame(400, $response->getStatusCode());
    }

    public function testItReturns404WhenUserNotFound(): void
    {
        $response = $this->handle(
            ProvideAndRetrieveUserStub::build(UserTestBuilder::anAnonymousUser()->build()),
            WebAuthnCredentialSourceDaoStub::withoutCredentialSources(),
            new WebAuthnChallengeDaoStub(),
            ['username' => 'John']
        );

        self::assertSame(404, $response->getStatusCode());
    }

    public function testItReturns200WhenNoRegisteredKeyForAnonymous(): void
    {
        $user     = UserTestBuilder::anActiveUser()->build();
        $response = $this->handle(
            ProvideAndRetrieveUserStub::build(UserTestBuilder::anAnonymousUser()->build())->withUsers([$user]),
            WebAuthnCredentialSourceDaoStub::withoutCredentialSources(),
            new WebAuthnChallengeDaoStub(),
            ['username' => $user->getUserName()]
        );

        self::assertSame(200, $response->getStatusCode());
    }

    public function testItReturnsOptionsForAnonymousUser(): void
    {
        $user     = UserTestBuilder::anActiveUser()->build();
        $response = $this->handle(
            ProvideAndRetrieveUserStub::build(UserTestBuilder::anAnonymousUser()->build())->withUsers([$user]),
            WebAuthnCredentialSourceDaoStub::withCredentialSources('id1'),
            new WebAuthnChallengeDaoStub(),
            ['username' => $user->getUserName()]
        );

        self::assertSame(200, $response->getStatusCode());
        $this->checkResponseBody($response);
    }

    public function testItReturns403WhenNoRegisteredKeyForLoginUser(): void
    {
        $response = $this->handle(
            ProvideAndRetrieveUserStub::build(UserTestBuilder::anActiveUser()->build()),
            WebAuthnCredentialSourceDaoStub::withoutCredentialSources(),
            new WebAuthnChallengeDaoStub()
        );

        self::assertSame(403, $response->getStatusCode());
    }

    public function testItReturnsOptionsForLoginUSer(): void
    {
        $response = $this->handle(
            ProvideAndRetrieveUserStub::build(UserTestBuilder::anActiveUser()->build()),
            WebAuthnCredentialSourceDaoStub::withCredentialSources('id1'),
            new WebAuthnChallengeDaoStub()
        );

        self::assertSame(200, $response->getStatusCode());
        $this->checkResponseBody($response);
    }

    private function checkResponseBody(ResponseInterface $response): void
    {
        $body = psl_json_decode($response->getBody()->getContents());
        self::assertIsArray($body);
        self::assertArrayHasKey('challenge', $body);
        self::assertIsString($body['challenge']);
        self::assertArrayHasKey('allowCredentials', $body);
        $credentials = $body['allowCredentials'];
        self::assertIsArray($credentials);
        self::assertCount(1, $credentials);
        self::assertArrayHasKey('id', $credentials[0]);
        self::assertSame(Base64UrlSafe::encodeUnpadded('id1'), $credentials[0]['id']);
        self::assertArrayHasKey('type', $credentials[0]);
        self::assertSame('public-key', $credentials[0]['type']);
    }

    private function handle(
        ProvideCurrentUser&RetrieveUserByUserName $provide_current_user,
        GetAllCredentialSourceByUserId $source_dao,
        SaveWebAuthnChallenge $challenge_dao,
        string|array $body = '',
    ): ResponseInterface {
        $controller = $this->getController($provide_current_user, $source_dao, $challenge_dao);

        if (is_array($body)) {
            $body = psl_json_encode($body);
        }

        return $controller->handle(
            (new NullServerRequest())->withBody(HTTPFactoryBuilder::streamFactory()->createStream($body))
        );
    }

    private function getController(
        ProvideCurrentUser&RetrieveUserByUserName $provide_current_user,
        GetAllCredentialSourceByUserId $source_dao,
        SaveWebAuthnChallenge $challenge_dao,
    ): PostAuthenticationChallengeController {
        $json_response_builder = new JSONResponseBuilder(HTTPFactoryBuilder::responseFactory(), HTTPFactoryBuilder::streamFactory());

        return new PostAuthenticationChallengeController(
            $provide_current_user,
            $source_dao,
            $challenge_dao,
            $json_response_builder,
            new RestlerErrorResponseBuilder($json_response_builder),
            new NoopSapiEmitter()
        );
    }

    public static function getTest400Data(): iterable
    {
        yield 'It returns 400 when no body' => [
            'body' => '',
        ];
        yield 'It returns 400 when invalid json body' => [
            'body' => '{',
        ];
        yield 'It returns 400 when missing username' => [
            'body' => ['my_body'],
        ];
        yield 'It returns 400 when username is not string' => [
            'body' => [
                'username' => 42,
            ],
        ];
    }
}
