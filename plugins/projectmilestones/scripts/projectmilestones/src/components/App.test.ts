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

import type { ShallowMountOptions, Wrapper } from "@vue/test-utils";
import { shallowMount } from "@vue/test-utils";
import App from "./App.vue";
import { createStoreMock } from "@tuleap/vuex-store-wrapper-jest";
import type { MilestoneData, StoreOptions } from "../type";
import { createReleaseWidgetLocalVue } from "../helpers/local-vue-for-test";
import RoadmapEmptyStateSection from "./ProjectMilestonesEmpty/RoadmapEmptyStateSection.vue";
import PastSection from "./PastSection/PastSection.vue";
import WhatsHotSection from "./WhatsHotSection/WhatsHotSection.vue";
import RoadmapSection from "./RoadmapSection/RoadmapSection.vue";

const project_id = 102;
const component_options: ShallowMountOptions<App> = {};

async function getPersonalWidgetInstance(store_options: StoreOptions): Promise<Wrapper<App>> {
    const store = createStoreMock(store_options);

    component_options.mocks = { $store: store };
    component_options.localVue = await createReleaseWidgetLocalVue();

    return shallowMount(App, component_options);
}

describe("Given a release widget", () => {
    let store_options: StoreOptions & Required<Pick<StoreOptions, "getters">>;
    beforeEach(() => {
        component_options.propsData = {
            project_id,
            is_browser_IE11: false,
        };

        store_options = {
            state: {
                is_loading: false,
                current_milestones: [],
                nb_backlog_items: 0,
                nb_past_releases: 0,
                nb_upcoming_releases: 0,
            },
            getters: {
                has_rest_error: false,
            },
        };
    });

    it("When there are no errors, then the widget content will be displayed", async () => {
        const wrapper = await getPersonalWidgetInstance(store_options);

        expect(wrapper.find("[data-test=widget-content-project-milestones]").exists()).toBe(true);
        expect(wrapper.find("[data-test=show-error-message]").exists()).toBe(false);
        expect(wrapper.find("[data-test=is-loading]").exists()).toBe(false);
    });

    it("When there is an error, then the widget content will not be displayed", async () => {
        store_options.state.error_message = "404 Error";
        store_options.getters.has_rest_error = true;
        const wrapper = await getPersonalWidgetInstance(store_options);

        expect(wrapper.find("[data-test=show-error-message]").exists()).toBe(true);
        expect(wrapper.find("[data-test=widget-content-project-milestones]").exists()).toBe(false);
        expect(wrapper.find("[data-test=is-loading]").exists()).toBe(false);
    });

    it("When it is loading rest data, then a loader will be displayed", async () => {
        store_options.state.is_loading = true;
        const wrapper = await getPersonalWidgetInstance(store_options);

        expect(wrapper.find("[data-test=is-loading]").exists()).toBe(true);
        expect(wrapper.find("[data-test=widget-content-project-milestones]").exists()).toBe(false);
        expect(wrapper.find("[data-test=show-error-message]").exists()).toBe(false);
    });

    it("When there is a rest error and it is empty, Then another message is displayed", async () => {
        store_options.state.error_message = "";
        store_options.getters.has_rest_error = true;

        const wrapper = await getPersonalWidgetInstance(store_options);
        expect(wrapper.get("[data-test=show-error-message]").text()).toBe(
            "Oops, an error occurred!",
        );
    });

    it("When there is an empty widget, Then only RoadmapEmptyStateSection component is renderer", async () => {
        const wrapper = await getPersonalWidgetInstance(store_options);

        expect(wrapper.find("[data-test=widget-content-project-milestones]").exists()).toBe(true);
        expect(wrapper.findComponent(RoadmapEmptyStateSection).exists()).toBe(true);
        expect(wrapper.findComponent(WhatsHotSection).exists()).toBe(false);
        expect(wrapper.findComponent(RoadmapSection).exists()).toBe(false);
        expect(wrapper.findComponent(PastSection).exists()).toBe(false);
    });

    it("When there is at least one backlog item, Then RoadmapSection is displayed", async () => {
        store_options.state.nb_backlog_items = 2;
        const wrapper = await getPersonalWidgetInstance(store_options);

        expect(wrapper.find("[data-test=widget-content-project-milestones]").exists()).toBe(true);
        expect(wrapper.findComponent(RoadmapEmptyStateSection).exists()).toBe(false);
        expect(wrapper.findComponent(WhatsHotSection).exists()).toBe(true);
        expect(wrapper.findComponent(RoadmapSection).exists()).toBe(true);
        expect(wrapper.findComponent(PastSection).exists()).toBe(true);
    });

    it("When there is at least one upcoming, Then RoadmapSection is displayed", async () => {
        store_options.state.nb_upcoming_releases = 2;
        const wrapper = await getPersonalWidgetInstance(store_options);

        expect(wrapper.find("[data-test=widget-content-project-milestones]").exists()).toBe(true);
        expect(wrapper.findComponent(RoadmapEmptyStateSection).exists()).toBe(false);
        expect(wrapper.findComponent(WhatsHotSection).exists()).toBe(true);
        expect(wrapper.findComponent(RoadmapSection).exists()).toBe(true);
        expect(wrapper.findComponent(PastSection).exists()).toBe(true);
    });

    it("When there is at least one past release, Then RoadmapSection is displayed", async () => {
        store_options.state.nb_past_releases = 2;
        const wrapper = await getPersonalWidgetInstance(store_options);

        expect(wrapper.find("[data-test=widget-content-project-milestones]").exists()).toBe(true);
        expect(wrapper.findComponent(RoadmapEmptyStateSection).exists()).toBe(false);
        expect(wrapper.findComponent(WhatsHotSection).exists()).toBe(true);
        expect(wrapper.findComponent(RoadmapSection).exists()).toBe(true);
        expect(wrapper.findComponent(PastSection).exists()).toBe(true);
    });

    it("When there is at least one current milestone, Then RoadmapSection is displayed", async () => {
        store_options.state.current_milestones = [
            {
                id: 101,
            } as MilestoneData,
        ];
        const wrapper = await getPersonalWidgetInstance(store_options);

        expect(wrapper.find("[data-test=widget-content-project-milestones]").exists()).toBe(true);
        expect(wrapper.findComponent(RoadmapEmptyStateSection).exists()).toBe(false);
        expect(wrapper.findComponent(WhatsHotSection).exists()).toBe(true);
        expect(wrapper.findComponent(RoadmapSection).exists()).toBe(true);
        expect(wrapper.findComponent(PastSection).exists()).toBe(true);
    });

    it("When there are backlog items, upcoming releases, current milestones and past releases, Then RoadmapSection is displayed", async () => {
        store_options.state.nb_backlog_items = 2;
        store_options.state.nb_upcoming_releases = 2;
        store_options.state.current_milestones = [
            {
                id: 101,
            } as MilestoneData,
        ];
        store_options.state.nb_past_releases = 2;

        const wrapper = await getPersonalWidgetInstance(store_options);

        expect(wrapper.find("[data-test=widget-content-project-milestones]").exists()).toBe(true);
        expect(wrapper.findComponent(RoadmapEmptyStateSection).exists()).toBe(false);
        expect(wrapper.findComponent(WhatsHotSection).exists()).toBe(true);
        expect(wrapper.findComponent(RoadmapSection).exists()).toBe(true);
        expect(wrapper.findComponent(PastSection).exists()).toBe(true);
    });
});
