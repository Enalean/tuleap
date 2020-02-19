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

final class SVNTokensPresenterBuilder
{
    /**
     * @var SVN_TokenHandler
     */
    private $token_handler;

    public function __construct(\SVN_TokenHandler $token_handler)
    {
        $this->token_handler = $token_handler;
    }

    public function getForUser(\PFUser $user, array &$storage): SVNTokensPresenter
    {
        $last_svn_token = '';
        if (isset($storage['last_svn_token'])) {
            $last_svn_token = $storage['last_svn_token'];
            unset($storage['last_svn_token']);
        }
        return new SVNTokensPresenter($this->token_handler->getSVNTokensForUser($user), $last_svn_token);
    }

    public static function build(): self
    {
        return new self(SVN_TokenHandler::build());
    }
}
