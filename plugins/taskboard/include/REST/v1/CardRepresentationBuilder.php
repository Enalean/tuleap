<?php
/**
 * Copyright (c) Enalean, 2019 - Present. All Rights Reserved.
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

namespace Tuleap\Taskboard\REST\v1;

use Cardwall_Semantic_CardFields;
use PFUser;
use Tuleap\Cardwall\BackgroundColor\BackgroundColorBuilder;
use Tuleap\Option\Option;
use Tuleap\Taskboard\Column\FieldValuesToColumnMapping\ArtifactMappedFieldValueRetriever;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\User\Avatar\ProvideUserAvatarUrl;
use Tuleap\User\REST\MinimalUserRepresentation;

final readonly class CardRepresentationBuilder
{
    public function __construct(
        private BackgroundColorBuilder $background_color_builder,
        private ArtifactMappedFieldValueRetriever $mapped_field_value_retriever,
        private RemainingEffortRepresentationBuilder $remaining_effort_representation_builder,
        private ProvideUserAvatarUrl $provide_user_avatar_url,
    ) {
    }

    public function build(
        \Planning_ArtifactMilestone $milestone,
        Artifact $artifact,
        PFUser $user,
        int $rank,
    ): CardRepresentation {
        $card_fields_semantic = Cardwall_Semantic_CardFields::load($artifact->getTracker());
        $background_color     = $this->background_color_builder->build($card_fields_semantic, $artifact, $user);
        $assignees            = $this->getAssignees($artifact, $user);
        $mapped_list_value    = $this->getMappedListValue($milestone->getArtifact()->getTracker(), $artifact, $user);
        $initial_effort       = $this->getInitialEffort($artifact, $user);
        $remaining_effort     = $this->remaining_effort_representation_builder->getRemainingEffort($user, $artifact);


        return CardRepresentation::build(
            $artifact,
            $background_color,
            $rank,
            $assignees,
            $mapped_list_value,
            $initial_effort,
            $remaining_effort,
            $this->isCollapsed($user, $artifact, $milestone),
            $milestone,
        );
    }

    /** @return Option<MappedListValueRepresentation> */
    private function getMappedListValue(
        \Tuleap\Tracker\Tracker $milestone_tracker,
        Artifact $artifact,
        PFUser $user,
    ): Option {
        return $this->mapped_field_value_retriever->getFirstValueAtLastChangeset($milestone_tracker, $artifact, $user)
            ->map(MappedListValueRepresentation::build(...));
    }

    /**
     * @return MinimalUserRepresentation[]
     * @psalm-return list<MinimalUserRepresentation>
     */
    private function getAssignees(Artifact $artifact, PFUser $user): array
    {
        $assignees = $artifact->getAssignedTo($user);

        return array_values(
            array_map(
                function (PFUser $user): MinimalUserRepresentation {
                    return MinimalUserRepresentation::build($user, $this->provide_user_avatar_url);
                },
                $assignees
            ),
        );
    }

    private function getInitialEffort(Artifact $artifact, PFUser $user)
    {
        $initial_effort_field = \AgileDashBoard_Semantic_InitialEffort::load($artifact->getTracker())->getField();

        if ($initial_effort_field === null || ! $initial_effort_field->userCanRead($user)) {
            return null;
        }

        $last_changeset_value = $initial_effort_field->getLastChangesetValue($artifact);

        if (! $last_changeset_value) {
            return null;
        }

        if ($last_changeset_value instanceof \Tracker_Artifact_ChangesetValue_List) {
            return $this->getListFieldFirstValue($last_changeset_value);
        }

        return $last_changeset_value->getValue();
    }

    private function getListFieldFirstValue(\Tracker_Artifact_ChangesetValue_List $value_list)
    {
        $list_values = $value_list->getListValues();

        if (count($list_values) === 0) {
            return null;
        }

        return reset($list_values)->getLabel();
    }

    private function isCollapsed(PFUser $user, Artifact $artifact, \Planning_ArtifactMilestone $milestone): bool
    {
        $preference_name = 'plugin_taskboard_collapse_' . $milestone->getArtifactId() . '_' . $artifact->getId();

        return ! empty($user->getPreference($preference_name));
    }
}
