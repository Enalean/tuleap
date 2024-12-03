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
use Tuleap\Artidoc\Document\SaveOneSection;
use Tuleap\Artidoc\Domain\Document\Section\AlreadyExistingSectionWithSameArtifactException;
use Tuleap\Artidoc\Domain\Document\Section\Identifier\InvalidSectionIdentifierStringException;
use Tuleap\Artidoc\Domain\Document\Section\Identifier\SectionIdentifierFactory;
use Tuleap\Artidoc\Domain\Document\Section\UnableToFindSiblingSectionException;
use Tuleap\NeverThrow\Err;
use Tuleap\NeverThrow\Fault;
use Tuleap\NeverThrow\Ok;
use Tuleap\NeverThrow\Result;

final readonly class POSTSectionHandler
{
    public function __construct(
        private RetrieveArtidocWithContext $retrieve_artidoc,
        private BuildSectionRepresentation $section_representation_builder,
        private SaveOneSection $dao,
        private SectionIdentifierFactory $identifier_factory,
        private BuildRequiredArtifactInformation $required_artifact_information_builder,
    ) {
    }

    /**
     * @return Ok<ArtidocSectionRepresentation>|Err<Fault>
     */
    public function handle(int $id, ArtidocPOSTSectionRepresentation $section, \PFUser $user): Ok|Err
    {
        return $this->retrieve_artidoc
            ->retrieveArtidocUserCanWrite($id)
            ->andThen(fn (ArtidocWithContext $artidoc) => $this->required_artifact_information_builder->getRequiredArtifactInformation($artidoc, $section->artifact->id, $user))
            ->andThen(fn (RequiredArtifactInformation $artifact_information) => $this->saveSection($id, $artifact_information, $section, $user));
    }

    /**
     * @return Ok<ArtidocSectionRepresentation>|Err<Fault>
     */
    private function saveSection(
        int $id,
        RequiredArtifactInformation $artifact_information,
        ArtidocPOSTSectionRepresentation $section,
        \PFUser $user,
    ): Ok|Err {
        try {
            $section_id = $section->position
                ? $this->dao->saveSectionBefore($id, $section->artifact->id, $this->identifier_factory->buildFromHexadecimalString($section->position->before))
                : $this->dao->saveSectionAtTheEnd($id, $section->artifact->id);
        } catch (InvalidSectionIdentifierStringException) {
            return Result::err(Fault::fromMessage('Sibling section id is invalid'));
        } catch (AlreadyExistingSectionWithSameArtifactException $exception) {
            return Result::err(AlreadyExistingSectionWithSameArtifactFault::fromThrowable($exception));
        } catch (UnableToFindSiblingSectionException $exception) {
            return Result::err(UnableToFindSiblingSectionFault::fromThrowable($exception));
        }

        return Result::ok($this->section_representation_builder->build($artifact_information, $section_id, $user));
    }
}
