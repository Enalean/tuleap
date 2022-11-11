<?php
/**
 * Copyright (c) Enalean, 2022 - Present. All Rights Reserved.
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

namespace Tuleap\Tracker\Reference;

use Tracker_ArtifactFactory;
use Tuleap\Reference\CheckCrossReferenceValidityEvent;
use Tuleap\Tracker\Artifact\Artifact;

class CrossReferenceValidator
{
    public function __construct(private Tracker_ArtifactFactory $artifact_factory)
    {
    }

    public function removeInvalidCrossReferences(CheckCrossReferenceValidityEvent $event): void
    {
        foreach ($event->getCrossReferences() as $key => $cross_reference) {
            if ($cross_reference->getRefSourceType() === Artifact::REFERENCE_NATURE) {
                $artifact_id = (int) $cross_reference->getRefSourceId();
                if ($this->removeReferenceIfNeeded($artifact_id, $event, (int) $key)) {
                    continue;
                }
            }

            if ($cross_reference->getRefTargetType() === Artifact::REFERENCE_NATURE) {
                $artifact_id = (int) $cross_reference->getRefTargetId();
                $this->removeReferenceIfNeeded($artifact_id, $event, (int) $key);
            }
        }
    }

    private function removeReferenceIfNeeded(int $artifact_id, CheckCrossReferenceValidityEvent $event, int $key): bool
    {
        $artifact = $this->artifact_factory->getArtifactById($artifact_id);
        if ($artifact === null || ! $artifact->userCanView($event->getUser())) {
            $event->removeInvalidCrossReference($key);
            return true;
        }

        return false;
    }
}
