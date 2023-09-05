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

import { shallowMount } from "@vue/test-utils";
import ProgramIncrementList from "./ProgramIncrementList.vue";
import * as retriever from "../../../helpers/ProgramIncrement/program-increment-retriever";
import { createProgramManagementLocalVue } from "../../../helpers/local-vue-for-test";
import type { DefaultData } from "vue/types/options";
import type { ProgramIncrement } from "../../../helpers/ProgramIncrement/program-increment-retriever";
import { createStoreMock } from "@tuleap/vuex-store-wrapper-jest";

describe("ProgramIncrementList", () => {
    it("Displays the empty state when no artifact are found", async () => {
        jest.spyOn(retriever, "getProgramIncrements").mockResolvedValue([]);

        const wrapper = shallowMount(ProgramIncrementList, {
            localVue: await createProgramManagementLocalVue(),
            data(): DefaultData<ProgramIncrementList> {
                return {
                    program_increments: [],
                    is_loading: false,
                    has_error: false,
                };
            },
            mocks: {
                $store: createStoreMock({
                    state: {
                        configuration: { program_id: 202 },
                    },
                }),
            },
        });

        expect(wrapper.find("[data-test=empty-state]").exists()).toBe(true);
        expect(wrapper.find("[data-test=program-increment-skeleton]").exists()).toBe(false);
        expect(wrapper.find("[data-test=program-increments]").exists()).toBe(false);
        expect(wrapper.find("[data-test=program-increment-error]").exists()).toBe(false);
    });

    it("Displays an error when rest route fail", async () => {
        jest.spyOn(retriever, "getProgramIncrements").mockResolvedValue([]);
        const wrapper = shallowMount(ProgramIncrementList, {
            localVue: await createProgramManagementLocalVue(),
            data(): DefaultData<ProgramIncrementList> {
                return {
                    program_increments: [],
                    is_loading: false,
                    has_error: true,
                    error_message: "Oups, something happened",
                };
            },
            mocks: {
                $store: createStoreMock({
                    state: {
                        configuration: { program_id: 202 },
                    },
                }),
            },
        });

        expect(wrapper.find("[data-test=empty-state]").exists()).toBe(false);
        expect(wrapper.find("[data-test=program-increment-skeleton]").exists()).toBe(false);
        expect(wrapper.find("[data-test=program-increments]").exists()).toBe(false);
        expect(wrapper.find("[data-test=program-increment-error]").exists()).toBe(true);
    });

    it("Displays the elements to be planned", async () => {
        const element_one = {
            id: 1,
            title: "PI 1",
            status: '"To be Planned',
            start_date: null,
            end_date: null,
        } as ProgramIncrement;
        const element_two = {
            title: "PI 2",
            status: "Planned",
            start_date: "2021-01-20T00:00:00+01:00",
            end_date: "2021-01-20T00:00:00+01:00",
            id: 2,
        } as ProgramIncrement;

        jest.spyOn(retriever, "getProgramIncrements").mockResolvedValue([element_one, element_two]);

        const wrapper = shallowMount(ProgramIncrementList, {
            localVue: await createProgramManagementLocalVue(),
            data(): DefaultData<ProgramIncrementList> {
                return {
                    program_increments: [element_one, element_two],
                    is_loading: false,
                    has_error: false,
                    error_message: "",
                };
            },
            mocks: {
                $store: createStoreMock({
                    state: {
                        configuration: { program_id: 202 },
                    },
                }),
            },
        });

        expect(wrapper.find("[data-test=empty-state]").exists()).toBe(false);
        expect(wrapper.find("[data-test=program-increment-skeleton]").exists()).toBe(false);
        expect(wrapper.find("[data-test=program-increments]").exists()).toBe(true);
        expect(wrapper.find("[data-test=program-increment-error]").exists()).toBe(false);
    });

    it("User can see the button when he can create program increment", async () => {
        jest.spyOn(retriever, "getProgramIncrements").mockResolvedValue([]);

        const wrapper = shallowMount(ProgramIncrementList, {
            localVue: await createProgramManagementLocalVue(),
            data(): DefaultData<ProgramIncrementList> {
                return {
                    program_increments: [
                        {
                            id: 1,
                            title: "PI 1",
                            status: '"To be Planned',
                            start_date: null,
                            end_date: null,
                        } as ProgramIncrement,
                    ],
                    is_loading: false,
                    has_error: false,
                };
            },
            mocks: {
                $store: createStoreMock({
                    state: {
                        configuration: {
                            program_id: 202,
                            can_create_program_increment: true,
                            tracker_program_increment_label: "Program Increments",
                            tracker_program_increment_sub_label: "program increment",
                        },
                    },
                }),
            },
        });

        expect(wrapper.find("[data-test=create-program-increment-button]").exists()).toBe(true);
        expect(wrapper.find("[data-test=program-increment-title]").text()).toBe(
            "Program Increments",
        );
        expect(wrapper.find("[data-test=button-add-program-increment-label]").text()).toBe(
            "New program increment",
        );
    });

    it("No button is displayed when user can not add program increments", async () => {
        jest.spyOn(retriever, "getProgramIncrements").mockResolvedValue([]);

        const wrapper = shallowMount(ProgramIncrementList, {
            localVue: await createProgramManagementLocalVue(),
            data(): DefaultData<ProgramIncrementList> {
                return {
                    program_increments: [
                        {
                            id: 1,
                            title: "PI 1",
                            status: '"To be Planned',
                            start_date: null,
                            end_date: null,
                        } as ProgramIncrement,
                    ],
                    is_loading: false,
                    has_error: false,
                };
            },
            mocks: {
                $store: createStoreMock({
                    state: {
                        configuration: {
                            program_id: 202,
                            can_create_program_increment: false,
                        },
                    },
                }),
            },
        });

        expect(wrapper.find("[data-test=create-program-increment-button]").exists()).toBe(false);
    });
});
