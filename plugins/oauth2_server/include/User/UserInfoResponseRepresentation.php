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

use Tuleap\language\LanguageTagFormatter;
use Tuleap\OAuth2Server\OpenIDConnect\Issuer;

/**
 * @see https://openid.net/specs/openid-connect-core-1_0.html#UserInfoResponse
 * @see https://openid.net/specs/openid-connect-core-1_0.html#StandardClaims
 * Note: The following claims are NOT SUPPORTED as we don't have any information on them:
 *      given_name, family_name, middle_name, nickname, website, gender, birthdate, address, phone_number, phone_number_verified, updated_at
 */
final class UserInfoResponseRepresentation implements \JsonSerializable
{
    /**
     * @var bool
     * @psalm-readonly
     */
    private $include_email;
    /**
     * @var bool
     * @psalm-readonly
     */
    private $include_profile;
    /**
     * @var \PFUser
     * @psalm-readonly
     */
    private $user;

    private function __construct(\PFUser $user, bool $include_email, bool $include_profile)
    {
        $this->user            = $user;
        $this->include_email   = $include_email;
        $this->include_profile = $include_profile;
    }

    public static function fromUserWithSubject(\PFUser $user): self
    {
        return new self($user, false, false);
    }

    public function withEmail(): self
    {
        return new self($this->user, true, $this->include_profile);
    }

    public function withProfile(): self
    {
        return new self($this->user, $this->include_email, true);
    }

    private function getAbsoluteProfileURI(string $relative_uri): string
    {
        return Issuer::toString() . $relative_uri;
    }

    public function jsonSerialize(): array
    {
        // See https://openid.net/specs/openid-connect-core-1_0.html#StandardClaims
        $json_encoded = ['sub' => (string) $this->user->getId()];
        if ($this->include_email === true) {
            $json_encoded['email']          = $this->user->getEmail();
            $json_encoded['email_verified'] = $this->user->isAlive();
        }
        if ($this->include_profile === true) {
            $json_encoded['name']               = $this->user->getRealName();
            $json_encoded['preferred_username'] = $this->user->getUserName();
            $json_encoded['profile']            = $this->getAbsoluteProfileURI($this->user->getPublicProfileUrl());
            $json_encoded['picture']            = $this->user->getAvatarUrl();
            $json_encoded['zoneinfo']           = $this->user->getTimezone();
            $json_encoded['locale']             = LanguageTagFormatter::formatAsRFC5646LanguageTag(
                $this->user->getLocale()
            );
        }
        return $json_encoded;
    }
}
