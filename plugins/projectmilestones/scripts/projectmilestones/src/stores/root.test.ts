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

import { mockFetchError } from "@tuleap/tlp-fetch/mocks/tlp-fetch-mock-helper";
import type {
    TrackerAgileDashboard,
    MilestoneData,
    TrackerProjectLabel,
    TestManagementCampaign,
    Artifact,
    Tracker,
    Project,
} from "../type";
import * as rest_querier from "../api/rest-querier";
import { FetchWrapperError } from "@tuleap/tlp-fetch";
import { setActivePinia, createPinia } from "pinia";
import { useStore } from "./root";

describe("Store actions", () => {
    beforeEach(() => {
        setActivePinia(createPinia());
    });

    describe("getMilestones - rest", () => {
        describe("getMilestones - rest errors", () => {
            it("Given a rest error, When a json error message is received, Then the error message is set.", async () => {
                const store = useStore();
                mockFetchError(jest.spyOn(rest_querier, "getCurrentMilestones"), {
                    error_json: {
                        error: {
                            code: 403,
                            message: "Forbidden",
                        },
                    },
                });

                jest.spyOn(rest_querier, "getNbOfPastRelease").mockReturnValue(Promise.resolve(10));
                jest.spyOn(rest_querier, "getLastRelease");

                await store.getMilestones();
                expect(store.is_loading).toBe(false);
                expect(store.error_message).toBe("403 Forbidden");
            });
        });
        describe("getMilestones - success", () => {
            it("Given a success response, When totals of backlog and upcoming releases are received, Then no message error is received", async () => {
                const store = useStore();

                const milestones: MilestoneData[] = [
                    {
                        id: 1,
                    } as MilestoneData,
                ];

                const last_release: MilestoneData[] = [
                    {
                        id: 10,
                    } as MilestoneData,
                ];

                jest.spyOn(rest_querier, "getLastRelease").mockReturnValue(
                    Promise.resolve(last_release),
                );

                jest.spyOn(rest_querier, "getCurrentMilestones").mockReturnValue(
                    Promise.resolve(milestones),
                );

                jest.spyOn(rest_querier, "getNbOfPastRelease").mockReturnValue(Promise.resolve(10));

                await store.getMilestones();
                expect(store.current_milestones).toBe(milestones);
                expect(store.nb_past_releases).toBe(10);
                expect(store.last_release).toBe(last_release[0]);
                expect(store.is_loading).toBe(false);
            });
        });

        it("When totals of backlog and upcoming releases are received, Then releases are sorted by id", async () => {
            const store = useStore();
            const milestones: MilestoneData[] = [
                {
                    id: 3,
                } as MilestoneData,
                {
                    id: 1,
                } as MilestoneData,
                {
                    id: 2,
                } as MilestoneData,
            ];

            const milestones_sorted: MilestoneData[] = [
                {
                    id: 3,
                } as MilestoneData,
                {
                    id: 2,
                } as MilestoneData,
                {
                    id: 1,
                } as MilestoneData,
            ];

            const last_release: MilestoneData[] = [
                {
                    id: 10,
                } as MilestoneData,
            ];

            jest.spyOn(rest_querier, "getLastRelease").mockReturnValue(
                Promise.resolve(last_release),
            );

            jest.spyOn(rest_querier, "getCurrentMilestones").mockReturnValue(
                Promise.resolve(milestones),
            );

            jest.spyOn(rest_querier, "getNbOfPastRelease").mockReturnValue(Promise.resolve(10));

            await store.getMilestones();
            expect(store.current_milestones).toEqual(milestones_sorted);
            expect(store.nb_past_releases).toBe(10);
            expect(store.last_release).toBe(last_release[0]);
            expect(store.is_loading).toBe(false);
        });
    });

    describe("getEnhancedMilestones()", () => {
        it("When there is no error in API, Then enriched milestone returned", async () => {
            const store = useStore();
            const trackers: TrackerAgileDashboard[] = [
                {
                    id: 1,
                    label: "one",
                    color_name: "red_fiesta",
                },
                {
                    id: 2,
                    label: "two",
                    color_name: "lake_placid_blue",
                },
            ];

            store.trackers_agile_dashboard = trackers;

            const milestone: MilestoneData = {
                id: 1,
                resources: {
                    content: {
                        accept: {
                            trackers: [
                                {
                                    id: 1,
                                    label: "one",
                                },
                                {
                                    id: 2,
                                    label: "two",
                                },
                            ],
                        },
                    },
                    milestones: {
                        accept: {
                            trackers: [] as TrackerProjectLabel[],
                        },
                    },
                },
            } as MilestoneData;

            const sprint_open = [
                {
                    id: 10,
                    semantic_status: "open",
                },
                {
                    id: 12,
                    semantic_status: "open",
                },
            ] as MilestoneData[];

            const sprint_open_sorted = [
                {
                    id: 12,
                    semantic_status: "open",
                },
                {
                    id: 10,
                    semantic_status: "open",
                },
            ] as MilestoneData[];

            const sprint_closed = {
                id: 11,
                semantic_status: "closed",
            } as MilestoneData;

            const enriched_milestones = {
                ...milestone,
                number_of_artifact_by_trackers: [
                    {
                        id: 1,
                        label: "one",
                        total_artifact: 5,
                        color_name: "red_fiesta",
                    },
                    {
                        id: 2,
                        label: "two",
                        total_artifact: 1,
                        color_name: "lake_placid_blue",
                    },
                ],
                initial_effort: 15,
                total_sprint: 3,
                total_closed_sprint: 1,
                open_sprints: sprint_open_sorted,
            };

            const milestone_content = [
                {
                    initial_effort: 5,
                    artifact: {
                        tracker: {
                            id: 1,
                        },
                    },
                },
                {
                    initial_effort: 10,
                    artifact: {
                        tracker: {
                            id: 2,
                        },
                    },
                },
                {
                    initial_effort: 0,
                    artifact: {
                        tracker: {
                            id: 1,
                        },
                    },
                },
            ];

            const item_in_sprint = [
                {
                    initial_effort: 15,
                    artifact: {
                        tracker: {
                            id: 1,
                        },
                    },
                },
            ];

            jest.spyOn(rest_querier, "getMilestonesBacklog").mockReturnValue(
                Promise.resolve(milestone_content),
            );
            jest.spyOn(rest_querier, "getAllSprints").mockReturnValue(
                Promise.resolve([sprint_closed, ...sprint_open]),
            );
            jest.spyOn(rest_querier, "getMilestonesContent").mockReturnValue(
                Promise.resolve(item_in_sprint),
            );

            const enriched_milestones_received = await store.getEnhancedMilestones(milestone);
            expect(enriched_milestones_received).toEqual(enriched_milestones);
        });
    });

    describe("getTestManagementCampaigns", () => {
        it("When there is a project id, Then all campaigns are returned", async () => {
            const store = useStore();
            const milestone: MilestoneData = {
                id: 101,
                artifact: {
                    tracker: {
                        project: {
                            id: 102,
                        } as Project,
                    } as Tracker,
                } as Artifact,
            } as MilestoneData;

            const campaigns: TestManagementCampaign[] = [
                {
                    nb_of_notrun: 1,
                    nb_of_blocked: 2,
                    nb_of_failed: 0,
                    nb_of_passed: 5,
                },
                {
                    nb_of_passed: 1,
                    nb_of_blocked: 3,
                    nb_of_failed: 5,
                    nb_of_notrun: 1,
                },
            ];

            const expected_campaign: TestManagementCampaign = {
                nb_of_notrun: 2,
                nb_of_blocked: 5,
                nb_of_failed: 5,
                nb_of_passed: 6,
            };

            jest.spyOn(rest_querier, "getTestManagementCampaigns").mockReturnValue(
                Promise.resolve(campaigns),
            );

            const campaign = await store.getTestManagementCampaigns(milestone);
            expect(campaign).toStrictEqual(expected_campaign);
        });
    });

    describe("handleErrorMessage - error", () => {
        it("Given an error, When it can't parse the error, Then the error message is empty.", async () => {
            const store = useStore();
            await store.handleErrorMessage(
                new FetchWrapperError("error", {
                    json(): Promise<void> {
                        throw new Error();
                    },
                } as Response),
            );

            expect(store.error_message).toBe("");
        });
    });
});
