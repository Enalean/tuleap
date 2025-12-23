<?php
/**
 * Copyright (c) Enalean, 2020-Present. All Rights Reserved.
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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

declare(strict_types=1);

namespace Tuleap\Gitlab\Reference;

use Psr\EventDispatcher\EventDispatcherInterface;
use Tuleap\Reference\GetProjectIdForSystemReferenceEvent;
use Tuleap\Tracker\Artifact\Artifact;

readonly class TuleapReferenceRetriever
{
    public function __construct(
        private EventDispatcherInterface $event_manager,
        private \ReferenceManager $reference_manager,
    ) {
    }

    /**
     * @throws TuleapReferencedArtifactNotFoundException
     * @throws TuleapReferenceNotFoundException
     */
    public function retrieveTuleapReference(int $artifact_id): \Reference
    {
        $project_id         = $this->getArtifactProjectId($artifact_id);
        $external_reference = $this->reference_manager->loadReferenceFromKeyword(
            'art',
            $artifact_id
        );

        if (! $external_reference) {
            throw new TuleapReferenceNotFoundException();
        }

        // Set group_id otherwise it is always 100 (#legacycode)
        $external_reference->setGroupId($project_id);

        return $external_reference;
    }

    /**
     * @throws TuleapReferencedArtifactNotFoundException
     */
    private function getArtifactProjectId(int $artifact_id): int
    {
        $artifact_project_id = $this->event_manager
            ->dispatch(new GetProjectIdForSystemReferenceEvent(Artifact::REFERENCE_NATURE, (string) $artifact_id))
            ->getProjectId();

        if ($artifact_project_id === null) {
            throw new TuleapReferencedArtifactNotFoundException(
                $artifact_id
            );
        }

        return $artifact_project_id;
    }
}
