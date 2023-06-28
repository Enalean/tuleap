<?php
/*
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

namespace Tuleap\WebAuthn;

use Tuleap\User\Admin\GetUserAuthenticatorsEvent;
use Tuleap\WebAuthn\Source\AuthenticatorPresenter;
use Tuleap\WebAuthn\Source\GetAllCredentialSourceByUserId;
use Tuleap\WebAuthn\Source\WebAuthnCredentialSource;

final class GetUserAuthenticatorsEventHandler
{
    public function __construct(
        private readonly GetAllCredentialSourceByUserId $source_dao,
    ) {
    }

    public function handle(GetUserAuthenticatorsEvent $event): void
    {
        $event->authenticators = array_map(
            static fn(WebAuthnCredentialSource $source) => new AuthenticatorPresenter($source, $event->user),
            $this->source_dao->getAllByUserId((int) $event->user->getId())
        );
        $event->answered       = true;
    }
}
