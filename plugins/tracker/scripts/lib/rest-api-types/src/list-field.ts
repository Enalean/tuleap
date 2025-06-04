/*
 * Copyright (c) Enalean, 2022-Present. All Rights Reserved.
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

import type { RegisteredUserWithAvatar } from "./users";
import type { BaseFieldStructure } from "./trackers";
import type { UserGroupRepresentation } from "./artifacts";
import type {
    CheckBoxFieldIdentifier,
    ColorName,
    MultiSelectBoxFieldIdentifier,
    RadioButtonFieldIdentifier,
    SelectBoxFieldIdentifier,
    StaticBindIdentifier,
    UserGroupsBindIdentifier,
    UsersBindIdentifier,
} from "@tuleap/plugin-tracker-constants";

export type StaticListItem = {
    readonly id: number;
    readonly label: string;
    readonly value_color: ColorName | "";
};

export type UserBoundListItem = {
    readonly id: number;
    readonly label: string;
    readonly user_reference: RegisteredUserWithAvatar;
};

export type UserGroupBoundListItem = {
    /**
     * Dynamic user groups have ids like "101_3", where 101 is the
     * project ID and 3 is a constant.
     */
    readonly id: string;
    readonly label: string;
    readonly ugroup_reference: UserGroupRepresentation;
};

type CommonListFieldStructure = BaseFieldStructure & {
    readonly type:
        | SelectBoxFieldIdentifier
        | MultiSelectBoxFieldIdentifier
        | RadioButtonFieldIdentifier
        | CheckBoxFieldIdentifier;
    readonly label: string;
    readonly required: boolean;
};

export type StaticBoundListField = CommonListFieldStructure & {
    readonly bindings: {
        readonly type: StaticBindIdentifier;
    };
    readonly default_value: ReadonlyArray<StaticListItem>;
    readonly values: ReadonlyArray<StaticListItem>;
};

export type UserBoundListField = CommonListFieldStructure & {
    readonly bindings: {
        readonly type: UsersBindIdentifier;
    };
    readonly default_value: ReadonlyArray<UserBoundListItem>;
    readonly values: ReadonlyArray<UserBoundListItem>;
};

export type UserGroupBoundListField = CommonListFieldStructure & {
    readonly bindings: {
        readonly type: UserGroupsBindIdentifier;
    };
    readonly default_value: ReadonlyArray<UserGroupBoundListItem>;
    readonly values: ReadonlyArray<UserGroupBoundListItem>;
};

export type ListFieldItem = StaticListItem | UserBoundListItem | UserGroupBoundListItem;

export type ListFieldStructure =
    | StaticBoundListField
    | UserBoundListField
    | UserGroupBoundListField;
