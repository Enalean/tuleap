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

import type { EventDispatcher } from "../../EventDispatcher";
import { DidChangeAllowedValues } from "./DidChangeAllowedValues";
import type { BindValueId } from "./BindValueId";

export type FieldDependenciesRule = {
    readonly source_field_id: number;
    readonly source_value_id: BindValueId;
    readonly target_field_id: number;
    readonly target_value_id: BindValueId;
};

export const FieldDependenciesValuesHelper = (
    event_dispatcher: EventDispatcher,
    field_dependencies_rules: readonly FieldDependenciesRule[],
): void => {
    event_dispatcher.addObserver("DidChangeListFieldValue", (event) => {
        const possible_values_map = field_dependencies_rules
            .filter((rule) => {
                return (
                    rule.source_field_id === event.field_id &&
                    event.bind_value_ids.includes(rule.source_value_id)
                );
            })
            .reduce((accumulator, rule) => {
                const target_field_possible_values = accumulator.get(rule.target_field_id);

                if (!target_field_possible_values) {
                    accumulator.set(rule.target_field_id, [rule.target_value_id]);
                } else {
                    target_field_possible_values.push(rule.target_value_id);
                }

                return accumulator;
            }, new Map<number, Array<number | string>>());

        for (const [target_field_id, target_values_ids] of possible_values_map.entries()) {
            event_dispatcher.dispatch(DidChangeAllowedValues(target_field_id, target_values_ids));
        }
    });
};
