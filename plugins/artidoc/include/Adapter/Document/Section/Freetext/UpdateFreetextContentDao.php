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

namespace Tuleap\Artidoc\Adapter\Document\Section\Freetext;

use Override;
use Tuleap\Artidoc\Adapter\Document\Section\UpdateLevel;
use Tuleap\Artidoc\Domain\Document\Section\Freetext\FreetextContent;
use Tuleap\Artidoc\Domain\Document\Section\Freetext\Identifier\FreetextIdentifier;
use Tuleap\Artidoc\Domain\Document\Section\Freetext\UpdateFreetextContent;
use Tuleap\Artidoc\Domain\Document\Section\Identifier\SectionIdentifier;
use Tuleap\DB\DataAccessObject;

final class UpdateFreetextContentDao extends DataAccessObject implements UpdateFreetextContent
{
    public function __construct(private readonly UpdateLevel $level_updater)
    {
        parent::__construct();
    }

    #[Override]
    public function updateFreetextContent(
        SectionIdentifier $section_identifier,
        FreetextIdentifier $id,
        FreetextContent $content,
    ): void {
        $this->getDB()->tryFlatTransaction(function () use ($section_identifier, $id, $content) {
            $this->level_updater->updateLevel($section_identifier, $content->level);

            $this->getDB()->update(
                'plugin_artidoc_section_freetext',
                [
                    'title'       => $content->title,
                    'description' => $content->description,
                ],
                [
                    'id' => $id->getBytes(),
                ],
            );
        });
    }
}
