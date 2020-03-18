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

namespace Tuleap\OAuth2Server\App;

use Tuleap\Authentication\SplitToken\SplitTokenVerificationString;
use Tuleap\Authentication\SplitToken\SplitTokenVerificationStringHasher;

final class NewOAuth2App
{
    /**
     * @var string
     * @psalm-readonly
     */
    private $name;
    /**
     * @var string
     * @psalm-readonly
     */
    private $redirect_endpoint;
    /**
     * @var SplitTokenVerificationString
     * @psalm-readonly
     */
    private $secret;
    /**
     * @var string
     * @psalm-readonly
     */
    private $hashed_secret;
    /**
     * @var \Project
     * @psalm-readonly
     */
    private $project;
    /**
     * @var bool
     * @psalm-readonly
     */
    private $use_pkce;

    private function __construct(
        string $name,
        string $redirect_endpoint,
        SplitTokenVerificationString $secret,
        string $hashed_secret,
        \Project $project,
        bool $use_pkce
    ) {
        $this->name              = $name;
        $this->redirect_endpoint = $redirect_endpoint;
        $this->secret            = $secret;
        $this->hashed_secret     = $hashed_secret;
        $this->project           = $project;
        $this->use_pkce          = $use_pkce;
    }

    /**
     * @throws InvalidAppDataException
     */
    public static function fromAppData(
        string $name,
        string $redirect_endpoint,
        bool $use_pkce,
        \Project $project,
        SplitTokenVerificationStringHasher $hasher
    ): self {
        $is_data_valid = self::isAppDataValid($name, $redirect_endpoint);

        if (! $is_data_valid) {
            throw new InvalidAppDataException();
        }

        $secret = SplitTokenVerificationString::generateNewSplitTokenVerificationString();

        return new self(
            $name,
            $redirect_endpoint,
            $secret,
            $hasher->computeHash($secret),
            $project,
            $use_pkce
        );
    }

    private static function isAppDataValid(string $name, string $redirect_endpoint): bool
    {
        $string_validator = new \Valid_String();
        $string_validator->required();
        // See https://tools.ietf.org/html/rfc6749#section-3.1.2
        $redirect_endpoint_validator = new \Valid_String();
        $redirect_endpoint_validator->required();
        $redirect_endpoint_validator->addRule(new \Rule_Regexp('/^https:\/\/[^#]*$/i'));

        return $string_validator->validate($name) && $redirect_endpoint_validator->validate($redirect_endpoint);
    }

    /**
     * @psalm-mutation-free
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @psalm-mutation-free
     */
    public function getRedirectEndpoint(): string
    {
        return $this->redirect_endpoint;
    }

    /**
     * @psalm-mutation-free
     */
    public function getProject(): \Project
    {
        return $this->project;
    }

    /**
     * @psalm-mutation-free
     */
    public function getSecret(): SplitTokenVerificationString
    {
        return $this->secret;
    }

    /**
     * @psalm-mutation-free
     */
    public function getHashedSecret(): string
    {
        return $this->hashed_secret;
    }

    public function isUsingPKCE(): bool
    {
        return $this->use_pkce;
    }
}
