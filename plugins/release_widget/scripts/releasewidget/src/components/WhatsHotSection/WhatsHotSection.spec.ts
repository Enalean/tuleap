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

import { shallowMount } from "@vue/test-utils";
import WhatsHotSection from "./WhatsHotSection.vue";
import { createStoreMock } from "@tuleap-vue-components/store-wrapper";
import Vue from "vue";
import GetTextPlugin from "vue-gettext";
import { MilestoneData, StoreOptions } from "../../type";

const project_id = 102;

function getPersonalWidgetInstance(store_options: StoreOptions) {
    const store = createStoreMock(store_options);
    const component_options = {
        propsData: {
            project_id
        },
        mocks: { $store: store }
    };
    Vue.use(GetTextPlugin, {
        translations: {},
        silent: true
    });

    return shallowMount(WhatsHotSection, component_options);
}

describe("What'sHotSection", () => {
    let store_options: StoreOptions = {
        state: {},
        getters: {}
    };

    beforeEach(() => {
        store_options = {
            state: {
                is_loading: false,
                current_milestones: []
            },
            getters: {
                has_rest_error: false
            }
        };

        getPersonalWidgetInstance(store_options);
    });

    it("When there are no current milestones, then ReleaseDisplayer Component is not allowed", () => {
        const wrapper = getPersonalWidgetInstance(store_options);

        expect(wrapper.contains("[data-test=current-milestones-test]")).toBeFalsy();
    });

    it("When there are some current_milestones, then ReleaseDisplayer Component is displayed", () => {
        const release1: MilestoneData = {
            label: "release_1",
            id: 1
        };

        const release2: MilestoneData = {
            label: "release_2",
            id: 2
        };

        store_options.state.current_milestones = [release1, release2];
        const wrapper = getPersonalWidgetInstance(store_options);

        expect(
            wrapper.contains("[data-test=current-milestones-test-" + release1.label + "]")
        ).toBeTruthy();
        expect(
            wrapper.contains("[data-test=current-milestones-test-" + release2.label + "]")
        ).toBeTruthy();
    });
});
