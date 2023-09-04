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
import WhatsHotSection from "./WhatsHotSection.vue";
import { createStoreMock } from "@tuleap/vuex-store-wrapper-jest";
import type { MilestoneData, StoreOptions } from "../../type";
import { createReleaseWidgetLocalVue } from "../../helpers/local-vue-for-test";

const project_id = 102;

async function getPersonalWidgetInstance(
    store_options: StoreOptions,
): Promise<Wrapper<WhatsHotSection>> {
    const store = createStoreMock(store_options);
    const component_options = {
        propsData: {
            project_id,
        },
        mocks: { $store: store },
        localVue: await createReleaseWidgetLocalVue(),
    };

    return shallowMount(WhatsHotSection, component_options);
}

describe("What'sHotSection", () => {
    let store_options: StoreOptions = {
        state: {},
        getters: {},
    };

    beforeEach(() => {
        store_options = {
            state: {
                is_loading: false,
                current_milestones: [],
            },
            getters: {
                has_rest_error: false,
            },
        };
    });

    it("When there are no current milestones, then ReleaseDisplayer Component is not allowed", async () => {
        const wrapper = await getPersonalWidgetInstance(store_options);

        expect(wrapper.find("[data-test=current-milestones-test]").exists()).toBe(false);
    });

    it("When there are some current_milestones, then ReleaseDisplayer Component is displayed", async () => {
        const release1: MilestoneData = {
            label: "release_1",
            id: 1,
        } as MilestoneData;

        const release2: MilestoneData = {
            label: "release_2",
            id: 2,
        } as MilestoneData;

        store_options.state.current_milestones = [release1, release2];
        const wrapper = await getPersonalWidgetInstance(store_options);

        expect(
            wrapper.find("[data-test=current-milestones-test-" + release1.label + "]").exists(),
        ).toBe(true);
        expect(
            wrapper.find("[data-test=current-milestones-test-" + release2.label + "]").exists(),
        ).toBe(true);
    });
});
