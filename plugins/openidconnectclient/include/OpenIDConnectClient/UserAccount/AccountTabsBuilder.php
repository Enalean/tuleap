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

namespace Tuleap\OpenIDConnectClient\UserAccount;

use Tuleap\User\Account\AccountTabPresenter;
use Tuleap\User\Account\AccountTabPresenterCollection;
use Tuleap\User\Account\AccountTabSecuritySection;

final class AccountTabsBuilder
{
    public function addTabs(AccountTabPresenterCollection $collection): void
    {
        $collection->add(AccountTabSecuritySection::NAME, new AccountTabPresenter(
            dgettext('tuleap-openidconnectclient', 'OpenID Connect providers'),
            OIDCProvidersController::URL,
            $collection->getCurrentHref()
        ));
    }
}
