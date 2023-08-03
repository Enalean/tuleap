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
import ReleaseHeader from "./ReleaseHeader.vue";
import type { MilestoneData, Pane } from "../../../type";
import { setUserLocale } from "../../../helpers/user-locale-helper";
import { createReleaseWidgetLocalVue } from "../../../helpers/local-vue-for-test";
import ReleaseHeaderRemainingDays from "./ReleaseHeaderRemainingDays.vue";
import ReleaseHeaderRemainingPoints from "./ReleaseHeaderRemainingPoints.vue";
import PastReleaseHeaderInitialPoints from "./PastReleaseHeaderInitialPoints.vue";
import PastReleaseHeaderTestsDisplayer from "./PastReleaseHeaderTestsDisplayer.vue";

let release_data: MilestoneData;
let component_options: ShallowMountOptions<ReleaseHeader>;

describe("ReleaseHeader", () => {
    async function getPersonalWidgetInstance(): Promise<Wrapper<ReleaseHeader>> {
        component_options.localVue = await createReleaseWidgetLocalVue();

        return shallowMount(ReleaseHeader, component_options);
    }

    beforeEach(() => {
        release_data = {
            label: "mile",
            id: 2,
            start_date: new Date("2017-01-22T13:42:08+02:00").toDateString(),
            end_date: new Date("2019-10-05T13:42:08+02:00").toDateString(),
            resources: {
                additional_panes: [] as Pane[],
            },
        } as MilestoneData;

        component_options = {
            propsData: {
                release_data,
            },
        };
    });

    describe("Display arrow between dates", () => {
        it("When there are a start date and end date, Then an arrow is displayed", async () => {
            setUserLocale("en-US");

            const wrapper = await getPersonalWidgetInstance();

            expect(wrapper.find("[data-test=display-arrow]").exists()).toBe(true);
        });

        it("When there isn't a start date of a release, Then there isn't an arrow", async () => {
            release_data = {
                id: 2,
                start_date: null,
            } as MilestoneData;

            component_options.propsData = {
                release_data,
            };

            const wrapper = await getPersonalWidgetInstance();
            expect(wrapper.find("[data-test=display-arrow]").exists()).toBe(false);
        });
    });

    it("When the widget is loading, Then there is a skeleton instead of points", async () => {
        release_data = {
            id: 2,
        } as MilestoneData;

        component_options.propsData = {
            release_data,
            isLoading: true,
        };

        const wrapper = await getPersonalWidgetInstance();
        expect(wrapper.find("[data-test=display-skeleton]").exists()).toBe(true);
    });

    it("When release's title contains '>', Then '>' is displayed", async () => {
        release_data = {
            label: "1 > 2",
            id: 2,
        } as MilestoneData;

        component_options.propsData = {
            release_data,
            isLoading: true,
        };

        const wrapper = await getPersonalWidgetInstance();
        expect(wrapper.get("[data-test=title-release]").text()).toBe("1 > 2");
    });

    describe("Display PastReleaseHeader", () => {
        it("When the release is not past, Then ReleaseHeaderRemaining components are displayed", async () => {
            component_options.propsData = {
                release_data,
                isPastRelease: false,
            };

            const wrapper = await getPersonalWidgetInstance();
            expect(wrapper.findComponent(ReleaseHeaderRemainingDays).exists()).toBe(true);
            expect(wrapper.findComponent(ReleaseHeaderRemainingPoints).exists()).toBe(true);
            expect(wrapper.findComponent(PastReleaseHeaderInitialPoints).exists()).toBe(false);
        });

        it("When the release is past, Then PastReleaseHeaderInitialPoints component are displayed", async () => {
            component_options.propsData = {
                release_data,
                isPastRelease: true,
            };

            const wrapper = await getPersonalWidgetInstance();
            expect(wrapper.findComponent(ReleaseHeaderRemainingDays).exists()).toBe(false);
            expect(wrapper.findComponent(ReleaseHeaderRemainingPoints).exists()).toBe(false);
            expect(wrapper.findComponent(PastReleaseHeaderInitialPoints).exists()).toBe(true);
        });

        it("When the release is past and TestPlan is enabled, Then PastReleaseHeaderTestsDisplayer component are displayed", async () => {
            release_data.resources.additional_panes = [
                {
                    icon_name: "fa-check",
                    identifier: "testplan",
                    title: "Tests",
                    uri: "testplan/project/2",
                },
            ];

            component_options.propsData = {
                release_data,
                isPastRelease: true,
            };

            const wrapper = await getPersonalWidgetInstance();
            expect(wrapper.findComponent(ReleaseHeaderRemainingDays).exists()).toBe(false);
            expect(wrapper.findComponent(ReleaseHeaderRemainingPoints).exists()).toBe(false);
            expect(wrapper.findComponent(PastReleaseHeaderInitialPoints).exists()).toBe(true);
            expect(wrapper.findComponent(PastReleaseHeaderTestsDisplayer).exists()).toBe(true);
        });
    });
});
