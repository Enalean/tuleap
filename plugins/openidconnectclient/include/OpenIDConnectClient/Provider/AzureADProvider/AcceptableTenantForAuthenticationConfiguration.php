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
final class AcceptableTenantForAuthenticationConfiguration
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
     * @see https://docs.microsoft.com/en-us/azure/active-directory/develop/access-tokens#payload-claims
     */
    private const TENANT_CONSUMERS_GUID = '9188040d-6c67-4c5b-b112-36a304b66dad';

    /**
     * @var string
     *
     * @psalm-var value-of<self::VALID_IDENTIFIERS>
     */
    private $identifier;
    /**
     * @var string
     */
    private $tenant_id;

    /**
     * @psalm-param value-of<self::VALID_IDENTIFIERS> $identifier
     */
    private function __construct(string $identifier, string $tenant_id)
    {
        $this->identifier = $identifier;
        $this->tenant_id  = $tenant_id;
    }

    public static function fromAcceptableTenantForLoginIdentifierAndTenantID(
        string $acceptable_tenant_for_login_identifier,
        string $tenant_id
    ): self {
        if (! in_array($acceptable_tenant_for_login_identifier, self::VALID_IDENTIFIERS, true)) {
            throw new UnknownAcceptableTenantForAuthenticationIdentifierException($acceptable_tenant_for_login_identifier);
        }

        return new self($acceptable_tenant_for_login_identifier, $tenant_id);
    }

    public static function fromSpecificTenantID(string $tenant_id): self
    {
        return new self(self::TENANT_SPECIFIC_IDENTIFIER, $tenant_id);
    }

    public function getValueForAuthenticationFlow(): string
    {
        if ($this->identifier === self::TENANT_SPECIFIC_IDENTIFIER) {
            return $this->tenant_id;
        }

        return $this->identifier;
    }

    /**
     * @psalm-return value-of<self::VALID_IDENTIFIERS>
     */
    public function getIdentifier(): string
    {
        return $this->identifier;
    }

    /**
     * @return string[]
     */
    public function getAcceptableIssuerTenantIDs(): array
    {
        switch ($this->identifier) {
            case self::TENANT_COMMON_IDENTIFIER:
                return [self::TENANT_CONSUMERS_GUID, $this->tenant_id];
            case self::TENANT_CONSUMERS_IDENTIFIER:
                return [self::TENANT_CONSUMERS_GUID];
            case self::TENANT_ORGANIZATIONS_IDENTIFIER:
            case self::TENANT_SPECIFIC_IDENTIFIER:
            default:
                return [$this->tenant_id];
        }
    }
}
