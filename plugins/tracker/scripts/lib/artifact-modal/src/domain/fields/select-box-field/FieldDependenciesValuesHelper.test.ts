/*
 * Copyright (c) Enalean, 2023 - present. All Rights Reserved.
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

import { FieldDependenciesValuesHelper } from "./FieldDependenciesValuesHelper";
import type { EventDispatcherType } from "../../AllEvents";
import { EventDispatcher } from "../../AllEvents";
import { DidChangeListFieldValue } from "./DidChangeListFieldValue";
import type { DidChangeAllowedValues } from "./DidChangeAllowedValues";
import type { FieldDependenciesRule } from "../../initialization/CurrentArtifactWithTrackerStructure";

const source_field_id = 1050;
const target_1_field_id = 1051;
const target_2_field_id = 1052;

const field_dependencies_rules: readonly FieldDependenciesRule[] = [
    {
        source_field_id,
        source_value_id: 1,
        target_field_id: target_1_field_id,
        target_value_id: 10511,
    },
    {
        source_field_id,
        source_value_id: 2,
        target_field_id: target_1_field_id,
        target_value_id: 10512,
    },
    {
        source_field_id,
        source_value_id: 2,
        target_field_id: target_2_field_id,
        target_value_id: 10521,
    },
];

describe("FieldDependenciesValuesHelper", () => {
    let event_dispatcher: EventDispatcherType,
        did_change_allowed_values_events: DidChangeAllowedValues[];

    beforeEach(() => {
        did_change_allowed_values_events = [];
        event_dispatcher = EventDispatcher();
        event_dispatcher.addObserver("DidChangeAllowedValues", (event) => {
            did_change_allowed_values_events.push(event);
        });
    });

    it(`Given that a field has changed its value,
        When this field has no target fields,
        Then no event should be fired`, () => {
        const value_changed_event = DidChangeListFieldValue(789, [1, 2]);

        FieldDependenciesValuesHelper(event_dispatcher, field_dependencies_rules);

        event_dispatcher.dispatch(value_changed_event);

        expect(did_change_allowed_values_events).toHaveLength(0);
    });

    it(`Given that a field has changed its value,
        When this field has target fields,
        Then an event per target field should be dispatched`, () => {
        const value_changed_event = DidChangeListFieldValue(source_field_id, [1, 2]);

        FieldDependenciesValuesHelper(event_dispatcher, field_dependencies_rules);

        event_dispatcher.dispatch(value_changed_event);

        expect(did_change_allowed_values_events).toHaveLength(2);

        const [first_target_notif, second_target_notif] = did_change_allowed_values_events;

        expect(first_target_notif.field_id).toBe(target_1_field_id);
        expect(first_target_notif.allowed_bind_value_ids).toStrictEqual([10511, 10512]);

        expect(second_target_notif.field_id).toBe(target_2_field_id);
        expect(second_target_notif.allowed_bind_value_ids).toStrictEqual([10521]);
    });
});
