<?php
/**
 * Copyright (c) Enalean, 2021 - Present. All Rights Reserved.
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

namespace Tuleap\ProgramManagement\Adapter\Program\Feature;

use Cardwall_Semantic_CardFields;
use Tuleap\Cardwall\BackgroundColor\BackgroundColorBuilder;
use Tuleap\ProgramManagement\Adapter\Workspace\RetrieveUser;
use Tuleap\ProgramManagement\Adapter\Workspace\Tracker\Artifact\RetrieveFullArtifact;
use Tuleap\ProgramManagement\Domain\Program\Backlog\Feature\BackgroundColor;
use Tuleap\ProgramManagement\Domain\Program\Feature\RetrieveBackgroundColor;
use Tuleap\ProgramManagement\Domain\Workspace\Tracker\Artifact\ArtifactIdentifier;
use Tuleap\ProgramManagement\Domain\Workspace\UserIdentifier;

final class BackgroundColorRetriever implements RetrieveBackgroundColor
{
    public function __construct(
        private BackgroundColorBuilder $background_color_builder,
        private RetrieveFullArtifact $artifact_retriever,
        private RetrieveUser $retrieve_user,
    ) {
    }

    #[\Override]
    public function retrieveBackgroundColor(ArtifactIdentifier $artifact_identifier, UserIdentifier $user_identifier): BackgroundColor
    {
        $artifact = $this->artifact_retriever->getNonNullArtifact($artifact_identifier);
        $user     = $this->retrieve_user->getUserWithId($user_identifier);

        $card_fields_semantic = Cardwall_Semantic_CardFields::load($artifact->getTracker());
        $background_color     = $this->background_color_builder->build(
            $card_fields_semantic,
            $artifact,
            $user
        );

        return new BackgroundColor($background_color->getBackgroundColorName());
    }
}
