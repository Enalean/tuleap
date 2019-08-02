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

import Vue from "vue";
import GetTextPlugin from "vue-gettext";
import { shallowMount } from "@vue/test-utils";
import ReleaseHeaderRemainingPoints from "./ReleaseHeaderRemainingPoints.vue";
import { createStoreMock } from "@tuleap-vue-components/store-wrapper.js";

let releaseData = {};
let component_options = {};

describe("ReleaseHeaderRemainingPoints", () => {
    let store_options;
    let store;

    function getPersonalWidgetInstance(store_options) {
        store = createStoreMock(store_options);

        component_options.mocks = { $store: store };

        Vue.use(GetTextPlugin, {
            translations: {},
            silent: true
        });

        return shallowMount(ReleaseHeaderRemainingPoints, component_options);
    }

    beforeEach(() => {
        store_options = {
            state: {}
        };

        releaseData = {
            label: "mile",
            id: 2,
            start_date: Date("2017-01-22T13:42:08+02:00"),
            capacity: 10
        };

        component_options = {
            propsData: {
                releaseData
            }
        };

        getPersonalWidgetInstance(store_options);
    });

    describe("Display remaining points", () => {
        it("When there is negative remaining points, Then it displays and percent in tooltip", () => {
            releaseData = {
                label: "mile",
                id: 2,
                planning: {
                    id: 100
                },
                start_date: null,
                remaining_effort: -1,
                initial_effort: 10
            };

            component_options.propsData = {
                releaseData
            };

            const wrapper = getPersonalWidgetInstance(store_options);

            const tooltip = wrapper.find("[data-test=display-remaining-points-tooltip]");
            const remaining_point_text = wrapper.find("[data-test=display-remaining-points-text]");
            const remaining_point_value = wrapper.find(
                "[data-test=display-remaining-points-value]"
            );

            expect(tooltip.attributes("data-tlp-tooltip")).toEqual("110.00%");
            expect(tooltip.classes()).not.toContain("release-remaining-value-success");
            expect(remaining_point_text.classes()).not.toContain(
                "release-remaining-value-disabled"
            );
            expect(remaining_point_value.classes()).not.toContain(
                "release-remaining-progress-value-disabled"
            );

            expect(remaining_point_text.text()).toEqual("-1");
        });

        it("When there isn't remaining effort points, Then 0 is displayed and message in tooltip", () => {
            releaseData = {
                label: "mile",
                id: 2,
                planning: {
                    id: 100
                },
                start_date: null,
                initial_effort: 10
            };

            component_options.propsData = {
                releaseData
            };

            const wrapper = getPersonalWidgetInstance(store_options);

            const tooltip = wrapper.find("[data-test=display-remaining-points-tooltip]");
            const remaining_point_text = wrapper.find("[data-test=display-remaining-points-text]");
            const remaining_point_value = wrapper.find(
                "[data-test=display-remaining-points-value]"
            );

            expect(tooltip.attributes("data-tlp-tooltip")).toEqual("No remaining effort defined.");
            expect(remaining_point_text.classes()).toContain("release-remaining-value-disabled");
            expect(remaining_point_value.classes()).toContain(
                "release-remaining-progress-value-disabled"
            );
            expect(remaining_point_text.text()).toEqual("0");
        });

        it("When there is remaining effort point and is null, Then 0 is displayed and message in tooltip", () => {
            releaseData = {
                label: "mile",
                id: 2,
                planning: {
                    id: 100
                },
                start_date: null,
                remaining_effort: null,
                initial_effort: 10
            };

            component_options.propsData = {
                releaseData
            };

            const wrapper = getPersonalWidgetInstance(store_options);

            const tooltip = wrapper.find("[data-test=display-remaining-points-tooltip]");
            const remaining_point_text = wrapper.find("[data-test=display-remaining-points-text]");
            const remaining_point_value = wrapper.find(
                "[data-test=display-remaining-points-value]"
            );

            expect(tooltip.attributes("data-tlp-tooltip")).toEqual("No remaining effort defined.");
            expect(remaining_point_text.classes()).toContain("release-remaining-value-disabled");
            expect(remaining_point_value.classes()).toContain(
                "release-remaining-progress-value-disabled"
            );
            expect(remaining_point_text.text()).toEqual("0");
        });

        it("When there is remaining effort point, not null and greater than 0, Then it's displayed and percent in tooltip", () => {
            releaseData = {
                label: "mile",
                id: 2,
                planning: {
                    id: 100
                },
                start_date: null,
                remaining_effort: 5,
                initial_effort: 10
            };

            component_options.propsData = {
                releaseData
            };

            const wrapper = getPersonalWidgetInstance(store_options);

            const tooltip = wrapper.find("[data-test=display-remaining-points-tooltip]");
            const remaining_point_text = wrapper.find("[data-test=display-remaining-points-text]");
            const remaining_point_value = wrapper.find(
                "[data-test=display-remaining-points-value]"
            );

            expect(tooltip.attributes("data-tlp-tooltip")).toEqual("50.00%");
            expect(remaining_point_text.classes()).toContain("release-remaining-value-success");
            expect(remaining_point_value.classes()).toContain(
                "release-remaining-progress-value-success"
            );
            expect(remaining_point_text.text()).toEqual("5");
        });

        it("When there is remaining effort point, equal at 0, Then it's displayed and percent in tooltip", () => {
            releaseData = {
                label: "mile",
                id: 2,
                planning: {
                    id: 100
                },
                start_date: null,
                remaining_effort: 0,
                initial_effort: 5
            };

            component_options.propsData = {
                releaseData
            };

            const wrapper = getPersonalWidgetInstance(store_options);

            const tooltip = wrapper.find("[data-test=display-remaining-points-tooltip]");
            const remaining_point_text = wrapper.find("[data-test=display-remaining-points-text]");
            const remaining_point_value = wrapper.find(
                "[data-test=display-remaining-points-value]"
            );

            expect(tooltip.attributes("data-tlp-tooltip")).toEqual("100.00%");
            expect(remaining_point_text.classes()).not.toContain("release-remaining-value-success");
            expect(remaining_point_text.classes()).not.toContain(
                "release-remaining-value-disabled"
            );
            expect(remaining_point_value.classes()).not.toContain(
                "release-remaining-progress-value-disabled"
            );
            expect(remaining_point_text.text()).toEqual("0");
        });

        it("When there isn't initial effort point, Then 0 is displayed and message in tooltip", () => {
            releaseData = {
                label: "mile",
                id: 2,
                planning: {
                    id: 100
                },
                start_date: null,
                remaining_effort: 5
            };

            component_options.propsData = {
                releaseData
            };

            const wrapper = getPersonalWidgetInstance(store_options);

            const tooltip = wrapper.find("[data-test=display-remaining-points-tooltip]");
            const remaining_point_text = wrapper.find("[data-test=display-remaining-points-text]");
            const remaining_point_value = wrapper.find(
                "[data-test=display-remaining-points-value]"
            );

            expect(tooltip.attributes("data-tlp-tooltip")).toEqual("No initial effort defined.");
            expect(remaining_point_text.classes()).toContain("release-remaining-value-disabled");
            expect(remaining_point_value.classes()).toContain(
                "release-remaining-progress-value-disabled"
            );
            expect(remaining_point_text.text()).toEqual("5");
        });

        it("When there is initial effort point but null, Then 0 is displayed and message in tooltip", () => {
            releaseData = {
                label: "mile",
                id: 2,
                planning: {
                    id: 100
                },
                start_date: null,
                remaining_effort: 5,
                initial_effort: null
            };

            component_options.propsData = {
                releaseData
            };

            const wrapper = getPersonalWidgetInstance(store_options);

            const tooltip = wrapper.find("[data-test=display-remaining-points-tooltip]");
            const remaining_point_text = wrapper.find("[data-test=display-remaining-points-text]");
            const remaining_point_value = wrapper.find(
                "[data-test=display-remaining-points-value]"
            );

            expect(tooltip.attributes("data-tlp-tooltip")).toEqual("No initial effort defined.");
            expect(remaining_point_text.classes()).toContain("release-remaining-value-disabled");
            expect(remaining_point_value.classes()).toContain(
                "release-remaining-progress-value-disabled"
            );
            expect(remaining_point_text.text()).toEqual("5");
        });

        it("When there is initial effort point but equal at 0, Then 0 is displayed and message in tooltip", () => {
            releaseData = {
                label: "mile",
                id: 2,
                planning: {
                    id: 100
                },
                start_date: null,
                remaining_effort: 5,
                initial_effort: 0
            };

            component_options.propsData = {
                releaseData
            };

            const wrapper = getPersonalWidgetInstance(store_options);

            const tooltip = wrapper.find("[data-test=display-remaining-points-tooltip]");
            const remaining_point_text = wrapper.find("[data-test=display-remaining-points-text]");
            const remaining_point_value = wrapper.find(
                "[data-test=display-remaining-points-value]"
            );

            expect(tooltip.attributes("data-tlp-tooltip")).toEqual("Initial effort equal at 0.");
            expect(remaining_point_text.classes()).toContain("release-remaining-value-disabled");
            expect(remaining_point_value.classes()).toContain(
                "release-remaining-progress-value-disabled"
            );
            expect(remaining_point_text.text()).toEqual("5");
        });

        it("When remaining effort > initial effort, Then remaining effort is displayed and message in tooltip", () => {
            releaseData = {
                label: "mile",
                id: 2,
                planning: {
                    id: 100
                },
                start_date: null,
                remaining_effort: 100,
                initial_effort: 10
            };

            component_options.propsData = {
                releaseData
            };

            const wrapper = getPersonalWidgetInstance(store_options);

            const tooltip = wrapper.find("[data-test=display-remaining-points-tooltip]");
            const remaining_point_text = wrapper.find("[data-test=display-remaining-points-text]");
            const remaining_point_value = wrapper.find(
                "[data-test=display-remaining-points-value]"
            );

            expect(tooltip.attributes("data-tlp-tooltip")).toEqual(
                "Initial effort (10) should be bigger or equal to remaining effort (100)."
            );
            expect(remaining_point_text.classes()).toContain("release-remaining-value-disabled");
            expect(remaining_point_value.classes()).toContain(
                "release-remaining-progress-value-disabled"
            );
            expect(remaining_point_text.text()).toEqual("100");
        });
    });
});
