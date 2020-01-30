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

namespace Tuleap\BuildVersion;

/**
 * @psalm-immutable
 */
final class VersionPresenter
{
    private const COMMUNITY_EDITION_FLAVOR_NAME  = 'Tuleap Community Edition';
    private const ENTERPRISE_EDITION_FLAVOR_NAME = 'Tuleap Enterprise Edition';

    /**
     * @var string
     */
    public $flavor_name;
    /**
     * @var string
     */
    public $version_number;
    /**
     * @var string
     */
    public $version_identifier;

    private function __construct(string $flavor_name, string $version_number, string $version_identifier)
    {
        $this->flavor_name        = $flavor_name;
        $this->version_number     = $version_number;
        $this->version_identifier = $version_identifier;
    }

    public static function fromFlavorFinder(FlavorFinder $flavor_finder): self
    {
        $version_number = \trim(\file_get_contents(__DIR__ . '/../../../VERSION'));

        if ($flavor_finder->isEnterprise()) {
            return new self(
                self::ENTERPRISE_EDITION_FLAVOR_NAME,
                $version_number,
                $version_number
            );
        }

        return new self(
            self::COMMUNITY_EDITION_FLAVOR_NAME,
            $version_number,
            'Dev Build ' . $version_number
        );
    }

    public function getFullDescriptiveVersion(): string
    {
        return $this->flavor_name . ' — ' . $this->version_identifier;
    }
}
