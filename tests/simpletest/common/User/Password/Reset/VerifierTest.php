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

class VerifierTest extends \TuleapTestCase
{
    public function itGetsUserAssociatedWithToken()
    {
        $creation_date = new \DateTime();
        $dao           = mock('Tuleap\\User\\Password\\Reset\\DataAccessObject');
        stub($dao)->getTokenInformationById()->returns(
            array(
                'verifier'      => 'token_verification_part_password_hashed',
                'user_id'       => 101,
                'creation_date' => $creation_date->getTimestamp()
            )
        );

        $password_handler = mock('PasswordHandler');
        stub($password_handler)->verifyHashPassword()->returns(true);

        $user         = mock('PFUser');
        $user_manager = mock('UserManager');
        stub($user_manager)->getUserById(101)->returns($user);

        $token = mock('Tuleap\\User\\Password\\Reset\\Token');

        $token_verifier = new Verifier($dao, $password_handler, $user_manager);

        $this->assertEqual($user, $token_verifier->getUser($token));
    }

    public function itThrowsAnExceptionWhenTokenIDCanNotBeFound()
    {
        $dao = mock('Tuleap\\User\\Password\\Reset\\DataAccessObject');
        stub($dao)->getTokenInformationById()->returns(false);

        $password_handler = mock('PasswordHandler');
        stub($password_handler)->verifyHashPassword()->returns(true);

        $user_manager = mock('UserManager');

        $token = mock('Tuleap\\User\\Password\\Reset\\Token');

        $token_verifier = new Verifier($dao, $password_handler, $user_manager);

        $this->expectException('Tuleap\\User\\Password\\Reset\\InvalidTokenException');
        $token_verifier->getUser($token);
    }

    public function itThrowsAnExceptionWhenVerifierPartIsNotValid()
    {
        $dao = mock('Tuleap\\User\\Password\\Reset\\DataAccessObject');
        stub($dao)->getTokenInformationById()->returns(array('verifier' => 'token_verification_part_password_hashed'));

        $password_handler = mock('PasswordHandler');
        stub($password_handler)->verifyHashPassword()->returns(false);

        $user_manager = mock('UserManager');

        $token = mock('Tuleap\\User\\Password\\Reset\\Token');

        $token_verifier = new Verifier($dao, $password_handler, $user_manager);

        $this->expectException('Tuleap\\User\\Password\\Reset\\InvalidTokenException');
        $token_verifier->getUser($token);
    }

    public function itThrowsAnExceptionWhenTheTokenIsExpired()
    {
        $expired_creation_date = new \DateTime();
        $expired_creation_date->sub(new \DateInterval(Verifier::TOKEN_VALIDITY_PERIOD));
        $expired_creation_date->sub(new \DateInterval(Verifier::TOKEN_VALIDITY_PERIOD));
        $dao                   = mock('Tuleap\\User\\Password\\Reset\\DataAccessObject');
        stub($dao)->getTokenInformationById()->returns(
            array(
                'verifier'      => 'token_verification_part_password_hashed',
                'user_id'       => 101,
                'creation_date' => $expired_creation_date->getTimestamp()
            )
        );

        $password_handler = mock('PasswordHandler');
        stub($password_handler)->verifyHashPassword()->returns(true);

        $user         = mock('PFUser');
        $user_manager = mock('UserManager');
        stub($user_manager)->getUserById(101)->returns($user);

        $token = mock('Tuleap\\User\\Password\\Reset\\Token');

        $token_verifier = new Verifier($dao, $password_handler, $user_manager);

        $this->expectException('Tuleap\\User\\Password\\Reset\\ExpiredTokenException');
        $token_verifier->getUser($token);
    }
}
