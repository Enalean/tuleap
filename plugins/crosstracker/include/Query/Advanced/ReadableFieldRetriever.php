<?php
/**
 * Copyright (c) Enalean, 2024-present. All Rights Reserved.
 *
 *  This file is a part of Tuleap.
 *
 *  Tuleap is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  Tuleap is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace Tuleap\CrossTracker\Query\Advanced;

use PFUser;
use Tuleap\Tracker\FormElement\Field\RetrieveUsedFields;
use Tuleap\Tracker\FormElement\Field\TrackerField;
use Tuleap\Tracker\Permission\FieldPermissionType;
use Tuleap\Tracker\Permission\RetrieveUserPermissionOnFields;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\Field;

final readonly class ReadableFieldRetriever
{
    public function __construct(
        private RetrieveUsedFields $retrieve_used_fields,
        private RetrieveUserPermissionOnFields $permission_on_fields,
    ) {
    }

    /**
     * @param int[] $tracker_ids
     * @return TrackerField[]
     */
    public function retrieveFieldsUserCanRead(Field $field, PFUser $user, array $tracker_ids): array
    {
        $fields = array_filter(
            array_map(
                fn(int $tracker_id) => $this->retrieve_used_fields->getUsedFieldByName($tracker_id, $field->getName()),
                $tracker_ids,
            ),
            static fn(?TrackerField $field) => $field !== null,
        );

        return $this->permission_on_fields
            ->retrieveUserPermissionOnFields($user, $fields, FieldPermissionType::PERMISSION_READ)
            ->allowed;
    }
}
