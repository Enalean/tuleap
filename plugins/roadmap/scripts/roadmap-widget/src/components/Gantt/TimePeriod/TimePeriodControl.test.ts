/**
 * Copyright (c) Enalean, 2021 - present. All Rights Reserved.
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

import { shallowMount } from "@vue/test-utils";
import type { Wrapper } from "@vue/test-utils";
import { createStoreMock } from "@tuleap/vuex-store-wrapper-jest";
import { createRoadmapLocalVue } from "../../../helpers/local-vue-for-test";
import type { TasksState } from "../../../store/tasks/type";
import type { RootState } from "../../../store/type";
import TimePeriodControl from "./TimePeriodControl.vue";

describe("TimePeriodControl", () => {
    let has_at_least_one_row_shown: boolean;

    beforeEach(() => {
        has_at_least_one_row_shown = true;
    });

    async function getWrapper(): Promise<Wrapper<Vue>> {
        return shallowMount(TimePeriodControl, {
            propsData: {
                value: "month",
            },
            localVue: await createRoadmapLocalVue(),
            mocks: {
                $store: createStoreMock({
                    state: {
                        tasks: {} as TasksState,
                    } as RootState,
                    getters: {
                        "tasks/has_at_least_one_row_shown": has_at_least_one_row_shown,
                    },
                }),
            },
        });
    }
    it("Emits input event when the value is changed", async () => {
        const wrapper = await getWrapper();

        wrapper.find(`[data-test=quarter]`).setSelected();
        wrapper.find(`[data-test=month]`).setSelected();
        wrapper.find(`[data-test=week]`).setSelected();

        const input_event = wrapper.emitted("input");
        if (!input_event) {
            throw new Error("Failed to catch input event");
        }

        expect(input_event[0][0]).toBe("quarter");
        expect(input_event[1][0]).toBe("month");
        expect(input_event[2][0]).toBe("week");
    });

    it("should mark the selectbox as disabled if there is no rows", async () => {
        has_at_least_one_row_shown = false;
        const wrapper = await getWrapper();

        const select = wrapper.find("[data-test=select-timescale]").element;
        if (!(select instanceof HTMLSelectElement)) {
            throw Error("Unable to find select");
        }

        expect(select.disabled).toBe(true);
    });
});
