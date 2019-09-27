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
use Tracker_Artifact;
use Tracker_FormElement_Field_List_BindValue;
use Tuleap\Cardwall\BackgroundColor\BackgroundColorBuilder;
use Tuleap\Tracker\REST\Artifact\ArtifactFieldValueListFullRepresentation;
use Tuleap\Tracker\Semantic\Status\StatusValueProvider;
use Tuleap\User\REST\UserRepresentation;

class CardRepresentationBuilder
{
    /**
     * @var BackgroundColorBuilder
     */
    private $background_color_builder;
    /**
     * @var StatusValueProvider
     */
    private $status_value_provider;

    public function __construct(
        BackgroundColorBuilder $background_color_builder,
        StatusValueProvider $status_value_provider
    ) {
        $this->background_color_builder = $background_color_builder;
        $this->status_value_provider    = $status_value_provider;
    }

    public function build(Tracker_Artifact $artifact, PFUser $user, int $rank): CardRepresentation
    {
        $card_fields_semantic = Cardwall_Semantic_CardFields::load($artifact->getTracker());
        $background_color     = $this->background_color_builder->build($card_fields_semantic, $artifact, $user);
        $assignees            = $this->getAssignees($artifact, $user);
        $status               = $this->getStatus($artifact, $user);
        $initial_effort       = $this->getInitialEffort($artifact, $user);

        $representation = new CardRepresentation();
        $representation->build($artifact, $background_color, $rank, $assignees, $status, $initial_effort);

        return $representation;
    }

    private function getStatus(Tracker_Artifact $artifact, PFUser $user): ?StatusRepresentation
    {
        $status_value = $this->status_value_provider->getStatusValue($artifact, $user);
        if (! $status_value instanceof Tracker_FormElement_Field_List_BindValue) {
            return null;
        }

        $representation = new StatusRepresentation();
        $representation->build($status_value);

        return $representation;
    }

    /**
     * @return UserRepresentation[]
     */
    private function getAssignees(Tracker_Artifact $artifact, PFUser $user): array
    {
        $assignees = $artifact->getAssignedTo($user);

        return array_map(
            function (PFUser $user): UserRepresentation {
                return (new UserRepresentation())->build($user);
            },
            $assignees
        );
    }

    private function getInitialEffort(Tracker_Artifact $artifact, PFUser $user)
    {
        $initial_effort_field = \AgileDashBoard_Semantic_InitialEffort::load($artifact->getTracker())->getField();

        if (! $initial_effort_field) {
            return null;
        }

        $last_changeset_value = $initial_effort_field->getLastChangesetValue($artifact);

        if (! $last_changeset_value) {
            return null;
        }

        if ($last_changeset_value instanceof \Tracker_Artifact_ChangesetValue_List) {
            return $this->getListFieldFirstValue($user, $last_changeset_value);
        }

        return $last_changeset_value->getValue();
    }

    private function getListFieldFirstValue(PFUser $user, \Tracker_Artifact_ChangesetValue_List $value_list)
    {
        $list_values = $value_list->getListValues();

        if (count($list_values) === 0) {
            return null;
        }

        return reset($list_values)->getLabel();
    }
}
