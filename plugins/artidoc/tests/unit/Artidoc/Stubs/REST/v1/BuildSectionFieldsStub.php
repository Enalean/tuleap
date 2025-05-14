<?php
/**
 * Copyright (c) Enalean, 2025 - Present. All Rights Reserved.
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

namespace Tuleap\Artidoc\Stubs\REST\v1;

use Tracker_Artifact_Changeset;
use Tuleap\Artidoc\REST\v1\ArtifactSection\Field\BuildSectionFields;
use Tuleap\Artidoc\REST\v1\ArtifactSection\Field\SectionStringFieldRepresentation;

final readonly class BuildSectionFieldsStub implements BuildSectionFields
{
    /**
     * @param list<SectionStringFieldRepresentation> $fields
     */
    private function __construct(private array $fields)
    {
    }

    public static function withoutFields(): self
    {
        return new self([]);
    }

    public function getFields(Tracker_Artifact_Changeset $changeset): array
    {
        return $this->fields;
    }
}
