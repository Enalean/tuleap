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
import { TrackerAgileDashboard, Context, MilestoneData } from "../type";
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
                trackers_agile_dashboard: [],
                is_browser_IE11: false,
                label_tracker_planning: "Release"
            }
        };
    });

    describe("getMilestones - rest", () => {
        describe("getMilestones - rest errors", () => {
            it("Given a rest error, When a json error message is received, Then the error message is set.", async () => {
                mockFetchError(jest.spyOn(rest_querier, "getCurrentMilestones"), {
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
                const trackers: TrackerAgileDashboard[] = [
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
                    trackers_agile_dashboard: trackers,
                    is_browser_IE11: false,
                    label_tracker_planning: "Release"
                };

                const milestones: MilestoneData[] = [
                    {
                        id: 1,
                        resources: {
                            content: {
                                accept: {
                                    trackers: [
                                        {
                                            id: 1,
                                            label: "one"
                                        }
                                    ]
                                }
                            },
                            milestones: {
                                accept: {
                                    trackers: [
                                        {
                                            label: "Sprints"
                                        }
                                    ]
                                }
                            }
                        },
                        number_of_artifact_by_trackers: []
                    }
                ];

                jest.spyOn(rest_querier, "getCurrentMilestones").mockReturnValue(
                    Promise.resolve(milestones)
                );

                await actions.getMilestones(context);
                expect(context.commit).toHaveBeenCalledWith("setIsLoading", true);
                expect(context.commit).toHaveBeenCalledWith("setCurrentMilestones", milestones);
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
                trackers_agile_dashboard: trackers,
                is_browser_IE11: false,
                label_tracker_planning: "Release"
            };

            const milestone: MilestoneData = {
                id: 1,
                resources: {
                    content: {
                        accept: {
                            trackers: [
                                {
                                    id: 1,
                                    label: "one"
                                },
                                {
                                    id: 2,
                                    label: "two"
                                }
                            ]
                        }
                    },
                    milestones: {
                        accept: {
                            trackers: [
                                {
                                    label: "Sprints"
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
                        total_artifact: 2,
                        color_name: "red_fiesta"
                    },
                    {
                        id: 2,
                        label: "two",
                        total_artifact: 1,
                        color_name: "lake_placid_blue"
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
                            id: 2
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
