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
import BarPopover from "./BarPopover.vue";
import { createRoadmapLocalVue } from "../../../helpers/local-vue-for-test";
import type { Task } from "../../../type";
import { createStoreMock } from "../../../../../../../../src/scripts/vue-components/store-wrapper-jest";

describe("BarPopover", () => {
    it("should display the info of the task", async () => {
        const wrapper = shallowMount(BarPopover, {
            localVue: await createRoadmapLocalVue(),
            propsData: {
                task: {
                    xref: "art #123",
                    title: "Create button",
                    start: new Date("2020-01-12T15:00:00.000Z"),
                    end: new Date("2020-01-30T15:00:00.000Z"),
                } as Task,
            },
            mocks: {
                $store: createStoreMock({
                    state: {
                        locale_bcp47: "en-US",
                    },
                }),
            },
        });

        expect(wrapper.text()).toContain("art #123");
        expect(wrapper.text()).toContain("Create button");
        expect(wrapper.text()).toContain("January 12, 2020");
        expect(wrapper.text()).toContain("January 30, 2020");
    });

    it("should display undefined if no start date", async () => {
        const wrapper = shallowMount(BarPopover, {
            localVue: await createRoadmapLocalVue(),
            propsData: {
                task: {
                    xref: "art #123",
                    title: "Create button",
                    start: null,
                    end: new Date("2020-01-30T15:00:00.000Z"),
                } as Task,
            },
            mocks: {
                $store: createStoreMock({
                    state: {
                        locale_bcp47: "en-US",
                    },
                }),
            },
        });

        expect(wrapper.text()).toContain("Undefined");
        expect(wrapper.text()).toContain("January 30, 2020");
    });

    it("should display undefined if no end date", async () => {
        const wrapper = shallowMount(BarPopover, {
            localVue: await createRoadmapLocalVue(),
            propsData: {
                task: {
                    xref: "art #123",
                    title: "Create button",
                    start: new Date("2020-01-12T15:00:00.000Z"),
                    end: null,
                } as Task,
            },
            mocks: {
                $store: createStoreMock({
                    state: {
                        locale_bcp47: "en-US",
                    },
                }),
            },
        });

        expect(wrapper.text()).toContain("January 12, 2020");
        expect(wrapper.text()).toContain("Undefined");
    });
});
