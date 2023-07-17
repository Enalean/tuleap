<?php
/**
 * Copyright (c) Enalean, 2023 - present. All Rights Reserved.
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

namespace Tuleap\Tracker\Test\Builders;

use Tracker_XML_Importer_ArtifactImportedMapping;

final class ArtifactImportedMappingBuilder
{
    /**
     * @psalm-param array{ source_id: int, destination_id: int }
     */
    public static function fromSourcesAndDestinations(array $sources_and_destinations): Tracker_XML_Importer_ArtifactImportedMapping
    {
        $mapping = new Tracker_XML_Importer_ArtifactImportedMapping();

        foreach ($sources_and_destinations as $source_and_destination) {
            $mapping->add($source_and_destination->source_id, $source_and_destination->destination_id);
        }

        return $mapping;
    }
}
