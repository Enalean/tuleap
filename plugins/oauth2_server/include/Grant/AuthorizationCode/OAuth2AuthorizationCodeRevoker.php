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
 */

declare(strict_types=1);

namespace Tuleap\OAuth2Server\Grant\AuthorizationCode;

class OAuth2AuthorizationCodeRevoker
{
    /**
     * @var OAuth2AuthorizationCodeDAO
     */
    private $authorization_code_DAO;

    public function __construct(OAuth2AuthorizationCodeDAO $authorization_code_DAO)
    {
        $this->authorization_code_DAO = $authorization_code_DAO;
    }

    public function revokeByAuthCodeId(int $authorization_code_id): void
    {
        $this->authorization_code_DAO->deleteAuthorizationCodeByID($authorization_code_id);
    }
}
