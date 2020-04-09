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

namespace Tuleap\OAuth2Server\User;

/**
 * @psalm-immutable
 *
 * @see https://openid.net/specs/openid-connect-core-1_0.html#UserInfoResponse
 * @see https://openid.net/specs/openid-connect-core-1_0.html#StandardClaims
 */
final class UserInfoResponseRepresentation implements \JsonSerializable
{
    /**
     * @var string
     */
    private $sub;
    /**
     * @var string|null
     */
    private $email;
    /**
     * @var bool|null
     */
    private $email_verified;

    private function __construct(string $sub, ?string $email, ?bool $email_verified)
    {
        $this->sub            = $sub;
        $this->email          = $email;
        $this->email_verified = $email_verified;
    }

    public static function fromSubject(string $subject): self
    {
        return new self($subject, null, null);
    }

    public function withEmail(string $email, bool $email_verified): self
    {
        return new self($this->sub, $email, $email_verified);
    }

    public function jsonSerialize(): array
    {
        $json_encoded = [
            'sub' => $this->sub
        ];
        if ($this->email !== null) {
            $json_encoded['email'] = $this->email;
        }
        if ($this->email_verified !== null) {
            $json_encoded['email_verified'] = $this->email_verified;
        }
        return $json_encoded;
    }
}
