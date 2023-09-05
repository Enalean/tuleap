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
import ReleaseHeaderRemainingDays from "./ReleaseHeaderRemainingDays.vue";
import { createStoreMock } from "@tuleap/vuex-store-wrapper-jest";
import type { MilestoneData, StoreOptions } from "../../../type";
import { createReleaseWidgetLocalVue } from "../../../helpers/local-vue-for-test";

let release_data: MilestoneData;
let component_options: ShallowMountOptions<ReleaseHeaderRemainingDays>;

describe("ReleaseHeaderRemainingDays", () => {
    let store_options: StoreOptions;
    let store;

    async function getPersonalWidgetInstance(
        store_options: StoreOptions,
    ): Promise<Wrapper<ReleaseHeaderRemainingDays>> {
        store = createStoreMock(store_options);

        component_options.mocks = { $store: store };
        component_options.localVue = await createReleaseWidgetLocalVue();

        return shallowMount(ReleaseHeaderRemainingDays, component_options);
    }

    beforeEach(() => {
        store_options = {
            state: {},
        };

        component_options = {
            propsData: {
                release_data,
            },
        };
    });

    describe("Display remaining days", () => {
        it("When there is number of start days but equal at 0, Then number days of end is displayed and percent in tooltip", async () => {
            release_data = {
                id: 2,
                number_days_until_end: 10,
                number_days_since_start: 0,
            } as MilestoneData;

            component_options.propsData = {
                release_data,
            };

            const wrapper = await getPersonalWidgetInstance(store_options);

            const remaining_day_text = wrapper.get("[data-test=display-remaining-day-text]");
            const remaining_day_value = wrapper.get("[data-test=display-remaining-day-value]");

            expect(wrapper.attributes("data-tlp-tooltip")).toBe("0.00%");
            expect(remaining_day_text.classes()).toContain("release-remaining-value-success");
            expect(remaining_day_value.classes()).toContain(
                "release-remaining-progress-value-success",
            );
            expect(remaining_day_text.text()).toBe("10");
        });

        it("When there number of start days but null, Then 0 is displayed and a message in tooltip", async () => {
            release_data = {
                id: 2,
                number_days_since_start: null,
            } as MilestoneData;

            component_options.propsData = {
                release_data,
            };

            const wrapper = await getPersonalWidgetInstance(store_options);

            const remaining_day_text = wrapper.get("[data-test=display-remaining-day-text]");
            const remaining_day_value = wrapper.get("[data-test=display-remaining-day-value]");

            expect(wrapper.attributes("data-tlp-tooltip")).toBe("No start date defined.");
            expect(remaining_day_text.classes()).toContain("release-remaining-value-disabled");
            expect(remaining_day_value.classes()).toContain(
                "release-remaining-progress-value-disabled",
            );
            expect(remaining_day_text.text()).toBe("0");
        });

        it("When there is negative number of start days, Then 0 is displayed and 0.00% in tooltip", async () => {
            release_data = {
                id: 2,
                number_days_until_end: -10,
                number_days_since_start: -10,
            } as MilestoneData;

            component_options.propsData = {
                release_data,
            };

            const wrapper = await getPersonalWidgetInstance(store_options);

            const remaining_day_text = wrapper.get("[data-test=display-remaining-day-text]");
            const remaining_day_value = wrapper.get("[data-test=display-remaining-day-value]");

            expect(wrapper.attributes("data-tlp-tooltip")).toBe("0.00%");
            expect(wrapper.classes()).not.toContain("release-remaining-value-disabled");
            expect(remaining_day_text.classes()).not.toContain("release-remaining-value-danger");
            expect(remaining_day_value.classes()).not.toContain(
                "release-remaining-progress-value-danger",
            );
            expect(remaining_day_text.text()).toBe("0");
        });

        it("When there is negative remaining days, Then 0 is displayed and 100% in tooltip", async () => {
            release_data = {
                id: 2,
                number_days_until_end: -10,
                number_days_since_start: 10,
            } as MilestoneData;

            component_options.propsData = {
                release_data,
            };

            const wrapper = await getPersonalWidgetInstance(store_options);

            const remaining_day_text = wrapper.get("[data-test=display-remaining-day-text]");
            const remaining_day_value = wrapper.get("[data-test=display-remaining-day-value]");

            expect(wrapper.attributes("data-tlp-tooltip")).toBe("100.00%");
            expect(wrapper.classes()).not.toContain("release-remaining-value-disabled");
            expect(remaining_day_text.classes()).not.toContain("release-remaining-value-danger");
            expect(remaining_day_value.classes()).not.toContain(
                "release-remaining-progress-value-danger",
            );
            expect(remaining_day_text.text()).toBe("0");
        });

        it("When there is remaining days but equal at 0, Then remaining days is displayed and percent in tooltip", async () => {
            release_data = {
                id: 2,
                number_days_until_end: 0,
                number_days_since_start: 10,
            } as MilestoneData;

            component_options.propsData = {
                release_data,
            };

            const wrapper = await getPersonalWidgetInstance(store_options);

            const remaining_day_text = wrapper.get("[data-test=display-remaining-day-text]");
            const remaining_day_value = wrapper.get("[data-test=display-remaining-day-value]");

            expect(wrapper.attributes("data-tlp-tooltip")).toBe("100.00%");
            expect(wrapper.classes()).not.toContain("release-remaining-value-danger");
            expect(wrapper.classes()).not.toContain("release-remaining-value-success");
            expect(remaining_day_value.classes()).not.toContain(
                "release-remaining-progress-value-danger",
            );
            expect(remaining_day_value.classes()).not.toContain(
                "release-remaining-progress-value-success",
            );
            expect(remaining_day_text.text()).toBe("0");
        });

        it("When there is remaining days and is null, Then 0 is displayed and there is a message in tooltip", async () => {
            release_data = {
                id: 2,
                number_days_since_start: 10,
                number_days_until_end: null,
            } as MilestoneData;

            component_options.propsData = {
                release_data,
            };

            const wrapper = await getPersonalWidgetInstance(store_options);

            const remaining_day_text = wrapper.get("[data-test=display-remaining-day-text]");
            const remaining_day_value = wrapper.get("[data-test=display-remaining-day-value]");

            expect(wrapper.attributes("data-tlp-tooltip")).toBe("No end date defined.");
            expect(remaining_day_text.classes()).toContain("release-remaining-value-disabled");
            expect(remaining_day_value.classes()).toContain(
                "release-remaining-progress-value-disabled",
            );
            expect(remaining_day_text.text()).toBe("0");
        });

        it("When there is remaining days, not null and greater than 0, Then remaining days is displayed and percent in tooltip", async () => {
            release_data = {
                id: 2,
                number_days_until_end: 5,
                number_days_since_start: 5,
            } as MilestoneData;

            component_options.propsData = {
                release_data,
            };

            const wrapper = await getPersonalWidgetInstance(store_options);

            const remaining_day_text = wrapper.get("[data-test=display-remaining-day-text]");
            const remaining_day_value = wrapper.get("[data-test=display-remaining-day-value]");

            expect(wrapper.attributes("data-tlp-tooltip")).toBe("50.00%");
            expect(remaining_day_text.classes()).toContain("release-remaining-value-success");
            expect(remaining_day_value.classes()).toContain(
                "release-remaining-progress-value-success",
            );
            expect(remaining_day_text.text()).toBe("5");
        });

        it("When the progress is at least 80%, Then remaining days is displayed in red and percent in tooltip", async () => {
            release_data = {
                id: 2,
                number_days_until_end: 2,
                number_days_since_start: 8,
            } as MilestoneData;

            component_options.propsData = {
                release_data,
            };

            const wrapper = await getPersonalWidgetInstance(store_options);

            const remaining_day_text = wrapper.get("[data-test=display-remaining-day-text]");
            const remaining_day_value = wrapper.get("[data-test=display-remaining-day-value]");

            expect(wrapper.attributes("data-tlp-tooltip")).toBe("80.00%");
            expect(remaining_day_text.classes()).toContain("release-remaining-value-danger");
            expect(remaining_day_value.classes()).toContain(
                "release-remaining-progress-value-danger",
            );
            expect(remaining_day_text.text()).toBe("2");
        });

        it("When the progress is between 80% and 100%, Then remaining days is displayed in red and percent in tooltip", async () => {
            release_data = {
                id: 2,
                number_days_until_end: 1,
                number_days_since_start: 10,
            } as MilestoneData;

            component_options.propsData = {
                release_data,
            };

            const wrapper = await getPersonalWidgetInstance(store_options);

            const remaining_day_text = wrapper.get("[data-test=display-remaining-day-text]");
            const remaining_day_value = wrapper.get("[data-test=display-remaining-day-value]");

            expect(wrapper.attributes("data-tlp-tooltip")).toBe("90.91%");
            expect(remaining_day_text.classes()).toContain("release-remaining-value-danger");
            expect(remaining_day_value.classes()).toContain(
                "release-remaining-progress-value-danger",
            );
            expect(remaining_day_text.text()).toBe("1");
        });
    });
});
