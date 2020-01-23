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

import { shallowMount, Wrapper } from "@vue/test-utils";
import TaskBoard from "./TaskBoard.vue";
import { createStoreMock } from "../../../../../../../src/www/scripts/vue-components/store-wrapper-jest";
import { Swimlane } from "../../type";
import { createTaskboardLocalVue } from "../../helpers/local-vue-for-test";
import { RootState } from "../../store/type";
import * as drekkenov from "../../helpers/drag-and-drop/drekkenov";
import ErrorModal from "../GlobalError/ErrorModal.vue";

async function createWrapper(
    swimlanes: Swimlane[],
    are_closed_items_displayed: boolean
): Promise<Wrapper<TaskBoard>> {
    return shallowMount(TaskBoard, {
        localVue: await createTaskboardLocalVue(),
        mocks: {
            $store: createStoreMock({
                state: {
                    are_closed_items_displayed,
                    swimlane: { swimlanes },
                    column: {},
                    error: { has_modal_error: false, modal_error_message: "" }
                } as RootState
            })
        }
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
                            { id: 3, label: "Done" }
                        ],
                        error: { has_modal_error: false, modal_error_message: "" }
                    }
                })
            }
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
                            { id: 3, label: "Done" }
                        ],
                        error: { has_modal_error: true, modal_error_message: "Ooooops" }
                    }
                })
            }
        });
        expect(wrapper.contains(ErrorModal)).toBe(true);
    });

    describe(`mounted()`, () => {
        it(`will create a "drek"`, async () => {
            const init = jest.spyOn(drekkenov, "init");
            await createWrapper([], false);

            expect(init).toHaveBeenCalled();
        });
    });

    describe(`destroy()`, () => {
        it(`will destroy the "drek"`, async () => {
            const mock_drek = {
                destroy: jest.fn()
            };
            jest.spyOn(drekkenov, "init").mockImplementation(() => mock_drek);
            const wrapper = await createWrapper([], false);
            wrapper.destroy();

            expect(mock_drek.destroy).toHaveBeenCalled();
        });
    });
});
