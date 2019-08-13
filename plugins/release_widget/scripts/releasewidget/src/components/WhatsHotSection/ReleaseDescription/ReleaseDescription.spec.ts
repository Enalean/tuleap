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
import ReleaseDescription from "./ReleaseDescription.vue";
import { createStoreMock } from "@tuleap-vue-components/store-wrapper";
import Vue from "vue";
import GetTextPlugin from "vue-gettext";
import VueDOMPurifyHTML from "vue-dompurify-html";
import { ComponentOption, MilestoneData, StoreOptions } from "../../../type";

let releaseData: MilestoneData;
const component_options: ComponentOption = {};
const project_id = 102;

describe("ReleaseDescription", () => {
    let store_options: StoreOptions;
    let store;

    function getPersonalWidgetInstance(store_options: StoreOptions) {
        store = createStoreMock(store_options);

        component_options.mocks = { $store: store };

        Vue.use(GetTextPlugin, {
            translations: {},
            silent: true
        });

        Vue.use(VueDOMPurifyHTML);

        return shallowMount(ReleaseDescription, component_options);
    }

    beforeEach(() => {
        store_options = {
            state: {}
        };

        releaseData = {
            id: 2,
            planning: {
                id: "100"
            },
            number_of_artifact_by_trackers: []
        };

        component_options.propsData = {
            releaseData
        };

        getPersonalWidgetInstance(store_options);
    });

    it("Given user display widget, Then a good link to top planning of the release is rendered", () => {
        store_options.state.project_id = project_id;

        const wrapper = getPersonalWidgetInstance(store_options);

        expect(wrapper.find("[data-test=overview-link]").attributes("href")).toEqual(
            "/plugins/agiledashboard/?group_id=" +
                encodeURIComponent(project_id) +
                "&planning_id=" +
                encodeURIComponent(releaseData.planning!.id) +
                "&action=show&aid=" +
                encodeURIComponent(releaseData.id) +
                "&pane=details"
        );
    });
});
