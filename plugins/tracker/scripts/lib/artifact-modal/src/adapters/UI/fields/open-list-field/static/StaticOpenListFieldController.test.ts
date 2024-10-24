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

import { describe, it, expect, beforeEach, jest } from "@jest/globals";
import type { InternalStaticOpenListField } from "./StaticOpenListField";
import type { ControlStaticOpenListField } from "./StaticOpenListFieldController";
import { StaticOpenListFieldController } from "./StaticOpenListFieldController";
import { StaticOpenListFieldPresenterBuilder } from "./StaticOpenListFieldPresenter";

import * as tlp from "tlp";
import type { StaticValueModelItem } from "../../../../../domain/fields/static-open-list-field/StaticOpenListValueModel";
import type { StaticOpenListFieldType } from "../../../../../domain/fields/static-open-list-field/StaticOpenListFieldType";
import type { Select2SelectionEvent } from "../Select2SelectionEvent";

jest.mock("tlp");

const values: ReadonlyArray<StaticValueModelItem> = [];
const field = {
    field_id: 1001,
    label: "Random meaningless stuff",
    name: "random_stuff",
    hint: "Please select some value, or create one and forget about it",
    required: false,
    values,
} as StaticOpenListFieldType;

describe("StaticOpenListFieldController", () => {
    let doc: Document, bind_value_objects: StaticValueModelItem[];

    beforeEach(() => {
        doc = document.implementation.createHTMLDocument();
        bind_value_objects = [
            { id: "1", label: "Foo", is_hidden: false },
            { id: "2", label: "Bar", is_hidden: false },
            { id: "3", label: "Baz", is_hidden: false },
        ];
    });

    const getController = (): ControlStaticOpenListField =>
        StaticOpenListFieldController(field, bind_value_objects);

    describe("init() -", () => {
        it(`When initializing the controller, Then:
            - a select2 will be created
            - its events will be listened
            - the component presenter will be built`, () => {
            const controller = getController();
            const host = {
                select_element: doc.createElement("select"),
                presenter: controller.getInitialPresenter(),
            } as InternalStaticOpenListField;

            const registerSelect2EventHandler = jest.fn();
            jest.spyOn(tlp, "select2").mockReturnValue({
                // eslint-disable-next-line @typescript-eslint/ban-ts-comment
                // @ts-ignore
                on: registerSelect2EventHandler,
            });

            controller.initSelect2(host);

            expect(tlp.select2).toHaveBeenCalledTimes(1);
            expect(registerSelect2EventHandler).toHaveBeenCalledTimes(2);
            expect(registerSelect2EventHandler).toHaveBeenCalledWith(
                "select2:selecting",
                expect.any(Function),
            );
            expect(registerSelect2EventHandler).toHaveBeenCalledWith(
                "select2:unselecting",
                expect.any(Function),
            );
            expect(host.presenter).toBeDefined();
        });
    });

    describe("handleStaticValueSelection() -", () => {
        it(`Given an event with a selection, then an object with 'id' and 'label' properties will be pushed in the value model`, () => {
            const selected_value = {
                id: "979",
                text: "palpate",
            };
            const event: Select2SelectionEvent = {
                params: {
                    args: {
                        data: selected_value,
                    },
                },
            };

            const host = {} as InternalStaticOpenListField;
            const expected_bind_value = {
                id: selected_value.id,
                label: selected_value.text,
                is_hidden: false,
            };

            bind_value_objects = [];
            getController().handleStaticValueSelection(host, event);

            expect(bind_value_objects).toStrictEqual([expected_bind_value]);
            expect(host.presenter.values).toStrictEqual([
                { ...expected_bind_value, selected: true },
            ]);
        });

        it("Given an event with a new 'tag' (not in possible values) selection, then an object with 'label' property will be pushed in the value model", function () {
            const selected_value = {
                id: "peptonuria",
                text: "peptonuria",
                is_tag: true,
            };

            const event: Select2SelectionEvent = {
                params: {
                    args: {
                        data: selected_value,
                    },
                },
            };

            const host = {} as InternalStaticOpenListField;
            const expected_bind_value = {
                id: selected_value.id,
                label: selected_value.text,
                is_hidden: false,
            };

            bind_value_objects = [];
            getController().handleStaticValueSelection(host, event);

            expect(bind_value_objects).toStrictEqual([expected_bind_value]);
            expect(host.presenter.values).toStrictEqual([
                { ...expected_bind_value, selected: true },
            ]);
        });
    });

    describe("handleStaticValueUnselection() -", () => {
        it("Given an event with a static value unselection, then it will be removed from the value model", () => {
            const value_to_unselect = {
                id: "470",
                text: "unriddleable",
            };
            const model_value = {
                id: value_to_unselect.id,
                label: value_to_unselect.text,
                is_hidden: false,
            };
            bind_value_objects = [model_value];

            const event: Select2SelectionEvent = {
                params: {
                    args: {
                        data: value_to_unselect,
                    },
                },
            };

            const host = {
                presenter: StaticOpenListFieldPresenterBuilder.withSelectableValues(
                    field,
                    bind_value_objects,
                    [model_value],
                ),
            } as InternalStaticOpenListField;

            getController().handleStaticValueUnselection(host, event);

            expect(bind_value_objects).toHaveLength(0);
            expect(host.presenter.values).toHaveLength(0);
        });

        it(`"Given an event with a 'tag' (not in possible values) unselection, then it will be removed from the value model"`, () => {
            const value_to_unselect = {
                id: "raticide",
                text: "raticide",
                isTag: true,
            };

            const model_value = {
                id: value_to_unselect.id,
                label: value_to_unselect.text,
                is_hidden: false,
            };
            bind_value_objects = [model_value];

            const event: Select2SelectionEvent = {
                params: {
                    args: {
                        data: value_to_unselect,
                    },
                },
            };

            const host = {
                presenter: StaticOpenListFieldPresenterBuilder.withSelectableValues(
                    field,
                    bind_value_objects,
                    [model_value],
                ),
            } as InternalStaticOpenListField;

            getController().handleStaticValueUnselection(host, event);

            expect(bind_value_objects).toHaveLength(0);
            expect(host.presenter.values).toHaveLength(0);
        });
    });

    describe("newOpenListStaticValue() -", () => {
        it(`Given blank space, then it returns null`, () => {
            const new_open_value = {
                term: "   ",
            };

            const result = getController().newOpenListStaticValue(field, new_open_value);

            expect(result).toBeNull();
        });

        it(`Given a string, then it returns an object with 'id', 'text', and 'isTag' attributes`, () => {
            const new_open_value = {
                term: "slopshop",
            };

            const result = getController().newOpenListStaticValue(field, new_open_value);

            expect(result).toStrictEqual({
                id: "slopshop",
                text: "slopshop",
                isTag: true,
            });
        });

        it(`Given a string with blank space, it trims it and returns an object`, () => {
            const new_open_value = {
                term: " slopshop  ",
            };

            const result = getController().newOpenListStaticValue(field, new_open_value);

            expect(result).toStrictEqual({
                id: "slopshop",
                text: "slopshop",
                isTag: true,
            });
        });

        it(`Given a string that already exists in the field's possible values, then it returns null`, () => {
            const field_with_values = {
                ...field,
                values: [
                    {
                        id: "682",
                        label: "magnetotherapy",
                        is_hidden: false,
                    },
                    {
                        id: "815",
                        label: "extensometer",
                        is_hidden: false,
                    },
                ],
            };

            const new_open_value = {
                term: "extensometer",
            };

            const result = getController().newOpenListStaticValue(
                field_with_values,
                new_open_value,
            );

            expect(result).toBeNull();
        });
    });
});
