<?php
/**
 * Copyright (c) Enalean, 2025-Present. All Rights Reserved.
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

namespace Tuleap\LicenseCheckPolicy;

require_once __DIR__ . '/SBOMComponentLicense.php';
require_once __DIR__ . '/SBOMComponentProperty.php';
require_once __DIR__ . '/SBOMComponentEvidence.php';

final readonly class SBOMComponent
{
    public function __construct(
        public string $name,
        public ?string $group,
        public string $version,
        public string $purl,
        public ?string $scope,
        /**
         * @var ?list<SBOMComponentLicense>
         */
        public ?array $licenses,
        /**
         * @var ?list<SBOMComponentProperty>
         */
        public ?array $properties,
        public ?SBOMComponentEvidence $evidence,
    ) {
    }
}
