/**
 * Copyright (c) Enalean, 2021 - Present. All Rights Reserved.
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
import { createStoreMock } from "@tuleap/vuex-store-wrapper-jest";
import ProgramIncrementList from "./ProgramIncrementList.vue";
import type { ProgramIncrement } from "../../../helpers/ProgramIncrement/program-increment-retriever";
import * as retriever from "../../../helpers/ProgramIncrement/program-increment-retriever";
import { createProgramManagementLocalVue } from "../../../helpers/local-vue-for-test";

jest.useFakeTimers();

describe("ProgramIncrementList", () => {
    async function getWrapper(can_create_program_increment: boolean): Promise<Wrapper<Vue>> {
        return shallowMount(ProgramIncrementList, {
            localVue: await createProgramManagementLocalVue(),
            mocks: {
                $store: createStoreMock({
                    state: {
                        configuration: {
                            can_create_program_increment,
                            tracker_program_increment_label: "Program Increments",
                            tracker_program_increment_sub_label: "program increment",
                            tracker_program_increment_id: 532,
                            program_id: 202,
                        },
                    },
                }),
            },
        });
    }

    it("Displays the empty state when no artifact are found", async () => {
        jest.spyOn(retriever, "getProgramIncrements").mockResolvedValue([]);

        const wrapper = await getWrapper(true);
        await jest.runOnlyPendingTimersAsync();

        expect(wrapper.find("[data-test=empty-state]").exists()).toBe(true);
        expect(wrapper.find("[data-test=program-increment-skeleton]").exists()).toBe(false);
        expect(wrapper.find("[data-test=program-increments]").exists()).toBe(false);
        expect(wrapper.find("[data-test=program-increment-error]").exists()).toBe(false);
    });

    it("Displays an error when rest route fail", async () => {
        jest.spyOn(retriever, "getProgramIncrements").mockResolvedValue([]);
        const wrapper = await getWrapper(true);
        wrapper.setData({ has_error: true, error_message: "Oups, something happened" });
        await wrapper.vm.$nextTick();

        expect(wrapper.find("[data-test=empty-state]").exists()).toBe(false);
        expect(wrapper.find("[data-test=program-increment-skeleton]").exists()).toBe(false);
        expect(wrapper.find("[data-test=program-increments]").exists()).toBe(false);
        expect(wrapper.find("[data-test=program-increment-error]").exists()).toBe(true);
    });

    it("Displays the program increments", async () => {
        const increment_one = {
            id: 1,
            title: "PI 1",
            status: "Planned",
            start_date: null,
            end_date: null,
        } as ProgramIncrement;
        const increment_two = {
            title: "PI 2",
            status: "Ongoing",
            start_date: "2021-01-20T00:00:00+01:00",
            end_date: "2021-01-20T00:00:00+01:00",
            id: 2,
        } as ProgramIncrement;

        jest.spyOn(retriever, "getProgramIncrements").mockResolvedValue([
            increment_one,
            increment_two,
        ]);

        const wrapper = await getWrapper(true);
        await jest.runOnlyPendingTimersAsync();

        expect(wrapper.find("[data-test=empty-state]").exists()).toBe(false);
        expect(wrapper.find("[data-test=program-increment-skeleton]").exists()).toBe(false);
        expect(wrapper.find("[data-test=program-increments]").exists()).toBe(true);
        expect(wrapper.find("[data-test=program-increment-error]").exists()).toBe(false);
    });

    it("User can see the button when he can create program increment", async () => {
        jest.spyOn(retriever, "getProgramIncrements").mockResolvedValue([
            {
                id: 1,
                title: "PI 1",
                status: "Planned",
                start_date: "2021-01-20T00:00:00+01:00",
                end_date: "2021-01-20T00:00:00+01:00",
            } as ProgramIncrement,
        ]);

        const wrapper = await getWrapper(true);
        await jest.runOnlyPendingTimersAsync();

        expect(wrapper.find("[data-test=create-program-increment-button]").exists()).toBe(true);
        expect(wrapper.find("[data-test=program-increment-title]").text()).toBe(
            "Program Increments",
        );
        expect(wrapper.find("[data-test=create-program-increment-button]").text()).toBe(
            "New program increment",
        );
    });

    it("No button is displayed when user can not add program increments", async () => {
        jest.spyOn(retriever, "getProgramIncrements").mockResolvedValue([
            {
                id: 1,
                title: "PI 1",
                status: "Planned",
                start_date: "2021-01-20T00:00:00+01:00",
                end_date: "2021-01-20T00:00:00+01:00",
            } as ProgramIncrement,
        ]);

        const wrapper = await getWrapper(false);

        expect(wrapper.find("[data-test=create-program-increment-button]").exists()).toBe(false);
    });
});
