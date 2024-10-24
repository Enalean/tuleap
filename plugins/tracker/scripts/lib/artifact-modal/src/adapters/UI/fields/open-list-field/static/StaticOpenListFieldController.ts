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
import type { StaticOpenListFieldPresenter } from "./StaticOpenListFieldPresenter";
import { StaticOpenListFieldPresenterBuilder } from "./StaticOpenListFieldPresenter";
import type { Select2SelectionEvent, Select2Value } from "../Select2SelectionEvent";

export type ControlStaticOpenListField = {
    initSelect2(host: InternalStaticOpenListField): void;
    getInitialPresenter(): StaticOpenListFieldPresenter;
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

type NewSelect2StaticValue = Select2Value & {
    isTag: true;
};

export const StaticOpenListFieldController = (
    field: StaticOpenListFieldType,
    bind_value_objects: StaticValueModelItem[],
): ControlStaticOpenListField => {
    let merged_values: Array<StaticValueModelItem> = [];

    const getFieldValues = (): Array<StaticValueModelItem> => {
        if (merged_values.length === 0) {
            const visible_values = field.values.filter((value) => !value.is_hidden);
            const union_values = [...visible_values, ...bind_value_objects];
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
            is_hidden: false,
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
        initSelect2(host: InternalStaticOpenListField): void {
            const select2_instance = select2(host.select_element, {
                placeholder: host.presenter.hint,
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
        getInitialPresenter: () =>
            StaticOpenListFieldPresenterBuilder.withSelectableValues(
                field,
                bind_value_objects,
                getFieldValues(),
            ),
        handleStaticValueSelection,
        handleStaticValueUnselection,
        newOpenListStaticValue,
    };
};
