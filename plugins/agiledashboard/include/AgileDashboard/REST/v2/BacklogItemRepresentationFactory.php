<?php
/**
 * Copyright (c) Enalean, 2014-Present. All Rights Reserved.
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

namespace Tuleap\AgileDashboard\REST\v2;

use Cardwall_Semantic_CardFields;
use Tuleap\AgileDashboard\Milestone\Backlog\IBacklogItem;
use Tuleap\Project\ProjectBackground\ProjectBackgroundConfiguration;
use Tuleap\Tracker\Permission\VerifySubmissionPermissions;
use UserManager;

final readonly class BacklogItemRepresentationFactory
{
    public function __construct(
        private ProjectBackgroundConfiguration $project_background_configuration,
        private VerifySubmissionPermissions $verify_tracker_submission_permissions,
    ) {
    }

    public function createBacklogItemRepresentation(IBacklogItem $backlog_item): BacklogItemRepresentation
    {
        $current_user = UserManager::instance()->getCurrentUser();
        return BacklogItemRepresentation::build(
            $backlog_item,
            $this->getBacklogItemCardFields($backlog_item, $current_user),
            $this->project_background_configuration,
            $this->verify_tracker_submission_permissions,
            $current_user,
        );
    }

    private function getBacklogItemCardFields(IBacklogItem $backlog_item, \PFUser $current_user): array
    {
        $card_fields_semantic = $this->getCardFieldsSemantic($backlog_item);
        $card_fields          = [];

        foreach ($card_fields_semantic->getFields() as $field) {
            if (! $field->userCanRead($current_user)) {
                continue;
            }

            $changeset = $backlog_item->getArtifact()->getLastChangeset();
            if (! $changeset) {
                continue;
            }

            $card_fields[] = $field->getFullRESTValue($current_user, $changeset);
        }

        return $card_fields;
    }

    private function getCardFieldsSemantic(IBacklogItem $backlog_item): Cardwall_Semantic_CardFields
    {
        return Cardwall_Semantic_CardFields::load($backlog_item->getArtifact()->getTracker());
    }
}
