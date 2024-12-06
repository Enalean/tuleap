<?php
/**
 * Copyright (c) Enalean, 2024 - Present. All Rights Reserved.
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

namespace Tuleap\Artidoc\REST\v1;

/**
 * @psalm-immutable
 */
final class ArtidocPOSTSectionRepresentation
{
    public ArtidocPOSTSectionArtifactRepresentation $artifact;

    /**
     * @var \Tuleap\Artidoc\REST\v1\ArtidocPOSTSectionPositionBeforeRepresentation | null The position {@type \Tuleap\Artidoc\REST\v1\ArtidocPOSTSectionPositionBeforeRepresentation} {@required false}
     */
    public mixed $position;

    public function __construct(
        ArtidocPOSTSectionArtifactRepresentation $artifact,
        ?ArtidocPOSTSectionPositionBeforeRepresentation $position,
    ) {
        $this->artifact = $artifact;
        $this->position = $position;
    }
}
