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

use Git_RemoteServer_GerritServerFactory;
use PFUser;
use Tuleap\User\Account\AccountTabGeneralSection;
use Tuleap\User\Account\AccountTabPresenter;
use Tuleap\User\Account\AccountTabPresenterCollection;

final class AccountTabsBuilder
{
    /**
     * @var Git_RemoteServer_GerritServerFactory
     */
    private $gerrit_server_factory;

    public function __construct(Git_RemoteServer_GerritServerFactory $gerrit_server_factory)
    {
        $this->gerrit_server_factory = $gerrit_server_factory;
    }

    public function addTabs(AccountTabPresenterCollection $collection): void
    {
        if ($this->hasRemoteAvailable() || $this->hasServerForUser($collection->getUser())) {
            $collection->add(
                AccountTabGeneralSection::NAME,
                new AccountTabPresenter(
                    dgettext('tuleap-git', 'Gerrit'),
                    AccountGerritController::URL,
                    $collection->getCurrentHref()
                )
            );
        }
    }

    private function hasServerForUser(PFUser $user): bool
    {
        return count($this->gerrit_server_factory->getRemoteServersForUser($user)) > 0;
    }

    private function hasRemoteAvailable(): bool
    {
        return $this->gerrit_server_factory->hasRemotesSetUp();
    }
}
