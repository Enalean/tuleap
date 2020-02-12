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

namespace Tuleap\OpenIDConnectClient\Provider\AzureADProvider;

/**
 * @psalm-immutable
 */
final class AzureADTenantSetup
{
    private const TENANT_SPECIFIC_IDENTIFIER      = 'tenant_specific';
    private const TENANT_COMMON_IDENTIFIER        = 'common';
    private const TENANT_ORGANIZATIONS_IDENTIFIER = 'organizations';
    private const TENANT_CONSUMERS_IDENTIFIER     = 'consumers';
    private const VALID_IDENTIFIERS               = [
        self::TENANT_COMMON_IDENTIFIER,
        self::TENANT_ORGANIZATIONS_IDENTIFIER,
        self::TENANT_CONSUMERS_IDENTIFIER,
        self::TENANT_SPECIFIC_IDENTIFIER
    ];

    /**
     * @var string
     *
     * @psalm-var value-of<self::VALID_IDENTIFIERS>
     */
    private $identifier;

    /**
     * @psalm-param value-of<self::VALID_IDENTIFIERS> $identifier
     */
    private function __construct(string $identifier)
    {
        $this->identifier = $identifier;
    }

    /**
     * @psalm-external-mutation-free
     */
    public static function fromIdentifier(string $identifier): self
    {
        if (! in_array($identifier, self::VALID_IDENTIFIERS, true)) {
            throw new UnknownAcceptableTenantForAuthenticationIdentifierException($identifier);
        }

        static $built_setups = [];

        if (! isset($built_setups[$identifier])) {
            $built_setups[$identifier] = new self($identifier);
        }

        return $built_setups[$identifier];
    }

    /**
     * @psalm-external-mutation-free
     */
    public static function tenantSpecific(): self
    {
        return self::fromIdentifier(self::TENANT_SPECIFIC_IDENTIFIER);
    }

    /**
     * @psalm-external-mutation-free
     */
    public static function common(): self
    {
        return self::fromIdentifier(self::TENANT_COMMON_IDENTIFIER);
    }

    /**
     * @psalm-external-mutation-free
     */
    public static function organizations(): self
    {
        return self::fromIdentifier(self::TENANT_ORGANIZATIONS_IDENTIFIER);
    }

    /**
     * @psalm-external-mutation-free
     */
    public static function consumers(): self
    {
        return self::fromIdentifier(self::TENANT_CONSUMERS_IDENTIFIER);
    }

    /**
     * @psalm-external-mutation-free
     *
     * @return self[]
     */
    public static function allPossibleSetups(): array
    {
        $tenant_setups = [];

        foreach (self::VALID_IDENTIFIERS as $identifier) {
            $tenant_setups[] = self::fromIdentifier($identifier);
        }

        return $tenant_setups;
    }

    /**
     * @psalm-return value-of<self::VALID_IDENTIFIERS>
     */
    public function getIdentifier(): string
    {
        return $this->identifier;
    }

    public function getDescription(): string
    {
        switch ($this->identifier) {
            case self::TENANT_SPECIFIC_IDENTIFIER:
                return dgettext('tuleap-openidconnectclient', 'Users from your specific Azure Active Directory');
            case self::TENANT_CONSUMERS_IDENTIFIER:
                return dgettext('tuleap-openidconnectclient', 'Only users with a personal Microsoft account');
            case self::TENANT_ORGANIZATIONS_IDENTIFIER:
                return dgettext('tuleap-openidconnectclient', 'Only users with a work/school account from Azure Active Directory');
            case self::TENANT_COMMON_IDENTIFIER:
            default:
                return dgettext('tuleap-openidconnectclient', 'Any users with a work/school account from Azure Active Directory or a personal Microsoft account');
        }
    }
}
