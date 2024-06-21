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
import type { InternalUserGroupOpenListField } from "./UserGroupOpenListField";
import type { ControlUserGroupOpenListField } from "./UserGroupOpenListFieldController";
import { UserGroupOpenListFieldController } from "./UserGroupOpenListFieldController";
import { UserGroupOpenListFieldPresenter } from "./UserGroupOpenListFieldPresenter";

import * as tlp from "tlp";
import type { UserGroupValueModelItem } from "../../../../../domain/fields/user-group-open-list-field/UserGroupOpenListValueModel";
import type { UserGroupOpenListFieldType } from "../../../../../domain/fields/user-group-open-list-field/UserGroupOpenListFieldType";
import type { Select2SelectionEvent } from "../Select2SelectionEvent";

jest.mock("tlp");

const values: ReadonlyArray<UserGroupValueModelItem> = [
    { id: "101_3", label: "Project members" },
    { id: "101_4", label: "Project administrators" },
    { id: "2", label: "The best dudes" },
];

const field = {
    field_id: 1001,
    label: "CC",
    name: "cc",
    hint: "Select the user groups to notify",
    required: false,
    values,
} as UserGroupOpenListFieldType;

describe("UserGroupOpenListField", () => {
    let doc: Document, bind_value_objects: UserGroupValueModelItem[];

    beforeEach(() => {
        doc = document.implementation.createHTMLDocument();
        bind_value_objects = [];
    });

    const getController = (): ControlUserGroupOpenListField =>
        UserGroupOpenListFieldController(field, bind_value_objects);

    describe("init() -", () => {
        it(`When initializing the controller, Then:
            - a select2 will be created
            - its events will be listened
            - the component presenter will be built`, () => {
            const controller = getController();
            const host = {
                select_element: doc.createElement("select"),
                presenter: controller.buildInitialPresenter(),
            } as InternalUserGroupOpenListField;

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

    describe("handleValueSelection() -", () => {
        it(`Given an event with a selection, then an object with 'id' and 'label' properties will be pushed in the value model`, () => {
            const selected_value = { id: "2", text: "The best dudes" };
            const event: Select2SelectionEvent = {
                params: {
                    args: {
                        data: selected_value,
                    },
                },
            };

            const host = {} as InternalUserGroupOpenListField;
            const expected_bind_value = {
                id: selected_value.id,
                label: selected_value.text,
            };

            bind_value_objects = [];
            getController().handleValueSelection(host, event);

            const value_in_presenter = host.presenter.values.find(
                (value) => value.id === expected_bind_value.id,
            );

            expect(bind_value_objects).toStrictEqual([expected_bind_value]);
            expect(value_in_presenter?.selected).toBe(true);
        });
    });

    describe("handleValueUnselection() -", () => {
        it("Given an event with a static value unselection, then it will be removed from the value model", () => {
            const value_to_unselect = { id: "2", text: "The best dudes" };
            const model_value = {
                id: value_to_unselect.id,
                label: value_to_unselect.text,
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
                presenter: UserGroupOpenListFieldPresenter.withSelectableValues(
                    field,
                    bind_value_objects,
                    [model_value],
                ),
            } as InternalUserGroupOpenListField;

            getController().handleValueUnselection(host, event);

            const value_in_presenter = host.presenter.values.find(
                (value) => value.id === model_value.id,
            );

            expect(bind_value_objects).toHaveLength(0);
            expect(value_in_presenter?.selected).toBe(false);
        });
    });
});
