/*
 * Copyright Enalean (c) 2018. All rights reserved.
 *
 * Tuleap and Enalean names and logos are registrated trademarks owned by
 * Enalean SAS. All other trademarks or names are properties of their respective
 * owners.
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
import { tlp, mockFetchError, mockFetchSuccess } from "tlp-mocks";

describe("Store actions", () => {
    let context;
    beforeEach(() => {
        context = {
            commit: jasmine.createSpy("commit"),
            state: {
                start_date: "2015-09-14",
                end_date: "2017-04-24",
                pagination_limit: 50,
                pagination_offset: 0,
                times_length: 1
            }
        };
    });
    it("Given new dates, Then dates must equal to the new dates and queryHasChanged must be true", () => {
        actions.setDatesAndReload(context, ["2015-09-14", "2017-04-24"]);
        expect(context.commit).toHaveBeenCalledWith("setParametersForNewQuery", [
            "2015-09-14",
            "2017-04-24"
        ]);
    });

    describe("loadFirstBatchOfTimes - success", () => {
        it("Given a succes response, When times are received, Then no message error is reveived", async () => {
            let times = [
                [
                    {
                        artifact: {},
                        project: {},
                        minutes: 20
                    }
                ]
            ];
            context.state.times = times;

            mockFetchSuccess(tlp.get, {
                headers: {
                    get: header_name => {
                        const headers = {
                            "X-PAGINATION-SIZE": 1
                        };

                        return headers[header_name];
                    }
                },
                return_json: times
            });

            await actions.loadFirstBatchOfTimes(context);
            expect(context.commit).toHaveBeenCalledWith("setIsLoading", true);
            expect(context.commit).toHaveBeenCalledWith("resetErrorMessage");
            expect(context.commit).toHaveBeenCalledWith("loadAChunkOfTimes", [times, 1]);
            expect(context.commit).toHaveBeenCalledWith("setIsLoading", false);
        });

        describe("getTimes - rest errors", () => {
            it("Given a rest error, When a json error message is received, Then the message is extracted in the component 's rest_error private property.", async () => {
                mockFetchError(tlp.get, {
                    error_json: {
                        error: {
                            code: 403,
                            message: "Forbidden"
                        }
                    }
                });

                await actions.getTimes(context);
                expect(context.commit).toHaveBeenCalledWith("resetErrorMessage");
                expect(context.commit).toHaveBeenCalledWith("setErrorMessage", "403 Forbidden");
            });

            it("Given a rest error, When a json error message is received, Then the message is extracted in the component 's rest_error private property.", async () => {
                mockFetchError(tlp.get, {});

                await actions.getTimes(context);
                expect(context.commit).toHaveBeenCalledWith("resetErrorMessage");
                expect(context.commit).toHaveBeenCalledWith("setErrorMessage", "An error occured");
            });
        });
    });
});
