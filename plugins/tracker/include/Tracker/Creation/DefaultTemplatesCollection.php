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

namespace Tuleap\Tracker\Creation;

class DefaultTemplatesCollection
{
    public const NAME = 'defaultTemplatesCollection';

    /**
     * @var array<string, DefaultTemplate>
     */
    private $default_templates = [];

    /**
     * @return TrackerTemplatesRepresentation[]
     */
    public function getSortedDefaultTemplatesRepresentations(): array
    {
        $representations = array_map(
            static function (DefaultTemplate $template): TrackerTemplatesRepresentation {
                return $template->getRepresentation();
            },
            $this->default_templates
        );

        usort(
            $representations,
            static function (TrackerTemplatesRepresentation $a, TrackerTemplatesRepresentation $b) {
                return strnatcasecmp($a->name, $b->name);
            }
        );

        return $representations;
    }

    public function has(string $name): bool
    {
        return isset($this->default_templates[$name]);
    }

    public function getXmlFile(string $name): string
    {
        if (!isset($this->default_templates[$name])) {
            throw new \OutOfBoundsException("Unable to find default template $name");
        }

        return $this->default_templates[$name]->getXmlFile();
    }

    public function add(string $name, DefaultTemplate $template): void
    {
        $this->default_templates[$name] = $template;
    }
}
