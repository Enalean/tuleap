<?php
/**
 * Copyright (c) Enalean, 2015. All Rights Reserved.
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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

class SVN_TokenHandler
{

    /** @var SVN_TokenDao */
    private $token_dao;

    /** @var RandomNumberGenerator */
    private $random_number_generator;

    /** @var PasswordHandler */
    private $password_handler;


    public function __construct(
        SVN_TokenDao $token_dao,
        RandomNumberGenerator $random_number_generator,
        PasswordHandler $password_handler
    ) {
        $this->token_dao               = $token_dao;
        $this->random_number_generator = $random_number_generator;
        $this->password_handler        = $password_handler;
    }

    public static function build(): self
    {
        return new self(
            new SVN_TokenDao(),
            new RandomNumberGenerator(),
            PasswordHandlerFactory::getPasswordHandler()
        );
    }

    /**
     * @return SVN_Token[]
     */
    public function getSVNTokensForUser(PFUser $user): array
    {
        $rows       = $this->token_dao->getSVNTokensForUser($user->getId());
        $svn_tokens = array();

        foreach ($rows as $row) {
            $svn_tokens[] = $this->instantiateFromRow($user, $row);
        }

        return $svn_tokens;
    }

    public function generateSVNTokenForUser(PFUser $user, $comment)
    {
        $token          = $this->generateRandomToken();
        $token_computed = $this->password_handler->computeUnixPassword($token);

        if ($this->token_dao->generateSVNTokenForUser($user->getId(), $token_computed, $comment)) {
            return $token;
        } else {
            return false;
        }
    }

    public function deleteSVNTokensForUser(PFUser $user, $svn_token_ids)
    {
        return $this->token_dao->deleteSVNTokensForUser($user->getId(), $svn_token_ids);
    }

    private function generateRandomToken()
    {
        return $this->random_number_generator->getNumber();
    }

    private function instantiateFromRow(PFUser $user, $svn_token_data)
    {
        return new SVN_Token(
            $user,
            $svn_token_data['id'],
            $svn_token_data['token'],
            $svn_token_data['generated_date'],
            $svn_token_data['last_usage'],
            $svn_token_data['last_ip'],
            $svn_token_data['comment']
        );
    }
}
