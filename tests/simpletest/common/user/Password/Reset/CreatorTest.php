<?php
/**
 * Copyright (c) Enalean, 2017. All Rights Reserved.
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

class CreatorTest extends \TuleapTestCase
{
    public function itCreatesToken()
    {
        $random_number_generator = mock('RandomNumberGenerator');
        stub($random_number_generator)->getNumber()->returns('random');

        $password_handler = mock('PasswordHandler');
        stub($password_handler)->computeHashPassword()->returns('random_password_hashed');

        $user = mock('PFUser');
        stub($user)->getId()->returns(101);

        $dao = mock('Tuleap\\User\\Password\\Reset\\DataAccessObject');
        stub($dao)->create(101, 'random_password_hashed', '*')->returns(22);

        $token_creator = new Creator($dao, $random_number_generator, $password_handler);

        $token = $token_creator->create($user);
        $this->assertEqual(22, $token->getId());
        $this->assertEqual('random', $token->getVerifier());
    }

    public function itThrowsExceptionWhenTokenCanNotBeCreated()
    {
        $random_number_generator = mock('RandomNumberGenerator');
        $password_handler        = mock('PasswordHandler');
        $user                    = mock('PFUser');

        $dao = mock('Tuleap\\User\\Password\\Reset\\DataAccessObject');
        stub($dao)->create()->returns(false);

        $token_creator = new Creator($dao, $random_number_generator, $password_handler);

        $this->expectException('Tuleap\\User\\Password\\Reset\\TokenNotCreatedException');
        $token_creator->create($user);
    }
}
