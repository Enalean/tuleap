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

use Psr\Http\Message\ResponseInterface;
use Tuleap\Http\HTTPFactoryBuilder;
use Tuleap\Http\Response\JSONResponseBuilder;
use Tuleap\Http\Response\RestlerErrorResponseBuilder;
use Tuleap\Http\Server\NullServerRequest;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\Helpers\NoopSapiEmitter;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Test\Stubs\ProvideCurrentUserStub;
use Tuleap\Test\Stubs\WebAuthn\PasskeyStub;
use Tuleap\User\ProvideCurrentUser;
use Webauthn\AttestationStatement\AttestationObjectLoader;
use Webauthn\AttestationStatement\AttestationStatementSupportManager;
use Webauthn\AttestationStatement\NoneAttestationStatementSupport;
use Webauthn\PublicKeyCredentialLoader;
use function Psl\Json\encode as psl_json_encode;

final class PostAuthenticationControllerTest extends TestCase
{
    public function testItReturns401WhenNoAuth(): void
    {
        $response = $this->handle(ProvideCurrentUserStub::buildWithUser(UserTestBuilder::anAnonymousUser()->build()));

        self::assertSame(401, $response->getStatusCode());
    }

    /**
     * @dataProvider getTest400Data
     */
    public function testItReturns400(
        string|array $body,
    ): void {
        $response = $this->handle(
            ProvideCurrentUserStub::buildWithUser(UserTestBuilder::anActiveUser()->build()),
            $body
        );

        self::assertSame(400, $response->getStatusCode());
    }

    public function testItReturns501(): void
    {
        $passkey = new PasskeyStub();

        $response = $this->handle(
            ProvideCurrentUserStub::buildWithUser(UserTestBuilder::anActiveUser()->build()),
            $passkey->generateAssertionResponse('challenge')
        );

        self::assertSame(501, $response->getStatusCode());
    }

    private function handle(
        ProvideCurrentUser $user_manager,
        string|array $body = '',
    ): ResponseInterface {
        $controller = $this->getController($user_manager);

        if (is_array($body)) {
            $body = psl_json_encode($body);
        }

        return $controller->handle(
            (new NullServerRequest())->withBody(HTTPFactoryBuilder::streamFactory()->createStream($body))
        );
    }

    private function getController(
        ProvideCurrentUser $user_manager,
    ): PostAuthenticationController {
        $attestation_statement_manager = new AttestationStatementSupportManager();
        $attestation_statement_manager->add(new NoneAttestationStatementSupport());
        $json_response_builder = new JSONResponseBuilder(HTTPFactoryBuilder::responseFactory(), HTTPFactoryBuilder::streamFactory());

        return new PostAuthenticationController(
            $user_manager,
            new PublicKeyCredentialLoader(
                new AttestationObjectLoader($attestation_statement_manager)
            ),
            new RestlerErrorResponseBuilder($json_response_builder),
            new NoopSapiEmitter()
        );
    }

    public static function getTest400Data(): iterable
    {
        yield 'It returns 400 when body is empty' => [
            'body' => '',
        ];
        yield 'It returns 400 when invalid body' => [
            'body' => 'content',
        ];
        yield 'It returns 400 when invalid json body' => [
            'body' => '{',
        ];
        yield 'It returns 400 when body is not passkey response' => [
            'body' => [
                'key' => 'value',
            ],
        ];
        $passkey = new PasskeyStub();
        yield 'It returns 400 when response is attestation' => [
            'body' => $passkey->generateAttestationResponse('challenge'),
        ];
    }
}
