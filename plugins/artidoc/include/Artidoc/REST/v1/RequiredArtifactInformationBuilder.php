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
use Tuleap\Artidoc\Domain\Document\ArtidocWithContext;
use Tuleap\NeverThrow\Err;
use Tuleap\NeverThrow\Fault;
use Tuleap\NeverThrow\Ok;
use Tuleap\NeverThrow\Result;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\Artifact\RetrieveArtifact;
use Tuleap\Tracker\REST\Artifact\ArtifactFieldValueFullRepresentation;
use Tuleap\Tracker\REST\Artifact\ArtifactTextFieldValueRepresentation;

final readonly class RequiredArtifactInformationBuilder implements BuildRequiredArtifactInformation
{
    public function __construct(private RetrieveArtifact $artifact_retriever)
    {
    }

    public function getRequiredArtifactInformation(ArtidocWithContext $artidoc, Artifact|int $artifact, \PFUser $user): Ok|Err
    {
        $artifact = $artifact instanceof Artifact ? $artifact : $this->artifact_retriever->getArtifactById($artifact);
        if (! $artifact || ! $artifact->userCanView($user)) {
            return Result::err(Fault::fromMessage('User cannot read artifact #{$artifact->getId()}'));
        }

        $last_changeset = $artifact->getLastChangeset();
        if ($last_changeset === null) {
            return Result::err(Fault::fromMessage("No changeset for artifact #{$artifact->getId()} of artidoc #{$artidoc->document->getId()}"));
        }

        $title_field = Tracker_Semantic_Title::load($artifact->getTracker())->getField();
        if (! $title_field) {
            return Result::err(Fault::fromMessage("There is no title field for artifact #{$artifact->getId()} of artidoc #{$artidoc->document->getId()}"));
        }
        if (! $title_field->userCanRead($user)) {
            return Result::err(Fault::fromMessage("User cannot read title of artifact #{$artifact->getId()} of artidoc #{$artidoc->document->getId()}"));
        }

        $title = $title_field->getFullRESTValue($user, $last_changeset);
        if (! $title instanceof ArtifactFieldValueFullRepresentation && ! $title instanceof ArtifactTextFieldValueRepresentation) {
            return Result::err(Fault::fromMessage("There is no title data for artifact #{$artifact->getId()} of artidoc #{$artidoc->document->getId()}"));
        }

        $description_field = Tracker_Semantic_Description::load($artifact->getTracker())->getField();
        if (! $description_field) {
            return Result::err(Fault::fromMessage("There is no description field for artifact #{$artifact->getId()} of artidoc #{$artidoc->document->getId()}"));
        }
        if (! $description_field->userCanRead($user)) {
            return Result::err(Fault::fromMessage("User cannot read title of artifact #{$artifact->getId()} of artidoc #{$artidoc->document->getId()}"));
        }

        $description = $description_field->getFullRESTValue($user, $last_changeset);
        if (! $description instanceof ArtifactTextFieldValueRepresentation) {
            return Result::err(Fault::fromMessage("There is no description data for artifact #{$artifact->getId()} of artidoc #{$artidoc->document->getId()}"));
        }

        return Result::ok(new RequiredArtifactInformation($last_changeset, $title_field, $title, $description_field, $description));
    }
}
