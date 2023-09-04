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
import SwimlaneHeader from "./SwimlaneHeader.vue";
import type { Swimlane } from "../../../../../type";
import { createTaskboardLocalVue } from "../../../../../helpers/local-vue-for-test";
import { createStoreMock } from "@tuleap/vuex-store-wrapper-jest";
import type { FullscreenState } from "../../../../../store/fullscreen/type";
import type { SwimlaneState } from "../../../../../store/swimlane/type";

const swimlane: Swimlane = {
    card: {
        color: "fiesta-red",
        label: "taskboard-swimlane",
    },
} as Swimlane;

async function createWrapper(
    is_fullscreen: boolean,
    backlog_items_have_children: boolean,
): Promise<Wrapper<SwimlaneHeader>> {
    return shallowMount(SwimlaneHeader, {
        localVue: await createTaskboardLocalVue(),
        mocks: {
            $store: createStoreMock({
                state: {
                    swimlane: {} as SwimlaneState,
                    fullscreen: {} as FullscreenState,
                    backlog_items_have_children: backlog_items_have_children,
                },
                getters: {
                    "swimlane/taskboard_cell_swimlane_header_classes": is_fullscreen
                        ? ["taskboard-fullscreen"]
                        : [""],
                },
            }),
        },
        propsData: {
            swimlane,
        },
    });
}

describe("SwimlaneHeader", () => {
    it("displays a toggle icon", async () => {
        const wrapper = await createWrapper(false, true);

        expect(wrapper.find(".taskboard-swimlane-toggle").exists()).toBe(true);
        expect(wrapper.find(".taskboard-fullscreen").exists()).toBe(false);
    });

    it("adds fullscreen class when taskboard is in fullscreen mode", async () => {
        const wrapper = await createWrapper(true, true);
        expect(wrapper.find(".taskboard-fullscreen").exists()).toBe(true);
    });

    it("collapse the swimlane when user click on the toggle icon", async () => {
        const wrapper = await createWrapper(false, true);

        wrapper.get(".taskboard-swimlane-toggle").trigger("click");
        expect(wrapper.vm.$store.dispatch).toHaveBeenCalledWith(
            "swimlane/collapseSwimlane",
            swimlane,
        );
    });

    it("does not display the swimline header is there is no tracker children", async () => {
        const wrapper = await createWrapper(false, false);
        expect(wrapper.find(".taskboard-cell-swimlane-header").exists()).toBe(false);
    });
});
