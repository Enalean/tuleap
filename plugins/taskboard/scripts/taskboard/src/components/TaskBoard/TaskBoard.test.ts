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

import type { VueWrapper } from "@vue/test-utils";
import { shallowMount } from "@vue/test-utils";
import TaskBoard from "./TaskBoard.vue";
import type { ColumnDefinition, Swimlane } from "../../type";
import { getGlobalTestOptions } from "../../helpers/global-options-for-test";
import type { RootState } from "../../store/type";
import * as drekkenov from "@tuleap/drag-and-drop";
import type { SwimlaneState } from "../../store/swimlane/type";
import type { ErrorState } from "../../store/error/type";
import ErrorModal from "../GlobalError/ErrorModal.vue";

jest.mock("../../keyboard-navigation/keyboard-navigation");

describe("TaskBoard", () => {
    const mock_pointer_enters_columns = jest.fn();

    function createWrapper(
        are_closed_items_displayed: boolean,
        column_of_cell?: ColumnDefinition,
    ): VueWrapper<InstanceType<typeof TaskBoard>> {
        return shallowMount(TaskBoard, {
            global: {
                ...getGlobalTestOptions({
                    state: {
                        are_closed_items_displayed,
                    } as RootState,
                    getters: {
                        column_of_cell: () => () => column_of_cell,
                    },
                    modules: {
                        swimlane: {
                            state: { swimlanes: [] as Swimlane[] } as SwimlaneState,
                            namespaced: true,
                        },
                        error: {
                            state: {
                                has_modal_error: false,
                                modal_error_message: "",
                            } as ErrorState,
                            namespaced: true,
                        },
                        column: {
                            state: {
                                columns: [
                                    { id: 2, label: "To do" },
                                    { id: 3, label: "Done" },
                                ],
                            },
                            mutations: {
                                pointerEntersColumn: mock_pointer_enters_columns,
                            },
                            namespaced: true,
                        },
                    },
                }),
            },
        });
    }

    it("displays a table with header and body", () => {
        const wrapper = createWrapper(true);
        expect(wrapper.element).toMatchSnapshot();
    });

    it("displays a modal on error", () => {
        const wrapper = shallowMount(TaskBoard, {
            global: {
                ...getGlobalTestOptions({
                    modules: {
                        column: {
                            state: {
                                columns: [
                                    { id: 2, label: "To do" },
                                    { id: 3, label: "Done" },
                                ],
                            },
                            namespaced: true,
                        },
                        error: {
                            state: {
                                has_modal_error: true,
                                modal_error_message: "Ooooops",
                            },
                            namespaced: true,
                        },
                    },
                }),
            },
        });
        expect(wrapper.findComponent(ErrorModal).exists()).toBe(true);
    });

    describe(`mounted()`, () => {
        it(`will create a "drek"`, () => {
            const init = jest.spyOn(drekkenov, "init");
            createWrapper(false);

            expect(init).toHaveBeenCalled();
        });
    });

    describe(`drag/drop callbacks`, () => {
        let target_dropzone: HTMLElement, doc: Document, init: jest.SpyInstance;

        beforeEach(() => {
            init = jest.spyOn(drekkenov, "init");
            doc = createLocalDocument();
            target_dropzone = doc.createElement("div");
        });

        describe(`onDragEnter()`, () => {
            let payload: drekkenov.PossibleDropCallbackParameter;

            beforeEach(() => {
                payload = {
                    dragged_element: doc.createElement("div"),
                    source_dropzone: doc.createElement("div"),
                    target_dropzone,
                };
            });

            it(`will set the drekOver data on the dropzone
                to provide feedback that the drop in collapsed column is valid`, () => {
                const column = { is_collapsed: false } as ColumnDefinition;
                createWrapper(false, column);

                const dragEnterCallback = init.mock.calls[0][0].onDragEnter;
                dragEnterCallback(payload);

                expect(target_dropzone.dataset.drekOver).toBe("1");
            });

            it(`will inform the pointerenter`, () => {
                const column = { is_collapsed: true } as ColumnDefinition;
                createWrapper(false, column);

                const dragEnterCallback = init.mock.calls[0][0].onDragEnter;
                dragEnterCallback(payload);

                expect(mock_pointer_enters_columns).toHaveBeenCalled();
            });
        });

        describe(`onDragLeave()`, () => {
            let payload: drekkenov.DragDropCallbackParameter;

            beforeEach(() => {
                payload = { dragged_element: doc.createElement("div"), target_dropzone };
            });

            it(`will remove the drekOver data from the dropzone`, () => {
                target_dropzone.dataset.drekOver = "1";
                const column = { is_collapsed: false } as ColumnDefinition;
                createWrapper(false, column);

                const dragLeaveCallback = init.mock.calls[0][0].onDragLeave;
                dragLeaveCallback(payload);

                expect(target_dropzone.dataset.drekOver).toBeUndefined();
            });
        });
    });

    describe(`destroy()`, () => {
        it(`will destroy the "drek"`, () => {
            const mock_drek = {
                destroy: jest.fn(),
            };
            jest.spyOn(drekkenov, "init").mockImplementation(() => mock_drek);
            const wrapper = createWrapper(false);
            wrapper.unmount();

            expect(mock_drek.destroy).toHaveBeenCalled();
        });
    });
});

function createLocalDocument(): Document {
    return document.implementation.createHTMLDocument();
}
