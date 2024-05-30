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
import type { StaticValueModelItem } from "../../../../../domain/fields/static-open-list-field/StaticOpenListValueModel";
import type { StaticOpenListFieldType } from "../../../../../domain/fields/static-open-list-field/StaticOpenListFieldType";
import type { InternalStaticOpenListField } from "./StaticOpenListField";
import { StaticOpenListFieldPresenterBuilder } from "./StaticOpenListFieldPresenter";

export type ControlStaticOpenListField = {
    init(host: InternalStaticOpenListField): void;
    handleStaticValueSelection(
        host: InternalStaticOpenListField,
        event: Select2SelectionEvent,
    ): void;
    handleStaticValueUnselection(
        host: InternalStaticOpenListField,
        event: Select2SelectionEvent,
    ): void;
    newOpenListStaticValue(
        field: StaticOpenListFieldType,
        new_open_value: { term: string },
    ): NewSelect2StaticValue | null;
};

type Select2StaticValue = {
    id: string;
    text: string;
};

type NewSelect2StaticValue = Select2StaticValue & {
    isTag: true;
};

export type Select2SelectionEvent = {
    params: {
        args: {
            data: Select2StaticValue;
        };
    };
};

export const StaticOpenListFieldController = (
    field: StaticOpenListFieldType,
    bind_value_objects: StaticValueModelItem[],
): ControlStaticOpenListField => {
    let merged_values: Array<StaticValueModelItem> = [];

    const getFieldValues = (): Array<StaticValueModelItem> => {
        if (merged_values.length === 0) {
            const union_values = [...field.values, ...bind_value_objects];
            merged_values = [
                ...new Map(
                    union_values.map((union_value) => {
                        return [
                            String(union_value.id),
                            { ...union_value, id: String(union_value.id) },
                        ];
                    }),
                ).values(),
            ];
        }
        return merged_values;
    };

    const handleStaticValueSelection = (
        host: InternalStaticOpenListField,
        event: Select2SelectionEvent,
    ): void => {
        const new_selection = event.params.args.data;

        bind_value_objects.push({
            id: new_selection.id,
            label: new_selection.text.replace("\n", "").trim(),
        });

        host.presenter = StaticOpenListFieldPresenterBuilder.withSelectableValues(
            field,
            bind_value_objects,
            getFieldValues(),
        );
    };

    const handleStaticValueUnselection = (
        host: InternalStaticOpenListField,
        event: Select2SelectionEvent,
    ): void => {
        const removed_selection = event.params.args.data;
        const index = bind_value_objects.findIndex(
            (value_object) => value_object.id === removed_selection.id,
        );

        bind_value_objects.splice(index, 1);

        host.presenter = StaticOpenListFieldPresenterBuilder.withSelectableValues(
            field,
            bind_value_objects,
            getFieldValues(),
        );
    };

    const newOpenListStaticValue = (
        field: StaticOpenListFieldType,
        new_open_value: { term: string },
    ): NewSelect2StaticValue | null => {
        const term = new_open_value.term.trim();
        if (term === "") {
            return null;
        }

        const tag_already_exists = field.values.some(
            (value: StaticValueModelItem): boolean =>
                value.label.toLowerCase() === term.toLowerCase(),
        );

        if (tag_already_exists) {
            return null;
        }

        return {
            id: term,
            text: term,
            isTag: true,
        };
    };

    return {
        init(host: InternalStaticOpenListField): void {
            const presenter = StaticOpenListFieldPresenterBuilder.withSelectableValues(
                field,
                bind_value_objects,
                getFieldValues(),
            );

            host.presenter = presenter;

            const select2_instance = select2(host.select_element, {
                placeholder: presenter.hint,
                allowClear: true,
                tags: true,
                createTag: (new_open_value) => newOpenListStaticValue(field, new_open_value),
            });

            // eslint-disable-next-line @typescript-eslint/ban-ts-comment
            // @ts-ignore
            select2_instance.on("select2:selecting", (event: Select2SelectionEvent) =>
                handleStaticValueSelection(host, event),
            );

            // eslint-disable-next-line @typescript-eslint/ban-ts-comment
            // @ts-ignore
            select2_instance.on("select2:unselecting", (event: Select2SelectionEvent) =>
                handleStaticValueUnselection(host, event),
            );
        },
        handleStaticValueSelection,
        handleStaticValueUnselection,
        newOpenListStaticValue,
    };
};
