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

namespace Tuleap\Artidoc\REST\v1\ArtifactSection;

use Override;
use Tracker_Artifact_ChangesetValue_Text;
use Tuleap\Artidoc\Domain\Document\ArtidocWithContext;
use Tuleap\NeverThrow\Err;
use Tuleap\NeverThrow\Fault;
use Tuleap\NeverThrow\Ok;
use Tuleap\NeverThrow\Result;
use Tuleap\Tracker\Artifact\RetrieveArtifact;
use Tuleap\Tracker\Semantic\Description\RetrieveSemanticDescriptionField;
use Tuleap\Tracker\Semantic\Title\RetrieveSemanticTitleField;

final readonly class RequiredArtifactInformationBuilder implements BuildRequiredArtifactInformation
{
    public function __construct(
        private RetrieveArtifact $artifact_retriever,
        private RetrieveSemanticDescriptionField $retrieve_description_field,
        private RetrieveSemanticTitleField $retrieve_semantic_title_field,
    ) {
    }

    #[Override]
    public function getRequiredArtifactInformation(ArtidocWithContext $artidoc, int $artifact_id, \PFUser $user): Ok|Err
    {
        $artifact = $this->artifact_retriever->getArtifactById($artifact_id);
        if (! $artifact || ! $artifact->userCanView($user)) {
            return Result::err(Fault::fromMessage(
                sprintf(
                    'User cannot read artifact #%s',
                    $artifact_id,
                )
            ));
        }

        $last_changeset = $artifact->getLastChangeset();
        if ($last_changeset === null) {
            return Result::err(Fault::fromMessage(
                sprintf(
                    'No changeset for artifact #%s of artidoc #%s',
                    $artifact->getId(),
                    $artidoc->document->getId(),
                )
            ));
        }

        $tracker     = $artifact->getTracker();
        $title_field = $this->retrieve_semantic_title_field->fromTracker($tracker);
        if (! $title_field) {
            return Result::err(Fault::fromMessage(
                sprintf(
                    'There is no title field for artifact #%s of artidoc #%s',
                    $artifact->getId(),
                    $artidoc->document->getId(),
                )
            ));
        }
        if (! $title_field->userCanRead($user)) {
            return Result::err(Fault::fromMessage(
                sprintf(
                    'User cannot read title of artifact #%s of artidoc #%s',
                    $artifact->getId(),
                    $artidoc->document->getId(),
                )
            ));
        }

        $title_field_value = $last_changeset->getValue($title_field);
        if (! $title_field_value instanceof Tracker_Artifact_ChangesetValue_Text) {
            return Result::err(Fault::fromMessage(
                sprintf(
                    'There is no title data for artifact #%s of artidoc #%s',
                    $artifact->getId(),
                    $artidoc->document->getId(),
                )
            ));
        }
        $title = $title_field_value->getContentAsText();

        $description_field = $this->retrieve_description_field->fromTracker($tracker);
        if (! $description_field) {
            return Result::err(Fault::fromMessage(
                sprintf(
                    'There is no description field for artifact #%s of artidoc #%s',
                    $artifact->getId(),
                    $artidoc->document->getId(),
                )
            ));
        }
        if (! $description_field->userCanRead($user)) {
            return Result::err(Fault::fromMessage(
                sprintf(
                    'User cannot read title of artifact #%s of artidoc #%s',
                    $artifact->getId(),
                    $artidoc->document->getId(),
                )
            ));
        }

        $description_field_value = $last_changeset->getValue($description_field);
        if (! $description_field_value instanceof Tracker_Artifact_ChangesetValue_Text) {
            return Result::err(Fault::fromMessage(
                sprintf(
                    'There is no description data for artifact #%s of artidoc #%s',
                    $artifact->getId(),
                    $artidoc->document->getId(),
                )
            ));
        }

        $textual_formats = [
            Tracker_Artifact_ChangesetValue_Text::COMMONMARK_CONTENT,
            Tracker_Artifact_ChangesetValue_Text::TEXT_CONTENT,
        ];
        $description     = in_array($description_field_value->getFormat(), $textual_formats, true)
            ? Tracker_Artifact_ChangesetValue_Text::getCommonMarkInterpreter(\Codendi_HTMLPurifier::instance())
                ->getInterpretedContent($description_field_value->getText())
            : $description_field_value->getText();

        return Result::ok(new RequiredArtifactInformation(
            $last_changeset,
            $title_field,
            $title,
            $description_field,
            $description,
        ));
    }
}
