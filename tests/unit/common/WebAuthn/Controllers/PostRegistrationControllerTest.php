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

use Cose\Algorithms;
use GuzzleHttp\Psr7\ServerRequest;
use Laminas\HttpHandlerRunner\Emitter\SapiEmitter;
use Tuleap\Http\HTTPFactoryBuilder;
use Tuleap\Http\Response\JSONResponseBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Test\Stubs\ProvideCurrentUserStub;
use Tuleap\WebAuthn\WebAuthnCredentialSourceDaoStub;
use Webauthn\PublicKeyCredentialDescriptor;
use Webauthn\PublicKeyCredentialParameters;
use Webauthn\PublicKeyCredentialRpEntity;
use Webauthn\PublicKeyCredentialSourceRepository;
use function Psl\Encoding\Base64\encode;
use function Psl\Json\decode as psl_json_decode;

final class PostRegistrationControllerTest extends TestCase
{
    public function testItReturns401WhenNoAuth(): void
    {
        $controller = $this->getController(
            ProvideCurrentUserStub::buildWithUser(UserTestBuilder::anAnonymousUser()->build()),
            WebAuthnCredentialSourceDaoStub::withoutCredentialSources()
        );

        $res = $controller->handle(new ServerRequest('POST', '/webauthn/registration'));
        self::assertEquals(401, $res->getStatusCode());
    }

    public function testItReturnsOptions(): void
    {
        $controller = $this->getController(
            ProvideCurrentUserStub::buildWithUser(UserTestBuilder::anActiveUser()->build()),
            WebAuthnCredentialSourceDaoStub::withoutCredentialSources()
        );

        $res = $controller->handle(new ServerRequest('POST', '/webauthn/registration'));
        self::assertEquals(201, $res->getStatusCode());
        $body = psl_json_decode($res->getBody()->getContents());
        self::assertIsArray($body);
        self::assertArrayHasKey('rp', $body);
        self::assertIsArray($body['rp']);
        self::assertArrayHasKey('user', $body);
        self::assertIsArray($body['user']);
        self::assertArrayHasKey('challenge', $body);
        self::assertIsString($body['challenge']);
        self::assertArrayHasKey('pubKeyCredParams', $body);
        self::assertIsArray($body['pubKeyCredParams']);
        self::assertCount(1, $body['pubKeyCredParams']);
        self::assertSame('public-key', $body['pubKeyCredParams'][0]['type']);
        self::assertEquals(Algorithms::COSE_ALGORITHM_ES256, $body['pubKeyCredParams'][0]['alg']);
        self::assertArrayHasKey('attestation', $body);
        self::assertSame('none', $body['attestation']);
        self::assertArrayNotHasKey('excludeCredentials', $body);
    }

    public function testItListsExistingSourcesAsExcluded(): void
    {
        $controller = $this->getController(
            ProvideCurrentUserStub::buildWithUser(UserTestBuilder::anActiveUser()->build()),
            WebAuthnCredentialSourceDaoStub::withCredentialSources(['key_id'])
        );

        $res = $controller->handle(new ServerRequest('POST', '/webauthn/registration'));
        self::assertEquals(201, $res->getStatusCode());
        $body = psl_json_decode($res->getBody()->getContents());
        self::assertArrayHasKey('excludeCredentials', $body);
        self::assertIsArray($body['excludeCredentials']);
        self::assertSame(encode('key_id'), $body['excludeCredentials'][0]['id']);
    }

    public function testItGenerateDifferentChallenge(): void
    {
        $controller = $this->getController(
            ProvideCurrentUserStub::buildWithUser(UserTestBuilder::anActiveUser()->build()),
            WebAuthnCredentialSourceDaoStub::withoutCredentialSources()
        );

        $res   = $controller->handle(new ServerRequest('POST', '/webauthn/registration'));
        $body  = psl_json_decode($res->getBody()->getContents());
        $res2  = $controller->handle(new ServerRequest('POST', '/webauthn/registration'));
        $body2 = psl_json_decode($res2->getBody()->getContents());
        self::assertNotSame($body['challenge'], $body2['challenge']);
    }

    /**
     * @param string[] $sources_id
     */
    private function getController(
        ProvideCurrentUserStub $current_user_stub,
        PublicKeyCredentialSourceRepository $source_repository,
    ): PostRegistrationController {
        return new PostRegistrationController(
            $current_user_stub,
            $source_repository,
            new PublicKeyCredentialRpEntity(
                'tuleap',
                'tuleap.example.com',
            ),
            [
                new PublicKeyCredentialParameters(PublicKeyCredentialDescriptor::CREDENTIAL_TYPE_PUBLIC_KEY, Algorithms::COSE_ALGORITHM_ES256),
            ],
            HTTPFactoryBuilder::responseFactory(),
            new JSONResponseBuilder(HTTPFactoryBuilder::responseFactory(), HTTPFactoryBuilder::streamFactory()),
            new SapiEmitter()
        );
    }
}
