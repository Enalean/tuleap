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
 *
 */

declare(strict_types=1);

namespace Tuleap\Tracker\Creation\JiraImporter\Configuration;

final class PlatformConfiguration
{
    /**
     * @var string[]
     */
    private $allowed_configuration_names       = [];
    private ?string $story_points_field        = null;
    private bool $agile_features_are_available = true;

    public function isConfigurationAllowed(string $configuration_name): bool
    {
        return in_array($configuration_name, $this->allowed_configuration_names);
    }

    public function addAllowedConfiguration(string $configuration_name): void
    {
        $this->allowed_configuration_names[] = $configuration_name;
    }

    public function setStoryPointsField(string $field): void
    {
        $this->story_points_field = $field;
    }

    public function hasStoryPointsField(): bool
    {
        return $this->story_points_field !== null;
    }

    public function getStoryPointsField(): string
    {
        if ($this->story_points_field === null) {
            throw new \RuntimeException('There is no Story Points field configured. Developer should have checked with `hasStoryPointsField`.');
        }
        return $this->story_points_field;
    }

    public function areAgileFeaturesAvailable(): bool
    {
        return $this->agile_features_are_available;
    }

    public function setAgileFeaturesAreNotAvailable(): void
    {
        $this->agile_features_are_available = false;
    }
}
