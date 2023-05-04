<?php
/**
 * Copyright (c) Enalean, 2023-present. All Rights Reserved.
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
 *  along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace Tuleap\Tracker\Semantic;

use PFUser;
use Tracker;
use Tuleap\Tracker\Permission\VerifySubmissionPermissions;

final class ArtifactCannotBeCreatedReasonsGetter
{
    public function __construct(
        private readonly VerifySubmissionPermissions $can_submit_artifact_verifier,
    ) {
    }

    public function getCannotCreateArtifactReasons(CollectionOfCreationSemanticToCheck $semantics_to_check, Tracker $tracker, PFUser $user): CollectionOfCannotCreateArtifactReason
    {
        $cannot_create_reasons = CollectionOfCannotCreateArtifactReason::fromEmptyReason();

        if ($semantics_to_check->isEmpty()) {
            return $cannot_create_reasons;
        }

        return $cannot_create_reasons->addReasons($this->canUserCreateArtifact($tracker, $user));
    }

    private function canUserCreateArtifact(Tracker $tracker, PFUser $user): CollectionOfCannotCreateArtifactReason
    {
        $cannot_create_reasons = CollectionOfCannotCreateArtifactReason::fromEmptyReason();

        if (! $this->can_submit_artifact_verifier->canUserSubmitArtifact($user, $tracker)) {
            $cannot_create_reasons = $cannot_create_reasons->addReason(CannotCreateArtifactReason::fromString(dgettext('tuleap-tracker', 'You can\'t submit an artifact because you do not have the right to submit all required fields')));
        }
        return $cannot_create_reasons;
    }
}
