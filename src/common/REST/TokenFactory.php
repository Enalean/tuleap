<?php
/**
 * Copyright (c) Enalean, 2013 - Present. All Rights Reserved.
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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

/**
 *
 * I instantiate Rest_Token
 */
class Rest_TokenFactory
{
    private $token_dao;

    public function __construct(Rest_TokenDao $token_dao)
    {
        $this->token_dao = $token_dao;
    }

    public function getTokensForUser(PFUser $user)
    {
        $tokens_dar = $this->token_dao->getTokensForUserId($user->getId());
        $tokens     = $tokens_dar->instanciateWith([$this, 'instantiateFromRow']);

        return $tokens;
    }

    public function doesTokenExist($user_id, $token_value)
    {
        $token_dar = $this->token_dao->checkTokenExistenceForUserId($user_id, $token_value);

        return count($token_dar) > 0;
    }

    public function instantiateFromRow(array $row)
    {
        return new Rest_Token(
            $row['user_id'],
            $row['token']
        );
    }
}
