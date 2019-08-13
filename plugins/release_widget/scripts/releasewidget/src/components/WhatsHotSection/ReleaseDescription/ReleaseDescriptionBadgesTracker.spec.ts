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
import ReleaseDescriptionBadgesTracker from "./ReleaseDescriptionBadgesTracker.vue";
import { createStoreMock } from "@tuleap-vue-components/store-wrapper";
import Vue from "vue";
import GetTextPlugin from "vue-gettext";
import VueDOMPurifyHTML from "vue-dompurify-html";
import { ComponentOption, MilestoneData, StoreOptions } from "../../../type";

let releaseData: MilestoneData;
const component_options: ComponentOption = {};
const project_id = 102;

describe("ReleaseDescriptionBadgesTracker", () => {
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

        return shallowMount(ReleaseDescriptionBadgesTracker, component_options);
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
            number_of_artifact_by_trackers: [
                {
                    label: "Bug",
                    id: 1,
                    total_artifact: 2,
                    color_name: "red-fiesta"
                }
            ]
        };

        component_options.propsData = {
            releaseData
        };

        getPersonalWidgetInstance(store_options);
    });

    it("Given user display widget, Then the good number of artifacts and good color of the tracker is rendered", () => {
        store_options.state.project_id = project_id;

        const wrapper = getPersonalWidgetInstance(store_options);

        expect(wrapper.find("[data-test=color-name-tracker").classes()).toEqual([
            "release-number-artifacts-tracker",
            "release-number-artifacts-tracker-red-fiesta"
        ]);

        expect(wrapper.find("[data-test=total-artifact-tracker").text()).toEqual("2");

        expect(wrapper.find("[data-test=artifact-tracker-name").text()).toEqual("Bug");
    });
});
