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

import * as rest_querier from "../api/rest-querier";
import { mockFetchError } from "@tuleap/tlp-fetch/mocks/tlp-fetch-mock-helper";
import {
    REST_FEEDBACK_DELETE,
    REST_FEEDBACK_ADD,
    REST_FEEDBACK_EDIT,
    ERROR_OCCURRED,
    SUCCESS_TYPE,
} from "@tuleap/plugin-timetracking-constants";
import { createPinia, setActivePinia } from "pinia";
import { usePersonalTimetrackingWidgetStore } from "./index";

describe("Store actions", () => {
    let store;
    beforeEach(() => {
        setActivePinia(createPinia());
        store = usePersonalTimetrackingWidgetStore();
    });
    it("Given new dates, Then dates must equal to the new dates and queryHasChanged must be true", () => {
        store.setDatesAndReload(["2015-09-14", "2017-04-24"]);
        expect(store.start_date).toBe("2015-09-14");
        expect(store.end_date).toBe("2017-04-24");
    });

    describe("loadFirstBatchOfTimes - success", () => {
        it("Given a success response, When times are received, Then no message error is received", async () => {
            const times = [
                [
                    {
                        artifact: {},
                        project: {},
                        minutes: 20,
                    },
                ],
            ];

            jest.spyOn(rest_querier, "getTrackedTimes").mockReturnValue(
                Promise.resolve({ times, total: 1 }),
            );

            await store.loadFirstBatchOfTimes();
            expect(store.times).toStrictEqual(times);
            expect(store.total_times).toBe(1);
            expect(store.error_message).toBe("");
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

                await store.getTimes();
                expect(store.error_message).toBe("403 Forbidden");
            });

            it("Given a rest error, When a json error message is received, Then the message is extracted by getTimes() into the error_message private property.", async () => {
                jest.spyOn(rest_querier, "getTrackedTimes").mockReturnValue(Promise.reject());

                await store.getTimes();
                expect(store.error_message).toBe(ERROR_OCCURRED);
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
                await store.addTime(["2018-01-01", 1, "11:11", "oui"]);
                expect(store.rest_feedback.message).toBe("403 Forbidden");
                expect(store.rest_feedback.type).toBe("danger");
            });

            it("Given a rest error, When a json error message is received, Then the message is extracted by addTime() into the rest_feedback private property.", async () => {
                jest.spyOn(rest_querier, "addTime").mockReturnValue(Promise.reject());

                await store.addTime(["2018-01-01", 1, "11:11", "oui"]);
                expect(store.rest_feedback.message).toBe(ERROR_OCCURRED);
                expect(store.rest_feedback.type).toBe("danger");
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

                await store.addTime(["2018-01-01", 1, "00:20", "oui"]);
                expect(store.current_times).toStrictEqual([time]);
                expect(restAddTime).toHaveBeenCalledTimes(1);
                expect(store.rest_feedback.message).toBe(REST_FEEDBACK_ADD);
                expect(store.rest_feedback.type).toBe(SUCCESS_TYPE);
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

                await store.updateTime(["2018-01-01", 1, "11:11", "oui"]);
                expect(store.rest_feedback.message).toBe("403 Forbidden");
                expect(store.rest_feedback.type).toBe("danger");
            });

            it("Given a rest error ,When no error message is provided, Then it should add a generic error message on rest_feedback", async () => {
                jest.spyOn(rest_querier, "updateTime").mockReturnValue(Promise.reject());

                await store.updateTime(["2018-01-01", 1, "11:11", "oui"]);
                expect(store.rest_feedback.message).toBe(ERROR_OCCURRED);
                expect(store.rest_feedback.type).toBe("danger");
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

                await store.updateTime(["2018-01-01", 1, "00:20", "oui"]);
                expect(getTrackedTimes).toHaveBeenCalled();
                expect(store.rest_feedback.message).toBe(REST_FEEDBACK_EDIT);
                expect(store.rest_feedback.type).toBe(SUCCESS_TYPE);
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

                await store.deleteTime(1);
                expect(store.rest_feedback.message).toBe("403 Forbidden");
                expect(store.rest_feedback.type).toBe("danger");
            });

            it("Given a rest error ,When no error message is provided, Then it should add a generic error message on rest_feedback", async () => {
                jest.spyOn(rest_querier, "deleteTime").mockReturnValue(Promise.reject());

                await store.deleteTime(1);
                expect(store.rest_feedback.message).toBe(ERROR_OCCURRED);
                expect(store.rest_feedback.type).toBe("danger");
            });
        });

        describe("deleteTime - success", () => {
            it("Given no rest error, then a success message is displayed", async () => {
                const getTrackedTimes = jest.spyOn(rest_querier, "getTrackedTimes");

                jest.spyOn(rest_querier, "deleteTime").mockReturnValue(Promise.resolve());

                const time_id = 1;
                store.current_times = [
                    [
                        {
                            date: "2028-01-01",
                            minutes: 20,
                            id: time_id,
                        },
                    ],
                ];
                await store.deleteTime(time_id);
                expect(store.current_times).toHaveLength(0);
                expect(getTrackedTimes).toHaveBeenCalled();
                expect(store.rest_feedback.message).toBe(REST_FEEDBACK_DELETE);
                expect(store.rest_feedback.type).toBe(SUCCESS_TYPE);
            });
        });

        describe("reloadTimes", () => {
            it("Given a success response, When times are received, Then no message error is received and reloadTimes' mutations are called", async () => {
                let times = [
                    [
                        {
                            artifact: {},
                            project: {},
                            minutes: 20,
                        },
                    ],
                ];

                jest.spyOn(rest_querier, "getTrackedTimes").mockReturnValue(times);

                await store.reloadTimes();
                expect(store.is_loading).toBe(false);
                expect(store.pagination_offset).toBe(0);
                expect(store.times).toStrictEqual([]);
                expect(store.is_add_mode).toBe(false);
            });
        });
    });
});
