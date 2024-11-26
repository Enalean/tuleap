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

namespace Tuleap\Artidoc\Document;

use Tuleap\Artidoc\Domain\Document\Section\AlreadyExistingSectionWithSameArtifactException;
use Tuleap\Artidoc\Domain\Document\Section\Identifier\SectionIdentifier;
use Tuleap\Artidoc\Domain\Document\Section\UnableToFindSiblingSectionException;

interface SaveOneSection
{
    /**
     * @throws AlreadyExistingSectionWithSameArtifactException
     */
    public function saveSectionAtTheEnd(int $item_id, int $artifact_id): SectionIdentifier;

    /**
     * @throws AlreadyExistingSectionWithSameArtifactException
     * @throws UnableToFindSiblingSectionException
     */
    public function saveSectionBefore(int $item_id, int $artifact_id, SectionIdentifier $sibling_section_id): SectionIdentifier;
}
