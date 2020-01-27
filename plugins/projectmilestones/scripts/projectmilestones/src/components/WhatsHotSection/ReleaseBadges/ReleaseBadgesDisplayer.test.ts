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
import ReleaseBadgesDisplayer from "./ReleaseBadgesDisplayer.vue";
import { createStoreMock } from "../../../../../../../../src/www/scripts/vue-components/store-wrapper-jest";
import { MilestoneData, StoreOptions } from "../../../type";
import { createReleaseWidgetLocalVue } from "../../../helpers/local-vue-for-test";
import ReleaseOthersBadges from "./ReleaseOthersBadges.vue";
import ReleaseBadgesClosedSprints from "./ReleaseBadgesClosedSprints.vue";

let release_data: MilestoneData & Required<Pick<MilestoneData, "planning">>;
const total_sprint = 10;
const initial_effort = 10;
const component_options: ShallowMountOptions<ReleaseBadgesDisplayer> = {};

const project_id = 102;

describe("ReleaseBadgesDisplayer", () => {
    let store_options: StoreOptions;
    let store;

    async function getPersonalWidgetInstance(
        store_options: StoreOptions
    ): Promise<Wrapper<ReleaseBadgesDisplayer>> {
        store = createStoreMock(store_options);

        component_options.mocks = { $store: store };
        component_options.localVue = await createReleaseWidgetLocalVue();

        return shallowMount(ReleaseBadgesDisplayer, component_options);
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

    it("When the component is rendered, Then ReleaseBasgesOthersSprints is rendered", async () => {
        const wrapper = await getPersonalWidgetInstance(store_options);

        expect(wrapper.contains(ReleaseOthersBadges)).toBe(true);
    });

    describe("Display number of sprint", () => {
        it("When there are not sprints, Then ReleaseBadgesSprints is not rendered", async () => {
            release_data = {
                label: "mile",
                id: 2,
                planning: {
                    id: "100"
                },
                total_sprint: 0,
                initial_effort,
                number_of_artifact_by_trackers: []
            };

            component_options.propsData = {
                release_data
            };

            const wrapper = await getPersonalWidgetInstance(store_options);

            expect(wrapper.contains("[data-test=badge-sprint]")).toBe(false);
        });

        it("When total_sprints is null, Then ReleaseBadgesSprints is not rendered", async () => {
            release_data = {
                label: "mile",
                id: 2,
                planning: {
                    id: "100"
                },
                total_sprint: null,
                initial_effort,
                number_of_artifact_by_trackers: []
            };

            component_options.propsData = {
                release_data
            };

            const wrapper = await getPersonalWidgetInstance(store_options);

            expect(wrapper.contains("[data-test=badge-sprint]")).toBe(false);
        });

        it("When there are some sprints, Then ReleaseBadgesSprints is rendered", async () => {
            release_data = {
                label: "mile",
                id: 2,
                planning: {
                    id: "100"
                },
                total_sprint: 10,
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

            component_options.propsData = {
                release_data
            };

            const wrapper = await getPersonalWidgetInstance(store_options);

            expect(wrapper.contains("[data-test=badge-sprint]")).toBe(true);
        });

        it("When there is no tracker of sprint, Then ReleasesBasgesSprints is not rendered", async () => {
            release_data = {
                label: "mile",
                id: 2,
                planning: {
                    id: "100"
                },
                total_sprint: null,
                initial_effort,
                number_of_artifact_by_trackers: [],
                resources: {
                    milestones: {
                        accept: {
                            trackers: []
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

            component_options.propsData = {
                release_data
            };

            const wrapper = await getPersonalWidgetInstance(store_options);

            expect(wrapper.contains("[data-test=badge-sprint]")).toBe(false);
        });
    });

    it("When the user clicked on sprints, Then a line is displayed", async () => {
        const wrapper = await getPersonalWidgetInstance(store_options);

        wrapper.setData({ open_sprints_details: true });
        expect(wrapper.contains("[data-test=line-displayed]")).toBe(true);
    });

    it("When the user clicked on sprints, Then ReleaseBadgesClosedSprints is rendered", async () => {
        const wrapper = await getPersonalWidgetInstance(store_options);

        expect(wrapper.contains(ReleaseBadgesClosedSprints)).toBe(false);
        wrapper.setData({ open_sprints_details: true });
        expect(wrapper.contains(ReleaseBadgesClosedSprints)).toBe(true);
    });
});
