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
use Psr\Http\Message\ResponseInterface;
use Tuleap\Http\HTTPFactoryBuilder;
use Tuleap\Http\Response\JSONResponseBuilder;
use Tuleap\Http\Response\RestlerErrorResponseBuilder;
use Tuleap\Http\Server\NullServerRequest;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\Helpers\NoopSapiEmitter;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Test\Stubs\CSRFSynchronizerTokenStub;
use Tuleap\Test\Stubs\FeedbackSerializerStub;
use Tuleap\Test\Stubs\ProvideCurrentUserStub;
use Tuleap\Test\Stubs\WebAuthn\PasskeyStub;
use Tuleap\Test\Stubs\WebAuthn\WebAuthnChallengeDaoStub;
use Tuleap\Test\Stubs\WebAuthn\WebAuthnCredentialSourceDaoStub;
use Tuleap\User\ProvideCurrentUser;
use Tuleap\WebAuthn\Challenge\RetrieveWebAuthnChallenge;
use Webauthn\AttestationStatement\AttestationObjectLoader;
use Webauthn\AttestationStatement\AttestationStatementSupportManager;
use Webauthn\AttestationStatement\NoneAttestationStatementSupport;
use Webauthn\AuthenticationExtensions\ExtensionOutputCheckerHandler;
use Webauthn\AuthenticatorAttestationResponseValidator;
use Webauthn\PublicKeyCredentialDescriptor;
use Webauthn\PublicKeyCredentialLoader;
use Webauthn\PublicKeyCredentialParameters;
use Webauthn\PublicKeyCredentialRpEntity;
use function Psl\Json\encode as psl_json_encode;

final class PostRegistrationControllerTest extends TestCase
{
    private WebAuthnCredentialSourceDaoStub $source_dao;
    private FeedbackSerializerStub $serializer;
    private CSRFSynchronizerTokenStub $synchronizer_token_stub;

    protected function setUp(): void
    {
        $this->source_dao              = WebAuthnCredentialSourceDaoStub::withoutCredentialSources();
        $this->serializer              = FeedbackSerializerStub::buildSelf();
        $this->synchronizer_token_stub = CSRFSynchronizerTokenStub::buildSelf();
    }

    public function testItReturns401WhenNoAuth(): void
    {
        $response = $this->handle(ProvideCurrentUserStub::buildWithUser(UserTestBuilder::anAnonymousUser()->build()));

        self::assertSame(401, $response->getStatusCode());
    }

    /**
     * @dataProvider getTest400Data
     */
    public function testItReturnsError400(
        string|array $body,
    ): void {
        $response = $this->handle(
            ProvideCurrentUserStub::buildWithUser(UserTestBuilder::anActiveUser()->build()),
            $body,
            new WebAuthnChallengeDaoStub()
        );

        self::assertSame(400, $response->getStatusCode());
    }

    public function testItReturns400WhenResponseIsWrong(): void
    {
        $challenge_dao = new WebAuthnChallengeDaoStub();
        $user_provider = ProvideCurrentUserStub::buildWithUser(UserTestBuilder::anActiveUser()->build());
        $challenge     = 'challenge';
        $challenge_dao->saveChallenge((int) $user_provider->getCurrentUser()->getId(), $challenge);

        $passkey  = new PasskeyStub();
        $response = $this->handle(
            $user_provider,
            [
                'response' => $passkey->generateAttestationResponse('wrong challenge'),
                'name' => 'name of passkey',
            ],
            $challenge_dao
        );

        self::assertSame(400, $response->getStatusCode());
    }

    public function testItReturns200(): void
    {
        $challenge_dao = new WebAuthnChallengeDaoStub();
        $user_provider = ProvideCurrentUserStub::buildWithUser(UserTestBuilder::anActiveUser()->build());
        $challenge     = 'challenge';
        $challenge_dao->saveChallenge((int) $user_provider->getCurrentUser()->getId(), $challenge);
        $passkey_name = 'My-awesome-key';

        $passkey  = new PasskeyStub();
        $response = $this->handle(
            $user_provider,
            [
                'response' => $passkey->generateAttestationResponse($challenge),
                'name' => $passkey_name,
                'csrf_token' => 'some token',
            ],
            $challenge_dao
        );

        self::assertSame(200, $response->getStatusCode());
        self::assertSame('OK', $response->getReasonPhrase());
        self::assertCount(1, $this->source_dao->sources_id);
        self::assertCount(1, $this->source_dao->sources_name);
        $key = array_keys($this->source_dao->sources_name)[0];
        self::assertSame($passkey_name, $this->source_dao->sources_name[$key]);
        self::assertCount(1, $this->serializer->getCapturedFeedbacks());
        self::assertSame(\Feedback::SUCCESS, $this->serializer->getCapturedFeedbacks()[0]->getLevel());
        self::assertTrue($this->synchronizer_token_stub->hasBeenChecked());
    }

