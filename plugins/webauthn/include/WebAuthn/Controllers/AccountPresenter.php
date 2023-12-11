<?php
/**
 * Copyright (c) Enalean, 2023-Present. All Rights Reserved.
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
 */

declare(strict_types=1);

namespace Tuleap\WebAuthn\Controllers;

use Tuleap\CSRFSynchronizerTokenPresenter;
use Tuleap\User\Account\AccountTabPresenterCollection;
use Tuleap\WebAuthn\Source\AuthenticatorPresenter;

final class AccountPresenter
{
    public readonly bool $need_more_authenticators;
    public readonly bool $has_authenticators;

    /**
     * @param AuthenticatorPresenter[] $authenticators
     */
    public function __construct(
        public readonly AccountTabPresenterCollection $tabs,
        public readonly array $authenticators,
        public readonly bool $passwordless_only,
        public readonly CSRFSynchronizerTokenPresenter $csrf_token_add,
        public readonly CSRFSynchronizerTokenPresenter $csrf_token_del,
        public readonly CSRFSynchronizerTokenPresenter $csrf_token_switch,
        public readonly bool $enable_passwordless_login,
    ) {
        $this->need_more_authenticators = count($this->authenticators) < 2;
        $this->has_authenticators       = ! empty($this->authenticators);
    }
}
