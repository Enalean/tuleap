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

use Cose\Algorithm\Manager;
use Cose\Algorithm\Signature\ECDSA\ES256;
use Cose\Algorithm\Signature\EdDSA\Ed25519;
use Cose\Algorithm\Signature\RSA\RS256;
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
use Tuleap\Test\Stubs\WebAuthn\WebAuthnChallengeDaoStub;
use Tuleap\Test\Stubs\WebAuthn\WebAuthnCredentialSourceDaoStub;
use Tuleap\User\ProvideCurrentUser;
use Tuleap\WebAuthn\Challenge\RetrieveWebAuthnChallenge;
use Tuleap\WebAuthn\Source\GetAllCredentialSourceByUserId;
use Webauthn\AttestationStatement\AttestationObjectLoader;
use Webauthn\AttestationStatement\AttestationStatementSupportManager;
use Webauthn\AttestationStatement\NoneAttestationStatementSupport;
use Webauthn\AuthenticationExtensions\ExtensionOutputCheckerHandler;
use Webauthn\AuthenticatorAssertionResponseValidator;
use Webauthn\PublicKeyCredentialLoader;
use Webauthn\PublicKeyCredentialRpEntity;
use Webauthn\PublicKeyCredentialSourceRepository;
use function Psl\Json\encode as psl_json_encode;

final class PostAuthenticationControllerTest extends TestCase
{
    public function testItReturns401WhenNoAuth(): void
    {
        $response = $this->handle(
            ProvideCurrentUserStub::buildWithUser(UserTestBuilder::anAnonymousUser()->build()),
            WebAuthnCredentialSourceDaoStub::withoutCredentialSources(),
        );

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
            WebAuthnCredentialSourceDaoStub::withCredentialSources('id1'),
            $body
        );

        self::assertSame(400, $response->getStatusCode());
    }

    public function testItReturns400WhenAssertionIsInvalid(): void
    {
        $current_user_stub = ProvideCurrentUserStub::buildWithUser(UserTestBuilder::anActiveUser()->build());
        $challenge_dao     = new WebAuthnChallengeDaoStub();
        $challenge_dao->saveChallenge(
            (int) $current_user_stub->getCurrentUser()->getId(),
            'myChallenge'
        );
        $passkey = new PasskeyStub();

        $response = $this->handle(
            $current_user_stub,
            WebAuthnCredentialSourceDaoStub::withCredentialSources('id1'),
            $passkey->generateAssertionResponse('anotherChallenge'),
            $challenge_dao
        );

        self::assertSame(400, $response->getStatusCode());
    }

    public function testItReturns200(): void
    {
        $current_user_stub = ProvideCurrentUserStub::buildWithUser(UserTestBuilder::anActiveUser()->build());
        $challenge_dao     = new WebAuthnChallengeDaoStub();
        $challenge         = 'myChallenge';
        $user_id           = $current_user_stub->getCurrentUser()->getId();
        $challenge_dao->saveChallenge(
            (int) $user_id,
            $challenge
        );
        $passkey_id = 'passkeyId';
        $passkey    = new PasskeyStub($passkey_id);
        $source     = $passkey->getCredentialSource((string) $user_id);

        $response = $this->handle(
            $current_user_stub,
            WebAuthnCredentialSourceDaoStub::withoutCredentialSources()
                ->withRealSource($source),
            $passkey->generateAssertionResponse($challenge),
            $challenge_dao
        );

        self::assertSame(200, $response->getStatusCode());
    }

    private function handle(
        ProvideCurrentUser $user_manager,
        GetAllCredentialSourceByUserId & PublicKeyCredentialSourceRepository $source_dao,
        string|array $body = '',
        RetrieveWebAuthnChallenge $challenge_dao = new WebAuthnChallengeDaoStub(),
    ): ResponseInterface {
        $controller = $this->getController(
            $user_manager,
            $source_dao,
            $challenge_dao
        );

        if (is_array($body)) {
            $body = psl_json_encode($body);
        }

        return $controller->handle(
            (new NullServerRequest())->withBody(HTTPFactoryBuilder::streamFactory()->createStream($body))
        );
    }

    private function getController(
        ProvideCurrentUser $user_manager,
        GetAllCredentialSourceByUserId & PublicKeyCredentialSourceRepository $source_dao,
        RetrieveWebAuthnChallenge $challenge_dao,
    ): PostAuthenticationController {
        $attestation_statement_manager = new AttestationStatementSupportManager();
        $attestation_statement_manager->add(new NoneAttestationStatementSupport());
        $response_factory      = HTTPFactoryBuilder::responseFactory();
        $json_response_builder = new JSONResponseBuilder($response_factory, HTTPFactoryBuilder::streamFactory());

        return new PostAuthenticationController(
            $user_manager,
            $source_dao,
            $challenge_dao,
            new PublicKeyCredentialRpEntity('tuleap', 'example.com'),
            new PublicKeyCredentialLoader(
                new AttestationObjectLoader($attestation_statement_manager)
            ),
            new AuthenticatorAssertionResponseValidator(
                $source_dao,
                null,
                new ExtensionOutputCheckerHandler(),
                Manager::create()
                    ->add(
                        Ed25519::create(),
                        RS256::create(),
                        ES256::create()
                    )
            ),
            $response_factory,
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
        yield 'It returns 400 when there is no stored challenge' => [
            'body' => $passkey->generateAssertionResponse('challenge'),
        ];
    }
}
