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

use Tuleap\Artidoc\Domain\Document\ArtidocWithContext;
use Tuleap\Artidoc\Domain\Document\RetrieveArtidocWithContext;
use Tuleap\Artidoc\Document\SaveSections;
use Tuleap\NeverThrow\Err;
use Tuleap\NeverThrow\Fault;
use Tuleap\NeverThrow\Ok;
use Tuleap\NeverThrow\Result;

final readonly class PUTSectionsHandler
{
    public function __construct(
        private RetrieveArtidocWithContext $retrieve_artidoc,
        private BuildRequiredArtifactInformation $required_artifact_information_builder,
        private SaveSections $dao,
    ) {
    }

    /**
     * @param ArtidocPUTSectionRepresentation[] $sections
     * @return Ok<true>|Err<Fault>
     */
    public function handle(int $id, array $sections, \PFUser $user): Ok|Err
    {
        return $this->retrieve_artidoc
            ->retrieveArtidocUserCanWrite($id)
            ->andThen(fn(ArtidocWithContext $artidoc) => $this->ensureThatUserCanReadAllNewSections($artidoc, $sections, $user))
            ->andThen(fn() => $this->saveSections($id, $sections));
    }

    /**
     * @param ArtidocPUTSectionRepresentation[] $sections
     * @return Ok<true>|Err<Fault>
     */
    private function ensureThatUserCanReadAllNewSections(ArtidocWithContext $artidoc, array $sections, \PFUser $user): Ok|Err
    {
        foreach ($sections as $section) {
            $result = $this->required_artifact_information_builder
                ->getRequiredArtifactInformation($artidoc, $section->artifact->id, $user);

            if (Result::isErr($result)) {
                return Result::err(UserCannotReadSectionFault::fromFault($result->error));
            }
        }

        return Result::ok(true);
    }

    /**
     * @param ArtidocPUTSectionRepresentation[] $sections
     * @return Ok<true>|Err<Fault>
     */
    private function saveSections(int $id, array $sections): Ok|Err
    {
        $this->dao->save(
            $id,
            array_map(
                static fn (ArtidocPUTSectionRepresentation $section) => $section->artifact->id,
                $sections,
            ),
        );

        return Result::ok(true);
    }
}
