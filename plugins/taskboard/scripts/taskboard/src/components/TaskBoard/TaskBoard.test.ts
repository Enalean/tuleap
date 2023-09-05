/*
 * Copyright (c) Enalean, 2019 - present. All Rights Reserved.
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

import type { Wrapper } from "@vue/test-utils";
import { shallowMount } from "@vue/test-utils";
import TaskBoard from "./TaskBoard.vue";
import { createStoreMock } from "@tuleap/vuex-store-wrapper-jest";
import type { ColumnDefinition, Swimlane } from "../../type";
import { createTaskboardLocalVue } from "../../helpers/local-vue-for-test";
import type { RootState } from "../../store/type";
import * as drekkenov from "@tuleap/drag-and-drop";
import ErrorModal from "../GlobalError/ErrorModal.vue";

jest.mock("../../keyboard-navigation/keyboard-navigation");

async function createWrapper(
    swimlanes: Swimlane[],
    are_closed_items_displayed: boolean,
): Promise<Wrapper<TaskBoard>> {
    return shallowMount(TaskBoard, {
        localVue: await createTaskboardLocalVue(),
        mocks: {
            $store: createStoreMock({
                state: {
                    are_closed_items_displayed,
                    swimlane: { swimlanes },
                    column: {},
                    error: { has_modal_error: false, modal_error_message: "" },
                } as RootState,
            }),
        },
    });
}

describe("TaskBoard", () => {
    it("displays a table with header and body", () => {
        const wrapper = shallowMount(TaskBoard, {
            mocks: {
                $store: createStoreMock({
                    state: {
                        columns: [
                            { id: 2, label: "To do" },
                            { id: 3, label: "Done" },
                        ],
                        error: { has_modal_error: false, modal_error_message: "" },
                    },
                }),
            },
        });
        expect(wrapper.element).toMatchSnapshot();
    });

    it("displays a modal on error", () => {
        const wrapper = shallowMount(TaskBoard, {
            mocks: {
                $store: createStoreMock({
                    state: {
                        columns: [
                            { id: 2, label: "To do" },
                            { id: 3, label: "Done" },
                        ],
                        error: { has_modal_error: true, modal_error_message: "Ooooops" },
                    },
                }),
            },
        });
        expect(wrapper.findComponent(ErrorModal).exists()).toBe(true);
    });

    describe(`mounted()`, () => {
        it(`will create a "drek"`, async () => {
            const init = jest.spyOn(drekkenov, "init");
            await createWrapper([], false);

            expect(init).toHaveBeenCalled();
        });
    });

    describe(`drag/drop callbacks`, () => {
        let wrapper: Wrapper<TaskBoard>,
            target_dropzone: HTMLElement,
            doc: Document,
            init: jest.SpyInstance;

        beforeEach(async () => {
            init = jest.spyOn(drekkenov, "init");
            const getters = { column_of_cell: undefined };
            const store = createStoreMock({
                state: { column: {}, error: {} },
                getters,
            });
            wrapper = shallowMount(TaskBoard, {
                localVue: await createTaskboardLocalVue(),
                mocks: { $store: store },
            });

            doc = createLocalDocument();
            target_dropzone = doc.createElement("div");
        });

        describe(`onDragEnter()`, () => {
            let dragEnterCallback: (context: drekkenov.PossibleDropCallbackParameter) => void,
                payload: drekkenov.PossibleDropCallbackParameter;

            beforeEach(() => {
                dragEnterCallback = init.mock.calls[0][0].onDragEnter;
                payload = {
                    dragged_element: doc.createElement("div"),
                    source_dropzone: doc.createElement("div"),
                    target_dropzone,
                };
            });

            it(`will set the drekOver data on the dropzone
                to provide feedback that the drop in collapsed column is valid`, () => {
                const column = { is_collapsed: false } as ColumnDefinition;
                wrapper.vm.$store.getters.column_of_cell = (): ColumnDefinition => column;

                dragEnterCallback(payload);

                expect(target_dropzone.dataset.drekOver).toBe("1");
            });

            it(`when the column of the dropzone is collapsed,
                it will inform the pointerenter`, () => {
                const column = { is_collapsed: true } as ColumnDefinition;
                wrapper.vm.$store.getters.column_of_cell = (): ColumnDefinition => column;

                dragEnterCallback(payload);

                expect(wrapper.vm.$store.commit).toHaveBeenCalledWith(
                    "column/pointerEntersColumn",
                    column,
                );
            });

            it(`when the column of the dropzone is expanded,
                it won't inform the pointerenter`, () => {
                const column = { is_collapsed: false } as ColumnDefinition;
                wrapper.vm.$store.getters.column_of_cell = (): ColumnDefinition => column;

                dragEnterCallback(payload);

                expect(wrapper.vm.$store.commit).not.toHaveBeenCalled();
            });
        });

        describe(`onDragLeave()`, () => {
            let dragLeaveCallback: (context: drekkenov.DragDropCallbackParameter) => void,
                payload: drekkenov.DragDropCallbackParameter;

            beforeEach(() => {
                dragLeaveCallback = init.mock.calls[0][0].onDragLeave;
                payload = { dragged_element: doc.createElement("div"), target_dropzone };
            });

            it(`will remove the drekOver data from the dropzone`, () => {
                target_dropzone.dataset.drekOver = "1";
                const column = { is_collapsed: false } as ColumnDefinition;
                wrapper.vm.$store.getters.column_of_cell = (): ColumnDefinition => column;

                dragLeaveCallback(payload);

                expect(target_dropzone.dataset.drekOver).toBeUndefined();
            });

            it(`when the column of the dropzone is collapsed,
                it will inform the pointerleave`, () => {
                const column = { is_collapsed: true } as ColumnDefinition;
                wrapper.vm.$store.getters.column_of_cell = (): ColumnDefinition => column;

                dragLeaveCallback(payload);

                expect(wrapper.vm.$store.commit).toHaveBeenCalledWith(
                    "column/pointerLeavesColumn",
                    column,
                );
            });

            it(`when the column of the dropzone is expanded,
                it won't inform the pointerleave`, () => {
                const column = { is_collapsed: false } as ColumnDefinition;
                wrapper.vm.$store.getters.column_of_cell = (): ColumnDefinition => column;

                dragLeaveCallback(payload);

                expect(wrapper.vm.$store.commit).not.toHaveBeenCalled();
            });
        });
    });

    describe(`destroy()`, () => {
        it(`will destroy the "drek"`, async () => {
            const mock_drek = {
                destroy: jest.fn(),
            };
            jest.spyOn(drekkenov, "init").mockImplementation(() => mock_drek);
            const wrapper = await createWrapper([], false);
            wrapper.destroy();

            expect(mock_drek.destroy).toHaveBeenCalled();
        });
    });
});

function createLocalDocument(): Document {
    return document.implementation.createHTMLDocument();
}
