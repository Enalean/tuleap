<?php
/**
 * Copyright (c) Enalean, 2013-2015. All Rights Reserved.
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
 * I Deal with Rest_Token
 */
class Rest_TokenManager
{
    /**
     * Expiration time for tokens in seconds (24 hours)
     */
    public const TOKENS_EXPIRATION_TIME = 86400;

    /** @var Rest_TokenDao */
    private $token_dao;

    /** @var  Rest_TokenFactory */
    private $token_factory;

    /** @var  UserManager */
    private $user_manager;

    public function __construct(Rest_TokenDao $token_dao, Rest_TokenFactory $token_factory, UserManager $user_manager)
    {
        $this->token_dao     = $token_dao;
        $this->token_factory = $token_factory;
        $this->user_manager  = $user_manager;
    }

    /**
     * @return PFUser or null if the user is not found
     * @throws Rest_Exception_InvalidTokenException
     */
    public function checkToken(Rest_Token $token)
    {
        if ($this->token_factory->doesTokenExist($token->getUserId(), $token->getTokenValue())) {
            return $this->user_manager->getUserById($token->getUserId());
        }

        throw new Rest_Exception_InvalidTokenException();
    }

    public function expireToken(Rest_Token $token)
    {
        if ($this->checkToken($token)) {
            return $this->token_dao->deleteToken($token->getTokenValue());
        }

        throw new Rest_Exception_InvalidTokenException();
    }

    public function expireOldTokens()
    {
        $timestamp = $this->computeExpirationTimestamp();
        return $this->token_dao->deleteTokensOlderThan($timestamp);
    }

    private function computeExpirationTimestamp()
    {
        return $_SERVER['REQUEST_TIME'] - self::TOKENS_EXPIRATION_TIME;
    }

    public function expireAllTokensForUser(PFUser $user)
    {
        return $this->token_dao->deleteAllTokensForUser($user->getId());
    }

    /**
     * @return Rest_Token
     */
    public function generateTokenForUser(PFUser $user)
    {
        $number_generator = new RandomNumberGenerator();
        $token            = $number_generator->getNumber();
        $this->token_dao->addTokenForUserId($user->getId(), $token, $_SERVER['REQUEST_TIME']);

        return new Rest_Token(
            $user->getId(),
            $token
        );
    }
}
