<?php
/**
 * Copyright (c) Enalean, 2022 - Present. All Rights Reserved.
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

namespace Tuleap\JWT\generators;

use Lcobucci\JWT\Configuration;
use Lcobucci\JWT\Encoding\JoseEncoder;
use Lcobucci\JWT\Signer\Hmac\Sha256;
use Lcobucci\JWT\Token\Parser;
use Lcobucci\JWT\Signer\Key;
use Lcobucci\JWT\Validation\Constraint\SignedWith;
use PHPUnit\Framework\MockObject\MockObject;
use Tuleap\GlobalLanguageMock;
use UserManager;

final class MercureJWTGeneratorImplTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use GlobalLanguageMock;

    private string $app_name = 'TestApp';

    private int $app_ID = 1;

    /** @var UserManager&MockObject */
    private $user_manager;

    private MercureJWTGeneratorImpl $mercure_jwt_generator;
    private Configuration $jwt_configuration;

    protected function setUp(): void
    {
        parent::setUp();

        $user = new \PFUser();

        $this->user_manager = $this->createStub(UserManager::class);
        $this->user_manager->method('getCurrentUser')->willReturn($user);


        $this->jwt_configuration     = Configuration::forSymmetricSigner(new Sha256(), Key\InMemory::plainText(str_repeat('a', 32)));
        $this->mercure_jwt_generator = new MercureJWTGeneratorImpl($this->jwt_configuration);
    }

    public function testJWTDecodedWithAlgorithmHS256(): void
    {
        $token   = $this->mercure_jwt_generator->getTokenWithSubscription($this->app_name, $this->app_ID, $this->user_manager->getCurrentUser());
        $decoded = $this->jwt_configuration->parser()->parse($token->getString());
        self::assertTrue($this->jwt_configuration->validator()->validate($decoded, new SignedWith($this->jwt_configuration->signer(), $this->jwt_configuration->signingKey())));
    }

    public function testContentJWTSubscriptionDisabled(): void
    {
        $expected = [
            $this->app_name . '/' . $this->app_ID,
            $this->app_name . '/' . $this->app_ID . '/{component}/{id}',
        ];
        $token    = $this->mercure_jwt_generator->getTokenWithoutSubscription($this->app_name, $this->app_ID, $this->user_manager->getCurrentUser());
        $decoded  = (new Parser(new JoseEncoder()))->parse($token->getString());

        self::assertSame($expected, (array) $decoded->claims()->get('mercure')['subscribe']);
    }

    public function testContentJWTSubscriptionEnabled(): void
    {
        $expected = [
            $this->app_name . '/' . $this->app_ID,
            $this->app_name . '/' . $this->app_ID . '/{component}/{id}',
            '/.well-known/mercure/subscriptions/' . urlencode($this->app_name . '/' . $this->app_ID) . '{/sub}',
            '/.well-known/mercure/subscriptions/' . urlencode($this->app_name . '/' . $this->app_ID) . '{subsubscription}{/sub}',
        ];
        $token    = $this->mercure_jwt_generator->getTokenWithSubscription($this->app_name, $this->app_ID, $this->user_manager->getCurrentUser());
        $decoded  = (new Parser(new JoseEncoder()))->parse($token->getString());

        self::assertSame($expected, (array) $decoded->claims()->get('mercure')['subscribe']);
    }

    public function testBackendJWT(): void
    {
        $expected = [
            'publish' => ['*'],
            'subscribe' => [''],
            'payload' => [''],
        ];
        $token    = $this->mercure_jwt_generator->getTokenBackend();
        $decoded  = (new Parser(new JoseEncoder()))->parse($token->getString());
        self::assertSame($expected, (array) $decoded->claims()->get('mercure'));
    }
}
