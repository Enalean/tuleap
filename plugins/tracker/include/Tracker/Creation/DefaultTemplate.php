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

class DefaultTemplate
{
    /**
     * @var TrackerTemplatesRepresentation
     */
    private $representation;
    /**
     * @var string
     */
    private $xml_file;

    public function __construct(TrackerTemplatesRepresentation $representation, string $xml_file)
    {
        $this->representation = $representation;
        $this->xml_file = $xml_file;
    }

    public function getRepresentation(): TrackerTemplatesRepresentation
    {
        return $this->representation;
    }

    public function getXmlFile(): string
    {
        return $this->xml_file;
    }
}
