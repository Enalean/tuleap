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

final class ArtifactLinksNewTypesChecker
{
    public function __construct(private SearchLinkedArtifacts $search_linked_artifacts)
    {
    }

    public function checkArtifactHaveMirroredMilestonesInProvidedLinks(
        ProvidedArtifactLinksTypesEvent $provided_artifact_links_types_event,
    ): void {
        $provided_links_without_system_types = [];
        foreach ($provided_artifact_links_types_event->getProvidedArtifactLinksTypes() as $linked_artifact_id => $type) {
            if ($type !== TimeboxArtifactLinkType::ART_LINK_SHORT_NAME) {
                $provided_links_without_system_types[] = $linked_artifact_id;
            }
        }

        if (empty($provided_links_without_system_types)) {
            return;
        }

        if (
            $this->search_linked_artifacts->doesArtifactHaveMirroredMilestonesInProvidedLinks(
                $provided_artifact_links_types_event->getUpdatedArtifactId(),
                $provided_links_without_system_types,
            )
        ) {
            $provided_artifact_links_types_event->setProvidedLinksAreNotValidWithMessage(
                sprintf(
                    dgettext('tuleap-program_management', 'Artifact links with "%s" type cannot be updated.'),
                    TimeboxArtifactLinkType::ART_LINK_SHORT_NAME
                )
            );
        }
    }
}
