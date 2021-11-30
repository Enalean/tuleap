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
use Lcobucci\JWT\Token\Parser;
use Lcobucci\JWT\Signer\Hmac\Sha512;
use Lcobucci\JWT\Signer\Key;
use Lcobucci\JWT\Validation\Constraint\SignedWith;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Tuleap\Project\UGroupLiteralizer;
use UserManager;

final class JWTGeneratorTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use MockeryPHPUnitIntegration;

    /** @var UserManager */
    private $user_manager;

    /** @var UGroupLiteralizer */
    private $ugroup_literalizer;

    /** @var  JWTGenerator */
    private $jwt_generator;

    /**
     * @var Configuration
     */
    private $jwt_configuration;

    protected function setUp(): void
    {
        parent::setUp();

        $user = Mockery::mock(\PFUser::class);
        $user->shouldReceive('getId')->andReturn(9);

        $this->user_manager = Mockery::mock(UserManager::class);
        $this->user_manager->shouldReceive('getCurrentUser')->andReturn($user);

        $u_groups = ['@site_active'];

        $this->ugroup_literalizer = \Mockery::mock(UGroupLiteralizer::class);
        $this->ugroup_literalizer->shouldReceive('getUserGroupsForUserWithArobase')->andReturn($u_groups);

        $this->jwt_configuration = Configuration::forSymmetricSigner(new Sha512(), Key\InMemory::plainText('private_key_test'));
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
