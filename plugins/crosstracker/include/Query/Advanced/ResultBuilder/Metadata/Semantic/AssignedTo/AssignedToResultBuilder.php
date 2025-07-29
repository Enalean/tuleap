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
use Tuleap\Option\Option;
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

            $value    = $result[$alias];
            $user_ids = is_array($value) ? $value : [$value];

            foreach ($user_ids as $user_id) {
                if (! is_int($user_id) || $user_id === Tracker_FormElement_Field_List_Bind::NONE_VALUE) {
                    continue;
                }

                $this->buildUserValueFromAssignedTo($user_id)->apply(
                    static function (UserRepresentation $user) use (&$values, $id): void {
                        $values[$id][] = $user;
                    },
                );
            }
        }

        return new SelectedValuesCollection(
            new CrossTrackerSelectedRepresentation('@assigned_to', CrossTrackerSelectedType::TYPE_USER_LIST),
            array_map(static fn(array $selected_values) => new SelectedValue('@assigned_to', new UserListRepresentation($selected_values)), $values),
        );
    }

    /**
     * @return Option<UserRepresentation>
     */
    private function buildUserValueFromAssignedTo(int $user_id): Option
    {
        if ($user_id === Tracker_FormElement_Field_List_Bind::NONE_VALUE) {
            return Option::nothing(UserRepresentation::class);
        }
        $user = $this->user_retriever->getUserById($user_id);
        if ($user === null) {
            throw new LogicException("User $user_id not found");
        }

        return Option::fromValue(UserRepresentation::fromPFUser($user, $this->user_helper));
    }
}
