/*
 * Copyright (c) Enalean, 2024 - present. All Rights Reserved.
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

import { select2 } from "tlp";
import type { UserGroupValueModelItem } from "../../../../../domain/fields/user-group-open-list-field/UserGroupOpenListValueModel";
import type { UserGroupOpenListFieldType } from "../../../../../domain/fields/user-group-open-list-field/UserGroupOpenListFieldType";
import type { InternalUserGroupOpenListField } from "./UserGroupOpenListField";
import { UserGroupOpenListFieldPresenter } from "./UserGroupOpenListFieldPresenter";
import type { Select2SelectionEvent } from "../Select2SelectionEvent";

export type ControlUserGroupOpenListField = {
    init(host: InternalUserGroupOpenListField): void;
    handleValueUnselection(
        host: InternalUserGroupOpenListField,
        event: Select2SelectionEvent,
    ): void;
    handleValueSelection(host: InternalUserGroupOpenListField, event: Select2SelectionEvent): void;
};

export const UserGroupOpenListFieldController = (
    field: UserGroupOpenListFieldType,
    bind_value_objects: UserGroupValueModelItem[],
): ControlUserGroupOpenListField => {
    function handleValueUnselection(
        host: InternalUserGroupOpenListField,
        event: Select2SelectionEvent,
    ): void {
        const removed_selection = event.params.args.data;
        const index = bind_value_objects.findIndex(
            (value_object) => value_object.id === removed_selection.id,
        );

        bind_value_objects.splice(index, 1);

        host.presenter = UserGroupOpenListFieldPresenter.withSelectableValues(
            field,
            bind_value_objects,
            field.values.map((value) => value),
        );
    }

    function handleValueSelection(
        host: InternalUserGroupOpenListField,
        event: Select2SelectionEvent,
    ): void {
        const new_selection = event.params.args.data;
        bind_value_objects.push({
            id: new_selection.id,
            label: new_selection.text.replace("\n", "").trim(),
        });

        host.presenter = UserGroupOpenListFieldPresenter.withSelectableValues(
            field,
            bind_value_objects,
            field.values.map((value) => value),
        );
    }

    return {
        init(host): void {
            const presenter = UserGroupOpenListFieldPresenter.withSelectableValues(
                field,
                bind_value_objects,
                field.values.map((value) => value),
            );

            host.presenter = presenter;

            const select2_instance = select2(host.select_element, {
                placeholder: host.presenter.hint,
                allowClear: true,
            });

            // eslint-disable-next-line @typescript-eslint/ban-ts-comment
            // @ts-ignore
            select2_instance.on("select2:selecting", (event: Select2SelectionEvent) => {
                handleValueSelection(host, event);
            });

            // eslint-disable-next-line @typescript-eslint/ban-ts-comment
            // @ts-ignore
            select2_instance.on("select2:unselecting", (event: Select2SelectionEvent) =>
                handleValueUnselection(host, event),
            );
        },
        handleValueSelection,
        handleValueUnselection,
    };
};
