<?php
/**
 * Copyright (c) Enalean, 2014 - Present. All Rights Reserved.
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

namespace Tuleap\AgileDashboard\REST\v1;

use Cardwall_Semantic_CardFields;
use PFUser;
use Tuleap\AgileDashboard\Milestone\Backlog\IBacklogItem;
use Tuleap\Cardwall\BackgroundColor\BackgroundColorBuilder;
use Tuleap\Project\ProjectBackground\ProjectBackgroundConfiguration;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\Permission\VerifySubmissionPermissions;
use UserManager;

final readonly class BacklogItemRepresentationFactory
{
    public function __construct(
        private BackgroundColorBuilder $background_color_builder,
        private UserManager $user_manager,
        private ProjectBackgroundConfiguration $project_background_configuration,
        private VerifySubmissionPermissions $verify_tracker_submission_permissions,
    ) {
    }

    public function createBacklogItemRepresentation(IBacklogItem $backlog_item): BacklogItemRepresentation
    {
        $artifact             = $backlog_item->getArtifact();
        $current_user         = $this->user_manager->getCurrentUser();
        $card_fields_semantic = $this->getCardFieldsSemantic($artifact);
        $card_fields          = $this->getCardFields($card_fields_semantic, $artifact, $current_user);
        $background_color     = $this->background_color_builder->build(
            $card_fields_semantic,
            $artifact,
            $current_user
        );

        return BacklogItemRepresentation::build(
            $backlog_item,
            $card_fields,
            $background_color,
            $this->project_background_configuration,
            $current_user,
            $this->verify_tracker_submission_permissions,
        );
    }

    private function getCardFields(
        Cardwall_Semantic_CardFields $card_fields_semantic,
        Artifact $artifact,
        PFUser $current_user,
    ): array {
        $card_fields = [];

        foreach ($card_fields_semantic->getFields() as $field) {
            if (! $field->userCanRead($current_user)) {
                continue;
            }

            $changeset = $artifact->getLastChangeset();
            if (! $changeset) {
                continue;
            }

            $value = $field->getFullRESTValue($current_user, $changeset);
            if ($value) {
                $card_fields[] = $value;
            }
        }

        return $card_fields;
    }

    private function getCardFieldsSemantic(Artifact $artifact): Cardwall_Semantic_CardFields
    {
        return Cardwall_Semantic_CardFields::load($artifact->getTracker());
    }
}
