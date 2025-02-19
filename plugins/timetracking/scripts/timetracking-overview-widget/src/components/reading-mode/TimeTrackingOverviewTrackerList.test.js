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

import { describe, it, expect } from "@jest/globals";
import { createTestingPinia } from "@pinia/testing";
import { shallowMount } from "@vue/test-utils";
import TimeTrackingOverviewTrackerList from "./TimeTrackingOverviewTrackerList.vue";
import { getGlobalTestOptions } from "../../../tests/helpers/global-options-for-tests";
import { defineStore } from "pinia";

describe("TimeTrackingOverviewTrackerList tests", () => {
    describe("Given a timetracking overview widget on reading mode", () => {
        const getWrapper = (selected_trackers) => {
            const useStore = defineStore("overview/1", {
                state: () => ({
                    selected_trackers,
                }),
            });

            const pinia = createTestingPinia();
            useStore(pinia);

            return shallowMount(TimeTrackingOverviewTrackerList, {
                global: getGlobalTestOptions(pinia),
            });
        };

        it("When no selected trackers, then 'no trackers selected' is displayed", async () => {
            const wrapper = await getWrapper([]);
            expect(
                wrapper
                    .find("[data-test=timetracking-overview-reading-mode-trackers-empty]")
                    .exists(),
            ).toBe(true);
        });

        it("When trackers are selected, then empty field is not displayed", () => {
            const selected_trackers = [
                {
                    artifacts: [
                        {
                            minutes: 20,
                        },
                        {
                            minutes: 40,
                        },
                    ],
                    id: "16",
                    label: "tracker",
                    project: {},
                    uri: "",
                },
                {
                    artifacts: [
                        {
                            minutes: 20,
                        },
                    ],
                    id: "18",
                    label: "tracker 2",
                    project: {},
                    uri: "",
                },
            ];

            const wrapper = getWrapper(selected_trackers);
            expect(
                wrapper
                    .find("[data-test=timetracking-overview-reading-mode-trackers-empty]")
                    .exists(),
            ).toBe(false);
        });
    });
});
