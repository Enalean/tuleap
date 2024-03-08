/*
 * Copyright (c) Enalean, 2020 - present. All Rights Reserved.
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

import type { VueWrapper } from "@vue/test-utils";
import { shallowMount } from "@vue/test-utils";
import ReleaseBadgesOpenSprint from "./ReleaseBadgesOpenSprint.vue";
import type { MilestoneData } from "../../../type";
import ReleaseButtonsDescription from "../ReleaseDescription/ReleaseButtonsDescription.vue";
import { getGlobalTestOptions } from "../../../helpers/global-options-for-test";

const total_sprint = 10;

describe("ReleaseBadgesOpenSprint", () => {
    function getPersonalWidgetInstance(
        start_date: string | null,
        end_date: string | null,
    ): VueWrapper<InstanceType<typeof ReleaseBadgesOpenSprint>> {
        const sprint_data = {
            label: "mile",
            id: 2,
            total_sprint,
            start_date,
            end_date,
            resources: {
                milestones: {
                    accept: {
                        trackers: [
                            {
                                label: "Sprint1",
                            },
                        ],
                    },
                },
            },
        } as MilestoneData;

        return shallowMount(ReleaseBadgesOpenSprint, {
            global: {
                ...getGlobalTestOptions(),
            },
            propsData: {
                sprint_data,
                isPastRelease: false,
            },
        });
    }

    describe("Display sprint data", () => {
        it("When the component is rendered, Then the label of sprint is displayed", () => {
            const wrapper = getPersonalWidgetInstance(null, null);

            expect(wrapper.get("[data-test=sprint-label]").text()).toBe("mile");
        });

        it("When the component is rendered, Then ReleaseBadgesButtonOpenSprint is rendered", () => {
            const wrapper = getPersonalWidgetInstance(null, null);

            expect(wrapper.findComponent(ReleaseButtonsDescription).exists()).toBe(true);
        });

        it("When a release is not in progress, Then the badge is outline", () => {
            const wrapper = getPersonalWidgetInstance(null, null);

            expect(wrapper.get("[data-test=sprint-label]").attributes("class")).toContain(
                "tlp-badge-outline",
            );
        });

        it("When a release is has no end date set, Then the badge is outline", () => {
            const wrapper = getPersonalWidgetInstance(null, new Date().toString());

            expect(wrapper.get("[data-test=sprint-label]").attributes("class")).toContain(
                "tlp-badge-outline",
            );
        });

        it("When a release is has no start date set, Then the badge is outline", () => {
            const wrapper = getPersonalWidgetInstance(new Date().toString(), null);

            expect(wrapper.get("[data-test=sprint-label]").attributes("class")).toContain(
                "tlp-badge-outline",
            );
        });

        it("When a release is in progress, Then the badge is not outline", () => {
            const start_date = new Date();
            const end_date = new Date();
            end_date.setDate(end_date.getDate() + 1);

            const wrapper = getPersonalWidgetInstance(start_date.toString(), end_date.toString());

            expect(wrapper.get("[data-test=sprint-label]").attributes("class")).not.toContain(
                "tlp-badge-outline",
            );
        });

        it("When a release is in past, Then the badge is not outline", () => {
            const start_date = new Date();
            start_date.setDate(1234567890);
            const end_date = new Date();
            end_date.setDate(end_date.getDate() + 1);

            const wrapper = getPersonalWidgetInstance(start_date.toString(), end_date.toString());

            expect(wrapper.get("[data-test=sprint-label]").attributes("class")).toContain(
                "tlp-badge-outline",
            );
        });
    });
});
