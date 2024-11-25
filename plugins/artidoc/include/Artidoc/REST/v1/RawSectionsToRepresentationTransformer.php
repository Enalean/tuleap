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

use Tracker_Semantic_Description;
use Tracker_Semantic_Title;
use Tuleap\Artidoc\Document\PaginatedRawSections;
use Tuleap\Artidoc\Domain\Document\Section\Identifier\SectionIdentifier;
use Tuleap\NeverThrow\Err;
use Tuleap\NeverThrow\Fault;
use Tuleap\NeverThrow\Ok;
use Tuleap\NeverThrow\Result;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\Artifact\GetFileUploadData;
use Tuleap\Tracker\REST\Artifact\ArtifactFieldValueFileFullRepresentation;
use Tuleap\Tracker\REST\Artifact\ArtifactFieldValueFullRepresentation;
use Tuleap\Tracker\REST\Artifact\ArtifactReference;
use Tuleap\Tracker\REST\Artifact\ArtifactTextFieldValueRepresentation;

final readonly class RawSectionsToRepresentationTransformer implements TransformRawSectionsToRepresentation
{
    public const DEFAULT_TRACKER_REPRESENTATION      = null;
    public const DEFAULT_STATUS_VALUE_REPRESENTATION = null;

    public function __construct(
        private \Tracker_ArtifactDao $artifact_dao,
        private \Tracker_ArtifactFactory $artifact_factory,
        private GetFileUploadData $file_upload_data_provider,
    ) {
    }

    public function getRepresentation(PaginatedRawSections $raw_sections, \PFUser $user): Ok|Err
    {
        return $this->instantiateArtifacts($raw_sections, $user)
            ->andThen(fn (array $artifacts) => $this->instantiateSections($raw_sections, $artifacts, $user))
            ->map(
                /**
                 * @param list<ArtidocSectionRepresentation> $sections
                 */
                static fn (array $sections) => new PaginatedArtidocSectionRepresentationCollection($sections, $raw_sections->total)
            );
    }

    /**
     * @return Ok<list<array{artifact: Artifact, section_identifier: SectionIdentifier}>>|Err<Fault>
     */
    private function instantiateArtifacts(PaginatedRawSections $raw_sections, \PFUser $user): Ok|Err
    {
        $identifiers  = [];
        $artifact_ids = [];
        foreach ($raw_sections->rows as $row) {
            $artifact_ids[]                 = $row->artifact_id;
            $identifiers[$row->artifact_id] = $row->id;
        }
        if (count($artifact_ids) === 0) {
            return Result::ok([]);
        }

        $artifact_order = array_flip($artifact_ids);

        $artifacts = [];
        foreach ($this->artifact_dao->searchByIds($artifact_ids) as $row) {
            $artifact = $this->artifact_factory->getInstanceFromRow($row);
            if (! $artifact->userCanView($user)) {
                return Result::err(Fault::fromMessage('User cannot read one of the artifact of artidoc #' . $raw_sections->id));
            }

            $id = $artifact->getId();

            $artifacts[$artifact_order[$id]] = [
                'artifact'           => $artifact,
                'section_identifier' => $identifiers[$id],
            ];
        }

        ksort($artifacts);

        return Result::ok(array_values($artifacts));
    }

    /**
     * @param list<array{artifact: Artifact, section_identifier: SectionIdentifier}> $artifacts
     * @return Ok<list<ArtidocSectionRepresentation>>|Err<Fault>
     */
    private function instantiateSections(PaginatedRawSections $raw_sections, array $artifacts, \PFUser $user): Ok|Err
    {
        $sections = [];
        foreach ($artifacts as $section) {
            $artifact = $section['artifact'];

            $last_changeset = $artifact->getLastChangeset();
            if ($last_changeset === null) {
                return Result::err(Fault::fromMessage("No changeset for artifact #{$artifact->getId()} of artidoc #{$raw_sections->id}"));
            }

            $title_field = Tracker_Semantic_Title::load($artifact->getTracker())->getField();
            if (! $title_field) {
                return Result::err(Fault::fromMessage("There is no title field for artifact #{$artifact->getId()} of artidoc #{$raw_sections->id}"));
            }
            if (! $title_field->userCanRead($user)) {
                return Result::err(Fault::fromMessage("User cannot read title of artifact #{$artifact->getId()} of artidoc #{$raw_sections->id}"));
            }

            $title = $title_field->getFullRESTValue($user, $last_changeset);
            if (! $title instanceof ArtifactFieldValueFullRepresentation && ! $title instanceof ArtifactTextFieldValueRepresentation) {
                return Result::err(Fault::fromMessage("There is no title data for artifact #{$artifact->getId()} of artidoc #{$raw_sections->id}"));
            }

            $description_field = Tracker_Semantic_Description::load($artifact->getTracker())->getField();
            if (! $description_field) {
                return Result::err(Fault::fromMessage("There is no description field for artifact #{$artifact->getId()} of artidoc #{$raw_sections->id}"));
            }
            if (! $description_field->userCanRead($user)) {
                return Result::err(Fault::fromMessage("User cannot read title of artifact #{$artifact->getId()} of artidoc #{$raw_sections->id}"));
            }

            $description = $description_field->getFullRESTValue($user, $last_changeset);
            if (! $description instanceof ArtifactTextFieldValueRepresentation) {
                return Result::err(Fault::fromMessage("There is no description data for artifact #{$artifact->getId()} of artidoc #{$raw_sections->id}"));
            }

            $can_user_edit_section = $title_field->userCanUpdate($user) && $description_field->userCanUpdate($user);

            $file_upload_data = $this->file_upload_data_provider->getFileUploadData($artifact->getTracker(), $artifact, $user);

            $attachments = null;
            if ($file_upload_data) {
                $attachments = $file_upload_data->getField()->getRESTValue($user, $last_changeset)
                    ?? ArtifactFieldValueFileFullRepresentation::fromEmptyValues($file_upload_data->getField());
            }

            $sections[] = new ArtidocSectionRepresentation(
                $section['section_identifier']->toString(),
                ArtifactReference::build($artifact),
                $title,
                $description,
                $can_user_edit_section,
                $attachments
            );
        }

        return Result::ok($sections);
    }
}
