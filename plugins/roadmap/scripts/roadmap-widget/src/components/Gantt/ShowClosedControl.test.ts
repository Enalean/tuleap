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

import type { Wrapper } from "@vue/test-utils";
import { shallowMount } from "@vue/test-utils";
import { createRoadmapLocalVue } from "../../helpers/local-vue-for-test";
import { createStoreMock } from "@tuleap/vuex-store-wrapper-jest";
import type { RootState } from "../../store/type";
import ShowClosedControl from "./ShowClosedControl.vue";

describe("ShowClosedControl", () => {
    let show_closed_elements: boolean;
    async function getWrapper(): Promise<Wrapper<Vue>> {
        return shallowMount(ShowClosedControl, {
            localVue: await createRoadmapLocalVue(),
            mocks: {
                $store: createStoreMock({
                    state: {
                        show_closed_elements: show_closed_elements,
                    } as RootState,
                }),
            },
        });
    }

    it("should mutate the store when the user decides to show/hide the closed elements", async () => {
        show_closed_elements = false;
        const wrapper = await getWrapper();
        await wrapper.find("[data-test=input]").setChecked();
        expect(wrapper.vm.$store.commit).toHaveBeenCalledWith(
            "toggleClosedElements",
            show_closed_elements,
        );

        show_closed_elements = true;
        const wrapper2 = await getWrapper();
        await wrapper2.find("[data-test=input]").setChecked();
        expect(wrapper2.vm.$store.commit).toHaveBeenCalledWith(
            "toggleClosedElements",
            show_closed_elements,
        );
    });
});
