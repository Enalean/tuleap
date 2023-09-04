/*
 * Copyright (c) Enalean, 2022 - present. All Rights Reserved.
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

import type { SelectBoxFieldValueModelType } from "./SelectBoxFieldValueModelType";
import { SelectBoxFieldPresenter } from "./SelectBoxFieldPresenter";
import type { EventDispatcher } from "../../../../domain/EventDispatcher";
import { DidChangeListFieldValue } from "../../../../domain/fields/select-box-field/DidChangeListFieldValue";
import type { ListFieldStructure } from "@tuleap/plugin-tracker-rest-api-types";
import type { ListPicker } from "@tuleap/list-picker";
import { createListPicker } from "@tuleap/list-picker";
import type { BindValueId } from "../../../../domain/fields/select-box-field/BindValueId";

export interface ControlSelectBoxField {
    getInitialBindValueIds(): BindValueId[];
    buildPresenter(): SelectBoxFieldPresenter;
    setSelectedValue(bind_value_ids: ReadonlyArray<BindValueId>): void;
    initListPicker(select: HTMLSelectElement): void;
    onDependencyChange(
        callback: (
            bind_value_ids: ReadonlyArray<BindValueId>,
            presenter: SelectBoxFieldPresenter,
        ) => void,
    ): void;
    destroy(): void;
}

export const SelectBoxFieldController = (
    event_dispatcher: EventDispatcher,
    field: ListFieldStructure,
    value_model: SelectBoxFieldValueModelType,
    is_field_disabled: boolean,
    user_locale: string,
): ControlSelectBoxField => {
    let list_picker_instance: ListPicker;

    return {
        getInitialBindValueIds(): BindValueId[] {
            return value_model.bind_value_ids;
        },
        buildPresenter(): SelectBoxFieldPresenter {
            return SelectBoxFieldPresenter.fromField(
                field,
                field.values.map((value) => value.id),
                is_field_disabled,
            );
        },
        setSelectedValue(bind_value_ids: ReadonlyArray<BindValueId>): void {
            value_model.bind_value_ids = [...bind_value_ids];

            event_dispatcher.dispatch(
                DidChangeListFieldValue(field.field_id, value_model.bind_value_ids),
            );
        },
        onDependencyChange(callback): void {
            event_dispatcher.addObserver("DidChangeAllowedValues", (event) => {
                if (event.field_id !== field.field_id) {
                    return;
                }

                value_model.bind_value_ids = value_model.bind_value_ids.filter((bind_value_id) =>
                    event.allowed_bind_value_ids.includes(bind_value_id),
                );
                if (
                    value_model.bind_value_ids.length === 0 &&
                    event.allowed_bind_value_ids.length > 0
                ) {
                    value_model.bind_value_ids = [event.allowed_bind_value_ids[0]];
                }

                callback(
                    value_model.bind_value_ids,
                    SelectBoxFieldPresenter.fromField(
                        field,
                        event.allowed_bind_value_ids,
                        is_field_disabled,
                    ),
                );

                event_dispatcher.dispatch(
                    DidChangeListFieldValue(field.field_id, value_model.bind_value_ids),
                );
            });
        },
        initListPicker(select: HTMLSelectElement): void {
            const additional_options = {};
            if (field.type === "msb") {
                Object.assign(additional_options, {
                    none_value: "100",
                });
            }

            list_picker_instance = createListPicker(select, {
                locale: user_locale,
                is_filterable: true,
                ...additional_options,
            });
        },
        destroy(): void {
            list_picker_instance.destroy();
        },
    };
};
