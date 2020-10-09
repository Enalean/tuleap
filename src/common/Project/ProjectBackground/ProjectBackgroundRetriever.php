<?php
/**
 * Copyright (c) Enalean, 2020 - Present. All Rights Reserved.
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

namespace Tuleap\Project\ProjectBackground;

use Project;

/**
 * @psalm-import-type ValidProjectBackgroundName from \Tuleap\Project\ProjectBackground\ProjectBackgroundName
 */
class ProjectBackgroundRetriever
{
    /**
     * @var ProjectBackgroundConfiguration
     */
    private $configuration;

    public function __construct(ProjectBackgroundConfiguration $configuration)
    {
        $this->configuration = $configuration;
    }

    /**
     * @return ProjectBackground[]
     */
    public function getBackgrounds(Project $project): array
    {
        $current_background_identifier = $this->configuration->getBackgroundIgnoringFeatureFlag($project);

        return [
            ProjectBackground::buildNoBackground($current_background_identifier === null),
            $this->instantiateBackground('aerial-water', 'Chris', $current_background_identifier),
            $this->instantiateBackground('asphalt-rock', 'Jake Blucker', $current_background_identifier),
            $this->instantiateBackground('beach-daytime', 'Chris Stenger', $current_background_identifier),
            $this->instantiateBackground('blue-rain', 'Jackson David', $current_background_identifier),
            $this->instantiateBackground('blue-sand', 'Amy Humphries', $current_background_identifier),
            $this->instantiateBackground('brown-desert', 'Wolfgang Hasselmann', $current_background_identifier),
            $this->instantiateBackground('brown-grass', 'Hasan Almasi', $current_background_identifier),
            $this->instantiateBackground('brown-textile', 'Noah Kroes', $current_background_identifier),
            $this->instantiateBackground('brush-daytime', 'Darwin Vegher', $current_background_identifier),
            $this->instantiateBackground('green-grass', 'Annelie Turner', $current_background_identifier),
            $this->instantiateBackground('green-leaf', 'Adam Kring', $current_background_identifier),
            $this->instantiateBackground('green-trees', 'Bruno Kelzer', $current_background_identifier),
            $this->instantiateBackground('led-light', 'Lubo Minar', $current_background_identifier),
            $this->instantiateBackground('ocean-waves', 'Taoh Nichols', $current_background_identifier),
            $this->instantiateBackground('octopus-black', 'Isabel Galvez', $current_background_identifier),
            $this->instantiateBackground('purple-building', 'David Becker', $current_background_identifier),
            $this->instantiateBackground('purple-droplet', 'Mary Ray', $current_background_identifier),
            $this->instantiateBackground('purple-textile', 'Delaney Van', $current_background_identifier),
            $this->instantiateBackground('snow-mountain', 'Tomasz Smal', $current_background_identifier),
            $this->instantiateBackground('tree-water', 'Andreas Gücklhorn', $current_background_identifier),
            $this->instantiateBackground('wooden-surface', 'Ian Dziuk', $current_background_identifier),
            $this->instantiateBackground('white-sheep', 'Will Bolding', $current_background_identifier),
        ];
    }

    /**
     * @psalm-param ValidProjectBackgroundName $identifier
     */
    private function instantiateBackground(
        string $identifier,
        string $author,
        ?string $current_background_identifier
    ): ProjectBackground {
        return ProjectBackground::buildFromIdentifier($identifier, $author, $current_background_identifier === $identifier);
    }
}
