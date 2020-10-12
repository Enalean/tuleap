<?php
/**
 * Copyright (c) Enalean, 2020-Present. All Rights Reserved.
 *
 *  This file is a part of Tuleap.
 *
 *  Tuleap is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  Tuleap is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace Tuleap\Project\ProjectBackground;

/**
 * @psalm-immutable
 * @psalm-type ValidProjectBackgroundName = value-of<ProjectBackgroundName::ALLOWED>
 */
final class ProjectBackgroundName
{
    public const ALLOWED = [
        'aerial-water',
        'asphalt-rock',
        'beach-daytime',
        'blue-rain',
        'blue-sand',
        'brown-desert',
        'brown-grass',
        'brown-textile',
        'brush-daytime',
        'green-grass',
        'green-leaf',
        'green-trees',
        'led-light',
        'ocean-waves',
        'octopus-black',
        'purple-building',
        'purple-droplet',
        'purple-textile',
        'snow-mountain',
        'tree-water',
        'white-sheep',
        'wooden-surface',
    ];

    /**
     * @var string
     * @psalm-var ValidProjectBackgroundName
     */
    private $identifier;

    /**
     * @psalm-param ValidProjectBackgroundName $name
     */
    private function __construct(string $name)
    {
        $this->identifier = $name;
    }

    /**
     * @psalm-param ValidProjectBackgroundName $name
     */
    public static function fromIdentifier(string $name): self
    {
        return new self($name);
    }

    /**
     * @psalm-return ValidProjectBackgroundName
     */
    public function getIdentifier(): string
    {
        return $this->identifier;
    }
}
