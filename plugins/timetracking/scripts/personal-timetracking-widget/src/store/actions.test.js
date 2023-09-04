/*
 * Copyright (c) Enalean, 2018-Present. All Rights Reserved.
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

import * as actions from "./actions.js";
import * as rest_querier from "../api/rest-querier.js";
import { mockFetchError } from "@tuleap/tlp-fetch/mocks/tlp-fetch-mock-helper";
import {
    REST_FEEDBACK_ADD,
    REST_FEEDBACK_EDIT,
    REST_FEEDBACK_DELETE,
    ERROR_OCCURRED,
} from "../../../constants.js";

describe("Store actions", () => {
    let context;
    beforeEach(() => {
        context = {
            commit: jest.fn(),
            state: {
                start_date: "2015-09-14",
                end_date: "2017-04-24",
                pagination_limit: 50,
                pagination_offset: 0,
                times_length: 1,
            },
        };
    });
    it("Given new dates, Then dates must equal to the new dates and queryHasChanged must be true", () => {
        actions.setDatesAndReload(context, ["2015-09-14", "2017-04-24"]);
        expect(context.commit).toHaveBeenCalledWith("setParametersForNewQuery", [
            "2015-09-14",
            "2017-04-24",
        ]);
    });

    describe("loadFirstBatchOfTimes - success", () => {
        it("Given a success response, When times are received, Then no message error is reveived", async () => {
            const times = [
                [
                    {
                        artifact: {},
                        project: {},
                        minutes: 20,
                    },
                ],
            ];
            context.state.times = times;

            jest.spyOn(rest_querier, "getTrackedTimes").mockReturnValue(
                Promise.resolve({ times, total: 1 }),
            );

            await actions.loadFirstBatchOfTimes(context);
            expect(context.commit).toHaveBeenCalledWith("setIsLoading", true);
            expect(context.commit).toHaveBeenCalledWith("resetErrorMessage");
            expect(context.commit).toHaveBeenCalledWith("loadAChunkOfTimes", [times, 1]);
            expect(context.commit).toHaveBeenCalledWith("setIsLoading", false);
        });

        describe("getTimes - rest errors", () => {
            it("Given a rest error, When a json error message is received, Then the message is extracted in the component 's error_message private property.", async () => {
                mockFetchError(jest.spyOn(rest_querier, "getTrackedTimes"), {
                    error_json: {
                        error: {
                            code: 403,
                            message: "Forbidden",
                        },
                    },
                });

                await actions.getTimes(context);
                expect(context.commit).toHaveBeenCalledWith("resetErrorMessage");
                expect(context.commit).toHaveBeenCalledWith("setErrorMessage", "403 Forbidden");
            });

            it("Given a rest error, When a json error message is received, Then the message is extracted by getTimes() into the error_message private property.", async () => {
                jest.spyOn(rest_querier, "getTrackedTimes").mockReturnValue(Promise.reject());

                await actions.getTimes(context);
                expect(context.commit).toHaveBeenCalledWith("resetErrorMessage");
                expect(context.commit).toHaveBeenCalledWith("setErrorMessage", ERROR_OCCURRED);
            });
        });

        describe("addTime - rest errors", () => {
            it("Given a rest error, When a json error message is received, Then the message is extracted in the component 's rest_feedback private property.", async () => {
                mockFetchError(jest.spyOn(rest_querier, "addTime"), {
                    error_json: {
                        error: {
                            code: 403,
                            message: "Forbidden",
                        },
                    },
                });

                await actions.addTime(context, ["2018-01-01", 1, "11:11", "oui"]);
                expect(context.commit).toHaveBeenCalledWith("setRestFeedback", [
                    "403 Forbidden",
                    "danger",
                ]);
            });

            it("Given a rest error, When a json error message is received, Then the message is extracted by addTime() into the rest_feedback private property.", async () => {
                jest.spyOn(rest_querier, "addTime").mockReturnValue(Promise.reject());

                await actions.addTime(context, ["2018-01-01", 1, "11:11", "oui"]);
                expect(context.commit).toHaveBeenCalledWith("setRestFeedback", [
                    ERROR_OCCURRED,
                    "danger",
                ]);
            });
        });

        describe("addTime - success", () => {
            it("Given no rest error, then a success message is displayed", async () => {
                const restAddTime = jest.spyOn(rest_querier, "addTime");

                let time = {
                    artifact: {},
                    project: {},
                    minutes: 20,
                };
                restAddTime.mockReturnValue(Promise.resolve(time));

                await actions.addTime(context, ["2018-01-01", 1, "00:20", "oui"]);
                expect(context.commit).toHaveBeenCalledWith("pushCurrentTimes", [
                    [time],
                    REST_FEEDBACK_ADD,
                ]);
                expect(restAddTime).toHaveBeenCalledTimes(1);
                expect(context.commit).not.toHaveBeenCalledWith("setRestFeedback");
            });
        });

        describe("updateTime - rest errors", () => {
            it("Given a rest error, When a json error message is received, Then it should add the json error message on rest_feedback", async () => {
                mockFetchError(jest.spyOn(rest_querier, "updateTime"), {
                    error_json: {
                        error: {
                            code: 403,
                            message: "Forbidden",
                        },
                    },
                });

                await actions.updateTime(context, ["2018-01-01", 1, "11:11", "oui"]);
                expect(context.commit).toHaveBeenCalledWith("setRestFeedback", [
                    "403 Forbidden",
                    "danger",
                ]);
            });

            it("Given a rest error ,When no error message is provided, Then it should add a generic error message on rest_feedback", async () => {
                jest.spyOn(rest_querier, "updateTime").mockReturnValue(Promise.reject());

                await actions.updateTime(context, ["2018-01-01", 1, "11:11", "oui"]);
                expect(context.commit).toHaveBeenCalledWith("setRestFeedback", [
                    ERROR_OCCURRED,
                    "danger",
                ]);
            });
        });

        describe("updateTime - success", () => {
            it("Given no rest error, then a success message is displayed", async () => {
                const getTrackedTimes = jest.spyOn(rest_querier, "getTrackedTimes");

                let time = {
                    artifact: {},
                    project: {},
                    id: 1,
                    minutes: 20,
                };
                jest.spyOn(rest_querier, "updateTime").mockReturnValue(Promise.resolve(time));

                await actions.updateTime(context, ["2018-01-01", 1, "00:20", "oui"]);
                expect(context.commit).toHaveBeenCalledWith("replaceInCurrentTimes", [
                    time,
                    REST_FEEDBACK_EDIT,
                ]);
                expect(getTrackedTimes).toHaveBeenCalled();
                expect(context.commit).not.toHaveBeenCalledWith("setRestFeedback");
            });
        });

        describe("deleteTime - rest errors", () => {
            it("Given a rest error, When a json error message is received, Then it should add the json error message on rest_feedback", async () => {
                mockFetchError(jest.spyOn(rest_querier, "deleteTime"), {
                    error_json: {
                        error: {
                            code: 403,
                            message: "Forbidden",
                        },
                    },
                });

                await actions.deleteTime(context, 1);
                expect(context.commit).toHaveBeenCalledWith("setRestFeedback", [
                    "403 Forbidden",
                    "danger",
                ]);
            });

            it("Given a rest error ,When no error message is provided, Then it should add a generic error message on rest_feedback", async () => {
                jest.spyOn(rest_querier, "deleteTime").mockReturnValue(Promise.reject());

                await actions.deleteTime(context, 1);
                expect(context.commit).toHaveBeenCalledWith("setRestFeedback", [
                    ERROR_OCCURRED,
                    "danger",
                ]);
            });
        });

        describe("deleteTime - success", () => {
            it("Given no rest error, then a success message is displayed", async () => {
                const getTrackedTimes = jest.spyOn(rest_querier, "getTrackedTimes");

                jest.spyOn(rest_querier, "deleteTime").mockReturnValue(Promise.resolve());

                const time_id = 1;
                await actions.deleteTime(context, time_id);
                expect(context.commit).toHaveBeenCalledWith("deleteInCurrentTimes", [
                    time_id,
                    REST_FEEDBACK_DELETE,
                ]);
                expect(getTrackedTimes).toHaveBeenCalled();
                expect(context.commit).not.toHaveBeenCalledWith("setRestFeedback");
            });
        });

        describe("reloadTimes", () => {
            it("Given a success response, When times are received, Then no message error is reveived and reloadTimes' mutations are called", async () => {
                let times = [
                    [
                        {
                            artifact: {},
                            project: {},
                            minutes: 20,
                        },
                    ],
                ];
                context.state.times = times;

                jest.spyOn(rest_querier, "getTrackedTimes").mockReturnValue(times);

                await actions.reloadTimes(context);
                expect(context.commit).toHaveBeenCalledWith("resetTimes");
                expect(context.commit).toHaveBeenCalledWith("setIsLoading", false);
            });
        });
    });
});
