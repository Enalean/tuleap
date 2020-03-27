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

import * as actions from "./actions";
import { mockFetchError } from "../../../../../../src/www/themes/common/tlp/mocks/tlp-fetch-mock-helper";
import { TrackerAgileDashboard, Context, MilestoneData, TrackerProjectLabel, State } from "../type";
import * as rest_querier from "../api/rest-querier";

describe("Store actions", () => {
    let context: Context;

    beforeEach(() => {
        context = {
            commit: jest.fn(),
            state: {
                project_id: 102,
                nb_backlog_items: 0,
                nb_upcoming_releases: 0,
                offset: 0,
                limit: 50,
                current_milestones: [] as MilestoneData[],
                error_message: null,
                is_loading: false,
                trackers_agile_dashboard: [] as TrackerAgileDashboard[],
                label_tracker_planning: "Release",
                is_timeframe_duration: true,
                label_start_date: "start date",
                label_timeframe: "duration",
            } as State,
        };
    });

    describe("getMilestones - rest", () => {
        describe("getMilestones - rest errors", () => {
            it("Given a rest error, When a json error message is received, Then the error message is set.", async () => {
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

                await actions.getMilestones(context);
                expect(context.commit).toHaveBeenCalledWith("setIsLoading", true);
                expect(context.commit).toHaveBeenCalledWith("resetErrorMessage");
                expect(context.commit).toHaveBeenCalledWith("setErrorMessage", "403 Forbidden");
                expect(context.commit).toHaveBeenCalledWith("setIsLoading", false);
            });
        });
        describe("getMilestones - success", () => {
            it("Given a success response, When totals of backlog and upcoming releases are received, Then no message error is received", async () => {
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

                context.state = {
                    project_id: 102,
                    nb_backlog_items: 0,
                    nb_upcoming_releases: 0,
                    error_message: null,
                    is_loading: false,
                    current_milestones: [] as MilestoneData[],
                    offset: 0,
                    limit: 50,
                    trackers_agile_dashboard: trackers,
                    label_tracker_planning: "Release",
                    is_timeframe_duration: true,
                    label_start_date: "start date",
                    label_timeframe: "duration",
                    last_release: null,
                } as State;

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
                    Promise.resolve(last_release)
                );

                jest.spyOn(rest_querier, "getCurrentMilestones").mockReturnValue(
                    Promise.resolve(milestones)
                );

                jest.spyOn(rest_querier, "getNbOfPastRelease").mockReturnValue(Promise.resolve(10));

                await actions.getMilestones(context);
                expect(context.commit).toHaveBeenCalledWith("setIsLoading", true);
                expect(context.commit).toHaveBeenCalledWith("setCurrentMilestones", milestones);
                expect(context.commit).toHaveBeenCalledWith("setNbPastReleases", 10);
                expect(context.commit).toHaveBeenCalledWith("setLastRelease", last_release[0]);
                expect(context.commit).toHaveBeenCalledWith("setIsLoading", false);
            });
        });
    });

    describe("getEnhancedMilestones()", () => {
        it("When there is no error in API, Then enriched milestone returned", async () => {
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

            context.state = {
                project_id: 102,
                nb_backlog_items: 0,
                nb_upcoming_releases: 0,
                error_message: null,
                is_loading: false,
                current_milestones: [] as MilestoneData[],
                offset: 0,
                limit: 50,
                trackers_agile_dashboard: trackers,
                label_tracker_planning: "Release",
                is_timeframe_duration: true,
                label_start_date: "start date",
                label_timeframe: "duration",
            } as State;

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

            const sprint = {
                id: 10,
            } as MilestoneData;

            const enriched_milestones = {
                ...milestone,
                number_of_artifact_by_trackers: [
                    {
                        id: 1,
                        label: "one",
                        total_artifact: 2,
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
                total_sprint: 11,
                total_closed_sprint: 10,
                open_sprints: [sprint],
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

            jest.spyOn(rest_querier, "getMilestonesContent").mockReturnValue(
                Promise.resolve(milestone_content)
            );
            jest.spyOn(rest_querier, "getOpenSprints").mockReturnValue(Promise.resolve([sprint]));
            jest.spyOn(rest_querier, "getNbOfClosedSprints").mockReturnValue(Promise.resolve(10));

            const enriched_milestones_received = await actions.getEnhancedMilestones(
                context,
                milestone
            );
            expect(enriched_milestones_received).toEqual(enriched_milestones);
        });
    });

    describe("handleErrorMessage - error", () => {
        it("Given an error, When it can't parse the error, Then the error message is empty.", async () => {
            await actions.handleErrorMessage(context, {
                name: "error",
                message: "Something went wrong",
                response: {
                    json(): Promise<void> {
                        throw new Error();
                    },
                } as Response,
            });

            expect(context.commit).toHaveBeenCalledWith("setErrorMessage", "");
        });
    });
});
