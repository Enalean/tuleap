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

namespace Tuleap\Artidoc\Adapter\Document\Section;

use Tuleap\Artidoc\Domain\Document\Section\DeleteOneSection;
use Tuleap\Artidoc\Domain\Document\Section\Identifier\SectionIdentifier;
use Tuleap\DB\DataAccessObject;

final class DeleteOneSectionDao extends DataAccessObject implements DeleteOneSection
{
    public function deleteSectionById(SectionIdentifier $section_id): void
    {
        $this->getDB()->run(
            <<<EOS
            DELETE section, section_version, freetext
            FROM plugin_artidoc_section AS section
                INNER JOIN plugin_artidoc_section_version AS section_version
                    ON (section.id = section_version.section_id)
                LEFT JOIN plugin_artidoc_section_freetext AS freetext
                    ON (section_version.freetext_id = freetext.id)
            WHERE section.id = ?
            EOS,
            $section_id->getBytes(),
        );
    }
}
