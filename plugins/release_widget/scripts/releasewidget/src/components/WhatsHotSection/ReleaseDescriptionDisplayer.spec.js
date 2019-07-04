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
import ReleaseDescriptionDisplayer from "./ReleaseDescriptionDisplayer.vue";
import { createStoreMock } from "@tuleap-vue-components/store-wrapper.js";
import Vue from "vue";
import GetTextPlugin from "vue-gettext";
import VueDOMPurifyHTML from "vue-dompurify-html";

let releaseData = {};
let component_options = {};
const project_id = "102";

describe("ReleaseDescriptionDisplayer", () => {
    let store_options;
    let store;

    function getPersonalWidgetInstance(store_options) {
        store = createStoreMock(store_options);

        component_options.mocks = { $store: store };

        Vue.use(GetTextPlugin, {
            translations: {},
            silent: true
        });

        Vue.use(VueDOMPurifyHTML);

        return shallowMount(ReleaseDescriptionDisplayer, component_options);
    }

    beforeEach(() => {
        store_options = {
            state: {}
        };

        releaseData = {
            id: 2,
            planning: {
                id: 100
            }
        };

        component_options = {
            propsData: {
                releaseData
            }
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
                encodeURIComponent(releaseData.planning.id) +
                "&action=show&aid=" +
                encodeURIComponent(releaseData.id) +
                "&pane=details"
        );
    });
});
