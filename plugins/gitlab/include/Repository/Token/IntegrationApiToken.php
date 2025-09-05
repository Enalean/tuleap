<?php
/**
 * Copyright (c) Enalean, 2021 - Present. All Rights Reserved.
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

namespace Tuleap\Gitlab\Repository\Token;

use Tuleap\Cryptography\ConcealedString;
use Tuleap\Gitlab\API\ApiToken;

/**
 * @psalm-immutable
 */
final class IntegrationApiToken implements ApiToken
{
    /**
     * @var ConcealedString
     */
    private $token;
    /**
     * @var bool
     */
    private $is_email_already_send_for_invalid_token;

    private function __construct(ConcealedString $token, bool $is_email_already_send_for_invalid_token)
    {
        $this->token                                   = $token;
        $this->is_email_already_send_for_invalid_token = $is_email_already_send_for_invalid_token;
    }

    public static function buildBrandNewToken(ConcealedString $token): self
    {
        return new self($token, false);
    }

    public static function buildAlreadyKnownToken(ConcealedString $token, bool $is_email_already_send_for_invalid_token): self
    {
        return new self($token, $is_email_already_send_for_invalid_token);
    }

    #[\Override]
    public function getToken(): ConcealedString
    {
        return $this->token;
    }

    public function isEmailAlreadySendForInvalidToken(): bool
    {
        return $this->is_email_already_send_for_invalid_token;
    }
}
