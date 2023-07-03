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

use AgileDashboard_Milestone_Backlog_IBacklogItem;
use Cardwall_Semantic_CardFields;
use PFUser;
use Tuleap\Cardwall\BackgroundColor\BackgroundColorBuilder;
use Tuleap\Project\ProjectBackground\ProjectBackgroundConfiguration;
use Tuleap\Tracker\Artifact\Artifact;
use UserManager;

final class BacklogItemRepresentationFactory
{
    public function __construct(
        private readonly BackgroundColorBuilder $background_color_builder,
        private readonly UserManager $user_manager,
        private readonly ProjectBackgroundConfiguration $project_background_configuration,
    ) {
    }

    public function createBacklogItemRepresentation(AgileDashboard_Milestone_Backlog_IBacklogItem $backlog_item): BacklogItemRepresentation
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

        $backlog_item_representation = new BacklogItemRepresentation();
        $backlog_item_representation->build(
            $backlog_item,
            $card_fields,
            $background_color,
            $this->project_background_configuration
        );

        return $backlog_item_representation;
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
