<?php
/**
 * Copyright (c) Enalean 2022 - Present. All Rights Reserved.
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

namespace Tuleap\Tracker\Semantic\Status;

class StatusColorForChangesetProvider
{
    public function __construct(private StatusValueForChangesetProvider $value_for_changeset_provider)
    {
    }

    public function provideColor(\Tracker_Artifact_Changeset $changeset, \Tuleap\Tracker\Tracker $tracker, \PFUser $user): ?string
    {
        $status_field = $tracker->getStatusField();
        if (! $status_field) {
            return null;
        }

        $value = $this->value_for_changeset_provider->getStatusValueForChangeset($changeset, $user);
        if (! $value) {
            return null;
        }

        $field_list_bind = $status_field->getBind();
        if (! $field_list_bind) {
            return null;
        }

        $decorators = $field_list_bind->getDecorators();
        if (! isset($decorators[$value->getId()])) {
            return null;
        }

        if ($decorators[$value->getId()]->tlp_color_name) {
            return $decorators[$value->getId()]->tlp_color_name;
        }

        return null;
    }
}
