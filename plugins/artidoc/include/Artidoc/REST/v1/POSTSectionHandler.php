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

use Tuleap\Artidoc\Document\ArtidocDocumentInformation;
use Tuleap\Artidoc\Document\PaginatedRawSections;
use Tuleap\Artidoc\Document\RetrieveArtidoc;
use Tuleap\Artidoc\Document\SaveOneSection;
use Tuleap\DB\DatabaseUUIDFactory;
use Tuleap\NeverThrow\Err;
use Tuleap\NeverThrow\Fault;
use Tuleap\NeverThrow\Ok;
use Tuleap\NeverThrow\Result;

final readonly class POSTSectionHandler
{
    public function __construct(
        private RetrieveArtidoc $retrieve_artidoc,
        private TransformRawSectionsToRepresentation $transformer,
        private SaveOneSection $dao,
        private DatabaseUUIDFactory $uuid_factory,
    ) {
    }

    /**
     * @return Ok<ArtidocSectionRepresentation>|Err<Fault>
     */
    public function handle(int $id, ArtidocPOSTSectionRepresentation $section, \PFUser $user): Ok|Err
    {
        return $this->retrieve_artidoc
            ->retrieveArtidoc($id, $user)
            ->andThen(fn (ArtidocDocumentInformation $document_information) => $this->ensureThatUserCanWriteDocument($document_information, $user))
            ->andThen(fn (ArtidocDocumentInformation $document_information) => $this->getSectionRepresentationToMakeSureThatUserCanReadIt($document_information, $section, $user))
            ->andThen(fn (ArtidocSectionRepresentation $section_representation) => $this->saveSection($id, $section_representation, $section));
    }

    /**
     * @return Ok<ArtidocDocumentInformation>|Err<Fault>
     */
    private function ensureThatUserCanWriteDocument(ArtidocDocumentInformation $document_information, \PFUser $user): Ok|Err
    {
        $permissions_manager = \Docman_PermissionsManager::instance((int) $document_information->document->getGroupId());
        if (! $permissions_manager->userCanWrite($user, (int) $document_information->document->getId())) {
            return Result::err(Fault::fromMessage('User cannot write document'));
        }

        return Result::ok($document_information);
    }

    /**
     * @return Ok<ArtidocSectionRepresentation>|Err<Fault>
     */
    private function getSectionRepresentationToMakeSureThatUserCanReadIt(
        ArtidocDocumentInformation $document_information,
        ArtidocPOSTSectionRepresentation $section,
        \PFUser $user,
    ): Ok|Err {
        $dummy_uuid = $this->uuid_factory->buildUUIDFromBytesData($this->uuid_factory->buildUUIDBytes());

        return $this->transformer
            ->getRepresentation(
                new PaginatedRawSections(
                    (int) $document_information->document->getId(),
                    [['id' => $dummy_uuid, 'artifact_id' => $section->artifact->id]],
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
        $uuid = $section->position
            ? $this->dao->saveSectionBefore($id, $section->artifact->id, $section->position->before)
            : $this->dao->saveSectionAtTheEnd($id, $section->artifact->id);

        return Result::ok(ArtidocSectionRepresentation::fromRepresentationWithId($section_representation, $uuid));
    }
}
