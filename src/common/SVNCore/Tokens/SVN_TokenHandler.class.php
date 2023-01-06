<?php
/**
 * Copyright (c) Enalean, 2015-Present. All Rights Reserved.
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

use Tuleap\Cryptography\ConcealedString;

class SVN_TokenHandler
{
    public function __construct(
        private SVN_TokenDao $token_dao,
        private PasswordHandler $password_handler,
    ) {
    }

    public static function build(): self
    {
        return new self(
            new SVN_TokenDao(),
            PasswordHandlerFactory::getPasswordHandler()
        );
    }

    /**
     * @return SVN_Token[]
     */
    public function getSVNTokensForUser(PFUser $user): array
    {
        $rows       = $this->token_dao->getSVNTokensForUser((int) $user->getId());
        $svn_tokens = [];

        foreach ($rows as $row) {
            $svn_tokens[] = $this->instantiateFromRow($user, $row);
        }

        return $svn_tokens;
    }

    public function deleteSVNTokensForUser(PFUser $user, array $svn_token_ids): void
    {
        $this->token_dao->deleteSVNTokensForUser((int) $user->getId(), $svn_token_ids);
    }

    public function isTokenValid(PFUser $user, ConcealedString $token, string $ip_address): bool
    {
        $existing_svn_tokens = $this->getSVNTokensForUser($user);

        foreach ($existing_svn_tokens as $existing_svn_token) {
            if ($this->password_handler->verifyHashPassword($token, $existing_svn_token->getToken())) {
                $this->token_dao->updateTokenLastUsage($existing_svn_token->getId(), $ip_address, (new DateTimeImmutable())->getTimestamp());
                return true;
            }
        }

        return false;
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
