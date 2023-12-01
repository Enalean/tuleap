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

namespace Tuleap\OAuth2ServerCore\App;

/**
 * @psalm-immutable
 */
final class ClientIdentifier
{
    private const PREFIX = 'tlp-client-id-';

    /** @var int */
    private $identifier;

    private function __construct(int $identifier)
    {
        $this->identifier = $identifier;
    }

    /**
     * @psalm-pure
     * @psalm-return self
     * @throws InvalidClientIdentifierKey
     */
    public static function fromClientId(string $identifier_key): self
    {
        if (preg_match('/^' . preg_quote(self::PREFIX, '/') . '(?<id>\d+)$/', $identifier_key, $matches) !== 1) {
            throw new InvalidClientIdentifierKey($identifier_key);
        }

        return new self((int) $matches['id']);
    }

    /**
     * @psalm-pure
     */
    public static function fromOAuth2App(OAuth2App $app): self
    {
        return new self($app->getId());
    }

    /**
     * @psalm-pure
     */
    public static function fromLastGeneratedClientSecret(LastGeneratedClientSecret $secret): self
    {
        return new self($secret->getAppID());
    }

    public function getInternalId(): int
    {
        return $this->identifier;
    }

    /**
     * @psalm-return non-empty-string
     */
    public function toString(): string
    {
        return self::PREFIX . $this->identifier;
    }
}
