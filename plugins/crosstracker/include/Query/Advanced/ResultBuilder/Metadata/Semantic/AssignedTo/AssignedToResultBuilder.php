<?php
/**
 * Copyright (c) Enalean, 2024-Present. All Rights Reserved.
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

namespace Tuleap\CrossTracker\Query\Advanced\ResultBuilder\Metadata\Semantic\AssignedTo;

use LogicException;
use Tracker_FormElement_Field_List_Bind;
use Tuleap\CrossTracker\Query\Advanced\ResultBuilder\Representations\UserListRepresentation;
use Tuleap\CrossTracker\Query\Advanced\ResultBuilder\Representations\UserRepresentation;
use Tuleap\CrossTracker\Query\Advanced\ResultBuilder\SelectedValue;
use Tuleap\CrossTracker\Query\Advanced\ResultBuilder\SelectedValuesCollection;
use Tuleap\CrossTracker\REST\v1\Representation\CrossTrackerSelectedRepresentation;
use Tuleap\CrossTracker\REST\v1\Representation\CrossTrackerSelectedType;
use Tuleap\User\RetrieveUserById;
use UserHelper;

final readonly class AssignedToResultBuilder
{
    public function __construct(
        private RetrieveUserById $user_retriever,
        private UserHelper $user_helper,
    ) {
    }

    public function getResult(array $select_results): SelectedValuesCollection
    {
        $values = [];
        $alias  = '@assigned_to';

        foreach ($select_results as $result) {
            $id = $result['id'];
            if (! isset($values[$id])) {
                $values[$id] = [];
            }

            $value = $result[$alias];
            if (! is_int($value) || $value === Tracker_FormElement_Field_List_Bind::NONE_VALUE) {
                continue;
            }
            $user = $this->user_retriever->getUserById($value);
            if ($user === null) {
                throw new LogicException("User $value not found");
            }
            $values[$id][] = UserRepresentation::fromPFUser($user, $this->user_helper);
        }

        return new SelectedValuesCollection(
            new CrossTrackerSelectedRepresentation('@assigned_to', CrossTrackerSelectedType::TYPE_USER_LIST),
            array_map(static fn(array $selected_values) => new SelectedValue('@assigned_to', new UserListRepresentation($selected_values)), $values),
        );
    }
}
