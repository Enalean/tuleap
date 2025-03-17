<?php
/**
 * Copyright (c) Enalean, 2025-Present. All Rights Reserved.
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

namespace Tuleap\Tracker\FormElement\Field\ArtifactLink;

use PFUser;
use Tracker;
use Tracker_FormElement_Field_ArtifactLink;
use Tuleap\Option\Option;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\Hierarchy\ParentInHierarchyRetriever;
use Tuleap\Tracker\Permission\RetrieveUserPermissionOnTrackers;
use Tuleap\Tracker\Permission\TrackerPermissionType;

final readonly class EditorWithReverseLinksBuilder
{
    public function __construct(
        private ParentInHierarchyRetriever $parent_tracker_retriever,
        private RetrieveUserPermissionOnTrackers $tracker_permissions_retriever,
    ) {
    }

    public function build(
        Tracker_FormElement_Field_ArtifactLink $link_field,
        Artifact $current_artifact,
        PFUser $user,
    ): EditorWithReverseLinksPresenter {
        $current_tracker = $current_artifact->getTracker();
        $parent_tracker  = $this->parent_tracker_retriever->getParentTracker($current_tracker)
            ->andThen(function (\Tracker $parent) use ($user) {
                $permissions               = $this->tracker_permissions_retriever->retrieveUserPermissionOnTrackers(
                    $user,
                    [$parent],
                    TrackerPermissionType::PERMISSION_VIEW
                );
                $parent_tracker_is_allowed = array_search($parent, $permissions->allowed, true);
                if ($parent_tracker_is_allowed !== false) {
                    return Option::fromValue($parent);
                }
                return Option::nothing(Tracker::class);
            })
            ->unwrapOr(null);
        return new EditorWithReverseLinksPresenter(
            $link_field,
            $current_artifact,
            $current_tracker,
            $parent_tracker,
        );
    }
}
