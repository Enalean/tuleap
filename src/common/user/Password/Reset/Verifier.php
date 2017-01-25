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

use DateInterval;
use DateTime;
use PasswordHandler;
use UserManager;

class Verifier
{
    const TOKEN_VALIDITY_PERIOD = 'PT1H';

    /**
     * @var DataAccessObject
     */
    private $dao;
    /**
     * @var PasswordHandler
     */
    private $password_handler;
    /**
     * @var UserManager
     */
    private $user_manager;

    public function __construct(DataAccessObject $dao, PasswordHandler $password_handler, UserManager $user_manager)
    {
        $this->dao              = $dao;
        $this->password_handler = $password_handler;
        $this->user_manager     = $user_manager;
    }

    /**
     * @return \PFUser
     * @throws \Tuleap\User\Password\Reset\ExpiredTokenException
     * @throws \Tuleap\User\Password\Reset\InvalidTokenException
     */
    public function getUser(Token $token)
    {
        $row = $this->dao->getTokenInformationById($token->getId());

        if ($row === false) {
            throw new InvalidTokenException('Invalid ID');
        }

        $is_token_valid = $this->password_handler->verifyHashPassword($token->getVerifier(), $row['verifier']);

        if ($is_token_valid === false) {
            throw new InvalidTokenException('Invalid identifier');
        }

        $maximum_expiration_date = new DateTime('@' . $row['creation_date']);
        $maximum_expiration_date->add(new DateInterval(self::TOKEN_VALIDITY_PERIOD));

        $current_date = new DateTime();

        if ($current_date > $maximum_expiration_date) {
            throw new ExpiredTokenException();
        }

        return $this->user_manager->getUserById($row['user_id']);
    }
}
