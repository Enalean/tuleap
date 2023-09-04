/*
 * Copyright (c) Enalean, 2018-Present. All Rights Reserved.
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
    StaticBindIdentifier,
    UserGroupsBindIdentifier,
    UsersBindIdentifier,
} from "@tuleap/plugin-tracker-constants";
import { LIST_BIND_STATIC, LIST_BIND_UGROUPS } from "@tuleap/plugin-tracker-constants";
import type {
    OpenListChangesetValue,
    OpenListFieldStructure,
    OpenListValueRepresentation,
    StaticBoundOpenListField,
    StaticValueRepresentation,
    UserGroupBoundOpenListField,
    UserGroupRepresentation,
    UserWithEmailAndStatus,
} from "@tuleap/plugin-tracker-rest-api-types";
import type {
    UserOpenListValueModel,
    UserValueModelItem,
} from "../../../../domain/fields/user-open-list-field/UserOpenListValueModel";
import type {
    StaticOpenListValueModel,
    StaticValueModelItem,
} from "../../../../domain/fields/static-open-list-field/StaticOpenListValueModel";
import type {
    UserGroupOpenListValueModel,
    UserGroupValueModelItem,
} from "../../../../domain/fields/user-group-open-list-field/UserGroupOpenListValueModel";

type FieldDataValueModel = Pick<OpenListFieldStructure, "field_id" | "type" | "permissions">;

type StaticOpenListValueModelWithFieldData = FieldDataValueModel &
    StaticOpenListValueModel & {
        readonly bindings: { readonly type: StaticBindIdentifier };
    };

type UserOpenListValueModelWithFieldData = FieldDataValueModel &
    UserOpenListValueModel & {
        readonly bindings: { readonly type: UsersBindIdentifier };
    };

type UserGroupOpenListValueModelWithFieldData = FieldDataValueModel &
    UserGroupOpenListValueModel & {
        readonly bindings: { readonly type: UserGroupsBindIdentifier };
    };

type OpenListValueModel =
    | StaticOpenListValueModelWithFieldData
    | UserOpenListValueModelWithFieldData
    | UserGroupOpenListValueModelWithFieldData;

const isStaticBound = (field: OpenListFieldStructure): field is StaticBoundOpenListField =>
    field.bindings.type === LIST_BIND_STATIC;

const isUserGroupBound = (field: OpenListFieldStructure): field is UserGroupBoundOpenListField =>
    field.bindings.type === LIST_BIND_UGROUPS;

export function formatDefaultValue(field: OpenListFieldStructure): OpenListValueModel {
    const { field_id, type, permissions } = field;
    if (isStaticBound(field)) {
        return {
            field_id,
            type,
            permissions,
            bindings: field.bindings,
            value: {
                bind_value_objects: field.default_value.map((value) => ({
                    label: value.label,
                    id: String(value.id),
                })),
            },
        };
    }
    if (isUserGroupBound(field)) {
        return {
            field_id,
            type,
            permissions,
            bindings: field.bindings,
            value: { bind_value_objects: [...field.default_value] },
        };
    }
    return {
        field_id,
        type,
        permissions,
        bindings: field.bindings,
        value: { bind_value_objects: [...field.default_value] },
    };
}

type OpenListBindValueObject =
    | UserWithEmailAndStatus
    | OpenListValueRepresentation
    | StaticValueRepresentation
    | UserGroupRepresentation;

const isUserValue = (value: OpenListBindValueObject): value is UserWithEmailAndStatus =>
    "email" in value;

const mapToUserValueModel = (
    bind_value_objects: readonly OpenListBindValueObject[],
): UserValueModelItem[] =>
    bind_value_objects.filter(isUserValue).map((user_value) => ({
        ...user_value,
        id: user_value.id ?? 0,
    }));

const isStaticValue = (
    value: OpenListBindValueObject,
): value is OpenListValueRepresentation | StaticValueRepresentation => !isUserValue(value);

const mapToStaticValueModel = (
    bind_value_objects: readonly OpenListBindValueObject[],
): StaticValueModelItem[] => bind_value_objects.filter(isStaticValue);

const isUserGroupValue = (value: OpenListBindValueObject): value is UserGroupRepresentation =>
    !isUserValue(value);

const mapToUserGroupValueModel = (
    bind_value_objects: readonly OpenListBindValueObject[],
): UserGroupValueModelItem[] => bind_value_objects.filter(isUserGroupValue);

export function formatExistingValue(
    field: OpenListFieldStructure,
    artifact_value: OpenListChangesetValue,
): OpenListValueModel {
    const { field_id, type, permissions } = field;

    if (isStaticBound(field)) {
        return {
            field_id,
            type,
            permissions,
            bindings: field.bindings,
            value: { bind_value_objects: mapToStaticValueModel(artifact_value.bind_value_objects) },
        };
    }
    if (isUserGroupBound(field)) {
        return {
            field_id,
            type,
            permissions,
            bindings: field.bindings,
            value: {
                bind_value_objects: mapToUserGroupValueModel(artifact_value.bind_value_objects),
            },
        };
    }

    return {
        field_id,
        type,
        permissions,
        bindings: field.bindings,
        value: { bind_value_objects: mapToUserValueModel(artifact_value.bind_value_objects) },
    };
}
