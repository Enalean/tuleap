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

namespace Tuleap\OpenIDConnectClient\Administration;

use Tuleap\OpenIDConnectClient\Provider\AzureADProvider\AzureADTenantSetup;

/**
 * @psalm-immutable
 */
final class AzureADTenantSetupPresenter
{
    /**
     * @var string
     */
    public $identifier;
    /**
     * @var string
     */
    public $description;
    /**
     * @var bool
     */
    public $selected;

    private function __construct(string $identifier, string $description, bool $selected)
    {
        $this->identifier  = $identifier;
        $this->description = $description;
        $this->selected    = $selected;
    }

    /**
     * @param AzureADTenantSetup[] $acceptable_values
     * @return self[]
     */
    public static function fromAllAcceptableValues(array $acceptable_values, AzureADTenantSetup $selected_value): array
    {
        $presenters = [];
        foreach ($acceptable_values as $acceptable_value) {
            $presenters[] = new self($acceptable_value->getIdentifier(), $acceptable_value->getDescription(), $acceptable_value === $selected_value);
        }
        return $presenters;
    }
}
