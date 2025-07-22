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

namespace Tuleap\CrossTracker\Query\Advanced\ResultBuilder\Field\UserList;

use LogicException;
use Tracker_FormElement_Field_List_Bind;
use Tuleap\CrossTracker\Query\Advanced\DuckTypedField\Select\DuckTypedFieldSelect;
use Tuleap\CrossTracker\Query\Advanced\ResultBuilder\Representations\UserListRepresentation;
use Tuleap\CrossTracker\Query\Advanced\ResultBuilder\Representations\UserRepresentation;
use Tuleap\CrossTracker\Query\Advanced\ResultBuilder\SelectedValue;
use Tuleap\CrossTracker\Query\Advanced\ResultBuilder\SelectedValuesCollection;
use Tuleap\CrossTracker\Query\Advanced\SelectResultKey;
use Tuleap\CrossTracker\REST\v1\Representation\CrossTrackerSelectedRepresentation;
use Tuleap\CrossTracker\REST\v1\Representation\CrossTrackerSelectedType;
use Tuleap\Option\Option;
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
        $alias  = SelectResultKey::fromDuckTypedField($field)->__toString();


        foreach ($select_results as $result) {
            $id = $result['id'];
            if (! isset($values[$id])) {
                $values[$id] = [];
            }

            if ($result["user_list_value_$alias"] !== null) {
                if (is_array($result["user_list_value_$alias"])) {
                    foreach ($result["user_list_value_$alias"] as $user_id) {
                        if ($user_id === null) {
                            continue;
                        }
                        $this->buildUserValueFromList((int) $user_id)->apply(
                            static function (UserRepresentation $user) use (&$values, $id): void {
                                $values[$id][] = $user;
                            },
                        );
                    }
                } else {
                    $this->buildUserValueFromList((int) $result["user_list_value_$alias"])->apply(
                        static function (UserRepresentation $user) use (&$values, $id): void {
                            $values[$id][] = $user;
                        },
                    );
                }
            }

            if ($result["user_list_open_$alias"] !== null) {
                if (is_array($result["user_list_open_$alias"])) {
                    foreach ($result["user_list_open_$alias"] as $user_email) {
                        if ($user_email === null) {
                            continue;
                        }
                        $values[$id][] = $this->buildUserValueFromOpenList((string) $user_email);
                    }
                } else {
                    $values[$id][] = $this->buildUserValueFromOpenList((string) $result["user_list_open_$alias"]);
                }
            }
        }

        return new SelectedValuesCollection(
            new CrossTrackerSelectedRepresentation($field->name, CrossTrackerSelectedType::TYPE_USER_LIST),
            array_map(static fn(array $selected_values) => new SelectedValue($field->name, new UserListRepresentation($selected_values)), $values),
        );
    }

    /**
     * @return Option<UserRepresentation>
     */
    private function buildUserValueFromList(int $user_id): Option
    {
        if ($user_id === Tracker_FormElement_Field_List_Bind::NONE_VALUE) {
            return Option::nothing(UserRepresentation::class);
        }
        $user = $this->user_id_retriever->getUserById($user_id);
        if ($user === null) {
            throw new LogicException("User $user_id not found");
        }

        return Option::fromValue(UserRepresentation::fromPFUser($user, $this->user_helper));
    }

    private function buildUserValueFromOpenList(string $user_email): UserRepresentation
    {
        $user = $this->user_email_retriever->getUserByEmail($user_email);
        if ($user === null) {
            $user = $this->user_name_retriever->getUserByUserName($user_email);
        }
        if ($user === null) {
            return UserRepresentation::fromAnonymous($user_email);
        }
        return UserRepresentation::fromPFUser($user, $this->user_helper);
    }
}
