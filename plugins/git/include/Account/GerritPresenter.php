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

namespace Tuleap\Git\Account;

use Tuleap\User\Account\AccountTabPresenterCollection;
use Tuleap\User\Account\DisplayKeysTokensController;

class GerritPresenter
{
    /**
     * @var string
     */
    public $keys_tokens_url = DisplayKeysTokensController::URL;
    /**
     * @var AccountTabPresenterCollection
     */
    public $tabs;
    /**
     * @var GerritServerPresenter[]
     */
    public $gerrit_servers;
    /**
     * @var \CSRFSynchronizerToken
     */
    public $csrf_token;

    /**
     * @param \Git_RemoteServer_GerritServer[] $gerrit_servers
     */
    public function __construct(\CSRFSynchronizerToken $csrf_token, AccountTabPresenterCollection $tabs, array $gerrit_servers)
    {
        $this->csrf_token = $csrf_token;
        $this->tabs       = $tabs;
        foreach ($gerrit_servers as $server) {
            $this->gerrit_servers[] = new GerritServerPresenter($server->getBaseUrl());
        }
    }
}
