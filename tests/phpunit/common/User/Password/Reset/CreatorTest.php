<?php
/**
 * Copyright (c) Enalean, 2017-2018. All Rights Reserved.
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

namespace Tuleap\User\Password\Reset;

use Tuleap\Authentication\SplitToken\SplitTokenVerificationStringHasher;

class CreatorTest extends \PHPUnit\Framework\TestCase
{
    use \Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    public function testItCreatesToken(): void
    {
        $hasher = \Mockery::mock(SplitTokenVerificationStringHasher::class);
        $hasher->shouldReceive('computeHash')->andReturns('random_hashed');

        $user = \Mockery::spy(\PFUser::class);
        $user->shouldReceive('getId')->andReturns(101);

        $dao = \Mockery::spy(\Tuleap\User\Password\Reset\DataAccessObject::class);
        $dao->shouldReceive('create')->with(101, 'random_hashed', \Mockery::any())->andReturns(22);

        $token_creator = new Creator($dao, $hasher);

        $token = $token_creator->create($user);
        $this->assertEquals(22, $token->getID());
    }

    public function testItThrowsExceptionWhenTokenCanNotBeCreated(): void
    {
        $hasher = \Mockery::mock(SplitTokenVerificationStringHasher::class);
        $hasher->shouldReceive('computeHash')->andReturns('random_hashed');
        $user = \Mockery::spy(\PFUser::class);

        $dao = \Mockery::spy(\Tuleap\User\Password\Reset\DataAccessObject::class);
        $dao->shouldReceive('create')->andReturns(false);

        $token_creator = new Creator($dao, $hasher);

        $this->expectException('Tuleap\\User\\Password\\Reset\\TokenNotCreatedException');
        $token_creator->create($user);
    }
}
