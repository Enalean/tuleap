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

import { shallowMount, ShallowMountOptions, Wrapper } from "@vue/test-utils";
import ReleaseDescription from "./ReleaseDescription.vue";
import { createStoreMock } from "@tuleap-vue-components/store-wrapper-jest";
import Vue from "vue";
import VueDOMPurifyHTML from "vue-dompurify-html";
import { MilestoneData, StoreOptions } from "../../../type";
import { initVueGettext } from "../../../../../../../../src/www/scripts/tuleap/gettext/vue-gettext-init";

let releaseData: MilestoneData & Required<Pick<MilestoneData, "planning">>;
const component_options: ShallowMountOptions<ReleaseDescription> = {};
const project_id = 102;

describe("ReleaseDescription", () => {
    let store_options: StoreOptions;
    let store;

    async function getPersonalWidgetInstance(
        store_options: StoreOptions
    ): Promise<Wrapper<ReleaseDescription>> {
        store = createStoreMock(store_options);

        component_options.mocks = { $store: store };

        await initVueGettext(Vue, () => {
            throw new Error("Fallback to default");
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
    });

    it("Given user display widget, Then a good link to top planning of the release is rendered", async () => {
        store_options.state.project_id = project_id;

        const wrapper = await getPersonalWidgetInstance(store_options);

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
