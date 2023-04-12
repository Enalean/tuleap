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

use CBOR\IndefiniteLengthByteStringObject;
use CBOR\IndefiniteLengthMapObject;
use CBOR\MapObject;
use CBOR\TextStringObject;
use ParagonIE\ConstantTime\Base64UrlSafe;
use Psl\Hash\Algorithm;
use Psr\Http\Message\ResponseInterface;
use Tuleap\Http\HTTPFactoryBuilder;
use Tuleap\Http\Server\NullServerRequest;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\Helpers\NoopSapiEmitter;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Test\Stubs\ProvideCurrentUserStub;
use Tuleap\User\ProvideCurrentUser;
use Webauthn\AttestationStatement\AttestationObjectLoader;
use Webauthn\AttestationStatement\AttestationStatement;
use Webauthn\AttestationStatement\AttestationStatementSupportManager;
use Webauthn\AttestationStatement\NoneAttestationStatementSupport;
use Webauthn\PublicKeyCredentialLoader;
use function Psl\Encoding\Base64\encode;
use function Psl\Json\encode as psl_json_encode;

final class PostRegistrationControllerTest extends TestCase
{
    public function testItReturns401WhenNoAuth(): void
    {
        $response = $this->handle(ProvideCurrentUserStub::buildWithUser(UserTestBuilder::anAnonymousUser()->build()));

        self::assertSame(401, $response->getStatusCode());
        self::assertSame('Unauthorized', $response->getReasonPhrase());
    }

    /**
     * @dataProvider getTest400Data
     */
    public function testItReturnsError400(
        string|array $body,
        string $error_message,
    ): void {
        $response = $this->handle(
            ProvideCurrentUserStub::buildWithUser(UserTestBuilder::anActiveUser()->build()),
            $body
        );

        self::assertSame(400, $response->getStatusCode());
        self::assertSame($error_message, $response->getReasonPhrase());
    }

    public function testItReturns501(): void
    {
        $response = $this->handle(
            ProvideCurrentUserStub::buildWithUser(UserTestBuilder::anActiveUser()->build()),
            [
                'response' => $this->generateResponse('challenge'),
                'name' => 'name of passkey',
            ]
        );

        self::assertSame('Not Implemented', $response->getReasonPhrase());
        self::assertSame(501, $response->getStatusCode());
    }

    // Above, tests functions
    // _.-._.-._.-._.-._.-._.-._.-._.-._.-._.-._.-._.-._.-._.-._.-._.-._.-._.-._.-._.-.
    // Below, utility functions

    private function handle(
        ProvideCurrentUser $provide_current_user,
        string|array $body = '',
    ): ResponseInterface {
        $controller = $this->getController($provide_current_user);

        if (is_array($body)) {
            $body = psl_json_encode($body);
        }

        return $controller->handle(
            (new NullServerRequest())->withBody(HTTPFactoryBuilder::streamFactory()->createStream($body))
        );
    }

    private function getController(
        ProvideCurrentUser $provide_current_user,
    ): PostRegistrationController {
        $attestation_statement_manager = new AttestationStatementSupportManager();
        $attestation_statement_manager->add(new NoneAttestationStatementSupport());

        return new PostRegistrationController(
            $provide_current_user,
            new PublicKeyCredentialLoader(
                new AttestationObjectLoader($attestation_statement_manager)
            ),
            HTTPFactoryBuilder::responseFactory(),
            new NoopSapiEmitter()
        );
    }

    public static function getTest400Data(): iterable
    {
        yield 'It returns 400 when no body' => [
            'body' => '',
            'error_message' => 'Request body is empty',
        ];
        yield 'It returns 400 when invalid json body' => [
            'body' => '{',
            'error_message' => 'Request body is not well formed',
        ];
        yield 'It returns 400 when missing response' => [
            'body' => ['my_body'],
            'error_message' => '"response" field is missing from the request body',
        ];
        yield 'It returns 400 when missing name' => [
            'body' => [
                'response' => ['some data'],
            ],
            'error_message' => '"name" field is missing from the request body',
        ];
        yield 'It returns 400 when response is not array' => [
            'body' => [
                'response' => 'not an array',
                'name' => 'name of passkey',
            ],
            'error_message' => 'Request body is not well formed',
        ];
        yield 'It returns 400 when name is not string' => [
            'body' => [
                'response' => [],
                'name' => -1,
            ],
            'error_message' => 'Request body is not well formed',
        ];
        yield 'It returns 400 when invalid response' => [
            'body' => [
                'response' => ['invalid data'],
                'name' => 'name of passkey',
            ],
            'error_message' => 'The result of passkey is not well formed',
        ];
    }

    private function generateResponse(string $challenge): array
    {
        $id                    = 'passkey id';
        $crypted_challenge     = $challenge;
        $relying_party_id      = 'https://example.com';
        $client_data_json      = Base64UrlSafe::encodeUnpadded(psl_json_encode([
            'type' => 'webauthn.create',
            'challenge' => Base64UrlSafe::encodeUnpadded($crypted_challenge),
            'origin' => $relying_party_id,
        ]));
        $relying_party_id_hash = hex2bin(\Psl\Hash\hash($relying_party_id, Algorithm::SHA256));
        $flags                 = (string) 0b0000101;
        $sign_count            = '0000';
        // see https://w3c.github.io/webauthn/#attestation-object
        $attestation_object = IndefiniteLengthMapObject::create()
            ->add(TextStringObject::create('fmt'), TextStringObject::create(AttestationStatement::TYPE_NONE))
            ->add(TextStringObject::create('attStmt'), MapObject::create([]))
            ->add(
                TextStringObject::create('authData'),
                IndefiniteLengthByteStringObject::create()
                    // see https://w3c.github.io/webauthn/#authenticator-data
                    ->append($relying_party_id_hash)
                    ->append($flags)
                    ->append($sign_count)
            );

        return [
            'id' => Base64UrlSafe::encodeUnpadded($id),
            'rawId' => encode($id),
            'type' => 'public-key',
            'response' => [
                'attestationObject' => encode((string) $attestation_object),
                'clientDataJSON' => $client_data_json,
            ],
        ];
    }
}
