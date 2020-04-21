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

use Lcobucci\JWT\Builder;
use Lcobucci\JWT\Parser;
use Lcobucci\JWT\Signer\Hmac\Sha512;
use Lcobucci\JWT\Signer\Key;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use UGroupLiteralizer;
use UserManager;

class JWTGeneratorTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /** @var UserManager */
    private $user_manager;

    /** @var UGroupLiteralizer */
    private $ugroup_literalizer;

    /** @var  JWTGenerator */
    private $jwt_generator;

    /** @var Key */
    private $private_key;
    /**
     * @var Builder
     */
    private $builder;
    /**
     * @var Sha512
     */
    private $signer;

    protected function setUp(): void
    {
        parent::setUp();

        $user = Mockery::mock(\PFUser::class);
        $user->shouldReceive('getId')->andReturn(9);

        $this->user_manager = Mockery::mock(UserManager::class);
        $this->user_manager->shouldReceive('getCurrentUser')->andReturn($user);

        $u_groups = array('@site_active');

        $this->ugroup_literalizer = \Mockery::mock(\UGroupLiteralizer::class);
        $this->ugroup_literalizer->shouldReceive('getUserGroupsForUserWithArobase')->andReturn($u_groups);

        $this->private_key   = new Key('private_key_test');
        $this->builder       = new Builder();
        $this->signer        = new Sha512();
        $this->jwt_generator = new JWTGenerator($this->private_key, $this->builder, $this->signer, $this->user_manager, $this->ugroup_literalizer);
    }

    public function testJWTDecodedWithAlgorithmHS512(): void
    {
        $token   = $this->jwt_generator->getToken();
        $decoded = (new Parser())->parse($token);
        $this->assertTrue($decoded->verify($this->signer, $this->private_key));
    }

    public function testContentJWT(): void
    {
        $expected = array(
            'user_id'     => 9,
            'user_rights' => array('@site_active')
        );

        $token        = $this->jwt_generator->getToken();
        $decoded      = (new Parser())->parse($token);

        $this->assertSame($expected, (array) $decoded->getClaim('data'));
    }
}
