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
import ReleaseBadgesAllSprints from "./ReleaseBadgesAllSprints.vue";
import { createStoreMock } from "../../../../../../../../src/www/scripts/vue-components/store-wrapper-jest";
import { MilestoneData, StoreOptions } from "../../../type";
import { createReleaseWidgetLocalVue } from "../../../helpers/local-vue-for-test";

let release_data: MilestoneData & Required<Pick<MilestoneData, "planning">>;
const total_sprint = 10;
const initial_effort = 10;
const component_options: ShallowMountOptions<ReleaseBadgesAllSprints> = {};

const project_id = 102;

describe("ReleaseBadgesAllSprints", () => {
    let store_options: StoreOptions;
    let store;

    async function getPersonalWidgetInstance(
        store_options: StoreOptions
    ): Promise<Wrapper<ReleaseBadgesAllSprints>> {
        store = createStoreMock(store_options);

        component_options.mocks = { $store: store };
        component_options.localVue = await createReleaseWidgetLocalVue();

        return shallowMount(ReleaseBadgesAllSprints, component_options);
    }

    beforeEach(() => {
        store_options = {
            state: {
                project_id: project_id
            }
        };

        release_data = {
            label: "mile",
            id: 2,
            planning: {
                id: "100"
            },
            capacity: 10,
            total_sprint,
            initial_effort,
            number_of_artifact_by_trackers: [],
            resources: {
                milestones: {
                    accept: {
                        trackers: [
                            {
                                label: "Sprint1"
                            }
                        ]
                    }
                },
                content: {
                    accept: {
                        trackers: []
                    }
                },
                additional_panes: [],
                burndown: null,
                cardwall: null
            }
        };

        component_options.propsData = { release_data };
    });

    describe("Display number of sprint", () => {
        it("When there is a tracker, Then number of sprint is displayed", async () => {
            const wrapper = await getPersonalWidgetInstance(store_options);

            expect(wrapper.find("[data-test=badge-sprint]").text()).toEqual("10 Sprint1");
        });

        it("When there isn't tracker, Then there is no link", async () => {
            release_data = {
                label: "mile",
                id: 2,
                planning: {
                    id: "100"
                },
                total_sprint,
                initial_effort,
                number_of_artifact_by_trackers: []
            };

            component_options.propsData = {
                release_data
            };

            const wrapper = await getPersonalWidgetInstance(store_options);

            expect(wrapper.contains("[data-test=badge-sprint]")).toBe(false);
        });
    });
});
