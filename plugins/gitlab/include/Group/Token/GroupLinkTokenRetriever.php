<?php
/**
 * Copyright (c) Enalean, 2022 - present. All Rights Reserved.
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
 *  along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace Tuleap\Gitlab\Group\Token;

use Tuleap\Cryptography\KeyFactory;
use Tuleap\Cryptography\SymmetricLegacy2025\SymmetricCrypto;
use Tuleap\Gitlab\Group\GroupLink;

final class GroupLinkTokenRetriever
{
    public function __construct(private GetTokenByGroupLinkId $get_token_by_group_id, private KeyFactory $key_factory)
    {
    }

    public function retrieveToken(GroupLink $gitlab_group): GroupLinkApiToken
    {
        $token = $this->get_token_by_group_id->getTokenByGroupId($gitlab_group->id);

        $concealed_secret = SymmetricCrypto::decrypt(
            $token,
            $this->key_factory->getLegacy2025EncryptionKey()
        );

        return GroupLinkApiToken::buildNewGroupToken($concealed_secret);
    }
}
