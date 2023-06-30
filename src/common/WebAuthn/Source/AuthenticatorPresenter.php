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

namespace Tuleap\WebAuthn\Source;

use ParagonIE\ConstantTime\Base64UrlSafe;
use Tuleap\Date\TlpRelativeDatePresenter;
use Tuleap\Date\TlpRelativeDatePresenterBuilder;

final class AuthenticatorPresenter
{
    public readonly string $id;
    public readonly string $name;
    public readonly TlpRelativeDatePresenter $created_at;
    public readonly TlpRelativeDatePresenter $last_use;

    public function __construct(
        WebAuthnCredentialSource $source,
        \PFUser $current_user,
    ) {
        $this->id         = Base64UrlSafe::encode($source->getSource()->getPublicKeyCredentialId());
        $this->name       = $source->getName();
        $builder          = new TlpRelativeDatePresenterBuilder();
        $this->created_at = $builder->getTlpRelativeDatePresenterInBlockContext(
            $source->getCreatedAt(),
            $current_user
        );
        $this->last_use   = $builder->getTlpRelativeDatePresenterInBlockContext(
            $source->getLastUse(),
            $current_user
        );
    }
}
