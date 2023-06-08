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
use Tuleap\Test\Stubs\CSRFSynchronizerTokenStub;
use Tuleap\Test\Stubs\FeedbackSerializerStub;
use Tuleap\Test\Stubs\ProvideCurrentUserStub;
use Tuleap\Test\Stubs\WebAuthn\PasskeyStub;
use Tuleap\Test\Stubs\WebAuthn\WebAuthnCredentialSourceDaoStub;
use Tuleap\User\ProvideCurrentUser;
use Tuleap\WebAuthn\Source\DeleteCredentialSource;
use Tuleap\WebAuthn\Source\GetCredentialSourceById;
use function Psl\Json\encode as psl_json_encode;

final class DeleteSourceControllerTest extends TestCase
{
    public FeedbackSerializerStub $serializer;

    protected function setUp(): void
    {
        $this->serializer = FeedbackSerializerStub::buildSelf();
    }

    public function testItReturns401WhenNoAuth(): void
    {
        $response = $this->handle(
            ProvideCurrentUserStub::buildWithUser(UserTestBuilder::anAnonymousUser()->build()),
            WebAuthnCredentialSourceDaoStub::withoutCredentialSources()
        );

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
            WebAuthnCredentialSourceDaoStub::withoutCredentialSources(),
            $body
        );

        self::assertSame(400, $response->getStatusCode());
    }

    public function testItReturns200WhenSourceNotFound(): void
    {
        $response = $this->handle(
            ProvideCurrentUserStub::buildWithUser(UserTestBuilder::anActiveUser()->build()),
            WebAuthnCredentialSourceDaoStub::withoutCredentialSources(),
            [
                'key_id' => Base64UrlSafe::encode('unknown source'),
                'csrf_token' => 'some token',
            ]
        );

        self::assertSame(200, $response->getStatusCode());
    }

    public function testItReturns200WhenNotOwner(): void
    {
        $source_id = 'source_1';
        $response  = $this->handle(
            ProvideCurrentUserStub::buildWithUser(UserTestBuilder::anActiveUser()->build()),
            WebAuthnCredentialSourceDaoStub::withCredentialSources($source_id),
            [
                'key_id' => Base64UrlSafe::encode($source_id),
                'csrf_token' => 'some token',
            ]
        );

        self::assertSame(200, $response->getStatusCode());
    }

    public function testItReturns200(): void
    {
        $user_id      = 105;
        $user_manager = ProvideCurrentUserStub::buildWithUser(UserTestBuilder::buildWithId($user_id));
        $source       = (new PasskeyStub())->getCredentialSource((string) $user_id);

        $response = $this->handle(
            $user_manager,
            WebAuthnCredentialSourceDaoStub::withoutCredentialSources()->withRealSource($source),
            [
                'key_id' => Base64UrlSafe::encode($source->getPublicKeyCredentialId()),
                'csrf_token' => 'some token',
            ]
        );

        self::assertSame(200, $response->getStatusCode());
        self::assertCount(1, $this->serializer->getCapturedFeedbacks());
    }

    private function handle(
        ProvideCurrentUser $provide_current_user,
        GetCredentialSourceById&DeleteCredentialSource $source_dao,
        array|string $body = '',
    ): ResponseInterface {
        $response_factory      = HTTPFactoryBuilder::responseFactory();
        $json_response_builder = new JSONResponseBuilder($response_factory, HTTPFactoryBuilder::streamFactory());

        $controller = new DeleteSourceController(
            $provide_current_user,
            $source_dao,
            new RestlerErrorResponseBuilder($json_response_builder),
            $response_factory,
            $this->serializer,
            CSRFSynchronizerTokenStub::buildSelf(),
            new NoopSapiEmitter()
        );

        if (is_array($body)) {
            $body = psl_json_encode($body);
        }

        return $controller->handle(
            (new NullServerRequest())->withBody(HTTPFactoryBuilder::streamFactory()->createStream($body))
        );
    }

    public static function getTest400Data(): iterable
    {
        yield 'It returns 400 when no body' => [
            'body' => '',
        ];
        yield 'It returns 400 when body is invalid json' => [
            'body' => '{',
        ];
        yield 'It returns 400 when missing key_id' => [
            'body' => [],
        ];
        yield 'It returns 400 when missing csrf_token' => [
            'body' => [
                'key_id' => 'some key id',
            ],
        ];
        yield 'It returns 400 when key_id is not string' => [
            'body' => [
                'key_id' => -1,
                'csrf_token' => 'some token',
            ],
        ];
        yield 'It returns 400 when key_id is empty' => [
            'body' => [
                'key_id' => '',
                'csrf_token' => 'some token',
            ],
        ];
        yield 'It returns 400 when csrf_token is not string' => [
            'body' => [
                'key_id' => 'some key id',
                'csrf_token' => -1,
            ],
        ];
        yield 'It returns 400 when csrf_token is empty' => [
            'body' => [
                'key_id' => 'some key id',
                'csrf_token' => '',
            ],
        ];
        yield 'It returns 400 when key_id is not base64' => [
            'body' => [
                'key_id' => 'not base64',
                'csrf_token' => 'some token',
            ],
        ];
    }
}
