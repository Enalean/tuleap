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
use Tuleap\Artidoc\Document\PaginatedRawSections;
use Tuleap\Artidoc\Document\RawSection;
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
        private TransformRawSectionsToRepresentation $transformer,
        private SaveOneSection $dao,
        private SectionIdentifierFactory $identifier_factory,
    ) {
    }

    /**
     * @return Ok<ArtidocSectionRepresentation>|Err<Fault>
     */
    public function handle(int $id, ArtidocPOSTSectionRepresentation $section, \PFUser $user): Ok|Err
    {
        return $this->retrieve_artidoc
            ->retrieveArtidocUserCanWrite($id)
            ->andThen(fn (ArtidocWithContext $document_information) => $this->getSectionRepresentationToMakeSureThatUserCanReadIt($document_information, $section, $user))
            ->andThen(fn (ArtidocSectionRepresentation $section_representation) => $this->saveSection($id, $section_representation, $section));
    }

    /**
     * @return Ok<ArtidocSectionRepresentation>|Err<Fault>
     */
    private function getSectionRepresentationToMakeSureThatUserCanReadIt(
        ArtidocWithContext $document_information,
        ArtidocPOSTSectionRepresentation $section,
        \PFUser $user,
    ): Ok|Err {
        $dummy_identifier = $this->identifier_factory->buildIdentifier();

        $item_id = $document_information->document->getId();
        return $this->transformer
            ->getRepresentation(
                new PaginatedRawSections(
                    $item_id,
                    [RawSection::fromRow(['id' => $dummy_identifier, 'artifact_id' => $section->artifact->id, 'item_id' => $item_id, 'rank' => 0])],
                    1,
                ),
                $user,
            )->andThen($this->getFirstAndOnlySectionFromCollection(...));
    }

    /**
     * @return Ok<ArtidocSectionRepresentation>|Err<Fault>
     */
    private function getFirstAndOnlySectionFromCollection(
        PaginatedArtidocSectionRepresentationCollection $collection,
    ): Ok|Err {
        if (count($collection->sections) !== 1) {
            return Result::err(Fault::fromMessage('We should have exactly one matching section'));
        }

        return Result::ok($collection->sections[0]);
    }

    /**
     * @return Ok<ArtidocSectionRepresentation>|Err<Fault>
     */
    private function saveSection(
        int $id,
        ArtidocSectionRepresentation $section_representation,
        ArtidocPOSTSectionRepresentation $section,
    ): Ok|Err {
        \BackendLogger::getDefaultLogger()->info(var_export($section, true));
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

        return Result::ok(ArtidocSectionRepresentation::fromRepresentationWithId($section_representation, $section_id));
    }
}
