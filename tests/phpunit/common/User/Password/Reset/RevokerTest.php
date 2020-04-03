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

class RevokerTest extends \PHPUnit\Framework\TestCase
{
    use \Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    public function testItThrowsAnExceptionIfTokensHaveNotBeenRemoved(): void
    {
        $user = \Mockery::spy(\PFUser::class);
        $user->shouldReceive('getId')->andReturns(101);

        $dao = \Mockery::spy(\Tuleap\User\Password\Reset\DataAccessObject::class);
        $dao->shouldReceive('deleteTokensByUserId')->with(101)->andReturns(false);

        $token_revoker = new Revoker($dao);

        $this->expectException('Tuleap\\User\\Password\\Reset\\TokenDataAccessException');
        $token_revoker->revokeTokens($user);
    }
}
