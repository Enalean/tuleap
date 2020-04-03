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
import SwimlaneHeader from "./SwimlaneHeader.vue";
import { Swimlane } from "../../../../../type";
import { createTaskboardLocalVue } from "../../../../../helpers/local-vue-for-test";
import { createStoreMock } from "../../../../../../../../../../src/scripts/vue-components/store-wrapper-jest";
import { FullscreenState } from "../../../../../store/fullscreen/type";
import { SwimlaneState } from "../../../../../store/swimlane/type";

const swimlane: Swimlane = {
    card: {
        color: "fiesta-red",
    },
} as Swimlane;

async function createWrapper(is_fullscreen: boolean): Promise<Wrapper<SwimlaneHeader>> {
    return shallowMount(SwimlaneHeader, {
        localVue: await createTaskboardLocalVue(),
        mocks: {
            $store: createStoreMock({
                state: {
                    swimlane: {} as SwimlaneState,
                    fullscreen: {} as FullscreenState,
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
        const wrapper = await createWrapper(false);

        expect(
            wrapper.contains(
                ".fa-minus-square.taskboard-swimlane-toggle.tlp-swatch-fiesta-red[role=button]"
            )
        ).toBe(true);
        expect(wrapper.contains(".taskboard-fullscreen")).toBe(false);
    });

    it("adds fullscreen class when taskboard is in fullscreen mode", async () => {
        const wrapper = await createWrapper(true);
        expect(wrapper.contains(".taskboard-fullscreen")).toBe(true);
    });

    it("collapse the swimlane when user click on the toggle icon", async () => {
        const wrapper = await createWrapper(false);

        wrapper.get(".taskboard-swimlane-toggle").trigger("click");
        expect(wrapper.vm.$store.dispatch).toHaveBeenCalledWith(
            "swimlane/collapseSwimlane",
            swimlane
        );
    });
});
