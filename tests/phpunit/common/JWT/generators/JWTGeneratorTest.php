<?php
/**
 * Copyright (c) Enalean, 2011 - 2019. All Rights Reserved.
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

namespace Tuleap\JWT\Generators;

use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Firebase\JWT\JWT;
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

    /** @var string */
    private $private_key;

    public function setUp()
    {
        parent::setUp();

        $user = Mockery::mock(\PFUser::class);
        $user->shouldReceive('getId')->andReturn(9);

        $this->user_manager = Mockery::mock(UserManager::class);
        $this->user_manager->shouldReceive('getCurrentUser')->andReturn($user);

        $u_groups = array('@site_active');

        $this->ugroup_literalizer = \Mockery::mock(\UGroupLiteralizer::class);
        $this->ugroup_literalizer->shouldReceive('getUserGroupsForUserWithArobase')->andReturn($u_groups);

        $this->private_key   = "private_key_test";
        $this->jwt_generator = new JWTGenerator($this->private_key, $this->user_manager, $this->ugroup_literalizer);
    }

    public function testJWTDecodedWithAlgorithmHS512()
    {
        $token   = $this->jwt_generator->getToken();
        $decoded = null;
        $decoded = JWT::decode($token, $this->private_key, array('HS512'));

        $this->assertTrue(is_object($decoded));
    }

    public function testContentJWT()
    {
        $expected = array(
            'user_id'     => 9,
            'user_rights' => array('@site_active')
        );

        $token        = $this->jwt_generator->getToken();
        $decoded      = JWT::decode($token, $this->private_key, array('HS512'));
        $decoded_data = (array) $decoded->data;

        $this->assertSame($decoded_data, $expected);
    }
}
