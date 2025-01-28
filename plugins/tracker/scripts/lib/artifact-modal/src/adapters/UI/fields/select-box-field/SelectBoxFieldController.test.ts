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

import * as list_picker_lib from "@tuleap/list-picker";
import type { ListFieldStructure } from "@tuleap/plugin-tracker-rest-api-types";
import type { SelectBoxFieldValueModelType } from "./SelectBoxFieldValueModelType";
import type { ControlSelectBoxField } from "./SelectBoxFieldController";
import { SelectBoxFieldController } from "./SelectBoxFieldController";
import type { EventDispatcherType } from "../../../../domain/AllEvents";
import { EventDispatcher } from "../../../../domain/AllEvents";
import { DidChangeAllowedValues } from "../../../../domain/fields/select-box-field/DidChangeAllowedValues";
import type { DidChangeListFieldValue } from "../../../../domain/fields/select-box-field/DidChangeListFieldValue";

const option_1_value = 101,
    option_2_value = 102;

describe("SelectBoxFieldController", () => {
    let field: ListFieldStructure,
        value_model: SelectBoxFieldValueModelType,
        select: HTMLSelectElement,
        option_1: HTMLOptionElement,
        option_2: HTMLOptionElement,
        event_dispatcher: EventDispatcherType,
        did_change_allowed_values_events: DidChangeAllowedValues[],
        did_change_list_field_value_events: DidChangeListFieldValue[];

    const getController = (): ControlSelectBoxField =>
        SelectBoxFieldController(event_dispatcher, field, value_model, false, "en_US");

    beforeEach(() => {
        did_change_allowed_values_events = [];
        did_change_list_field_value_events = [];
        event_dispatcher = EventDispatcher();

        event_dispatcher.addObserver("DidChangeAllowedValues", (event) => {
            did_change_allowed_values_events.push(event);
        });
        event_dispatcher.addObserver("DidChangeListFieldValue", (event) => {
            did_change_list_field_value_events.push(event);
        });

        field = {
            field_id: 105,
            label: "Status",
            required: true,
            values: [
                { id: 101, label: "option 1" },
                { id: 102, label: "option 2" },
            ],
        } as unknown as ListFieldStructure;

        value_model = {
            bind_value_ids: [],
        };

        const doc = document.implementation.createHTMLDocument();
        select = doc.createElement("select");

        option_1 = doc.createElement("option");
        option_1.value = String(option_1_value);

        option_2 = doc.createElement("option");
        option_2.value = String(option_2_value);

        select.append(option_1, option_2);
    });

    describe("setSelectedValue", () => {
        it("When the select has a value, then it should mutate the value model and dispatch a DidChangeListFieldValue event", () => {
            getController().setSelectedValue([option_2_value]);

            expect(value_model.bind_value_ids).toStrictEqual([option_2_value]);
            expect(did_change_list_field_value_events).toHaveLength(1);

            const [fired_event] = did_change_list_field_value_events;
            expect(fired_event.field_id).toStrictEqual(field.field_id);
            expect(fired_event.bind_value_ids).toStrictEqual([option_2_value]);
        });
    });

    describe("onDependencyChange", () => {
        it(`Given that a DidChangeAllowedValues has been received
            When it is not targeting the current field
            Then it should ignore it`, () => {
            const callback = jest.fn();

            getController().onDependencyChange(callback);
            event_dispatcher.dispatch(DidChangeAllowedValues(field.field_id + 10, [1, 2]));

            expect(did_change_allowed_values_events).toHaveLength(1);
            expect(callback).not.toHaveBeenCalled();
        });

        it(`Given that a DidChangeAllowedValues has been received
            Then it should:
            - keep only the allowed values in the value model
            - execute the callback with the updated model and presenter
            - and notify potential target fields about its value update`, () => {
            const callback = jest.fn();

            value_model.bind_value_ids = [option_1_value, option_2_value];

            getController().onDependencyChange(callback);
            event_dispatcher.dispatch(DidChangeAllowedValues(field.field_id, [option_2_value]));

            expect(did_change_list_field_value_events).toHaveLength(1);
            expect(callback).toHaveBeenCalledWith([option_2_value], {
                field_id: 105,
                field_label: "Status",
                is_field_disabled: false,
                is_field_required: true,
                is_multiple_select_box: false,
                select_box_options: [
                    {
                        id: option_2.value,
                        label: "option 2",
                    },
                ],
            });

            const [fired_event] = did_change_list_field_value_events;
            expect(fired_event.field_id).toStrictEqual(field.field_id);
            expect(fired_event.bind_value_ids).toStrictEqual([option_2_value]);
        });

        it(`Given that a DidChangeAllowedValues has been received
            When the selected values are not allowed anymore
            Then it should select the first possible allowed value`, () => {
            value_model.bind_value_ids = [option_1_value];

            getController().onDependencyChange(() => {
                /* Do nothing */
            });
            event_dispatcher.dispatch(DidChangeAllowedValues(field.field_id, [option_2_value]));

            expect(value_model.bind_value_ids).toStrictEqual([option_2_value]);
        });

        it(`Given that a DidChangeAllowedValues has been received
            When the selected values are not allowed anymore
            And there is no allowed value anymore
            Then it should only clear the previous selection`, () => {
            value_model.bind_value_ids = [option_1_value];

            getController().onDependencyChange(() => {
                /* Do nothing */
            });
            event_dispatcher.dispatch(DidChangeAllowedValues(field.field_id, []));

            expect(value_model.bind_value_ids).toStrictEqual([]);
        });
    });

    describe("create/destroy list-picker", () => {
        it("should be able to create and destroy the list-picker instance", () => {
            const controller = getController();
            const list_picker_instance = {
                destroy: jest.fn(),
            };

            jest.spyOn(list_picker_lib, "createListPicker").mockReturnValue(list_picker_instance);

            controller.initListPicker(select);

            expect(list_picker_lib.createListPicker).toHaveBeenCalledWith(select, {
                locale: "en_US",
                is_filterable: true,
            });

            controller.destroy();

            expect(list_picker_instance.destroy).toHaveBeenCalled();
        });

        it("should activate the none_value option of the list-picker when the field is a multiple list field", () => {
            field = {
                type: "msb",
            } as unknown as ListFieldStructure;

            jest.spyOn(list_picker_lib, "createListPicker").mockImplementation();

            getController().initListPicker(select);

            expect(list_picker_lib.createListPicker).toHaveBeenCalledWith(select, {
                locale: "en_US",
                is_filterable: true,
                none_value: "100",
            });
        });
    });
});
