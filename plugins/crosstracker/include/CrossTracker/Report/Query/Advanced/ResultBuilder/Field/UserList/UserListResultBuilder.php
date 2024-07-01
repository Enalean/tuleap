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

namespace Tuleap\CrossTracker\Report\Query\Advanced\ResultBuilder\Field\UserList;

use LogicException;
use Tuleap\CrossTracker\Report\Query\Advanced\DuckTypedField\Select\DuckTypedFieldSelect;
use Tuleap\CrossTracker\Report\Query\Advanced\ResultBuilder\SelectedValue;
use Tuleap\CrossTracker\Report\Query\Advanced\ResultBuilder\SelectedValuesCollection;
use Tuleap\CrossTracker\Report\Query\Advanced\SelectResultKey;
use Tuleap\CrossTracker\REST\v1\Representation\CrossTrackerSelectedRepresentation;
use Tuleap\CrossTracker\REST\v1\Representation\CrossTrackerSelectedType;
use Tuleap\User\RetrieveUserByEmail;
use Tuleap\User\RetrieveUserById;
use Tuleap\User\RetrieveUserByUserName;
use UserHelper;

final readonly class UserListResultBuilder
{
    public function __construct(
        private RetrieveUserById $user_id_retriever,
        private RetrieveUserByEmail $user_email_retriever,
        private RetrieveUserByUserName $user_name_retriever,
        private UserHelper $user_helper,
    ) {
    }

    public function getResult(DuckTypedFieldSelect $field, array $select_results): SelectedValuesCollection
    {
        $values = [];
        $alias  = SelectResultKey::fromDuckTypedField($field);

        foreach ($select_results as $result) {
            $id = $result['id'];
            if (! isset($values[$id])) {
                $values[$id] = [];
            }

            if ($result["user_list_value_$alias"] !== null) {
                $user = $this->user_id_retriever->getUserById((int) $result["user_list_value_$alias"]);
                if ($user === null) {
                    throw new LogicException("User {$result["user_list_value_$alias"]} not found");
                }
                $values[$id][] = UserListValueRepresentation::fromPFUser($user, $this->user_helper);
            } elseif ($result["user_list_open_$alias"] !== null) {
                $user = $this->user_email_retriever->getUserByEmail((string) $result["user_list_open_$alias"]);
                if ($user === null) {
                    $user = $this->user_name_retriever->getUserByUserName((string) $result["user_list_open_$alias"]);
                }
                if ($user === null) {
                    $values[$id][] = UserListValueRepresentation::fromAnonymous((string) $result["user_list_open_$alias"]);
                } else {
                    $values[$id][] = UserListValueRepresentation::fromPFUser($user, $this->user_helper);
                }
            }
        }

        return new SelectedValuesCollection(
            new CrossTrackerSelectedRepresentation($field->name, CrossTrackerSelectedType::TYPE_USER_LIST),
            array_map(static fn(array $selected_values) => new SelectedValue($field->name, new UserListRepresentation($selected_values)), $values),
        );
    }
}
