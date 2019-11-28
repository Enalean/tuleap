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
import EventBus from "../../helpers/event-bus";
import { Swimlane, TaskboardEvent } from "../../type";
import { createTaskboardLocalVue } from "../../helpers/local-vue-for-test";
import { RootState } from "../../store/type";
import * as dragula from "dragula";
import ErrorModal from "../GlobalError/ErrorModal.vue";

interface FakeDrake {
    on: jest.SpyInstance;
    destroy: jest.SpyInstance;
}

jest.mock("dragula", () => {
    const fake_drake = {
        on: jest.fn(),
        destroy: jest.fn(),
        cancel: jest.fn()
    };
    return jest.fn((): FakeDrake => fake_drake);
});

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
    afterEach(() => {
        jest.clearAllMocks();
    });

    it("displays a table with header and body", () => {
        const wrapper = shallowMount(TaskBoard, {
            mocks: {
                $store: createStoreMock({
                    state: {
                        columns: [{ id: 2, label: "To do" }, { id: 3, label: "Done" }],
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
                        columns: [{ id: 2, label: "To do" }, { id: 3, label: "Done" }],
                        error: { has_modal_error: true, modal_error_message: "Ooooops" }
                    }
                })
            }
        });
        expect(wrapper.contains(ErrorModal)).toBe(true);
    });

    it(`will cancel dragging on "Escape"`, async () => {
        const mock_drake = dragula.default();
        jest.spyOn(mock_drake, "cancel").mockImplementation(() => {});

        await createWrapper([], false);
        EventBus.$emit(TaskboardEvent.ESC_KEY_PRESSED);

        expect(mock_drake.cancel).toHaveBeenCalledWith(true);
    });

    describe(`mounted()`, () => {
        it(`will create a "drake"`, async () => {
            await createWrapper([], false);

            expect(dragula.default).toHaveBeenCalled();
        });

        it(`will listen to esc-key-pressed event`, async () => {
            const event_bus_on = jest.spyOn(EventBus, "$on");

            await createWrapper([], false);

            expect(event_bus_on).toHaveBeenCalledWith(
                TaskboardEvent.ESC_KEY_PRESSED,
                expect.any(Function)
            );
        });
    });

    describe(`destroy()`, () => {
        it(`will destroy the "drake"`, async () => {
            const mock_drake = dragula.default();
            const wrapper = await createWrapper([], false);
            wrapper.destroy();

            expect(mock_drake.destroy).toHaveBeenCalled();
        });

        it(`will remove the esc-key-pressed listener`, async () => {
            const event_bus_off = jest.spyOn(EventBus, "$off");

            const wrapper = await createWrapper([], false);
            wrapper.destroy();

            expect(event_bus_off).toHaveBeenCalledWith(
                TaskboardEvent.ESC_KEY_PRESSED,
                expect.any(Function)
            );
        });
    });
});
