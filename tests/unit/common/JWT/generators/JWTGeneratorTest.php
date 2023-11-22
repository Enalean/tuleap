<?php
/**
 * Copyright (c) Enalean, 2011 - Present. All Rights Reserved.
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

namespace Tuleap\JWT\Generators;

use Lcobucci\JWT\Configuration;
use Lcobucci\JWT\Encoding\JoseEncoder;
use Lcobucci\JWT\Signer\Hmac\Sha512;
use Lcobucci\JWT\Signer\Key;
use Lcobucci\JWT\Token\Parser;
use Lcobucci\JWT\Validation\Constraint\SignedWith;
use PHPUnit\Framework\MockObject\MockObject;
use Tuleap\Project\UGroupLiteralizer;
use Tuleap\Test\Builders\UserTestBuilder;
use UserManager;

final class JWTGeneratorTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private UserManager&MockObject $user_manager;

    private UGroupLiteralizer&MockObject $ugroup_literalizer;

    private JWTGenerator $jwt_generator;

    private Configuration $jwt_configuration;

    protected function setUp(): void
    {
        parent::setUp();

        $user = UserTestBuilder::anActiveUser()
            ->withId(9)
            ->build();

        $this->user_manager = $this->createMock(UserManager::class);
        $this->user_manager->method('getCurrentUser')->willReturn($user);

        $u_groups = ['@site_active'];

        $this->ugroup_literalizer = $this->createMock(UGroupLiteralizer::class);
        $this->ugroup_literalizer->method('getUserGroupsForUserWithArobase')->willReturn($u_groups);

        $this->jwt_configuration = Configuration::forSymmetricSigner(new Sha512(), Key\InMemory::plainText(str_repeat('a', 64)));
        $this->jwt_generator     = new JWTGenerator($this->jwt_configuration, $this->user_manager, $this->ugroup_literalizer);
    }

    public function testJWTDecodedWithAlgorithmHS512(): void
    {
        $token   = $this->jwt_generator->getToken();
        $decoded = $this->jwt_configuration->parser()->parse($token);
        self::assertTrue($this->jwt_configuration->validator()->validate($decoded, new SignedWith($this->jwt_configuration->signer(), $this->jwt_configuration->signingKey())));
    }

    public function testContentJWT(): void
    {
        $expected = [
            'user_id'     => 9,
            'user_rights' => ['@site_active'],
        ];

        $token   = $this->jwt_generator->getToken();
        $decoded = (new Parser(new JoseEncoder()))->parse($token);

        self::assertSame($expected, (array) $decoded->claims()->get('data'));
    }
}
