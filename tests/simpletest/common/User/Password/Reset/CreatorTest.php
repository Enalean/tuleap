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

class CreatorTest extends \TuleapTestCase
{
    public function itCreatesToken()
    {
        $hasher = \Mockery::mock(SplitTokenVerificationStringHasher::class);
        $hasher->shouldReceive('computeHash')->andReturns('random_hashed');

        $user = mock('PFUser');
        stub($user)->getId()->returns(101);

        $dao = mock('Tuleap\\User\\Password\\Reset\\DataAccessObject');
        stub($dao)->create(101, 'random_hashed', '*')->returns(22);

        $token_creator = new Creator($dao, $hasher);

        $token = $token_creator->create($user);
        $this->assertEqual(22, $token->getID());
    }

    public function itThrowsExceptionWhenTokenCanNotBeCreated()
    {
        $hasher = \Mockery::mock(SplitTokenVerificationStringHasher::class);
        $hasher->shouldReceive('computeHash')->andReturns('random_hashed');
        $user = mock('PFUser');

        $dao = mock('Tuleap\\User\\Password\\Reset\\DataAccessObject');
        stub($dao)->create()->returns(false);

        $token_creator = new Creator($dao, $hasher);

        $this->expectException('Tuleap\\User\\Password\\Reset\\TokenNotCreatedException');
        $token_creator->create($user);
    }
}
