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
use Tuleap\Test\Stubs\ProvideCurrentUserStub;
use Tuleap\Test\Stubs\WebAuthn\WebAuthnChallengeDaoStub;
use Tuleap\Test\Stubs\WebAuthn\WebAuthnCredentialSourceDaoStub;
use Tuleap\User\ProvideCurrentUser;
use Tuleap\WebAuthn\Challenge\SaveWebAuthnChallenge;
use Tuleap\WebAuthn\Source\GetAllCredentialSourceByUserId;
use function Psl\Json\decode as psl_json_decode;

final class PostAuthenticationChallengeControllerTest extends TestCase
{
    public function testItReturns401WhenNoAuth(): void
    {
        $response = $this->handle(
            ProvideCurrentUserStub::buildWithUser(UserTestBuilder::anAnonymousUser()->build()),
            WebAuthnCredentialSourceDaoStub::withoutCredentialSources(),
            new WebAuthnChallengeDaoStub()
        );

        self::assertSame(401, $response->getStatusCode());
    }

    public function testItReturns403WhenNoRegisteredKey(): void
    {
        $response = $this->handle(
            ProvideCurrentUserStub::buildWithUser(UserTestBuilder::anActiveUser()->build()),
            WebAuthnCredentialSourceDaoStub::withoutCredentialSources(),
            new WebAuthnChallengeDaoStub()
        );

        self::assertSame(403, $response->getStatusCode());
    }

    public function testItReturnsOptions(): void
    {
        $response = $this->handle(
            ProvideCurrentUserStub::buildWithUser(UserTestBuilder::anActiveUser()->build()),
            WebAuthnCredentialSourceDaoStub::withCredentialSources('id1'),
            new WebAuthnChallengeDaoStub()
        );

        self::assertSame(200, $response->getStatusCode());
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
        ProvideCurrentUser $provide_current_user,
        GetAllCredentialSourceByUserId $source_dao,
        SaveWebAuthnChallenge $challenge_dao,
    ): ResponseInterface {
        $controller = $this->getController($provide_current_user, $source_dao, $challenge_dao);

        return $controller->handle(new NullServerRequest());
    }

    private function getController(
        ProvideCurrentUser $provide_current_user,
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
}
