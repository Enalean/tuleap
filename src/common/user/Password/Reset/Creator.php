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

use PasswordHandler;
use PFUser;
use RandomNumberGenerator;

class Creator
{
    /**
     * @var DataAccessObject
     */
    private $dao;
    /**
     * @var RandomNumberGenerator
     */
    private $random_number_generator;
    /**
     * @var PasswordHandler
     */
    private $password_handler;

    public function __construct(
        DataAccessObject $dao,
        RandomNumberGenerator $random_number_generator,
        PasswordHandler $password_handler
    ) {
        $this->dao                     = $dao;
        $this->random_number_generator = $random_number_generator;
        $this->password_handler        = $password_handler;
    }

    /**
     * @return Token
     * @throws \Tuleap\User\Password\Reset\TokenNotCreatedException
     */
    public function create(PFUser $user)
    {
        $verifier                 = $this->random_number_generator->getNumber();
        $verifier_password_hashed = $this->password_handler->computeHashPassword($verifier);
        $current_date             = new \DateTime();

        $token_id = $this->dao->create($user->getId(), $verifier_password_hashed, $current_date->getTimestamp());

        if ($token_id === false) {
            throw new TokenNotCreatedException();
        }

        return new Token($token_id, $verifier);
    }
}
