<?php
/**
 * Copyright (c) Enalean, 2023 - Present. All Rights Reserved.
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
 * along with Tuleap. If not, see http://www.gnu.org/licenses/.
 */

declare(strict_types=1);

namespace Tuleap\ProgramManagement\Domain\ArtifactLinks;

use Tuleap\ProgramManagement\Domain\Program\Backlog\TimeboxArtifactLinkType;

class DeletedArtifactLinksChecker
{
    public function __construct(private SearchLinkedArtifacts $search_linked_artifacts)
    {
    }

    public function checkArtifactHaveMirroredMilestonesInProvidedDeletedLinks(DeletedArtifactLinksEvent $deleted_artifact_links_event): void
    {
        $deleted_artifact_links_ids = $deleted_artifact_links_event->getDeletedArtifactLinksIds();

        if (empty($deleted_artifact_links_ids)) {
            return;
        }

        if (
            $this->search_linked_artifacts->doesArtifactHaveMirroredMilestonesInProvidedLinks(
                $deleted_artifact_links_event->getUpdatedArtifactId(),
                $deleted_artifact_links_ids,
            )
        ) {
            $deleted_artifact_links_event->setDeletedLinksAreNotValidWithMessage(
                sprintf(
                    dgettext('tuleap-program_management', 'Artifact links with "%s" type cannot be removed.'),
                    TimeboxArtifactLinkType::ART_LINK_SHORT_NAME
                )
            );
        }
    }
}
