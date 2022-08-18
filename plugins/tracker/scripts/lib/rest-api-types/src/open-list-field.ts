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

import type {
    OpenListFieldIdentifier,
    Permission,
    StaticBindIdentifier,
    UserGroupsBindIdentifier,
    UsersBindIdentifier,
} from "@tuleap/plugin-tracker-constants";
import type { RegisteredUserRepresentation } from "./artifacts";
import type { BaseFieldStructure } from "./trackers";

type StaticOpenListItem = {
    readonly id: number;
    readonly label: string;
};

type UserGroupOpenListItem = {
    readonly id: string;
    readonly label: string;
};

type CommonOpenListFieldStructure = BaseFieldStructure & {
    readonly type: OpenListFieldIdentifier;
    readonly permissions: ReadonlyArray<Permission>;
};

export type StaticBoundOpenListField = CommonOpenListFieldStructure & {
    readonly bindings: {
        readonly type: StaticBindIdentifier;
    };
    readonly default_value: ReadonlyArray<StaticOpenListItem>;
};

export type UserBoundOpenListField = CommonOpenListFieldStructure & {
    readonly bindings: {
        readonly type: UsersBindIdentifier;
    };
    readonly default_value: ReadonlyArray<RegisteredUserRepresentation>;
};

export type UserGroupBoundOpenListField = CommonOpenListFieldStructure & {
    readonly bindings: {
        readonly type: UserGroupsBindIdentifier;
    };
    readonly default_value: ReadonlyArray<UserGroupOpenListItem>;
};

export type OpenListFieldStructure =
    | StaticBoundOpenListField
    | UserBoundOpenListField
    | UserGroupBoundOpenListField;
