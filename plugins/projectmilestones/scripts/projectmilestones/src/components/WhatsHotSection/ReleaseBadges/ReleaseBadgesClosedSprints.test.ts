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

import { shallowMount, ShallowMountOptions, Wrapper } from "@vue/test-utils";
import ReleaseBadgesClosedSprints from "./ReleaseBadgesClosedSprints.vue";
import { createStoreMock } from "../../../../../../../../src/www/scripts/vue-components/store-wrapper-jest";
import { MilestoneData, StoreOptions } from "../../../type";
import { createReleaseWidgetLocalVue } from "../../../helpers/local-vue-for-test";

let release_data: MilestoneData & Required<Pick<MilestoneData, "planning">>;
const total_sprint = 10;
const initial_effort = 10;
const component_options: ShallowMountOptions<ReleaseBadgesClosedSprints> = {};

const project_id = 102;

describe("ReleaseBadgesClosedSprints", () => {
    let store_options: StoreOptions;
    let store;

    async function getPersonalWidgetInstance(
        store_options: StoreOptions
    ): Promise<Wrapper<ReleaseBadgesClosedSprints>> {
        store = createStoreMock(store_options);

        component_options.mocks = { $store: store };
        component_options.localVue = await createReleaseWidgetLocalVue();

        return shallowMount(ReleaseBadgesClosedSprints, component_options);
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
            capacity: 15,
            total_sprint,
            total_closed_sprint: 5,
            initial_effort,
            number_of_artifact_by_trackers: []
        };

        component_options.propsData = { release_data };
    });

    describe("Display total of closed sprints", () => {
        it("When there are some closed sprints, Then the total is displayed", async () => {
            release_data = {
                label: "mile",
                id: 2,
                planning: {
                    id: "100"
                },
                capacity: 10,
                total_sprint,
                total_closed_sprint: 6,
                initial_effort: null,
                number_of_artifact_by_trackers: [],
                resources: {
                    milestones: {
                        accept: {
                            trackers: [
                                {
                                    label: "sprint"
                                }
                            ]
                        }
                    },
                    burndown: null,
                    additional_panes: [],
                    content: {
                        accept: {
                            trackers: [
                                {
                                    id: 2,
                                    label: "sprints"
                                }
                            ]
                        }
                    },
                    cardwall: null
                }
            };

            component_options.propsData = { release_data };
            const wrapper = await getPersonalWidgetInstance(store_options);

            expect(wrapper.contains("[data-test=total-closed-sprints]")).toBe(true);
        });

        it("When the total of closed sprints is null, Then the total is not displayed", async () => {
            release_data = {
                label: "mile",
                id: 2,
                planning: {
                    id: "100"
                },
                capacity: 10,
                total_sprint,
                total_closed_sprint: null,
                initial_effort: null,
                number_of_artifact_by_trackers: [],
                resources: {
                    milestones: {
                        accept: {
                            trackers: [
                                {
                                    label: "sprint"
                                }
                            ]
                        }
                    },
                    burndown: null,
                    additional_panes: [],
                    content: {
                        accept: {
                            trackers: [
                                {
                                    id: 2,
                                    label: "sprints"
                                }
                            ]
                        }
                    },
                    cardwall: null
                }
            };

            component_options.propsData = { release_data };
            const wrapper = await getPersonalWidgetInstance(store_options);

            expect(wrapper.contains("[data-test=total-closed-sprints]")).toBe(false);
        });

        it("When the total of closed sprints is 0, Then the total is displayed", async () => {
            release_data = {
                label: "mile",
                id: 2,
                planning: {
                    id: "100"
                },
                capacity: 10,
                total_sprint,
                total_closed_sprint: 0,
                initial_effort: null,
                number_of_artifact_by_trackers: [],
                resources: {
                    milestones: {
                        accept: {
                            trackers: [
                                {
                                    label: "sprint"
                                }
                            ]
                        }
                    },
                    burndown: null,
                    additional_panes: [],
                    content: {
                        accept: {
                            trackers: [
                                {
                                    id: 2,
                                    label: "sprints"
                                }
                            ]
                        }
                    },
                    cardwall: null
                }
            };

            component_options.propsData = { release_data };
            const wrapper = await getPersonalWidgetInstance(store_options);

            expect(wrapper.contains("[data-test=total-closed-sprints]")).toBe(true);
        });

        it("When there is no trackers of sprints, Then the total is not displayed", async () => {
            release_data = {
                label: "mile",
                id: 2,
                planning: {
                    id: "100"
                },
                capacity: 10,
                total_sprint,
                total_closed_sprint: 0,
                initial_effort: null,
                number_of_artifact_by_trackers: []
            };

            component_options.propsData = { release_data };
            const wrapper = await getPersonalWidgetInstance(store_options);

            expect(wrapper.contains("[data-test=total-closed-sprints]")).toBe(false);
        });

        it("When there are resources but no trackers of sprints, Then the total is not displayed", async () => {
            release_data = {
                label: "mile",
                id: 2,
                planning: {
                    id: "100"
                },
                capacity: 10,
                total_sprint,
                total_closed_sprint: 0,
                initial_effort: null,
                number_of_artifact_by_trackers: [],
                resources: {
                    milestones: {
                        accept: {
                            trackers: []
                        }
                    },
                    burndown: null,
                    additional_panes: [],
                    content: {
                        accept: {
                            trackers: []
                        }
                    },
                    cardwall: null
                }
            };

            component_options.propsData = { release_data };
            const wrapper = await getPersonalWidgetInstance(store_options);

            expect(wrapper.contains("[data-test=total-closed-sprints]")).toBe(false);
        });
    });
});
