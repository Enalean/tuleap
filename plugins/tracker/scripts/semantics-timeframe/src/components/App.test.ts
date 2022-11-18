/*
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

import App from "./App.vue";
import { createSemanticTimeframeAdminLocalVue } from "../helpers/local-vue-for-tests";
import TimeframeConfigModeSelector from "./TimeframeConfigModeSelector.vue";
import TimeframeBasedOnFieldsConfig from "./TimeframeBasedOnFieldsConfig.vue";
import TimeframeImpliedFromAnotherTrackerConfig from "./TimeframeImpliedFromAnotherTrackerConfig.vue";
import { MODE_BASED_ON_TRACKER_FIELDS, MODE_IMPLIED_FROM_ANOTHER_TRACKER } from "../constants";

describe("App", () => {
    async function getWrapper(): Promise<Wrapper<App>> {
        return shallowMount(App, {
            localVue: await createSemanticTimeframeAdminLocalVue(),
            propsData: {
                usable_date_fields: [],
                usable_numeric_fields: [],
                suitable_trackers: [],
                start_date_field_id: "",
                end_date_field_id: "",
                duration_field_id: "",
                implied_from_tracker_id: "",
                current_tracker_id: "",
                target_url: "",
                csrf_token: "",
                has_other_trackers_implying_their_timeframes: false,
                has_tracker_charts: false,
                has_artifact_link_field: true,
                semantic_presentation: "",
            },
        });
    }

    it("should display the right configuration section according to the selected mode", async () => {
        const wrapper = await getWrapper();
        const mode_selector = wrapper.getComponent(TimeframeConfigModeSelector);

        expect(wrapper.findComponent(TimeframeBasedOnFieldsConfig).exists()).toBe(true);
        expect(wrapper.findComponent(TimeframeImpliedFromAnotherTrackerConfig).exists()).toBe(
            false
        );

        mode_selector.vm.$emit("timeframe-mode-selected", MODE_IMPLIED_FROM_ANOTHER_TRACKER);

        await wrapper.vm.$nextTick();

        expect(wrapper.findComponent(TimeframeBasedOnFieldsConfig).exists()).toBe(false);
        expect(wrapper.findComponent(TimeframeImpliedFromAnotherTrackerConfig).exists()).toBe(true);

        mode_selector.vm.$emit("timeframe-mode-selected", MODE_BASED_ON_TRACKER_FIELDS);

        await wrapper.vm.$nextTick();

        expect(wrapper.findComponent(TimeframeBasedOnFieldsConfig).exists()).toBe(true);
        expect(wrapper.findComponent(TimeframeImpliedFromAnotherTrackerConfig).exists()).toBe(
            false
        );
    });
});
