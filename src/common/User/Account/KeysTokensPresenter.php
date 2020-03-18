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

use CSRFSynchronizerToken;

final class KeysTokensPresenter
{
    /**
     * @var CSRFSynchronizerToken
     * @psalm-readonly
     */
    public $csrf_token;
    /**
     * @var SSHKeysPresenter
     * @psalm-readonly
     */
    public $ssh_keys_presenter;
    /**
     * @var AccessKeyPresenter
     * @psalm-readonly
     */
    public $access_key_presenter;
    /**
     * @var SVNTokensPresenter
     * @psalm-readonly
     */
    public $svn_tokens_presenter;
    /**
     * @var AccountTabPresenterCollection
     * @psalm-readonly
     */
    public $tabs;

    public function __construct(CSRFSynchronizerToken $csrf_token, AccountTabPresenterCollection $tabs, SSHKeysPresenter $ssh_keys_presenter, AccessKeyPresenter $access_key_presenter, SVNTokensPresenter $svn_tokens_presenter)
    {
        $this->csrf_token = $csrf_token;
        $this->tabs = $tabs;
        $this->access_key_presenter = $access_key_presenter;
        $this->ssh_keys_presenter = $ssh_keys_presenter;
        $this->svn_tokens_presenter = $svn_tokens_presenter;
    }
}
