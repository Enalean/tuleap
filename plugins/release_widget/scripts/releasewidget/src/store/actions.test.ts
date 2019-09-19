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
import { mockFetchError } from "tlp-fetch-mocks-helper-jest";
import { TrackerProject, Context } from "../type";
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
                current_milestones: [],
                error_message: null,
                is_loading: false,
                trackers: []
            }
        };
    });

    describe("getMilestones - rest", () => {
        describe("getMilestones - rest errors", () => {
            it("Given a rest error, When a json error message is received, Then the error message is set.", async () => {
                mockFetchError(jest.spyOn(rest_querier, "getTrackersProject"), {
                    error_json: {
                        error: {
                            code: 403,
                            message: "Forbidden"
                        }
                    }
                });

                await actions.getMilestones(context);
                expect(context.commit).toHaveBeenCalledWith("setIsLoading", true);
                expect(context.commit).toHaveBeenCalledWith("resetErrorMessage");
                expect(context.commit).toHaveBeenCalledWith("setErrorMessage", "403 Forbidden");
                expect(context.commit).toHaveBeenCalledWith("setIsLoading", false);
            });
        });
        describe("getMilestones - success", () => {
            it("Given a success response, When totals of backlog and upcoming releases are received, Then no message error is received", async () => {
                const trackers: TrackerProject[] = [
                    {
                        id: 1,
                        label: "one",
                        color_name: "red_fiesta"
                    },
                    {
                        id: 2,
                        label: "two",
                        color_name: "lake_placid_blue"
                    }
                ];

                context.state = {
                    project_id: 102,
                    nb_backlog_items: 0,
                    nb_upcoming_releases: 0,
                    error_message: null,
                    is_loading: false,
                    current_milestones: [],
                    offset: 0,
                    limit: 50,
                    trackers
                };

                const milestones = [
                    {
                        id: 1,
                        resources: {
                            content: {
                                accept: {
                                    trackers: [
                                        {
                                            id: 1,
                                            label: "one",
                                            color_name: "red_fiesta"
                                        }
                                    ]
                                }
                            }
                        },
                        number_of_artifact_by_trackers: []
                    }
                ];

                jest.spyOn(rest_querier, "getTrackersProject").mockReturnValue(
                    Promise.resolve(trackers)
                );
                jest.spyOn(rest_querier, "getNbOfUpcomingReleases").mockReturnValue(
                    Promise.resolve(1)
                );
                jest.spyOn(rest_querier, "getNbOfBacklogItems").mockReturnValue(Promise.resolve(2));
                jest.spyOn(rest_querier, "getCurrentMilestones").mockReturnValue(
                    Promise.resolve(milestones)
                );

                await actions.getMilestones(context);
                expect(context.commit).toHaveBeenCalledWith("setIsLoading", true);
                expect(context.commit).toHaveBeenCalledWith("setTrackers", trackers);
                expect(context.commit).toHaveBeenCalledWith("setNbUpcomingReleases", 1);
                expect(context.commit).toHaveBeenCalledWith("setNbBacklogItem", 2);
                expect(context.commit).toHaveBeenCalledWith("setCurrentMilestones", milestones);
                expect(context.commit).toHaveBeenCalledWith("setIsLoading", false);
            });
        });
    });

    describe("getEnhancedMilestones()", () => {
        it("When there is no error in API, Then enriched milestone returned", async () => {
            const trackers: TrackerProject[] = [
                {
                    id: 1,
                    label: "one",
                    color_name: "red_fiesta"
                },
                {
                    id: 2,
                    label: "two",
                    color_name: "lake_placid_blue"
                }
            ];

            context.state = {
                project_id: 102,
                nb_backlog_items: 0,
                nb_upcoming_releases: 0,
                error_message: null,
                is_loading: false,
                current_milestones: [],
                offset: 0,
                limit: 50,
                trackers
            };

            const milestone = {
                id: 1,
                resources: {
                    content: {
                        accept: {
                            trackers: [
                                {
                                    id: 1,
                                    label: "one",
                                    color_name: "red_fiesta"
                                }
                            ]
                        }
                    }
                },
                number_of_artifact_by_trackers: []
            };

            const enriched_milestones = {
                ...milestone,
                number_of_artifact_by_trackers: [
                    {
                        id: 1,
                        label: "one",
                        total_artifact: 3,
                        color_name: "red_fiesta"
                    }
                ],
                initial_effort: 15,
                total_sprint: 5
            };

            const milestone_content = [
                {
                    initial_effort: 5,
                    artifact: {
                        tracker: {
                            id: 1
                        }
                    }
                },
                {
                    initial_effort: 10,
                    artifact: {
                        tracker: {
                            id: 1
                        }
                    }
                },
                {
                    initial_effort: 0,
                    artifact: {
                        tracker: {
                            id: 1
                        }
                    }
                }
            ];

            jest.spyOn(rest_querier, "getMilestonesContent").mockReturnValue(
                Promise.resolve(milestone_content)
            );
            jest.spyOn(rest_querier, "getNbOfSprints").mockReturnValue(Promise.resolve(5));

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
                    }
                } as Response
            });

            expect(context.commit).toHaveBeenCalledWith("setErrorMessage", "");
        });
    });
});