    // Above, tests functions
    // _.-._.-._.-._.-._.-._.-._.-._.-._.-._.-._.-._.-._.-._.-._.-._.-._.-._.-._.-._.-.
    // Below, utility functions

    private function handle(
        ProvideCurrentUser $provide_current_user,
        string|array $body = '',
        RetrieveWebAuthnChallenge $challenge_dao = new WebAuthnChallengeDaoStub(),
    ): ResponseInterface {
        $controller = $this->getController($provide_current_user, $challenge_dao);

        if (is_array($body)) {
            $body = psl_json_encode($body);
        }

        return $controller->handle(
            (new NullServerRequest())->withBody(HTTPFactoryBuilder::streamFactory()->createStream($body))
        );
    }

    private function getController(
        ProvideCurrentUser $provide_current_user,
        RetrieveWebAuthnChallenge $challenge_dao,
    ): PostRegistrationController {
        $attestation_statement_manager = new AttestationStatementSupportManager();
        $attestation_statement_manager->add(new NoneAttestationStatementSupport());
        $response_factory      = HTTPFactoryBuilder::responseFactory();
        $json_response_builder = new JSONResponseBuilder($response_factory, HTTPFactoryBuilder::streamFactory());

        return new PostRegistrationController(
            $provide_current_user,
            $challenge_dao,
            $this->source_dao,
            new PublicKeyCredentialRpEntity('tuleap', 'example.com'),
            [new PublicKeyCredentialParameters(PublicKeyCredentialDescriptor::CREDENTIAL_TYPE_PUBLIC_KEY, Algorithms::COSE_ALGORITHM_ES256)],
            new PublicKeyCredentialLoader(
                new AttestationObjectLoader($attestation_statement_manager)
            ),
            new AuthenticatorAttestationResponseValidator(
                $attestation_statement_manager,
                WebAuthnCredentialSourceDaoStub::withoutCredentialSources(),
                null,
                new ExtensionOutputCheckerHandler()
            ),
            $response_factory,
            new RestlerErrorResponseBuilder($json_response_builder),
            $this->serializer,
            $this->synchronizer_token_stub,
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
        yield 'It returns 400 when missing response' => [
            'body' => ['my_body'],
        ];
        yield 'It returns 400 when missing name' => [
            'body' => [
                'response' => ['some data'],
            ],
        ];
        yield 'It returns 400 when missing csrf_token' => [
            'body' => [
                'response' => ['some data'],
                'name' => 'a name',
            ],
        ];
        yield 'It returns 400 when response is not array' => [
            'body' => [
                'response' => 'not an array',
                'name' => 'name of passkey',
                'csrf_token' => 'a token',
            ],
        ];
        yield 'It returns 400 when name is not string' => [
            'body' => [
                'response' => [],
                'name' => -1,
                'csrf_token' => 'a token',
            ],
        ];
        yield 'It returns 400 when csrf_token is not string' => [
            'body' => [
                'response' => [],
                'name' => 'name of passkey',
                'csrf_token' => -1,
            ],
        ];
        yield 'It returns 400 when name is empty' => [
            'body' => [
                'response' => [],
                'name' => '',
                'csrf_token' => 'a token',
            ],
        ];
        yield 'It returns 400 when csrf_token is empty' => [
            'body' => [
                'response' => [],
                'name' => 'name of passkey',
                'csrf_token' => '',
            ],
        ];
        yield 'It returns 400 when name is too long' => [
            'body' => [
                'response' => [],
                'name' => 'abcdefghijklmnopqrstuvwxyz0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789abcdefghijklmnopqrstuvwxyz0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789abcdefghijklmnopqrstuvwxyz0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789abcdefghijklmnopqrstuvwxyz0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789abcdefghijklmnopqrstuvwxyz0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789',
                'csrf_token' => '',
            ],
        ];
        yield 'It returns 400 when invalid response' => [
            'body' => [
                'response' => ['invalid data'],
                'name' => 'name of passkey',
                'csrf_token' => 'some token',
            ],
        ];
        yield 'It returns 400 when response is assertion' => [
            'body' => [
                'response' => (new PasskeyStub())->generateAssertionResponse('challenge'),
                'name' => 'name of passkey',
                'csrf_token' => 'some token',
            ],
        ];
        yield 'It returns 400 when there is not stored challenge' => [
            'body' => [
                'response' => (new PasskeyStub())->generateAttestationResponse('challenge'),
                'name' => 'name of passkey',
                'csrf_token' => 'some token',
            ],
        ];
    }
}
