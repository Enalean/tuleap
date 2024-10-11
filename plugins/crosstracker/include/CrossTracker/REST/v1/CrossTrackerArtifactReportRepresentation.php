<?php
/**
 * Copyright (c) Enalean, 2017 - Present. All Rights Reserved.
 *
 *  This file is a part of Tuleap.
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
 *
 */

declare(strict_types=1);

namespace Tuleap\CrossTracker\REST\v1;

use PFUser;
use Tuleap\Project\REST\ProjectReference;
use Tuleap\REST\JsonCast;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\REST\TrackerReference;
use Tuleap\User\Avatar\ProvideUserAvatarUrl;
use Tuleap\User\REST\MinimalUserRepresentation;

/**
 * @psalm-immutable
 */
final readonly class CrossTrackerArtifactReportRepresentation
{
    /**
     * @param MinimalUserRepresentation[] $assigned_to
     */
    private function __construct(
        public int $id,
        public string $title,
        public ?string $status,
        public string $last_update_date,
        public ?MinimalUserRepresentation $submitted_by,
        public array $assigned_to,
        public TrackerReference $tracker,
        public CrossTrackerArtifactBadgeRepresentation $badge,
        public ProjectReference $project,
    ) {
    }

    public static function build(Artifact $artifact, PFUser $user, ProvideUserAvatarUrl $provide_user_avatar_url): self
    {
        $assigned_to = [];
        foreach ($artifact->getAssignedTo($user) as $user_assigned_to) {
            $assigned_to[] = MinimalUserRepresentation::build($user_assigned_to, $provide_user_avatar_url);
        }

        $tracker = $artifact->getTracker();

        return new self(
            JsonCast::toInt($artifact->getId()),
            $artifact->getTitle() ?? '',
            $artifact->getStatus(),
            JsonCast::toDate($artifact->getLastUpdateDate()),
            MinimalUserRepresentation::build($artifact->getSubmittedByUser(), $provide_user_avatar_url),
            $assigned_to,
            TrackerReference::build($tracker),
            new CrossTrackerArtifactBadgeRepresentation(
                $artifact->getUri(),
                $artifact->getTracker()->getColor()->getName(),
                $artifact->getXRef(),
            ),
            new ProjectReference($tracker->getProject()),
        );
    }
}
