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

class TuleapReferenceRetriever
{
    /**
     * @var \EventManager
     */
    private $event_manager;
    /**
     * @var \ReferenceManager
     */
    private $reference_manager;

    public function __construct(
        \EventManager $event_manager,
        \ReferenceManager $reference_manager,
    ) {
        $this->event_manager     = $event_manager;
        $this->reference_manager = $reference_manager;
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
        $external_reference->setGroupId((int) $project_id);

        return $external_reference;
    }

    /**
     * @throws TuleapReferencedArtifactNotFoundException
     */
    private function getArtifactProjectId(int $artifact_id): string
    {
        $artifact_project_id = null;
        $this->event_manager->processEvent(
            \Event::GET_ARTIFACT_REFERENCE_GROUP_ID,
            [
                'artifact_id' => $artifact_id,
                'group_id' => &$artifact_project_id,
            ]
        );

        if ($artifact_project_id === null) {
            throw new TuleapReferencedArtifactNotFoundException(
                $artifact_id
            );
        }

        return $artifact_project_id;
    }
}
