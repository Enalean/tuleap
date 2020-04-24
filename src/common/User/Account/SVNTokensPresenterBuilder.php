<?php
/**
 * Copyright (c) Enalean, 2020-Present. All Rights Reserved.
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
 *
 */

declare(strict_types=1);

namespace Tuleap\User\Account;

use SVN_TokenHandler;
use Tuleap\Cryptography\KeyFactory;
use Tuleap\Cryptography\Symmetric\SymmetricCrypto;

final class SVNTokensPresenterBuilder
{
    /**
     * @var SVN_TokenHandler
     */
    private $token_handler;
    /**
     * @var KeyFactory
     */
    private $key_factory;

    public function __construct(\SVN_TokenHandler $token_handler, KeyFactory $key_factory)
    {
        $this->token_handler = $token_handler;
        $this->key_factory   = $key_factory;
    }

    public function getForUser(\PFUser $user, array &$storage): SVNTokensPresenter
    {
        $last_svn_token = null;
        if (isset($storage['last_svn_token'])) {
            $last_svn_token = SymmetricCrypto::decrypt($storage['last_svn_token'], $this->key_factory->getEncryptionKey());
            sodium_memzero($storage['last_svn_token']);
            unset($storage['last_svn_token']);
        }
        return new SVNTokensPresenter($this->token_handler->getSVNTokensForUser($user), $last_svn_token);
    }

    public static function build(): self
    {
        return new self(SVN_TokenHandler::build(), new KeyFactory());
    }
}
