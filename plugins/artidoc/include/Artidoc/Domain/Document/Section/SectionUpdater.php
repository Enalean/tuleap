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

namespace Tuleap\Artidoc\Domain\Document\Section;

use Tuleap\Artidoc\Domain\Document\Section\Freetext\FreetextContent;
use Tuleap\Artidoc\Domain\Document\Section\Freetext\RetrievedSectionContentFreetext;
use Tuleap\Artidoc\Domain\Document\Section\Freetext\UpdateFreetextContent;
use Tuleap\Artidoc\Domain\Document\Section\Identifier\SectionIdentifier;
use Tuleap\NeverThrow\Err;
use Tuleap\NeverThrow\Fault;
use Tuleap\NeverThrow\Ok;
use Tuleap\NeverThrow\Result;

final readonly class SectionUpdater
{
    public function __construct(private RetrieveSection $retriever, private UpdateFreetextContent $update_freetext)
    {
    }

    /**
     * @return Ok<null>|Err<Fault>
     */
    public function update(SectionIdentifier $section_identifier, string $title, string $description): Ok|Err
    {
        $title = trim($title);
        if ($title === '') {
            return Result::err(EmptyTitleFault::build());
        }

        return $this->retriever
            ->retrieveSectionUserCanWrite($section_identifier)
            ->andThen(fn (RetrievedSection $section) => $section->content->apply(
                $this->updateArtifactSection(...),
                fn (RetrievedSectionContentFreetext $freetext) => $this->updateFreetextSection($freetext, $title, $description),
            ));
    }

    /**
     * @return Ok<null>|Err<Fault>
     */
    private function updateArtifactSection(): Ok|Err
    {
        return Result::err(UnableToUpdateArtifactSectionFault::build());
    }

    /**
     * @return Ok<null>|Err<Fault>
     */
    private function updateFreetextSection(RetrievedSectionContentFreetext $freetext, string $title, string $description): Ok|Err
    {
        $this->update_freetext->updateFreetextContent($freetext->id, new FreetextContent($title, $description));

        return Result::ok(null);
    }
}
