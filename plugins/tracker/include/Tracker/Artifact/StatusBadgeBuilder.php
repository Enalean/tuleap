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

namespace Tuleap\Tracker\Artifact;

final class StatusBadgeBuilder
{
    public function __construct(private \Tuleap\Tracker\Semantic\Status\TrackerSemanticStatusFactory $status_factory)
    {
    }

    /**
     * @template T
     * @psalm-param Closure(string, ?string):T $build_badge_instance_callback
     * @return list<T>
     */
    public function buildBadgesFromArtifactStatus(
        Artifact $artifact,
        \PFUser $user,
        callable $build_badge_instance_callback,
    ): array {
        $semantic_status = $this->status_factory->getByTracker($artifact->getTracker());
        $status_field    = $semantic_status->getField();
        if (! $status_field) {
            return [];
        }

        if (! $status_field->userCanRead($user)) {
            return [];
        }

        if (! $semantic_status->isFieldBoundToStaticValues()) {
            return [];
        }

        $changeset_value = $artifact->getValue($status_field);
        if (! $changeset_value instanceof \Tracker_Artifact_ChangesetValue_List) {
            return [];
        }

        $decorators = $status_field->getDecorators();

        return array_values(
            array_map(
                fn(\Tracker_FormElement_Field_List_BindValue $value) => $build_badge_instance_callback(
                    $value->getLabel(),
                    $this->getBadgeColor($value, $decorators)
                ),
                $changeset_value->getListValues(),
            )
        );
    }

    /**
     * @param \Tracker_FormElement_Field_List_BindDecorator[] $decorators
     */
    private function getBadgeColor(\Tracker_FormElement_Field_List_BindValue $value, array $decorators): ?string
    {
        if (empty($decorators)) {
            return null;
        }

        if (! isset($decorators[$value->getId()])) {
            return null;
        }

        $decorator = $decorators[$value->getId()];
        if ($decorator->isUsingOldPalette()) {
            return null;
        }

        return $decorator->tlp_color_name;
    }
}
