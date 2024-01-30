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
import TimeTrackingOverviewTrackerList from "./TimeTrackingOverviewTrackerList.vue";
import { createStoreMock } from "@tuleap/vuex-store-wrapper-jest";
import { createLocalVueForTests } from "../../../tests/helpers/local-vue.js";

describe("TimeTrackingOverviewTrackerList tests", () => {
    describe("Given a timetracking overview widget on reading mode", () => {
        let component_options, store_options, store;
        beforeEach(async () => {
            store_options = {
                state: {
                    selected_trackers: [],
                },
            };
            store = createStoreMock(store_options);

            component_options = {
                localVue: await createLocalVueForTests(),
                mocks: { $store: store },
            };
        });

        it("When no selected trackers, then 'no trackers selected' is displayed", () => {
            const wrapper = shallowMount(TimeTrackingOverviewTrackerList, component_options);
            expect(
                wrapper
                    .find("[data-test=timetracking-overview-reading-mode-trackers-empty]")
                    .exists(),
            ).toBeTruthy();
        });

        it("When trackers are selected, then empty field is not displayed", () => {
            store.state.selected_trackers = [
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

            const wrapper = shallowMount(TimeTrackingOverviewTrackerList, component_options);
            expect(
                wrapper
                    .find("[data-test=timetracking-overview-reading-mode-trackers-empty]")
                    .exists(),
            ).toBeFalsy();
        });
    });
});
